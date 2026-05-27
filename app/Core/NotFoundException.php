<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Recurso inexistente; mapeada para erros de validação em formulários e API.
 */
final class NotFoundException extends ValidationException
{
    public function __construct(string $entity, string $field = 'id')
    {
        parent::__construct(
            [$field => sprintf('%s não encontrado(a).', $entity)],
            sprintf('%s não encontrado(a).', $entity)
        );
    }
}
