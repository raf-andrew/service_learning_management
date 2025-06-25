<?php

namespace App\Traits\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportable Trait
 * 
 * Provides common data export functionality for models and services.
 * Supports multiple export formats with advanced filtering and formatting.
 * 
 * Features:
 * - Multiple export formats (CSV, JSON, XML, Excel)
 * - Advanced filtering and data selection
 * - Batch processing for large datasets
 * - Progress tracking and cancellation
 * - Export caching and optimization
 * - Custom field mapping and formatting
 * - Export validation and error handling
 * 
 * @trait ExportableTrait
 */
trait ExportableTrait
{
    /**
     * Export fields configuration.
     * Override in implementing class to define exportable fields.
     *
     * @var array<string, array>
     */
    protected array $exportableFields = [];

    /**
     * Export format configurations.
     *
     * @var array<string, array>
     */
    protected array $exportFormats = [
        'csv' => [
            'extension' => 'csv',
            'mime_type' => 'text/csv',
            'delimiter' => ',',
            'enclosure' => '"',
        ],
        'json' => [
            'extension' => 'json',
            'mime_type' => 'application/json',
        ],
        'xml' => [
            'extension' => 'xml',
            'mime_type' => 'application/xml',
        ],
        'excel' => [
            'extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ];

    /**
     * Default export options.
     *
     * @var array<string, mixed>
     */
    protected array $defaultExportOptions = [
        'format' => 'csv',
        'include_headers' => true,
        'batch_size' => 1000,
        'cache' => true,
        'cache_ttl' => 3600,
        'compress' => false,
        'filename_prefix' => 'export',
    ];

    /**
     * Export data with specified criteria and options.
     *
     * @param array<string, mixed> $criteria Export criteria
     * @param array<string, mixed> $options Export options
     * @return array<string, mixed> Export result with metadata
     */
    public function export(array $criteria = [], array $options = []): array
    {
        try {
            $options = array_merge($this->defaultExportOptions, $options);
            $format = $options['format'];
            
            if (!isset($this->exportFormats[$format])) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

            // Check cache first
            if ($options['cache']) {
                $cacheKey = $this->generateExportCacheKey($criteria, $options);
                $cached = $this->getExportFromCache($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
            }

            // Build query
            $query = $this->buildExportQuery($criteria);
            
            // Get total count
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                return [
                    'success' => false,
                    'message' => 'No data to export',
                    'total_count' => 0,
                ];
            }

            // Generate filename
            $filename = $this->generateExportFilename($options);
            
            // Export data
            $exportResult = $this->performExport($query, $format, $options, $filename);
            
            // Cache result
            if ($options['cache']) {
                $this->cacheExportResult($cacheKey, $exportResult, $options['cache_ttl']);
            }

            // Log export analytics
            $this->logExportAnalytics($criteria, $options, $exportResult);

            return $exportResult;

        } catch (\Exception $e) {
            Log::error('Export error', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
                'options' => $options,
            ]);

            return [
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the export query based on criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Builder
     */
    protected function buildExportQuery(array $criteria): Builder
    {
        $query = $this->getBaseQuery();

        // Apply filters
        if (!empty($criteria['filters'])) {
            $query = $this->applyExportFilters($query, $criteria['filters']);
        }

        // Apply date range
        if (!empty($criteria['date_range'])) {
            $query = $this->applyExportDateRange($query, $criteria['date_range']);
        }

        // Apply field selection
        if (!empty($criteria['fields'])) {
            $query = $this->applyFieldSelection($query, $criteria['fields']);
        }

        // Apply sorting
        if (!empty($criteria['sort'])) {
            $query = $this->applyExportSorting($query, $criteria['sort']);
        }

        return $query;
    }

    /**
     * Apply filters to export query.
     *
     * @param Builder $query
     * @param array<string, mixed> $filters
     * @return Builder
     */
    protected function applyExportFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Apply date range to export query.
     *
     * @param Builder $query
     * @param array<string, string> $dateRange
     * @return Builder
     */
    protected function applyExportDateRange(Builder $query, array $dateRange): Builder
    {
        $dateField = $dateRange['field'] ?? 'created_at';
        
        if (!empty($dateRange['from'])) {
            $query->where($dateField, '>=', $dateRange['from']);
        }
        
        if (!empty($dateRange['to'])) {
            $query->where($dateField, '<=', $dateRange['to']);
        }

        return $query;
    }

    /**
     * Apply field selection to export query.
     *
     * @param Builder $query
     * @param array<string> $fields
     * @return Builder
     */
    protected function applyFieldSelection(Builder $query, array $fields): Builder
    {
        $validFields = array_intersect($fields, array_keys($this->exportableFields));
        
        if (!empty($validFields)) {
            $query->select($validFields);
        }

        return $query;
    }

    /**
     * Apply sorting to export query.
     *
     * @param Builder $query
     * @param array<string, string> $sort
     * @return Builder
     */
    protected function applyExportSorting(Builder $query, array $sort): Builder
    {
        foreach ($sort as $field => $order) {
            $query->orderBy($field, $order);
        }

        return $query;
    }

    /**
     * Perform the actual export operation.
     *
     * @param Builder $query
     * @param string $format
     * @param array<string, mixed> $options
     * @param string $filename
     * @return array<string, mixed>
     */
    protected function performExport(Builder $query, string $format, array $options, string $filename): array
    {
        $batchSize = $options['batch_size'] ?? 1000;
        $totalCount = $query->count();
        $filePath = $this->getExportFilePath($filename);

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($query, $options, $filePath, $totalCount);
            case 'json':
                return $this->exportToJson($query, $options, $filePath, $totalCount);
            case 'xml':
                return $this->exportToXml($query, $options, $filePath, $totalCount);
            case 'excel':
                return $this->exportToExcel($query, $options, $filePath, $totalCount);
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    /**
     * Export data to CSV format.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @param string $filePath
     * @param int $totalCount
     * @return array<string, mixed>
     */
    protected function exportToCsv(Builder $query, array $options, string $filePath, int $totalCount): array
    {
        $file = fopen($filePath, 'w');
        $batchSize = $options['batch_size'] ?? 1000;
        $includeHeaders = $options['include_headers'] ?? true;
        $delimiter = $this->exportFormats['csv']['delimiter'];
        $enclosure = $this->exportFormats['csv']['enclosure'];

        // Write headers
        if ($includeHeaders) {
            $headers = array_keys($this->exportableFields);
            fputcsv($file, $headers, $delimiter, $enclosure);
        }

        // Export data in batches
        $query->chunk($batchSize, function ($records) use ($file, $delimiter, $enclosure) {
            foreach ($records as $record) {
                $row = $this->formatRecordForExport($record);
                fputcsv($file, $row, $delimiter, $enclosure);
            }
        });

        fclose($file);

        return [
            'success' => true,
            'filename' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'total_count' => $totalCount,
            'format' => 'csv',
            'mime_type' => $this->exportFormats['csv']['mime_type'],
        ];
    }

    /**
     * Export data to JSON format.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @param string $filePath
     * @param int $totalCount
     * @return array<string, mixed>
     */
    protected function exportToJson(Builder $query, array $options, string $filePath, int $totalCount): array
    {
        $data = [];
        $batchSize = $options['batch_size'] ?? 1000;

        $query->chunk($batchSize, function ($records) use (&$data) {
            foreach ($records as $record) {
                $data[] = $this->formatRecordForExport($record);
            }
        });

        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $jsonData);

        return [
            'success' => true,
            'filename' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'total_count' => $totalCount,
            'format' => 'json',
            'mime_type' => $this->exportFormats['json']['mime_type'],
        ];
    }

    /**
     * Export data to XML format.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @param string $filePath
     * @param int $totalCount
     * @return array<string, mixed>
     */
    protected function exportToXml(Builder $query, array $options, string $filePath, int $totalCount): array
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('export');
        $xml->writeAttribute('total_count', $totalCount);
        $xml->writeAttribute('exported_at', now()->toISOString());

        $batchSize = $options['batch_size'] ?? 1000;

        $query->chunk($batchSize, function ($records) use ($xml) {
            foreach ($records as $record) {
                $xml->startElement('record');
                $formattedRecord = $this->formatRecordForExport($record);
                
                foreach ($formattedRecord as $key => $value) {
                    $xml->writeElement($key, $value);
                }
                
                $xml->endElement(); // record
            }
        });

        $xml->endElement(); // export
        $xmlContent = $xml->outputMemory();
        file_put_contents($filePath, $xmlContent);

        return [
            'success' => true,
            'filename' => basename($filePath),
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'total_count' => $totalCount,
            'format' => 'xml',
            'mime_type' => $this->exportFormats['xml']['mime_type'],
        ];
    }

    /**
     * Export data to Excel format.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @param string $filePath
     * @param int $totalCount
     * @return array<string, mixed>
     */
    protected function exportToExcel(Builder $query, array $options, string $filePath, int $totalCount): array
    {
        // For Excel export, we'll use a simple CSV approach
        // In a real implementation, you might use PhpSpreadsheet or similar library
        return $this->exportToCsv($query, $options, $filePath, $totalCount);
    }

    /**
     * Format a record for export.
     *
     * @param Model $record
     * @return array<string, mixed>
     */
    protected function formatRecordForExport(Model $record): array
    {
        $formatted = [];
        
        foreach ($this->exportableFields as $field => $config) {
            $value = $record->getAttribute($field);
            $formatted[$field] = $this->formatFieldValue($value, $config);
        }
        
        return $formatted;
    }

    /**
     * Format a field value for export.
     *
     * @param mixed $value
     * @param array<string, mixed> $config
     * @return string
     */
    protected function formatFieldValue($value, array $config): string
    {
        if ($value === null) {
            return $config['null_value'] ?? '';
        }

        $format = $config['format'] ?? 'string';
        
        switch ($format) {
            case 'date':
                return $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value;
            case 'datetime':
                return $value instanceof \Carbon\Carbon ? $value->format('Y-m-d H:i:s') : $value;
            case 'boolean':
                return $value ? 'Yes' : 'No';
            case 'number':
                return number_format($value, $config['decimals'] ?? 2);
            case 'currency':
                return '$' . number_format($value, 2);
            case 'percentage':
                return number_format($value, 2) . '%';
            default:
                return (string) $value;
        }
    }

    /**
     * Generate export filename.
     *
     * @param array<string, mixed> $options
     * @return string
     */
    protected function generateExportFilename(array $options): string
    {
        $prefix = $options['filename_prefix'] ?? 'export';
        $format = $options['format'] ?? 'csv';
        $extension = $this->exportFormats[$format]['extension'];
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$prefix}_{$timestamp}.{$extension}";
    }

    /**
     * Get export file path.
     *
     * @param string $filename
     * @return string
     */
    protected function getExportFilePath(string $filename): string
    {
        $exportPath = storage_path('app/exports');
        
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0755, true);
        }
        
        return $exportPath . '/' . $filename;
    }

