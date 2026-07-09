<?php

namespace App\Modules\Pesquisa\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class PesquisaData
{
    public function __construct(
        public ?int $formularioId = null,
        public ?int $empresaId = null,
        public ?string $nome = null,
        public ?string $descricao = null,
        public ?string $dataInicio = null,
        public ?string $dataFim = null,
        public ?bool $anonima = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            formularioId: $data['formulario_id'] ?? null,
            empresaId: array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null,
            nome: $data['nome'] ?? null,
            descricao: $data['descricao'] ?? null,
            dataInicio: $data['data_inicio'] ?? null,
            dataFim: $data['data_fim'] ?? null,
            anonima: array_key_exists('anonima', $data) ? (bool) $data['anonima'] : null,
        );
    }
}
