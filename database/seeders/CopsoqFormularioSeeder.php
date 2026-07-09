<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\Pesquisa\Enums\CategoriaReferencia;
use App\Modules\Pesquisa\Enums\StatusFormulario;
use App\Modules\Pesquisa\Enums\TipoConceito;
use App\Modules\Pesquisa\Enums\TipoFormulario;
use App\Modules\Pesquisa\Enums\TipoPergunta;
use App\Modules\Pesquisa\Models\Categoria;
use App\Modules\Pesquisa\Models\Conceito;
use App\Modules\Pesquisa\Models\Formulario;
use App\Modules\Pesquisa\Models\Pergunta;
use App\Modules\Pesquisa\Models\Subcategoria;
use Illuminate\Database\Seeder;

/**
 * Semeia o formulário oficial COPSOQ II – Versão Média, com as perguntas do
 * Anexo II do relatório técnico modelo (redação original + adaptação PT-BR),
 * organizadas nas 11 categorias de risco psicossocial oficiais (Portaria
 * GM/MS nº 5.674/2024), cada uma já vinculada à sua severidade fixa via
 * categoria_referencia. Substitui o conteúdo inventado do PesquisaDemoSeeder
 * por conteúdo real da metodologia, para a mesma "Empresa Demo". Idempotente.
 */
class CopsoqFormularioSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::where('cnpj', '12.345.678/0001-90')->first();
        $admin   = User::where('email', 'admin@empresa.com')->first();

        if (! $empresa || ! $admin) {
            $this->command->warn('CopsoqFormularioSeeder: execute EmpresaDemoSeeder primeiro (empresa/usuário demo não encontrados).');

            return;
        }

        $escala = $this->criarEscalaFrequencia($empresa->id);

        $formulario = Formulario::updateOrCreate(
            ['codigo' => 'copsoq-ii-versao-media', 'empresa_id' => $empresa->id],
            [
                'nome'       => 'COPSOQ II – Versão Média (Riscos Psicossociais / NR-1)',
                'descricao'  => 'Instrumento oficial COPSOQ II adaptado ao contexto brasileiro, correlacionado aos fatores de risco psicossociais da Portaria GM/MS nº 5.674/2024.',
                'status'     => StatusFormulario::PUBLICADO,
                'tipo'       => TipoFormulario::EMPRESA,
                'versao'     => 1,
                'ativo'      => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $ordemCategoria = 0;
        foreach ($this->perguntasPorCategoria() as $categoriaRef => $perguntas) {
            $ref = CategoriaReferencia::from($categoriaRef);

            $categoria = Categoria::updateOrCreate(
                ['formulario_id' => $formulario->id, 'nome' => $ref->label()],
                [
                    'categoria_referencia' => $ref->value,
                    'severidade'           => $ref->severidadePadrao(),
                    'descricao'            => $ref->descricaoTecnica(),
                    'ordem'                => ++$ordemCategoria,
                    'ativo'                => true,
                ]
            );

            $subcategoria = Subcategoria::updateOrCreate(
                ['categoria_id' => $categoria->id, 'nome' => 'Perguntas COPSOQ II'],
                ['formulario_id' => $formulario->id, 'ordem' => 1, 'ativo' => true]
            );

            $ordemPergunta = 0;
            foreach ($perguntas as [$numero, $original, $adaptacao]) {
                Pergunta::updateOrCreate(
                    ['subcategoria_id' => $subcategoria->id, 'texto' => $adaptacao],
                    [
                        'formulario_id'      => $formulario->id,
                        'conceito_id'        => $escala->id,
                        'tipo_pergunta'      => TipoPergunta::ESCALA,
                        'descricao'          => "COPSOQ II nº {$numero} — original: {$original}",
                        'obrigatoria'        => true,
                        'permite_observacao' => false,
                        'permite_anexo'      => false,
                        'ordem'              => ++$ordemPergunta,
                        'ativo'              => true,
                    ]
                );
            }
        }

        $this->command?->info('CopsoqFormularioSeeder: formulário COPSOQ II oficial semeado com sucesso.');
    }

    private function criarEscalaFrequencia(int $empresaId): Conceito
    {
        $conceito = Conceito::updateOrCreate(
            ['empresa_id' => $empresaId, 'nome' => 'Escala de Frequência COPSOQ II'],
            ['tipo' => TipoConceito::FREQUENCIA, 'descricao' => 'Escala oficial de frequência (1 a 5) do COPSOQ II – Versão Média.', 'ativo' => true]
        );

        $itens = [
            ['Nunca / Quase nunca', 1, '#22c55e'],
            ['Raramente', 2, '#84cc16'],
            ['Às vezes', 3, '#eab308'],
            ['Frequentemente', 4, '#f97316'],
            ['Sempre', 5, '#ef4444'],
        ];

        foreach ($itens as $ordem => [$descricao, $valor, $cor]) {
            $conceito->itens()->updateOrCreate(
                ['descricao' => $descricao],
                ['valor' => $valor, 'cor' => $cor, 'ordem' => $ordem + 1]
            );
        }

        return $conceito->load('itens');
    }

    /**
     * Anexo II do relatório técnico modelo — Nº do item COPSOQ II, pergunta
     * original e adaptação para o português do Brasil, agrupadas pela
     * categoria de risco psicossocial oficial a que pertencem.
     *
     * @return array<string, array<int, array{0:int,1:string,2:string}>>
     */
    private function perguntasPorCategoria(): array
    {
        return [
            CategoriaReferencia::GESTAO_ORGANIZACIONAL->value => [
                [16, 'No seu local de trabalho, é informado com antecedência sobre decisões importantes, mudanças ou planos para o futuro?', 'Você recebe comunicação antecipada sobre mudanças, decisões importantes ou planos futuros da empresa?'],
                [17, 'Recebe toda a informação de que necessita para fazer bem o seu trabalho?', 'Você recebe todas as informações necessárias para executar seu trabalho adequadamente?'],
                [18, 'O seu trabalho apresenta objectivos claros?', 'Os objetivos e direcionamentos do seu trabalho são claros para você?'],
                [21, 'O seu trabalho é reconhecido e apreciado pela gerência?', 'Você sente que seu trabalho é reconhecido e valorizado pela liderança?'],
                [22, 'A gerência do seu local de trabalho respeita-o?', 'Você se sente respeitado(a) pela liderança da empresa?'],
                [23, 'É tratado de forma justa no seu local de trabalho?', 'Você considera que é tratado(a) de forma justa no ambiente de trabalho?'],
                [30, 'Com que frequência o seu superior imediato fala consigo sobre como está a decorrer o seu trabalho?', 'Sua liderança acompanha e conversa sobre como está seu trabalho?'],
                [31, 'Com que frequência tem ajuda e apoio do seu superior imediato?', 'Você recebe apoio da sua liderança quando precisa?'],
                [32, 'Com que frequência é que o seu superior imediato fala consigo em relação ao seu desempenho laboral?', 'Sua liderança conversa com você sobre seu desempenho profissional?'],
                [36, 'Oferece aos indivíduos e ao grupo boas oportunidades de desenvolvimento?', 'A liderança oferece oportunidades de desenvolvimento para a equipe?'],
                [37, 'Dá prioridade à satisfação no trabalho?', 'A empresa demonstra preocupação com o bem-estar e satisfação dos colaboradores?'],
                [38, 'É bom no planeamento do trabalho?', 'O trabalho é bem planejado e organizado pela liderança?'],
                [39, 'É bom a resolver conflitos?', 'Os conflitos no trabalho são tratados e resolvidos adequadamente?'],
                [43, 'A gerência confia nos seus funcionários para fazerem o seu trabalho bem?', 'Você percebe que a liderança confia no trabalho realizado pelos colaboradores?'],
                [44, 'Confia na informação que lhe é transmitida pela gerência?', 'Você confia nas informações e orientações repassadas pela empresa?'],
                [45, 'A gerência oculta informação aos seus funcionários?', 'Você sente que informações importantes são omitidas pela liderança?'],
                [47, 'As sugestões dos funcionários são tratadas de forma séria pela gerência?', 'As sugestões dos colaboradores são levadas em consideração pela empresa?'],
            ],
            CategoriaReferencia::CONTEXTO_ORGANIZACAO->value => [
                [1, 'A sua carga de trabalho acumula-se por ser mal distribuída?', 'Você sente que o volume de trabalho é mal distribuído?'],
                [2, 'Com que frequência não tem tempo para completar todas as tarefas do seu trabalho?', 'Você costuma não ter tempo suficiente para concluir suas atividades?'],
                [3, 'Precisa fazer horas-extra?', 'Você precisa fazer horas extras com frequência para dar conta do trabalho?'],
                [4, 'Precisa trabalhar muito rapidamente?', 'Seu trabalho exige um ritmo muito acelerado?'],
                [9, 'Tem um elevado grau de influência no seu trabalho?', 'Você possui autonomia para influenciar a forma como realiza seu trabalho?'],
                [11, 'Pode influenciar a quantidade de trabalho que lhe compete a si?', 'Você consegue influenciar ou negociar sua carga de trabalho?'],
                [12, 'Tem alguma influência sobre o tipo de tarefas que faz?', 'Você tem participação na definição das atividades que executa?'],
                [19, 'Sabe exactamente quais as suas responsabilidades?', 'Suas responsabilidades no trabalho são claras para você?'],
                [20, 'Sabe exactamente o que é esperado de si?', 'Você sabe exatamente o que a empresa espera do seu trabalho?'],
                [24, 'Faz coisas no seu trabalho que uns concordam mas outros não?', 'Você recebe orientações contraditórias sobre como executar seu trabalho?'],
                [25, 'Por vezes tem que fazer coisas que deveriam ser feitas de outra maneira?', 'Você precisa realizar atividades de uma forma que considera inadequada?'],
                [26, 'Por vezes tem que fazer coisas que considera desnecessárias?', 'Você realiza tarefas que considera sem sentido ou desnecessárias?'],
                [48, 'O trabalho é igualmente distribuído pelos funcionários?', 'O trabalho é distribuído de forma equilibrada entre as pessoas da equipe?'],
            ],
            CategoriaReferencia::RELACOES_SOCIAIS->value => [
                [27, 'Com que frequência tem ajuda e apoio dos seus colegas de trabalho?', 'Você recebe apoio dos colegas quando precisa?'],
                [28, 'Com que frequência os seus colegas estão dispostos a ouvi-lo(a) sobre os seus problemas de trabalho?', 'Seus colegas costumam ouvir e apoiar você diante de dificuldades no trabalho?'],
                [29, 'Com que frequência os seus colegas falam consigo acerca do seu desempenho laboral?', 'Existe troca de feedback entre você e seus colegas de trabalho?'],
                [30, 'Com que frequência o seu superior imediato fala consigo sobre como está a decorrer o seu trabalho?', 'Sua liderança acompanha e conversa sobre como está seu trabalho?'],
                [31, 'Com que frequência tem ajuda e apoio do seu superior imediato?', 'Você recebe apoio da sua liderança quando precisa?'],
                [32, 'Com que frequência é que o seu superior imediato fala consigo em relação ao seu desempenho laboral?', 'Sua liderança conversa com você sobre seu desempenho profissional?'],
                [33, 'Existe um bom ambiente de trabalho entre si e os seus colegas?', 'Você considera o clima entre os colegas positivo?'],
                [34, 'Existe uma boa cooperação entre os colegas de trabalho?', 'Existe cooperação e ajuda mútua entre as pessoas da equipe?'],
                [35, 'No seu local de trabalho sente-se parte de uma comunidade?', 'Você se sente pertencente e integrado(a) à equipe?'],
                [40, 'Os funcionários ocultam informações uns dos outros?', 'As pessoas costumam esconder informações importantes entre si?'],
                [42, 'Os funcionários confiam uns nos outros de um modo geral?', 'Existe confiança entre as pessoas da equipe?'],
            ],
            CategoriaReferencia::CONTEUDO_TAREFAS->value => [
                [5, 'O seu trabalho exige a sua atenção constante?', 'Seu trabalho exige atenção constante durante a maior parte do tempo?'],
                [6, 'O seu trabalho requer que seja bom a propor novas ideias?', 'Seu trabalho exige criatividade e novas ideias?'],
                [7, 'O seu trabalho exige que tome decisões difíceis?', 'Você precisa tomar decisões difíceis no trabalho?'],
                [8, 'O seu trabalho exige emocionalmente de si?', 'Seu trabalho exige muito emocionalmente de você?'],
                [13, 'O seu trabalho exige que tenha iniciativa?', 'Seu trabalho exige iniciativa própria frequentemente?'],
                [14, 'O seu trabalho permite-lhe aprender coisas novas?', 'Seu trabalho proporciona aprendizado e desenvolvimento?'],
                [15, 'O seu trabalho permite-lhe usar as suas habilidades ou perícias?', 'Você consegue utilizar suas habilidades e competências no trabalho?'],
                [51, 'O seu trabalho tem algum significado para si?', 'Você considera seu trabalho significativo?'],
                [52, 'Sente que o seu trabalho é importante?', 'Você sente que seu trabalho é importante?'],
                [53, 'Sente-se motivado e envolvido com o seu trabalho?', 'Você se sente motivado(a) e engajado(a) com seu trabalho?'],
                [69, 'Irritado?', 'Você tem se sentido irritado(a) por situações relacionadas ao trabalho?'],
                [70, 'Ansioso?', 'Você tem se sentido ansioso(a) por causa do trabalho?'],
            ],
            CategoriaReferencia::CONDICOES_AMBIENTE->value => [
                [57, 'As condições físicas do seu local de trabalho?', 'Como você avalia as condições físicas do seu ambiente de trabalho?'],
                [61, 'Em geral, sente que a sua saúde é:', 'Como você avalia sua saúde atualmente?'],
            ],
            CategoriaReferencia::INTERACAO_PESSOA_TAREFA->value => [
                [49, 'Sou sempre capaz de resolver problemas, se tentar o suficiente.', 'Você sente que consegue resolver os problemas relacionados ao seu trabalho?'],
                [50, 'É-me fácil seguir os meus planos e atingir os meus objectivos.', 'Você consegue atingir seus objetivos profissionais no trabalho?'],
                [58, 'A forma como as suas capacidades são utilizadas?', 'Você considera que suas capacidades são bem aproveitadas no trabalho?'],
            ],
            CategoriaReferencia::JORNADA_TRABALHO->value => [
                [62, 'Sente que o seu trabalho lhe exige muita energia que acaba por afectar a sua vida privada negativamente?', 'O trabalho consome sua energia a ponto de prejudicar sua vida pessoal?'],
                [63, 'Sente que o seu trabalho lhe exige muito tempo que acaba por afectar a sua vida privada negativamente?', 'O tempo dedicado ao trabalho prejudica sua vida pessoal ou familiar?'],
                [64, 'A sua família e os seus amigos dizem-lhe que trabalha demais?', 'Pessoas próximas dizem que você trabalha excessivamente?'],
                [65, 'Dificuldade a adormecer?', 'Você tem dificuldade para dormir por causa das preocupações do trabalho?'],
                [66, 'Acordou várias vezes durante a noite e depois não conseguia adormecer novamente?', 'Você acorda durante a noite pensando em questões do trabalho?'],
                [67, 'Fisicamente exausto?', 'Você se sente fisicamente esgotado(a) por causa do trabalho?'],
                [68, 'Emocionalmente exausto?', 'Você se sente emocionalmente esgotado(a) por causa do trabalho?'],
                [71, 'Triste?', 'Você tem se sentido triste em função do trabalho?'],
                [72, 'Falta de interesse por coisas quotidianas?', 'Você perdeu interesse em atividades do dia a dia por causa do trabalho?'],
            ],
            CategoriaReferencia::VIOLENCIA_ASSEDIO->value => [
                [73, 'Tem sido alvo de insultos ou provocações verbais?', 'Você já sofreu humilhações, ofensas ou provocações no trabalho?'],
                [74, 'Tem sido exposto a assédio sexual indesejado?', 'Você já passou por situações de assédio sexual no ambiente de trabalho?'],
            ],
            CategoriaReferencia::DISCRIMINACAO->value => [
                [23, 'É tratado de forma justa no seu local de trabalho?', 'Você sente que todas as pessoas são tratadas de forma justa na empresa?'],
                [35, 'No seu local de trabalho sente-se parte de uma comunidade?', 'Você se sente incluído(a) e respeitado(a) no ambiente de trabalho?'],
            ],
            CategoriaReferencia::RISCO_MORTE_TRAUMA->value => [
                [75, 'Tem sido exposto a ameaças de violência?', 'Você já sofreu ameaças no ambiente de trabalho?'],
                [76, 'Tem sido exposto a violência física?', 'Você já sofreu agressão física no trabalho?'],
            ],
            CategoriaReferencia::DESEMPREGO_INSEGURANCA->value => [
                [56, 'As suas perspectivas de trabalho?', 'Você se sente seguro(a) em relação ao seu futuro profissional na empresa?'],
                [60, 'Sente-se preocupado em ficar desempregado?', 'Você tem medo de perder seu emprego?'],
            ],
        ];
    }
}
