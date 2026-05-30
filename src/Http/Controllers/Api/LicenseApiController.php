<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Licensing\Server\Exceptions\ActivationLimitReachedException;
use Kurt\Modules\Licensing\Server\Exceptions\LicensingException;
use Kurt\Modules\Licensing\Server\Models\License;
use Kurt\Modules\Licensing\Server\Support\ActivationManager;
use Kurt\Modules\Licensing\Server\Support\KeyHasher;
use Kurt\Modules\Licensing\Server\Support\LicenseClaims;
use Kurt\Modules\Licensing\Server\Support\LicenseValidator;

/**
 * Public licensing API consumed by the client SDK. Responses mirror the SDK's
 * ValidationResponse shape: { valid, reason, claims }.
 */
final class LicenseApiController
{
    public function validate(Request $request, LicenseValidator $validator): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'fingerprint' => ['nullable', 'string'],
        ]);

        $key = (string) $validated['key'];
        $fingerprint = isset($validated['fingerprint']) && is_string($validated['fingerprint'])
            ? $validated['fingerprint']
            : null;

        $result = $validator->validateKey($key, $fingerprint);

        return response()->json([
            'valid' => $result->valid,
            'reason' => $result->reason,
            'claims' => $result->valid && $result->license !== null
                ? LicenseClaims::build($result->license)
                : null,
        ]);
    }

    public function activate(Request $request, KeyHasher $hasher, ActivationManager $activations): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
            'label' => ['nullable', 'string'],
        ]);

        $license = License::query()->where('key_hash', $hasher->hash((string) $validated['key']))->first();

        if ($license === null) {
            return response()->json(['valid' => false, 'reason' => 'not_found'], 404);
        }

        $meta = array_filter([
            'label' => is_string($validated['label'] ?? null) ? $validated['label'] : null,
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

    public function deactivate(Request $request, KeyHasher $hasher, ActivationManager $activations): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
        ]);

        $license = License::query()->where('key_hash', $hasher->hash((string) $validated['key']))->first();

        if ($license === null) {
            return response()->json(['deactivated' => false, 'reason' => 'not_found'], 404);
        }

        return response()->json([
            'deactivated' => $activations->deactivate($license, (string) $validated['fingerprint']),
        ]);
    }
}
