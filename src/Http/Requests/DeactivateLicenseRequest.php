<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Input for the machine-facing `POST deactivate` endpoint. Authentication is the
 * license key itself, so the request is always authorized at this layer.
 */
final class DeactivateLicenseRequest extends FormRequest
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
            'key' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
        ];
    }
}
