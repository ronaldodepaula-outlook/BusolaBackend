<?php

namespace App\Modules\Pesquisa\Services\Versionamento;

use App\Models\Log;
use App\Models\User;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Subcategoria;
use App\Modules\Pesquisa\Repositories\FormularioRepository;
use Illuminate\Support\Facades\DB;

/**
 * Ponto único de decisão sobre editar um formulário in-place ou criar uma
 * nova versão. Ver plano de implementação (seção "Estratégia de
 * versionamento") para o racional completo.
 */
class FormularioVersionador
{
    public function __construct(
        private readonly FormularioRepository $formularioRepository,
    ) {
    }

    public function precisaNovaVersao(Formulario $formulario): bool
    {
        return $formulario->ativo && $this->formularioRepository->existeEncerradaParaFormulario($formulario->id);
    }

    /**
     * Resolve o Formulario a ser efetivamente editado: o mesmo, se ainda não
     * está travado por uma pesquisa encerrada, ou uma nova versão clonada.
     *
     * @return array{formulario: Formulario, versionado: bool}
     */
    public function resolverParaEdicao(Formulario $formulario, User $user): array
    {
        abort_if(
            ! $formulario->ativo,
            409,
            'Esta é uma versão arquivada deste formulário e não pode mais ser editada.'
        );

        if (! $this->precisaNovaVersao($formulario)) {
            return ['formulario' => $formulario, 'versionado' => false];
        }

        $nova = DB::transaction(fn () => $this->criarNovaVersao($formulario, $user, 'auto'));

        return ['formulario' => $nova, 'versionado' => true];
    }

    /**
     * Força a criação de uma nova versão, independentemente de o formulário
     * estar ou não travado por uma pesquisa encerrada.
     */
    public function forcarNovaVersao(Formulario $formulario, User $user): Formulario
    {
        return DB::transaction(fn () => $this->criarNovaVersao($formulario, $user, 'manual'));
    }

    private function criarNovaVersao(Formulario $formulario, User $user, string $motivo): Formulario
    {
        $raizId = $formulario->raizId();
        $proximaVersao = $this->formularioRepository->proximaVersao($raizId);
        $snapshotAntigo = $this->snapshot($formulario);

        $nova = Formulario::create([
            'formulario_raiz_id'   => $raizId,
            'empresa_id'           => $formulario->empresa_id,
            'padrao_formulario_id' => $formulario->padrao_formulario_id,
            'nome'                 => $formulario->nome,
            'codigo'               => $formulario->codigo,
            'descricao'            => $formulario->descricao,
            'status'               => StatusFormulario::RASCUNHO,
            'tipo'                 => $formulario->tipo,
            'versao'               => $proximaVersao,
            'ativo'                => true,
            'created_by'           => $formulario->created_by,
            'updated_by'           => $user->id,
        ]);

        $this->clonarArvore($formulario, $nova);

        $formulario->update(['ativo' => false]);

        Log::create([
            'empresa_id'       => $formulario->empresa_id,
            'user_id'          => $user->id,
            'usuario_nome'     => $user->nome,
            'acao'             => $motivo === 'manual' ? 'VERSIONAMENTO_MANUAL' : 'VERSIONAMENTO_AUTOMATICO',
            'modulo'           => 'formulario',
            'rota'             => '/'.ltrim(request()->path(), '/'),
            'metodo'           => request()->method(),
            'payload'          => json_encode($this->snapshot($nova), JSON_UNESCAPED_UNICODE),
            'payload_anterior' => json_encode($snapshotAntigo, JSON_UNESCAPED_UNICODE),
            'ip'               => request()->ip(),
            'user_agent'       => request()->userAgent(),
            'status_code'      => 200,
        ]);

        return $nova;
    }

    private function clonarArvore(Formulario $antigo, Formulario $novo): void
    {
        $categorias = $antigo->categorias()->with('subcategorias.perguntas')->get();

        foreach ($categorias as $categoriaAntiga) {
            $categoriaNova = Categoria::create([
                'formulario_id' => $novo->id,
                'origem_id'     => $categoriaAntiga->id,
                'nome'          => $categoriaAntiga->nome,
                'descricao'     => $categoriaAntiga->descricao,
                'ordem'         => $categoriaAntiga->ordem,
                'ativo'         => $categoriaAntiga->ativo,
            ]);

            foreach ($categoriaAntiga->subcategorias as $subcategoriaAntiga) {
                $subcategoriaNova = Subcategoria::create([
                    'categoria_id'  => $categoriaNova->id,
                    'formulario_id' => $novo->id,
                    'origem_id'     => $subcategoriaAntiga->id,
                    'nome'          => $subcategoriaAntiga->nome,
                    'descricao'     => $subcategoriaAntiga->descricao,
                    'ordem'         => $subcategoriaAntiga->ordem,
                    'ativo'         => $subcategoriaAntiga->ativo,
                ]);

                foreach ($subcategoriaAntiga->perguntas as $perguntaAntiga) {
                    Pergunta::create([
                        'subcategoria_id'     => $subcategoriaNova->id,
                        'formulario_id'       => $novo->id,
                        'conceito_id'         => $perguntaAntiga->conceito_id,
                        'origem_id'           => $perguntaAntiga->id,
                        'tipo_pergunta'       => $perguntaAntiga->tipo_pergunta,
                        'texto'               => $perguntaAntiga->texto,
                        'descricao'           => $perguntaAntiga->descricao,
                        'obrigatoria'         => $perguntaAntiga->obrigatoria,
                        'permite_observacao'  => $perguntaAntiga->permite_observacao,
                        'permite_anexo'       => $perguntaAntiga->permite_anexo,
                        'ordem'               => $perguntaAntiga->ordem,
                        'ativo'               => $perguntaAntiga->ativo,
                    ]);
                }
            }
        }
    }

    private function snapshot(Formulario $formulario): array
    {
        return [
            'id'     => $formulario->id,
            'nome'   => $formulario->nome,
            'codigo' => $formulario->codigo,
            'versao' => $formulario->versao,
            'status' => $formulario->status instanceof \BackedEnum ? $formulario->status->value : $formulario->status,
            'ativo'  => $formulario->ativo,
        ];
    }
}
