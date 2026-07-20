<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;

/**
 * Input for admin license edits. All fields are optional (partial update); the
 * secret key, product and lookup hash are immutable and cannot be changed here.
 */
final class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(LicenseStatus::class)],
            'policy_type' => ['sometimes', new Enum(PolicyType::class)],
            'max_activations' => ['sometimes', 'integer', 'min:1'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'updates_until' => ['sometimes', 'nullable', 'date'],
            'licensee_name' => ['sometimes', 'nullable', 'string'],
            'licensee_company' => ['sometimes', 'nullable', 'string'],
            'order_reference' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
