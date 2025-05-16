<?php

namespace Tests\Feature\Database;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Review;
use App\Models\Image;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_expected_data()
    {
        $this->seed(ProductSeeder::class);

        // Check categories
        $this->assertCount(5, Category::all());

        // Check tags
        $this->assertCount(10, Tag::all());

        // Check products
        $this->assertCount(28, Product::all()); // 5 categories * 5 products + 5 on sale + 3 out of stock

        // Check relationships
        $product = Product::with(['category', 'tags', 'reviews', 'images'])->first();

        // Check category relationship
        $this->assertInstanceOf(Category::class, $product->category);

        // Check tags relationship
        $this->assertGreaterThanOrEqual(2, $product->tags->count());
        $this->assertLessThanOrEqual(5, $product->tags->count());
        $this->assertInstanceOf(Tag::class, $product->tags->first());

        // Check reviews relationship
        $this->assertGreaterThanOrEqual(1, $product->reviews->count());
        $this->assertLessThanOrEqual(5, $product->reviews->count());
        $this->assertInstanceOf(Review::class, $product->reviews->first());

        // Check images relationship
        $this->assertGreaterThanOrEqual(1, $product->images->count());
        $this->assertLessThanOrEqual(3, $product->images->count());
        $this->assertInstanceOf(Image::class, $product->images->first());

        // Check on sale products
        $onSaleProducts = Product::whereNotNull('metadata->original_price')->get();
        $this->assertCount(5, $onSaleProducts);

        // Check out of stock products
        $outOfStockProducts = Product::where('stock', 0)->get();
        $this->assertCount(3, $outOfStockProducts);
    }

    public function test_seeder_creates_consistent_data()
    {
        // Run seeder twice to ensure consistent data
        $this->seed(ProductSeeder::class);
        $firstRunCounts = [
            'categories' => Category::count(),
            'tags' => Tag::count(),
            'products' => Product::count(),
            'reviews' => Review::count(),
            'images' => Image::count(),
        ];

        $this->refreshDatabase();
        $this->seed(ProductSeeder::class);

        $secondRunCounts = [
            'categories' => Category::count(),
            'tags' => Tag::count(),
            'products' => Product::count(),
            'reviews' => Review::count(),
            'images' => Image::count(),
        ];

        $this->assertEquals($firstRunCounts, $secondRunCounts);
    }
} 