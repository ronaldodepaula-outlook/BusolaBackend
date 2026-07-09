<?php

namespace App\Modules\Pesquisa\Http\Controllers;

use App\Modules\Pesquisa\Http\Requests\RelatorioTecnico\GerarRelatorioTecnicoRequest;
use App\Modules\Pesquisa\Services\RelatorioTecnicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RelatorioTecnicoController extends PesquisaBaseController
{
    public function __construct(private readonly RelatorioTecnicoService $service)
    {
    }

    public function gerar(GerarRelatorioTecnicoRequest $request, int $pesquisa): JsonResponse
    {
        $relatorio = $this->service->gerar($pesquisa, $request->authUser(), [
            'nome'     => $request->validated('responsavel_tecnico_nome'),
            'registro' => $request->validated('responsavel_tecnico_registro'),
        ]);

        return $this->respostaSucesso($relatorio, 'Relatório técnico gerado com sucesso.', 201);
    }

    public function porEmpresa(Request $request): JsonResponse
    {
        abort_if(! $request->auth_user->empresa_id, 422, 'Usuário não está vinculado a uma empresa.');

        return $this->respostaSucesso($this->service->listarPorEmpresa($request->auth_user->empresa_id));
    }

    /** Listagem cross-empresa — apenas super administrador. */
    public function todas(Request $request): JsonResponse
    {
        abort_unless($request->auth_user->isSuperAdmin(), 403, 'Apenas o super administrador pode acessar esta listagem.');

        $empresaId = $request->integer('empresa_id') ?: null;

        return $this->respostaSucesso($this->service->listarTodas($empresaId));
    }

    public function download(Request $request, int $id): Response
    {
        $relatorio = $this->service->buscar($id, $request->auth_user);
        $conteudo = $this->service->conteudo($relatorio);

        return response($conteudo, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.basename($relatorio->arquivo_path).'"',
        ]);
    }
}
