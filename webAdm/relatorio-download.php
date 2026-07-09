<?php
/**
 * Download do PDF de um Relatório Técnico — standalone porque precisa
 * repassar bytes binários (application/pdf), o que o ApiClient não suporta
 * (ele sempre faz json_decode da resposta) e o que também não é possível a
 * partir de paginas/*.php (o HTML do painel já começou a ser enviado antes
 * desse ponto).
 */
require_once __DIR__ . '/classe/Config.php';
require_once __DIR__ . '/classe/Auth.php';

Auth::exigirAuth();

if (!Auth::hasPermission('relatorio.listar') && !Auth::hasPermission('relatorio.listar_todas')) {
    http_response_code(403);
    echo 'Acesso não autorizado.';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo 'Relatório inválido.';
    exit;
}

$url = API_BASE_URL . "pesquisa-psicossocial/relatorios-tecnicos/{$id}/download";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/pdf',
        'Authorization: Bearer ' . Auth::getToken(),
    ],
    CURLOPT_SSL_VERIFYPEER => false,
]);

$conteudo = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erro     = curl_error($ch);
curl_close($ch);

if ($erro || $httpCode !== 200) {
    http_response_code($httpCode ?: 502);
    echo 'Não foi possível baixar o relatório.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="relatorio-tecnico-' . $id . '.pdf"');
header('Content-Length: ' . strlen($conteudo));
echo $conteudo;
exit;
