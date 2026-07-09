<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\CategoriaData;
use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Repositories\CategoriaRepository;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use App\Modules\Pesquisa\Services\Versionamento\FormularioVersionador;
use Illuminate\Database\Eloquent\Collection;

class CategoriaService
{
    public function __construct(
        private readonly CategoriaRepository $categoriaRepository,
        private readonly FormularioRepository $formularioRepository,
        private readonly FormularioVersionador $versionador,
    ) {
    }

    public function listar(int $formularioId, User $user): Collection
    {
        $this->buscarFormularioVisivel($formularioId, $user);

        return $this->categoriaRepository->listarPorFormulario($formularioId);
    }

    public function buscar(int $id, User $user): Categoria
    {
        $categoria = $this->categoriaRepository->buscarPorId($id);
        abort_if(! $categoria, 404, 'Categoria não encontrada.');

        $this->buscarFormularioVisivel($categoria->formulario_id, $user);

        return $categoria;
    }

    /**
     * @return array{categoria: Categoria, versionado: bool, formulario_atual_id: int}
     */
    public function criar(int $formularioId, CategoriaData $dto, User $user): array
    {
        $formulario = $this->buscarFormularioVisivel($formularioId, $user);
        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $categoria = Categoria::create([
            'formulario_id'         => $formularioAlvo->id,
            'nome'                  => $dto->nome,
            'descricao'             => $dto->descricao,
            'categoria_referencia'  => $dto->categoriaReferencia,
            'severidade'            => $dto->severidade ?? $this->severidadePadrao($dto->categoriaReferencia),
            'ordem'                 => $dto->ordem ?? $this->categoriaRepository->proximaOrdem($formularioAlvo->id),
            'ativo'                 => true,
        ]);

        return [
            'categoria'           => $categoria,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{categoria: Categoria, versionado: bool, formulario_atual_id: int}
     */
    public function atualizar(int $id, CategoriaData $dto, User $user): array
    {
        $categoria = $this->buscar($id, $user);
        $formulario = $categoria->formulario;

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->categoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $categoria->id)
            : $categoria;

        abort_if(! $alvo, 500, 'Falha ao localizar a categoria na nova versão do formulário.');

        $alvo->fill(array_filter([
            'nome'                 => $dto->nome,
            'descricao'            => $dto->descricao,
            'categoria_referencia' => $dto->categoriaReferencia,
            'severidade'           => $dto->severidade ?? $this->severidadePadrao($dto->categoriaReferencia),
            'ordem'                => $dto->ordem,
        ], fn ($v) => $v !== null));
        $alvo->save();

        return [
            'categoria'           => $alvo,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function excluir(int $id, User $user): array
    {
        $categoria = $this->buscar($id, $user);
        $formulario = $categoria->formulario;

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->categoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $categoria->id)
            : $categoria;

        $alvo?->delete();

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @param  int[]  $idsOrdenados  IDs de categoria na ordem desejada
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function reordenar(int $formularioId, array $idsOrdenados, User $user): array
    {
        $formulario = $this->buscarFormularioVisivel($formularioId, $user);

        $categorias = $this->categoriaRepository->listarPorFormulario($formularioId)->keyBy('id');

        foreach ($idsOrdenados as $id) {
            abort_if(! $categorias->has($id), 422, "A categoria {$id} não pertence a este formulário.");
        }

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        foreach ($idsOrdenados as $index => $idOriginal) {
            $alvo = $resolucao['versionado']
                ? $this->categoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $idOriginal)
                : $categorias->get($idOriginal);

            $alvo?->update(['ordem' => $index + 1]);
        }

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /** Severidade fixa oficial quando a categoria é vinculada a um fator de risco de referência. */
    private function severidadePadrao(?string $categoriaReferencia): ?int
    {
        return $categoriaReferencia ? CategoriaReferencia::from($categoriaReferencia)->severidadePadrao() : null;
    }

    private function buscarFormularioVisivel(int $formularioId, User $user): Formulario
    {
        $formulario = $this->formularioRepository->buscarPorId($formularioId, $user);
        abort_if(! $formulario, 404, 'Formulário não encontrado.');

        return $formulario;
    }
}
