<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\PesquisaData;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\StatusPesquisa;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaPublico;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use App\Modules\Pesquisa\Repositories\PesquisaRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PesquisaService
{
    public function __construct(
        private readonly PesquisaRepository $pesquisaRepository,
        private readonly FormularioRepository $formularioRepository,
        private readonly ConviteService $conviteService,
    ) {
    }

    public function listar(array $filtros, User $user): LengthAwarePaginator
    {
        return $this->pesquisaRepository->paginar($filtros, $user);
    }

    public function buscar(int $id, User $user): Pesquisa
    {
        $pesquisa = $this->pesquisaRepository->buscarPorId($id, $user);

        abort_if(! $pesquisa, 404, 'Campanha não encontrada.');

        return $pesquisa;
    }

    public function criar(PesquisaData $dto, User $user): Pesquisa
    {
        $empresaId = $user->isSuperAdmin() ? $dto->empresaId : $user->empresa_id;
        abort_if(! $empresaId, 422, 'Informe a empresa da campanha.');

        $formulario = $this->formularioRepository->buscarPorId($dto->formularioId, $user);
        abort_if(! $formulario, 404, 'Formulário não encontrado.');
        abort_if(
            $formulario->status !== StatusFormulario::PUBLICADO || ! $formulario->ativo,
            422,
            'Só é possível criar uma campanha a partir de um formulário publicado e vigente.'
        );

        return Pesquisa::create([
            'empresa_id'    => $empresaId,
            'formulario_id' => $formulario->id,
            'status'        => StatusPesquisa::RASCUNHO,
            'criado_por'    => $user->id,
        ]);
    }

    public function atualizar(int $id, PesquisaData $dto, User $user): Pesquisa
    {
        $pesquisa = $this->buscar($id, $user);
        $this->garantirRascunho($pesquisa);

        $pesquisa->fill(array_filter([
            'nome'        => $dto->nome,
            'descricao'   => $dto->descricao,
            'data_inicio' => $dto->dataInicio,
            'data_fim'    => $dto->dataFim,
            'anonima'     => $dto->anonima,
        ], fn ($v) => $v !== null));
        $pesquisa->save();

        return $pesquisa;
    }

    /**
     * @param  int[]  $ids
     */
    public function definirPublico(int $id, string $tipo, array $ids, User $user): Pesquisa
    {
        $pesquisa = $this->buscar($id, $user);
        $this->garantirRascunho($pesquisa);

        $pesquisa->publico()->delete();

        if ($tipo === 'filiais') {
            foreach ($ids as $filialId) {
                PesquisaPublico::create(['pesquisa_id' => $pesquisa->id, 'filial_id' => $filialId]);
            }
        } elseif ($tipo === 'colaboradores') {
            foreach ($ids as $colaboradorId) {
                PesquisaPublico::create(['pesquisa_id' => $pesquisa->id, 'colaborador_id' => $colaboradorId]);
            }
        }
        // tipo === 'toda_empresa' => nenhuma linha (comportamento padrão implícito)

        return $pesquisa->fresh('publico');
    }

    public function publicar(int $id, User $user): Pesquisa
    {
        $pesquisa = $this->buscar($id, $user);
        abort_if($pesquisa->status !== StatusPesquisa::RASCUNHO, 409, 'Só é possível publicar uma campanha em rascunho.');

        $formulario = $pesquisa->formulario;
        abort_if(
            $formulario->status !== StatusFormulario::PUBLICADO || ! $formulario->ativo,
            422,
            'O formulário vinculado a esta campanha não está mais publicado/vigente.'
        );

        $pesquisa->update([
            'status'             => StatusPesquisa::ATIVA,
            'link_publico_token' => $pesquisa->link_publico_token ?? Str::random(48),
        ]);

        $this->conviteService->gerarConvites($pesquisa);

        return $pesquisa;
    }

    public function encerrar(int $id, User $user): Pesquisa
    {
        $pesquisa = $this->buscar($id, $user);
        abort_if($pesquisa->status !== StatusPesquisa::ATIVA, 409, 'Só é possível encerrar uma campanha ativa.');

        $pesquisa->update(['status' => StatusPesquisa::ENCERRADA]);

        return $pesquisa;
    }

    public function excluir(int $id, User $user): void
    {
        $pesquisa = $this->buscar($id, $user);
        $this->garantirRascunho($pesquisa);

        $pesquisa->delete();
    }

    /**
     * Exclusão definitiva e irreversível de uma campanha, em qualquer status,
     * incluindo todo o conteúdo já coletado (convites, respostas, plano de
     * ação e relatórios técnicos). Reservada ao super administrador para
     * manutenção/limpeza do sistema — não é a exclusão de rascunho de
     * excluir(), que é auto-serviço do próprio administrador da empresa.
     */
    public function excluirDefinitivamente(int $id, User $user): void
    {
        abort_unless($user->isSuperAdmin(), 403, 'Apenas o super administrador pode excluir uma campanha definitivamente.');

        $pesquisa = Pesquisa::withTrashed()->find($id);
        abort_if(! $pesquisa, 404, 'Campanha não encontrada.');

        $caminhosRelatorios = $pesquisa->relatoriosTecnicos()->pluck('arquivo_path');

        DB::transaction(function () use ($pesquisa) {
            $pesquisa->forceDelete();
        });

        foreach ($caminhosRelatorios as $caminho) {
            Storage::disk('local')->delete($caminho);
        }
    }

    private function garantirRascunho(Pesquisa $pesquisa): void
    {
        abort_if(
            $pesquisa->status !== StatusPesquisa::RASCUNHO,
            409,
            'Esta ação só é permitida enquanto a campanha está em rascunho.'
        );
    }
}
