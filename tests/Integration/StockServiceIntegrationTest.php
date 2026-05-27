<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use App\Services\StockService;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequiresPostgresTrait;

final class StockServiceIntegrationTest extends TestCase
{
    use RequiresPostgresTrait;

    private const COMPANY_ID = 1;

    private ?int $productIdToRestore = null;

    private ?int $stockBefore = null;

    /** @var list<int> */
    private array $movementIdsToCleanup = [];

    protected function setUp(): void
    {
        $this->requirePostgres();
        CompanyContext::setJwtCompanyId(self::COMPANY_ID);
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();

        if ($this->productIdToRestore !== null && $this->stockBefore !== null)
        {
            $pdo->prepare(
                'UPDATE products SET stock = :stock WHERE id = :id AND company_id = :company_id'
            )->execute([
                'stock' => $this->stockBefore,
                'id' => $this->productIdToRestore,
                'company_id' => self::COMPANY_ID,
            ]);
        }

        foreach ($this->movementIdsToCleanup as $movementId)
        {
            $pdo->prepare('DELETE FROM stock_movements WHERE id = :id')->execute(['id' => $movementId]);
        }
        $this->movementIdsToCleanup = [];

        $this->resetTestContext();
        parent::tearDown();
    }

    public function testManualMovementAdjustsStock(): void
    {
        $pdo = Database::getConnection();
        $productRepo = new ProductRepository($pdo);

        $stmt = $pdo->prepare(
            "SELECT id FROM products
             WHERE company_id = :company_id AND type = 'product' AND stock >= 2
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['company_id' => self::COMPANY_ID]);
        $productId = (int) $stmt->fetchColumn();
        $this->assertGreaterThan(0, $productId);

        $product = $productRepo->findById($productId);
        $this->assertNotNull($product);

        $this->productIdToRestore = $productId;
        $this->stockBefore = $product->stock;

        $service = new StockService(new StockMovementRepository($pdo), $productRepo, $pdo);

        $movementIn = $service->registerManual($productId, 'entrada', 1, 'Teste integração entrada');
        $this->movementIdsToCleanup[] = $movementIn;

        $afterIn = $productRepo->findById($productId);
        $this->assertNotNull($afterIn);
        $this->assertSame($this->stockBefore + 1, $afterIn->stock);

        $movementOut = $service->registerManual($productId, 'saida', 1, 'Teste integração saída');
        $this->movementIdsToCleanup[] = $movementOut;

        $afterOut = $productRepo->findById($productId);
        $this->assertNotNull($afterOut);
        $this->assertSame($this->stockBefore, $afterOut->stock);
    }

    public function testInsufficientStockThrowsValidationException(): void
    {
        $pdo = Database::getConnection();
        $productRepo = new ProductRepository($pdo);

        $stmt = $pdo->prepare(
            "SELECT id, stock FROM products
             WHERE company_id = :company_id AND type = 'product' AND stock >= 0
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['company_id' => self::COMPANY_ID]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertIsArray($row);

        $productId = (int) $row['id'];
        $stock = (int) $row['stock'];

        $service = new StockService(new StockMovementRepository($pdo), $productRepo, $pdo);

        $this->expectException(ValidationException::class);
        $service->registerManual($productId, 'saida', $stock + 1000);
    }
}
