<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Models\Empresa;
use App\Modules\Pesquisa\Models\Colaborador;
use Illuminate\Support\Facades\DB;

class ColaboradorTest extends PesquisaTestCase
{
    public function test_crud_completo_de_colaborador(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $headers = $this->headersParaUsuario($admin);

        $idColaborador = $this->postJson('/api/v1/pesquisa-psicossocial/colaboradores', [
            'nome' => 'Maria da Silva',
            'email' => 'maria@empresa.com',
            'cargo' => 'Analista',
            'cpf'   => '123.456.789-09',
        ], $headers)->assertStatus(201)->json('dados.id');

        $this->getJson("/api/v1/pesquisa-psicossocial/colaboradores/{$idColaborador}", $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', 'Maria da Silva')
            ->assertJsonPath('dados.cpf_mascarado', '***.***.**9-09');

        $this->putJson("/api/v1/pesquisa-psicossocial/colaboradores/{$idColaborador}", ['cargo' => 'Analista Sênior'], $headers)
            ->assertStatus(200)
            ->assertJsonPath('dados.cargo', 'Analista Sênior');

        $this->deleteJson("/api/v1/pesquisa-psicossocial/colaboradores/{$idColaborador}", [], $headers)->assertStatus(200);
        $this->getJson("/api/v1/pesquisa-psicossocial/colaboradores/{$idColaborador}", $headers)->assertStatus(404);
    }

    public function test_cpf_nunca_e_gravado_em_claro_no_banco(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $id = $this->postJson('/api/v1/pesquisa-psicossocial/colaboradores', [
            'nome' => 'João Pereira',
            'cpf'  => '111.222.333-44',
        ], $this->headersParaUsuario($admin))->json('dados.id');

        $bruto = DB::table('pesq_colaboradores')->where('id', $id)->first();

        $this->assertStringNotContainsString('111222333', $bruto->cpf);
        $this->assertStringNotContainsString('111.222.333-44', $bruto->cpf);
        $this->assertNotEmpty($bruto->cpf_hash);
        $this->assertSame(hash('sha256', '11122233344'), $bruto->cpf_hash);
    }

    public function test_listagem_padrao_nunca_expoe_cpf_em_claro_apenas_mascarado(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        Colaborador::factory()->create(['empresa_id' => $empresa->id, 'cpf' => '987.654.321-00']);

        $resp = $this->getJson('/api/v1/pesquisa-psicossocial/colaboradores', $this->headersParaUsuario($admin))->assertStatus(200);

        $bruto = $resp->getContent();
        $this->assertStringNotContainsString('987654321', $bruto);
        $this->assertStringNotContainsString('987.654.321-00', $bruto);
        $this->assertStringContainsString('***.***.**1-00', $bruto);
    }

    public function test_apenas_permissao_dedicada_pode_revelar_dados_sensiveis_em_claro(): void
    {
        $empresa = Empresa::factory()->create();
        $comPermissao = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $semPermissao = $this->criarUsuarioComPermissoes($empresa, ['colaborador.listar']);

        $colaborador = Colaborador::factory()->create(['empresa_id' => $empresa->id, 'cpf' => '555.666.777-88']);

        $this->getJson("/api/v1/pesquisa-psicossocial/colaboradores/{$colaborador->id}/dados-sensiveis", $this->headersParaUsuario($semPermissao))
            ->assertStatus(403);

        $this->getJson("/api/v1/pesquisa-psicossocial/colaboradores/{$colaborador->id}/dados-sensiveis", $this->headersParaUsuario($comPermissao))
            ->assertStatus(200)
            ->assertJsonPath('dados.cpf', '55566677788');
    }

    public function test_nao_permite_cpf_duplicado_na_mesma_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        Colaborador::factory()->create(['empresa_id' => $empresa->id, 'cpf' => '444.555.666-77']);

        $this->postJson('/api/v1/pesquisa-psicossocial/colaboradores', [
            'nome' => 'Outro Nome',
            'cpf'  => '444.555.666-77',
        ], $this->headersParaUsuario($admin))->assertStatus(422);
    }

    public function test_colaboradores_sao_isolados_por_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $adminB = $this->criarUsuarioComPermissoes($empresaB, $this->todasPermissoesDoModulo());
        $colaboradorA = Colaborador::factory()->create(['empresa_id' => $empresaA->id]);

        $this->getJson("/api/v1/pesquisa-psicossocial/colaboradores/{$colaboradorA->id}", $this->headersParaUsuario($adminB))
            ->assertStatus(404);
    }

    public function test_importacao_csv_cria_colaboradores_e_relata_erros_por_linha(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());

        $csv = "nome,cpf,email,cargo,matricula\n"
            ."Carlos Souza,123.111.222-33,carlos@empresa.com,Técnico,MAT001\n"
            .",456.111.222-33,semnome@empresa.com,Analista,MAT002\n"
            ."Fernanda Lima,,fernanda@empresa.com,Coordenadora,MAT003\n";

        $resp = $this->postJson('/api/v1/pesquisa-psicossocial/colaboradores/importar', [
            'conteudo_csv' => $csv,
        ], $this->headersParaUsuario($admin))->assertStatus(200);

        $this->assertSame(2, $resp->json('dados.importados')); // Carlos + Fernanda (sem CPF)
        $this->assertCount(1, $resp->json('dados.erros')); // linha sem nome
        $this->assertSame(3, $resp->json('dados.erros.0.linha'));

        $this->assertDatabaseHas('pesq_colaboradores', ['empresa_id' => $empresa->id, 'nome' => 'Carlos Souza', 'matricula' => 'MAT001']);
        $this->assertDatabaseHas('pesq_colaboradores', ['empresa_id' => $empresa->id, 'nome' => 'Fernanda Lima', 'matricula' => 'MAT003']);
    }

    public function test_importacao_csv_atualiza_colaborador_existente_pelo_mesmo_cpf(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        Colaborador::factory()->create(['empresa_id' => $empresa->id, 'cpf' => '321.321.321-00', 'cargo' => 'Estagiário']);

        $csv = "nome,cpf,cargo\nNome Atualizado,321.321.321-00,Efetivado\n";

        $resp = $this->postJson('/api/v1/pesquisa-psicossocial/colaboradores/importar', [
            'conteudo_csv' => $csv,
        ], $this->headersParaUsuario($admin))->assertStatus(200);

        $this->assertSame(0, $resp->json('dados.importados'));
        $this->assertSame(1, $resp->json('dados.atualizados'));
        $this->assertDatabaseHas('pesq_colaboradores', ['empresa_id' => $empresa->id, 'nome' => 'Nome Atualizado', 'cargo' => 'Efetivado']);
    }

    public function test_anonimizar_remove_dados_pessoais_sem_afetar_convites_ja_gerados(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->criarUsuarioComPermissoes($empresa, $this->todasPermissoesDoModulo());
        $colaborador = Colaborador::factory()->create(['empresa_id' => $empresa->id, 'cpf' => '999.888.777-66']);

        $this->patchJson("/api/v1/pesquisa-psicossocial/colaboradores/{$colaborador->id}/anonimizar", [], $this->headersParaUsuario($admin))
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', 'Colaborador removido')
            ->assertJsonPath('dados.cpf_mascarado', null);
    }
}
