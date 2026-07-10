<?php

namespace Tests\Feature\Auth;

use App\Mail\RecuperacaoSenhaMail;
use App\Models\User;
use App\Services\Auth\TokenSenhaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicita_recuperacao_para_email_existente_envia_email(): void
    {
        Mail::fake();
        $usuario = User::factory()->create(['email' => 'existe@empresa-teste.com']);

        $resposta = $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'existe@empresa-teste.com']);

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);
        Mail::assertSent(RecuperacaoSenhaMail::class, fn ($mail) => $mail->hasTo('existe@empresa-teste.com'));

        $usuario->refresh();
        $this->assertNotNull($usuario->token_reset_senha);
    }

    public function test_solicita_recuperacao_para_email_inexistente_retorna_mesma_mensagem_generica_sem_enviar_email(): void
    {
        Mail::fake();

        $resposta = $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'nao.existe@empresa-teste.com']);

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);
        Mail::assertNothingSent();
    }

    public function test_nao_envia_link_de_recuperacao_para_conta_bloqueada(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'bloqueado@empresa-teste.com', 'status' => 'bloqueado']);

        $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'bloqueado@empresa-teste.com'])
            ->assertStatus(200);

        Mail::assertNothingSent();
    }

    public function test_respostas_de_email_existente_e_inexistente_sao_indistinguiveis(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'existe2@empresa-teste.com']);

        $comEmail = $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'existe2@empresa-teste.com']);
        $semEmail = $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'inexistente2@empresa-teste.com']);

        $this->assertSame($comEmail->status(), $semEmail->status());
        $this->assertSame($comEmail->json('mensagem'), $semEmail->json('mensagem'));
    }

    public function test_valida_token_de_recuperacao_valido(): void
    {
        $usuario = User::factory()->create();
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));

        $this->getJson("/api/v1/auth/resetar-senha/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('dados.nome', $usuario->nome);
    }

    public function test_token_de_recuperacao_invalido_retorna_422(): void
    {
        $this->getJson('/api/v1/auth/resetar-senha/token-que-nao-existe')->assertStatus(422);
    }

    public function test_conclui_redefinicao_altera_senha_e_permite_login(): void
    {
        $usuario = User::factory()->create(['email' => 'redefinindo@empresa-teste.com']);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));

        $resposta = $this->postJson('/api/v1/auth/resetar-senha', [
            'token'             => $token,
            'senha'             => 'NovaSenhaForte1!',
            'confirmacao_senha' => 'NovaSenhaForte1!',
        ]);

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);

        $usuario->refresh();
        $this->assertTrue(Hash::check('NovaSenhaForte1!', $usuario->senha));
        $this->assertNull($usuario->token_reset_senha);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'redefinindo@empresa-teste.com',
            'senha' => 'NovaSenhaForte1!',
        ])->assertStatus(200)->assertJsonPath('sucesso', true);
    }

    public function test_redefinicao_reativa_conta_que_nunca_havia_sido_ativada(): void
    {
        // Usuário criado pelo Fluxo 1, mas que nunca clicou no link de
        // ativação e em vez disso usou "esqueci minha senha" — precisa sair
        // dali com a conta plenamente utilizável, não apenas com uma senha
        // nova numa conta ainda "inativa".
        $usuario = User::factory()->create(['senha' => null, 'status' => 'inativo', 'primeiro_acesso' => true]);
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));

        $this->postJson('/api/v1/auth/resetar-senha', [
            'token'             => $token,
            'senha'             => 'NovaSenhaForte1!',
            'confirmacao_senha' => 'NovaSenhaForte1!',
        ])->assertStatus(200);

        $usuario->refresh();
        $this->assertSame('ativo', $usuario->status);
        $this->assertFalse($usuario->primeiro_acesso);
    }

    public function test_token_de_recuperacao_expirado_nao_pode_ser_usado(): void
    {
        $usuario = User::factory()->create();
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->subMinute());

        $this->postJson('/api/v1/auth/resetar-senha', [
            'token'             => $token,
            'senha'             => 'NovaSenhaForte1!',
            'confirmacao_senha' => 'NovaSenhaForte1!',
        ])->assertStatus(422);
    }

    public function test_token_de_recuperacao_so_pode_ser_usado_uma_unica_vez(): void
    {
        $usuario = User::factory()->create();
        $token = app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));

        $payload = ['token' => $token, 'senha' => 'NovaSenhaForte1!', 'confirmacao_senha' => 'NovaSenhaForte1!'];

        $this->postJson('/api/v1/auth/resetar-senha', $payload)->assertStatus(200);
        $this->postJson('/api/v1/auth/resetar-senha', $payload)->assertStatus(422);
    }

    public function test_gerar_novo_token_invalida_o_token_anterior(): void
    {
        $usuario = User::factory()->create();
        $tokenAntigo = app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));
        app(TokenSenhaService::class)->gerar($usuario, now()->addMinutes(30));

        $this->postJson('/api/v1/auth/resetar-senha', [
            'token'             => $tokenAntigo,
            'senha'             => 'NovaSenhaForte1!',
            'confirmacao_senha' => 'NovaSenhaForte1!',
        ])->assertStatus(422);
    }

    public function test_endpoint_de_solicitacao_tem_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'qualquer@empresa-teste.com'])
                ->assertStatus(200);
        }

        $this->postJson('/api/v1/auth/recuperar-senha', ['email' => 'qualquer@empresa-teste.com'])
            ->assertStatus(429);
    }
}
