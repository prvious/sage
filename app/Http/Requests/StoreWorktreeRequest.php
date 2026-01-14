<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorktreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\/_-]+$/',
            ],
            'create_branch' => 'boolean',
            'database_isolation' => 'required|in:separate,prefix,shared',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_name.required' => 'Branch name is required.',
            'branch_name.regex' => 'Branch name can only contain letters, numbers, slashes, hyphens, and underscores.',
            'database_isolation.required' => 'Database isolation type is required.',
            'database_isolation.in' => 'Database isolation must be either separate, prefix, or shared.',
        ];
    }
}
