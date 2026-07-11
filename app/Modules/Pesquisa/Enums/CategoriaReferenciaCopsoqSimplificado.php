<?php

namespace App\Modules\Pesquisa\Enums;

use App\Modules\Pesquisa\Contracts\FatorRiscoReferenciaInterface;

/**
 * As 7 dimensões do padrão "COPSOQ II resumido", fonte:
 * documentos/planilha_perguntas_psicossociais.xlsx (abas
 * "PerguntasXProbabilidade" e "Gravidade"). Padrão alternativo ao NR-1
 * completo ({@see CategoriaReferencia}), selecionável via Padrão de
 * Formulário (`ModeloCalculoRisco::COPSOQ_SIMPLIFICADO`).
 *
 * Diferente do padrão NR-1 completo, esta planilha de referência não define
 * um catálogo de Plano de Ação (não há aba equivalente a BASE_ACAO) — por
 * isso {@see NivelRiscoCopsoqSimplificado::nivelBaseAcao()} sempre retorna
 * null e nenhuma ação é gerada automaticamente para categorias deste padrão.
 */
enum CategoriaReferenciaCopsoqSimplificado: string implements FatorRiscoReferenciaInterface
{
    case DEMANDAS = 'Demandas';
    case CONTROLE = 'Controle';
    case APOIO_CHEFIA = 'Apoio da chefia';
    case APOIO_COLEGAS = 'Apoio dos colegas';
    case RELACIONAMENTOS = 'Relacionamentos';
    case CARGO = 'Cargo';
    case COMUNICACAO_MUDANCAS = 'Comunicação e mudanças';

    public function label(): string
    {
        return $this->value;
    }

    /** Severidade (S) fixa oficial — aba "Gravidade" da planilha de referência. */
    public function severidadePadrao(): int
    {
        return match ($this) {
            self::DEMANDAS => 5,
            self::CONTROLE => 3,
            self::APOIO_CHEFIA => 4,
            self::APOIO_COLEGAS => 3,
            self::RELACIONAMENTOS => 5,
            self::CARGO => 2,
            self::COMUNICACAO_MUDANCAS => 3,
        };
    }

    /** Anexo I — descrição técnica da dimensão (colunas "O que significa"/"O que avalia"/"Possíveis impactos" da planilha). */
    public function descricaoTecnica(): string
    {
        return match ($this) {
            self::DEMANDAS => 'Quantidade, intensidade e pressão do trabalho. Avalia sobrecarga, ritmo acelerado, pressão por resultados, excesso de tarefas e dificuldade de pausas. Pode gerar estresse, fadiga, esgotamento, erros e adoecimento mental.',
            self::CONTROLE => 'Grau de autonomia sobre o trabalho. Avalia a liberdade para decidir como trabalhar, a participação nas decisões e a flexibilidade de horário. Pode gerar sensação de impotência, desmotivação e baixa autonomia.',
            self::APOIO_CHEFIA => 'Qualidade do suporte oferecido pela liderança. Avalia comunicação, orientação, incentivo, acolhimento e suporte emocional/técnico do chefe imediato. Pode gerar sofrimento psicológico, insegurança e conflitos com a liderança.',
            self::APOIO_COLEGAS => 'Qualidade do suporte social entre equipes. Avalia cooperação, ajuda mútua, respeito e trabalho em equipe entre colegas. Pode gerar isolamento, individualismo e ambiente pouco colaborativo.',
            self::RELACIONAMENTOS => 'Qualidade das relações interpessoais no ambiente de trabalho. Avalia conflitos, tensões, hostilidade, perseguições e tratamento agressivo entre colegas. Pode gerar clima tóxico, assédio moral e sofrimento emocional.',
            self::CARGO => 'Clareza sobre o papel e as responsabilidades exercidas. Avalia o entendimento das funções, metas, responsabilidades e objetivos do setor. Pode gerar confusão de papéis, insegurança e retrabalho.',
            self::COMUNICACAO_MUDANCAS => 'Forma como a empresa comunica e conduz mudanças. Avalia transparência, participação, alinhamento e qualidade da comunicação organizacional. Pode gerar ansiedade, insegurança, resistência a mudanças e boatos.',
        };
    }

    /**
     * A planilha de referência deste padrão (diferente da planilha do NR-1
     * completo) não relaciona CID/doenças por dimensão — retorna vazio de
     * propósito, para não inventar conteúdo clínico sem fonte.
     *
     * @return string[]
     */
    public function doencasRelacionadas(): array
    {
        return [];
    }
}
