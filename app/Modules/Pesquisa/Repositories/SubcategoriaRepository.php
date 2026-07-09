<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Database\Eloquent\Collection;

class SubcategoriaRepository
{
    public function listarPorCategoria(int $categoriaId): Collection
    {
        return Subcategoria::query()
            ->where('categoria_id', $categoriaId)
            ->withCount('perguntas')
            ->orderBy('ordem')
            ->get();
    }

    public function buscarPorId(int $id): ?Subcategoria
    {
        return Subcategoria::query()->find($id);
    }

    public function proximaOrdem(int $categoriaId): int
    {
        return (int) Subcategoria::query()->where('categoria_id', $categoriaId)->max('ordem') + 1;
    }

    public function buscarEquivalenteNaVersao(int $novoFormularioId, int $origemId): ?Subcategoria
    {
        return Subcategoria::query()
            ->where('formulario_id', $novoFormularioId)
            ->where('origem_id', $origemId)
            ->first();
    }
}
