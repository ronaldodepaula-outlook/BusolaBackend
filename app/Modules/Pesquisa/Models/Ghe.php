<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaGhe',
    description: 'Grupo Homogêneo de Exposição (GHE) — agrupa um ou mais Setores para fins de análise de risco psicossocial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'GHE 01 – Comercial e Relacionamento'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Ghe extends Model
{
    use SoftDeletes;

    protected $table = 'pesq_ghes';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function setores(): HasMany
    {
        return $this->hasMany(Setor::class, 'ghe_id');
    }
}
