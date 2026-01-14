<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class PreviewUrl
{
    public static function generate(string $branchName, string $baseUrl): string
    {
        // Sanitize branch name: feature/auth-system → feature-auth-system
        $sanitized = self::sanitizeBranchName($branchName);

        // Parse base URL
        $parsedUrl = parse_url($baseUrl);
        $host = $parsedUrl['host'] ?? 'localhost';
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $port = isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : '';

        // Generate preview URL
        $previewUrl = "{$scheme}://{$sanitized}.{$host}{$port}";

        // Validate URL format
        if (! self::isValidUrl($previewUrl)) {
            throw new \InvalidArgumentException("Generated URL is invalid: {$previewUrl}");
        }

        return $previewUrl;
    }

    public static function sanitizeBranchName(string $branchName): string
    {
        // Replace slashes with hyphens first, then slug the rest
        $branchName = str_replace('/', '-', $branchName);
        $sanitized = Str::slug($branchName);

        // Limit length to prevent overly long URLs (max 63 chars for subdomain)
        if (strlen($sanitized) > 63) {
            $sanitized = substr($sanitized, 0, 63);
            $sanitized = rtrim($sanitized, '-');
        }

        return $sanitized;
    }

    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
