<?php

/**
 * A API (este projeto Laravel) e o painel webAdm são duas aplicações PHP
 * independentes servidas em pastas irmãs (public/ e webAdm/). Os e-mails
 * transacionais (ativação de conta, recuperação de senha) precisam montar
 * links que abrem o webAdm, não a própria API — por isso a URL do frontend
 * é uma configuração própria, e não uma derivação de APP_URL.
 */
return [
    'url' => rtrim(env('FRONTEND_URL', 'http://localhost/SistemaPesquisas/webAdm'), '/'),
];
