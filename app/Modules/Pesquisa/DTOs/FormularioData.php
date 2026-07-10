<?php

namespace App\Modules\Pesquisa\DTOs;

use App\Modules\Pesquisa\Enums\TipoFormulario;
use Illuminate\Foundation\Http\FormRequest;

final readonly class FormularioData
{
    public function __construct(
        public ?string $nome = null,
        public ?string $codigo = null,
        public ?string $descricao = null,
        public ?TipoFormulario $tipo = null,
        public ?int $empresaId = null,
        public bool $padraoFormularioIdInformado = false,
        public ?int $padraoFormularioId = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            nome: $data['nome'] ?? null,
            codigo: $data['codigo'] ?? null,
            descricao: $data['descricao'] ?? null,
            tipo: isset($data['tipo']) ? TipoFormulario::from($data['tipo']) : null,
            empresaId: array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null,
            padraoFormularioIdInformado: array_key_exists('padrao_formulario_id', $data),
            padraoFormularioId: $data['padrao_formulario_id'] ?? null,
        );
    }
}
