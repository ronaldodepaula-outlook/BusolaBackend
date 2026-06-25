<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Filial',
    description: 'Dados de uma filial',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Filial Centro'),
        new OA\Property(property: 'codigo', type: 'string', nullable: true, example: 'FIL001'),
        new OA\Property(property: 'cnpj', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'telefone', type: 'string', nullable: true),
        new OA\Property(property: 'responsavel', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo'], example: 'ativo'),
        new OA\Property(property: 'endereco', type: 'string', nullable: true),
        new OA\Property(property: 'cidade', type: 'string', nullable: true),
        new OA\Property(property: 'estado', type: 'string', nullable: true),
        new OA\Property(property: 'horario_abertura', type: 'string', nullable: true, example: '08:00'),
        new OA\Property(property: 'horario_fechamento', type: 'string', nullable: true, example: '18:00'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class Filial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'filiais';

    protected $fillable = [
        'empresa_id',
        'nome',
        'codigo',
        'cnpj',
        'email',
        'telefone',
        'responsavel',
        'status',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'horario_abertura',
        'horario_fechamento',
        'observacoes',
    ];

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'filial_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'filial_id');
    }

    public function configuracoes(): HasMany
    {
        return $this->hasMany(Configuracao::class, 'filial_id');
    }
}
