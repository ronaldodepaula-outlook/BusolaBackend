<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Classe auxiliar (helper) com a política de senha forte aplicada a todo
 * fluxo em que o próprio usuário define/redefine a senha (ativação de conta
 * e recuperação de senha). Centralizar aqui evita que a regra diverja entre
 * os dois FormRequests que a usam e dá um único ponto de ajuste caso a
 * política precise mudar no futuro.
 *
 * Regras exigidas: mínimo de 8 caracteres, letras maiúsculas e minúsculas,
 * ao menos um número e ao menos um símbolo.
 *
 * Deliberadamente NÃO usa `->uncompromised()` (checagem contra a base
 * pública "Have I Been Pwned"): essa regra depende de uma chamada HTTP
 * externa a cada validação, com timeout padrão de 30s — inaceitável como
 * dependência dura de um fluxo de autenticação, e tornaria os testes de
 * integração lentos/instáveis num ambiente sem acesso garantido à internet.
 */
final class PoliticaSenha
{
    public static function regras(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
