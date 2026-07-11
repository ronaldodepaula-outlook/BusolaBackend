<?php

namespace App\Modules\Pesquisa\Casts;

use App\Modules\Pesquisa\Contracts\FatorRiscoReferenciaInterface;
use App\Modules\Pesquisa\Support\FatorRiscoReferenciaResolver;
use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast polimórfico de `Categoria::categoria_referencia`: resolve a string
 * gravada no banco para a instância de enum correta (NR-1 completo ou
 * COPSOQ resumido — ver {@see FatorRiscoReferenciaResolver}), em vez de
 * travar a coluna a uma única classe de enum via o cast nativo do Laravel.
 * Todo código consumidor (severidadeEfetiva(), o relatório técnico, os
 * Anexos I/II) continua chamando label()/severidadePadrao()/etc. sem saber
 * qual dos dois padrões está por trás.
 *
 * @implements CastsAttributes<FatorRiscoReferenciaInterface|null, string|BackedEnum|null>
 */
class ReferenciaFatorRiscoCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FatorRiscoReferenciaInterface
    {
        if ($value === null) {
            return null;
        }

        return FatorRiscoReferenciaResolver::resolver($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof BackedEnum ? $value->value : (string) $value;
    }
}
