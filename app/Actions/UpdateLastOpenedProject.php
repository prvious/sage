<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Cache;

final readonly class UpdateLastOpenedProject
{
    public function handle(int $projectId): void
    {
        $cacheKey = $this->getCacheKey();
        $ttl = config('sage.last_project_ttl', 60 * 24 * 30); // 30 days in minutes

        Cache::put($cacheKey, $projectId, now()->addMinutes($ttl));
    }

    private function getCacheKey(): string
    {
        return 'last_opened_project:'.session()->getId();
    }
}
