<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContextFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+\.md$/',
            ],
            'content' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'filename.required' => 'The filename is required.',
            'filename.regex' => 'The filename must only contain alphanumeric characters, dashes, underscores, and must end with .md',
            'filename.max' => 'The filename must not exceed 255 characters.',
            'content.required' => 'The file content is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $filename = $this->input('filename');

        // Auto-append .md if not present
        if ($filename && ! str_ends_with($filename, '.md')) {
            $this->merge([
                'filename' => $filename.'.md',
            ]);
        }
    }
}
