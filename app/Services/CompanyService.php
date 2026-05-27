<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;
use App\Models\Company;
use App\Repositories\CompanyRepository;

final class CompanyService
{
    private CompanyRepository $companies;

    public function __construct(?CompanyRepository $companies = null)
    {
        $this->companies = $companies ?? new CompanyRepository();
    }

    /**
     * @return array{items: list<Company>, total: int}
     */
    public function list(int $page, int $perPage, ?string $search, ?string $status): array
    {
        $activeOnly = match ($status)
        {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        return $this->companies->paginate($page, $perPage, $search, $activeOnly);
    }

    public function find(int $id): ?Company
    {
        return $this->companies->findById($id, false);
    }

    public function create(string $name, ?string $taxId, string $slug): int
    {
        $v = $this->validate($name, $taxId, $slug, null);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        return Database::transaction(function (\PDO $pdo) use ($v): int
        {
            $id = $this->companies->insert($v['name'], $v['tax_id'], $v['slug']);
            Audit::record('criar', 'usuarios', $id, null, ['name' => $v['name'], 'slug' => $v['slug']]);

            return $id;
        });
    }

    public function update(int $id, string $name, ?string $taxId, string $slug): void
    {
        $existing = $this->companies->findById($id, false);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Empresa não encontrada.']);
        }

        $v = $this->validate($name, $taxId, $slug, $id);
        if ($v['errors'] !== [])
        {
            throw new ValidationException($v['errors']);
        }

        $this->companies->update($id, $v['name'], $v['tax_id'], $v['slug']);
        Audit::record('editar', 'usuarios', $id, ['name' => $existing->name], ['name' => $v['name']]);
    }

    public function setActive(int $id, bool $active): void
    {
        $existing = $this->companies->findById($id, false);
        if ($existing === null)
        {
            throw new ValidationException(['id' => 'Empresa não encontrada.']);
        }

        $this->companies->setActive($id, $active);
        Audit::record(
            $active ? 'ativar' : 'desativar',
            'usuarios',
            $id,
            ['active' => $existing->active],
            ['active' => $active]
        );
    }

    /**
     * @return array{errors: array<string, string>, name: string, tax_id: ?string, slug: string}
     */
    private function validate(string $name, ?string $taxId, string $slug, ?int $excludeId): array
    {
        $nameResult = Validator::requiredString($name, 'name', 'Nome da empresa é obrigatório.');
        $errors = $nameResult['errors'];
        $name = $nameResult['value'];

        $slug = $this->normalizeSlug($slug !== '' ? $slug : $name);
        if ($slug === '')
        {
            $errors['slug'] = 'Identificador (slug) inválido.';
        }
        elseif ($this->companies->slugExists($slug, $excludeId))
        {
            $errors['slug'] = 'Este identificador já está em uso.';
        }

        $taxId = $taxId !== null && trim($taxId) !== ''
            ? InputSanitizer::string($taxId, 20)
            : null;

        if ($taxId !== null && $this->companies->taxIdExists($taxId, $excludeId))
        {
            $errors['tax_id'] = 'Documento já cadastrado para outra empresa.';
        }

        return ['errors' => $errors, 'name' => $name, 'tax_id' => $taxId, 'slug' => $slug];
    }

    private function normalizeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return substr($slug, 0, 80);
    }
}
