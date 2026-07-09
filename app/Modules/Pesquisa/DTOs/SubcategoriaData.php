<?php

namespace App\Modules\Pesquisa\DTOs;

use Illuminate\Foundation\Http\FormRequest;

final readonly class SubcategoriaData
{
    public function __construct(
        public ?string $nome = null,
        public ?string $descricao = null,
        public ?int $ordem = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            nome: $data['nome'] ?? null,
            descricao: $data['descricao'] ?? null,
            ordem: $data['ordem'] ?? null,
        );
    }
}
