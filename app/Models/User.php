<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[OA\Schema(
    schema: 'Usuario',
    description: 'Dados de um usuário',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'empresa_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'filial_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@empresa.com'),
        new OA\Property(property: 'foto', type: 'string', nullable: true, description: 'Path relativo no disco público'),
        new OA\Property(property: 'foto_url', type: 'string', nullable: true, description: 'URL completa e pronta para uso em <img src>'),
        new OA\Property(property: 'telefone', type: 'string', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['superadmin', 'admin', 'gerente', 'usuario'], example: 'usuario'),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo', 'bloqueado'], example: 'ativo'),
        new OA\Property(property: 'ultimo_login', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'primeiro_acesso', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'nome',
        'email',
        'senha',
        'foto',
        'telefone',
        'tipo',
        'status',
        'ultimo_login',
        'token_reset_senha',
        'token_reset_expira_em',
        'primeiro_acesso',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
        'token_reset_senha',
    ];

    protected $appends = [
        'foto_url',
    ];

    protected $casts = [
        'senha'                 => 'hashed',
        'ultimo_login'          => 'datetime',
        'token_reset_expira_em' => 'datetime',
        'primeiro_acesso'       => 'boolean',
    ];

    protected function fotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->foto ? Storage::disk('public')->url($this->foto) : null,
        );
    }

    // JWT Methods

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'filial_id'  => $this->filial_id,
            'tipo'       => $this->tipo,
        ];
    }

    // Auth override — use 'senha' as password field

    /**
     * Nulo enquanto o usuário não conclui a ativação da conta (Fluxo 1 —
     * criado pelo administrador, sem senha, até definir a própria senha via
     * link de ativação). Hash::check()/os hashers do Laravel já tratam um
     * hash nulo/vazio como "nunca confere", então o login simplesmente falha
     * com credenciais inválidas — não é preciso nenhuma checagem extra aqui.
     */
    public function getAuthPassword(): ?string
    {
        return $this->senha;
    }

    // Relationships

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'user_id');
    }

    // Helper Methods

    public function hasPermission(string $slug): bool
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('slug', $slug)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->tipo === 'superadmin';
    }
}
