<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Models\Ghe;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use App\Modules\Pesquisa\Models\Setor;
use App\Modules\Pesquisa\Models\UsuarioSetor;
use Illuminate\Database\Seeder;

/**
 * Semeia a composição de GHE de exemplo (mesma estrutura do relatório
 * técnico modelo: GHE 01 – Comercial e Relacionamento / GHE 02 – Operações e
 * Suporte) para a Empresa Demo, distribuindo os usuários existentes entre os
 * setores para que o dashboard de resultados tenha grupos reais para
 * classificar. Idempotente.
 */
class GheDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::where('cnpj', '12.345.678/0001-90')->first();

        if (! $empresa) {
            $this->command->warn('GheDemoSeeder: execute EmpresaDemoSeeder primeiro (empresa demo não encontrada).');

            return;
        }

        $gheComercial = Ghe::updateOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'GHE 01 – Comercial e Relacionamento'],
            ['descricao' => 'Agrupa os setores Comercial e Treinamento.', 'ativo' => true]
        );
        $gheOperacoes = Ghe::updateOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'GHE 02 – Operações e Suporte'],
            ['descricao' => 'Agrupa os setores Administrativo e Pós-Vendas.', 'ativo' => true]
        );

        $setores = [
            'Comercial'      => $gheComercial,
            'Treinamento'    => $gheComercial,
            'Administrativo' => $gheOperacoes,
            'Pós-Vendas'     => $gheOperacoes,
        ];

        $setoresCriados = [];
        foreach ($setores as $nome => $ghe) {
            $setoresCriados[] = Setor::updateOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $nome],
                ['ghe_id' => $ghe->id, 'ativo' => true]
            );
        }

        $usuarios = User::where('empresa_id', $empresa->id)->where('status', 'ativo')->get();
        if ($usuarios->isEmpty()) {
            $this->command->warn('GheDemoSeeder: nenhum usuário ativo encontrado na empresa demo.');

            return;
        }

        foreach ($usuarios->values() as $indice => $usuario) {
            $setor = $setoresCriados[$indice % count($setoresCriados)];

            UsuarioSetor::updateOrCreate(['user_id' => $usuario->id], ['setor_id' => $setor->id]);

            PesquisaConvite::where('user_id', $usuario->id)->whereNull('ghe_id')->update(['ghe_id' => $setor->ghe_id]);
        }

        // Distribui as respostas de exemplo já existentes entre os GHEs, só para
        // que o dashboard de resultados tenha algo para classificar por grupo.
        $pesquisaIds = Pesquisa::where('empresa_id', $empresa->id)->pluck('id');
        $respostasSemGhe = PesquisaResposta::whereIn('pesquisa_id', $pesquisaIds)->whereNull('ghe_id')->get();
        foreach ($respostasSemGhe->values() as $indice => $resposta) {
            $resposta->update(['ghe_id' => $setoresCriados[$indice % count($setoresCriados)]->ghe_id]);
        }

        $this->command?->info('GheDemoSeeder: GHEs/setores de demonstração semeados e usuários distribuídos.');
    }
}
