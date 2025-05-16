<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Review;
use App\Models\Image;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = Category::factory()->count(5)->create();

        // Create tags
        $tags = Tag::factory()->count(10)->create();

        // Create products with relationships
        foreach ($categories as $category) {
            // Create 5 products per category
            $products = Product::factory()
                ->count(5)
                ->create(['category_id' => $category->id]);

            foreach ($products as $product) {
                // Attach random tags
                $product->tags()->attach(
                    $tags->random(rand(2, 5))->pluck('id')->toArray()
                );

                // Create reviews
                Review::factory()
                    ->count(rand(1, 5))
                    ->create(['product_id' => $product->id]);

                // Create images
                Image::factory()
                    ->count(rand(1, 3))
                    ->create([
                        'imageable_type' => Product::class,
                        'imageable_id' => $product->id,
                    ]);
            }
        }

        // Create some products on sale
        Product::factory()
            ->count(5)
            ->onSale()
            ->create();

        // Create some out of stock products
        Product::factory()
            ->count(3)
            ->outOfStock()
            ->create();
    }
} 