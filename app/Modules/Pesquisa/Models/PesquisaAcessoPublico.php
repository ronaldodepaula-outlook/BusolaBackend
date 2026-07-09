<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\PesquisaAcessoPublicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesquisaAcessoPublico extends Model
{
    use HasFactory;

    protected $table = 'pesq_pesquisa_acessos_publicos';

    protected $fillable = [
        'pesquisa_id',
        'ghe_id',
        'sessao_token',
        'ip',
        'user_agent',
        'respondido_em',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
    ];

    protected static function newFactory(): PesquisaAcessoPublicoFactory
    {
        return PesquisaAcessoPublicoFactory::new();
    }

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
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
