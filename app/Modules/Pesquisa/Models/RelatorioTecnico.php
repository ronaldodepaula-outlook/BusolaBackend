<?php

namespace App\Modules\Pesquisa\Models;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada Relatório Técnico (PDF) gerado para uma campanha.
 * empresa_id é desnormalizado para permitir a listagem cross-empresa na
 * tela de gestão do super administrador.
 */
class RelatorioTecnico extends Model
{
    protected $table = 'pesq_relatorios_tecnicos';

    public $timestamps = true;

    protected $fillable = [
        'pesquisa_id',
        'empresa_id',
        'gerado_por',
        'responsavel_tecnico_nome',
        'responsavel_tecnico_registro',
        'arquivo_path',
        'tamanho_bytes',
        'gerado_em',
    ];

    protected $casts = [
        'gerado_em' => 'datetime',
    ];

    public function pesquisa(): BelongsTo
    {
        return $this->belongsTo(Pesquisa::class, 'pesquisa_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function geradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerado_por');
    }
}
