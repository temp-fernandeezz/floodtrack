<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Registra quando o scraper de notícias e o expirador automático rodaram pela última vez,
 * pra dar visibilidade operacional no dashboard (sem precisar vasculhar logs).
 */
class ScraperHealthRecorder
{
    private const FETCH_AT_KEY       = 'flood:health:fetch_at';
    private const FETCH_IMPORTED_KEY = 'flood:health:fetch_imported';
    private const FETCH_TOTAL_KEY    = 'flood:health:fetch_total';
    private const EXPIRE_AT_KEY      = 'flood:health:expire_at';
    private const EXPIRE_COUNT_KEY   = 'flood:health:expire_count';

    public function recordFetch(int $imported, int $totalItemsSeen): void
    {
        Cache::forever(self::FETCH_AT_KEY, now());
        Cache::forever(self::FETCH_IMPORTED_KEY, $imported);
        Cache::forever(self::FETCH_TOTAL_KEY, $totalItemsSeen);
    }

    public function recordExpire(int $expiredCount): void
    {
        Cache::forever(self::EXPIRE_AT_KEY, now());
        Cache::forever(self::EXPIRE_COUNT_KEY, $expiredCount);
    }

    /**
     * @return array{at: ?\Illuminate\Support\Carbon, imported: ?int, total: ?int}
     */
    public function lastFetch(): array
    {
        return [
            'at'       => Cache::get(self::FETCH_AT_KEY),
            'imported' => Cache::get(self::FETCH_IMPORTED_KEY),
            'total'    => Cache::get(self::FETCH_TOTAL_KEY),
        ];
    }

    /**
     * @return array{at: ?\Illuminate\Support\Carbon, count: ?int}
     */
    public function lastExpire(): array
    {
        return [
            'at'    => Cache::get(self::EXPIRE_AT_KEY),
            'count' => Cache::get(self::EXPIRE_COUNT_KEY),
        ];
    }
}
