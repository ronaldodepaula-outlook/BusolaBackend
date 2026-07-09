<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Ghe\GheRequest;
use App\Modules\Pesquisa\Services\GheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GheController extends PesquisaBaseController
{
    public function __construct(private readonly GheService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respostaSucesso($this->service->listar($request->auth_user));
    }

    public function store(GheRequest $request): JsonResponse
    {
        $ghe = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($ghe, 'GHE criado com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    public function update(GheRequest $request, int $id): JsonResponse
    {
        $ghe = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($ghe, 'GHE atualizado com sucesso.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'GHE removido com sucesso.');
    }
}
