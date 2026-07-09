<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\DTOs\PesquisaData;
use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\RelatorioTecnico;
use App\Modules\Pesquisa\Models\Setor;
use App\Modules\Pesquisa\Services\PesquisaService;
use App\Modules\Pesquisa\Services\RelatorioTecnicoService;
use App\Modules\Pesquisa\Services\RespostaPublicaService;
use Illuminate\Database\Seeder;

/**
 * Executa o ciclo operacional COMPLETO do sistema — através dos mesmos
 * Services que a API/webAdm usam (PesquisaService, RespostaPublicaService,
 * RelatorioTecnicoService) — para o formulário COPSOQ II oficial: cria uma
 * campanha, distribui colaboradores de exemplo pelos GHEs, submete respostas
 * plausíveis por grupo (via o fluxo público real, com token individual) e
 * gera o Relatório Técnico (PDF) a partir do resultado dessa campanha.
 * Idempotente na parte de estrutura; reexecutar gera uma nova rodada de
 * respostas e um novo relatório (assim como aconteceria no uso real).
 */
class CopsoqCampanhaDemoSeeder extends Seeder
{
    /** Média-alvo por (GHE, fator de risco) — perfil inspirado no relatório técnico modelo (ARES ACM). */
    private const PERFIL = [
        'GHE 01 – Comercial e Relacionamento' => [
            'Gestão Organizacional' => 2.2, 'Contexto da Organização do Trabalho' => 2.6,
            'Relações Sociais no Trabalho' => 2.0, 'Conteúdo das Tarefas' => 1.9,
            'Condições do Ambiente de Trabalho' => 2.6, 'Interação Pessoa–Tarefa' => 2.3,
            'Jornada de Trabalho' => 3.2, 'Violência e Assédio Moral/Sexual' => 2.0,
            'Discriminação' => 1.8, 'Fatores Psicossociais Relacionados a Risco de Morte e Trauma' => 1.3,
            'Insegurança no Emprego' => 2.9,
        ],
        'GHE 02 – Operações e Suporte' => [
            'Gestão Organizacional' => 2.0, 'Contexto da Organização do Trabalho' => 2.3,
            'Relações Sociais no Trabalho' => 1.8, 'Conteúdo das Tarefas' => 1.9,
            'Condições do Ambiente de Trabalho' => 2.4, 'Interação Pessoa–Tarefa' => 1.5,
            'Jornada de Trabalho' => 2.3, 'Violência e Assédio Moral/Sexual' => 1.9,
            'Discriminação' => 1.7, 'Fatores Psicossociais Relacionados a Risco de Morte e Trauma' => 1.6,
            'Insegurança no Emprego' => 2.7,
        ],
    ];

    public function run(): void
    {
        $empresa = Empresa::where('cnpj', '12.345.678/0001-90')->first();
        $admin = User::where('email', 'admin@empresa.com')->first();
        $formulario = Formulario::where('codigo', 'copsoq-ii-versao-media')->first();

        if (! $empresa || ! $admin || ! $formulario) {
            $this->command->warn('CopsoqCampanhaDemoSeeder: rode EmpresaDemoSeeder, GheDemoSeeder e CopsoqFormularioSeeder primeiro.');

            return;
        }

        $colaboradores = $this->garantirColaboradores($empresa->id);
        $this->command?->info('CopsoqCampanhaDemoSeeder: '.count($colaboradores).' colaboradores de exemplo distribuídos pelos GHEs.');

        $pesquisaService = app(PesquisaService::class);
        $respostaService = app(RespostaPublicaService::class);
        $relatorioService = app(RelatorioTecnicoService::class);

        $pesquisa = \App\Modules\Pesquisa\Models\Pesquisa::where('formulario_id', $formulario->id)
            ->where('nome', 'Avaliação de Riscos Psicossociais — COPSOQ II 2026/1')
            ->first();

        if (! $pesquisa) {
            $pesquisa = $pesquisaService->criar(new PesquisaData(formularioId: $formulario->id, empresaId: $empresa->id), $admin);
            $pesquisa = $pesquisaService->atualizar($pesquisa->id, new PesquisaData(
                nome: 'Avaliação de Riscos Psicossociais — COPSOQ II 2026/1',
                descricao: 'Aplicação do COPSOQ II a toda a empresa, com resultados tabulados por GHE.',
                dataInicio: now()->subDays(20)->toDateString(),
                dataFim: now()->addDays(10)->toDateString(),
                anonima: true,
            ), $admin);
            $pesquisaService->definirPublico($pesquisa->id, 'toda_empresa', [], $admin);
            $pesquisa = $pesquisaService->publicar($pesquisa->id, $admin);
            $this->command?->info("CopsoqCampanhaDemoSeeder: campanha #{$pesquisa->id} publicada, convites gerados.");
        } else {
            $this->command?->info("CopsoqCampanhaDemoSeeder: reutilizando campanha #{$pesquisa->id} já existente.");
        }

        $perguntasPorCategoria = $formulario->categorias()->with('subcategorias.perguntas.conceito.itens')->get();

        $convites = PesquisaConvite::where('pesquisa_id', $pesquisa->id)->whereNull('respondido_em')->with('ghe')->get();
        $respondidos = 0;

        foreach ($convites as $convite) {
            $perfilGhe = self::PERFIL[$convite->ghe?->nome] ?? null;
            $respostas = $this->gerarRespostas($perguntasPorCategoria, $perfilGhe);

            $respostaService->submeterRespostas($convite->token, $respostas);
            $respondidos++;
        }

        $this->command?->info("CopsoqCampanhaDemoSeeder: {$respondidos} resposta(s) submetida(s) via fluxo público real (token individual).");

        $relatorio = $relatorioService->gerar($pesquisa->id, $admin, [
            'nome'     => 'Cinthia Santos Gadelha',
            'registro' => 'CRP 11/15242',
        ]);

        $this->command?->info("CopsoqCampanhaDemoSeeder: Relatório Técnico #{$relatorio->id} gerado em storage/app/private/{$relatorio->arquivo_path} (pesquisa #{$pesquisa->id}).");
    }

