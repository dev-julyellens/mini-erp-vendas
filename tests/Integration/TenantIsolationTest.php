<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Helpers\CompanyContext;
use App\Repositories\ProductRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequiresPostgresTrait;

final class TenantIsolationTest extends TestCase
{
    use RequiresPostgresTrait;

    private const COMPANY_A = 1;

    private ?int $companyBId = null;

    private ?int $productBId = null;

    protected function setUp(): void
    {
        $this->requirePostgres();
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();

        if ($this->productBId !== null)
        {
            $pdo->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $this->productBId]);
        }

        if ($this->companyBId !== null)
        {
            $pdo->prepare('DELETE FROM companies WHERE id = :id')->execute(['id' => $this->companyBId]);
        }

        $this->resetTestContext();
        parent::tearDown();
    }

    public function testProductFromOtherCompanyIsNotVisible(): void
    {
        $pdo = Database::getConnection();
        $slug = 'test-tenant-' . bin2hex(random_bytes(4));

        $stmt = $pdo->prepare(
            'INSERT INTO companies (name, slug, onboarding_step, onboarding_completed_at, active)
             VALUES (:name, :slug, \'completed\', CURRENT_TIMESTAMP, TRUE)
             RETURNING id'
        );
        $stmt->execute([
            'name' => 'Empresa Teste Isolamento',
            'slug' => $slug,
        ]);
        $this->companyBId = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "INSERT INTO products (
                company_id, name, sku, unit_of_measure, cost_price, price, stock, min_stock, type
             ) VALUES (
                :company_id, 'Produto Empresa B', :sku, 'UN', 10.00, 19.90, 5, 1, 'product'
             ) RETURNING id"
        );
        $stmt->execute([
            'company_id' => $this->companyBId,
            'sku' => 'SKU-TENANT-' . bin2hex(random_bytes(3)),
        ]);
        $this->productBId = (int) $stmt->fetchColumn();

        CompanyContext::setJwtCompanyId(self::COMPANY_A);
        $repo = new ProductRepository($pdo);

        $this->assertNull($repo->findById($this->productBId));

        CompanyContext::setJwtCompanyId($this->companyBId);
        $found = $repo->findById($this->productBId);
        $this->assertNotNull($found);
        $this->assertSame('Produto Empresa B', $found->name);
    }
}
