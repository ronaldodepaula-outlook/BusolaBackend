<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\FormularioFactory;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaFormulario',
    description: 'Formulário de pesquisa psicossocial (global ou de uma empresa), com versionamento',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'formulario_raiz_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'nome', type: 'string', example: 'Avaliação de Riscos Psicossociais NR-1'),
        new OA\Property(property: 'codigo', type: 'string', example: 'nr1-padrao'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['rascunho', 'publicado', 'arquivado'], example: 'publicado'),
        new OA\Property(property: 'tipo', type: 'string', enum: ['global', 'empresa'], example: 'global'),
        new OA\Property(property: 'versao', type: 'integer', example: 1),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
        new OA\Property(property: 'created_by', type: 'integer', nullable: true),
        new OA\Property(property: 'updated_by', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Formulario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_formularios';

    protected $fillable = [
        'formulario_raiz_id',
        'empresa_id',
        'nome',
        'codigo',
        'descricao',
        'status',
        'tipo',
        'versao',
        'ativo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => StatusFormulario::class,
        'tipo'   => TipoFormulario::class,
        'versao' => 'integer',
        'ativo'  => 'boolean',
    ];

    protected static function newFactory(): FormularioFactory
    {
        return FormularioFactory::new();
    }

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function raiz(): BelongsTo
    {
        return $this->belongsTo(self::class, 'formulario_raiz_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(self::class, 'formulario_raiz_id');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'formulario_id')->orderBy('ordem');
    }

    public function pesquisas(): HasMany
    {
        return $this->hasMany(Pesquisa::class, 'formulario_id');
    }

    // Domain helpers

    public function raizId(): int
    {
        return $this->formulario_raiz_id ?? $this->id;
    }

    public function ehGlobal(): bool
    {
        return $this->empresa_id === null;
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

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
