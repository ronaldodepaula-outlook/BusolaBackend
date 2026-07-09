<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mapeamento usuário → setor, isolado dentro do módulo (não altera a tabela
 * `users` do core).
 */
class UsuarioSetor extends Model
{
    protected $table = 'pesq_usuario_setores';

    protected $fillable = [
        'user_id',
        'setor_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }
}
