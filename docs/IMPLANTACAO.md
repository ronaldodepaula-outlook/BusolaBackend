# Documentação de Implantação — SistemaPesquisas (busola)

> Como subir o ambiente do zero e como o deploy funciona hoje. Nenhum valor real de segredo (senha de banco, de e-mail, `JWT_SECRET`) é reproduzido aqui — sempre use placeholders e mantenha o `.env` real fora do controle de versão.

## 1. Visão geral da topologia

O projeto é **duas aplicações PHP servidas sob o mesmo host**, em pastas irmãs:

```
/SistemaPesquisas/
├── public/    ← document root da API Laravel (ex.: http://seu-host/SistemaPesquisas/public)
└── webAdm/    ← painel administrativo, PHP puro, é a SUA PRÓPRIA raiz web (ex.: http://seu-host/SistemaPesquisas/webAdm)
```

Ambas precisam estar acessíveis via HTTP(S) e sincronizadas quanto às URLs configuradas (seção 4).

## 2. Requisitos de servidor

- **PHP ^8.2** (verificado em tempo de boot pelo Composer — a aplicação não sobe em versão menor).
- Extensões PHP: `pdo_mysql` (conexão com o banco), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `dom` — as duas últimas (`dom`, `mbstring`) são exigidas diretamente pelo gerador de PDF (`barryvdh/laravel-dompdf`). `gd` é recomendada (não obrigatória) se os relatórios em PDF vierem a incluir imagens.
- **MySQL** (o projeto usa `DB_CONNECTION=mysql` em todo ambiente real; SQLite só é usado pela suíte de testes automatizados, nunca em produção/dev).
- **Composer 2** e **Node.js** (para `npm run build`, usado pelos assets do Vite/Tailwind da API — o `webAdm` não usa Node, é 100% Bootstrap/jQuery vendorizado).
- Apache com `mod_rewrite` (o projeto já traz `public/.htaccess` padrão do Laravel) ou Nginx equivalente.

## 3. Passo a passo — subir o ambiente do zero

```bash
# 1. Clonar e instalar dependências
git clone <repo> SistemaPesquisas
cd SistemaPesquisas
composer install --no-dev --optimize-autoloader   # produção; sem --no-dev em dev
npm install && npm run build

# 2. Variáveis de ambiente
cp .env.example .env
php artisan key:generate

# 3. Editar .env (ver seção 4 — todos os valores abaixo são exemplos, nunca copie os daqui)

# 4. Migrations e symlink de storage
php artisan migrate --force
php artisan storage:link

# 5. Seeders — ver ordem e descrição na seção 6
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
```

Alternativamente, o próprio `composer.json` já define um script `setup` que encadeia boa parte disso (`composer install` → copia `.env` → `key:generate` → `migrate --force` → `npm install && npm run build`) — rode `composer run setup` para o bootstrap básico, e depois os seeders manualmente (o script não roda seeders).

## 4. Variáveis de ambiente (`.env`) — o que configurar e por quê

Nenhum valor abaixo é o valor real usado em produção — são exemplos ilustrativos.

