<?php

namespace App\Modules\Pesquisa\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class PesquisaFormRequest extends FormRequest
{
    private ?User $resolvedAuthUser = null;

    /**
     * Autorização de rota já é feita pelo middleware `permission:<slug>`
     * (ver routes/api.php do módulo), então toda request é autorizada aqui.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Laravel resolve um FormRequest como uma NOVA instância (via
     * FormRequest::createFrom()), que não herda propriedades dinâmicas
     * (como `auth_user`) setadas pelo AuthMiddleware no objeto Request
     * original — apenas os bags nativos (query/attributes/etc) são copiados.
     * Resolvemos o usuário autenticado novamente aqui, sem depender disso.
     */
    public function authUser(): User
    {
        return $this->resolvedAuthUser ??= JWTAuth::parseToken()->authenticate();
    }
}
