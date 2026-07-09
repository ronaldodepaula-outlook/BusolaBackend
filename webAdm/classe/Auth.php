<?php
/**
 * Auth — gerencia sessão, login e logout via API REST.
 */
class Auth
{
    // ── Sessão ───────────────────────────────────────────────────────────────

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SP_ADM');
            session_start();
        }
    }

    // ── Verificações ─────────────────────────────────────────────────────────

    public static function estaLogado(): bool
    {
        self::iniciar();
        return !empty($_SESSION['auth_token']) && !empty($_SESSION['user']);
    }

    /** Redireciona para login se não autenticado. */
    public static function exigirAuth(): void
    {
        if (!self::estaLogado()) {
            header('Location: login.php');
            exit;
        }
    }

    // ── Login ────────────────────────────────────────────────────────────────

    public static function login(string $email, string $senha): array
    {
        require_once __DIR__ . '/Config.php';
        require_once __DIR__ . '/ApiClient.php';

        $api    = new ApiClient();
        $result = $api->post('auth/login', ['email' => $email, 'senha' => $senha]);

        if ($result['success'] && isset($result['data']['dados']['token'])) {
            self::iniciar();
            $usuario = $result['data']['dados']['usuario'];
            $usuario['permissoes'] = self::achatarPermissoes($usuario['roles'] ?? []);

            $_SESSION['auth_token'] = $result['data']['dados']['token'];
            $_SESSION['user']       = $usuario;
            return ['sucesso' => true];
        }

        $mensagem = $result['data']['mensagem']
            ?? ($result['status'] === 0 ? 'Não foi possível conectar à API.' : 'Credenciais inválidas.');

        return ['sucesso' => false, 'mensagem' => $mensagem];
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    /** Logout normal: notifica a API e destrói a sessão. */
    public static function logout(): void
    {
        self::iniciar();

        if (!empty($_SESSION['auth_token'])) {
            require_once __DIR__ . '/Config.php';
            require_once __DIR__ . '/ApiClient.php';
            $api = new ApiClient($_SESSION['auth_token']);
            @$api->post('auth/logout', []);
        }

        $_SESSION = [];
        session_destroy();
    }

    /** Encerra sessão local sem chamar a API (usado quando o token já expirou). */
    public static function encerrarSessao(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }

    // ── Getters ──────────────────────────────────────────────────────────────

    public static function getUser(): ?array
    {
        self::iniciar();
        return $_SESSION['user'] ?? null;
    }

    public static function getToken(): ?string
    {
        self::iniciar();
        return $_SESSION['auth_token'] ?? null;
    }

    public static function getNome(): string
    {
        return self::getUser()['nome'] ?? 'Usuário';
    }

    public static function getEmail(): string
    {
        return self::getUser()['email'] ?? '';
    }

    public static function getTipo(): string
    {
        return self::getUser()['tipo'] ?? '';
    }

    public static function getFoto(): ?string
    {
        return self::getUser()['foto'] ?? null;
    }

    public static function getEmpresaId(): ?int
    {
        $id = self::getUser()['empresa_id'] ?? null;
        return $id ? (int) $id : null;
    }

    public static function getTipoLabel(): string
    {
        $labels = [
            'superadmin' => 'Super Admin',
            'admin'      => 'Administrador',
            'gerente'    => 'Gerente',
            'usuario'    => 'Usuário',
        ];
        return $labels[self::getTipo()] ?? self::getTipo();
    }

    public static function isSuperAdmin(): bool
    {
        return self::getTipo() === 'superadmin';
    }

    // ── Permissões ───────────────────────────────────────────────────────────

    /** Achata roles[].permissions[].slug em uma lista simples de slugs (sem duplicatas). */
    private static function achatarPermissoes(array $roles): array
    {
        $slugs = [];
        foreach ($roles as $role) {
            foreach ($role['permissions'] ?? [] as $permissao) {
                if (!empty($permissao['slug'])) {
                    $slugs[$permissao['slug']] = true;
                }
            }
        }
        return array_keys($slugs);
    }

    /**
     * Verifica se o usuário logado possui a permissão informada.
     * Superadmin sempre tem acesso irrestrito.
     */
    public static function hasPermission(string $slug): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }

        self::iniciar();

        if (!isset($_SESSION['user']['permissoes'])) {
            // Sessões iniciadas antes desta funcionalidade existir: busca uma vez e cacheia.
            require_once __DIR__ . '/Config.php';
            require_once __DIR__ . '/ApiClient.php';
            $api  = new ApiClient(self::getToken());
            $resp = $api->get('perfil/permissoes');
            $_SESSION['user']['permissoes'] = $resp['data']['dados']['slugs'] ?? [];
        }

        return in_array($slug, $_SESSION['user']['permissoes'], true);
    }
}
