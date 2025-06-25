<?php

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait Exportable
{
    /**
     * Exportable fields for this model
     */
    protected array $exportableFields = [];

    /**
     * Export formats supported
     */
    protected array $exportFormats = ['csv', 'json', 'xml', 'xlsx'];

    /**
     * Export data to CSV
     */
    public function exportToCsv(Builder|Collection $data, array $fields = [], string $filename = null): string
    {
        $fields = empty($fields) ? $this->exportableFields : $fields;
        $filename = $filename ?: $this->generateFilename('csv');
        
        $csvData = $this->prepareCsvData($data, $fields);
        $csvContent = $this->arrayToCsv($csvData);
        
        Storage::put("exports/{$filename}", $csvContent);
        
        return $filename;
    }

    /**
     * Export data to JSON
     */
    public function exportToJson(Builder|Collection $data, array $fields = [], string $filename = null): string
    {
        $fields = empty($fields) ? $this->exportableFields : $fields;
        $filename = $filename ?: $this->generateFilename('json');
        
        $jsonData = $this->prepareJsonData($data, $fields);
        $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT);
        
        Storage::put("exports/{$filename}", $jsonContent);
        
        return $filename;
    }

    /**
     * Export data to XML
     */
    public function exportToXml(Builder|Collection $data, array $fields = [], string $filename = null): string
    {
        $fields = empty($fields) ? $this->exportableFields : $fields;
        $filename = $filename ?: $this->generateFilename('xml');
        
        $xmlData = $this->prepareXmlData($data, $fields);
        $xmlContent = $this->arrayToXml($xmlData);
        
        Storage::put("exports/{$filename}", $xmlContent);
        
        return $filename;
    }

    /**
     * Export data to XLSX
     */
    public function exportToXlsx(Builder|Collection $data, array $fields = [], string $filename = null): string
    {
        $fields = empty($fields) ? $this->exportableFields : $fields;
        $filename = $filename ?: $this->generateFilename('xlsx');
        
        // For XLSX, we'll use a simple CSV approach for now
        // In a real implementation, you'd use a library like PhpSpreadsheet
        return $this->exportToCsv($data, $fields, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Download export file
     */
    public function downloadExport(string $filename, string $format = 'csv'): \Symfony\Component\HttpFoundation\Response
    {
        $path = "exports/{$filename}";
        
        if (!Storage::exists($path)) {
            abort(404, 'Export file not found');
        }

        $content = Storage::get($path);
        $mimeType = $this->getMimeType($format);

        return Response::make($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Prepare data for CSV export
     */
    protected function prepareCsvData(Builder|Collection $data, array $fields): array
    {
        $csvData = [];
        
        // Add headers
        $csvData[] = $fields;
        
        // Add data rows
        if ($data instanceof Builder) {
            $data = $data->get();
        }
        
        foreach ($data as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = $this->getFieldValue($item, $field);
            }
            $csvData[] = $row;
        }
        
        return $csvData;
    }

    /**
     * Prepare data for JSON export
     */
    protected function prepareJsonData(Builder|Collection $data, array $fields): array
    {
        $jsonData = [];
        
        if ($data instanceof Builder) {
            $data = $data->get();
        }
        
        foreach ($data as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $this->getFieldValue($item, $field);
            }
            $jsonData[] = $row;
        }
        
        return $jsonData;
    }

    /**
     * Prepare data for XML export
     */
    protected function prepareXmlData(Builder|Collection $data, array $fields): array
    {
        $xmlData = ['items' => []];
        
        if ($data instanceof Builder) {
            $data = $data->get();
        }
        
        foreach ($data as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $this->getFieldValue($item, $field);
            }
            $xmlData['items'][] = $row;
        }
        
        return $xmlData;
    }

    /**
     * Get field value from model
     */
    protected function getFieldValue($item, string $field): mixed
    {
        if (is_array($item)) {
            return $item[$field] ?? '';
        }
        
        if (is_object($item)) {
            return $item->{$field} ?? '';
        }
        
        return '';
    }

    /**
     * Convert array to CSV
     */
    protected function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Convert array to XML
     */
    protected function arrayToXml(array $data, string $rootElement = 'root'): string
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}></{$rootElement}>");
        
        $this->arrayToXmlRecursive($data, $xml);
        
        return $xml->asXML();
    }

    /**
     * Recursively convert array to XML
     */
    protected function arrayToXmlRecursive(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXmlRecursive($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * Generate filename for export
     */
    protected function generateFilename(string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $modelName = $this->getModelName();
        
        return "{$modelName}_export_{$timestamp}.{$format}";
    }

    /**
     * Get model name for filename
     */
    protected function getModelName(): string
    {
        if (method_exists($this, 'getModel')) {
            $model = $this->getModel();
            return class_basename($model);
        }
        
        if (property_exists($this, 'model')) {
            return class_basename($this->model);
        }
        
        return 'data';
    }

    /**
     * Get MIME type for format
     */
    protected function getMimeType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'text/plain',
        };
    }

    /**
     * Set exportable fields
     */
    public function setExportableFields(array $fields): void
    {
        $this->exportableFields = $fields;
    }

    /**
     * Add exportable field
     */
    public function addExportableField(string $field): void
    {
        if (!in_array($field, $this->exportableFields)) {
            $this->exportableFields[] = $field;
        }
    }

    /**
     * Get exportable fields
     */
    public function getExportableFields(): array
    {
        return $this->exportableFields;
    }

    /**
     * Get supported export formats
     */
    public function getExportFormats(): array
    {
        return $this->exportFormats;
    }

    /**
     * Check if format is supported
     */
    public function isFormatSupported(string $format): bool
    {
        return in_array(strtolower($format), $this->exportFormats);
    }

    /**
     * Export with pagination
     */
    public function exportPaginated(Builder $query, array $fields = [], string $format = 'csv', int $perPage = 1000): array
    {
        $files = [];
        $page = 1;
        
        do {
            $data = $query->paginate($perPage, ['*'], 'page', $page);
            $filename = $this->generateFilename($format);
            $filename = str_replace('.', "_{$page}.", $filename);
            
            $exportMethod = "exportTo" . ucfirst($format);
            $files[] = $this->{$exportMethod}($data->items(), $fields, $filename);
            
            $page++;
        } while ($data->hasMorePages());
        
        return $files;
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $cutoff = now()->subDays($daysOld);
        $deleted = 0;
        
        $files = Storage::files('exports');
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified < $cutoff->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
} 