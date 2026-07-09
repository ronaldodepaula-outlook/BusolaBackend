<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\User;
use App\Modules\Pesquisa\Enums\Eficacia;
use App\Modules\Pesquisa\Enums\FasePdca;
use App\Modules\Pesquisa\Enums\NivelRisco;
use App\Modules\Pesquisa\Enums\StatusPlanoAcao;
use App\Modules\Pesquisa\Enums\TipoControle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Instância de ação do plano de ação de uma campanha — gerada a partir da
 * classificação de risco (categoria × GHE) e de um PlanoAcaoTemplate, e
 * acompanhada como um ciclo PDCA explícito (Planejar → Executar → Verificar
 * → Agir, ver Enums\FasePdca) até ser considerada eficaz ou reaberta.
 */
class PlanoAcao extends Model
{
    protected $table = 'pesq_planos_acao';

    protected $fillable = [
        'pesquisa_id',
        'categoria_id',
        'ghe_id',
        'template_id',
        'tipo_controle',
        'nivel_risco',
        'farol',
        'acao',
        'como_executar',
        'evidencia',
        'responsavel',
        'prazo',
        'status',
        'concluido_em',
        'observacoes',
        'fase_pdca',
        'ciclo_pdca',
        'executado_em',
        'evidencia_execucao',
        'verificado_em',
        'verificado_por',
        'parecer_verificacao',
        'agido_em',
        'eficacia',
        'necessita_nova_acao',
        'historico_pdca',
    ];

    protected $casts = [
        'tipo_controle'        => TipoControle::class,
        'nivel_risco'          => NivelRisco::class,
        'status'               => StatusPlanoAcao::class,
        'concluido_em'         => 'datetime',
        'fase_pdca'            => FasePdca::class,
        'ciclo_pdca'           => 'integer',
        'executado_em'         => 'datetime',
        'verificado_em'        => 'datetime',
        'agido_em'             => 'datetime',
        'eficacia'             => Eficacia::class,
        'necessita_nova_acao'  => 'boolean',
        'historico_pdca'       => 'array',
    ];

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PlanoAcaoTemplate::class, 'template_id');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}
