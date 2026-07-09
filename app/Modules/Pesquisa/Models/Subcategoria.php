<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\SubcategoriaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaSubcategoria',
    description: 'Subcategoria de uma categoria de formulário de pesquisa psicossocial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'formulario_id', type: 'integer', example: 1),
        new OA\Property(property: 'origem_id', type: 'integer', nullable: true),
        new OA\Property(property: 'nome', type: 'string', example: 'Excesso de demandas'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'ordem', type: 'integer', example: 1),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Subcategoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_subcategorias';

    protected $fillable = [
        'categoria_id',
        'formulario_id',
        'origem_id',
        'nome',
        'descricao',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'ativo' => 'boolean',
    ];

    protected static function newFactory(): SubcategoriaFactory
    {
        return SubcategoriaFactory::new();
    }

    // Relationships

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_id');
    }

    public function perguntas(): HasMany
    {
        return $this->hasMany(Pergunta::class, 'subcategoria_id')->orderBy('ordem');
    }

    // Scopes

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
