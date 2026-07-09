<?php

namespace Tests\Feature\Modules\Pesquisa;

use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Pesquisa;
use App\Modules\Pesquisa\Models\PesquisaConvite;
use App\Modules\Pesquisa\Models\PesquisaResposta;
use Database\Seeders\EmpresaDemoSeeder;
use Database\Seeders\PesquisaDemoSeeder;
use Database\Seeders\RoleSeeder;

class PesquisaDemoSeederTest extends PesquisaTestCase
{
    public function test_seeder_popula_exemplo_completo_e_e_idempotente(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(EmpresaDemoSeeder::class);

        $this->seed(PesquisaDemoSeeder::class);
        $this->seed(PesquisaDemoSeeder::class); // roda de novo — não deve duplicar nem quebrar

        $formulario = Formulario::where('codigo', 'nr1-riscos-psicossociais-demo')->first();
        $this->assertNotNull($formulario);
        $this->assertEquals('publicado', $formulario->status->value);

        $this->assertCount(3, Conceito::all());
        $this->assertEquals(4, $formulario->categorias()->count());

        $totalPerguntas = Pergunta::where('formulario_id', $formulario->id)->count();
        $this->assertEquals(11, $totalPerguntas);

        $pesquisa = Pesquisa::where('formulario_id', $formulario->id)->first();
        $this->assertNotNull($pesquisa);
        $this->assertEquals('ativa', $pesquisa->status->value);

        // 1 convite por usuário da Empresa Demo (admin criado pelo EmpresaDemoSeeder)
        $this->assertGreaterThanOrEqual(1, PesquisaConvite::where('pesquisa_id', $pesquisa->id)->count());

        // exatamente 1 resposta de exemplo, mesmo rodando o seeder duas vezes
        $this->assertEquals(1, PesquisaResposta::where('pesquisa_id', $pesquisa->id)->count());

        $conviteRespondido = PesquisaConvite::where('pesquisa_id', $pesquisa->id)->whereNotNull('respondido_em')->first();
        $this->assertNotNull($conviteRespondido);
    }
}
