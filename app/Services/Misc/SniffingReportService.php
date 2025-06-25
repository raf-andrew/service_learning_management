<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use App\Models\SniffViolation;
use App\Models\SniffResult;
use Carbon\Carbon;

class SniffingReportService
{
    public function generateReport(array $results, string $format = 'html')
    {
        $summary = $this->generateSummary($results);
        $violations = $this->groupViolationsByFile($results['violations']);
        $trends = $this->generateTrends();

        if ($format === 'html') {
            return $this->generateHtmlReport($summary, $violations, $trends);
        }

        return $this->generateJsonReport($summary, $violations, $trends);
    }

    protected function generateSummary(array $results): array
    {
        return [
            'total_files' => $results['total_files'] ?? 0,
            'total_violations' => count($results['violations'] ?? []),
            'files_passed' => ($results['total_files'] ?? 0) - count(array_unique(array_column($results['violations'] ?? [], 'file'))),
            'generated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    protected function groupViolationsByFile(array $violations): array
    {
        $grouped = [];
        foreach ($violations as $violation) {
            $file = $violation['file'];
            if (!isset($grouped[$file])) {
                $grouped[$file] = [];
            }
            $grouped[$file][] = $violation;
        }
        return $grouped;
    }

    protected function generateTrends(): array
    {
        $trends = [];
        $violationTypes = SniffViolation::select('type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $total = $violationTypes->sum('count');

        foreach ($violationTypes as $type) {
            $trends[] = [
                'type' => $type->type,
                'count' => $type->count,
                'percentage' => ($type->count / $total) * 100,
            ];
        }

        return $trends;
    }

    protected function generateHtmlReport(array $summary, array $violations, array $trends): string
    {
        $html = View::make('sniffing.report', [
            'summary' => $summary,
            'violations' => $violations,
            'trends' => $trends,
        ])->render();

        $filename = 'sniffing-report-' . Carbon::now()->format('Y-m-d-His') . '.html';
        Storage::put('sniffing/reports/' . $filename, $html);

        return $filename;
    }

    protected function generateJsonReport(array $summary, array $violations, array $trends): string
    {
        $report = [
            'summary' => $summary,
            'violations' => $violations,
            'trends' => $trends,
        ];

        $filename = 'sniffing-report-' . Carbon::now()->format('Y-m-d-His') . '.json';
        Storage::put('sniffing/reports/' . $filename, json_encode($report, JSON_PRETTY_PRINT));

        return $filename;
    }
} 