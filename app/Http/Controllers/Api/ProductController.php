<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->orderBy('name')->get(),
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->refresh());
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'This product has order history and cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        $product->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
