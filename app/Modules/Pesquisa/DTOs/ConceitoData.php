<?php

namespace App\Modules\Pesquisa\DTOs;

use App\Modules\Pesquisa\Enums\TipoConceito;
use Illuminate\Foundation\Http\FormRequest;

final readonly class ConceitoData
{
    public function __construct(
        public ?string $nome = null,
        public ?string $descricao = null,
        public ?TipoConceito $tipo = null,
        public ?int $empresaId = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            nome: $data['nome'] ?? null,
            descricao: $data['descricao'] ?? null,
            tipo: isset($data['tipo']) ? TipoConceito::from($data['tipo']) : null,
            empresaId: array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null,
        );
    }
}
