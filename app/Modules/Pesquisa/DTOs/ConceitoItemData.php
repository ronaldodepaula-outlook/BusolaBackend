<?php

namespace App\Modules\Pesquisa\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class ConceitoItemData
{
    public function __construct(
        public ?string $descricao = null,
        public ?float $valor = null,
        public ?string $cor = null,
        public ?int $ordem = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            descricao: $data['descricao'] ?? null,
            valor: array_key_exists('valor', $data) ? (float) $data['valor'] : null,
            cor: $data['cor'] ?? null,
            ordem: $data['ordem'] ?? null,
        );
    }
}
