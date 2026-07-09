<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Modules\Pesquisa\Models\ConceitoItem;

class ConceitoItemRepository
{
    public function buscarPorId(int $conceitoId, int $itemId): ?ConceitoItem
    {
        return ConceitoItem::query()->where('conceito_id', $conceitoId)->find($itemId);
    }

    public function proximaOrdem(int $conceitoId): int
    {
        return (int) ConceitoItem::query()->where('conceito_id', $conceitoId)->max('ordem') + 1;
    }
}
