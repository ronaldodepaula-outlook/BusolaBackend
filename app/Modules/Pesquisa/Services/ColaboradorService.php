<?php

namespace App\Modules\Pesquisa\Services;

use App\Models\User;
use App\Modules\Pesquisa\Models\Colaborador;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * CRUD de Colaborador, escopado por empresa. Dados sensíveis (CPF, data de
 * nascimento) nunca são incluídos nas listagens/consultas padrão — só o
 * método dedicado `dadosSensiveis()` os expõe em claro, atrás da permissão
 * `colaborador.visualizar_dados_sensiveis`.
 */
class ColaboradorService
{
    public function listar(array $filtros, User $user): LengthAwarePaginator
    {
        return Colaborador::query()
            ->where('empresa_id', $this->empresaAlvo($user, $filtros['empresa_id'] ?? null))
            ->when($filtros['search'] ?? null, fn ($q, $busca) => $q->where(function ($inner) use ($busca) {
                $inner->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%");
            }))
            ->when($filtros['setor_id'] ?? null, fn ($q, $setorId) => $q->where('setor_id', $setorId))
            ->when($filtros['filial_id'] ?? null, fn ($q, $filialId) => $q->where('filial_id', $filialId))
            ->when(array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null, fn ($q) => $q->where('ativo', (bool) $filtros['ativo']))
            ->with(['filial:id,nome', 'setor:id,nome,ghe_id'])
            ->orderBy('nome')
            ->paginate((int) ($filtros['per_page'] ?? 20));
    }

    public function buscar(int $id, User $user): Colaborador
    {
        $colaborador = Colaborador::where('empresa_id', $this->empresaAlvo($user))
            ->with(['filial:id,nome', 'setor:id,nome,ghe_id'])
            ->find($id);

        abort_if(! $colaborador, 404, 'Colaborador não encontrado.');

        return $colaborador;
    }

    public function criar(array $dados, User $user): Colaborador
    {
        $empresaId = $this->empresaAlvo($user, $dados['empresa_id'] ?? null);

        $this->garantirCpfInedito($dados['cpf'] ?? null, $empresaId);

        return Colaborador::create([
            'empresa_id'       => $empresaId,
            'filial_id'        => $dados['filial_id'] ?? null,
            'setor_id'         => $dados['setor_id'] ?? null,
            'matricula'        => $dados['matricula'] ?? null,
            'nome'             => $dados['nome'],
            'email'            => $dados['email'] ?? null,
            'cargo'            => $dados['cargo'] ?? null,
            'ativo'            => $dados['ativo'] ?? true,
            'cpf'              => $dados['cpf'] ?? null,
            'data_nascimento'  => $dados['data_nascimento'] ?? null,
            'origem'           => 'manual',
            'base_legal_lgpd'  => $dados['base_legal_lgpd'] ?? 'Execução de política de saúde e segurança do trabalho (NR-1) e cumprimento de obrigação legal do empregador.',
            'consentimento_em' => now(),
        ]);
    }

    public function atualizar(int $id, array $dados, User $user): Colaborador
    {
        $colaborador = $this->buscar($id, $user);

        if (array_key_exists('cpf', $dados) && $dados['cpf']) {
            $digitos = preg_replace('/\D/', '', $dados['cpf']);
            if (hash('sha256', $digitos) !== $colaborador->getRawOriginal('cpf_hash')) {
                $this->garantirCpfInedito($dados['cpf'], $colaborador->empresa_id, $colaborador->id);
            }
        }

        $colaborador->fill(array_filter([
            'filial_id'       => $dados['filial_id'] ?? null,
            'setor_id'        => array_key_exists('setor_id', $dados) ? $dados['setor_id'] : null,
            'matricula'       => $dados['matricula'] ?? null,
            'nome'            => $dados['nome'] ?? null,
            'email'           => $dados['email'] ?? null,
            'cargo'           => $dados['cargo'] ?? null,
            'ativo'           => array_key_exists('ativo', $dados) ? $dados['ativo'] : null,
            'cpf'             => $dados['cpf'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
        ], fn ($v) => $v !== null));
        $colaborador->save();

        return $colaborador;
    }

    public function excluir(int $id, User $user): void
    {
        $this->buscar($id, $user)->delete();
    }

    /**
     * Anonimiza um colaborador (direito de exclusão/anonimização da LGPD)
     * sem quebrar o histórico de convites já emitidos — como a resposta em
     * si nunca referencia o colaborador, isso não afeta nenhum dado de
     * pesquisa já coletado.
     */
    public function anonimizar(int $id, User $user): Colaborador
    {
        $colaborador = $this->buscar($id, $user);

        $colaborador->update([
            'nome'            => 'Colaborador removido',
            'email'           => null,
            'matricula'       => null,
            'cpf'             => null,
            'data_nascimento' => null,
            'ativo'           => false,
        ]);

        return $colaborador;
    }

    /**
     * Único ponto de leitura em claro dos dados sensíveis — a rota que
     * chama este método já fica registrada pelo log automático do sistema
     * (LogMiddleware), com usuário, IP e timestamp.
     */
    public function dadosSensiveis(int $id, User $user): array
    {
        $colaborador = $this->buscar($id, $user);

        return array_merge(
            ['id' => $colaborador->id, 'nome' => $colaborador->nome],
            $colaborador->dadosSensiveisEmClaro()
        );
    }

    private function garantirCpfInedito(?string $cpf, int $empresaId, ?int $ignorarId = null): void
    {
        if (! $cpf) {
            return;
        }

        $digitos = preg_replace('/\D/', '', $cpf);
        $hash = hash('sha256', $digitos);

        $existe = Colaborador::where('empresa_id', $empresaId)
            ->where('cpf_hash', $hash)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();

        abort_if($existe, 422, 'Já existe um colaborador com este CPF nesta empresa.');
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
