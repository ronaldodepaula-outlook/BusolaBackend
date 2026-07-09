# 🧭 busola - Sistema de Gestão de Riscos Psicossociais NR-1

## 📋 Descrição

Plataforma web interativa para gestão completa de riscos psicossociais ocupacionais, em conformidade com:

- **NR-1** (Portaria MTE 1.419/2024)
- **GRO/PGR** (Gerenciamento de Riscos Ocupacionais / Programa de Gerenciamento de Riscos)
- **ISO 45003:2021** (Gestão de riscos psicossociais)
- **COPSOQ II** (Copenhagen Psychosocial Questionnaire)
- **HSE Indicator Tool** (Health & Safety Executive)
- **Portaria GM/MS nº 5.674/2024**
- Diretrizes **OIT** e **OMS**

## 🎯 Funcionalidades Principais

### 1. **Dashboard Executivo**
- Score psicossocial geral
- Distribuição de riscos por nível (Irrelevante, Baixo, Moderado, Alto, Crítico)
- Heatmap organizacional
- Indicadores por dimensão
- Gráficos de tendências

### 2. **Gestão Organizacional**
- Cadastro de empresas (multi-tenant)
- Gestão de filiais e unidades
- Estrutura organizacional (áreas, setores, cargos)
- Grupos Homogêneos de Exposição (GHE/GES)
- Importação de colaboradores

### 3. **Motor de Questionários**
- COPSOQ II - Versão Média
- HSE Indicator Tool
- Clima Organizacional
- Questionários customizáveis
- Aplicação anônima e confidencial
- Distribuição por email, WhatsApp, QR Code

### 4. **Análise de Risco**
- Cálculo automático: R = Probabilidade × Gravidade
- Matriz de Risco 5×5
- Classificação visual por cores
- Inventário de riscos psicossociais
- Análise qualitativa (entrevistas, observações)

### 5. **Plano de Ação (PDCA)**
- Criação de ações auditáveis
- Rastreamento de progresso
- Atribuição de responsáveis
- Prazos e SLAs
- Workflow de status

### 6. **Relatórios Técnicos**
- Relatório Técnico NR-1 (completo)
- Relatório Executivo
- Relatório RH
- Relatório SST
- Exportação em PDF
- Assinatura digital

### 7. **Segurança e Compliance**
- Trilha de auditoria completa
- Controle de acesso por perfil
- Criptografia de dados sensíveis
- Conformidade LGPD
- Anonimização de respondentes

## 👥 Perfis de Usuário

### Super Administrador
- Gestão multiempresa
- Gestão de planos e licenciamento
- Cadastro de usuários
- Configuração de templates
- Gestão da metodologia

### Administrador da Empresa
- Gerenciamento de unidades
- Cadastro de GHE/GES
- Gestão de colaboradores
- Aplicação de avaliações
- Visualização de dashboards
- Aprovação de planos de ação
- Emissão de relatórios

### Psicólogo / Consultor
- Avaliação qualitativa
- Registro de entrevistas
- Inserção de pareceres
- Ajuste técnico de gravidade
- Geração de laudo técnico

### Gestor / Liderança
- Visualização da sua área
- Plano de ação do setor
- Indicadores do time
- Evidências de execução

### Colaborador
- Responder questionários
- Visualizar status
- Canal confidencial
- Aceitar termos LGPD

## 🏗️ Estrutura do Projeto

```
busola-sistema-riscos/
├── index.html          # Página principal (HTML interativo)
├── styles.css          # Estilos e design system
├── script.js           # Lógica e interatividade
├── README.md           # Este arquivo
└── docs/
    ├── MANUAL.md       # Manual do usuário
    ├── API.md          # Documentação da API
    └── SEGURANCA.md    # Políticas de segurança
```

## 🚀 Como Usar

### Instalação
1. Extraia o arquivo ZIP
2. Abra `index.html` em um navegador moderno (Chrome, Firefox, Safari, Edge)
3. Não requer instalação de dependências

### Navegação
- Use o menu lateral para acessar diferentes módulos
- Clique nos botões "+ Nova..." para criar registros
- Use as tabelas para visualizar e gerenciar dados
- Acesse relatórios através do menu "Relatórios"

### Funcionalidades Interativas
- **Modais**: Clique nos botões para abrir formulários
- **Tabelas**: Busque, filtre e ordene dados
- **Gráficos**: Visualize dados em tempo real
- **Matriz de Risco**: Veja a classificação de riscos
- **Exportação**: Exporte dados em JSON ou CSV

## 🎨 Design System

### Paleta de Cores
- **Primária**: #0066cc (Azul)
- **Secundária**: #7c3aed (Púrpura)
- **Sucesso**: #22c55e (Verde)
- **Aviso**: #eab308 (Amarelo)
- **Perigo**: #ef4444 (Vermelho)
- **Info**: #06b6d4 (Ciano)

### Escala de Risco
- 🔵 **Irrelevante** (1-2): Azul
- 🟢 **Baixo** (3-4): Verde
- 🟡 **Moderado** (5-9): Amarelo
- 🟠 **Alto** (10-16): Laranja
- 🔴 **Crítico** (17-25): Vermelho

