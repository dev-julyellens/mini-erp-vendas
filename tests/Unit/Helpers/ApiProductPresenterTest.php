<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ApiProductPresenter;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

final class ApiProductPresenterTest extends TestCase
{
    public function testPublicDtoExcludesCostAndMarginFields(): void
    {
        $product = new Product();
        $product->id = 1;
        $product->name = 'Produto A';
        $product->description = 'Desc';
        $product->sku = 'SKU1';
        $product->barcode = null;
        $product->categoryId = 2;
        $product->categoryName = 'Cat';
        $product->unitOfMeasure = 'UN';
        $product->costPrice = '10.00';
        $product->marginPercent = '20.00';
        $product->markupPercent = '25.00';
        $product->price = '12.00';
        $product->stock = 5;
        $product->minStock = 2;
        $product->type = 'product';
        $product->estimatedTimeMinutes = null;

        $dto = ApiProductPresenter::toArray($product);

        $this->assertArrayHasKey('price', $dto);
        $this->assertArrayNotHasKey('cost_price', $dto);
        $this->assertArrayNotHasKey('margin_percent', $dto);
        $this->assertArrayNotHasKey('markup_percent', $dto);
        $this->assertSame('12.00', $dto['price']);
    }
}
