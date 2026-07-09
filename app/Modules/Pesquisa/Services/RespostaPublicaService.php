<?php

namespace App\Modules\Pesquisa\Services;

use App\Modules\Pesquisa\Enums\StatusPesquisa;
use App\Modules\Pesquisa\Enums\StatusResposta;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaAcessoPublico;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use App\Modules\Pesquisa\Models\Setor;
use App\Modules\Pesquisa\Repositories\AcessoPublicoRepository;
use App\Modules\Pesquisa\Repositories\ConviteRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fluxo público (sem autenticação) de resposta a uma campanha, via link
 * INDIVIDUAL (um PesquisaConvite por colaborador) ou via link GLOBAL
 * (compartilhado, com controle de "uma resposta por dispositivo" através de
 * um token de sessão + IP). Em ambos os casos, a resposta gravada
 * (PesquisaResposta/Itens) nunca é referenciada por quem a enviou.
 */
class RespostaPublicaService
{
    public function __construct(
        private readonly ConviteRepository $conviteRepository,
        private readonly AcessoPublicoRepository $acessoPublicoRepository,
    ) {
    }

    // -------------------------------------------------------------------
    // Link individual (por convite)
    // -------------------------------------------------------------------

    public function validarToken(string $token): PesquisaConvite
    {
        $convite = $this->conviteRepository->buscarPorToken($token);
        abort_if(! $convite, 404, 'Link inválido ou não encontrado.');

        abort_if($convite->jaRespondeu(), 409, 'Você já respondeu esta pesquisa. Obrigado pela participação!');

        $this->garantirPesquisaDisponivel($convite->pesquisa);

        return $convite;
    }

    /**
     * @return array{pesquisa: Pesquisa, formulario: Formulario}
     */
    public function buscarEstrutura(string $token): array
    {
        $convite = $this->validarToken($token);

        return ['pesquisa' => $convite->pesquisa, 'formulario' => $this->carregarFormulario($convite->pesquisa)];
    }

    /**
     * @param  array<int, mixed>  $respostas
     * @param  array<int, string>  $observacoes
     */
    public function submeterRespostas(string $token, array $respostas, array $observacoes = []): PesquisaResposta
    {
        $convite = $this->validarToken($token);
        $formulario = $this->carregarFormulario($convite->pesquisa);
        $perguntas = $this->montarMapaPerguntas($formulario);

        $this->validarObrigatorias($perguntas, $respostas);

        return DB::transaction(function () use ($convite, $respostas, $observacoes, $perguntas) {
            $resposta = $this->criarRespostaComItens($convite->pesquisa_id, $convite->ghe_id, $perguntas, $respostas, $observacoes);
            $convite->update(['respondido_em' => now()]);

            return $resposta;
        });
    }

    // -------------------------------------------------------------------
    // Link global (compartilhado por sessão/dispositivo)
    // -------------------------------------------------------------------

    public function validarLinkGlobal(string $linkToken, string $sessaoToken, ?string $ip, ?string $userAgent): PesquisaAcessoPublico
    {
        $pesquisa = $this->acessoPublicoRepository->buscarPorPesquisaEToken($linkToken);
        abort_if(! $pesquisa, 404, 'Link inválido ou não encontrado.');

        $this->garantirPesquisaDisponivel($pesquisa);

        $acesso = $this->acessoPublicoRepository->buscarOuCriarSessao($pesquisa->id, $sessaoToken, $ip, $userAgent);
        abort_if($acesso->jaRespondeu(), 409, 'Você já respondeu esta pesquisa a partir deste dispositivo. Obrigado pela participação!');

        return $acesso;
    }

    /**
     * @return array{pesquisa: Pesquisa, formulario: Formulario}
     */
    public function buscarEstruturaGlobal(string $linkToken, string $sessaoToken, ?string $ip, ?string $userAgent): array
    {
        $acesso = $this->validarLinkGlobal($linkToken, $sessaoToken, $ip, $userAgent);
        $pesquisa = $acesso->pesquisa;

        return ['pesquisa' => $pesquisa, 'formulario' => $this->carregarFormulario($pesquisa)];
    }

    /**
     * Lista os setores da empresa da campanha, para o respondente do link
     * global se identificar por grupo (sem se identificar individualmente)
     * antes de responder — é o que permite tabular o resultado por GHE
     * mesmo nesse fluxo sem usuário autenticado.
     */
    public function listarSetoresParaSelecao(Pesquisa $pesquisa): Collection
    {
        return Setor::where('empresa_id', $pesquisa->empresa_id)->where('ativo', true)->orderBy('nome')->get();
    }

    /**
     * @param  array<int, mixed>  $respostas
     * @param  array<int, string>  $observacoes
     */
    public function submeterRespostasGlobal(
        string $linkToken,
        string $sessaoToken,
        ?string $ip,
        ?string $userAgent,
        array $respostas,
        array $observacoes = [],
        ?int $setorId = null,
    ): PesquisaResposta {
        $acesso = $this->validarLinkGlobal($linkToken, $sessaoToken, $ip, $userAgent);
        $formulario = $this->carregarFormulario($acesso->pesquisa);
        $perguntas = $this->montarMapaPerguntas($formulario);

        $this->validarObrigatorias($perguntas, $respostas);

        $gheId = $setorId
            ? Setor::where('empresa_id', $acesso->pesquisa->empresa_id)->find($setorId)?->ghe_id
            : null;

        return DB::transaction(function () use ($acesso, $respostas, $observacoes, $perguntas, $gheId) {
            $resposta = $this->criarRespostaComItens($acesso->pesquisa_id, $gheId, $perguntas, $respostas, $observacoes);
            $acesso->update(['respondido_em' => now(), 'ghe_id' => $gheId]);

            return $resposta;
        });
    }