```env
APP_NAME=SistemaPesquisas
APP_ENV=production            # "local" em dev
APP_KEY=                       # gerado por php artisan key:generate — nunca definir manualmente
APP_DEBUG=false                # SEMPRE false em produção (true vaza stack trace nas respostas de erro)
APP_URL=https://seu-dominio.com/SistemaPesquisas/public
APP_LOCALE=pt_BR

# URL do painel webAdm — usada para montar os links dos e-mails transacionais
# (ativação de conta, recuperação de senha). É uma variável própria do projeto
# (config/frontend.php), pois API e webAdm são apps separadas.
FRONTEND_URL=https://seu-dominio.com/SistemaPesquisas/webAdm

DB_CONNECTION=mysql
DB_HOST=<host-do-mysql>
DB_PORT=3306
DB_DATABASE=<nome-do-banco>
DB_USERNAME=<usuario>
DB_PASSWORD=<senha>

FILESYSTEM_DISK=local           # disco 'public' é usado explicitamente para upload de foto/logo — ver DESENVOLVEDOR.md
QUEUE_CONNECTION=database        # tabela `jobs` já migrada; ver nota sobre worker na seção 7
CACHE_STORE=database
SESSION_DRIVER=database

MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=465
MAIL_USERNAME=<usuario-smtp>
MAIL_PASSWORD=<senha-smtp>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=<remetente>
MAIL_FROM_NAME="Suporte"

JWT_SECRET=                     # gerado por php artisan jwt:secret — nunca definir manualmente nem reaproveitar entre ambientes
# JWT_TTL / JWT_REFRESH_TTL não definidos = usam o default do pacote (60 min / 2 semanas)

# Swagger — recomendações para produção (ver seção 8, hardening)
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://seu-dominio.com
L5_SWAGGER_BASE_PATH=/SistemaPesquisas/public
```

E, no arquivo **`webAdm/classe/Config.php`** (não é lido do `.env` — é um arquivo PHP próprio do painel, editado manualmente):

```php
define('API_BASE_URL', 'https://seu-dominio.com/SistemaPesquisas/public/api/v1/');
define('WEBADM_URL', 'https://seu-dominio.com/SistemaPesquisas/webAdm/');
```

**As três URLs acima (`APP_URL`, `FRONTEND_URL`, e as duas constantes de `Config.php`) precisam ser mantidas coerentes entre si** ao mudar de ambiente — é a causa mais comum de "link do e-mail aponta para localhost" em produção. Depois de qualquer mudança nessas variáveis, se o ambiente usar `config:cache` (ver alerta na seção 7), rode `php artisan config:clear && php artisan config:cache` novamente.

## 5. Migrations

O projeto tem **49 migrations** no total: 15 do schema "core" (`database/migrations` — empresas, filiais, usuários, roles, permissões, logs, configurações, blacklist de token, sessões, cache, filas) e 34 do módulo de Pesquisas Psicossociais (`app/Modules/Pesquisa/Database/Migrations`, carregadas automaticamente pelo `PesquisaServiceProvider`).

Duas migrations usam `Schema::table(...)->change()` (alteração portável de coluna), o que exige o pacote `doctrine/dbal` — já incluso no `composer.json`, nenhuma ação extra necessária.

## 6. Seeders — o que cada um faz e em que ordem rodar

| Ordem | Seeder | O que faz | Idempotente? |
|---|---|---|---|
| 1 | `PermissionSeeder` | Cria/atualiza todas as permissões do sistema | Sim (`updateOrCreate`) |
| 2 | `RoleSeeder` | Cria os 4 papéis de sistema (superadmin/admin/gerente/operador) e sincroniza suas permissões | Sim |
| 3 | `SuperAdminSeeder` | Cria a conta do super administrador | Sim |
| 4 | `EmpresaDemoSeeder` | Cria uma empresa de demonstração + admin dessa empresa (opcional — pule em produção real sem dados de demo) | Sim |
| 5 | `PlanoAcaoTemplateSeeder` | Semeia o catálogo de 165 ações-modelo de Plano de Ação (padrão NR-1 completo) | Sim |
| 6 | `CopsoqFormularioSeeder` | Semeia o formulário global "COPSOQ II — Versão Média" (11 categorias oficiais) | Sim |
| 7 | `CopsoqSimplificadoFormularioSeeder` | Semeia o Padrão de Formulário + formulário global "COPSOQ II — Versão Resumida" (7 dimensões) | Sim |
| — | `GheDemoSeeder`, `PesquisaDemoSeeder`, `CopsoqCampanhaDemoSeeder` | Dados de demonstração completos (GHEs, campanha, colaboradores, respostas simuladas, relatório) — **só para ambiente de demonstração/homologação**, não rode em produção real | Sim, mas gera dados fictícios |

