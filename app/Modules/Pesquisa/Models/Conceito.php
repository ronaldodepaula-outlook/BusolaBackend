<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\ConceitoFactory;
use App\Modules\Pesquisa\Enums\TipoConceito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaConceito',
    description: 'Conceito de avaliação (escala) reutilizável entre perguntas e formulários',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'nome', type: 'string', example: 'Escala de Satisfação'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['escala_likert', 'frequencia', 'numerica', 'personalizado'], example: 'escala_likert'),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Conceito extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_conceitos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'tipo',
        'ativo',
    ];

    protected $casts = [
        'tipo'  => TipoConceito::class,
        'ativo' => 'boolean',
    ];

    protected static function newFactory(): ConceitoFactory
    {
        return ConceitoFactory::new();
    }

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ConceitoItem::class, 'conceito_id')->orderBy('ordem');
    }

    public function perguntas(): HasMany
    {
        return $this->hasMany(Pergunta::class, 'conceito_id');
    }

    // Scopes

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereNull('empresa_id')->orWhere('empresa_id', $user->empresa_id);
        });
    }
}
