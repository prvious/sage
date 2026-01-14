<?php

namespace App\Support;

final class EnvParser
{
    /**
     * Parse .env file content into structured array
     */
    public static function parse(string $content): array
    {
        $variables = [];
        $lines = explode("\n", $content);
        $currentComment = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Handle comments
            if (str_starts_with($line, '#')) {
                $currentComment = substr($line, 1);

                continue;
            }

            // Skip empty lines
            if (empty($line)) {
                $currentComment = null;

                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                $variables[$key] = [
                    'value' => $value,
                    'comment' => $currentComment,
                    'is_sensitive' => self::isSensitive($key),
                ];

                $currentComment = null;
            }
        }

        return $variables;
    }

    /**
     * Convert array of variables back to .env format
     */
    public static function stringify(array $variables): string
    {
        $lines = [];

        foreach ($variables as $key => $data) {
            // Add comment if present
            if (! empty($data['comment'])) {
                $lines[] = '# '.$data['comment'];
            }

            // Add key=value pair
            $value = $data['value'];

            // Quote value if it contains spaces or special characters
            if (str_contains($value, ' ') || str_contains($value, '#')) {
                $value = '"'.str_replace('"', '\\"', $value).'"';
            }

            $lines[] = "{$key}={$value}";
            $lines[] = ''; // Empty line for readability
        }

        return implode("\n", $lines);
    }

    /**
     * Check if a key contains sensitive data
     */
    public static function isSensitive(string $key): bool
    {
        $sensitiveKeys = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN', 'API_KEY', 'PRIVATE'];

        foreach ($sensitiveKeys as $pattern) {
            if (str_contains(strtoupper($key), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Group variables by section based on prefix
     */
    public static function groupBySection(array $variables): array
    {
        $groups = [
            'Application' => [],
            'Database' => [],
            'Cache' => [],
            'Queue' => [],
            'Mail' => [],
            'Session' => [],
            'Broadcasting' => [],
            'Logging' => [],
            'Filesystem' => [],
            'AWS' => [],
            'Pusher' => [],
            'Redis' => [],
            'Vite' => [],
            'Other' => [],
        ];

        $prefixMap = [
            'APP_' => 'Application',
            'DB_' => 'Database',
            'CACHE_' => 'Cache',
            'QUEUE_' => 'Queue',
            'MAIL_' => 'Mail',
            'SESSION_' => 'Session',
            'BROADCAST_' => 'Broadcasting',
            'LOG_' => 'Logging',
            'FILESYSTEM_' => 'Filesystem',
            'AWS_' => 'AWS',
            'PUSHER_' => 'Pusher',
            'REDIS_' => 'Redis',
            'VITE_' => 'Vite',
        ];

        foreach ($variables as $key => $data) {
            $section = 'Other';

            foreach ($prefixMap as $prefix => $group) {
                if (str_starts_with($key, $prefix)) {
                    $section = $group;
                    break;
                }
            }

            $groups[$section][$key] = $data;
        }

        // Remove empty sections
        return array_filter($groups, fn ($group) => ! empty($group));
    }
}
