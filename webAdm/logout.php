<?php
require_once 'classe/Config.php';
require_once 'classe/ApiClient.php';
require_once 'classe/Auth.php';

if (isset($_GET['expired'])) {
    // Token já expirado — destrói apenas a sessão local sem chamar a API
    Auth::encerrarSessao();
    header('Location: login.php?expired=1');
    exit;
}

// Logout normal: notifica a API e destrói a sessão
Auth::logout();
header('Location: login.php');
exit;
