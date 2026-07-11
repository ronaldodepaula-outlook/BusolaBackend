# Documentação de Regras de Negócio — SistemaPesquisas (busola)

> Este documento descreve o **que** o sistema faz e **por quê**, com base no código realmente implementado — não é um documento de intenções. Para arquitetura/convenções técnicas, veja [DESENVOLVEDOR.md](DESENVOLVEDOR.md).

## 1. Visão geral

O busola é uma plataforma **multi-tenant** (multi-empresa) com dois grandes domínios:

1. **Gestão administrativa** ("core"): empresas clientes, filiais, usuários (contas de acesso), papéis e permissões (RBAC), configurações, auditoria.
2. **Gestão de Pesquisas Psicossociais** (módulo `Pesquisa`): construção de formulários de avaliação de risco psicossocial ocupacional, aplicação de campanhas anônimas aos colaboradores, tabulação de resultados, classificação de risco (Probabilidade × Severidade), geração de plano de ação (PDCA) e emissão de relatório técnico em PDF — alinhado à NR-1 (Portaria GM/MS nº 5.674/2024) e à metodologia COPSOQ II.

## 2. Multi-tenancy

Cada usuário (exceto o super administrador) pertence a exatamly uma Empresa (`users.empresa_id`) e só enxerga dados dessa empresa — reforçado pelo `TenantMiddleware` em toda rota que o aplica.

- **Super administrador** (`tipo = superadmin`): sem `empresa_id`. Pode operar "sem tenant" (acesso global) ou escopar uma requisição a uma empresa específica enviando o header `X-Empresa-Id` — usado pelo webAdm sempre que o superadmin escolhe uma empresa num seletor de tela.
- **Demais usuários**: presos à própria empresa; o header `X-Empresa-Id` é ignorado para eles mesmo que enviado — não há como um admin de empresa "vazar" para outro tenant trocando um header.
- Empresa com `status` diferente de `ativo` bloqueia login/acesso de todos os seus usuários (`EMPRESA_INATIVA`).
- O grupo de rotas `empresas` não aplica o middleware `tenant` (só autenticação) — o próprio `EmpresaController` faz o escopo manualmente, já que é a única entidade que "está acima" do conceito de tenant.

## 3. RBAC — papéis e permissões

Dois níveis de controle, aplicados juntos:

1. **Tipo de usuário** (`users.tipo`: `superadmin | admin | gerente | usuario`) — `superadmin` ignora toda checagem de permissão granular (bypass total no `PermissionMiddleware` e no `TenantMiddleware`).
2. **Permissões granulares** (slugs como `pesquisa.criar`, `colaborador.excluir`), atribuídas via **Papéis** (`roles`, N:N com `users` e com `permissions`).

Um papel pode ser:
- **De sistema** (`sistema=true`, `empresa_id=null`): os 4 papéis seedados (`superadmin`, `admin`, `gerente`, `operador`) são visíveis e atribuíveis por qualquer empresa.
- **Customizado de uma empresa** (`sistema=false`, `empresa_id=<empresa>`): criado pela própria empresa via tela de Perfis de Acesso, visível só para ela.

### Papéis-padrão e o que cada um recebe

| Papel | Regra |
|---|---|
| **Super Administrador** | Todas as permissões do sistema (redundante com o bypass de `tipo=superadmin`, mas mantido por consistência/auditoria). |
| **Administrador** | Todas as permissões **exceto**: excluir empresa, criar/editar/excluir permissões do sistema, dashboard de superadmin, exportar logs, listar relatórios técnicos de todas as empresas, e **excluir campanha definitivamente** — essas operações são reservadas ao super administrador mesmo dentro da própria empresa. |
| **Gerente** | Lista curada, essencialmente somente-consulta: gestão de usuários (sem excluir), leitura de filiais/papéis/logs, e no módulo Pesquisa apenas listar/visualizar/consultar resultados — nunca criar, editar ou publicar campanhas/formulários. |
| **Operador** | Só leitura de usuários e filiais + dashboard da empresa. |

Usuários do tipo `usuario` **não recebem nenhum papel automaticamente** — precisam de atribuição explícita (na criação ou depois, via "Atribuir Perfis").

**Nota de implementação — dupla camada**: vários endpoints administrativos (criar/excluir Empresa, excluir Usuário, ativar/inativar Empresa) checam o `tipo` do usuário diretamente no controller, *além* da permissão da rota — uma segunda barreira deliberada para operações consideradas críticas (defesa em profundidade), não apenas uma consequência do RBAC genérico.

## 4. Empresas e Filiais

