<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\Colaborador\ColaboradorRequest;
use App\Modules\Pesquisa\Http\Requests\Colaborador\ImportarColaboradoresRequest;
use App\Modules\Pesquisa\Services\ColaboradorImportService;
use App\Modules\Pesquisa\Services\ColaboradorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColaboradorController extends PesquisaBaseController
{
    public function __construct(
        private readonly ColaboradorService $service,
        private readonly ColaboradorImportService $importService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only(['search', 'setor_id', 'filial_id', 'ativo', 'empresa_id', 'per_page']);

        return $this->respostaSucesso($this->service->listar($filtros, $request->auth_user));
    }

    public function store(ColaboradorRequest $request): JsonResponse
    {
        $colaborador = $this->service->criar($request->validated(), $request->authUser());

        return $this->respostaSucesso($colaborador, 'Colaborador cadastrado com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->buscar($id, $request->auth_user));
    }

    public function update(ColaboradorRequest $request, int $id): JsonResponse
    {
        $colaborador = $this->service->atualizar($id, $request->validated(), $request->authUser());

        return $this->respostaSucesso($colaborador, 'Colaborador atualizado com sucesso.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->excluir($id, $request->auth_user);

        return $this->respostaSucesso(null, 'Colaborador removido com sucesso.');
    }

    public function anonimizar(Request $request, int $id): JsonResponse
    {
        $colaborador = $this->service->anonimizar($id, $request->auth_user);

        return $this->respostaSucesso($colaborador, 'Dados pessoais do colaborador anonimizados com sucesso.');
    }

    /**
     * Único endpoint que devolve CPF/data de nascimento em claro — a rota em
     * si já fica registrada pelo log automático do sistema (usuário, IP e
     * timestamp), atendendo ao princípio de responsabilização da LGPD.
     */
    public function dadosSensiveis(Request $request, int $id): JsonResponse
    {
        return $this->respostaSucesso($this->service->dadosSensiveis($id, $request->auth_user));
    }

    public function importar(ImportarColaboradoresRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $resultado = $this->importService->importar($dados['conteudo_csv'], $request->authUser(), $dados['empresa_id'] ?? null);

        return $this->respostaSucesso(
            $resultado,
            "{$resultado['importados']} colaborador(es) importado(s), {$resultado['atualizados']} atualizado(s)."
        );
    }
}
