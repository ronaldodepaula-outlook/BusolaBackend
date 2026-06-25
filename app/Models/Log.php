<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Log',
    description: 'Registro de auditoria',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true),
        new OA\Property(property: 'filial_id', type: 'integer', nullable: true),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'usuario_nome', type: 'string', nullable: true, example: 'João Silva'),
        new OA\Property(property: 'acao', type: 'string', example: 'POST'),
        new OA\Property(property: 'modulo', type: 'string', nullable: true, example: 'empresas'),
        new OA\Property(property: 'rota', type: 'string', nullable: true, example: '/api/v1/empresas'),
        new OA\Property(property: 'metodo', type: 'string', nullable: true, example: 'POST'),
        new OA\Property(property: 'ip', type: 'string', nullable: true, example: '192.168.0.1'),
        new OA\Property(property: 'user_agent', type: 'string', nullable: true),
        new OA\Property(property: 'status_code', type: 'integer', nullable: true, example: 201),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'user_id',
        'usuario_nome',
        'acao',
        'modulo',
        'rota',
        'metodo',
        'payload',
        'payload_anterior',
        'ip',
        'user_agent',
        'status_code',
    ];

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