    // -------------------------------------------------------------------
    // Lógica compartilhada
    // -------------------------------------------------------------------

    private function garantirPesquisaDisponivel(Pesquisa $pesquisa): void
    {
        abort_if($pesquisa->status !== StatusPesquisa::ATIVA, 409, 'Esta pesquisa não está mais disponível para resposta.');

        $hoje = now()->toDateString();
        if ($pesquisa->data_inicio && $hoje < $pesquisa->data_inicio->toDateString()) {
            abort(409, 'Esta pesquisa ainda não começou. Volte a partir de ' . $pesquisa->data_inicio->format('d/m/Y') . '.');
        }
        abort_if(
            $pesquisa->data_fim && $hoje > $pesquisa->data_fim->toDateString(),
            409,
            'O prazo para responder esta pesquisa foi encerrado.'
        );
    }

    private function carregarFormulario(Pesquisa $pesquisa): Formulario
    {
        return Formulario::with(['categorias.subcategorias.perguntas.conceito.itens'])
            ->findOrFail($pesquisa->formulario_id);
    }

    /**
     * @return array<int, Pergunta>
     */
    private function montarMapaPerguntas(Formulario $formulario): array
    {
        $perguntas = [];
        foreach ($formulario->categorias as $categoria) {
            foreach ($categoria->subcategorias as $subcategoria) {
                foreach ($subcategoria->perguntas as $pergunta) {
                    $perguntas[$pergunta->id] = $pergunta;
                }
            }
        }

        return $perguntas;
    }

    /**
     * @param  array<int, Pergunta>  $perguntas
     * @param  array<int, mixed>  $respostas
     */
    private function validarObrigatorias(array $perguntas, array $respostas): void
    {
        foreach ($perguntas as $perguntaId => $pergunta) {
            $valor = $respostas[$perguntaId] ?? null;
            $vazio = $valor === null || $valor === '' || (is_array($valor) && count($valor) === 0);
            abort_if($pergunta->obrigatoria && $vazio, 422, "A pergunta \"{$pergunta->texto}\" é obrigatória.");
        }
    }

    /**
     * @param  array<int, Pergunta>  $perguntas
     * @param  array<int, mixed>  $respostas
     * @param  array<int, string>  $observacoes
     */
    private function criarRespostaComItens(int $pesquisaId, ?int $gheId, array $perguntas, array $respostas, array $observacoes): PesquisaResposta
    {
        $resposta = PesquisaResposta::create([
            'pesquisa_id'   => $pesquisaId,
            'ghe_id'        => $gheId,
            'iniciado_em'   => now(),
            'finalizado_em' => now(),
            'status'        => StatusResposta::CONCLUIDA,
        ]);

        foreach ($respostas as $perguntaId => $valor) {
            $pergunta = $perguntas[$perguntaId] ?? null;
            if (! $pergunta || $valor === null || $valor === '' || (is_array($valor) && count($valor) === 0)) {
                continue;
            }

            $this->gravarItem($resposta, $pergunta, $valor);
        }

        foreach ($observacoes as $perguntaId => $texto) {
            $pergunta = $perguntas[$perguntaId] ?? null;
            if (! $pergunta || ! $pergunta->permite_observacao || trim((string) $texto) === '') {
                continue;
            }

            PesquisaRespostaItem::create([
                'pesquisa_resposta_id' => $resposta->id,
                'pergunta_id'          => $perguntaId,
                'observacao'           => trim((string) $texto),
            ]);
        }

        return $resposta;
    }

    private function gravarItem(PesquisaResposta $resposta, Pergunta $pergunta, mixed $valor): void
    {
        match ($pergunta->tipo_pergunta) {
            TipoPergunta::MULTIPLA_ESCOLHA => collect((array) $valor)->each(fn ($itemId) => PesquisaRespostaItem::create([
                'pesquisa_resposta_id' => $resposta->id,
                'pergunta_id'          => $pergunta->id,
                'conceito_item_id'     => $itemId,
            ])),
            TipoPergunta::ESCALA, TipoPergunta::UNICA_ESCOLHA => PesquisaRespostaItem::create([
                'pesquisa_resposta_id' => $resposta->id,
                'pergunta_id'          => $pergunta->id,
                'conceito_item_id'     => $valor,
            ]),
            TipoPergunta::NUMERO => PesquisaRespostaItem::create([
                'pesquisa_resposta_id' => $resposta->id,
                'pergunta_id'          => $pergunta->id,
                'valor_numero'         => $valor,
            ]),
            default => PesquisaRespostaItem::create([ // texto, data, sim_nao
                'pesquisa_resposta_id' => $resposta->id,
                'pergunta_id'          => $pergunta->id,
                'valor_texto'          => (string) $valor,
            ]),
        };
    }
}
