<?php

namespace App\Modules\Pesquisa\Enums;

/**
 * Fase do ciclo PDCA (Plan–Do–Check–Act) de uma ação do plano de ação,
 * conforme a Seção 3.1 da metodologia. A ordem é sempre sequencial —
 * PlanoAcaoService::avancarFase() não permite pular etapas.
 */
enum FasePdca: string
{
    case PLANEJAR = 'planejar';
    case EXECUTAR = 'executar';
    case VERIFICAR = 'verificar';
    case AGIR = 'agir';

    public function label(): string
    {
        return match ($this) {
            self::PLANEJAR => 'Planejar',
            self::EXECUTAR => 'Executar',
            self::VERIFICAR => 'Verificar',
            self::AGIR => 'Agir',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::PLANEJAR => 'Ação, responsável e prazo definidos a partir da classificação de risco atual.',
            self::EXECUTAR => 'Ação em execução — aguarda registro de evidência para seguir à verificação.',
            self::VERIFICAR => 'Execução concluída — aguarda validação/parecer técnico.',
            self::AGIR => 'Verificada — aguarda avaliação de eficácia para encerrar ou reabrir o ciclo.',
        };
    }

    /** Próxima fase na sequência, ou null quando já está em Agir (fase final). */
    public function proxima(): ?self
    {
        return match ($this) {
            self::PLANEJAR => self::EXECUTAR,
            self::EXECUTAR => self::VERIFICAR,
            self::VERIFICAR => self::AGIR,
            self::AGIR => null,
        };
    }

    /**
     * @return self[] Todas as fases, na ordem do ciclo.
     */
    public static function ordem(): array
    {
        return [self::PLANEJAR, self::EXECUTAR, self::VERIFICAR, self::AGIR];
    }
}
