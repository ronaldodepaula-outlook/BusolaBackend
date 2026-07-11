<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\PadraoFormularioFactory;
use App\Modules\Pesquisa\Enums\ModeloCalculoRisco;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaPadraoFormulario',
    description: 'Padrão/norma que um formulário segue (ex.: COPSOQ II, NR-1, ISO 45003 ou um padrão específico da empresa)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, example: null, description: 'Nulo = padrão global, disponível a todas as empresas'),
        new OA\Property(property: 'nome', type: 'string', example: 'COPSOQ II'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
        new OA\Property(property: 'modelo_calculo', type: 'string', enum: ['nr1_completo', 'copsoq_simplificado'], example: 'nr1_completo', description: 'Motor de cálculo de risco usado pelas campanhas deste padrão'),
    ]
)]
class PadraoFormulario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_padroes_formulario';

    protected $fillable = [
        'empresa_id',
        'nome',
        'descricao',
        'ativo',
        'created_by',
        'modelo_calculo',
    ];

    protected $casts = [
        'ativo'          => 'boolean',
        'modelo_calculo' => ModeloCalculoRisco::class,
    ];

    protected static function newFactory(): PadraoFormularioFactory
    {
        return PadraoFormularioFactory::new();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class, 'padrao_formulario_id');
    }

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
