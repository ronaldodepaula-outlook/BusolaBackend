<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 90px 60px 70px 60px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; }
    h1, h2, h3 { color: #0F1E3D; }
    h1 { font-size: 16px; text-align: center; margin: 0 0 24px; }
    h2 { font-size: 14px; margin-top: 26px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #16A996; }
    h3 { font-size: 12px; margin-top: 16px; margin-bottom: 6px; color: #16A996; }
    p { margin: 0 0 9px; text-align: justify; }
    ul { margin: 0 0 10px; padding-left: 16px; }
    li { margin-bottom: 4px; text-align: justify; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed; }
    th, td { border: 1px solid #cfd6e4; padding: 5px 7px; font-size: 9.5px; text-align: left; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
    th { background: #EAF6F4; color: #0F1E3D; font-weight: bold; }
    .num, .farol { text-align: center; }
    .dot { display: inline-block; width: 11px; height: 11px; border-radius: 50%; }
    .subtitulo-doc { text-align: center; color: #55607a; font-size: 11px; margin-bottom: 20px; }
    .assinatura { margin-top: 40px; text-align: center; }
    .assinatura .linha { display: inline-block; border-top: 1px solid #1a1a2e; padding-top: 4px; min-width: 260px; }
    .rodape-nota { margin-top: 30px; font-size: 9px; color: #7a8199; }
    .cap { color: #16A996; font-weight: bold; }

    /* Capa */
    .capa { page-break-after: always; }
    .capa .eyebrow { color: #16A996; font-size: 15px; letter-spacing: 4px; font-weight: bold; margin-top: 60px; }
    .capa h1.titulo-capa { text-align: left; font-size: 32px; line-height: 1.15; color: #0F1E3D; margin: 8px 0 18px; }
    .capa .regra { width: 46px; height: 4px; background: #16A996; margin-bottom: 18px; }
    .capa .tags { color: #55607a; font-size: 11px; line-height: 1.8; margin-bottom: 220px; }
    .capa .rodape-marca { background: #0F1E3D; color: #ffffff; padding: 18px 22px; }
    .capa .rodape-marca .nome { color: #ffffff; font-size: 15px; font-weight: bold; }
    .capa .rodape-marca .nome span { color: #16A996; }
    .capa .rodape-marca .slogan { color: #cfd6e4; font-size: 9.5px; margin-top: 4px; }
</style>
</head>
<body>

<div class="capa">
    <div class="eyebrow">{{ $geradoEm->format('Y') }}</div>
    <h1 class="titulo-capa">RELATÓRIO<br>DE RISCOS<br>PSICOSSOCIAIS</h1>
    <div class="regra"></div>
    <div class="tags">ANÁLISE &bull; MONITORAMENTO<br>INSIGHTS &bull; AÇÃO</div>
    <div class="rodape-marca">
        <div class="nome">bus<span>o</span>la</div>
        <div class="slogan">PESSOAS NO CENTRO. DECISÕES COM DIREÇÃO.</div>
    </div>
</div>

<h1>RELATÓRIO TÉCNICO DE ANÁLISE DE RISCOS PSICOSSOCIAIS</h1>
<p class="subtitulo-doc"><strong>Nome da empresa:</strong> {{ $empresa->nome }} &nbsp;&nbsp; <strong>CNPJ:</strong> {{ $empresa->cnpj ?? '—' }}</p>

<h2>1. Introdução</h2>
<p>Este relatório técnico apresenta os resultados da avaliação dos riscos psicossociais relacionados ao trabalho da empresa, elaborada no contexto do Gerenciamento de Riscos Ocupacionais (GRO) e do Programa de Gerenciamento de Riscos (PGR), conforme estabelecido pela Norma Regulamentadora nº 01 (NR-1), atualizada pela Portaria MTE nº 1.419/2024.</p>
<p>A avaliação foi desenvolvida com base em referências normativas nacionais e internacionais aplicáveis à gestão de riscos psicossociais no trabalho, considerando especialmente:</p>
<ul>
    <li>a Portaria GM/MS nº 5.674/2024, que atualiza a Lista de Doenças Relacionadas ao Trabalho (LDRT) e reconhece formalmente fatores psicossociais relacionados ao adoecimento ocupacional;</li>
    <li>a ISO 45003:2021, primeira norma internacional voltada especificamente à gestão de riscos psicossociais em sistemas de Segurança e Saúde no Trabalho;</li>
    <li>as diretrizes da Organização Internacional do Trabalho (OIT) e da Organização Mundial da Saúde (OMS) relacionadas à promoção da saúde mental no trabalho;</li>
    <li>e os princípios do Gerenciamento de Riscos Ocupacionais previstos na NR-1.</li>
</ul>
<p>O presente documento tem como finalidade identificar, avaliar e classificar os fatores de risco psicossociais existentes no ambiente laboral, considerando aspectos organizacionais, relacionais, operacionais e emocionais que possam impactar a saúde mental, física e social dos trabalhadores.</p>
<p>A metodologia adotada integra avaliação quantitativa e qualitativa dos fatores psicossociais, utilizando como instrumento principal o COPSOQ II – Versão Média (Copenhagen Psychosocial Questionnaire), adaptado linguisticamente para o contexto brasileiro e correlacionado aos fatores de risco psicossociais previstos na Portaria GM/MS nº 5.674/2024.</p>
<p>O levantamento dos riscos foi estruturado por Grupos Homogêneos de Exposição (GHE/GES), conforme organização previamente existente no Programa de Gerenciamento de Riscos (PGR) da contratante, permitindo análise técnica coerente com a realidade operacional da empresa.</p>
<p>Todas as informações obtidas durante o processo foram tratadas de forma ética, sigilosa e anonimizada, em conformidade com o Código de Ética Profissional do Psicólogo. Os resultados foram analisados de forma coletiva e agrupada, impossibilitando a identificação individual dos trabalhadores avaliados.</p>
<p>Nos casos em que o quantitativo reduzido de participantes pudesse comprometer a confidencialidade das respostas, os setores foram agrupados por similaridade de exposição ocupacional e psicossocial, preservando-se a integridade metodológica da análise e o sigilo profissional.</p>
<p>Além da identificação dos fatores de risco psicossociais, este relatório também apresenta diretrizes técnicas para subsidiar o plano de ação do PGR, contribuindo para a implementação de medidas preventivas, corretivas e de monitoramento contínuo da saúde ocupacional.</p>

<h2>2. Responsabilidades</h2>
<h3>2.1 Responsabilidades da contratante</h3>
<p>Nos termos da legislação vigente de Segurança e Saúde no Trabalho (SST), compete ao empregador cumprir e fazer cumprir as disposições legais e regulamentares relativas à prevenção de acidentes e agravos relacionados ao trabalho, permanecendo como principal responsável pelo gerenciamento dos riscos ocupacionais, ainda que haja contratação de empresa especializada para apoio técnico.</p>
<p>Em conformidade com a NR-1 e com os princípios do Gerenciamento de Riscos Ocupacionais (GRO), a organização deve implementar e manter, por estabelecimento, o Programa de Gerenciamento de Riscos (PGR), assegurando recursos humanos, técnicos, operacionais e financeiros necessários à sua efetiva execução.</p>
<p><strong>Compete à contratante:</strong></p>
<ul>
    <li>Identificar, avaliar e controlar os riscos ocupacionais existentes no ambiente de trabalho;</li>
    <li>Adotar medidas de prevenção visando à eliminação, redução ou controle dos riscos identificados;</li>
    <li>Implementar e manter o Programa de Gerenciamento de Riscos (PGR) como processo contínuo e permanente;</li>
    <li>Elaborar, executar e acompanhar plano de ação compatível com os riscos identificados;</li>
    <li>Designar responsável interno para acompanhamento e gestão do programa;</li>
    <li>Garantir o cumprimento dos prazos definidos no plano de ação;</li>
    <li>Atualizar o inventário de riscos e demais documentos sempre que houver alterações nos ambientes, processos, organização do trabalho ou condições operacionais;</li>
    <li>Implementar, acompanhar e avaliar a eficácia das medidas de prevenção adotadas, realizando monitoramento contínuo e reavaliações periódicas;</li>
    <li>Interromper atividades que apresentem risco grave e iminente à integridade física ou mental dos trabalhadores;</li>
    <li>Estabelecer e manter procedimentos de resposta a emergências, conforme legislação aplicável;</li>
    <li>Promover capacitação, orientação e treinamento dos trabalhadores quanto aos riscos ocupacionais identificados, medidas preventivas e procedimentos de segurança;</li>
    <li>Garantir a comunicação das informações relacionadas aos riscos psicossociais, conforme previsto no inventário de riscos e plano de ação do PGR.</li>
</ul>
<p>A Mentsaude realizou o levantamento dos fatores de risco psicossociais com base nas informações disponibilizadas pelas áreas de Recursos Humanos e/ou Saúde e Segurança do Trabalho (SST) da empresa contratante, bem como nas avaliações quantitativas e qualitativas realizadas durante o processo.</p>
<p>Além do diagnóstico técnico, a contratada apresenta recomendações e sugestões de medidas preventivas e corretivas destinadas a subsidiar o plano de ação da organização, apoiando o gerenciamento contínuo dos riscos psicossociais.</p>
<p>Ressalta-se que a gestão dos riscos psicossociais constitui obrigação legal da organização, devendo ser conduzida de forma contínua, sistemática e documentada, independentemente da empresa responsável pelo acompanhamento técnico.</p>

<h3>2.2 Responsabilidades da contratada</h3>
<p><strong>Compete à contratada:</strong></p>
<ul>
    <li>Realizar avaliações quantitativas e qualitativas dos fatores psicossociais relacionados ao trabalho, observando critérios técnicos, científicos, éticos e normativos aplicáveis;</li>
    <li>Disponibilizar profissionais habilitados para condução das avaliações, orientações técnicas e acompanhamento das atividades, de forma presencial e/ou remota;</li>
    <li>Desenvolver análises alinhadas às informações fornecidas pela contratante e à estrutura existente do PGR;</li>
    <li>Orientar tecnicamente a organização quanto aos fatores de risco psicossociais identificados e às possibilidades de gerenciamento;</li>
    <li>Elaborar e entregar os documentos previstos contratualmente, incluindo levantamento de dados, relatório técnico e recomendações para composição do plano de ação;</li>
    <li>Apresentar os resultados técnicos à gestão da empresa, sempre que previsto contratualmente;</li>
    <li>Garantir o sigilo, a confidencialidade e a proteção ética das informações obtidas durante todo o processo de avaliação.</li>
</ul>

<h2>3. Metodologia e critérios</h2>
<p>A presente metodologia está estruturada com base no Gerenciamento de Riscos Ocupacionais (GRO), conforme estabelecido na NR-1, adotando o ciclo de melhoria contínua PDCA (Plan–Do–Check–Act) como eixo central de organização e execução.</p>
<p>De acordo com o manual da NR-1, o GRO deve ser conduzido como um processo contínuo, sistemático e integrado, voltado à identificação de perigos, avaliação e controle dos riscos ocupacionais, com foco na prevenção e na melhoria contínua do ambiente de trabalho.</p>
<p>Entretanto, para garantir maior aderência à realidade organizacional e à gestão de riscos psicossociais, esta metodologia adota uma leitura aplicada do PDCA, estruturando as etapas conforme a dinâmica prática de diagnóstico, validação e intervenção.</p>

<h3>3.1 Estrutura do PDCA na metodologia aplicada</h3>
<p><strong>1. Planejar (Plan)</strong> — Refere-se à preparação e estruturação do processo, incluindo:</p>
<ul>
    <li>Definição da metodologia e critérios de avaliação (P e S);</li>
    <li>Mapeamento das categorias de risco (COPSOQ + Portaria 5.674/2024);</li>
    <li>Estruturação dos instrumentos de coleta (quantitativos e qualitativos);</li>
    <li>Alinhamento com a empresa (escopo, GHEs, estratégia de aplicação).</li>
</ul>
<p><strong>2. Executar (Do)</strong> — Corresponde à fase de coleta e tratamento dos dados, incluindo:</p>
<ul>
    <li>Aplicação dos instrumentos de diagnóstico (ex.: COPSOQ e questionários complementares);</li>
    <li>Consolidação e análise dos dados coletados;</li>
    <li>Classificação dos riscos (probabilidade, severidade e matriz);</li>
    <li>Estruturação do Inventário de Riscos.</li>
</ul>
<p><strong>3. Verificar (Check)</strong> — Refere-se à validação dos resultados junto à organização e aos trabalhadores:</p>
<ul>
    <li>Apresentação dos resultados para a diretoria;</li>
    <li>Discussão crítica dos achados;</li>
    <li>Validação qualitativa por meio de conversas com colaboradores;</li>
    <li>Identificação de possíveis distorções ou pontos não capturados quantitativamente.</li>
</ul>
<p><strong>4. Agir (Act)</strong> — Corresponde à transformação do diagnóstico em intervenção:</p>
<ul>
    <li>Construção do Plano de Ação;</li>
    <li>Definição de prioridades conforme nível de risco;</li>
    <li>Implementação das ações (organizacionais, treinamentos, processos, etc.);</li>
    <li>Acompanhamento da execução.</li>
</ul>

<h3>3.2 Abordagem Quantitativa - COPSOQ II Versão Média</h3>
<p>O COPSOQ II (Copenhagen Psychosocial Questionnaire) é um instrumento internacionalmente reconhecido para avaliação de fatores psicossociais relacionados ao trabalho, desenvolvido pelo National Research Centre for the Working Environment (NFA), da Dinamarca, e atualmente utilizado em diversos países em contextos de pesquisa, saúde ocupacional e gestão organizacional.</p>
<p>O instrumento possui fundamentação nos principais modelos teóricos de estresse ocupacional, incluindo:</p>
<ul>
    <li>Modelo Demanda–Controle (Karasek);</li>
    <li>Modelo Esforço–Recompensa (Siegrist);</li>
    <li>Teoria do Apoio Social;</li>
    <li>Modelos de equilíbrio entre trabalho e vida pessoal.</li>
</ul>
<p>Sua utilização permite mensurar dimensões psicossociais relacionadas à organização do trabalho, relações interpessoais, exigências emocionais, autonomia, reconhecimento, apoio social, exaustão e impactos do trabalho sobre a saúde mental e física dos trabalhadores.</p>
<p>O COPSOQ II possui estudos de validação no Brasil e apresenta alinhamento técnico com as diretrizes da ISO 45003:2021, norma internacional voltada à gestão de riscos psicossociais em sistemas de Segurança e Saúde no Trabalho (SST), bem como com recomendações da Organização Internacional do Trabalho (OIT) e da Organização Mundial da Saúde (OMS).</p>
<p>A presente avaliação utilizou como referência metodológica o COPSOQ II – Versão Média, com adaptações linguísticas voltadas ao contexto brasileiro e ao público avaliado, preservando os construtos psicossociais originais do instrumento.</p>
<p>Além disso, a metodologia foi estruturada considerando os fatores de risco psicossociais previstos na Portaria GM/MS nº 5.674/2024, que atualiza a Lista de Doenças Relacionadas ao Trabalho (LDRT) e reconhece formalmente fatores psicossociais associados ao adoecimento ocupacional.</p>
<p>Dessa forma, o processo avaliativo integrou: referências científicas internacionalmente reconhecidas; diretrizes normativas nacionais aplicáveis à SST; princípios da NR-1/GRO/PGR; e critérios técnicos voltados à gestão de riscos psicossociais relacionados ao trabalho.</p>
<p>Os fatores de risco psicossociais considerados nesta avaliação, suas descrições e possíveis agravos relacionados à saúde encontram-se detalhados no <strong>Anexo I</strong> deste relatório.</p>
<p>A correlação metodológica entre as perguntas utilizadas na avaliação e os fatores psicossociais previstos na Portaria GM/MS nº 5.674/2024 encontra-se apresentada no <strong>Anexo II</strong>.</p>

<h3>3.3 Abordagem Qualitativa</h3>
<p>Além da avaliação quantitativa, foi realizada etapa qualitativa complementar com o objetivo de aprofundar, validar e contextualizar os achados identificados no levantamento quantitativo.</p>
<p>A avaliação qualitativa envolveu: observação técnica do ambiente organizacional; entrevistas e escuta dos trabalhadores; análise do contexto ocupacional; identificação de fatores organizacionais, relacionais e operacionais associados aos riscos psicossociais.</p>
<p>O processo foi conduzido por profissional de Psicologia, observando rigorosamente os princípios éticos da profissão, especialmente quanto ao sigilo, neutralidade, anonimato e confidencialidade das informações obtidas.</p>
<p>As informações qualitativas foram utilizadas como apoio interpretativo dos resultados quantitativos, contribuindo para maior precisão técnica na análise dos riscos psicossociais identificados.</p>

<h3>3.4 Consolidação dos Resultados e Matriz de Risco</h3>
<p>Os resultados obtidos por meio da avaliação quantitativa foram consolidados por categoria de risco psicossocial, considerando a média das respostas obtidas na escala Likert do COPSOQ II (1 a 5).</p>
<p>As médias representam a percepção dos trabalhadores quanto à frequência, intensidade e recorrência dos fatores psicossociais presentes no ambiente de trabalho.</p>
<p>Para integração ao modelo corporativo de gerenciamento de riscos da organização, os fatores psicossociais foram avaliados por meio da Matriz de Risco Corporativa, considerando: Probabilidade de exposição (P); Severidade dos possíveis danos à saúde dos trabalhadores (S).</p>
<p>A classificação final do risco foi determinada pelo posicionamento da categoria na matriz corporativa, mediante o cruzamento entre Probabilidade e Severidade, observando-se a interpretação da célula correspondente da matriz de risco adotada pela organização, e não exclusivamente o produto matemático entre as variáveis.</p>
<p>A avaliação considera não apenas os resultados quantitativos do COPSOQ II, mas também informações complementares obtidas por meio de entrevistas, observações técnicas, análise documental, indicadores organizacionais e contexto ocupacional.</p>

<h3>3.5 Critérios de Probabilidade (P)</h3>
<p>A probabilidade representa a frequência percebida de exposição aos fatores psicossociais, sendo definida principalmente a partir dos resultados obtidos no COPSOQ II, podendo ser complementada por análise qualitativa, entrevistas, observações técnicas, indicadores organizacionais e contexto ocupacional e organizacional.</p>
<table>
    <thead><tr><th style="width:14%">Média COPSOQ II</th><th style="width:11%">Probabilidade (P)</th><th style="width:14%">Classificação</th><th>Interpretação</th></tr></thead>
    <tbody>
        <tr><td>&lt; 1,30*</td><td class="num">N/A</td><td>Exposição Não Significativa</td><td>Não há evidências quantitativas de exposição relevante ao fator psicossocial avaliado.</td></tr>
        <tr><td>1,30 – 1,49</td><td class="num">1</td><td>Raro</td><td>Exposição psicossocial rara ou pontual, com baixa evidência de recorrência entre os trabalhadores avaliados.</td></tr>
        <tr><td>1,50 – 2,49</td><td class="num">2</td><td>Pouco provável</td><td>Baixa recorrência dos fatores psicossociais, com impacto limitado sobre os trabalhadores.</td></tr>
        <tr><td>2,50 – 3,49</td><td class="num">3</td><td>Possível</td><td>Presença moderada dos fatores psicossociais, indicando necessidade de monitoramento e atenção.</td></tr>
        <tr><td>3,50 – 4,29</td><td class="num">4</td><td>Provável</td><td>Frequente percepção de exposição aos fatores psicossociais, com potencial de impacto relevante sobre a saúde e o desempenho.</td></tr>
        <tr><td>4,30 – 5,00</td><td class="num">5</td><td>Muito provável</td><td>Exposição intensa, recorrente e amplamente percebida pelos trabalhadores, indicando elevada probabilidade de ocorrência de danos à saúde.</td></tr>
    </tbody>
</table>
<p><em>*Critério de Materialidade da Exposição: categorias que apresentaram média inferior a 1,30 serão consideradas sem exposição significativa para fins de gerenciamento dos riscos psicossociais. Nesses casos, o fator permanecerá registrado no inventário para monitoramento periódico, não sendo submetido à matriz de risco nem demandando plano de ação específico, salvo existência de evidências qualitativas, ocorrências registradas ou outros elementos que justifiquem tratamento diferenciado.</em></p>

<h3>3.6 Critérios técnicos de Severidade (S) dos Riscos Psicossociais</h3>
<p>A Severidade representa a magnitude dos possíveis impactos decorrentes da exposição aos fatores de risco psicossociais identificados na avaliação. Sua definição considera os efeitos potenciais sobre saúde mental dos trabalhadores, funcionalidade ocupacional, relações socioprofissionais, desempenho laboral, estabilidade organizacional e segurança e bem-estar no ambiente de trabalho.</p>
<p>A determinação da severidade considera: intensidade do agravo potencial; possibilidade de reversibilidade; comprometimento funcional esperado; impacto organizacional; abrangência da exposição; potencial de agravamento do adoecimento relacionado ao trabalho.</p>
<p>A classificação foi estruturada com base nos princípios da NR-1, GRO/PGR, ISO 31010, literatura científica sobre saúde mental do trabalhador e demais referências técnicas aplicáveis à gestão dos riscos psicossociais.</p>
<table>
    <thead><tr><th style="width:5%">S</th><th style="width:13%">Classificação</th><th style="width:38%">Critério Técnico</th><th>Exemplos de Impactos Associados</th></tr></thead>
    <tbody>
        <tr><td class="num">1</td><td>Lesão leve</td><td>Impactos mínimos ou desprezíveis, sem repercussão significativa sobre a saúde ou desempenho ocupacional.</td><td>Desconforto pontual, pequenas insatisfações, necessidade ocasional de adaptação sem prejuízo funcional.</td></tr>
        <tr><td class="num">2</td><td>Lesão baixa</td><td>Impactos transitórios e reversíveis, com baixa repercussão sobre a saúde e o trabalho.</td><td>Irritabilidade ocasional, fadiga leve, estresse pontual, desconforto emocional passageiro.</td></tr>
        <tr><td class="num">3</td><td>Lesão moderada</td><td>Impactos capazes de gerar comprometimento funcional parcial ou recorrente, com repercussões perceptíveis sobre o desempenho e as relações de trabalho.</td><td>Sofrimento psíquico persistente, conflitos interpessoais frequentes, presenteísmo, aumento de erros operacionais, redução de produtividade.</td></tr>
        <tr><td class="num">4</td><td>Lesão alta</td><td>Impactos relevantes à saúde mental, com potencial de adoecimento relacionado ao trabalho e afastamentos temporários ou recorrentes.</td><td>Burnout, transtornos ansiosos ou depressivos relacionados ao trabalho, afastamentos prolongados, incapacidade funcional temporária importante.</td></tr>
        <tr><td class="num">5</td><td>Lesão extrema</td><td>Impactos críticos com potencial de incapacidade permanente, trauma severo ou morte relacionada ao trabalho.</td><td>Tentativa de suicídio, suicídio consumado, transtorno de estresse pós-traumático grave, violência extrema, incapacidade permanente decorrente de adoecimento mental relacionado ao trabalho.</td></tr>
    </tbody>
</table>

<h3>3.7 Classificação de Severidade por Categoria de Risco Psicossocial</h3>
<table>
    <thead><tr><th style="width:16%">Categoria</th><th style="width:5%">S</th><th style="width:14%">Classificação Técnica</th><th>Justificativa</th></tr></thead>
    <tbody>
    @foreach($categoriasReferenciaTodas as $fator)
        <tr>
            <td>{{ $fator->label() }}</td>
            <td class="num">{{ $fator->severidadePadrao() }}</td>
            <td>{{ ['', 'Danos Leves', 'Danos Leves', 'Danos Moderados', 'Danos Graves', 'Danos Gravíssimos'][$fator->severidadePadrao()] }}</td>
            <td>{{ $fator->descricaoTecnica() }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h3>3.8 Classificação dos Níveis de Risco</h3>
<table>
    <thead><tr><th style="width:8%">Farol</th><th style="width:16%">Classificação</th><th>Diretrizes de Gerenciamento</th></tr></thead>
    <tbody>
    @foreach($niveisRiscoTodos as $nivel)
        <tr>
            <td class="farol"><span class="dot" style="background:{{ $nivel->farolCor() }}"></span></td>
            <td>{{ $nivel->label() }}</td>
            <td>{{ $nivel->diretriz() }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h3>3.9 Integração com o GRO/PGR</h3>
<p>A presente metodologia foi estruturada para integração ao Programa de Gerenciamento de Riscos (PGR) da organização, permitindo que os riscos psicossociais sejam tratados de forma compatível com os demais riscos ocupacionais previstos no GRO.</p>
<p>A metodologia contempla: identificação dos fatores psicossociais; avaliação da exposição; classificação da criticidade; definição de medidas preventivas e corretivas; monitoramento contínuo dos indicadores organizacionais relacionados à saúde mental e ao contexto ocupacional.</p>
<p>Recomenda-se que a organização realize monitoramento periódico dos fatores psicossociais, incluindo: reaplicação periódica do instrumento; acompanhamento de absenteísmo; análise de afastamentos relacionados a CID F; turnover; indicadores de clima e saúde ocupacional.</p>

<h3>3.10 Limitações Metodológicas</h3>
<p>Os resultados apresentados refletem a percepção dos trabalhadores no período de aplicação dos instrumentos, considerando o contexto organizacional existente durante a avaliação.</p>
<p>A metodologia não possui finalidade diagnóstica individual ou clínica, não substituindo avaliação médica, psicológica ou psiquiátrica individualizada.</p>
<p>Os resultados devem ser interpretados de forma coletiva, organizacional e ocupacional, sendo utilizados como subsídio técnico para gerenciamento dos riscos psicossociais no contexto do GRO/PGR.</p>

<h2>4. Composição dos Grupos Homogêneos de Exposição</h2>
@if($composicaoGhe->isEmpty())
    <p>Nenhum GHE cadastrado para esta empresa.</p>
@else
<table>
    <thead><tr><th style="width:20%">Empresa</th><th style="width:30%">GHE</th><th style="width:35%">Setores agrupados</th><th class="num" style="width:15%">Quantidade de pessoas</th></tr></thead>
    <tbody>
    @foreach($composicaoGhe as $ghe)
        <tr><td>{{ $empresa->nome }}</td><td>{{ $ghe['nome'] }}</td><td>{{ $ghe['setores'] }}</td><td class="num">{{ $ghe['total'] }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<h2>5. Resultados da Avaliação dos Riscos Psicossociais</h2>
<table>
    <colgroup><col style="width:20%"><col style="width:80%"></colgroup>
    <thead><tr><th colspan="2">Legenda da tabela de riscos psicossociais</th></tr></thead>
    <tbody>
        <tr><td><strong>Categoria</strong></td><td>Fator de risco (categoria)</td></tr>
        <tr><td><strong>Média</strong></td><td>Média do COPSOQ</td></tr>
        <tr><td><strong>P</strong></td><td>Probabilidade</td></tr>
        <tr><td><strong>G</strong></td><td>Severidade</td></tr>
        <tr><td><strong>Risco</strong></td><td>Probabilidade x Severidade</td></tr>
        <tr><td><strong>Nível</strong></td><td>Classificação do nível de risco</td></tr>
        <tr><td><strong>Farol</strong></td><td>Sinalização visual do nível de risco</td></tr>
    </tbody>
</table>

@php
    $temRisco = collect($resultado['categorias'])->contains(fn ($c) => $c['severidade'] && !empty($c['grupos_ghe']));
@endphp

@php
    $porGrupo = [];
    foreach ($resultado['categorias'] as $categoria) {
        if (!$categoria['severidade']) continue;
        foreach (($categoria['grupos_ghe'] ?: [['nome' => '—', 'total_respostas' => 0, 'media' => null, 'risco' => null]]) as $grupo) {
            $porGrupo[$grupo['nome']][] = ['categoria' => $categoria, 'grupo' => $grupo];
        }
    }
@endphp

@forelse($porGrupo as $nomeGrupo => $linhas)
    <h3>{{ $nomeGrupo }}</h3>
    <table>
        <colgroup>
            <col style="width:30%"><col style="width:9%"><col style="width:6%"><col style="width:6%">
            <col style="width:15%"><col style="width:24%"><col style="width:10%">
        </colgroup>
        <thead><tr><th>Categoria</th><th class="num">Média</th><th class="num">P</th><th class="num">S</th><th>Interseção Matriz</th><th>Nível</th><th class="farol">Farol</th></tr></thead>
        <tbody>
        @foreach($linhas as $linha)
            <tr>
                <td>{{ $linha['categoria']['nome'] }}</td>
                <td class="num">{{ $linha['grupo']['media'] ?? '—' }}</td>
                <td class="num">{{ $linha['grupo']['risco']['probabilidade'] ?? '—' }}</td>
                <td class="num">{{ $linha['grupo']['risco']['severidade'] ?? $linha['categoria']['severidade'] }}</td>
                <td>P{{ $linha['grupo']['risco']['probabilidade'] ?? '—' }} x S{{ $linha['grupo']['risco']['severidade'] ?? $linha['categoria']['severidade'] }}</td>
                <td>{{ $linha['grupo']['risco']['nivel']?->rotuloRelatorio() ?? '—' }}</td>
                <td class="farol">
                    @if($linha['grupo']['risco']['nivel'] ?? null)
                        <span class="dot" style="background:{{ $linha['grupo']['risco']['farol_cor'] }}"></span>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@empty
    <p>Nenhuma categoria com fator de risco oficial associado nesta campanha — sem classificação de risco a exibir.</p>
@endforelse

@if($temRisco)
<p><strong>Observação importante:</strong> categorias classificadas como Críticas ou Não Toleráveis pela matriz corporativa em função da elevada severidade potencial dos danos associados podem, ainda assim, apresentar média de exposição baixa — o que indica baixa frequência percebida pelos trabalhadores avaliados, e não necessariamente ocorrência disseminada do fator. A leitura conjunta de Probabilidade e Severidade, e não apenas do farol final, é recomendada antes de qualquer decisão. Verificar: Anexo III - Nota técnica - CRITÉRIOS PARA ANÁLISE DOS RISCOS DE MORTE E TRAUMA NO TRABALHO.</p>
@endif

<h2>6. Plano de Ação</h2>
@if($planoAcao->isEmpty())
    <p>Plano de ação ainda não gerado para esta campanha.</p>
@else
<table>
    <colgroup>
        <col style="width:8%"><col style="width:11%"><col style="width:8%"><col style="width:8%"><col style="width:5%">
        <col style="width:11%"><col style="width:27%"><col style="width:12%"><col style="width:10%">
    </colgroup>
    <thead><tr><th>Empresa</th><th>Categoria</th><th>Nível Relatório</th><th>Nível Base</th><th class="farol">Farol</th><th>GHE</th><th>Ação</th><th>Responsável</th><th>Prazo</th></tr></thead>
    <tbody>
    @foreach($planoAcao as $categoriaNome => $acoes)
        @foreach($acoes as $acao)
            <tr>
                <td>{{ $empresa->nome }}</td>
                <td>{{ $categoriaNome }}</td>
                <td>{{ $acao->nivel_risco->rotuloRelatorio() }}</td>
                <td>{{ $acao->nivel_risco->nivelBaseAcao()?->value }}</td>
                <td class="farol"><span class="dot" style="background:{{ $acao->nivel_risco->farolCor() }}"></span></td>
                <td>{{ $acao->ghe->nome ?? 'Grupo agregado' }}</td>
                <td>{{ $acao->acao }}</td>
                <td>{{ $acao->responsavel }}</td>
                <td>{{ $acao->prazo }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
@endif

<div class="assinatura">
    <div class="linha">
        {{ $responsavel['nome'] ?? '________________________________' }}
        @if(!empty($responsavel['registro']))
            <br>{{ $responsavel['registro'] }}
        @endif
    </div>
</div>

<div style="page-break-before: always;"></div>
<h2>Anexo I – Fatores de risco psicossociais previstos na Portaria GM/MS nº 5.674/2024, suas descrições e respectivos agravos à saúde</h2>
<table>
    <colgroup><col style="width:14%"><col style="width:43%"><col style="width:43%"></colgroup>
    <thead><tr><th>Fator</th><th>Descrição</th><th>Possíveis Doenças Relacionadas ao Trabalho (CID)</th></tr></thead>
    <tbody>
    @forelse($anexoI as $fator)
        <tr>
            <td>{{ $fator->label() }}</td>
            <td>{{ $fator->descricaoTecnica() }}</td>
            <td>{{ implode('; ', $fator->doencasRelacionadas()) }}</td>
        </tr>
    @empty
        <tr><td colspan="3">Nenhuma categoria desta campanha está vinculada a um fator de risco oficial.</td></tr>
    @endforelse
    </tbody>
</table>

@if($anexoII->isNotEmpty())
<div style="page-break-before: always;"></div>
<h2>Anexo II – Relação entre as perguntas aplicadas e os fatores de risco psicossociais previstos na Portaria GM/MS nº 5.674/2024</h2>
@foreach($anexoII as $categoriaLabel => $perguntas)
    <h3>{{ $categoriaLabel }}</h3>
    <table>
        <thead><tr><th style="width:6%">Nº</th><th style="width:47%">Pergunta original</th><th>Adaptação para o contexto brasileiro</th></tr></thead>
        <tbody>
        @foreach($perguntas as $p)
            <tr>
                <td class="num">{{ $p['numero'] ?? '—' }}</td>
                <td>{{ $p['original'] ? '"'.$p['original'].'"' : '—' }}</td>
                <td>{{ $p['adaptacao'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach
@endif

<div style="page-break-before: always;"></div>
<h2>Anexo III - Nota técnica - Critérios para Análise dos Riscos de Morte e Trauma no Trabalho</h2>
<p>A categoria "Risco de Morte e Trauma no Trabalho" deve ser interpretada considerando os princípios da NR-1, especialmente no que se refere à identificação de perigos ocupacionais e à gestão de riscos relacionados ao trabalho.</p>
<p>Nos termos da legislação vigente, a avaliação deve priorizar:</p>
<ul>
    <li>Riscos internos decorrentes das atividades, processos, equipamentos, condições de trabalho ou organização do trabalho sob controle ou influência direta da organização;</li>
    <li>Perigos externos previsíveis relacionados ao trabalho, conforme previsto no item 1.5.4.3.2 da NR-1, caracterizados por situações externas ao estabelecimento ou local de trabalho, mas que possam afetar a saúde e segurança dos trabalhadores de forma razoavelmente previsível.</li>
</ul>
<p>Dessa forma, a simples existência de eventos potencialmente traumáticos na sociedade, na cidade ou na região onde a organização está inserida não implica, por si só, a caracterização de risco ocupacional sob responsabilidade da empresa.</p>
<p>Eventos externos cuja origem decorra predominantemente de fatores de segurança pública, criminalidade geral, instabilidade social, desastres naturais imprevisíveis ou outras condições alheias à atividade laboral somente devem ser considerados no processo de gestão de riscos quando houver nexo com o trabalho, previsibilidade de ocorrência e possibilidade de adoção de medidas mitigadoras razoáveis pela organização.</p>
<p>Assim, situações de violência urbana generalizada, criminalidade difusa ou eventos fortuitos sem relação direta com a atividade desempenhada não devem ser interpretadas como riscos ocupacionais controláveis pela empresa, mas como fatores externos cujo gerenciamento compete, prioritariamente, aos órgãos públicos responsáveis.</p>
<p>Nesses casos, cabe à organização adotar medidas preventivas compatíveis com sua esfera de atuação, tais como:</p>
<ul>
    <li>Monitoramento de ocorrências relevantes relacionadas às atividades laborais;</li>
    <li>Orientação e treinamento dos trabalhadores;</li>
    <li>Procedimentos de segurança;</li>
    <li>Canais de comunicação e registro de incidentes;</li>
    <li>Medidas de proteção patrimonial e pessoal compatíveis com a realidade operacional;</li>
    <li>Revisão periódica das condições de exposição identificadas.</li>
</ul>
<p>Portanto, para fins de enquadramento na categoria "Risco de Morte e Trauma no Trabalho", recomenda-se que sejam considerados prioritariamente os riscos efetivamente relacionados ao contexto ocupacional, às atividades desempenhadas e aos perigos internos ou externos previsíveis associados ao trabalho, evitando-se a atribuição à organização de eventos cuja origem, controle e responsabilidade estejam fora de sua esfera de governabilidade.</p>

<p class="rodape-nota">Relatório gerado automaticamente pelo sistema em {{ $geradoEm->format('d/m/Y H:i') }}, a partir dos dados reais da campanha "{{ $pesquisa->nome }}". Este documento não possui finalidade diagnóstica individual, clínica ou psiquiátrica.</p>

</body>
</html>
