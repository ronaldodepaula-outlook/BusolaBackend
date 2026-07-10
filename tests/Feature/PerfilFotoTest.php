<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PerfilFotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function headersParaUsuario(User $user): array
    {
        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
    }

    public function test_usuario_autenticado_envia_foto_de_perfil_com_sucesso(): void
    {
        $user = User::factory()->create();

        $resposta = $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('avatar.jpg', 100),
        ], $this->headersParaUsuario($user));

        $resposta->assertStatus(200)->assertJsonPath('sucesso', true);

        $user->refresh();
        $this->assertNotNull($user->foto);
        Storage::disk('public')->assertExists($user->foto);
        // Regressão: Storage::url() sem ::disk('public') resolve pelo disco
        // padrão (local) e gera uma URL raiz errada (ex.: /storage/... sem o
        // subdiretório da aplicação) quando o app não está hospedado na raiz.
        $this->assertSame(Storage::disk('public')->url($user->foto), $resposta->json('dados.foto'));
    }

    public function test_upload_substitui_foto_anterior_e_remove_arquivo_antigo(): void
    {
        $user = User::factory()->create();
        $antiga = UploadedFile::fake()->create('antiga.jpg', 50)->store('fotos', 'public');
        $user->update(['foto' => $antiga]);

        $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('nova.jpg', 100),
        ], $this->headersParaUsuario($user))->assertStatus(200);

        Storage::disk('public')->assertMissing($antiga);

        $user->refresh();
        $this->assertNotEquals($antiga, $user->foto);
        Storage::disk('public')->assertExists($user->foto);
    }

    public function test_rejeita_arquivo_que_nao_e_imagem(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
        ], $this->headersParaUsuario($user))->assertStatus(422);
    }

    public function test_rejeita_imagem_maior_que_2mb(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('grande.jpg', 3000),
        ], $this->headersParaUsuario($user))->assertStatus(422);
    }

    public function test_endpoint_exige_autenticacao(): void
    {
        $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('avatar.jpg', 100),
        ])->assertStatus(401);
    }

    public function test_perfil_retorna_foto_url_completa_apos_upload(): void
    {
        $user = User::factory()->create();
        $headers = $this->headersParaUsuario($user);

        $this->postJson('/api/v1/perfil/foto', [
            'foto' => UploadedFile::fake()->create('avatar.jpg', 100),
        ], $headers)->assertStatus(200);

        $perfil = $this->getJson('/api/v1/perfil', $headers)->assertStatus(200);
        $user->refresh();

        $this->assertSame(Storage::disk('public')->url($user->foto), $perfil->json('dados.foto_url'));
    }
}
