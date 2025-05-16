<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'category_id' => Category::factory(),
            'status' => $this->faker->randomElement(['draft', 'active', 'inactive']),
            'metadata' => [
                'weight' => $this->faker->randomFloat(2, 0.1, 10),
                'dimensions' => [
                    'width' => $this->faker->numberBetween(1, 100),
                    'height' => $this->faker->numberBetween(1, 100),
                    'depth' => $this->faker->numberBetween(1, 100),
                ],
                'features' => $this->faker->words(5),
            ],
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => 0,
                'status' => 'out_of_stock',
            ];
        });
    }

    /**
     * Indicate that the product is in stock.
     */
    public function inStock(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => $this->faker->numberBetween(1, 100),
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 5, 50),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'original_price' => $this->faker->randomFloat(2, 51, 100),
                    'discount_percentage' => $this->faker->numberBetween(10, 50),
                ]),
            ];
        });
    }
} 