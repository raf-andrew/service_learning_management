<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    /**
     * Display a listing of the products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::query();

            // Apply filters
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Apply sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve products');
        }
    }

    /**
     * Store a newly created product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0'
        ];

        if (!$this->validateRequest($request, $rules)) {
            return $this->errorResponse('Validation failed', 422, $this->validator->errors());
        }

        try {
            DB::beginTransaction();

            $product = Product::create($request->all());

            // Clear cache
            Cache::tags(['products'])->flush();

            DB::commit();

            return $this->successResponse($product, 'Product created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create product');
        }
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = Cache::tags(['products'])->remember(
                "product.{$id}",
                now()->addHours(24),
                fn() => Product::findOrFail($id)
            );

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve product');
        }
    }

    /**
     * Update the specified product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category' => 'sometimes|required|string|max:100',
            'stock' => 'sometimes|required|integer|min:0'
        ];

        if (!$this->validateRequest($request, $rules)) {
            return $this->errorResponse('Validation failed', 422, $this->validator->errors());
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);
            $product->update($request->all());

            // Clear cache
            Cache::tags(['products'])->flush();

            DB::commit();

            return $this->successResponse($product, 'Product updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update product');
        }
    }

    /**
     * Remove the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);
            $product->delete();

            // Clear cache
            Cache::tags(['products'])->flush();

            DB::commit();

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete product');
        }
    }
} 