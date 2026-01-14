<?php

namespace App\Actions\Env;

use App\Support\EnvParser;

final readonly class ReadEnvFile
{
    /**
     * Read and parse an .env file
     */
    public function handle(string $path): array
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Environment file not found: {$path}");
        }

        if (! is_readable($path)) {
            throw new \RuntimeException("Environment file is not readable: {$path}");
        }

        $content = file_get_contents($path);

        return EnvParser::parse($content);
    }
}
