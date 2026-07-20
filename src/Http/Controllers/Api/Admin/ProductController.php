<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Licensing\Http\Requests\StoreProductRequest;
use Kurt\Modules\Licensing\Http\Requests\UpdateProductRequest;
use Kurt\Modules\Licensing\Http\Resources\ProductResource;
use Kurt\Modules\Licensing\Server\Models\Product;

/**
 * Admin CRUD over products. Every action is gated by the ProductPolicy (the
 * shared `licensing:manage` ability) on top of the module auth middleware.
 */
final class ProductController extends ApiController
{
    use HandlesApiQuery;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query();
        $query = $this->applyApiFilters($query, $request, [
            'slug' => 'like',
            'is_active' => 'exact',
        ]);
        $query = $this->applyApiSorts($query, $request, ['id', 'slug', 'created_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), ProductResource::class);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->respond(ProductResource::make($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::query()->create($request->validated());

        return $this->respondCreated(ProductResource::make($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return $this->respond(ProductResource::make($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return $this->respondNoContent();
    }
}