### Tipografia
- **Sans-serif**: Sistema padrão do navegador
- **Tamanhos**: 11px (labels) até 32px (títulos)
- **Pesos**: 400 (regular), 500 (medium), 600 (semibold), 700 (bold)

## 📊 Matriz de Risco

A matriz implementa a fórmula:

```
Risco = Probabilidade × Gravidade

Probabilidade (1-5):
1.00–1.79 = 1
1.80–2.59 = 2
2.60–3.39 = 3
3.40–4.19 = 4
4.20–5.00 = 5

Gravidade (1-5):
Configurável por dimensão
```

### Classificação de Risco
| Resultado | Classificação | Cor | Ação |
|-----------|----------------|-----|------|
| 1-2 | Irrelevante | 🔵 Azul | Monitorar |
| 3-4 | Baixo | 🟢 Verde | Acompanhar |
| 5-9 | Moderado | 🟡 Amarelo | Intervir |
| 10-16 | Alto | 🟠 Laranja | Intervir Urgente |
| 17-25 | Crítico | 🔴 Vermelho | Intervir Imediato |

## 🔒 Segurança

### Conformidade
- ✅ LGPD (Lei Geral de Proteção de Dados)
- ✅ NR-1 (Norma Regulamentadora 1)
- ✅ ISO 45003:2021
- ✅ Sigilo profissional (Código de Ética do Psicólogo)

### Anonimato
- Respondentes identificados apenas por token único
- Impossibilidade de identificação individual em grupos pequenos
- Dados agregados por GHE/GES

### Auditoria
- Trilha completa de todas as operações
- Registro de IP, data/hora, usuário
- Histórico de alterações
- Impossibilidade de exclusão de logs

## 📱 Responsividade

O sistema é totalmente responsivo:
- **Desktop**: Layout completo com sidebar
- **Tablet**: Menu colapsável
- **Mobile**: Interface otimizada para toque

## 🔄 Fluxo PDCA

O sistema implementa o ciclo contínuo:

1. **Plan (Planejar)**
   - Definição de metodologia
   - Mapeamento de GHE/GES
   - Estruturação de instrumentos

2. **Do (Executar)**
   - Coleta de dados (quantitativa e qualitativa)
   - Aplicação de questionários
   - Registro de entrevistas

3. **Check (Verificar)**
   - Análise de resultados
   - Cálculo de riscos
   - Geração de relatórios

4. **Act (Agir)**
   - Criação de plano de ação
   - Implementação de medidas
   - Monitoramento contínuo

## 📈 Indicadores-Chave

- Score Psicossocial (0-10)
- Taxa de Resposta (%)
- Distribuição de Riscos
- Absenteísmo
- Presenteísmo
- Turnover
- Afastamentos INSS
- CID Mental
- eNPS (Employee Net Promoter Score)
- Clima Organizacional

## 🛠️ Tecnologias

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Armazenamento**: LocalStorage (navegador)
- **Compatibilidade**: Todos os navegadores modernos
- **Responsividade**: Mobile-first

## 📚 Documentação Adicional

Consulte os arquivos na pasta `docs/`:
- `MANUAL.md` - Manual completo do usuário
- `API.md` - Documentação de integração
- `SEGURANCA.md` - Políticas de segurança

## 🤝 Suporte

Para dúvidas ou sugestões:
- 📧 Email: support@busola.com.br
- 💬 WhatsApp: (11) 9999-9999
- 🌐 Website: www.busola.com.br

## 📄 Licença

Todos os direitos reservados © 2024 busola - Gestão Inteligente de Riscos

## 🎓 Conformidade Regulatória

Este sistema foi desenvolvido em conformidade com:

- **NR-1** - Disposições Gerais (Portaria MTE nº 1.419/2024)
- **GRO** - Gerenciamento de Riscos Ocupacionais
- **PGR** - Programa de Gerenciamento de Riscos
- **ISO 45003:2021** - Gestão de riscos psicossociais em sistemas de gestão de SST
- **COPSOQ II** - Copenhagen Psychosocial Questionnaire (versão brasileira)
- **HSE Indicator Tool** - Health & Safety Executive
- **Portaria GM/MS nº 5.674/2024** - Diretrizes sobre riscos psicossociais
- **Diretrizes OIT** - Organização Internacional do Trabalho
- **Diretrizes OMS** - Organização Mundial da Saúde
- **LGPD** - Lei Geral de Proteção de Dados
- **Código de Ética Profissional do Psicólogo**

## 📊 Dados de Exemplo

O sistema vem pré-carregado com dados de exemplo:
- 2 empresas
- 3 GHE/GES
- 190 colaboradores
- 245 respostas de questionários
- 18 ações no plano de ação

Você pode limpar esses dados e começar com dados reais.

## 🔄 Versão

**Versão Atual**: 1.0.0
**Data de Lançamento**: Janeiro 2024
**Última Atualização**: Janeiro 2024

---

**Desenvolvido com ❤️ para a saúde mental nas organizações**
