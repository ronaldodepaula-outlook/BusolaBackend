<?php

namespace App\Http\Requests\Auth;

use App\Support\PoliticaSenha;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação compartilhada pelos dois pontos do sistema em que o próprio
 * usuário define uma senha a partir de um token recebido por e-mail:
 * conclusão da ativação de conta (Fluxo 1) e redefinição por recuperação
 * (Fluxo 2). A forma dos dados é idêntica nos dois casos — o que muda é
 * qual Service/Controller consome o token, não como ele é validado.
 */
class DefinirSenhaComTokenRequest extends FormRequest
{
    /**
     * Endpoint público (sem usuário autenticado) — a autorização real é a
     * posse do token válido, verificada pelo Service, não por este método.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'             => ['required', 'string'],
            'senha'             => ['required', 'string', PoliticaSenha::regras()],
            'confirmacao_senha' => ['required', 'same:senha'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'             => 'Link inválido.',
            'senha.required'             => 'Informe a nova senha.',
            'confirmacao_senha.required' => 'Confirme a nova senha.',
            'confirmacao_senha.same'     => 'A confirmação não coincide com a nova senha.',
        ];
    }
}
