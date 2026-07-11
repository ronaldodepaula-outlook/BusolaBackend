# Documentação do Desenvolvedor — SistemaPesquisas (busola)

> Guia de arquitetura e convenções para quem vai manter ou evoluir este código.
> Para regras de negócio, veja [REGRAS_DE_NEGOCIO.md](REGRAS_DE_NEGOCIO.md). Para subir o ambiente, veja [IMPLANTACAO.md](IMPLANTACAO.md).

## 1. Visão geral da arquitetura

O sistema é composto por **duas aplicações PHP independentes**, servidas em pastas irmãs sob o mesmo host:

```
SistemaPesquisas/
├── app/                    # API Laravel 12 (backend "core": Empresa, Usuário, Auth, RBAC...)
│   └── Modules/Pesquisa/   # Módulo de Pesquisas Psicossociais (auto-contido, ver seção 3)
├── public/                 # document root da API (http://host/SistemaPesquisas/public)
├── webAdm/                 # painel administrativo — PHP puro, SEM framework
│   ├── classe/             # Auth, ApiClient, Config — camada de acesso à API
│   ├── paginas/             # uma página por arquivo, incluída via index.php?paginas=xxx
│   └── componentes/         # header/sidebar/footer compartilhados
├── database/                # migrations/seeders do "core"
├── tests/                   # Pest/PHPUnit — Feature tests (sem testes unitários isolados)
└── docs/                    # esta documentação
```

**Por que duas aplicações e não uma SPA?** O `webAdm` é deliberadamente PHP simples (sem Vue/React/build step): cada página faz `require` dos componentes compartilhados, busca dados da API via `ApiClient` (cURL) e renderiza server-side. Toda escrita (POST/PUT/PATCH/DELETE) é feita depois, no browser, via `fetch()` (`webAdm/assets/js/busola-crud.js`) usando o token JWT exposto na página (`API_TOKEN`/`API_BASE`, ver `webAdm/index.php`). Isso significa: **a API é a única fonte de verdade e de regras de negócio** — o webAdm nunca decide nada sozinho, só exibe e encaminha.

Duas exceções a essa regra, ambas documentadas onde ocorrem no código:
- `webAdm/formulario-importar.php` faz parsing de CSV localmente antes de enviar cada linha para a API (evita reimplementar parsing de CSV na API para um caso de uso único).
- `webAdm/atualizar-sessao-foto.php` sincroniza a sessão PHP local após um upload de foto, para o header (renderizado server-side) mostrar a imagem nova sem esperar o próximo login.

## 2. Convenções da API (app/Http, fora do módulo)

- **Envelope de resposta padrão**: toda resposta JSON segue `{"sucesso": bool, "mensagem"?: string, "dados"?: mixed, "erros"?: object}`. Isso é reforçado centralmente em `bootstrap/app.php` (`withExceptions`), que intercepta `ValidationException` e `HttpResponseException` para qualquer rota `api/*` e força esse formato — então um `FormRequest` que falha validação, ou um `abort(422, '...')`, já saem no formato certo sem o Controller precisar fazer nada.
- **Middlewares de rota** (`bootstrap/app.php` registra os aliases): `auth.jwt` (autentica via JWT, popula `$request->auth_user`), `tenant` (resolve `$request->empresa` a partir do usuário ou do header `X-Empresa-Id` para superadmin), `filial`, `permission:<slug>` (RBAC, ver REGRAS_DE_NEGOCIO.md), `log.api` (grava auditoria automática na tabela `logs`, redigindo campos sensíveis).
- Os controllers "core" (`AuthController`, `UsuarioController`, `EmpresaController` etc.) em geral **não usam FormRequest nem Service dedicados** — são mais antigos, com `Validator::make()` inline e regra de negócio direto no controller. Os pontos mais novos criados sobre essa base (`app/Http/Controllers/Auth/*`) já seguem o padrão mais limpo do módulo (ver item 3.3) — ao tocar em código core antigo, não é necessário refatorar tudo para o padrão novo, mas todo código **novo** deve seguir o padrão do módulo Pesquisa.
- Toda rota documentada com atributos `#[OA\Get]`/`#[OA\Post]`/etc. (pacote `darkaonline/l5-swagger`, zircote/swagger-php por baixo). **Nenhum endpoint deve ficar sem essa documentação** — rode `php artisan l5-swagger:generate` depois de adicionar/alterar uma rota e confira em `/api/documentation`.

## 3. O módulo `app/Modules/Pesquisa`

