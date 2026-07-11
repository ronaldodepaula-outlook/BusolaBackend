<div align="center">

# 🧭 busola

### Gestão Inteligente de Riscos Psicossociais

Avaliação de riscos psicossociais ocupacionais alinhada à **NR-1** (Portaria GM/MS nº 5.674/2024) e à metodologia **COPSOQ II**, com aplicação 100% anônima, cálculo automático de risco, plano de ação PDCA e relatório técnico em PDF — pronto para o seu GRO/PGR.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](composer.json)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](docs/DESENVOLVEDOR.md#6-testes)
[![License](https://img.shields.io/badge/license-proprietary-lightgrey.svg)](#licença)

[Regras de Negócio](docs/REGRAS_DE_NEGOCIO.md) · [Fluxo Operacional](docs/FLUXO_OPERACIONAL.md) · [Implantação](docs/IMPLANTACAO.md) · [Para Desenvolvedores](docs/DESENVOLVEDOR.md)

</div>

---

## O problema

Desde a atualização da NR-1, toda empresa brasileira precisa identificar, avaliar e gerenciar riscos psicossociais no ambiente de trabalho — e provar isso, de forma auditável, no seu Programa de Gerenciamento de Riscos (PGR). Fazer isso com planilhas soltas, formulários avulsos e Excel manual não escala e não é auditável.

## O que o busola entrega

- 📋 **Formulários configuráveis, com dois padrões metodológicos prontos** — COPSOQ II completo (11 categorias oficiais, alinhado à NR-1) e COPSOQ II resumido (7 dimensões) — ou crie o seu próprio Padrão de Formulário, com motor de cálculo de risco dedicado.
- 🕵️ **Resposta genuinamente anônima** — link individual por colaborador (sem exigir conta de acesso ao sistema) ou link público da campanha; a tabela de respostas **nunca** guarda qualquer referência a quem respondeu — é uma garantia de schema, não só de regra de aplicação.
- 🧮 **Classificação de risco automática** — Probabilidade × Severidade calculada a partir das respostas reais, com matriz de risco visual no dashboard e agrupamento automático de GHEs pequenos para preservar o anonimato.
- 🔄 **Plano de Ação com ciclo PDCA de verdade** — Planejar → Executar → Verificar → Agir, com reabertura automática de ciclo quando uma ação não é plenamente eficaz. Catálogo de mais de 160 ações-modelo prontas por categoria/nível de risco.
- 📄 **Relatório Técnico em PDF**, gerado sob demanda, com metodologia, composição de GHEs, resultados, plano de ação e anexos técnicos — pronto para anexar ao seu PGR.
- 👥 **Gestão de Colaboradores com LGPD levada a sério** — CPF e data de nascimento cifrados no banco, mascarados em qualquer listagem, revelação em claro só sob permissão dedicada e auditada; importação em massa via CSV.
- 🏢 **Multi-tenant de verdade** — várias empresas clientes no mesmo sistema, isoladas entre si, com planos, limites de uso e RBAC granular (papéis + permissões).
- 🔐 **Autenticação completa** — login JWT, ativação de conta por e-mail (usuário criado sem senha até definir a própria), recuperação de senha self-service, política de senha forte.

## Como é construído

| Camada | Tecnologia |
|---|---|
| API | Laravel 12 · PHP 8.2+ · JWT (`tymon/jwt-auth`) · MySQL |
| Documentação da API | OpenAPI/Swagger (`darkaonline/l5-swagger`) — 100% dos endpoints documentados |
| Geração de PDF | `barryvdh/laravel-dompdf` |
| Painel administrativo | PHP puro (sem framework JS), Bootstrap 5, Chart.js |

A arquitetura separa claramente **API** (toda regra de negócio) e **painel** (`webAdm`, apenas apresentação e orquestração de chamadas) — dois processos PHP independentes, comunicando-se só via HTTP. Veja o detalhamento completo em [docs/DESENVOLVEDOR.md](docs/DESENVOLVEDOR.md).

## Começando

```bash
git clone <repo> SistemaPesquisas && cd SistemaPesquisas
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate && php artisan jwt:secret
php artisan migrate --force && php artisan storage:link
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
```

Passo a passo completo (variáveis de ambiente, seeders, requisitos de servidor, checklist pós-deploy) em **[docs/IMPLANTACAO.md](docs/IMPLANTACAO.md)**.

## Documentação

| Documento | Para quem |
|---|---|
| [docs/FLUXO_OPERACIONAL.md](docs/FLUXO_OPERACIONAL.md) | Quem vai **usar** o sistema no dia a dia (administradores de empresa, super administrador) |
| [docs/REGRAS_DE_NEGOCIO.md](docs/REGRAS_DE_NEGOCIO.md) | Quem precisa entender **por que** o sistema se comporta assim (compliance, produto, suporte) |
| [docs/IMPLANTACAO.md](docs/IMPLANTACAO.md) | Quem vai **subir/operar** a infraestrutura |
| [docs/DESENVOLVEDOR.md](docs/DESENVOLVEDOR.md) | Quem vai **programar** neste repositório |

## Testes

```bash
php artisan test
```

Suíte de testes de integração (Feature tests) cobrindo autenticação, RBAC, todo o ciclo de campanha (formulário → convites → resposta anônima → resultados → plano de ação → relatório técnico), LGPD de colaboradores, e os dois motores de cálculo de risco.

## Licença

Este projeto é software proprietário. Todos os direitos reservados.
