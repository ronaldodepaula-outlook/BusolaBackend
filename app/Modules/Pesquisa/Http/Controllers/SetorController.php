<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Setor\SetorRequest;
use App\Modules\Pesquisa\Services\SetorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetorController extends PesquisaBaseController
{
    public function __construct(private readonly SetorService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respostaSucesso($this->service->listar($request->auth_user));
    }

    public function store(SetorRequest $request): JsonResponse
    {
        $setor = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($setor, 'Setor criado com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    public function update(SetorRequest $request, int $id): JsonResponse
    {
        $setor = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($setor, 'Setor atualizado com sucesso.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Setor removido com sucesso.');
    }

    public function definirUsuario(Request $request, int $id): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $this->service->definirSetorDoUsuario((int) $request->input('user_id'), $id, $request->auth_user);

        return $this->respostaSucesso(null, 'Setor do colaborador atualizado com sucesso.');
    }
}