Todo o domínio de "Pesquisas Psicossociais" vive isolado em `app/Modules/Pesquisa`, registrado por `PesquisaServiceProvider` (carrega migrations, views com namespace `pesquisa::`, e `routes/api.php` do módulo sob o grupo de middleware `api`). Isso é um **módulo, não um pacote Composer separado** — vive dentro do mesmo `app/`, só que namespaced e fisicamente isolado, para poder um dia virar um pacote de verdade sem grande reescrita.

Estrutura interna (contagem atual de arquivos):

| Pasta | Papel | Qtd. |
|---|---|---|
| `Models/` | Eloquent — nunca contêm regra de negócio além de casts/relations/scopes/accessors triviais | 20 |
| `Services/` | **Toda** regra de negócio mora aqui — um Service por agregado (FormularioService, ColaboradorService, PlanoAcaoService...) | 21 |
| `Http/Controllers/` | Finos: extraem dados validados, chamam o Service, envolvem a resposta | 17 |
| `Http/Requests/` | Validação via FormRequest — nunca `Validator::make()` inline neste módulo | 26 |
| `Http/Resources/` | Serialização de resposta quando o Model puro não é suficiente | 8 |
| `Enums/` | Domínio rico em enums (status, tipos, níveis de risco, fatores de referência) | 16 |
| `Contracts/` | Interfaces para polimorfismo entre padrões de cálculo (ver seção 4) | 3 |
| `Casts/` | Casts Eloquent customizados | 1 |
| `Support/` | Classes auxiliares sem estado (resolvers estáticos, políticas) | 1 |
| `Database/Migrations/` | Migrations próprias do módulo (prefixo de tabela `pesq_`) | 33 |
| `Database/Factories/` | Factories para teste | 13 |

**Regra de ouro deste módulo**: Controller → FormRequest (valida) → Service (decide) → Model/Repository (persiste). Um Controller nunca deve conter uma condicional de negócio; se você está tentado a escrever `if` num Controller além de checar o resultado de uma chamada, esse `if` pertence ao Service.

### 3.1 Repositórios — usados seletivamente

Nem todo agregado tem Repository. Entidades com consultas compostas reais (Formulario, Pesquisa, Categoria, Convite) têm `Repositories/`; entidades de CRUD simples (Ghe, Setor, PadraoFormulario) fazem a consulta direto no Service via Eloquent — não crie um Repository "porque sim" para uma entidade cujo Service só faz `Model::find()`/`Model::create()`.

### 3.2 DTOs

Alguns Services recebem um DTO (`DTOs/FormularioData.php`, `DTOs/PesquisaData.php`, `DTOs/CategoriaData.php`) construído via `DTO::fromRequest($request)` em vez do array bruto `$request->validated()`. Use um DTO quando o Service precisa distinguir "campo não enviado" de "campo enviado como null" (ver `FormularioData::$padraoFormularioIdInformado` — permite limpar um relacionamento opcional explicitamente, sem confundir com "não mexeu nesse campo"). Para casos simples, `$request->validated()` (array) basta.

### 3.3 Exceções de domínio com `render()` próprio

Em vez de `try/catch` espalhado pelos controllers, exceções de domínio implementam `render(Request $request)` (suportado nativamente pelo handler do Laravel) e já retornam a resposta HTTP correta. Exemplo: `App\Exceptions\Auth\TokenInvalidoOuExpiradoException` — o Service lança a exceção, o Controller não sabe que ela existe, e a resposta 422 com a mensagem certa sai sozinha. Prefira esse padrão a `abort_if`/`try-catch` sempre que a mesma condição de erro se repetir em mais de um Controller.

### 3.4 Polimorfismo entre padrões de cálculo (Contracts/)

Este é o ponto mais arquiteturalmente denso do módulo — leia antes de mexer em qualquer coisa relacionada a risco/severidade/categoria de referência.

O sistema suporta **mais de um modelo de cálculo de risco psicossocial** simultaneamente (hoje: "NR-1 completo", 11 categorias, matriz assimétrica de 6 níveis; e "COPSOQ II resumido", 7 dimensões, matriz P×S de 4 níveis — ver REGRAS_DE_NEGOCIO.md para o conteúdo de cada um). Cada `PadraoFormulario` declara qual `ModeloCalculoRisco` usa; cada `Formulario` pertence a um `PadraoFormulario` (opcional — ausência = modelo NR-1 completo, o comportamento histórico).

Para o resto do código (ResultadoService, RelatorioTecnicoService, o PDF) não precisar saber qual dos dois modelos está em jogo, três interfaces em `Contracts/` abstraem a diferença:

- `FatorRiscoReferenciaInterface` — implementada por `Enums/CategoriaReferencia` (NR-1) e `Enums/CategoriaReferenciaCopsoqSimplificado` (COPSOQ resumido). Ambas expõem `label()`, `severidadePadrao()`, `descricaoTecnica()`, `doencasRelacionadas()`.
- `NivelRiscoInterface` — implementada por `Enums/NivelRisco` (6 níveis) e `Enums/NivelRiscoCopsoqSimplificado` (4 níveis). Expõe `label()`, `farolEmoji()`, `farolCor()`, `diretriz()`, `nivelBaseAcao()` (pode retornar `null` — ver abaixo).
- `MotorCalculoRiscoInterface` — implementada por `Services/RiscoCalculator` (NR-1) e `Services/RiscoCalculatorCopsoqSimplificado`. Expõe `probabilidade(float $media): ?int` e `classificar(...)`/`avaliar(...)`.

`Services/MotorCalculoRiscoResolver` é o único ponto que decide, a partir do `PadraoFormulario` de uma campanha, qual motor/enum concreto usar — **nenhum outro lugar do código deve ter um `if ($padrao->modelo_calculo === ...)`**. Se for adicionar um terceiro modelo de cálculo no futuro: crie o par de enums + o Calculator, registre em `ModeloCalculoRisco` e em `MotorCalculoRiscoResolver`, e pronto — nada mais muda.

A coluna `pesq_categorias.categoria_referencia` guarda a string de QUALQUER um dos dois enums (os valores nunca colidem entre si) e usa um cast customizado, `Casts/ReferenciaFatorRiscoCast`, que tenta resolver contra os dois enums via `Support/FatorRiscoReferenciaResolver::resolver()`. Isso permite que `$categoria->categoria_referencia?->label()` funcione igual não importa qual padrão a categoria segue — nunca troque esse cast de volta para um enum único.

`NivelRiscoInterface::nivelBaseAcao()` pode retornar `null` — é assim que o padrão COPSOQ resumido sinaliza "não tenho catálogo de Plano de Ação próprio" (a planilha de referência dele não define uma tabela equivalente a `BASE_ACAO`). `PlanoAcaoService::gerar()` já trata isso (`continue` quando `null`) — não gere ações fictícias para preencher essa lacuna.

### 3.5 Enums com métodos ricos, não só valores

Este módulo usa enums nativos do PHP 8.1+ extensivamente, sempre com métodos além do valor bruto (`label()`, `farolCor()`, `severidadePadrao()`, `proxima()` para máquinas de estado como `FasePdca`). Ao adicionar um novo `case`, sempre trate todos os `match()` existentes daquele enum — o PHP falha em tempo de execução (não de compilação) se um `match` sem `default` não cobrir um case novo, então rode a suíte de testes depois de adicionar um case.

## 4. Segurança e LGPD — padrões estabelecidos

- **Dados sensíveis cifrados, nunca em texto puro no banco**: ver `Colaborador::cpf` (accessor/mutator via `Attribute::make()`, cifra com `Crypt::encryptString()` e grava também um hash SHA-256 não reversível em `cpf_hash` só para deduplicação). Siga esse padrão para qualquer dado sensível novo — nunca crie um cast Eloquent nativo `encrypted` puro quando precisar também de um hash de busca, porque o cast nativo não permite derivar o hash no mesmo `set()`.
- **Tokens de uso único**: sempre gerados com `random_bytes()` (nunca `Str::random()`, que usa uma fonte mais fraca para este propósito), armazenados como `hash('sha256', $token)`, nunca em claro. Ver `Services/Auth/TokenSenhaService` como referência — token com expiração + consumo atômico dentro de transação com `lockForUpdate()`.
- **Mascaramento por padrão**: campos sensíveis (CPF de colaborador) só aparecem em claro num endpoint dedicado e explicitamente permissionado (`colaborador.visualizar_dados_sensiveis`), nunca na listagem padrão. A listagem padrão sempre expõe a versão mascarada (`cpf_mascarado`).
- **Nunca revelar existência de e-mail/registro**: ver `RecuperacaoSenhaService::solicitar()` — a resposta HTTP é idêntica (200, mesma mensagem) exista ou não o e-mail informado; a decisão real acontece só dentro do Service, nunca vaza pro Controller/resposta.
- **Auditoria automática, não manual**: antes de escrever um log manual para "registrar quem fez o quê", verifique se a rota já está sob o middleware `log.api` — ele grava em `logs` automaticamente (usuário, IP, rota, payload redigido) para qualquer rota que o inclua. Logging manual (`Log::info()`, o logger de arquivo do Laravel) é para **eventos semânticos** que a tabela `logs` não capta (ex.: "token de ativação gerado", "tentativa com token expirado") — os dois se complementam, não se substituem.

