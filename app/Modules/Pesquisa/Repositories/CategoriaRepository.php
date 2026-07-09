<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

class CategoriaRepository
{
    public function listarPorFormulario(int $formularioId): Collection
    {
        return Categoria::query()
            ->where('formulario_id', $formularioId)
            ->withCount('subcategorias')
            ->orderBy('ordem')
            ->get();
    }

    public function buscarPorId(int $id): ?Categoria
    {
        return Categoria::query()->find($id);
    }

    public function proximaOrdem(int $formularioId): int
    {
        return (int) Categoria::query()->where('formulario_id', $formularioId)->max('ordem') + 1;
    }

    public function buscarEquivalenteNaVersao(int $novoFormularioId, int $origemId): ?Categoria
    {
        return Categoria::query()
            ->where('formulario_id', $novoFormularioId)
            ->where('origem_id', $origemId)
            ->first();
    }
}