Todos os seeders são escritos com `updateOrCreate`/`sync` — **seguros para rodar novamente** a qualquer momento sem duplicar dados. Nenhum deles está registrado no `DatabaseSeeder::run()` padrão (que só chama os 4 primeiros); os demais são executados individualmente via `php artisan db:seed --class=<Nome>`.

## 7. Fila (queue) e e-mail

O `QUEUE_CONNECTION` está configurado como `database` e a tabela `jobs` já está migrada — porém **os e-mails transacionais do sistema (convite de ativação de conta, recuperação de senha) são enviados de forma síncrona (não implementam `ShouldQueue`)**, por decisão deliberada: os links têm expiração curta (24h e 30min) e não há garantia de um worker de fila (`php artisan queue:work`) rodando continuamente neste projeto. Se for colocar um worker supervisionado em produção (`supervisor`, systemd, etc.), os dois Mailables (`app/Mail/ConviteAtivacaoContaMail.php`, `app/Mail/RecuperacaoSenhaMail.php`) podem passar a implementar `ShouldQueue` — só então o `QUEUE_CONNECTION=database` passa a ter efeito prático para esse fluxo.

## 8. CI/CD atual e hardening recomendado para produção

O repositório tem **um único workflow** (`.github/workflows/main.yml`): a cada push na branch `main`, ele faz checkout do código e sincroniza os arquivos via **FTP** para o servidor (`SamKirkland/FTP-Deploy-Action`). Isso é puramente um espelhamento de arquivos — **não roda testes, não roda `composer install`/`npm run build`, não roda migrations**. Consequências práticas para quem for operar o deploy:

- `vendor/`, `node_modules/`, `public/build`, `.env` e o symlink `public/storage` estão no `.gitignore` — **não são versionados nem enviados pelo FTP** e precisam ser criados/atualizados manualmente no servidor após cada deploy (rodar os passos da seção 3 diretamente lá, ou adaptar o workflow para incluir essas etapas).
- Migrations novas **não rodam sozinhas** — é preciso `php artisan migrate --force` manual no servidor após cada deploy que inclua migration nova.

Pontos de hardening a revisar antes de expor este sistema publicamente em produção (nenhum bloqueia o funcionamento, mas todos são recomendações de segurança):

1. **CORS** (`config/cors.php`) está com `allowed_origins => ['*']` — considere restringir ao(s) domínio(s) real(is) do `webAdm` em produção.
2. **Swagger UI** (`/api/documentation`) não tem nenhum middleware de proteção — fica publicamente acessível. Considere colocar atrás de autenticação básica do servidor web ou restringir por IP em produção.
3. `L5_SWAGGER_GENERATE_ALWAYS` deve ser `false` em produção (regenerar a documentação a cada requisição é custo desnecessário fora de desenvolvimento).
4. `APP_DEBUG` deve ser sempre `false` em produção (evita vazar stack trace/caminhos de arquivo nas respostas de erro).
5. O arquivo `ACESSOS.txt` (raiz do projeto) guarda credenciais de demonstração em texto puro — não deve ser versionado/publicado num repositório público; se este projeto for para um GitHub público, adicione-o ao `.gitignore` e remova do histórico se já commitado.

## 9. Verificação pós-deploy (smoke test)

1. `GET /api/health` → `{"status":"ok", ...}`.
2. Login via `POST /api/v1/auth/login` com uma conta válida → recebe token JWT.
3. Acessar `webAdm/login.php`, autenticar, confirmar que o painel carrega e que o link "Esqueci minha senha" funciona (dispara e-mail real, valida a integração SMTP).
4. Confirmar que `public/storage/` responde (ex.: acessar a URL de uma foto de perfil) — valida o `storage:link`.
5. Gerar um relatório técnico de teste (exige `dompdf` funcionando) e confirmar o download do PDF.
