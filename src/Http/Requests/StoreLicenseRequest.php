<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Kurt\Modules\Licensing\Enums\PolicyType;

/**
 * Input for admin license issuance. The policy is enforced in the controller
 * via $this->authorize(); this request only validates shape.
 */
final class StoreLicenseRequest extends FormRequest
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
            'product_id' => ['required', 'integer', Rule::exists('licensing_products', 'id')],
            'licensee_email' => ['required', 'email'],
            'licensee_user_id' => ['nullable', 'integer'],
            'licensee_name' => ['nullable', 'string'],
            'licensee_company' => ['nullable', 'string'],
            'policy_type' => ['nullable', new Enum(PolicyType::class)],
            'max_activations' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'updates_until' => ['nullable', 'date'],
            'order_reference' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * The license attributes to hand to the issuer (everything but product_id).
     *
     * @return array<string, mixed>
     */
    public function licenseAttributes(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->safe()->except('product_id');

        return $data;
    }
}
