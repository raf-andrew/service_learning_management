<?php

namespace App\Services;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Request;

class AccessLogService
{
    /**
     * Log an API access
     *
     * @param array $data
     * @return AccessLog
     */
    public function logAccess(array $data): AccessLog
    {
        return AccessLog::create([
            'api_key_id' => $data['api_key_id'] ?? null,
            'route_id' => $data['route_id'] ?? null,
            'method' => $data['method'],
            'path' => $data['path'],
            'ip_address' => $data['ip_address'] ?? Request::ip(),
            'user_agent' => $data['user_agent'] ?? Request::userAgent(),
            'status_code' => $data['status_code'],
            'response_time' => $data['response_time'] ?? null,
            'request_body' => $data['request_body'] ?? null,
            'response_body' => $data['response_body'] ?? null,
        ]);
    }

    /**
     * Get access logs with optional filters
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAccessLogs(array $filters = [])
    {
        $query = AccessLog::query();

        if (isset($filters['api_key_id'])) {
            $query->where('api_key_id', $filters['api_key_id']);
        }

        if (isset($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        if (isset($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (isset($filters['status_code'])) {
            $query->where('status_code', $filters['status_code']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get access statistics
     *
     * @param array $filters
     * @return array
     */
    public function getAccessStatistics(array $filters = []): array
    {
        $query = AccessLog::query();

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_requests' => $query->count(),
            'average_response_time' => $query->avg('response_time'),
            'status_codes' => $query->selectRaw('status_code, count(*) as count')
                ->groupBy('status_code')
                ->get()
                ->pluck('count', 'status_code')
                ->toArray(),
            'methods' => $query->selectRaw('method, count(*) as count')
                ->groupBy('method')
                ->get()
                ->pluck('count', 'method')
                ->toArray(),
        ];
    }

    /**
     * Clean up old access logs
     *
     * @param int $daysToKeep
     * @return int
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        $date = now()->subDays($daysToKeep);
        return AccessLog::where('created_at', '<', $date)->delete();
    }

    /**
     * Get access log by ID
     *
     * @param int $id
     * @return AccessLog|null
     */
    public function getAccessLog(int $id): ?AccessLog
    {
        return AccessLog::find($id);
    }

    /**
     * Delete an access log
     *
     * @param AccessLog $accessLog
     * @return bool
     */
    public function deleteAccessLog(AccessLog $accessLog): bool
    {
        return $accessLog->delete();
    }
} 