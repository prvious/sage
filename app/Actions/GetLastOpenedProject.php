<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Cache;

final readonly class GetLastOpenedProject
{
    public function handle(): ?int
    {
        $cacheKey = $this->getCacheKey();

        return Cache::get($cacheKey);
    }

    private function getCacheKey(): string
    {
        return 'last_opened_project:'.session()->getId();
    }
}
