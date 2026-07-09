<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\ConceitoData;
use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Repositories\ConceitoRepository;
use App\Modules\Pesquisa\Repositories\PerguntaRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConceitoService
{
    public function __construct(
        private readonly ConceitoRepository $conceitoRepository,
        private readonly PerguntaRepository $perguntaRepository,
    ) {
    }

    public function listar(array $filtros, User $user): LengthAwarePaginator
    {
        return $this->conceitoRepository->paginar($filtros, $user);
    }

    public function buscar(int $id, User $user): Conceito
    {
        $conceito = $this->conceitoRepository->buscarPorId($id, $user);
        abort_if(! $conceito, 404, 'Conceito não encontrado.');

        return $conceito;
    }

    public function criar(ConceitoData $dto, User $user): Conceito
    {
        $empresaId = $user->isSuperAdmin() ? $dto->empresaId : $user->empresa_id;

        return Conceito::create([
            'empresa_id' => $empresaId,
            'nome'       => $dto->nome,
            'descricao'  => $dto->descricao,
            'tipo'       => $dto->tipo ?? TipoConceito::PERSONALIZADO,
            'ativo'      => true,
        ]);
    }

    public function atualizar(int $id, ConceitoData $dto, User $user): Conceito
    {
        $conceito = $this->buscar($id, $user);

        $conceito->fill(array_filter([
            'nome'      => $dto->nome,
            'descricao' => $dto->descricao,
        ], fn ($v) => $v !== null));
        $conceito->save();

        return $conceito;
    }

    public function excluir(int $id, User $user): void
    {
        $conceito = $this->buscar($id, $user);

        abort_if(
            $this->perguntaRepository->existeAtivaComConceito($conceito->id),
            409,
            'Não é possível excluir um conceito utilizado por perguntas ativas.'
        );

        $conceito->delete();
    }
}
