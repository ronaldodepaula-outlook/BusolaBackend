<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Pesquisa\Enums\CategoriaReferenciaCopsoqSimplificado;
use App\Modules\Pesquisa\Enums\ModeloCalculoRisco;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\PadraoFormulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Database\Seeder;

/**
 * Semeia o Padrão de Formulário global "COPSOQ II — Versão Resumida" e um
 * formulário global com as 35 perguntas reais de
 * documentos/planilha_perguntas_psicossociais.xlsx, organizadas nas 7
 * dimensões daquela planilha (Demandas, Controle, Apoio da chefia, Apoio dos
 * colegas, Relacionamentos, Cargo, Comunicação e mudanças), cada uma já
 * vinculada à sua severidade fixa via categoria_referencia.
 *
 * É um padrão GLOBAL (empresa_id nulo): qualquer empresa pode escolher este
 * formulário para uma campanha, e o resultado passa a ser calculado pelo
 * motor `ModeloCalculoRisco::COPSOQ_SIMPLIFICADO` automaticamente — nenhuma
 * campanha/formulário existente é afetada (o padrão NR-1 completo continua
 * sendo o default). Standalone — não faz parte do DatabaseSeeder::run(),
 * execute via `php artisan db:seed --class=CopsoqSimplificadoFormularioSeeder`.
 * Idempotente.
 */
class CopsoqSimplificadoFormularioSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('tipo', 'superadmin')->first();

        $padrao = PadraoFormulario::updateOrCreate(
            ['empresa_id' => null, 'nome' => 'COPSOQ II — Versão Resumida'],
            [
                'descricao'      => 'Questionário psicossocial resumido (7 dimensões: Demandas, Controle, Apoio da chefia, Apoio dos colegas, Relacionamentos, Cargo, Comunicação e mudanças). Fonte: planilha_perguntas_psicossociais.xlsx.',
                'ativo'          => true,
                'modelo_calculo' => ModeloCalculoRisco::COPSOQ_SIMPLIFICADO,
                'created_by'     => $superadmin?->id,
            ]
        );

        $escala = $this->criarEscalaFrequencia();

        $formulario = Formulario::updateOrCreate(
            ['codigo' => 'copsoq-ii-resumido', 'empresa_id' => null],
            [
                'nome'                 => 'COPSOQ II — Versão Resumida (Psicossocial)',
                'descricao'            => 'Instrumento psicossocial resumido, 7 dimensões, com motor de cálculo próprio (Probabilidade × Severidade = faixas Baixo/Moderado/Alto/Crítico).',
                'status'               => StatusFormulario::PUBLICADO,
                'tipo'                 => TipoFormulario::GLOBAL,
                'padrao_formulario_id' => $padrao->id,
                'versao'               => 1,
                'ativo'                => true,
                'created_by'           => $superadmin?->id,
                'updated_by'           => $superadmin?->id,
            ]
        );

        $ordemCategoria = 0;
        foreach ($this->perguntasPorDimensao() as $dimensaoValue => $perguntas) {
            $ref = CategoriaReferenciaCopsoqSimplificado::from($dimensaoValue);

            $categoria = Categoria::updateOrCreate(
                ['formulario_id' => $formulario->id, 'nome' => $ref->label()],
                [
                    'categoria_referencia' => $ref->value,
                    'severidade'           => $ref->severidadePadrao(),
                    'descricao'            => $ref->descricaoTecnica(),
                    'ordem'                => ++$ordemCategoria,
                    'ativo'                => true,
                ]
            );

            $subcategoria = Subcategoria::updateOrCreate(
                ['categoria_id' => $categoria->id, 'nome' => 'Perguntas'],
                ['formulario_id' => $formulario->id, 'ordem' => 1, 'ativo' => true]
            );

            $ordemPergunta = 0;
            foreach ($perguntas as $texto) {
                Pergunta::updateOrCreate(
                    ['subcategoria_id' => $subcategoria->id, 'texto' => $texto],
                    [
                        'formulario_id'      => $formulario->id,
                        'conceito_id'        => $escala->id,
                        'tipo_pergunta'      => TipoPergunta::ESCALA,
                        'obrigatoria'        => true,
                        'permite_observacao' => false,
                        'permite_anexo'      => false,
                        'ordem'              => ++$ordemPergunta,
                        'ativo'              => true,
                    ]
                );
            }
        }

        $this->command?->info('CopsoqSimplificadoFormularioSeeder: formulário COPSOQ II resumido (35 perguntas, 7 dimensões) semeado com sucesso.');
    }

    private function criarEscalaFrequencia(): Conceito
    {
        $conceito = Conceito::updateOrCreate(
            ['empresa_id' => null, 'nome' => 'Escala de Frequência COPSOQ II Resumido'],
            [
                'tipo'       => TipoConceito::FREQUENCIA,
                'descricao'  => 'Escala oficial de frequência (1 a 5): Nunca, Raramente, Às vezes, Frequentemente, Sempre.',
                'ativo'      => true,
            ]
        );

        $itens = [
            ['Nunca', 1, '#22c55e'],
            ['Raramente', 2, '#84cc16'],
            ['Às vezes', 3, '#eab308'],
            ['Frequentemente', 4, '#f97316'],
            ['Sempre', 5, '#ef4444'],
        ];

        foreach ($itens as $ordem => [$descricao, $valor, $cor]) {
            $conceito->itens()->updateOrCreate(
                ['descricao' => $descricao],
                ['valor' => $valor, 'cor' => $cor, 'ordem' => $ordem + 1]
            );
        }

        return $conceito->load('itens');
    }

    /**
     * As 35 perguntas reais da aba "PerguntasXProbabilidade" da planilha de
     * referência, agrupadas pela dimensão a que pertencem.
     *
     * @return array<string, string[]>
     */
    private function perguntasPorDimensao(): array
    {
        return [
            CategoriaReferenciaCopsoqSimplificado::DEMANDAS->value => [
                'As exigências de trabalho feitas por colegas e supervisores são difíceis de combinar',
                'Tenho prazos inatingíveis',
                'Devo trabalhar muito intensamente',
                'Eu não faço algumas tarefas porque tenho muita coisa para fazer',
                'Não tenho possibilidade de fazer pausas suficientes',
                'Recebo pressão para trabalhar em outro horário',
                'Tenho que fazer meu trabalho com muita rapidez',
                'As pausas temporárias são impossíveis de cumprir',
            ],
            CategoriaReferenciaCopsoqSimplificado::CONTROLE->value => [
                'Posso decidir quando fazer uma pausa',
                'Consideram a minha opinião sobre a velocidade do meu trabalho',
                'Tenho liberdade de escolha de como fazer meu trabalho',
                'Tenho liberdade de escolha para decidir o que fazer no meu trabalho',
                'Minhas sugestões são consideradas sobre como fazer meu trabalho',
                'O meu horário de trabalho pode ser flexível',
            ],
            CategoriaReferenciaCopsoqSimplificado::APOIO_CHEFIA->value => [
                'Recebo informações e suporte que me ajudam no trabalho que eu faço',
                'Posso confiar no meu chefe quando tiver problemas no trabalho',
                'Quando algo no trabalho me perturba ou irrita posso falar com meu chefe',
                'Tenho suportado trabalhos emocionalmente exigentes',
                'Meu chefe me incentiva no trabalho',
            ],
            CategoriaReferenciaCopsoqSimplificado::APOIO_COLEGAS->value => [
                'Quando o trabalho se torna difícil, posso contar com ajuda dos colegas',
                'Meus colegas me ajudam e me dão apoio quando eu preciso',
                'No trabalho os meus colegas demonstram o respeito que mereço',
                'Os colegas estão disponíveis para escutar os meus problemas de trabalho',
            ],
            CategoriaReferenciaCopsoqSimplificado::RELACIONAMENTOS->value => [
                'Falam ou se comportam comigo de forma dura',
                'Existem conflitos entre os colegas',
                'Sinto que sou perseguido no trabalho',
                'As relações no trabalho são tensas',
            ],
            CategoriaReferenciaCopsoqSimplificado::CARGO->value => [
                'Tenho clareza sobre o que se espera do meu trabalho',
                'Eu sei como fazer o meu trabalho',
                'Estão claras as minhas tarefas e responsabilidades',
                'Os objetivos e metas do meu setor são claros para mim',
                'Eu vejo como o meu trabalho se encaixa nos objetivos da empresa',
            ],
            CategoriaReferenciaCopsoqSimplificado::COMUNICACAO_MUDANCAS->value => [
                'Tenho oportunidades para pedir explicações ao chefe sobre as mudanças relacionadas ao meu trabalho',
                'As pessoas são sempre consultadas sobre as mudanças no trabalho',
                'Quando há mudanças, faço o meu trabalho com o mesmo carinho',
            ],
        ];
    }
}
