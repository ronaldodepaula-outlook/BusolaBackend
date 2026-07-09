<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\User;
use App\Modules\Pesquisa\Database\Factories\PesquisaConviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesquisaConvite extends Model
{
    use HasFactory;

    protected $table = 'pesq_pesquisa_convites';

    protected $fillable = [
        'pesquisa_id',
        'colaborador_id',
        'user_id',
        'ghe_id',
        'token',
        'respondido_em',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
    ];

    protected static function newFactory(): PesquisaConviteFactory
    {
        return PesquisaConviteFactory::new();
    }

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    /** @deprecated mantido por compatibilidade com convites antigos gerados antes do modelo Colaborador. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    public function jaRespondeu(): bool
    {
        return $this->respondido_em !== null;
    }
}
