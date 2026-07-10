<?php

namespace Tests\Feature\Auth;

use App\Mail\ConviteAtivacaoContaMail;
use App\Models\Empresa;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\TokenSenhaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AtivacaoContaTest extends TestCase
{
    use RefreshDatabase;

    private function headersParaUsuario(User $user): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($user)];
    }

    /** @return array{0: User, 1: Empresa} */
    private function criarAdminComPermissaoCriarUsuario(): array
    {
        $empresa = Empresa::factory()->create(['status' => 'ativo']);
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'tipo' => 'admin']);

        $permissao = Permission::create(['nome' => 'Criar Usuário', 'slug' => 'usuario.criar', 'modulo' => 'usuario']);
        $role = Role::create(['empresa_id' => $empresa->id, 'nome' => 'Admin Teste', 'status' => 'ativo', 'sistema' => false]);
        $role->permissions()->attach($permissao->id);
        $admin->roles()->attach($role->id);

        return [$admin, $empresa];
    }

    public function test_admin_cria_usuario_sem_senha_dispara_convite_de_ativacao_por_email(): void
    {
        Mail::fake();
        [$admin] = $this->criarAdminComPermissaoCriarUsuario();

        $resposta = $this->postJson('/api/v1/usuarios', [
            'nome'  => 'Novo Colega',
            'email' => 'novo.colega@empresa-teste.com',
            'tipo'  => 'usuario',
        ], $this->headersParaUsuario($admin));

        $resposta->assertStatus(201)->assertJsonPath('sucesso', true);

        $this->assertDatabaseHas('users', [
            'email'  => 'novo.colega@empresa-teste.com',
            'senha'  => null,
            'status' => 'inativo',
        ]);

        Mail::assertSent(ConviteAtivacaoContaMail::class, fn ($mail) => $mail->hasTo('novo.colega@empresa-teste.com'));
    }

    public function test_admin_cria_usuario_com_senha_mantem_comportamento_anterior_sem_enviar_convite(): void
    {
        Mail::fake();
        [$admin] = $this->criarAdminComPermissaoCriarUsuario();

        $resposta = $this->postJson('/api/v1/usuarios', [
            'nome'  => 'Colega Com Senha',
            'email' => 'colega.com.senha@empresa-teste.com',
            'senha' => 'SenhaTemporaria123',
            'tipo'  => 'usuario',
        ], $this->headersParaUsuario($admin));

        $resposta->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email'  => 'colega.com.senha@empresa-teste.com',
            'status' => 'ativo',
        ]);

        $usuario = User::where('email', 'colega.com.senha@empresa-teste.com')->first();
        $this->assertNotNull($usuario->senha);

        Mail::assertNothingSent();
    }

    public function test_valida_token_de_ativacao_valido(): void
    {
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addHours(24));

        $this->getJson("/api/v1/auth/ativacao/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', $usuario->nome);
    }

    public function test_token_de_ativacao_invalido_retorna_422(): void
    {
        $this->getJson('/api/v1/auth/ativacao/token-que-nao-existe')->assertStatus(422);
    }

    public function test_conclui_ativacao_define_senha_ativa_conta_e_permite_login(): void
    {
        $usuario = User::factory()->create([
            'email'           => 'ativando@empresa-teste.com',
            'senha'           => null,
            'status'          => 'inativo',
            'primeiro_acesso' => true,
        ]);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addHours(24));

        $resposta = $this->postJson('/api/v1/auth/ativacao', [
            'token'             => $token,
            'senha'             => 'SenhaForte123!',
            'confirmacao_senha' => 'SenhaForte123!',
        ]);

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);

        $usuario->refresh();
        $this->assertTrue(Hash::check('SenhaForte123!', $usuario->senha));
        $this->assertSame('ativo', $usuario->status);
        $this->assertFalse($usuario->primeiro_acesso);
        $this->assertNull($usuario->token_reset_senha);

        // Prova de ponta a ponta: a conta recém-ativada já consegue logar.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ativando@empresa-teste.com',
            'senha' => 'SenhaForte123!',
        ])->assertStatus(200)->assertJsonPath('sucesso', true);
    }

    public function test_token_expirado_nao_pode_ser_usado(): void
    {
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->subMinute());

        $this->postJson('/api/v1/auth/ativacao', [
            'token'             => $token,
            'senha'             => 'SenhaForte123!',
            'confirmacao_senha' => 'SenhaForte123!',
        ])->assertStatus(422);
    }

    public function test_token_so_pode_ser_usado_uma_unica_vez(): void
    {
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addHours(24));

        $payload = ['token' => $token, 'senha' => 'SenhaForte123!', 'confirmacao_senha' => 'SenhaForte123!'];

        $this->postJson('/api/v1/auth/ativacao', $payload)->assertStatus(200);
        $this->postJson('/api/v1/auth/ativacao', $payload)->assertStatus(422);
    }

    public function test_senha_fora_da_politica_e_rejeitada(): void
    {
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addHours(24));

        $this->postJson('/api/v1/auth/ativacao', [
            'token'             => $token,
            'senha'             => '12345678',
            'confirmacao_senha' => '12345678',
        ])->assertStatus(422);
    }

    public function test_confirmacao_de_senha_diferente_e_rejeitada(): void
    {
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addHours(24));

        $this->postJson('/api/v1/auth/ativacao', [
            'token'             => $token,
            'senha'             => 'SenhaForte123!',
            'confirmacao_senha' => 'OutraSenha123!',
        ])->assertStatus(422);
    }
}
