<?php

namespace App\Modules\Pesquisa\Enums;

use App\Modules\Pesquisa\Contracts\FatorRiscoReferenciaInterface;

/**
 * Os 11 fatores de risco psicossociais oficiais do COPSOQ II / Portaria GM/MS
 * nº 5.674/2024, usados como referência opcional de uma Categoria de
 * formulário para acionar o RiscoCalculator (severidade fixa) e o catálogo de
 * templates de Plano de Ação. Este é o padrão "NR-1 completo" — ver também
 * {@see CategoriaReferenciaCopsoqSimplificado} para o padrão COPSOQ II
 * resumido (7 dimensões), selecionável via Padrão de Formulário.
 *
 * O valor de cada case é a chave usada tanto na tabela `pesq_categorias`
 * quanto em `pesq_plano_acao_templates.categoria_referencia` — mantém-se
 * idêntico ao texto da coluna "Categoria" da planilha de referência (BASE_ACAO)
 * para permitir o join direto. label() retorna o nome mais completo usado nos
 * relatórios (Seção 3.7 do relatório técnico modelo), que difere ligeiramente
 * em alguns casos.
 */
enum CategoriaReferencia: string implements FatorRiscoReferenciaInterface
{
    case GESTAO_ORGANIZACIONAL = 'Gestão Organizacional';
    case CONTEXTO_ORGANIZACAO = 'Contexto da Organização do Trabalho';
    case RELACOES_SOCIAIS = 'Relações Sociais no Trabalho';
    case CONTEUDO_TAREFAS = 'Conteúdo das Tarefas';
    case CONDICOES_AMBIENTE = 'Condições do Ambiente de Trabalho';
    case INTERACAO_PESSOA_TAREFA = 'Interação Pessoa–Tarefa';
    case JORNADA_TRABALHO = 'Jornada de Trabalho';
    case VIOLENCIA_ASSEDIO = 'Violência e Assédio Moral/Sexual';
    case DISCRIMINACAO = 'Discriminação';
    case RISCO_MORTE_TRAUMA = 'Fatores Psicossociais Relacionados a Risco de Morte e Trauma';
    case DESEMPREGO_INSEGURANCA = 'Insegurança no Emprego';

    public function label(): string
    {
        return match ($this) {
            self::GESTAO_ORGANIZACIONAL => 'Gestão Organizacional',
            self::CONTEXTO_ORGANIZACAO => 'Contexto da Organização do Trabalho',
            self::RELACOES_SOCIAIS => 'Relações Sociais no Trabalho',
            self::CONTEUDO_TAREFAS => 'Conteúdo das Tarefas do Trabalho',
            self::CONDICOES_AMBIENTE => 'Condições do Ambiente de Trabalho',
            self::INTERACAO_PESSOA_TAREFA => 'Interação Pessoa–Tarefa',
            self::JORNADA_TRABALHO => 'Jornada de Trabalho',
            self::VIOLENCIA_ASSEDIO => 'Violência e Assédio Moral/Sexual no Trabalho',
            self::DISCRIMINACAO => 'Discriminação no Trabalho',
            self::RISCO_MORTE_TRAUMA => 'Risco de Morte e Trauma no Trabalho',
            self::DESEMPREGO_INSEGURANCA => 'Desemprego / Insegurança Ocupacional',
        };
    }

    /**
     * Severidade (S) fixa oficial — Seção 3.7 do relatório técnico modelo /
     * aba SEVERIDADE da planilha de referência. Não é calculada a partir de
     * nenhuma resposta.
     */
    public function severidadePadrao(): int
    {
        return match ($this) {
            self::GESTAO_ORGANIZACIONAL => 3,
            self::CONTEXTO_ORGANIZACAO => 3,
            self::RELACOES_SOCIAIS => 3,
            self::CONTEUDO_TAREFAS => 3,
            self::CONDICOES_AMBIENTE => 2,
            self::INTERACAO_PESSOA_TAREFA => 2,
            self::JORNADA_TRABALHO => 4,
            self::VIOLENCIA_ASSEDIO => 5,
            self::DISCRIMINACAO => 4,
            self::RISCO_MORTE_TRAUMA => 5,
            self::DESEMPREGO_INSEGURANCA => 3,
        };
    }

