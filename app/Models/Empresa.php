<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Empresa',
    description: 'Dados de uma empresa (tenant)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Empresa ABC Ltda'),
        new OA\Property(property: 'cnpj', type: 'string', example: '12.345.678/0001-90'),
        new OA\Property(property: 'email', type: 'string', example: 'contato@empresa.com'),
        new OA\Property(property: 'telefone', type: 'string', nullable: true),
        new OA\Property(property: 'logo', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo', 'bloqueado'], example: 'ativo'),
        new OA\Property(property: 'plano', type: 'string', example: 'professional'),
        new OA\Property(property: 'max_filiais', type: 'integer', example: 10),
        new OA\Property(property: 'max_usuarios', type: 'integer', example: 100),
        new OA\Property(property: 'responsavel', type: 'string', nullable: true),
        new OA\Property(property: 'cep', type: 'string', nullable: true),
        new OA\Property(property: 'endereco', type: 'string', nullable: true),
        new OA\Property(property: 'cidade', type: 'string', nullable: true),
        new OA\Property(property: 'estado', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'cnpj',
        'email',
        'telefone',
        'logo',
        'status',
        'plano',
        'max_filiais',
        'max_usuarios',
        'responsavel',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
    ];

    protected $casts = [
        'max_filiais'  => 'integer',
        'max_usuarios' => 'integer',
    ];

    // Relationships

    public function filiais(): HasMany
    {
        return $this->hasMany(Filial::class, 'empresa_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'empresa_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'empresa_id');
    }

    public function configuracoes(): HasMany
    {
        return $this->hasMany(Configuracao::class, 'empresa_id');
    }
}
