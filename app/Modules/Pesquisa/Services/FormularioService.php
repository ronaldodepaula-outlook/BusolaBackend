<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\FormularioData;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use App\Modules\Pesquisa\Services\Versionamento\FormularioVersionador;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FormularioService
{
    public function __construct(
        private readonly FormularioRepository $formularioRepository,
        private readonly FormularioVersionador $versionador,
    ) {
    }

    public function listar(array $filtros, User $user): LengthAwarePaginator
    {
        return $this->formularioRepository->paginar($filtros, $user);
    }

    public function buscar(int $id, User $user): Formulario
    {
        $formulario = $this->formularioRepository->buscarPorId($id, $user);

        abort_if(! $formulario, 404, 'Formulário não encontrado.');

        return $formulario;
    }

    public function estrutura(int $id, User $user): Formulario
    {
        $formulario = $this->formularioRepository->buscarComEstrutura($id, $user);

        abort_if(! $formulario, 404, 'Formulário não encontrado.');

        return $formulario;
    }

    public function criar(FormularioData $dto, User $user): Formulario
    {
        $tipo = $dto->tipo ?? TipoFormulario::EMPRESA;
        $empresaId = $tipo === TipoFormulario::GLOBAL
            ? null
            : ($user->isSuperAdmin() ? $dto->empresaId : $user->empresa_id);

        abort_if(
            $this->formularioRepository->existeCodigoNoEscopo($dto->codigo, $empresaId),
            422,
            'Já existe um formulário com este código neste escopo.'
        );

        return Formulario::create([
            'nome'                 => $dto->nome,
            'codigo'               => $dto->codigo,
            'descricao'            => $dto->descricao,
            'tipo'                 => $tipo,
            'empresa_id'           => $empresaId,
            'padrao_formulario_id' => $dto->padraoFormularioId,
            'status'               => StatusFormulario::RASCUNHO,
            'versao'               => 1,
            'ativo'                => true,
            'created_by'           => $user->id,
            'updated_by'           => $user->id,
        ]);
    }

    /**
     * @return array{formulario: Formulario, versionado: bool}
     */
    public function atualizar(int $id, FormularioData $dto, User $user): array
    {
        $formulario = $this->buscar($id, $user);

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $alvo = $resolucao['formulario'];

        $alvo->fill(array_filter([
            'nome'      => $dto->nome,
            'descricao' => $dto->descricao,
        ], fn ($v) => $v !== null));

        if ($dto->padraoFormularioIdInformado) {
            $alvo->padrao_formulario_id = $dto->padraoFormularioId;
        }

        $alvo->updated_by = $user->id;
        $alvo->save();

        return ['formulario' => $alvo, 'versionado' => $resolucao['versionado']];
    }

    public function publicar(int $id, User $user): Formulario
    {
        $formulario = $this->buscar($id, $user);
        abort_if(! $formulario->ativo, 409, 'Esta é uma versão arquivada e não pode ser publicada.');

        $formulario->update(['status' => StatusFormulario::PUBLICADO, 'updated_by' => $user->id]);

        return $formulario;
    }

    public function arquivar(int $id, User $user): Formulario
    {
        $formulario = $this->buscar($id, $user);
        abort_if(! $formulario->ativo, 409, 'Esta versão já está arquivada.');

        $formulario->update(['status' => StatusFormulario::ARQUIVADO, 'updated_by' => $user->id]);

        return $formulario;
    }

    public function novaVersaoManual(int $id, User $user): Formulario
    {
        $formulario = $this->buscar($id, $user);

        return $this->versionador->forcarNovaVersao($formulario, $user);
    }

    public function listarVersoes(int $id, User $user): Collection
    {
        $formulario = $this->buscar($id, $user);

        return $this->formularioRepository->todasVersoes($formulario->raizId());
    }

    public function excluir(int $id, User $user): void
    {
        $formulario = $this->buscar($id, $user);

        abort_if(
            $this->formularioRepository->existePesquisaParaGrupo($formulario->raizId()),
            409,
            'Não é possível excluir um formulário utilizado em pesquisas.'
        );

        $formulario->delete();
    }
}
