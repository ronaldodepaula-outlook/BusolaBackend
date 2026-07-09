<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\DTOs\PerguntaData;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Subcategoria;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use App\Modules\Pesquisa\Repositories\PerguntaRepository;
use App\Modules\Pesquisa\Repositories\SubcategoriaRepository;
use App\Modules\Pesquisa\Services\Versionamento\FormularioVersionador;
use Illuminate\Database\Eloquent\Collection;

class PerguntaService
{
    public function __construct(
        private readonly PerguntaRepository $perguntaRepository,
        private readonly SubcategoriaRepository $subcategoriaRepository,
        private readonly FormularioRepository $formularioRepository,
        private readonly FormularioVersionador $versionador,
    ) {
    }

    public function listar(int $subcategoriaId, User $user): Collection
    {
        $this->buscarSubcategoriaVisivel($subcategoriaId, $user);

        return $this->perguntaRepository->listarPorSubcategoria($subcategoriaId);
    }

    public function buscar(int $id, User $user): Pergunta
    {
        $pergunta = $this->perguntaRepository->buscarPorId($id);
        abort_if(! $pergunta, 404, 'Pergunta não encontrada.');

        $this->buscarFormularioVisivel($pergunta->formulario_id, $user);

        return $pergunta;
    }

    /**
     * @return array{pergunta: Pergunta, versionado: bool, formulario_atual_id: int}
     */
    public function criar(int $subcategoriaId, PerguntaData $dto, User $user): array
    {
        $subcategoria = $this->buscarSubcategoriaVisivel($subcategoriaId, $user);

        if ($dto->conceitoId) {
            $this->validarConceitoCompativel($dto->conceitoId, $subcategoria->formulario);
        }

        $resolucao = $this->versionador->resolverParaEdicao($subcategoria->formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $subcategoriaAlvo = $resolucao['versionado']
            ? $this->subcategoriaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $subcategoria->id)
            : $subcategoria;

        abort_if(! $subcategoriaAlvo, 500, 'Falha ao localizar a subcategoria na nova versão do formulário.');

        $pergunta = Pergunta::create([
            'subcategoria_id'    => $subcategoriaAlvo->id,
            'formulario_id'      => $formularioAlvo->id,
            'conceito_id'        => $dto->conceitoId,
            'tipo_pergunta'      => $dto->tipoPergunta,
            'texto'              => $dto->texto,
            'descricao'          => $dto->descricao,
            'obrigatoria'        => $dto->obrigatoria ?? true,
            'permite_observacao' => $dto->permiteObservacao ?? false,
            'permite_anexo'      => $dto->permiteAnexo ?? false,
            'ordem'              => $dto->ordem ?? $this->perguntaRepository->proximaOrdem($subcategoriaAlvo->id),
            'ativo'              => true,
        ]);

        return [
            'pergunta'            => $pergunta,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{pergunta: Pergunta, versionado: bool, formulario_atual_id: int}
     */
    public function atualizar(int $id, PerguntaData $dto, User $user): array
    {
        $pergunta = $this->buscar($id, $user);
        $formulario = $pergunta->formulario;

        if ($dto->conceitoId) {
            $this->validarConceitoCompativel($dto->conceitoId, $formulario);
        }

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->perguntaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $pergunta->id)
            : $pergunta;

        abort_if(! $alvo, 500, 'Falha ao localizar a pergunta na nova versão do formulário.');

        $alvo->fill(array_filter([
            'tipo_pergunta'      => $dto->tipoPergunta,
            'texto'              => $dto->texto,
            'descricao'          => $dto->descricao,
            'obrigatoria'        => $dto->obrigatoria,
            'permite_observacao' => $dto->permiteObservacao,
            'permite_anexo'      => $dto->permiteAnexo,
            'conceito_id'        => $dto->conceitoId,
            'ordem'              => $dto->ordem,
        ], fn ($v) => $v !== null));
        $alvo->save();

        return [
            'pergunta'            => $alvo,
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function excluir(int $id, User $user): array
    {
        $pergunta = $this->buscar($id, $user);
        $formulario = $pergunta->formulario;

        $resolucao = $this->versionador->resolverParaEdicao($formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        $alvo = $resolucao['versionado']
            ? $this->perguntaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $pergunta->id)
            : $pergunta;

        $alvo?->delete();

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    /**
     * @param  int[]  $idsOrdenados  IDs de pergunta na ordem desejada
     * @return array{versionado: bool, formulario_atual_id: int}
     */
    public function reordenar(int $subcategoriaId, array $idsOrdenados, User $user): array
    {
        $subcategoria = $this->buscarSubcategoriaVisivel($subcategoriaId, $user);

        $perguntas = $this->perguntaRepository->listarPorSubcategoria($subcategoriaId)->keyBy('id');

        foreach ($idsOrdenados as $id) {
            abort_if(! $perguntas->has($id), 422, "A pergunta {$id} não pertence a esta subcategoria.");
        }

        $resolucao = $this->versionador->resolverParaEdicao($subcategoria->formulario, $user);
        $formularioAlvo = $resolucao['formulario'];

        foreach ($idsOrdenados as $index => $idOriginal) {
            $alvo = $resolucao['versionado']
                ? $this->perguntaRepository->buscarEquivalenteNaVersao($formularioAlvo->id, $idOriginal)
                : $perguntas->get($idOriginal);

            $alvo?->update(['ordem' => $index + 1]);
        }

        return [
            'versionado'          => $resolucao['versionado'],
            'formulario_atual_id' => $formularioAlvo->id,
        ];
    }

    private function validarConceitoCompativel(int $conceitoId, Formulario $formulario): void
    {
        $conceito = Conceito::find($conceitoId);
        abort_if(! $conceito, 422, 'Conceito de avaliação não encontrado.');

        if ($conceito->empresa_id !== null) {
            abort_if(
                $conceito->empresa_id !== $formulario->empresa_id,
                422,
                'O conceito de avaliação selecionado não pertence à empresa deste formulário.'
            );
        }
    }

    private function buscarSubcategoriaVisivel(int $subcategoriaId, User $user): Subcategoria
    {
        $subcategoria = $this->subcategoriaRepository->buscarPorId($subcategoriaId);
        abort_if(! $subcategoria, 404, 'Subcategoria não encontrada.');

        $this->buscarFormularioVisivel($subcategoria->formulario_id, $user);

        return $subcategoria;
    }

    private function buscarFormularioVisivel(int $formularioId, User $user): Formulario
    {
        $formulario = $this->formularioRepository->buscarPorId($formularioId, $user);
        abort_if(! $formulario, 404, 'Formulário não encontrado.');

        return $formulario;
    }
}
