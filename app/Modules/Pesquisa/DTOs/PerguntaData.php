<?php

namespace App\Modules\Pesquisa\DTOs;

use App\Modules\Pesquisa\Enums\TipoPergunta;
use Illuminate\Foundation\Http\FormRequest;

final readonly class PerguntaData
{
    public function __construct(
        public ?TipoPergunta $tipoPergunta = null,
        public ?string $texto = null,
        public ?string $descricao = null,
        public ?bool $obrigatoria = null,
        public ?bool $permiteObservacao = null,
        public ?bool $permiteAnexo = null,
        public ?int $conceitoId = null,
        public ?int $ordem = null,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            tipoPergunta: isset($data['tipo_pergunta']) ? TipoPergunta::from($data['tipo_pergunta']) : null,
            texto: $data['texto'] ?? null,
            descricao: $data['descricao'] ?? null,
            obrigatoria: array_key_exists('obrigatoria', $data) ? (bool) $data['obrigatoria'] : null,
            permiteObservacao: array_key_exists('permite_observacao', $data) ? (bool) $data['permite_observacao'] : null,
            permiteAnexo: array_key_exists('permite_anexo', $data) ? (bool) $data['permite_anexo'] : null,
            conceitoId: array_key_exists('conceito_id', $data) ? $data['conceito_id'] : null,
            ordem: $data['ordem'] ?? null,
        );
    }
}
