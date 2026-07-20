<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Input for admin product creation.
 */
final class StoreProductRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('licensing_products', 'slug')],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'composer_packages' => ['nullable', 'array'],
            'composer_packages.*' => ['string'],
            'default_policy' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
