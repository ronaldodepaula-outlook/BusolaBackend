# Documentação de Fluxo Operacional — SistemaPesquisas (busola)

> Guia de uso do painel (`webAdm`), passo a passo, por perfil de usuário. Para as regras por trás de cada tela, veja [REGRAS_DE_NEGOCIO.md](REGRAS_DE_NEGOCIO.md).

## 1. Acesso ao sistema

### 1.1 Login

Acesse a URL do painel (`login.php`) e informe e-mail/senha. Casos especiais:
- **Sessão expirada**: o sistema redireciona automaticamente para o login com um aviso; é só entrar novamente.
- **Esqueci minha senha**: link na própria tela de login → informe o e-mail → um link de redefinição chega por e-mail, válido por **30 minutos**, uso único. Por segurança, a tela sempre mostra a mesma mensagem de confirmação, exista ou não aquele e-mail cadastrado.

### 1.2 Primeiro acesso de um usuário recém-criado

Quando um administrador cadastra um novo usuário **sem definir uma senha**, o próprio usuário recebe um e-mail com um link de ativação (válido por **24 horas**, uso único). Ao abrir o link, ele define sua própria senha (mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo) e a conta é ativada automaticamente — não é preciso nenhum passo adicional do administrador.

## 2. Estrutura do menu

O menu lateral é organizado em 5 grupos:

- **Dashboard** — painel inicial com indicadores.
- **Gestão** — Empresas¹, Filiais, Usuários, submenu **Pesquisas Psicossociais** (Formulários, Padrões de Formulário, Conceitos de Avaliação, Campanhas, Colaboradores, Setores e GHE), Relatórios Técnicos¹.
- **Controle de Acesso** — Perfis de Acesso, Permissões¹.
- **Sistema** — Configurações, Logs de Auditoria.
- **Conta** — Meu Perfil, Sair.

¹ Visível apenas ao super administrador (ou, no caso de Relatórios Técnicos, também a quem tiver a permissão de listar relatórios de todas as empresas).

## 3. Jornada do Super Administrador

O super administrador enxerga e opera **todas as empresas** clientes do sistema.

1. **Cadastrar uma empresa nova**: menu Gestão → Empresas → "Nova Empresa". Defina plano, limites de filiais/usuários, dados cadastrais. A empresa nasce ativa.
2. **Selecionar uma empresa para operar em nome dela**: em qualquer tela com seletor de empresa (Filiais, Usuários, Configurações, Logs, formulários, etc.), escolha a empresa no topo — as ações seguintes (criar usuário, criar formulário...) passam a valer para aquela empresa.
3. **Gerenciar permissões do sistema**: menu Controle de Acesso → Permissões (exclusivo do super administrador) — normalmente não é necessário mexer aqui no dia a dia, é a base do RBAC.
4. **Auditar relatórios técnicos de todas as empresas**: menu Gestão → Relatórios Técnicos — visão consolidada, com filtro por empresa, para acompanhamento/auditoria sem precisar entrar em cada empresa individualmente.
5. **Excluir uma campanha definitivamente**: ação exclusiva do super administrador (nem o Administrador de empresa tem essa permissão) — remove em cascata a campanha, convites, respostas coletadas, plano de ação e relatórios técnicos gerados. Ação irreversível: a tela exige digitar a palavra "EXCLUIR" para confirmar.

## 4. Jornada do Administrador de Empresa — configuração inicial

Antes de aplicar a primeira campanha de pesquisa, uma empresa nova normalmente configura, nesta ordem:

1. **Filiais** (se houver mais de uma unidade) — menu Gestão → Filiais.
2. **Usuários** (contas de acesso ao painel para outras pessoas da empresa) — menu Gestão → Usuários. Ao criar, você pode deixar o campo de senha em branco: o próprio usuário recebe o e-mail de ativação (ver 1.2).
3. **Colaboradores** (as pessoas que vão *responder* às pesquisas — não precisam de conta de acesso ao painel) — menu Pesquisas Psicossociais → Colaboradores. Cadastro manual, um por um, ou importação em massa via CSV.
4. **Setores e GHE** (Grupos Homogêneos de Exposição) — agrupe setores por similaridade de exposição ocupacional; os resultados das campanhas são tabulados por GHE.
5. **Conceitos de Avaliação** (escalas, ex.: "Nunca / Raramente / Às vezes / Frequentemente / Sempre") — normalmente já vêm prontos nos formulários oficiais (COPSOQ II completo e resumido), só é necessário criar um conceito novo para uma escala customizada.
6. **Padrões de Formulário** (opcional) — se a empresa quiser usar uma metodologia própria em vez das duas oficiais já disponíveis (COPSOQ II Completo e COPSOQ II Resumido), cadastre aqui o padrão e associe-o a um formulário.
7. **Formulários** — crie um formulário do zero (Categoria → Subcategoria → Pergunta, com drag-and-drop de ordenação, ou importação de estrutura via CSV) ou reutilize um formulário global (COPSOQ II Completo/Resumido) já publicado pelo super administrador. Um formulário só pode ser usado numa campanha depois de **publicado**.

