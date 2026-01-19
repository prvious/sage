<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuidelineRequest extends FormRequest
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
                'regex:/^[a-zA-Z0-9_-]+\.(md|blade\.php)$/',
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
            'filename.regex' => 'The filename must only contain alphanumeric characters, dashes, underscores, and must end with .md or .blade.php',
            'filename.max' => 'The filename must not exceed 255 characters.',
            'content.required' => 'The guideline content is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $filename = $this->input('filename');
        $extension = $this->input('extension', 'md');

        // Auto-append extension if not present
        if ($filename && ! str_ends_with($filename, '.md') && ! str_ends_with($filename, '.blade.php')) {
            $finalFilename = $extension === 'blade.php'
                ? $filename.'.blade.php'
                : $filename.'.md';

            $this->merge([
                'filename' => $finalFilename,
            ]);
        }
    }
}
