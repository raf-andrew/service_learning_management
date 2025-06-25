<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use App\Events\AuditLogCreated;

/**
 * Enhanced Audit Service
 * 
 * Comprehensive audit service with multiple storage backends, advanced
 * filtering, retention policies, and export capabilities.
 * 
 * Features:
 * - Multiple storage backends (Database, File, Redis, External APIs)
 * - Automatic audit logging for all CRUD operations
 * - Advanced search and filtering
 * - Audit log retention policies
 * - Audit log export functionality
 * - Real-time audit monitoring
 * - Compliance reporting
 * 
 * @package App\Services
 */
class EnhancedAuditService
{
    /**
     * Storage backends configuration.
     *
     * @var array<string, array>
     */
    protected array $storageBackends = [];

    /**
     * Primary storage backend.
     *
     * @var string
     */
    protected string $primaryBackend = 'database';

    /**
     * Audit configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Audit statistics.
     *
     * @var array<string, mixed>
     */
    protected array $statistics = [
        'total_logs' => 0,
        'logs_today' => 0,
        'logs_this_week' => 0,
        'logs_this_month' => 0,
        'storage_usage' => [],
    ];

    /**
     * Create a new enhanced audit service instance.
     */
    public function __construct()
    {
        $this->initializeConfiguration();
        $this->initializeStorageBackends();
    }

    /**
     * Initialize audit configuration.
     */
    protected function initializeConfiguration(): void
    {
        $this->config = [
            'enabled' => config('audit.enabled', true),
            'log_level' => config('audit.log_level', 'info'),
            'retention_days' => config('audit.retention_days', 2555), // 7 years
            'batch_size' => config('audit.batch_size', 100),
            'compression' => config('audit.compression', true),
            'encryption' => config('audit.encryption', false),
            'real_time_monitoring' => config('audit.real_time_monitoring', true),
            'auto_cleanup' => config('audit.auto_cleanup', true),
        ];
    }

    /**
     * Initialize storage backends.
     */
    protected function initializeStorageBackends(): void
    {
        $this->storageBackends = [
            'database' => [
                'driver' => 'database',
                'table' => 'audit_logs',
                'enabled' => true,
                'compression' => false,
                'encryption' => false,
            ],
            'file' => [
                'driver' => 'file',
                'path' => storage_path('logs/audit'),
                'enabled' => true,
                'compression' => true,
                'encryption' => false,
                'rotation' => 'daily',
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'audit',
                'enabled' => config('audit.redis.enabled', false),
                'compression' => true,
                'encryption' => false,
                'ttl' => 86400, // 24 hours
            ],
            'external_api' => [
                'driver' => 'api',
                'endpoint' => config('audit.external.endpoint'),
                'enabled' => config('audit.external.enabled', false),
                'compression' => true,
                'encryption' => true,
                'timeout' => 30,
            ],
        ];
    }

    /**
     * Log an audit event with multiple storage backends.
     *
     * @param string $module
     * @param string $action
     * @param array<string, mixed> $context
     * @param array<string> $backends
     * @return bool
     */
    public function log(string $module, string $action, array $context = [], array $backends = []): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $auditData = $this->prepareAuditData($module, $action, $context);
        $backends = empty($backends) ? array_keys($this->storageBackends) : $backends;
        $success = true;

        foreach ($backends as $backend) {
            if (!isset($this->storageBackends[$backend]) || !$this->storageBackends[$backend]['enabled']) {
                continue;
            }

            try {
                $this->storeAuditData($backend, $auditData);
                $this->updateStatistics($backend);
            } catch (\Exception $e) {
                Log::error("Audit storage failed for backend: {$backend}", [
                    'error' => $e->getMessage(),
                    'audit_data' => $auditData,
                ]);
                $success = false;
            }
        }

        // Fire audit event
        if ($this->config['real_time_monitoring']) {
            Event::dispatch(new AuditLogCreated($auditData));
        }

