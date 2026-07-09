<?php
/**
 * ApiClient — cliente HTTP para consumir a API REST do SistemaPesquisas.
 * Usa cURL. Requer Config.php carregado antes.
 */
class ApiClient
{
    private string  $baseUrl;
    private ?string $token;
    private ?int    $empresaId;
    private int     $timeout = 15;

    public function __construct(?string $token = null, ?int $empresaId = null)
    {
        $this->baseUrl   = API_BASE_URL;
        $this->token     = $token;
        $this->empresaId = $empresaId;
    }

    // ── Métodos públicos ─────────────────────────────────────────────────────

    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $this->baseUrl . $endpoint, $data);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $this->baseUrl . $endpoint, $data);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $this->baseUrl . $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $this->baseUrl . $endpoint);
    }

    // ── Implementação ────────────────────────────────────────────────────────

    private function request(string $method, string $url, array $data = []): array
    {
        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        if ($this->empresaId) {
            $headers[] = 'X-Empresa-Id: ' . $this->empresaId;
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method !== 'GET') {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            if ($data) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $opts);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'status'  => 0,
                'data'    => null,
                'error'   => $error,
            ];
        }

        // Token expirado ou inválido — redireciona para logout automático
        if ($httpCode === 401 && !headers_sent()) {
            header('Location: ' . WEBADM_URL . 'logout.php?expired=1');
            exit;
        }

        $decoded = json_decode($raw, true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status'  => $httpCode,
            'data'    => $decoded,
        ];
    }
}
