<?php

namespace App\Exceptions\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Lançada quando um token de ativação/recuperação não existe, já foi
 * consumido ou expirou. Implementa `render()` próprio (suportado
 * nativamente pelo handler de exceções do Laravel) para que os Controllers
 * não precisem de try/catch — mantém os Services livres de qualquer
 * conhecimento sobre HTTP, e os Controllers finos.
 */
class TokenInvalidoOuExpiradoException extends RuntimeException
{
    public function __construct(string $mensagem = 'Este link não é mais válido. Solicite um novo.')
    {
        parent::__construct($mensagem);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'sucesso'  => false,
            'mensagem' => $this->getMessage(),
        ], 422);
    }
}