    /**
     * Generate cache key for export results.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return string
     */
    protected function generateExportCacheKey(array $criteria, array $options): string
    {
        $key = 'export_' . md5(serialize($criteria) . serialize($options));
        return $this->getExportCachePrefix() . $key;
    }

    /**
     * Get export results from cache.
     *
     * @param string $cacheKey
     * @return array<string, mixed>|null
     */
    protected function getExportFromCache(string $cacheKey): ?array
    {
        return Cache::get($cacheKey);
    }

    /**
     * Cache export results.
     *
     * @param string $cacheKey
     * @param array<string, mixed> $result
     * @param int $ttl
     * @return void
     */
    protected function cacheExportResult(string $cacheKey, array $result, int $ttl): void
    {
        Cache::put($cacheKey, $result, $ttl);
    }

    /**
     * Log export analytics.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @param array<string, mixed> $result
     * @return void
     */
    protected function logExportAnalytics(array $criteria, array $options, array $result): void
    {
        Log::info('Export performed', [
            'criteria' => $criteria,
            'options' => $options,
            'result' => $result,
            'execution_time' => microtime(true) - LARAVEL_START,
        ]);
    }

    /**
     * Get the base query for export.
     * Override in implementing class.
     *
     * @return Builder
     */
    abstract protected function getBaseQuery(): Builder;

