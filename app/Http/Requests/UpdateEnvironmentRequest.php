<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnvironmentRequest extends FormRequest
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
            'env_path' => ['sometimes', 'string'],
            'variables' => ['sometimes', 'array'],
            'variables.*.value' => ['string'],
            'variables.*.comment' => ['nullable', 'string'],
            'variables.*.is_sensitive' => ['boolean'],
            'source_type' => ['sometimes', 'string', 'in:project,worktree'],
            'source_id' => ['sometimes', 'integer'],
            'targets' => ['sometimes', 'array'],
            'targets.*' => ['integer', 'exists:worktrees,id'],
            'overwrite' => ['sometimes', 'boolean'],
            'backup_path' => ['sometimes', 'string'],
            'target_path' => ['sometimes', 'string'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'variables.*.value.string' => 'All environment variable values must be strings',
            'targets.*.exists' => 'One or more selected worktrees do not exist',
            'source_type.in' => 'Source type must be either project or worktree',
        ];
    }
}
