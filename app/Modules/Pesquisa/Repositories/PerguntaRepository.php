<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\Pergunta;
use Illuminate\Database\Eloquent\Collection;

class PerguntaRepository
{
    public function listarPorSubcategoria(int $subcategoriaId): Collection
    {
        return Pergunta::query()
            ->where('subcategoria_id', $subcategoriaId)
            ->orderBy('ordem')
            ->get();
    }

    public function buscarPorId(int $id): ?Pergunta
    {
        return Pergunta::query()->find($id);
    }

    public function proximaOrdem(int $subcategoriaId): int
    {
        return (int) Pergunta::query()->where('subcategoria_id', $subcategoriaId)->max('ordem') + 1;
    }

    public function buscarEquivalenteNaVersao(int $novoFormularioId, int $origemId): ?Pergunta
    {
        return Pergunta::query()
            ->where('formulario_id', $novoFormularioId)
            ->where('origem_id', $origemId)
            ->first();
    }

    public function existeAtivaComConceito(int $conceitoId): bool
    {
        return Pergunta::query()->where('conceito_id', $conceitoId)->where('ativo', true)->exists();
    }
}
