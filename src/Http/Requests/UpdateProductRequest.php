<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Input for admin product edits. All fields are optional (partial update); the
 * slug stays unique, ignoring the product being edited.
 */
final class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');
        $id = is_object($product) && method_exists($product, 'getKey') ? $product->getKey() : $product;

        return [
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('licensing_products', 'slug')->ignore($id)],
            'name' => ['sometimes', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'composer_packages' => ['sometimes', 'nullable', 'array'],
            'composer_packages.*' => ['string'],
            'default_policy' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
