<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\StatusPesquisa;
use App\Modules\Pesquisa\Enums\StatusResposta;
use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\ConceitoItem;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\PesquisaRespostaItem;
use App\Modules\Pesquisa\Models\Subcategoria;
use App\Modules\Pesquisa\Services\ConviteService;
use Illuminate\Database\Seeder;

/**
 * Popula um exemplo completo do módulo de Pesquisas Psicossociais, usando a
 * mesma "Empresa Demo" / usuário admin@empresa.com criados pelo
 * EmpresaDemoSeeder: 3 conceitos de avaliação com itens, 1 formulário
 * publicado com árvore completa (categorias → subcategorias → perguntas de
 * vários tipos), 1 campanha ativa com convites (links individuais) gerados
 * para todos os usuários da empresa, e 1 resposta de exemplo já registrada
 * (sem nenhuma ligação ao convite/usuário, como no fluxo real). Idempotente
 * — seguro rodar de novo.
 */
class PesquisaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::where('cnpj', '12.345.678/0001-90')->first();
        $admin   = User::where('email', 'admin@empresa.com')->first();

        if (! $empresa || ! $admin) {
            $this->command->warn('PesquisaDemoSeeder: execute EmpresaDemoSeeder primeiro (empresa/usuário demo não encontrados).');
            return;
        }

        // ── 1. Conceitos de avaliação ────────────────────────────────────────
        $escalaFrequencia = $this->criarConceito($empresa->id, 'Escala de Frequência', TipoConceito::FREQUENCIA, [
            ['Nunca', 1, '#22c55e'],
            ['Raramente', 2, '#84cc16'],
            ['Às vezes', 3, '#eab308'],
            ['Frequentemente', 4, '#f97316'],
            ['Sempre', 5, '#ef4444'],
        ]);

        $escalaConcordancia = $this->criarConceito($empresa->id, 'Escala de Concordância', TipoConceito::ESCALA_LIKERT, [
            ['Discordo totalmente', 1, '#ef4444'],
            ['Discordo', 2, '#f97316'],
            ['Neutro', 3, '#eab308'],
            ['Concordo', 4, '#84cc16'],
            ['Concordo totalmente', 5, '#22c55e'],
        ]);

        $escalaSatisfacao = $this->criarConceito($empresa->id, 'Escala de Satisfação', TipoConceito::ESCALA_LIKERT, [
            ['Muito insatisfeito', 1, '#ef4444'],
            ['Insatisfeito', 2, '#f97316'],
            ['Neutro', 3, '#eab308'],
            ['Satisfeito', 4, '#84cc16'],
            ['Muito satisfeito', 5, '#22c55e'],
        ]);

        // ── 2. Formulário publicado ──────────────────────────────────────────
        $formulario = Formulario::updateOrCreate(
            ['codigo' => 'nr1-riscos-psicossociais-demo', 'empresa_id' => $empresa->id],
            [
                'nome'       => 'Avaliação de Riscos Psicossociais - NR-1 (Demo)',
                'descricao'  => 'Formulário de exemplo cobrindo carga de trabalho, ambiente organizacional, relacionamento e saúde mental.',
                'status'     => StatusFormulario::PUBLICADO,
                'tipo'       => TipoFormulario::EMPRESA,
                'versao'     => 1,
                'ativo'      => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // ── 3. Árvore categoria → subcategoria → pergunta ────────────────────
        $estrutura = [
            'Carga de Trabalho' => [
                'Excesso de demandas' => [
                    ['escala', 'Com que frequência você sente que tem mais trabalho do que consegue dar conta?', $escalaFrequencia],
                    ['escala', 'Com que frequência você precisa levar trabalho para casa?', $escalaFrequencia],
                ],
                'Prazos' => [
                    ['escala', 'Com que frequência os prazos estabelecidos são difíceis de cumprir?', $escalaFrequencia],
                    ['sim_nao', 'Você participa da definição dos prazos das suas atividades?', null],
                ],
            ],
            'Ambiente Organizacional' => [
                'Comunicação' => [
                    ['unica_escolha', 'Você recebe informações claras sobre mudanças na empresa?', $escalaConcordancia],
                    ['sim_nao', 'Você sabe a quem recorrer em caso de dúvidas sobre seu trabalho?', null],
                ],
                'Reconhecimento' => [
                    ['unica_escolha', 'Meu trabalho é reconhecido pelos meus superiores.', $escalaConcordancia],
                ],
            ],
            'Relacionamento' => [
                'Assédio' => [
                    ['unica_escolha', 'Você já presenciou situações de assédio moral no ambiente de trabalho?', $escalaConcordancia],
                ],
                'Liderança' => [
                    ['unica_escolha', 'Minha liderança direta trata a equipe com respeito.', $escalaConcordancia],
                ],
            ],
            'Saúde Mental' => [
                'Bem-estar' => [
                    ['unica_escolha', 'De forma geral, qual seu nível de satisfação com seu trabalho atualmente?', $escalaSatisfacao],
                    ['texto', 'Descreva como você tem se sentido no trabalho ultimamente.', null],
                ],
            ],
        ];

        $ordemCategoria = 0;
        foreach ($estrutura as $nomeCategoria => $subcategorias) {
            $categoria = Categoria::updateOrCreate(
                ['formulario_id' => $formulario->id, 'nome' => $nomeCategoria],
                ['ordem' => ++$ordemCategoria, 'ativo' => true]
            );

            $ordemSub = 0;
            foreach ($subcategorias as $nomeSubcategoria => $perguntas) {
                $subcategoria = Subcategoria::updateOrCreate(
                    ['categoria_id' => $categoria->id, 'nome' => $nomeSubcategoria],
                    ['formulario_id' => $formulario->id, 'ordem' => ++$ordemSub, 'ativo' => true]
                );

                $ordemPergunta = 0;
                foreach ($perguntas as [$tipo, $texto, $conceito]) {
                    Pergunta::updateOrCreate(
                        ['subcategoria_id' => $subcategoria->id, 'texto' => $texto],
                        [
                            'formulario_id'      => $formulario->id,
                            'conceito_id'        => $conceito?->id,
                            'tipo_pergunta'      => TipoPergunta::from($tipo),
                            'obrigatoria'        => true,
                            'permite_observacao' => $tipo === 'texto',
                            'permite_anexo'      => false,
                            'ordem'              => ++$ordemPergunta,
                            'ativo'              => true,
                        ]
                    );
                }
            }
        }

        // ── 4. Campanha (Pesquisa) ativa, público-alvo = toda a empresa ──────
        $pesquisa = Pesquisa::updateOrCreate(
            ['formulario_id' => $formulario->id, 'nome' => 'Campanha Demo 2026/1'],
            [
                'empresa_id'  => $empresa->id,
                'descricao'   => 'Campanha de exemplo aplicando o formulário NR-1 (Demo) a toda a empresa.',
                'data_inicio' => now()->toDateString(),
                'data_fim'    => now()->addDays(30)->toDateString(),
                'anonima'     => true,
                'status'      => StatusPesquisa::ATIVA,
                'criado_por'  => $admin->id,
            ]
        );

        // ── 4b. Colaboradores de exemplo (alvo do convite individual) ────────
        foreach (['ana.demo@empresademo.com' => 'Ana Beatriz Souza', 'bruno.demo@empresademo.com' => 'Bruno Costa Lima'] as $email => $nome) {
            Colaborador::updateOrCreate(
                ['empresa_id' => $empresa->id, 'email' => $email],
                ['nome' => $nome, 'ativo' => true, 'origem' => 'manual']
            );
        }

        // ── 5. Convites (links individuais) para todos os colaboradores da empresa ─
        $criados = app(ConviteService::class)->gerarConvites($pesquisa);

        // ── 6. Uma resposta de exemplo já registrada (anônima, sem FK ao convite) ─
        $primeiraPergunta = Pergunta::where('formulario_id', $formulario->id)
            ->where('tipo_pergunta', TipoPergunta::ESCALA)
            ->first();
        $perguntaSimNao = Pergunta::where('formulario_id', $formulario->id)
            ->where('tipo_pergunta', TipoPergunta::SIM_NAO)
            ->first();
        $perguntaTexto = Pergunta::where('formulario_id', $formulario->id)
            ->where('tipo_pergunta', TipoPergunta::TEXTO)
            ->first();

        if ($primeiraPergunta && ! PesquisaResposta::where('pesquisa_id', $pesquisa->id)->exists()) {
            $resposta = PesquisaResposta::create([
                'pesquisa_id'   => $pesquisa->id,
                'iniciado_em'   => now(),
                'finalizado_em' => now(),
                'status'        => StatusResposta::CONCLUIDA,
            ]);

            $itemFrequente = $escalaFrequencia->itens()->orderByDesc('valor')->first();
            if ($itemFrequente) {
                PesquisaRespostaItem::create([
                    'pesquisa_resposta_id' => $resposta->id,
                    'pergunta_id'          => $primeiraPergunta->id,
                    'conceito_item_id'     => $itemFrequente->id,
                ]);
            }
            if ($perguntaSimNao) {
                PesquisaRespostaItem::create([
                    'pesquisa_resposta_id' => $resposta->id,
                    'pergunta_id'          => $perguntaSimNao->id,
                    'valor_texto'          => 'sim',
                ]);
            }
            if ($perguntaTexto) {
                PesquisaRespostaItem::create([
                    'pesquisa_resposta_id' => $resposta->id,
                    'pergunta_id'          => $perguntaTexto->id,
                    'valor_texto'          => 'Exemplo de resposta de texto livre para demonstração do dashboard de resultados.',
                ]);
            }

            // Marca um convite qualquer como respondido (sem ligar à resposta acima)
            PesquisaConvite::where('pesquisa_id', $pesquisa->id)->whereNull('respondido_em')->first()?->update(['respondido_em' => now()]);
        }

        $this->command->info("PesquisaDemoSeeder: formulário \"{$formulario->nome}\" (ID {$formulario->id}), campanha \"{$pesquisa->nome}\" (ID {$pesquisa->id}) e {$criados} convite(s) criados/atualizados para a Empresa Demo.");
    }

    /**
     * @param  array<int, array{0: string, 1: float, 2: string}>  $itens
     */
    private function criarConceito(int $empresaId, string $nome, TipoConceito $tipo, array $itens): Conceito
    {
        $conceito = Conceito::updateOrCreate(
            ['empresa_id' => $empresaId, 'nome' => $nome],
            ['tipo' => $tipo, 'ativo' => true]
        );

        foreach ($itens as $ordem => [$descricao, $valor, $cor]) {
            ConceitoItem::updateOrCreate(
                ['conceito_id' => $conceito->id, 'descricao' => $descricao],
                ['valor' => $valor, 'cor' => $cor, 'ordem' => $ordem + 1]
            );
        }

        return $conceito;
    }
}
