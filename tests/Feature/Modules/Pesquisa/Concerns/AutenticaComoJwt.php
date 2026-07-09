<?php

namespace Tests\Feature\Modules\Pesquisa\Concerns;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AutenticaComoJwt
{
    protected function headersParaUsuario(User $user): array
    {
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => "Bearer {$token}"];
    }
}
