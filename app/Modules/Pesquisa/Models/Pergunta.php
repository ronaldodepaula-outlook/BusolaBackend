<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\PerguntaFactory;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaPergunta',
    description: 'Pergunta de uma subcategoria de formulário de pesquisa psicossocial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'subcategoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'formulario_id', type: 'integer', example: 1),
        new OA\Property(property: 'conceito_id', type: 'integer', nullable: true),
        new OA\Property(property: 'origem_id', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo_pergunta', type: 'string', enum: ['escala', 'texto', 'numero', 'data', 'sim_nao', 'multipla_escolha', 'unica_escolha'], example: 'escala'),
        new OA\Property(property: 'texto', type: 'string', example: 'Com que frequência você sente excesso de demandas no trabalho?'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'obrigatoria', type: 'boolean', example: true),
        new OA\Property(property: 'permite_observacao', type: 'boolean', example: false),
        new OA\Property(property: 'permite_anexo', type: 'boolean', example: false),
        new OA\Property(property: 'ordem', type: 'integer', example: 1),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Pergunta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_perguntas';

    protected $fillable = [
        'subcategoria_id',
        'formulario_id',
        'conceito_id',
        'origem_id',
        'tipo_pergunta',
        'texto',
        'descricao',
        'obrigatoria',
        'permite_observacao',
        'permite_anexo',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'tipo_pergunta'      => TipoPergunta::class,
        'obrigatoria'        => 'boolean',
        'permite_observacao' => 'boolean',
        'permite_anexo'      => 'boolean',
        'ordem'              => 'integer',
        'ativo'              => 'boolean',
    ];

    protected static function newFactory(): PerguntaFactory
    {
        return PerguntaFactory::new();
    }

    // Relationships

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function conceito(): BelongsTo
    {
        return $this->belongsTo(Conceito::class, 'conceito_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_id');
    }

    // Scopes

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