    /** Anexo I — descrição técnica do fator de risco. */
    public function descricaoTecnica(): string
    {
        return match ($this) {
            self::GESTAO_ORGANIZACIONAL => 'Refere-se ao modelo de gestão, estilo de liderança, pressão por metas, reconhecimento, comunicação institucional, apoio organizacional e previsibilidade das decisões. Ambientes com liderança tóxica, pressão excessiva e ausência de suporte favorecem sofrimento psíquico crônico.',
            self::CONTEXTO_ORGANIZACAO => 'Relaciona-se à organização das atividades, autonomia, clareza de funções, distribuição de demandas, estabilidade, ritmo de trabalho e controle organizacional. Ambiguidade de papéis e desorganização aumentam o desgaste emocional e cognitivo.',
            self::RELACOES_SOCIAIS => 'Engloba a qualidade das relações interpessoais entre equipes, lideranças e colegas, incluindo apoio social, conflitos, isolamento, comunicação e respeito no ambiente laboral. Relações deterioradas favorecem sofrimento emocional persistente.',
            self::CONTEUDO_TAREFAS => 'Relaciona-se às características das tarefas executadas: monotonia, repetitividade, excesso de responsabilidade, sobrecarga emocional, atividades sem significado ou baixa utilização das competências do trabalhador.',
            self::CONDICOES_AMBIENTE => 'Refere-se às condições físicas e estruturais do ambiente laboral, incluindo ruído, temperatura, ergonomia, segurança física, iluminação, equipamentos e conforto ambiental. Ambientes inseguros ou inadequados potencializam adoecimento mental e físico ocupacional.',
            self::INTERACAO_PESSOA_TAREFA => 'Relaciona-se à compatibilidade entre as exigências físicas, emocionais e cognitivas da função e a capacidade do trabalhador para executá-la. Sobrecarga cognitiva e emocional contínua aumentam o risco de esgotamento mental.',
            self::JORNADA_TRABALHO => 'Relaciona-se à duração e intensidade do trabalho, incluindo jornadas prolongadas, trabalho noturno, ausência de pausas, excesso de horas extras e dificuldade de recuperação física e mental.',
            self::VIOLENCIA_ASSEDIO => 'Abrange situações de humilhação, constrangimento, perseguição, abuso de poder, violência psicológica, assédio moral e assédio sexual. É um dos fatores psicossociais com maior potencial de dano à saúde mental do trabalhador.',
            self::DISCRIMINACAO => 'Refere-se a práticas discriminatórias relacionadas a gênero, raça, orientação sexual, idade, deficiência, religião ou qualquer outra condição individual. Ambientes discriminatórios favorecem exclusão social e sofrimento psíquico contínuo.',
            self::RISCO_MORTE_TRAUMA => 'Relaciona-se à exposição frequente a acidentes graves, violência, emergências, perdas humanas, situações críticas e eventos traumáticos durante o exercício laboral. Muito presente em atividades de saúde, segurança, resgate e transporte.',
            self::DESEMPREGO_INSEGURANCA => 'Refere-se ao medo constante de perda do emprego, instabilidade financeira, insegurança sobre permanência na organização e ausência de perspectivas profissionais. A insegurança ocupacional prolongada é fator relevante para sofrimento mental crônico.',
        };
    }

    /**
     * Anexo I — possíveis doenças relacionadas ao trabalho (CID), apenas
     * conteúdo de referência técnica (não gera diagnóstico individual).
     *
     * @return string[]
     */
    public function doencasRelacionadas(): array
    {
        return match ($this) {
            self::GESTAO_ORGANIZACIONAL => [
                'Episódio depressivo – CID F32', 'Transtorno depressivo recorrente – CID F33',
                'Transtornos ansiosos – CID F41', 'Reações ao estresse grave e transtornos de adaptação – CID F43',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Outros transtornos neuróticos relacionados ao trabalho – CID F48',
            ],
            self::CONTEXTO_ORGANIZACAO => [
                'Transtornos ansiosos – CID F41', 'Transtornos de adaptação – CID F43.2',
                'Episódio depressivo – CID F32', 'Síndrome de Burnout – CID QD85 / Z73.0',
                'Neurastenia / fadiga mental – CID F48.0',
            ],
            self::RELACOES_SOCIAIS => [
                'Episódio depressivo – CID F32', 'Transtorno depressivo recorrente – CID F33',
                'Transtornos ansiosos – CID F41', 'Transtornos de adaptação – CID F43.2',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos somatoformes – CID F45',
            ],
            self::CONTEUDO_TAREFAS => [
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Episódio depressivo – CID F32',
                'Transtornos ansiosos – CID F41', 'Neurastenia / fadiga mental – CID F48.0',
                'Transtornos do sono relacionados ao trabalho – CID G47',
            ],
            self::CONDICOES_AMBIENTE => [
                'Transtornos ansiosos – CID F41', 'Estresse ocupacional – CID Z73',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos do sono relacionados ao trabalho – CID G47',
                'Transtornos somatoformes – CID F45',
            ],
            self::INTERACAO_PESSOA_TAREFA => [
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos ansiosos – CID F41',
                'Episódio depressivo – CID F32', 'Transtorno depressivo recorrente – CID F33',
                'Neurastenia / fadiga mental – CID F48.0', 'Transtornos de adaptação – CID F43.2',
            ],
            self::JORNADA_TRABALHO => [
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos do sono relacionados ao trabalho – CID G47',
                'Transtornos ansiosos – CID F41', 'Episódio depressivo – CID F32',
                'Fadiga relacionada ao trabalho – CID Z73',
            ],
            self::VIOLENCIA_ASSEDIO => [
                'Transtorno de estresse pós-traumático – CID F43.1', 'Reação aguda ao estresse – CID F43.0',
                'Episódio depressivo grave – CID F32.2/F32.3', 'Transtornos ansiosos – CID F41',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos dissociativos relacionados ao trauma – CID F44',
            ],
            self::DISCRIMINACAO => [
                'Episódio depressivo – CID F32', 'Transtorno depressivo recorrente – CID F33',
                'Transtornos ansiosos – CID F41', 'Transtornos de adaptação – CID F43.2',
                'Síndrome de Burnout – CID QD85 / Z73.0',
            ],
            self::RISCO_MORTE_TRAUMA => [
                'Transtorno de estresse pós-traumático – CID F43.1', 'Reação aguda ao estresse – CID F43.0',
                'Transtornos ansiosos – CID F41', 'Episódio depressivo – CID F32',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos relacionados ao uso de álcool e substâncias – CID F10/F19',
            ],
            self::DESEMPREGO_INSEGURANCA => [
                'Transtornos ansiosos – CID F41', 'Episódio depressivo – CID F32',
                'Transtorno depressivo recorrente – CID F33', 'Transtornos de adaptação – CID F43.2',
                'Síndrome de Burnout – CID QD85 / Z73.0', 'Transtornos do sono relacionados ao trabalho – CID G47',
            ],
        };
    }
}