- Empresa tem `plano` (`basic | professional | enterprise`), `max_filiais` e `max_usuarios` (limites; ausência = ilimitado).
- **Ao criar uma Filial ou um Usuário**, o sistema conta quantos já existem na empresa e bloqueia com HTTP 400 ("Limite de X atingido (N). Atualize seu plano para adicionar mais.") se o limite do plano já foi atingido — mesma regra aplicada de forma idêntica nos dois recursos.
- Apenas o **super administrador** pode criar ou excluir uma Empresa; ninguém pode excluir a própria empresa nem a própria conta de usuário.
- Empresa/Filial/Usuário usam soft delete — excluir não apaga fisicamente o registro.

## 5. Usuários e Autenticação

### 5.1 Criação de usuário — com ou sem senha

Ao criar um usuário (`POST /usuarios`), a senha é **opcional**:

- **Sem senha informada** (fluxo padrão recomendado): o usuário nasce com `senha = null`, `status = inativo`. O sistema gera um token de ativação (256 bits de entropia, válido por **24 horas**, uso único) e envia um e-mail com o link `ativar-conta.php?token=...`. Enquanto não ativa, o login falha normalmente (hash nulo nunca confere) — não é necessária nenhuma checagem extra de "conta pendente".
- **Com senha informada**: comportamento legado preservado — usuário nasce `status = ativo`, pronto para uso imediato, sem e-mail de convite.

Ao concluir a ativação (definir a primeira senha), a conta é automaticamente marcada `status = ativo`, `primeiro_acesso = false`, e o token é invalidado.

### 5.2 Login

- `POST /auth/login` com e-mail/senha → JWT. Falha genérica ("E-mail ou senha incorretos") tanto para e-mail inexistente quanto senha errada (não distingue, por segurança).
- Bloqueado se `status` do usuário for `inativo` ou `bloqueado` (qualquer coisa diferente de `ativo`).
- Logout adiciona o token a uma blacklist própria (`token_blacklist`) **e** invalida via o pacote JWT — dupla garantia contra reuso do mesmo token após logout.

### 5.3 Recuperação de senha ("Esqueci minha senha")

- `POST /auth/recuperar-senha` (e-mail) → **sempre** responde com sucesso genérico, exista ou não o e-mail — proteção contra enumeração de contas.
- Contas **bloqueadas** pelo administrador não recebem o link (recuperar senha não pode ser um atalho para burlar um bloqueio administrativo).
- Token válido por **30 minutos**, uso único.
- Se a conta usada nunca tinha sido ativada (Fluxo 1 pendente), concluir a recuperação **também** ativa a conta (`status=ativo`, `primeiro_acesso=false`) — evita deixar uma conta com senha nova mas ainda tecnicamente "inativa".
- Rate limit: 5 solicitações por minuto por IP.

### 5.4 Redefinição/troca de senha — 3 caminhos distintos

| Caminho | Quem aciona | Exige senha atual? | Observação |
|---|---|---|---|
| Trocar senha (autoatendido) | O próprio usuário logado | Sim | `POST /auth/trocar-senha` |
| Reset forçado pelo admin | Administrador da empresa | Não | Gera senha aleatória de 12 caracteres e **devolve em claro na resposta** para o admin repassar manualmente — não envia e-mail |
| Recuperação (Fluxo 2) | O próprio usuário, self-service | Não (usa token de e-mail) | Ver 5.3 |

### 5.5 Política de senha forte

Toda vez que o próprio usuário define uma senha (ativação ou recuperação), ela deve ter: mínimo de 8 caracteres, letras maiúsculas e minúsculas, ao menos um número e um símbolo. (O reset forçado pelo admin e a criação inicial pelo admin não passam por essa política — são de responsabilidade de quem os executa.)

## 6. Configurações

Chave-valor livre por empresa (e opcionalmente por filial), sem um catálogo fixo de chaves — qualquer `grupo`/`chave` pode ser criado. Usado hoje, por exemplo, para parâmetros de e-mail/SMTP por empresa. **Atenção**: as rotas de configuração não exigem a permissão `configuracao.editar` — qualquer usuário autenticado da própria empresa pode ler/gravar suas configurações.

## 7. Auditoria (Logs)

Toda rota marcada com o middleware `log.api` grava automaticamente, após a resposta, quem fez o quê: usuário, empresa, IP, rota, verbo HTTP, corpo da requisição (com campos sensíveis como `senha`/`token`/`nova_senha` substituídos por `***`), e o código de status HTTP retornado. Uma falha ao gravar o log nunca derruba a resposta ao cliente (é capturada e apenas registrada no log de arquivo do servidor). Esse mecanismo é a base do atendimento a requisitos de rastreabilidade/LGPD do sistema — não é preciso (nem deve ser) implementado auditoria manual redundante em cada endpoint.

## 8. Dashboard

