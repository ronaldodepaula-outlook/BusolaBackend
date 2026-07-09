<?php
/**
 * Exportação CSV dos convites de uma campanha — standalone porque precisa
 * enviar headers de download (Content-Type/Content-Disposition), o que não
 * é possível a partir de paginas/*.php (o HTML do painel já começou a ser
 * enviado antes desse ponto).
 */
require_once __DIR__ . '/classe/Config.php';
require_once __DIR__ . '/classe/ApiClient.php';
require_once __DIR__ . '/classe/Auth.php';

Auth::exigirAuth();

if (!Auth::hasPermission('pesquisa.visualizar')) {
    http_response_code(403);
    echo 'Acesso não autorizado.';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo 'Campanha inválida.';
    exit;
}

$api = new ApiClient(Auth::getToken());
$resp = $api->get("pesquisa-psicossocial/pesquisas/{$id}/convites");
$convites = $resp['data']['dados'] ?? [];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="convites-pesquisa-' . $id . '.csv"');

echo "\xEF\xBB\xBF"; // BOM UTF-8, para o Excel abrir acentuação corretamente

$out = fopen('php://output', 'w');
fputcsv($out, ['nome', 'email', 'status', 'link']);

foreach ($convites as $c) {
    $link = WEBADM_URL . 'responder.php?token=' . $c['token'];
    fputcsv($out, [
        $c['nome'],
        $c['email'],
        $c['respondido'] ? 'respondido' : 'pendente',
        $link,
    ]);
}

fclose($out);
exit;
