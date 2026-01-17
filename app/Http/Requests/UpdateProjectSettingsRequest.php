<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'base_url' => ['sometimes', 'required', 'string', 'url', 'max:255'],
            'server_driver' => ['sometimes', 'required', 'in:caddy,nginx,artisan'],
            'server_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'tls_enabled' => ['sometimes', 'boolean'],
            'custom_domain' => ['sometimes', 'nullable', 'string', 'max:255'],
            'custom_directives' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_url.url' => 'The base URL must be a valid URL.',
            'server_driver.in' => 'The server driver must be either caddy, nginx, or artisan.',
            'server_port.min' => 'The server port must be at least 1.',
            'server_port.max' => 'The server port cannot exceed 65535.',
        ];
    }
}
