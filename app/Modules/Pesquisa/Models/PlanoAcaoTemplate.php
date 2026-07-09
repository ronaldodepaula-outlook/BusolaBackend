<?php

namespace App\Modules\Pesquisa\Models;

use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Enums\NivelBaseAcao;
use App\Modules\Pesquisa\Enums\TipoControle;
use Illuminate\Database\Eloquent\Model;

/**
 * Biblioteca de referência de ações de plano de ação (Categoria oficial ×
 * Nível de risco × Tipo de controle), seguindo a aba BASE_ACAO da planilha
 * de cálculo oficial. Não pertence a nenhuma empresa — é conteúdo global
 * semeado a partir da metodologia.
 */
class PlanoAcaoTemplate extends Model
{
    protected $table = 'pesq_plano_acao_templates';

    protected $fillable = [
        'categoria_referencia',
        'nivel_base_acao',
        'tipo_controle',
        'acao',
        'como_executar',
        'evidencia',
        'responsavel_padrao',
        'prazo',
    ];

    protected $casts = [
        'categoria_referencia' => CategoriaReferencia::class,
        'nivel_base_acao'      => NivelBaseAcao::class,
        'tipo_controle'        => TipoControle::class,
    ];

    /** Texto final da ação, com o prefixo de urgência do nível já aplicado. */
    public function textoCompleto(): string
    {
        return ucfirst($this->nivel_base_acao->prefixoAcao()).': '.$this->acao;
    }
}