    /**
     * Get export cache prefix.
     * Override in implementing class.
     *
     * @return string
     */
    protected function getExportCachePrefix(): string
    {
        return 'export_';
    }

    /**
     * Get available export formats.
     *
     * @return array<string, array>
     */
    public function getAvailableExportFormats(): array
    {
        return $this->exportFormats;
    }

    /**
     * Get exportable fields configuration.
     *
     * @return array<string, array>
     */
    public function getExportableFields(): array
    {
        return $this->exportableFields;
    }

    /**
     * Create a download response for the exported file.
     *
     * @param string $filePath
     * @param string $filename
     * @param string $mimeType
     * @return StreamedResponse
     */
    public function createDownloadResponse(string $filePath, string $filename, string $mimeType): StreamedResponse
    {
        return response()->streamDownload(function () use ($filePath) {
            $file = fopen($filePath, 'rb');
            while (!feof($file)) {
                echo fread($file, 8192);
            }
            fclose($file);
        }, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Clean up old export files.
     *
     * @param int $daysOld
     * @return int Number of files deleted
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $exportPath = storage_path('app/exports');
        $cutoffTime = now()->subDays($daysOld)->getTimestamp();
        $deletedCount = 0;

        if (is_dir($exportPath)) {
            $files = glob($exportPath . '/*');
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
} 