- **Super administrador**: indicadores globais — total de empresas (ativas/inativas), total de usuários/filiais no sistema, últimos acessos, empresas bloqueadas (com contagem de filiais/usuários afetados), últimos logs de todo o sistema, distribuição de empresas por plano.
- **Empresa** (qualquer usuário autenticado): indicadores da própria empresa — usuários/filiais (e filiais ativas), papéis visíveis (próprios + de sistema), total de permissões do sistema, últimos acessos e logs da empresa, distribuição de usuários por tipo e por status.

---

## 9. Módulo de Pesquisas Psicossociais

### 9.1 Formulário

Um Formulário é a estrutura reutilizável de perguntas (Categoria → Subcategoria → Pergunta), com **versionamento automático**: editar um formulário já vinculado a uma campanha **encerrada** cria uma nova versão automaticamente (a versão antiga é preservada e arquivada, nunca alterada retroativamente) — garante que o relatório técnico de uma campanha já encerrada sempre reflita exatamente o formulário que foi de fato aplicado.

- **Tipo**: `global` (qualquer empresa pode usar, só o super administrador cria) ou `empresa` (exclusivo de uma empresa).
- **Status**: `rascunho → publicado → arquivado`. Só formulário `publicado` e `ativo` (vigente) pode ser usado para criar uma campanha nova.

### 9.2 Padrão de Formulário e os dois motores de cálculo de risco

Um Formulário pode (opcionalmente) seguir um **Padrão de Formulário** — a norma/metodologia que ele implementa (ex.: "COPSOQ II completo", "COPSOQ II Resumido", ou um padrão próprio de uma empresa). O Padrão é `global` ou de uma `empresa`, e define **qual motor de cálculo de risco** as campanhas baseadas naquele formulário usam. Isso permite empresas diferentes (ou a mesma empresa, em momentos diferentes) aplicarem metodologias distintas sem qualquer alteração de código.

Hoje o sistema traz dois motores prontos:

| | **NR-1 Completo** (padrão histórico, usado quando nenhum Padrão é definido) | **COPSOQ II Resumido** |
|---|---|---|
| Categorias/dimensões | 11 fatores oficiais (Gestão Organizacional, Contexto da Organização do Trabalho, Relações Sociais, Conteúdo das Tarefas, Condições do Ambiente, Interação Pessoa-Tarefa, Jornada, Violência e Assédio, Discriminação, Risco de Morte e Trauma, Insegurança no Emprego) | 7 dimensões (Demandas, Controle, Apoio da Chefia, Apoio dos Colegas, Relacionamentos, Cargo, Comunicação e Mudanças) |
| Severidade | Fixa por categoria (2 a 5), oficial | Fixa por dimensão (2 a 5) |
| Conversão Média → Probabilidade | Faixas irregulares, com piso de materialidade: média **abaixo de 1,30** = "Exposição Não Significativa" (fora da matriz) | Faixas uniformes (1,00–1,79 / 1,80–2,59 / 2,60–3,39 / 3,40–4,19 / 4,20–5,00), sem piso — toda média de 1 a 5 sempre produz uma Probabilidade |
| Classificação final | Tabela de consulta assimétrica (não é o produto matemático), 6 níveis: Não Significativo, Trivial, Tolerável, Moderado, Substancial, Intolerável — agrupados em 5 rótulos no relatório (Monitoramento / Irrelevante / De Atenção / Crítico / Não Tolerável) | Produto matemático simples Probabilidade × Severidade, 4 faixas: 1–3 Baixo, 4–8 Moderado, 9–14 Alto, 15–25 Crítico |
| Plano de Ação automático | Sim — catálogo de 165 ações-modelo (3 tipos de controle × 5 níveis de risco × 11 categorias) | **Não** — a planilha de referência deste padrão não define um catálogo de ações; nenhuma ação é gerada automaticamente para campanhas neste modelo (o nível de risco continua sendo calculado e exibido normalmente) |

A mesma pergunta, categoria e resultado tabulado funcionam de forma idêntica nos dois padrões — a única coisa que muda é qual severidade/faixas/matriz é aplicada, decidido automaticamente a partir do Padrão de Formulário da campanha.

### 9.3 Colaboradores e LGPD

Colaborador é a pessoa que efetivamente responde a uma pesquisa — **não é uma conta de acesso ao sistema** (não faz login). Pode ser cadastrado manualmente ou importado em lote via CSV.

- Dados sensíveis (**CPF**, **data de nascimento**) são **cifrados no banco** (nunca gravados em texto puro), com um hash SHA-256 adicional do CPF só para permitir checar duplicidade sem nunca precisar descriptografar.
- Em qualquer listagem/consulta padrão, esses campos aparecem **mascarados** (ex.: CPF como `***.***.**1-23`).
- Ver o dado em claro exige uma permissão dedicada e separada (`colaborador.visualizar_dados_sensiveis`) — e essa consulta específica fica registrada no log de auditoria (rastreabilidade de quem acessou dado sensível, quando).
- **Anonimização** (em vez de exclusão): remove nome/e-mail/matrícula/CPF/nascimento, mas preserva o histórico de convites/respostas já vinculado — que, por design (ver 9.5), nunca guardou dado pessoal de qualquer forma.

