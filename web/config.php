<?php
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_NAME',     'dashboard_admin');
define('DB_CHARSET',  'utf8mb4');

define('SITE_NAME',   'AdminPanel');
define('SITE_URL',    'http://localhost/SistemaPesquisas/web');
define('VERSION',     '1.0.0');



function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
        }
    }
    return $pdo;
}

function query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function queryOne(string $sql, array $params = []): array|false {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function execute(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) db()->lastInsertId() ?: $stmt->rowCount();
}

function formatMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatData(string $data, string $formato = 'd/m/Y H:i'): string {
    return date($formato, strtotime($data));
}

function statusBadge(string $status): string {
    $map = [
        'ativo'       => 'success',
        'entregue'    => 'success',
        'inativo'     => 'secondary',
        'cancelado'   => 'danger',
        'bloqueado'   => 'danger',
        'pendente'    => 'warning',
        'processando' => 'info',
        'enviado'     => 'primary',
        'esgotado'    => 'danger',
    ];
    $cls = $map[$status] ?? 'secondary';
    return "<span class=\"badge badge-{$cls}\">{$status}</span>";
}
