<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\PesquisaFactory;
use App\Modules\Pesquisa\Enums\StatusPesquisa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaCampanha',
    description: 'Campanha de aplicação de um formulário de pesquisa psicossocial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'formulario_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', nullable: true, example: 'Avaliação de Riscos Psicossociais 2026/1'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'data_inicio', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'data_fim', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'anonima', type: 'boolean', example: true),
        new OA\Property(property: 'status', type: 'string', enum: ['rascunho', 'ativa', 'encerrada', 'cancelada'], example: 'rascunho'),
        new OA\Property(property: 'criado_por', type: 'integer', nullable: true),
        new OA\Property(property: 'link_publico_token', type: 'string', nullable: true, description: 'Token do link global compartilhável da campanha'),
    ]
)]
class Pesquisa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_pesquisas';

    protected $fillable = [
        'empresa_id',
        'formulario_id',
        'nome',
        'descricao',
        'data_inicio',
        'data_fim',
        'anonima',
        'minimo_respondentes',
        'status',
        'criado_por',
        'link_publico_token',
    ];

    protected $casts = [
        'status'              => StatusPesquisa::class,
        'data_inicio'         => 'date',
        'data_fim'            => 'date',
        'anonima'             => 'boolean',
        'minimo_respondentes' => 'integer',
    ];

    protected static function newFactory(): PesquisaFactory
    {
        return PesquisaFactory::new();
    }

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function publico(): HasMany
    {
        return $this->hasMany(PesquisaPublico::class, 'pesquisa_id');
    }

    public function acessosPublicos(): HasMany
    {
        return $this->hasMany(PesquisaAcessoPublico::class, 'pesquisa_id');
    }

    public function planosAcao(): HasMany
    {
        return $this->hasMany(PlanoAcao::class, 'pesquisa_id');
    }

    public function relatoriosTecnicos(): HasMany
    {
        return $this->hasMany(RelatorioTecnico::class, 'pesquisa_id');
    }

    // Scopes

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('empresa_id', $user->empresa_id);
    }
}
