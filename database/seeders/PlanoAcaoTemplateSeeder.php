<?php

namespace Database\Seeders;

use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Enums\NivelBaseAcao;
use App\Modules\Pesquisa\Enums\TipoControle;
use App\Modules\Pesquisa\Models\PlanoAcaoTemplate;
use Illuminate\Database\Seeder;

/**
 * Semeia a biblioteca de referência de ações de plano de ação (aba BASE_ACAO
 * da planilha de cálculo oficial): para cada uma das 11 categorias oficiais
 * e cada um dos 3 tipos de controle, existe UMA ação-base fixa; os 5 níveis
 * de risco só alteram o prefixo de urgência, o "como executar" e o prazo
 * (ver Enums\NivelBaseAcao) — reproduzindo exatamente o padrão observado na
 * planilha (165 linhas = 11 categorias × 5 níveis × 3 tipos de controle).
 * Conteúdo global, não pertence a nenhuma empresa. Idempotente.
 */
class PlanoAcaoTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $acoesBase = $this->acoesBasePorCategoria();
        $total = 0;

        foreach ($acoesBase as $categoriaRef => $porTipo) {
            foreach ($porTipo as $tipoControle => $acao) {
                foreach (NivelBaseAcao::cases() as $nivel) {
                    PlanoAcaoTemplate::updateOrCreate(
                        [
                            'categoria_referencia' => $categoriaRef,
                            'nivel_base_acao'      => $nivel->value,
                            'tipo_controle'        => $tipoControle,
                        ],
                        [
                            'acao'                => $acao,
                            'como_executar'        => $nivel->comoExecutarPadrao(),
                            'evidencia'            => NivelBaseAcao::EVIDENCIA_PADRAO,
                            'responsavel_padrao'   => NivelBaseAcao::RESPONSAVEL_PADRAO,
                            'prazo'                => $nivel->prazoPadrao(),
                        ]
                    );
                    $total++;
                }
            }
        }

        $this->command?->info("PlanoAcaoTemplateSeeder: {$total} templates de plano de ação semeados.");
    }

    /**
     * @return array<string, array<string, string>> categoria_referencia => tipo_controle => ação-base
     */
    private function acoesBasePorCategoria(): array
    {
        return [
            CategoriaReferencia::GESTAO_ORGANIZACIONAL->value => [
                TipoControle::ESTRUTURAL->value => 'revisar estrutura de liderança e dimensionamento gerencial',
                TipoControle::ADMINISTRATIVO->value => 'formalizar rotinas de feedback, pdi e indicadores de gestão',
                TipoControle::PSICOSSOCIAL->value => 'capacitar líderes em feedback, conflitos, cnv e segurança psicológica',
            ],
            CategoriaReferencia::CONTEXTO_ORGANIZACAO->value => [
                TipoControle::ESTRUTURAL->value => 'redesenhar fluxos e processos críticos',
                TipoControle::ADMINISTRATIVO->value => 'formalizar papéis, responsabilidades e comunicação',
                TipoControle::PSICOSSOCIAL->value => 'capacitar equipes em alinhamento e colaboração',
            ],
            CategoriaReferencia::RELACOES_SOCIAIS->value => [
                TipoControle::ESTRUTURAL->value => 'estruturar mediação de conflitos e suporte entre equipes',
                TipoControle::ADMINISTRATIVO->value => 'criar rituais de alinhamento e cooperação',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em comunicação interpessoal e gestão de conflitos',
            ],
            CategoriaReferencia::CONTEUDO_TAREFAS->value => [
                TipoControle::ESTRUTURAL->value => 'redistribuir tarefas e revisar metas',
                TipoControle::ADMINISTRATIVO->value => 'padronizar prioridades e critérios de execução',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em gestão do tempo e manejo do estresse',
            ],
            CategoriaReferencia::CONDICOES_AMBIENTE->value => [
                TipoControle::ESTRUTURAL->value => 'adequar ergonomia, iluminação, ruído e conforto térmico',
                TipoControle::ADMINISTRATIVO->value => 'implantar checklists e rotina de inspeção',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em autocuidado e prevenção de fadiga',
            ],
            CategoriaReferencia::INTERACAO_PESSOA_TAREFA->value => [
                TipoControle::ESTRUTURAL->value => 'ajustar função, autonomia e uso de competências',
                TipoControle::ADMINISTRATIVO->value => 'revisar descrições de cargo e responsabilidades',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em autonomia e tomada de decisão',
            ],
            CategoriaReferencia::JORNADA_TRABALHO->value => [
                TipoControle::ESTRUTURAL->value => 'revisar escalas, pausas e dimensionamento',
                TipoControle::ADMINISTRATIVO->value => 'monitorar horas extras, banco de horas e absenteísmo',
                TipoControle::PSICOSSOCIAL->value => 'capacitar líderes em prevenção de fadiga e burnout',
            ],
            CategoriaReferencia::VIOLENCIA_ASSEDIO->value => [
                TipoControle::ESTRUTURAL->value => 'implantar canal seguro de denúncia e proteção',
                TipoControle::ADMINISTRATIVO->value => 'formalizar protocolo de investigação e resposta',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em prevenção ao assédio e respeito',
            ],
            CategoriaReferencia::DISCRIMINACAO->value => [
                TipoControle::ESTRUTURAL->value => 'implantar práticas de equidade e inclusão',
                TipoControle::ADMINISTRATIVO->value => 'formalizar política antidiscriminação e canal de denúncia',
                TipoControle::PSICOSSOCIAL->value => 'capacitar equipes em diversidade e inclusão',
            ],
            CategoriaReferencia::RISCO_MORTE_TRAUMA->value => [
                TipoControle::ESTRUTURAL->value => 'revisar protocolos de prevenção e resposta a incidentes críticos',
                TipoControle::ADMINISTRATIVO->value => 'formalizar fluxo de suporte pós-evento crítico',
                TipoControle::PSICOSSOCIAL->value => 'capacitar em gestão de crise e primeiros socorros psicológicos',
            ],
            CategoriaReferencia::DESEMPREGO_INSEGURANCA->value => [
                TipoControle::ESTRUTURAL->value => 'reforçar transparência sobre mudanças organizacionais',
                TipoControle::ADMINISTRATIVO->value => 'formalizar critérios de carreira e movimentação',
                TipoControle::PSICOSSOCIAL->value => 'capacitar líderes em comunicação de mudanças',
            ],
        ];
    }
}
