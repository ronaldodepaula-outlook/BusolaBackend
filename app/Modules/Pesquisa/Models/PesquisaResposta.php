<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\PesquisaRespostaFactory;
use App\Modules\Pesquisa\Enums\StatusResposta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabeçalho de uma resposta de campanha. Deliberadamente sem user_id/convite_id
 * — não há como ligar o conteúdo desta resposta a quem a enviou.
 */
class PesquisaResposta extends Model
{
    use HasFactory;

    protected $table = 'pesq_pesquisa_respostas';

    protected $fillable = [
        'pesquisa_id',
        'ghe_id',
        'iniciado_em',
        'finalizado_em',
        'status',
    ];

    protected $casts = [
        'iniciado_em'   => 'datetime',
        'finalizado_em' => 'datetime',
        'status'        => StatusResposta::class,
    ];

    protected static function newFactory(): PesquisaRespostaFactory
    {
        return PesquisaRespostaFactory::new();
    }

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
    }

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(PesquisaRespostaItem::class, 'pesquisa_resposta_id');
    }
}
