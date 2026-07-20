<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Input for the admin `POST licenses/{license}/revoke` action.
 */
final class RevokeLicenseRequest extends FormRequest
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
            'reason' => ['nullable', 'string'],
        ];
    }
}
