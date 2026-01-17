<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
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
            'agent_type' => ['required', 'string', 'in:claude-code,opencode'],
            'api_key' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agent_type.required' => 'Please specify the agent type.',
            'agent_type.in' => 'The selected agent type is invalid.',
            'api_key.required' => 'Please provide an API key.',
            'api_key.min' => 'The API key must be at least 10 characters.',
        ];
    }
}