## 5. Frontend (webAdm) — padrões

- **Toda leitura** (GET) é feita server-side em PHP via `ApiClient` (`webAdm/classe/ApiClient.php`, wrapper cURL simples) — a página monta o HTML já com os dados.
- **Toda escrita** (POST/PUT/PATCH/DELETE) é feita client-side via `fetch()`, usando `apiFetch()` (`webAdm/assets/js/busola-crud.js`) — que sempre serializa o corpo como JSON. **Para upload de arquivo, use `apiUpload()`** (mesmo arquivo) em vez de `apiFetch()` — `apiFetch` força `Content-Type: application/json` e quebraria um `FormData`; `apiUpload` deixa o browser definir o boundary do multipart sozinho.
- Páginas públicas (sem exigir login) são standalone: não incluem `index.php`/sidebar/header, chamam `new ApiClient()` sem token. Ver `responder.php`, `ativar-conta.php`, `redefinir-senha.php`, `esqueci-senha.php` como referência de estrutura para uma nova página pública.
- Identidade visual: variáveis CSS `--primary:#0946b0; --dark:#063080; --cyan:#11bbce; --green:#73ddb3; --bg:#f3f4f8` repetidas em `login.php` e nas páginas públicas standalone (não há um CSS compartilhado entre elas hoje — duplicar essas poucas variáveis é aceitável, não vale a pena introduzir um build step por isso).

## 6. Testes

- Framework: PHPUnit via `php artisan test`, sempre com `RefreshDatabase` (SQLite em memória — configurado em `phpunit.xml`, nunca rode testes contra o banco real; ver nota de segurança abaixo).
- Localização: `tests/Feature/Modules/Pesquisa/*` (módulo) e `tests/Feature/*`/`tests/Feature/Auth/*` (core). Não há testes unitários isolados — todo teste é um teste de integração HTTP (`$this->postJson(...)`) ou, quando o endpoint é público (sem JWT), sem headers de autenticação.
- Autenticação em teste: `JWTAuth::fromUser($user)` + header `Authorization: Bearer`. Para o módulo Pesquisa, use o trait `Tests\Feature\Modules\Pesquisa\Concerns\AutenticaComoJwt` (`headersParaUsuario($user)`); para testes core fora do módulo, o mesmo padrão é replicado inline (ver `tests/Feature/PerfilFotoTest.php`, `tests/Feature/Auth/*`).
- Permissões em teste: `PesquisaTestCase::criarUsuarioComPermissoes($empresa, array $slugs)` cria um papel ad-hoc só com os slugs pedidos — não dependa dos papéis-padrão do `RoleSeeder` dentro de um teste, exceto quando o próprio teste é sobre esses papéis.
- **⚠️ Nunca rode `config:cache` neste projeto em ambiente de desenvolvimento.** Se um `bootstrap/cache/config.php` existir, o Laravel para de reavaliar `.env`/variáveis de ambiente do `phpunit.xml` a cada execução — os testes silenciosamente passam a rodar contra o banco de dados real (não o SQLite isolado), e como os testes usam `RefreshDatabase`, isso **apaga todos os dados reais**. Se encontrar esse arquivo, apague-o (`rm bootstrap/cache/config.php`) antes de rodar qualquer teste.

## 7. Checklist para adicionar uma funcionalidade nova ao módulo Pesquisa

1. Migration em `Database/Migrations/` (prefixo `pesq_` na tabela).
2. Model em `Models/` — só relations/casts/scopes/accessors triviais.
3. Se a regra de negócio precisar de mais de um passo/validação composta: Service em `Services/`.
4. FormRequest em `Http/Requests/<Entidade>/` — nunca `Validator::make()` inline.
5. Controller fino em `Http/Controllers/` + atributos `#[OA\...]` completos.
6. Rota em `routes/api.php` do módulo, com o middleware `permission:<slug>.<acao>` correto.
7. Permissão nova → adicionar em `database/seeders/PermissionSeeder.php` e decidir explicitamente se `RoleSeeder.php` inclui/exclui do papel Admin/Gerente.
8. Teste em `tests/Feature/Modules/Pesquisa/`.
9. Se a entidade aparecer no webAdm: página em `paginas/`, entrada no `componentes/sidebar.php` gated pela permissão certa.
10. Rodar `php artisan test` (suíte inteira) e `php artisan l5-swagger:generate` antes de considerar pronto.
