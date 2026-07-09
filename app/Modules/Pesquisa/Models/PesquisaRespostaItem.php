<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Database\Factories\PesquisaRespostaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesquisaRespostaItem extends Model
{
    use HasFactory;

    protected $table = 'pesq_pesquisa_respostas_itens';

    protected $fillable = [
        'pesquisa_resposta_id',
        'pergunta_id',
        'conceito_item_id',
        'valor_texto',
        'valor_numero',
        'observacao',
    ];

    protected static function newFactory(): PesquisaRespostaItemFactory
    {
        return PesquisaRespostaItemFactory::new();
    }

    public function resposta(): BelongsTo
    {
        return $this->belongsTo(PesquisaResposta::class, 'pesquisa_resposta_id');
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class, 'pergunta_id');
    }

    public function conceitoItem(): BelongsTo
    {
        return $this->belongsTo(ConceitoItem::class, 'conceito_item_id');
    }
}
