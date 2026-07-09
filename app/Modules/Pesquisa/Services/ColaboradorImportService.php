<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\Filial;
use App\Models\User;
use App\Modules\Pesquisa\Models\Colaborador;
use App\Modules\Pesquisa\Models\Setor;
use Illuminate\Support\Facades\DB;

/**
 * Importação em massa de colaboradores via CSV. Cada linha é processada de
 * forma independente (uma linha inválida não interrompe as demais) e o
 * relatório final nunca inclui o CPF em claro — apenas o número da linha e o
 * nome, para não vazar dado sensível em uma tela de resultado de importação
 * ou em um log.
 */
class ColaboradorImportService
{
    private const COLUNAS_RECONHECIDAS = ['nome', 'cpf', 'data_nascimento', 'email', 'cargo', 'matricula', 'setor', 'filial'];

    public function importar(string $conteudoCsv, User $user, ?int $empresaIdSolicitada = null): array
    {
        $empresaId = $this->empresaAlvo($user, $empresaIdSolicitada);
        $linhas = $this->parseCsv($conteudoCsv);

        abort_if(empty($linhas), 422, 'Arquivo CSV vazio ou em formato inválido.');

        $cabecalho = array_map(fn ($c) => strtolower(trim($c)), array_shift($linhas));

        $setoresPorNome = Setor::where('empresa_id', $empresaId)->get()->keyBy(fn ($s) => mb_strtolower($s->nome));
        $filiaisPorNome = Filial::where('empresa_id', $empresaId)->get()->keyBy(fn ($f) => mb_strtolower($f->nome));

        $importados = 0;
        $atualizados = 0;
        $erros = [];
        $avisos = [];

        foreach ($linhas as $indice => $linha) {
            $numeroLinha = $indice + 2; // +1 pelo cabeçalho, +1 porque $indice é 0-based

            if (count(array_filter($linha, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // linha em branco
            }

            $dados = $this->combinarLinha($cabecalho, $linha);

            try {
                DB::transaction(function () use ($dados, $empresaId, $user, $setoresPorNome, $filiaisPorNome, $numeroLinha, &$importados, &$atualizados, &$avisos) {
                    abort_if(empty($dados['nome']), 422, 'Coluna "nome" é obrigatória.');

                    $setorId = null;
                    if (! empty($dados['setor'])) {
                        $setor = $setoresPorNome->get(mb_strtolower(trim($dados['setor'])));
                        if ($setor) {
                            $setorId = $setor->id;
                        } else {
                            $avisos[] = "Linha {$numeroLinha}: setor \"{$dados['setor']}\" não encontrado — colaborador importado sem setor.";
                        }
                    }

                    $filialId = null;
                    if (! empty($dados['filial'])) {
                        $filial = $filiaisPorNome->get(mb_strtolower(trim($dados['filial'])));
                        if ($filial) {
                            $filialId = $filial->id;
                        } else {
                            $avisos[] = "Linha {$numeroLinha}: filial \"{$dados['filial']}\" não encontrada — colaborador importado sem filial.";
                        }
                    }

                    $cpfDigitos = ! empty($dados['cpf']) ? preg_replace('/\D/', '', $dados['cpf']) : null;
                    $cpfHash = $cpfDigitos ? hash('sha256', $cpfDigitos) : null;

                    $existente = $cpfHash
                        ? Colaborador::where('empresa_id', $empresaId)->where('cpf_hash', $cpfHash)->first()
                        : (! empty($dados['matricula'])
                            ? Colaborador::where('empresa_id', $empresaId)->where('matricula', $dados['matricula'])->first()
                            : null);

                    $atributos = array_filter([
                        'filial_id'       => $filialId,
                        'setor_id'        => $setorId,
                        'matricula'       => $dados['matricula'] ?? null,
                        'nome'            => $dados['nome'],
                        'email'           => $dados['email'] ?? null,
                        'cargo'           => $dados['cargo'] ?? null,
                        'cpf'             => $dados['cpf'] ?? null,
                        'data_nascimento' => $dados['data_nascimento'] ?? null,
                    ], fn ($v) => $v !== null);

                    if ($existente) {
                        $existente->fill($atributos);
                        $existente->save();
                        $atualizados++;
                    } else {
                        Colaborador::create(array_merge($atributos, [
                            'empresa_id'       => $empresaId,
                            'ativo'            => true,
                            'origem'           => 'importacao_csv',
                            'importado_por'    => $user->id,
                            'base_legal_lgpd'  => 'Execução de política de saúde e segurança do trabalho (NR-1) e cumprimento de obrigação legal do empregador.',
                            'consentimento_em' => now(),
                        ]));
                        $importados++;
                    }
                });
            } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                $erros[] = ['linha' => $numeroLinha, 'nome' => $dados['nome'] ?? '(sem nome)', 'motivo' => $e->getMessage()];
            } catch (\Throwable $e) {
                $erros[] = ['linha' => $numeroLinha, 'nome' => $dados['nome'] ?? '(sem nome)', 'motivo' => 'Erro inesperado ao processar a linha.'];
            }
        }

        return [
            'importados'  => $importados,
            'atualizados' => $atualizados,
            'avisos'      => $avisos,
            'erros'       => $erros,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $conteudo): array
    {
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo); // remove BOM, se houver
        $primeiraLinha = strtok($conteudo, "\n") ?: '';
        $delimitador = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $conteudo);
        rewind($stream);

        $linhas = [];
        while (($linha = fgetcsv($stream, 0, $delimitador)) !== false) {
            $linhas[] = $linha;
        }
        fclose($stream);

        return $linhas;
    }

    /**
     * @param  string[]  $cabecalho
     * @param  string[]  $linha
     * @return array<string, string>
     */
    private function combinarLinha(array $cabecalho, array $linha): array
    {
        $dados = [];
        foreach ($cabecalho as $i => $coluna) {
            if (in_array($coluna, self::COLUNAS_RECONHECIDAS, true)) {
                $dados[$coluna] = isset($linha[$i]) ? trim((string) $linha[$i]) : null;
            }
        }

        return $dados;
    }

    private function empresaAlvo(User $user, ?int $empresaIdSolicitada = null): int
    {
        if ($user->isSuperAdmin() && $empresaIdSolicitada) {
            return $empresaIdSolicitada;
        }

        abort_if(! $user->empresa_id, 422, 'Usuário não está vinculado a uma empresa.');

        return $user->empresa_id;
    }
}
