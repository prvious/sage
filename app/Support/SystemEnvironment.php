<?php

declare(strict_types=1);

namespace App\Support;

final class SystemEnvironment
{
    private static ?array $faked = null;

    /**
     * Get a single environment variable.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (self::$faked !== null) {
            return self::$faked[$key] ?? $default;
        }

        $value = getenv($key);

        return $value !== false ? $value : $default;
    }

    /**
     * Get all environment variables.
     */
    public function all(): array
    {
        if (self::$faked !== null) {
            return self::$faked;
        }

        return getenv();
    }

    /**
     * Check if an environment variable exists and is not empty.
     */
    public function has(string $key): bool
    {
        if (self::$faked !== null) {
            return isset(self::$faked[$key]) && self::$faked[$key] !== '' && self::$faked[$key] !== null;
        }

        $value = getenv($key);

        return $value !== false && $value !== '' && $value !== null;
    }

    /**
     * Fake the system environment for testing.
     * Pass an array of environment variables to fake.
     */
    public static function fake(array $environment = []): void
    {
        self::$faked = $environment;
    }

    /**
     * Clear any faked environment and restore normal behavior.
     */
    public static function clearFake(): void
    {
        self::$faked = null;
    }
}
