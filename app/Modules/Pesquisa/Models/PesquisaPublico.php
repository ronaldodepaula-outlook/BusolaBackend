<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Filial;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesquisaPublico extends Model
{
    protected $table = 'pesq_pesquisa_publico';

    protected $fillable = [
        'pesquisa_id',
        'filial_id',
        'colaborador_id',
        'user_id',
    ];

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
    }

    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    /** @deprecated mantido por compatibilidade com público-alvo definido antes do modelo Colaborador. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
