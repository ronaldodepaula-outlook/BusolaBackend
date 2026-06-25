<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Role',
    description: 'Perfil de acesso (Role)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true),
        new OA\Property(property: 'nome', type: 'string', example: 'Administrador Financeiro'),
        new OA\Property(property: 'slug', type: 'string', example: 'administrador-financeiro'),
        new OA\Property(property: 'descricao', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo'], example: 'ativo'),
        new OA\Property(property: 'sistema', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'empresa_id',
        'nome',
        'slug',
        'descricao',
        'status',
        'sistema',
    ];

    protected $casts = [
        'sistema' => 'boolean',
    ];

    // Boot: auto-generate slug from nome

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Role $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->nome);
            }
        });

        static::updating(function (Role $role) {
            if ($role->isDirty('nome') && empty($role->slug)) {
                $role->slug = Str::slug($role->nome);
            }
        });
    }

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id');
    }
}