## 5. Ciclo de vida de uma Campanha (Pesquisa)

### 5.1 Criação — assistente de 4 passos

Menu Pesquisas Psicossociais → Campanhas → "Nova Campanha" abre o assistente:

1. **Formulário** — escolha um formulário publicado. A campanha já é criada em rascunho neste momento.
2. **Dados da Campanha** — nome, descrição, se as respostas são anônimas (recomendado, marcado por padrão), data de início e fim.
3. **Público-alvo** — toda a empresa, filiais específicas, ou colaboradores específicos (marcados individualmente).
4. **Revisão e Publicação** — confira o resumo e escolha: "Salvar como rascunho" (decide depois) ou "Publicar Campanha".

Enquanto estiver em **rascunho**, qualquer dado e o público-alvo podem ser editados livremente pelo assistente. **Depois de publicada, não há mais volta** por essa tela — a campanha já está coletando respostas.

### 5.2 Após publicar — convites e distribuição do link

Ao publicar, o sistema gera automaticamente um **link individual único** para cada colaborador do público-alvo. Acesse "Convites" (a partir da lista de Campanhas) para:
- Copiar o link de uma pessoa específica.
- Exportar todos os links em CSV, para distribuição em massa (por e-mail, WhatsApp etc. — o envio em si é manual, o sistema não dispara e-mail automático dos links).
- Acompanhar quem já respondeu (sem nunca revelar o conteúdo da resposta de ninguém).

Existe também um **link público único da campanha** (compartilhável, ex. num mural ou grupo), para quem preferir não gerenciar convite por pessoa — o sistema evita respostas duplicadas do mesmo dispositivo/sessão.

### 5.3 O colaborador respondendo

O colaborador abre o link recebido (sem precisar de login/conta no sistema), vê um aviso de anonimato, e preenche o formulário em rolagem única. Depois de enviado, o link daquele colaborador não pode mais ser reutilizado.

### 5.4 Acompanhamento — Resultados

Acesse "Resultados" (a partir da lista de Campanhas, requer a permissão de consultar resultados) para ver, em tempo real conforme as respostas chegam:
- Taxa de resposta (individual e via link público).
- Média por categoria/dimensão avaliada.
- Classificação de risco por categoria e por GHE (com farol de cor).
- **Matriz de Risco** (Probabilidade × Severidade): grade visual mostrando quantas avaliações caíram em cada combinação.
- Distribuição de respostas por pergunta (gráficos) e respostas de texto livre, quando o formulário tiver perguntas abertas.

Grupos (GHEs) com poucos respondentes aparecem combinados num "grupo agregado" para preservar o anonimato — é esperado não ver um setor pequeno isolado na tabela.

### 5.5 Plano de Ação (quando aplicável)

Se o formulário usado segue um padrão com catálogo de ações (o padrão "COPSOQ II Completo" tem; o "COPSOQ II Resumido" não), acesse "Plano de Ação" para ver o quadro Kanban PDCA:

- **Planejar** → **Executar** (anexe evidência de execução) → **Verificar** (registre um parecer) → **Agir** (avalie a eficácia).
- Se a eficácia não for plena, um novo ciclo é reaberto automaticamente na mesma ação — acompanhe pelo histórico de ciclos.

### 5.6 Relatório Técnico

Acesse "Relatório Técnico" para gerar o PDF consolidado (metodologia, composição dos GHEs, resultados, classificação de risco, plano de ação quando houver, e os anexos de referência técnica) — informe o nome/registro do responsável técnico e gere. Relatórios já gerados ficam listados para novo download a qualquer momento.

### 5.7 Encerrando a campanha

Quando o período de coleta terminar, encerre a campanha na lista de Campanhas. Isso trava o formulário usado (qualquer edição futura nele criará uma nova versão automaticamente, sem afetar o relatório já emitido desta campanha).

## 6. Meu Perfil (qualquer usuário)

Menu Conta → Meu Perfil: atualizar nome/telefone, enviar/trocar foto de perfil, e alterar a própria senha (exige informar a senha atual).
