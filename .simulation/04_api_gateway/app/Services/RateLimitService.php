<?php

namespace App\Services;

use App\Models\RateLimit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    /**
     * Check if a request is within rate limits
     *
     * @param string $key
     * @param RateLimit $rateLimit
     * @return bool
     */
    public function isWithinLimits(string $key, RateLimit $rateLimit): bool
    {
        $cacheKey = "rate_limit:{$key}";
        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= $rateLimit->max_requests) {
            return false;
        }

        Cache::increment($cacheKey);
        Cache::expire($cacheKey, $rateLimit->time_window);

        return true;
    }

    /**
     * Get remaining requests for a key
     *
     * @param string $key
     * @param RateLimit $rateLimit
     * @return int
     */
    public function getRemainingRequests(string $key, RateLimit $rateLimit): int
    {
        $cacheKey = "rate_limit:{$key}";
        $currentCount = Cache::get($cacheKey, 0);

        return max(0, $rateLimit->max_requests - $currentCount);
    }

    /**
     * Get reset time for a rate limit
     *
     * @param string $key
     * @return int|null
     */
    public function getResetTime(string $key): ?int
    {
        $cacheKey = "rate_limit:{$key}";
        $ttl = Cache::ttl($cacheKey);

        return $ttl > 0 ? now()->addSeconds($ttl)->timestamp : null;
    }

    /**
     * Create a new rate limit
     *
     * @param array $data
     * @return RateLimit
     */
    public function createRateLimit(array $data): RateLimit
    {
        return RateLimit::create($data);
    }

    /**
     * Update an existing rate limit
     *
     * @param RateLimit $rateLimit
     * @param array $data
     * @return RateLimit
     */
    public function updateRateLimit(RateLimit $rateLimit, array $data): RateLimit
    {
        $rateLimit->update($data);
        return $rateLimit;
    }

    /**
     * Delete a rate limit
     *
     * @param RateLimit $rateLimit
     * @return bool
     */
    public function deleteRateLimit(RateLimit $rateLimit): bool
    {
        return $rateLimit->delete();
    }

    /**
     * Get rate limit headers
     *
     * @param string $key
     * @param RateLimit $rateLimit
     * @return array
     */
    public function getRateLimitHeaders(string $key, RateLimit $rateLimit): array
    {
        $remaining = $this->getRemainingRequests($key, $rateLimit);
        $reset = $this->getResetTime($key);

        return [
            'X-RateLimit-Limit' => $rateLimit->max_requests,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $reset,
        ];
    }

    /**
     * Clear rate limit for a key
     *
     * @param string $key
     * @return void
     */
    public function clearRateLimit(string $key): void
    {
        Cache::forget("rate_limit:{$key}");
    }
} 