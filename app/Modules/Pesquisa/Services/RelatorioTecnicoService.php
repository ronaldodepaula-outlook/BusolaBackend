<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\RelatorioTecnico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;

/**
 * Gera o Relatório Técnico (PDF) de uma campanha, consolidando resultado por
 * categoria/GHE, classificação de risco, plano de ação e o Anexo I de
 * referência — no mesmo formato do relatório técnico modelo da metodologia
 * COPSOQ II / NR-1. O arquivo é salvo em disco privado (nunca público) e
 * cada geração fica registrada para consulta posterior, inclusive na tela
 * de gestão cross-empresa do super administrador.
 */
class RelatorioTecnicoService
{
    private const DISCO = 'local';
    private const DIRETORIO = 'relatorios-tecnicos';

    public function __construct(
        private readonly ResultadoService $resultadoService,
        private readonly PlanoAcaoService $planoAcaoService,
    ) {
    }

    public function gerar(int $pesquisaId, User $user, array $responsavelTecnico = []): RelatorioTecnico
    {
        $resultado = $this->resultadoService->resultados($pesquisaId, $user);
        $planoAcao = $this->planoAcaoService->listar($pesquisaId, $user);

        $pesquisa = Pesquisa::with('empresa')->findOrFail($pesquisaId);
        $empresa = $pesquisa->empresa;

        $composicaoGhe = $this->composicaoGhe($empresa->id);

        $categoriasReferenciadas = collect($resultado['categorias'])
            ->filter(fn ($c) => ! empty($c['categoria_referencia']))
            ->map(fn ($c) => CategoriaReferencia::from($c['categoria_referencia']))
            ->unique('value');

        $pdf = Pdf::loadView('pesquisa::relatorios.tecnico', [
            'empresa'          => $empresa,
            'pesquisa'         => $pesquisa,
            'resultado'        => $resultado,
            'composicaoGhe'    => $composicaoGhe,
            'planoAcao'        => $planoAcao->groupBy('categoria.nome'),
            'anexoI'           => $categoriasReferenciadas,
            'anexoII'          => $this->anexoII($pesquisa->formulario_id, $categoriasReferenciadas),
            'responsavel'      => $responsavelTecnico,
            'geradoEm'         => now(),
        ])->setPaper('a4');

        $conteudo = $pdf->output();
        $nomeArquivo = sprintf('pesquisa-%d-%s.pdf', $pesquisaId, now()->format('YmdHis'));
        $caminho = self::DIRETORIO."/{$empresa->id}/{$nomeArquivo}";

        Storage::disk(self::DISCO)->put($caminho, $conteudo);

        return RelatorioTecnico::create([
            'pesquisa_id'                   => $pesquisaId,
            'empresa_id'                    => $empresa->id,
            'gerado_por'                    => $user->id,
            'responsavel_tecnico_nome'      => $responsavelTecnico['nome'] ?? null,
            'responsavel_tecnico_registro'  => $responsavelTecnico['registro'] ?? null,
            'arquivo_path'                  => $caminho,
            'tamanho_bytes'                 => strlen($conteudo),
            'gerado_em'                     => now(),
        ]);
    }

    public function listarPorEmpresa(int $empresaId): Collection
    {
        return RelatorioTecnico::where('empresa_id', $empresaId)
            ->with('pesquisa', 'geradoPor')
            ->orderByDesc('gerado_em')
            ->get();
    }

    /** Listagem cross-empresa para a tela de gestão do super administrador. */
    public function listarTodas(?int $empresaId, int $porPagina = 20): LengthAwarePaginator
    {
        return RelatorioTecnico::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->with(['pesquisa', 'empresa', 'geradoPor'])
            ->orderByDesc('gerado_em')
            ->paginate($porPagina);
    }

    public function buscar(int $id, User $user): RelatorioTecnico
    {
        $relatorio = RelatorioTecnico::with('empresa', 'pesquisa')->find($id);
        abort_if(! $relatorio, 404, 'Relatório não encontrado.');

        if (! $user->isSuperAdmin()) {
            abort_if($relatorio->empresa_id !== $user->empresa_id, 403, 'Sem acesso a este relatório.');
        }

        return $relatorio;
    }

    public function conteudo(RelatorioTecnico $relatorio): string
    {
        abort_if(! Storage::disk(self::DISCO)->exists($relatorio->arquivo_path), 404, 'Arquivo do relatório não encontrado.');

        return Storage::disk(self::DISCO)->get($relatorio->arquivo_path);
    }

    /**
     * Anexo II — perguntas do formulário efetivamente aplicado, agrupadas
     * pela categoria de referência oficial. Gerado dinamicamente a partir da
     * árvore real do formulário (não é uma lista fixa do COPSOQ): o número e
     * a redação original são extraídos de Pergunta::descricao quando
     * presentes (convenção usada pelo CopsoqFormularioSeeder), e a
     * adaptação é sempre Pergunta::texto.
     *
     * @return SupportCollection<string, array<int, array{numero: ?string, original: ?string, adaptacao: string}>>
     */
    private function anexoII(int $formularioId, SupportCollection $categoriasReferenciadas): SupportCollection
    {
        if ($categoriasReferenciadas->isEmpty()) {
            return collect();
        }

        $formulario = Formulario::with('categorias.subcategorias.perguntas')->find($formularioId);
        if (! $formulario) {
            return collect();
        }

        return $formulario->categorias
            ->filter(fn ($categoria) => $categoria->categoria_referencia !== null)
            ->groupBy(fn ($categoria) => $categoria->categoria_referencia->label())
            ->map(function ($categorias) {
                return $categorias->flatMap(fn ($categoria) => $categoria->subcategorias)
                    ->flatMap(fn ($subcategoria) => $subcategoria->perguntas)
                    ->map(function ($pergunta) {
                        if (preg_match('/nº\s*(\d+)\s*—\s*original:\s*(.+)$/u', (string) $pergunta->descricao, $m)) {
                            return ['numero' => $m[1], 'original' => $m[2], 'adaptacao' => $pergunta->texto];
                        }

                        return ['numero' => null, 'original' => null, 'adaptacao' => $pergunta->texto];
                    })
                    ->values();
            });
    }

    /**
     * Composição dos GHEs da empresa: setores agrupados e quantidade de
     * pessoas — Seção 4 do relatório técnico modelo.
     */
    private function composicaoGhe(int $empresaId): SupportCollection
    {
        return Ghe::where('empresa_id', $empresaId)
            ->with(['setores' => fn ($q) => $q->withCount(['colaboradores' => fn ($cq) => $cq->where('ativo', true)])])
            ->get()
            ->map(fn (Ghe $ghe) => [
                'nome'    => $ghe->nome,
                'setores' => $ghe->setores->pluck('nome')->implode(' + '),
                'total'   => $ghe->setores->sum('colaboradores_count'),
            ]);
    }
}