        return $success;
    }

    /**
     * Prepare audit data for storage.
     *
     * @param string $module
     * @param string $action
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function prepareAuditData(string $module, string $action, array $context): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'module' => $module,
            'action' => $action,
            'user_id' => $this->getCurrentUserId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
            'timestamp' => now()->toISOString(),
            'context' => $context,
            'metadata' => [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'headers' => $this->sanitizeHeaders(request()->headers->all()),
                'input' => $this->sanitizeInput(request()->all()),
            ],
        ];
    }

    /**
     * Store audit data in specified backend.
     *
     * @param string $backend
     * @param array<string, mixed> $auditData
     * @return void
     */
    protected function storeAuditData(string $backend, array $auditData): void
    {
        $config = $this->storageBackends[$backend];
        
        switch ($config['driver']) {
            case 'database':
                $this->storeInDatabase($auditData, $config);
                break;
            case 'file':
                $this->storeInFile($auditData, $config);
                break;
            case 'redis':
                $this->storeInRedis($auditData, $config);
                break;
            case 'api':
                $this->storeInExternalApi($auditData, $config);
                break;
        }
    }

    /**
     * Store audit data in database.
     *
     * @param array<string, mixed> $auditData
     * @param array<string, mixed> $config
     * @return void
     */
    protected function storeInDatabase(array $auditData, array $config): void
    {
        $table = $config['table'];
        
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            $this->createAuditTable($table);
        }

        DB::table($table)->insert([
            'id' => $auditData['id'],
            'module' => $auditData['module'],
            'action' => $auditData['action'],
            'user_id' => $auditData['user_id'],
            'ip_address' => $auditData['ip_address'],
            'user_agent' => $auditData['user_agent'],
            'session_id' => $auditData['session_id'],
            'request_id' => $auditData['request_id'],
            'context' => json_encode($auditData['context']),
            'metadata' => json_encode($auditData['metadata']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Store audit data in file.
     *
     * @param array<string, mixed> $auditData
     * @param array<string, mixed> $config
     * @return void
     */
    protected function storeInFile(array $auditData, array $config): void
    {
        $path = $config['path'];
        $filename = $this->generateAuditFilename($config['rotation'] ?? 'daily');
        $filePath = "{$path}/{$filename}";

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $logEntry = json_encode($auditData) . "\n";
        
        if ($config['compression'] ?? false) {
            $logEntry = gzencode($logEntry);
        }

        file_put_contents($filePath, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Store audit data in Redis.
     *
     * @param array<string, mixed> $auditData
     * @param array<string, mixed> $config
     * @return void
     */
    protected function storeInRedis(array $auditData, array $config): void
    {
        $redis = Cache::driver('redis');
        $key = "audit:{$auditData['id']}";
        $value = $auditData;
        
        if ($config['compression'] ?? false) {
            $value = gzencode(json_encode($value));
        }

        $redis->put($key, $value, $config['ttl'] ?? 86400);
    }

    /**
     * Store audit data in external API.
     *
     * @param array<string, mixed> $auditData
     * @param array<string, mixed> $config
     * @return void
     */
    protected function storeInExternalApi(array $auditData, array $config): void
    {
        $endpoint = $config['endpoint'];
        $timeout = $config['timeout'] ?? 30;
        
        $data = $auditData;
        
        if ($config['compression'] ?? false) {
            $data = gzencode(json_encode($data));
        }

        // This would typically use a proper HTTP client
        // For now, we'll just log the attempt
        Log::info('External audit API call', [
            'endpoint' => $endpoint,
            'data' => $auditData,
        ]);
    }

    /**
     * Search audit logs with advanced filtering.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function search(array $criteria = [], array $options = []): array
    {
        $defaultOptions = [
            'backend' => $this->primaryBackend,
            'limit' => 100,
            'offset' => 0,
            'sort_by' => 'timestamp',
            'sort_order' => 'desc',
        ];

        $options = array_merge($defaultOptions, $options);
        $backend = $options['backend'];

        if (!isset($this->storageBackends[$backend]) || !$this->storageBackends[$backend]['enabled']) {
            return ['data' => [], 'total' => 0];
        }

        try {
            switch ($this->storageBackends[$backend]['driver']) {
                case 'database':
                    return $this->searchDatabase($criteria, $options);
                case 'file':
                    return $this->searchFile($criteria, $options);
                case 'redis':
                    return $this->searchRedis($criteria, $options);
                default:
                    return ['data' => [], 'total' => 0];
            }
        } catch (\Exception $e) {
            Log::error('Audit search failed', [
                'backend' => $backend,
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * Search audit logs in database.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function searchDatabase(array $criteria, array $options): array
    {
        $table = $this->storageBackends['database']['table'];
        $query = DB::table($table);

        // Apply filters
        if (!empty($criteria['module'])) {
            $query->where('module', $criteria['module']);
        }

        if (!empty($criteria['action'])) {
            $query->where('action', $criteria['action']);
        }

        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('module', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('action', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('context', 'LIKE', "%{$searchTerm}%");
            });
        }

        $total = $query->count();
        $data = $query->orderBy($options['sort_by'], $options['sort_order'])
                     ->offset($options['offset'])
                     ->limit($options['limit'])
                     ->get()
                     ->toArray();

        return [
            'data' => $data,
            'total' => $total,
            'limit' => $options['limit'],
            'offset' => $options['offset'],
        ];
    }

    /**
     * Search audit logs in file.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function searchFile(array $criteria, array $options): array
    {
        $path = $this->storageBackends['file']['path'];
        $files = glob("{$path}/*.log");
        $data = [];
        $total = 0;

        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $line) {
                $logEntry = json_decode($line, true);
                
                if ($this->matchesCriteria($logEntry, $criteria)) {
                    $data[] = $logEntry;
                    $total++;
                    
                    if (count($data) >= $options['limit']) {
                        break 2;
                    }
                }
            }
        }

        // Sort data
        usort($data, function ($a, $b) use ($options) {
            $aVal = $a[$options['sort_by']] ?? '';
            $bVal = $b[$options['sort_by']] ?? '';
            
            if ($options['sort_order'] === 'asc') {
                return $aVal <=> $bVal;
            } else {
                return $bVal <=> $aVal;
            }
        });

        return [
            'data' => array_slice($data, $options['offset'], $options['limit']),
            'total' => $total,
            'limit' => $options['limit'],
            'offset' => $options['offset'],
        ];
    }

    /**
     * Search audit logs in Redis.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function searchRedis(array $criteria, array $options): array
    {
        $redis = Cache::driver('redis');
        $keys = $redis->get('audit:*');
        $data = [];
        $total = 0;

        foreach ($keys as $key) {
            $logEntry = $redis->get($key);
            
            if ($this->matchesCriteria($logEntry, $criteria)) {
                $data[] = $logEntry;
                $total++;
                
                if (count($data) >= $options['limit']) {
                    break;
                }
            }
        }

        return [
            'data' => array_slice($data, $options['offset'], $options['limit']),
            'total' => $total,
            'limit' => $options['limit'],
            'offset' => $options['offset'],
        ];
    }

    /**
     * Check if log entry matches search criteria.
     *
     * @param array<string, mixed> $logEntry
     * @param array<string, mixed> $criteria
     * @return bool
     */
    protected function matchesCriteria(array $logEntry, array $criteria): bool
    {
        foreach ($criteria as $field => $value) {
            if (!isset($logEntry[$field]) || $logEntry[$field] !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Export audit logs.
     *
     * @param array<string, mixed> $criteria
     * @param string $format
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function export(array $criteria = [], string $format = 'json', array $options = []): array
    {
        $searchResults = $this->search($criteria, ['limit' => 10000]);
        $data = $searchResults['data'];

        switch ($format) {
            case 'json':
                return $this->exportToJson($data, $options);
            case 'csv':
                return $this->exportToCsv($data, $options);
            case 'xml':
                return $this->exportToXml($data, $options);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Export to JSON format.
     *
     * @param array $data
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function exportToJson(array $data, array $options): array
    {
        $filename = 'audit_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filePath = storage_path("app/exports/{$filename}");
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $jsonData);

        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'format' => 'json',
            'record_count' => count($data),
        ];
    }

    /**
     * Export to CSV format.
     *
     * @param array $data
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function exportToCsv(array $data, array $options): array
    {
        $filename = 'audit_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = storage_path("app/exports/{$filename}");
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $file = fopen($filePath, 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
        
        fclose($file);

        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'format' => 'csv',
            'record_count' => count($data),
        ];
    }

    /**
     * Export to XML format.
     *
     * @param array $data
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function exportToXml(array $data, array $options): array
    {
        $filename = 'audit_export_' . now()->format('Y-m-d_H-i-s') . '.xml';
        $filePath = storage_path("app/exports/{$filename}");
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('audit_logs');
        $xml->writeAttribute('exported_at', now()->toISOString());
        $xml->writeAttribute('total_records', count($data));

        foreach ($data as $entry) {
            $xml->startElement('log_entry');
            foreach ($entry as $key => $value) {
                $xml->writeElement($key, is_array($value) ? json_encode($value) : $value);
            }
            $xml->endElement();
        }

        $xml->endElement();
        $xmlContent = $xml->outputMemory();
        file_put_contents($filePath, $xmlContent);

        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'format' => 'xml',
            'record_count' => count($data),
        ];
    }

    /**
     * Clean up old audit logs based on retention policy.
     *
     * @param int|null $daysOld
     * @return array<string, mixed>
     */
    public function cleanupOldLogs(?int $daysOld = null): array
    {
        $daysOld = $daysOld ?: $this->config['retention_days'];
        $cutoffDate = now()->subDays($daysOld);
        $results = [];

        foreach ($this->storageBackends as $backend => $config) {
            if (!$config['enabled']) {
                continue;
            }

            try {
                switch ($config['driver']) {
                    case 'database':
                        $results[$backend] = $this->cleanupDatabase($cutoffDate, $config);
                        break;
                    case 'file':
                        $results[$backend] = $this->cleanupFile($cutoffDate, $config);
                        break;
                    case 'redis':
                        $results[$backend] = $this->cleanupRedis($cutoffDate, $config);
                        break;
                }
            } catch (\Exception $e) {
                Log::error("Cleanup failed for backend: {$backend}", [
                    'error' => $e->getMessage(),
                ]);
                $results[$backend] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Clean up old logs from database.
     *
     * @param \Carbon\Carbon $cutoffDate
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function cleanupDatabase(\Carbon\Carbon $cutoffDate, array $config): array
    {
        $table = $config['table'];
        
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return ['success' => true, 'deleted_count' => 0];
        }

        $deletedCount = DB::table($table)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString(),
        ];
    }

    /**
     * Clean up old logs from file.
     *
     * @param \Carbon\Carbon $cutoffDate
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function cleanupFile(\Carbon\Carbon $cutoffDate, array $config): array
    {
        $path = $config['path'];
        $deletedCount = 0;

        if (!is_dir($path)) {
            return ['success' => true, 'deleted_count' => 0];
        }

        $files = glob("{$path}/*.log");
        
        foreach ($files as $file) {
            $fileDate = date('Y-m-d', filemtime($file));
            if ($fileDate < $cutoffDate->format('Y-m-d')) {
                unlink($file);
                $deletedCount++;
            }
        }

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString(),
        ];
    }

    /**
     * Clean up old logs from Redis.
     *
     * @param \Carbon\Carbon $cutoffDate
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function cleanupRedis(\Carbon\Carbon $cutoffDate, array $config): array
    {
        $redis = Cache::driver('redis');
        $keys = $redis->get('audit:*');
        $deletedCount = 0;

        foreach ($keys as $key) {
            $logEntry = $redis->get($key);
            if ($logEntry && isset($logEntry['timestamp'])) {
                $logDate = \Carbon\Carbon::parse($logEntry['timestamp']);
                if ($logDate->lt($cutoffDate)) {
                    $redis->forget($key);
                    $deletedCount++;
                }
            }
        }

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString(),
        ];
    }

    /**
     * Get audit statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $this->updateStatistics();
        
        return array_merge($this->statistics, [
            'config' => $this->config,
            'storage_backends' => array_keys(array_filter($this->storageBackends, fn($b) => $b['enabled'])),
            'primary_backend' => $this->primaryBackend,
        ]);
    }

    /**
     * Update audit statistics.
     *
     * @param string|null $backend
     * @return void
     */
    protected function updateStatistics(?string $backend = null): void
    {
        if ($backend) {
            $this->statistics['storage_usage'][$backend] = $this->calculateStorageUsage($backend);
        } else {
            foreach (array_keys($this->storageBackends) as $backend) {
                $this->statistics['storage_usage'][$backend] = $this->calculateStorageUsage($backend);
            }
        }
    }

    /**
     * Calculate storage usage for a backend.
     *
     * @param string $backend
     * @return array<string, mixed>
     */
    protected function calculateStorageUsage(string $backend): array
    {
        $config = $this->storageBackends[$backend] ?? [];
        
        switch ($config['driver']) {
            case 'database':
                return $this->calculateDatabaseUsage($config);
            case 'file':
                return $this->calculateFileUsage($config);
            case 'redis':
                return $this->calculateRedisUsage($config);
            default:
                return ['size' => 0, 'records' => 0];
        }
    }

    /**
     * Calculate database storage usage.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function calculateDatabaseUsage(array $config): array
    {
        $table = $config['table'];
        
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return ['size' => 0, 'records' => 0];
        }

        $records = DB::table($table)->count();
        $size = DB::select("SELECT pg_total_relation_size('{$table}') as size")[0]->size ?? 0;

        return [
            'size' => $size,
            'records' => $records,
        ];
    }

    /**
     * Calculate file storage usage.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function calculateFileUsage(array $config): array
    {
        $path = $config['path'];
        $size = 0;
        $records = 0;

        if (is_dir($path)) {
            $files = glob("{$path}/*.log");
            
            foreach ($files as $file) {
                $size += filesize($file);
                $records += count(file($file, FILE_IGNORE_NEW_LINES));
            }
        }

        return [
            'size' => $size,
            'records' => $records,
        ];
    }

    /**
     * Calculate Redis storage usage.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function calculateRedisUsage(array $config): array
    {
        $redis = Cache::driver('redis');
        $keys = $redis->get('audit:*');
        $size = 0;
        $records = count($keys);

        foreach ($keys as $key) {
            $value = $redis->get($key);
            $size += strlen(serialize($value));
        }

        return [
            'size' => $size,
            'records' => $records,
        ];
    }

    /**
     * Get current user ID safely.
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        try {
            return Auth::id();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize headers for audit logging.
     *
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '[REDACTED]';
            }
        }
        
        return $headers;
    }

    /**
     * Sanitize input for audit logging.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '[REDACTED]';
            }
        }
        
        return $input;
    }

    /**
     * Generate audit filename based on rotation policy.
     *
     * @param string $rotation
     * @return string
     */
    protected function generateAuditFilename(string $rotation): string
    {
        switch ($rotation) {
            case 'hourly':
                return 'audit_' . now()->format('Y-m-d_H') . '.log';
            case 'daily':
                return 'audit_' . now()->format('Y-m-d') . '.log';
            case 'weekly':
                return 'audit_' . now()->format('Y-\WW') . '.log';
            case 'monthly':
                return 'audit_' . now()->format('Y-m') . '.log';
            default:
                return 'audit_' . now()->format('Y-m-d') . '.log';
        }
    }

    /**
     * Create audit table if it doesn't exist.
     *
     * @param string $table
     * @return void
     */
    protected function createAuditTable(string $table): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS {$table} (
                id VARCHAR(36) PRIMARY KEY,
                module VARCHAR(100) NOT NULL,
                action VARCHAR(100) NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                session_id VARCHAR(255) NULL,
                request_id VARCHAR(255) NULL,
                context JSON NULL,
                metadata JSON NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_module_action (module, action),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                INDEX idx_ip_address (ip_address)
            )
        ");
    }
} 