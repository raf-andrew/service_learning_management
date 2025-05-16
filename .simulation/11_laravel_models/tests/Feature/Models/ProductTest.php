<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use App\Models\Tag;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
        ]);
    }

    public function test_product_has_default_status()
    {
        $product = Product::factory()->create(['status' => null]);

        $this->assertEquals('draft', $product->status);
    }

    public function test_product_updates_status_when_stock_is_zero()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->assertEquals('active', $product->status);

        $product->update(['stock' => 0]);
        $this->assertEquals('out_of_stock', $product->fresh()->status);
    }

    public function test_product_belongs_to_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_has_many_reviews()
    {
        $product = Product::factory()->create();
        $reviews = Review::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->reviews);
        $this->assertInstanceOf(Review::class, $product->reviews->first());
    }

    public function test_product_belongs_to_many_tags()
    {
        $product = Product::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $product->tags()->attach($tags);

        $this->assertCount(3, $product->tags);
        $this->assertInstanceOf(Tag::class, $product->tags->first());
    }

    public function test_product_has_morph_many_images()
    {
        $product = Product::factory()->create();
        $images = Image::factory()->count(3)->create([
            'imageable_type' => Product::class,
            'imageable_id' => $product->id,
        ]);

        $this->assertCount(3, $product->images);
        $this->assertInstanceOf(Image::class, $product->images->first());
    }

    public function test_can_scope_active_products()
    {
        Product::factory()->count(3)->active()->create();
        Product::factory()->count(2)->create(['status' => 'inactive']);

        $this->assertCount(3, Product::active()->get());
    }

    public function test_can_scope_in_stock_products()
    {
        Product::factory()->count(3)->inStock()->create();
        Product::factory()->count(2)->outOfStock()->create();

        $this->assertCount(3, Product::inStock()->get());
    }

    public function test_can_scope_products_by_price_range()
    {
        Product::factory()->create(['price' => 50]);
        Product::factory()->create(['price' => 100]);
        Product::factory()->create(['price' => 150]);

        $this->assertCount(2, Product::priceRange(50, 100)->get());
    }

    public function test_can_get_average_rating()
    {
        $product = Product::factory()->create();
        Review::factory()->create(['product_id' => $product->id, 'rating' => 4]);
        Review::factory()->create(['product_id' => $product->id, 'rating' => 5]);

        $this->assertEquals(4.5, $product->average_rating);
    }

    public function test_can_get_is_in_stock_attribute()
    {
        $inStockProduct = Product::factory()->inStock()->create();
        $outOfStockProduct = Product::factory()->outOfStock()->create();

        $this->assertTrue($inStockProduct->is_in_stock);
        $this->assertFalse($outOfStockProduct->is_in_stock);
    }

    public function test_can_get_formatted_price()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertEquals('$99.99', $product->formatted_price);
    }

    public function test_rounds_price_to_two_decimal_places()
    {
        $product = Product::factory()->create(['price' => 99.999]);

        $this->assertEquals(100.00, $product->price);
    }

    public function test_hides_metadata_from_serialization()
    {
        $product = Product::factory()->create();
        $productArray = $product->toArray();

        $this->assertArrayNotHasKey('metadata', $productArray);
    }

    public function test_can_soft_delete_product()
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
} 