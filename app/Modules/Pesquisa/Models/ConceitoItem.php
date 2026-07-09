<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\ConceitoItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaConceitoItem',
    description: 'Item (opção) de um conceito de avaliação',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'conceito_id', type: 'integer', example: 1),
        new OA\Property(property: 'descricao', type: 'string', example: 'Muito satisfeito'),
        new OA\Property(property: 'valor', type: 'number', format: 'float', example: 5),
        new OA\Property(property: 'cor', type: 'string', nullable: true, example: '#22c55e'),
        new OA\Property(property: 'ordem', type: 'integer', example: 1),
    ]
)]
class ConceitoItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesq_conceito_itens';

    protected $fillable = [
        'conceito_id',
        'descricao',
        'valor',
        'cor',
        'ordem',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'ordem' => 'integer',
    ];

    protected static function newFactory(): ConceitoItemFactory
    {
        return ConceitoItemFactory::new();
    }

    // Relationships

    public function conceito(): BelongsTo
    {
        return $this->belongsTo(Conceito::class, 'conceito_id');
    }
}
