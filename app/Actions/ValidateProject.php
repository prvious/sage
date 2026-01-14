<?php

namespace App\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

final readonly class ValidateProject
{
    public function handle(string $path): bool
    {
        if (! File::exists($path)) {
            throw ValidationException::withMessages([
                'path' => 'The specified path does not exist.',
            ]);
        }

        if (! File::isDirectory($path)) {
            throw ValidationException::withMessages([
                'path' => 'The specified path is not a directory.',
            ]);
        }

        if (! str_starts_with($path, '/')) {
            throw ValidationException::withMessages([
                'path' => 'The path must be absolute, not relative.',
            ]);
        }

        $composerPath = $path.'/composer.json';
        if (! File::exists($composerPath)) {
            throw ValidationException::withMessages([
                'path' => 'The directory does not contain a composer.json file.',
            ]);
        }

        $composerJson = json_decode(File::get($composerPath), true);
        if (! isset($composerJson['require']['laravel/framework'])) {
            throw ValidationException::withMessages([
                'path' => 'The directory does not appear to be a Laravel project.',
            ]);
        }

        $envPath = $path.'/.env';
        if (! File::exists($envPath)) {
            throw ValidationException::withMessages([
                'path' => 'The Laravel project does not have a .env file.',
            ]);
        }

        return true;
    }
}
