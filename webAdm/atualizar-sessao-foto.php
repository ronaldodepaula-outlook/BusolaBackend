<?php
require_once 'classe/Config.php';
require_once 'classe/ApiClient.php';
require_once 'classe/Auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::estaLogado()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada.']);
    exit;
}

// Reconsulta o perfil na API (não confia no valor enviado pelo cliente) só
// para manter a sessão local do webAdm em sincronia após um upload de foto.
$api  = new ApiClient(Auth::getToken());
$resp = $api->get('perfil');
$user = $resp['data']['dados'] ?? null;

if (!$user) {
    http_response_code(502);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível atualizar a sessão.']);
    exit;
}

Auth::iniciar();
$_SESSION['user']['foto']     = $user['foto'] ?? null;
$_SESSION['user']['foto_url'] = $user['foto_url'] ?? null;

echo json_encode(['sucesso' => true, 'foto_url' => $_SESSION['user']['foto_url']]);
