<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\SecurityConfig;

final class PasswordPolicyService
{
    /**
     * @return array<string, string>
     */
    public function validate(string $password, string $passwordConfirm): array
    {
        $errors = [];
        $minLength = SecurityConfig::minPasswordLength();

        if (strlen($password) < $minLength)
        {
            $errors['password'] = 'A senha deve ter no mínimo ' . $minLength . ' caracteres.';
        }

        if ($password !== $passwordConfirm)
        {
            $errors['password_confirm'] = 'As senhas não conferem.';
        }

        if ($errors !== [] || !SecurityConfig::requirePasswordComplexity())
        {
            return $errors;
        }

        if (!preg_match('/[a-z]/', $password))
        {
            $errors['password'] = 'A senha deve conter ao menos uma letra minúscula.';
        }
        elseif (!preg_match('/[A-Z]/', $password))
        {
            $errors['password'] = 'A senha deve conter ao menos uma letra maiúscula.';
        }
        elseif (!preg_match('/\d/', $password))
        {
            $errors['password'] = 'A senha deve conter ao menos um número.';
        }
        elseif (!preg_match('/[^a-zA-Z0-9]/', $password))
        {
            $errors['password'] = 'A senha deve conter ao menos um caractere especial.';
        }

        return $errors;
    }

    public function requirementsHint(): string
    {
        $min = SecurityConfig::minPasswordLength();
        if (!SecurityConfig::requirePasswordComplexity())
        {
            return 'Mínimo de ' . $min . ' caracteres.';
        }

        return 'Mínimo de ' . $min . ' caracteres, com maiúscula, minúscula, número e caractere especial.';
    }
}
