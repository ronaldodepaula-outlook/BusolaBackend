<?php

namespace App\Modules\Pesquisa\Repositories;

use App\Models\User;
use App\Modules\Pesquisa\Enums\StatusPesquisa;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pesquisa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FormularioRepository
{
    public function paginar(array $filtros, User $user, int $porPagina = 15): LengthAwarePaginator
    {
        $query = Formulario::query()->visiveisPara($user);

        if (! empty($filtros['empresa_id']) && $user->isSuperAdmin()) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (! empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if (! isset($filtros['incluir_versoes_antigas'])) {
            $query->vigentes();
        }

        return $query->withCount('categorias')->with('padraoFormulario:id,nome,empresa_id')->orderBy('nome')->paginate($porPagina);
    }

    public function buscarPorId(int $id, User $user): ?Formulario
    {
        return Formulario::query()->visiveisPara($user)->with('padraoFormulario:id,nome,empresa_id')->find($id);
    }

    public function buscarComEstrutura(int $id, User $user): ?Formulario
    {
        return Formulario::query()
            ->visiveisPara($user)
            ->with(['categorias.subcategorias.perguntas.conceito.itens', 'padraoFormulario:id,nome,empresa_id'])
            ->find($id);
    }

    public function existeCodigoNoEscopo(string $codigo, ?int $empresaId, ?int $ignorarRaizId = null): bool
    {
        $query = Formulario::query()
            ->where('codigo', $codigo)
            ->when($empresaId === null, fn ($q) => $q->whereNull('empresa_id'))
            ->when($empresaId !== null, fn ($q) => $q->where('empresa_id', $empresaId));

        if ($ignorarRaizId !== null) {
            $query->whereNotIn('id', function ($q) use ($ignorarRaizId) {
                $q->select('id')->from('pesq_formularios')
                    ->where('id', $ignorarRaizId)
                    ->orWhere('formulario_raiz_id', $ignorarRaizId);
            });
        }

        return $query->exists();
    }

    /**
     * Calcula a próxima versão de um grupo de formulário, travando as linhas
     * do grupo para evitar corrida entre requisições concorrentes.
     */
    public function proximaVersao(int $raizId): int
    {
        $max = Formulario::query()
            ->where(function ($q) use ($raizId) {
                $q->where('id', $raizId)->orWhere('formulario_raiz_id', $raizId);
            })
            ->lockForUpdate()
            ->max('versao');

        return ($max ?? 0) + 1;
    }

    public function todasVersoes(int $raizId): Collection
    {
        return Formulario::query()
            ->where(function ($q) use ($raizId) {
                $q->where('id', $raizId)->orWhere('formulario_raiz_id', $raizId);
            })
            ->orderBy('versao')
            ->get();
    }

    public function existeEncerradaParaFormulario(int $formularioId): bool
    {
        return Pesquisa::query()
            ->where('formulario_id', $formularioId)
            ->where('status', StatusPesquisa::ENCERRADA)
            ->exists();
    }

    public function existePesquisaParaGrupo(int $raizId): bool
    {
        return Pesquisa::query()
            ->whereIn('formulario_id', function ($q) use ($raizId) {
                $q->select('id')->from('pesq_formularios')
                    ->where('id', $raizId)->orWhere('formulario_raiz_id', $raizId);
            })
            ->exists();
    }

    public function transacao(\Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
