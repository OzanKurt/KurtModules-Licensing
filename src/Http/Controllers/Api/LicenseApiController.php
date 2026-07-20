<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Licensing\Http\Requests\ActivateLicenseRequest;
use Kurt\Modules\Licensing\Http\Requests\DeactivateLicenseRequest;
use Kurt\Modules\Licensing\Http\Requests\ValidateLicenseRequest;
use Kurt\Modules\Licensing\Server\Exceptions\ActivationLimitReachedException;
use Kurt\Modules\Licensing\Server\Exceptions\LicensingException;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseClaims;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;

/**
 * Machine-facing licensing API consumed by the client SDK. These endpoints
 * authenticate by the license key carried in the request body — not a logged-in
 * user — and go through the domain services so seat accounting and offline /
 * expiry semantics are preserved.
 *
 * Responses use the flat `{ valid, reason, claims }` / `{ deactivated }` shape
 * the SDK's HttpLicenseTransport expects, deliberately not the Core `data`
 * envelope. Only the public claim set (never internal fields like key_hash) is
 * ever returned.
 */
final class LicenseApiController extends ApiController
{
    public function validateLicense(ValidateLicenseRequest $request, LicenseValidator $validator): JsonResponse
    {
        $validated = $request->validated();

        $result = $validator->validateKey(
            (string) $validated['key'],
            isset($validated['fingerprint']) && is_string($validated['fingerprint']) ? $validated['fingerprint'] : null,
        );

        return response()->json([
            'valid' => $result->valid,
            'reason' => $result->reason,
            'claims' => $result->valid && $result->license !== null
                ? LicenseClaims::build($result->license)
                : null,
        ]);
    }

    public function activate(ActivateLicenseRequest $request, KeyHasher $hasher, ActivationManager $activations): JsonResponse
    {
        $validated = $request->validated();
        $license = License::query()->where('key_hash', $hasher->hash((string) $validated['key']))->first();

        if ($license === null) {
            return response()->json(['valid' => false, 'reason' => 'not_found'], 404);
        }

        $meta = array_filter([
            'label' => isset($validated['label']) && is_string($validated['label']) ? $validated['label'] : null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], static fn ($value): bool => $value !== null);

        try {
            $activations->activate($license, (string) $validated['fingerprint'], $meta);
        } catch (LicensingException $e) {
            return response()->json([
                'valid' => false,
                'reason' => $e instanceof ActivationLimitReachedException ? 'limit_reached' : 'not_usable',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'claims' => LicenseClaims::build($license),
        ]);
    }

    public function deactivate(DeactivateLicenseRequest $request, KeyHasher $hasher, ActivationManager $activations): JsonResponse
    {
        $validated = $request->validated();
        $license = License::query()->where('key_hash', $hasher->hash((string) $validated['key']))->first();

        if ($license === null) {
            return response()->json(['deactivated' => false, 'reason' => 'not_found'], 404);
        }

        return response()->json([
            'deactivated' => $activations->deactivate($license, (string) $validated['fingerprint']),
        ]);
    }
}
