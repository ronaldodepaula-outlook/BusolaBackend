<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Casts\ReferenciaFatorRiscoCast;
use App\Modules\Pesquisa\Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaCategoria',
    description: 'Categoria de um formulário de pesquisa psicossocial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'formulario_id', type: 'integer', example: 1),
        new OA\Property(property: 'origem_id', type: 'integer', nullable: true),
        new OA\Property(property: 'nome', type: 'string', example: 'Carga de Trabalho'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'categoria_referencia', type: 'string', nullable: true, description: 'Fator de risco psicossocial oficial (COPSOQ II) associado a esta categoria'),
        new OA\Property(property: 'severidade', type: 'integer', nullable: true, description: 'Severidade fixa (1-5); preenchida automaticamente quando categoria_referencia é definida'),
        new OA\Property(property: 'ordem', type: 'integer', example: 1),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Categoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_categorias';

    protected $fillable = [
        'formulario_id',
        'origem_id',
        'nome',
        'descricao',
        'categoria_referencia',
        'severidade',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'categoria_referencia' => ReferenciaFatorRiscoCast::class,
        'ordem'                => 'integer',
        'severidade'           => 'integer',
        'ativo'                => 'boolean',
    ];

    /** Severidade efetiva: a fixa manualmente, ou a padrão da referência oficial, ou null. */
    public function severidadeEfetiva(): ?int
    {
        return $this->severidade ?? $this->categoria_referencia?->severidadePadrao();
    }

    protected static function newFactory(): CategoriaFactory
    {
        return CategoriaFactory::new();
    }

    // Relationships

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class, 'categoria_id')->orderBy('ordem');
    }

    // Scopes

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
