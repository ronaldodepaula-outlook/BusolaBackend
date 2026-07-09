<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\SubcategoriaData;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Subcategoria;
use App\Modules\Pesquisa\Repositories\CategoriaRepository;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use App\Modules\Pesquisa\Repositories\SubcategoriaRepository;
use App\Modules\Pesquisa\Services\Versionamento\FormularioVersionador;
use Illuminate\Database\Eloquent\Collection;

class SubcategoriaService
{
    public function __construct(
        private readonly SubcategoriaRepository $subcategoriaRepository,
        private readonly CategoriaRepository $categoriaRepository,
        private readonly FormularioRepository $formularioRepository,
        private readonly FormularioVersionador $versionador,
    ) {
    }

    public function listar(int $categoriaId, User $user): Collection
    {
        $this->buscarCategoriaVisivel($categoriaId, $user);

        return $this->subcategoriaRepository->listarPorCategoria($categoriaId);
    }

    public function buscar(int $id, User $user): Subcategoria
    {
        $subcategoria = $this->subcategoriaRepository->buscarPorId($id);
        abort_if(! $subcategoria, 404, 'Subcategoria não encontrada.');

        $this->buscarFormularioVisivel($subcategoria->formulario_id, $user);

        return $subcategoria;
    }

    /**
     * @return array{subcategoria: Subcategoria, versionado: bool, formulario_atual_id: int}
     */
    public function criar(int $categoriaId, SubcategoriaData $dto, User $user): array
    {
        $categoria = $this->buscarCategoriaVisivel($categoriaId, $user);
        $resolucao = $this->versionador->resolverParaEdicao($categoria->formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $categoriaAlvo = $resolucao['versionado']
            ? $this->categoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $categoria->id)
            : $categoria;

        abort_if(! $categoriaAlvo, 500, 'Falha ao localizar a categoria na nova versão do formulário.');

        $subcategoria = Subcategoria::create([
            'categoria_id'  => $categoriaAlvo->id,
            'formulario_id' => $formularioAlvo->id,
            'nome'          => $dto->nome,
            'descricao'     => $dto->descricao,
            'ordem'         => $dto->ordem ?? $this->subcategoriaRepository->proximaOrdem($categoriaAlvo->id),
            'ativo'         => true,
        ]);

        return [
            'subcategoria'        => $subcategoria,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{subcategoria: Subcategoria, versionado: bool, formulario_atual_id: int}
     */
    public function atualizar(int $id, SubcategoriaData $dto, User $user): array
    {
        $subcategoria = $this->buscar($id, $user);
        $formulario = $subcategoria->formulario;

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->subcategoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $subcategoria->id)
            : $subcategoria;

        abort_if(! $alvo, 500, 'Falha ao localizar a subcategoria na nova versão do formulário.');

        $alvo->fill(array_filter([
            'nome'      => $dto->nome,
            'descricao' => $dto->descricao,
            'ordem'     => $dto->ordem,
        ], fn ($v) => $v !== null));
        $alvo->save();

        return [
            'subcategoria'        => $alvo,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function excluir(int $id, User $user): array
    {
        $subcategoria = $this->buscar($id, $user);
        $formulario = $subcategoria->formulario;

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->subcategoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $subcategoria->id)
            : $subcategoria;

        $alvo?->delete();

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @param  int[]  $idsOrdenados  IDs de subcategoria na ordem desejada
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function reordenar(int $categoriaId, array $idsOrdenados, User $user): array
    {
        $categoria = $this->buscarCategoriaVisivel($categoriaId, $user);

        $subcategorias = $this->subcategoriaRepository->listarPorCategoria($categoriaId)->keyBy('id');

        foreach ($idsOrdenados as $id) {
            abort_if(! $subcategorias->has($id), 422, "A subcategoria {$id} não pertence a esta categoria.");
        }

        $resolucao = $this->versionador->resolverParaEdicao($categoria->formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        foreach ($idsOrdenados as $index => $idOriginal) {
            $alvo = $resolucao['versionado']
                ? $this->subcategoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $idOriginal)
                : $subcategorias->get($idOriginal);

            $alvo?->update(['ordem' => $index + 1]);
        }

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    private function buscarCategoriaVisivel(int $categoriaId, User $user): Categoria
    {
        $categoria = $this->categoriaRepository->buscarPorId($categoriaId);
        abort_if(! $categoria, 404, 'Categoria não encontrada.');

        $this->buscarFormularioVisivel($categoria->formulario_id, $user);

        return $categoria;
    }

    private function buscarFormularioVisivel(int $formularioId, User $user): Formulario
    {
        $formulario = $this->formularioRepository->buscarPorId($formularioId, $user);
        abort_if(! $formulario, 404, 'Formulário não encontrado.');

        return $formulario;
    }
}