### 9.4 Campanha (Pesquisa) — ciclo de vida

`rascunho → ativa → encerrada` (ou `cancelada`). Só é possível editar dados/público-alvo enquanto `rascunho`. Publicar (`ativa`) é o momento em que:

1. O público-alvo é travado.
2. Os convites individuais são gerados (um token único por colaborador do público-alvo).
3. A campanha passa a aceitar respostas.

**Público-alvo**, definido no wizard de criação (4 passos: escolher formulário → dados da campanha → público-alvo → revisão/publicação): toda a empresa, filiais específicas, ou colaboradores específicos.

### 9.5 Anonimato das respostas

Ponto central de design do sistema: **o conteúdo de uma resposta nunca pode ser associado a quem respondeu**, mesmo pelo administrador.

- Cada colaborador do público-alvo recebe um **link individual** (token único) — a plataforma sabe *quem tem* aquele link (para acompanhar quem já respondeu) e pode marcar `respondido_em`, mas a tabela de respostas em si (`pesq_pesquisa_respostas`) **não tem nenhuma coluna** que ligue uma resposta a um usuário ou convite específico — é uma garantia estrutural (de schema), não apenas de regra de aplicação.
- Existe também um **link público global** por campanha (compartilhável), com controle de "uma resposta por sessão/dispositivo" via cookie, para cenários sem convite individual.
- Um token de convite só pode ser usado uma vez e apenas dentro do período de vigência da campanha (`data_inicio`/`data_fim`).

### 9.6 Resultados — agregação e confidencialidade por grupo pequeno

Toda consulta de resultado retorna dados **agregados** (médias, contagens) — nunca uma resposta individual associada a quem respondeu. Além disso, para preservar o anonimato de **grupos pequenos**: GHEs (Grupos Homogêneos de Exposição) com menos respondentes que o quantitativo mínimo configurado na campanha (`minimo_respondentes`, padrão 5) são combinados automaticamente em um "Grupo agregado (confidencialidade)" em vez de aparecerem isolados — evita que, num setor de 2 pessoas, dê para inferir quem respondeu o quê pela média do grupo.

A **Matriz de Risco** (Probabilidade × Severidade) do dashboard mostra, numa grade 5×5, quantas avaliações de risco (Categoria × GHE) caíram em cada combinação — a cor de cada célula já reflete o motor de cálculo (NR-1 ou COPSOQ resumido) da campanha em questão.

### 9.7 Plano de Ação — ciclo PDCA

Quando o padrão da campanha tem catálogo de ações (NR-1 completo), cada combinação Categoria × GHE × Tipo de Controle com nível de risco significativo gera automaticamente uma ação, iniciando na fase **Planejar** de um ciclo PDCA explícito:

`Planejar → Executar → Verificar → Agir`, sequencial e sem pular etapas — avançar de Executar para Verificar exige registrar uma evidência de execução; avançar de Verificar para Agir exige um parecer de verificação; concluir o ciclo exige uma avaliação de eficácia (`eficaz | parcialmente_eficaz | ineficaz`).

- **Ciclo eficaz** → ação encerrada.
- **Ciclo não plenamente eficaz** → um **novo ciclo é aberto automaticamente** (a fase volta a Planejar, o número do ciclo é incrementado, e o ciclo anterior fica arquivado no histórico) — o sistema nunca deixa uma ação "morrer" silenciosamente por ineficácia.

### 9.8 Relatório Técnico (PDF)

Documento consolidado por campanha, gerado sob demanda, salvo em disco privado (nunca público) e listado para consulta posterior (inclusive numa visão cross-empresa exclusiva do super administrador). Conteúdo:

- Metodologia (fixa, universal aos dois padrões de cálculo).
- Seções 3.7/3.8 — tabela de severidade por categoria e tabela de níveis de risco — **específicas do padrão de cálculo da campanha** (lista as 11 categorias do NR-1 completo ou as 7 dimensões do COPSOQ resumido, conforme o caso).
- Composição dos GHEs da empresa (setores agrupados, quantidade de colaboradores ativos).
- Resultados por categoria/GHE com classificação de risco.
- Plano de ação (quando o padrão possuir catálogo de ações).
- Anexo I (referência técnica de cada fator/dimensão de risco) e Anexo II (perguntas efetivamente aplicadas, agrupadas por fator) — ambos gerados dinamicamente a partir dos dados reais da campanha, nunca uma lista estática.
