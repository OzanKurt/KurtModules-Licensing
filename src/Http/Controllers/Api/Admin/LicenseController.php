<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Licensing\Enums\LicenseEventType;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Http\Requests\RevokeLicenseRequest;
use Kurt\Modules\Licensing\Http\Requests\StoreLicenseRequest;
use Kurt\Modules\Licensing\Http\Requests\UpdateLicenseRequest;
use Kurt\Modules\Licensing\Http\Resources\ActivationResource;
use Kurt\Modules\Licensing\Http\Resources\LicenseResource;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Models\Product;
use Kurt\Modules\Licensing\Server\Support\EventLogger;
use Kurt\Modules\Licensing\Server\Support\LicenseIssuer;

/**
 * Admin CRUD over licenses. Every action is gated by the LicensePolicy (the
 * shared `licensing:manage` ability) on top of the module auth middleware.
 */
final class LicenseController extends ApiController
{
    use HandlesApiQuery;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', License::class);

        $query = License::query()->with('product');
        $query = $this->applyApiFilters($query, $request, [
            'status' => 'exact',
            'policy_type' => 'exact',
            'product_id' => 'exact',
            'licensee_email' => 'like',
        ]);
        $query = $this->applyApiSorts($query, $request, ['id', 'issued_at', 'expires_at', 'status', 'created_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), LicenseResource::class);
    }

    public function show(License $license): JsonResponse
    {
        $this->authorize('view', $license);

        return $this->respond(LicenseResource::make($license->loadMissing('product')));
    }

    public function store(StoreLicenseRequest $request, LicenseIssuer $issuer): JsonResponse
    {
        $this->authorize('create', License::class);

        /** @var Product $product */
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $issued = $issuer->issue($product, $request->licenseAttributes());

        return $this->respondCreated([
            'key' => $issued->key,
            'license' => LicenseResource::make($issued->license->loadMissing('product')),
        ]);
    }

    public function update(UpdateLicenseRequest $request, License $license): JsonResponse
    {
        $this->authorize('update', $license);

        $license->update($request->validated());

        return $this->respond(LicenseResource::make($license->loadMissing('product')));
    }

    public function revoke(RevokeLicenseRequest $request, License $license, EventLogger $events): JsonResponse
    {
        $this->authorize('revoke', $license);

        $reason = $request->string('reason')->toString();

        $license->update([
            'status' => LicenseStatus::Revoked,
            'revoked_at' => now(),
            'revoked_reason' => $reason !== '' ? $reason : null,
        ]);

        $events->log($license, LicenseEventType::Revoked, ['reason' => $reason !== '' ? $reason : null]);

        return $this->respond(LicenseResource::make($license->loadMissing('product')));
    }

    public function activations(Request $request, License $license): JsonResponse
    {
        $this->authorize('view', $license);

        $query = $license->activations()->getQuery();
        $query = $this->applyApiSorts($query, $request, ['id', 'activated_at', 'last_seen_at', 'deactivated_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), ActivationResource::class);
    }
}