    /**
     * @return array<int, int> lista de IDs de Colaborador distribuídos pelos setores
     */
    private function garantirColaboradores(int $empresaId): array
    {
        $setores = Setor::where('empresa_id', $empresaId)->orderBy('id')->get();
        abort_if($setores->isEmpty(), 500, 'Nenhum setor encontrado — rode GheDemoSeeder primeiro.');

        // Distribuição inspirada no relatório modelo: GHE 01 (Comercial+Treinamento) maior, GHE 02 menor.
        $distribuicao = ['Comercial' => 16, 'Treinamento' => 6, 'Administrativo' => 4, 'Pós-Vendas' => 4];

        $idsCriados = [];
        foreach ($distribuicao as $nomeSetor => $quantidade) {
            $setor = $setores->firstWhere('nome', $nomeSetor);
            if (! $setor) {
                continue;
            }

            for ($i = 1; $i <= $quantidade; $i++) {
                $email = 'colaborador.'.\Illuminate\Support\Str::slug($nomeSetor).$i.'@empresademo.com';

                $colaborador = Colaborador::updateOrCreate(
                    ['empresa_id' => $empresaId, 'email' => $email],
                    [
                        'setor_id'         => $setor->id,
                        'nome'             => ucfirst($nomeSetor)." Colaborador {$i}",
                        'cargo'            => ucfirst($nomeSetor),
                        'ativo'            => true,
                        'origem'           => 'manual',
                        'base_legal_lgpd'  => 'Execução de política de saúde e segurança do trabalho (NR-1) e cumprimento de obrigação legal do empregador.',
                        'consentimento_em' => now(),
                    ]
                );

                $idsCriados[] = $colaborador->id;
            }
        }

        return $idsCriados;
    }

    /**
     * Gera respostas plausíveis para todas as perguntas do formulário, com
     * médias por categoria próximas do perfil do GHE do respondente (ou
     * neutras, se o convite não tiver GHE resolvido).
     *
     * @return array<int, int>
     */
    private function gerarRespostas($categorias, ?array $perfilGhe): array
    {
        $respostas = [];

        foreach ($categorias as $categoria) {
            $alvo = $perfilGhe[$categoria->categoria_referencia?->value] ?? 2.5;

            foreach ($categoria->subcategorias as $subcategoria) {
                foreach ($subcategoria->perguntas as $pergunta) {
                    $itens = $pergunta->conceito?->itens;
                    if (! $itens || $itens->isEmpty()) {
                        continue;
                    }

                    $valorAlvo = max(1, min(5, (int) round($alvo + random_int(-10, 10) / 10)));
                    $item = $itens->firstWhere('valor', $valorAlvo) ?? $itens->sortBy(fn ($it) => abs($it->valor - $valorAlvo))->first();

                    $respostas[$pergunta->id] = $item->id;
                }
            }
        }

        return $respostas;
    }
}
