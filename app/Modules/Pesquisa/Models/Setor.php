<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PesquisaSetor',
    description: 'Setor organizacional — pertence a no máximo um GHE',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'ghe_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Comercial'),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ]
)]
class Setor extends Model
{
    use SoftDeletes;

    protected $table = 'pesq_setores';

    protected $fillable = [
        'empresa_id',
        'ghe_id',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    /** @deprecated Setor de contas de acesso (User) — o alvo do convite individual hoje é Colaborador, não User. */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pesq_usuario_setores', 'setor_id', 'user_id')
            ->withTimestamps();
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'setor_id');
    }
}
