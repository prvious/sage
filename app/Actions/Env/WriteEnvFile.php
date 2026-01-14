<?php

namespace App\Actions\Env;

use App\Support\EnvParser;

final readonly class WriteEnvFile
{
    /**
     * Write variables to an .env file
     */
    public function handle(string $path, array $variables): bool
    {
        if (! is_writable(dirname($path))) {
            throw new \RuntimeException('Directory is not writable: '.dirname($path));
        }

        if (file_exists($path) && ! is_writable($path)) {
            // Try to make it writable
            if (! chmod($path, 0644)) {
                throw new \RuntimeException("Environment file is not writable: {$path}");
            }
        }

        $content = EnvParser::stringify($variables);

        return file_put_contents($path, $content) !== false;
    }
}
