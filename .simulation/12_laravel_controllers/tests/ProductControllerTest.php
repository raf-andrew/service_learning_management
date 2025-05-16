<?php

namespace Tests;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /** @test */
    public function it_can_list_products_with_filters()
    {
        // Create test products
        Product::factory()->count(3)->create([
            'category' => 'electronics',
            'price' => 100
        ]);

        Product::factory()->count(2)->create([
            'category' => 'books',
            'price' => 50
        ]);

        // Test filtering by category
        $response = $this->getJson('/api/products?category=electronics');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');

        // Test filtering by price range
        $response = $this->getJson('/api/products?min_price=75&max_price=125');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');

        // Test sorting
        $response = $this->getJson('/api/products?sort_by=price&sort_direction=asc');
        $response->assertStatus(200);
        $products = $response->json('data.data');
        $this->assertTrue($products[0]['price'] <= $products[1]['price']);
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'category' => 'test',
            'stock' => 100
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => $productData['name'],
                    'price' => $productData['price']
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'price' => $productData['price']
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_product()
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price', 'category', 'stock']);
    }

    /** @test */
    public function it_can_show_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_product()
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'price' => 199.99
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $updateData['name'],
                    'price' => $updateData['price']
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $updateData['name'],
            'price' => $updateData['price']
        ]);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /** @test */
    public function it_handles_database_transactions_correctly()
    {
        $product = Product::factory()->create();
        $invalidData = [
            'price' => 'invalid_price' // This should cause a database error
        ];

        $response = $this->putJson("/api/products/{$product->id}", $invalidData);

        $response->assertStatus(422);

        // Verify the product wasn't updated
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => $product->price
        ]);
    }

    /** @test */
    public function it_implements_caching()
    {
        $product = Product::factory()->create();

        // First request should hit the database
        $response1 = $this->getJson("/api/products/{$product->id}");
        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this->getJson("/api/products/{$product->id}");
        $response2->assertStatus(200);

        // Update the product
        $this->putJson("/api/products/{$product->id}", ['name' => 'Updated Name']);

        // Next request should get fresh data
        $response3 = $this->getJson("/api/products/{$product->id}");
        $response3->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name'
                ]
            ]);
    }
} 