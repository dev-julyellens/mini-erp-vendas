<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Repositories\CategoryRepository;

final class CategoryService
{
    private CategoryRepository $categories;

    public function __construct(?CategoryRepository $categories = null)
    {
        $this->categories = $categories ?? new CategoryRepository();
    }

    /**
     * @return array{errors: array<string, string>, name: string, description: ?string}
     */
    private function validate(string $name, ?string $description, ?int $excludeId = null): array
    {
        $errors = [];
        $name = trim($name);
        if ($name === '')
        {
            $errors['name'] = 'Nome da categoria é obrigatório.';
        }
        elseif (mb_strlen($name) > 120)
        {
            $errors['name'] = 'Nome deve ter no máximo 120 caracteres.';
        }

        $description = $description !== null ? trim($description) : null;
        if ($description === '')
        {
            $description = null;
        }

        if ($name !== '' && $errors === [])
        {
            $existing = $this->categories->findByName($name);
            if ($existing !== null && ($excludeId === null || $existing->id !== $excludeId))
            {
                $errors['name'] = 'Já existe uma categoria com este nome.';
            }
        }

        return [
            'errors' => $errors,
            'name' => $name,
            'description' => $description,
        ];
    }

    public function create(string $name, ?string $description): int
    {
        $v = $this->validate($name, $description);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        return $this->categories->insert($v['name'], $v['description']);
    }

    public function update(int $id, string $name, ?string $description): void
    {
        if ($this->categories->findById($id) === null)
        {
            throw new ValidationException(['id' => 'Categoria não encontrada.']);
        }

        $v = $this->validate($name, $description, $id);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $this->categories->update($id, $v['name'], $v['description']);
    }

    public function delete(int $id): void
    {
        if ($this->categories->findById($id) === null)
        {
            throw new ValidationException(['id' => 'Categoria não encontrada.']);
        }

        if ($this->categories->countProductsLinked($id) > 0)
        {
            throw new ValidationException([
                'id' => 'Não é possível excluir categoria vinculada a produtos.',
            ]);
        }

        $this->categories->delete($id);
    }
}
