# Product Model Usage Examples

## Basic CRUD Operations

### Creating a Product
```php
// Create a new product
$product = Product::create([
    'name' => 'Example Product',
    'description' => 'This is an example product',
    'price' => 99.99,
    'stock' => 100,
    'category_id' => 1,
    'status' => 'active',
    'metadata' => [
        'weight' => 1.5,
        'dimensions' => [
            'width' => 10,
            'height' => 20,
            'depth' => 5,
        ],
    ],
]);

// Create using factory
$product = Product::factory()->create();

// Create with specific state
$product = Product::factory()
    ->active()
    ->inStock()
    ->create();
```

### Reading Products
```php
// Find by ID
$product = Product::find(1);

// Find with relationships
$product = Product::with(['category', 'tags', 'reviews', 'images'])->find(1);

// Get all active products
$activeProducts = Product::active()->get();

// Get products in stock within price range
$products = Product::inStock()
    ->priceRange(50, 100)
    ->get();

// Get product with average rating
$product = Product::withAvg('reviews', 'rating')->find(1);
```

### Updating Products
```php
// Update a product
$product = Product::find(1);
$product->update([
    'price' => 89.99,
    'stock' => 50,
]);

// Update stock and check status
$product->update(['stock' => 0]);
// Status will automatically change to 'out_of_stock'

// Update with relationship
$product->tags()->sync([1, 2, 3]);
```

### Deleting Products
```php
// Soft delete
$product = Product::find(1);
$product->delete();

// Force delete
$product->forceDelete();

// Restore soft deleted product
$product->restore();
```

## Relationship Usage

### Category Relationship
```php
// Get product's category
$category = $product->category;

// Get all products in a category
$products = Category::find(1)->products;

// Get products with their categories
$products = Product::with('category')->get();
```

### Reviews Relationship
```php
// Get product's reviews
$reviews = $product->reviews;

// Add a review
$product->reviews()->create([
    'rating' => 5,
    'comment' => 'Great product!',
]);

// Get average rating
$averageRating = $product->average_rating;
```

### Tags Relationship
```php
// Get product's tags
$tags = $product->tags;

// Add tags
$product->tags()->attach([1, 2, 3]);

// Remove tags
$product->tags()->detach([1, 2]);

// Sync tags (replace all)
$product->tags()->sync([1, 2, 3]);

// Toggle tags
$product->tags()->toggle([1, 2]);
```

### Images Relationship
```php
// Get product's images
$images = $product->images;

// Add an image
$product->images()->create([
    'url' => 'path/to/image.jpg',
    'alt' => 'Product image',
]);

// Remove an image
$product->images()->where('id', 1)->delete();
```

## Event Handling

### Model Events
```php
// Listen for product creation
Product::creating(function ($product) {
    // Set default status if not provided
    if (!$product->status) {
        $product->status = 'draft';
    }
});

// Listen for product updates
Product::updating(function ($product) {
    // Update status based on stock
    if ($product->stock <= 0) {
        $product->status = 'out_of_stock';
    }
});

// Listen for product deletion
Product::deleting(function ($product) {
    // Clean up related data
    $product->reviews()->delete();
    $product->images()->delete();
});
```

## Scope Usage

### Local Scopes
```php
// Get active products
$activeProducts = Product::active()->get();

// Get in-stock products
$inStockProducts = Product::inStock()->get();

// Get products in price range
$products = Product::priceRange(50, 100)->get();

// Chain scopes
$products = Product::active()
    ->inStock()
    ->priceRange(50, 100)
    ->get();
```

### Dynamic Scopes
```php
// Get products by status
$products = Product::ofStatus('active')->get();

// Get products by category
$products = Product::ofCategory(1)->get();

// Get products with minimum rating
$products = Product::withMinRating(4)->get();
```

## Accessor and Mutator Usage

### Accessors
```php
// Get formatted price
$formattedPrice = $product->formatted_price; // Returns "$99.99"

// Get stock status
$isInStock = $product->is_in_stock; // Returns true/false

// Get average rating
$averageRating = $product->average_rating; // Returns 4.5
```

### Mutators
```php
// Set price (automatically rounded to 2 decimal places)
$product->price = 99.999; // Stored as 100.00

// Set metadata (automatically converted to JSON)
$product->metadata = [
    'weight' => 1.5,
    'dimensions' => [
        'width' => 10,
        'height' => 20,
        'depth' => 5,
    ],
];
``` 