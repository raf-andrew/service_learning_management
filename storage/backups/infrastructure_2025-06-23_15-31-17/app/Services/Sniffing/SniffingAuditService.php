<?php

namespace App\Services\Sniffing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SniffingAuditService
{
    /**
     * Log a sniffing operation
     */
    public function logOperation(string $operation, array $data, ?string $status = null): void
    {
        try {
            $user = Auth::user();
            
            $logData = [
                'user_id' => $user ? $user->id : null,
                'operation' => $operation,
                'data' => json_encode($data),
                'status' => $status,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            // Log to database
            DB::table('sniffing_audit_logs')->insert($logData);

            // Log to file
            Log::info('Sniffing operation', [
                'operation' => $operation,
                'user' => $user ? $user->email : 'anonymous',
                'data' => $data,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log sniffing operation', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'data' => $data,
            ]);
        }
    }

    /**
     * Get audit logs
     */
    public function getLogs(array $filters = []): array
    {
        $query = DB::table('sniffing_audit_logs')
            ->select([
                'id',
                'user_id',
                'operation',
                'data',
                'status',
                'ip_address',
                'user_agent',
                'created_at',
            ]);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['operation'])) {
            $query->where('operation', $filters['operation']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50)
            ->toArray();
    }

    /**
     * Get operation statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_operations' => $this->getTotalOperations(),
            'operations_by_type' => $this->getOperationsByType(),
            'operations_by_user' => $this->getOperationsByUser(),
            'operations_by_status' => $this->getOperationsByStatus(),
            'recent_operations' => $this->getRecentOperations(),
        ];
    }

    /**
     * Get total number of operations
     */
    protected function getTotalOperations(): int
    {
        return DB::table('sniffing_audit_logs')->count();
    }

    /**
     * Get operations by type
     */
    protected function getOperationsByType(): array
    {
        return DB::table('sniffing_audit_logs')
            ->select('operation', DB::raw('count(*) as count'))
            ->groupBy('operation')
            ->get()
            ->toArray();
    }

    /**
     * Get operations by user
     */
    protected function getOperationsByUser(): array
    {
        return DB::table('sniffing_audit_logs')
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->get()
            ->toArray();
    }

    /**
     * Get operations by status
     */
    protected function getOperationsByStatus(): array
    {
        return DB::table('sniffing_audit_logs')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * Get recent operations
     */
    protected function getRecentOperations(): array
    {
        return DB::table('sniffing_audit_logs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
} 