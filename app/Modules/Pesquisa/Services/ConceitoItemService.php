<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\ConceitoItemData;
use App\Modules\Pesquisa\Models\ConceitoItem;
use App\Modules\Pesquisa\Repositories\ConceitoItemRepository;
use App\Modules\Pesquisa\Repositories\ConceitoRepository;

class ConceitoItemService
{
    public function __construct(
        private readonly ConceitoItemRepository $conceitoItemRepository,
        private readonly ConceitoRepository $conceitoRepository,
    ) {
    }

    public function adicionar(int $conceitoId, ConceitoItemData $dto, User $user): ConceitoItem
    {
        $conceito = $this->conceitoRepository->buscarPorId($conceitoId, $user);
        abort_if(! $conceito, 404, 'Conceito não encontrado.');

        return ConceitoItem::create([
            'conceito_id' => $conceito->id,
            'descricao'   => $dto->descricao,
            'valor'       => $dto->valor,
            'cor'         => $dto->cor,
            'ordem'       => $dto->ordem ?? $this->conceitoItemRepository->proximaOrdem($conceito->id),
        ]);
    }

    public function atualizar(int $conceitoId, int $itemId, ConceitoItemData $dto, User $user): ConceitoItem
    {
        $this->garantirConceitoVisivel($conceitoId, $user);

        $item = $this->conceitoItemRepository->buscarPorId($conceitoId, $itemId);
        abort_if(! $item, 404, 'Item de conceito não encontrado.');

        $item->fill(array_filter([
            'descricao' => $dto->descricao,
            'valor'     => $dto->valor,
            'cor'       => $dto->cor,
            'ordem'     => $dto->ordem,
        ], fn ($v) => $v !== null));
        $item->save();

        return $item;
    }

    public function remover(int $conceitoId, int $itemId, User $user): void
    {
        $this->garantirConceitoVisivel($conceitoId, $user);

        $item = $this->conceitoItemRepository->buscarPorId($conceitoId, $itemId);
        abort_if(! $item, 404, 'Item de conceito não encontrado.');

        $item->delete();
    }

    /**
     * @param  int[]  $idsOrdenados
     */
    public function reordenar(int $conceitoId, array $idsOrdenados, User $user): void
    {
        $conceito = $this->conceitoRepository->buscarPorId($conceitoId, $user);
        abort_if(! $conceito, 404, 'Conceito não encontrado.');

        foreach ($idsOrdenados as $index => $itemId) {
            $this->conceitoItemRepository->buscarPorId($conceitoId, $itemId)?->update(['ordem' => $index + 1]);
        }
    }

    private function garantirConceitoVisivel(int $conceitoId, User $user): void
    {
        $conceito = $this->conceitoRepository->buscarPorId($conceitoId, $user);
        abort_if(! $conceito, 404, 'Conceito não encontrado.');
    }
}
