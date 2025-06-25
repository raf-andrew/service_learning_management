<?php

namespace App\Repositories\Sniffing;

use App\Models\Sniffing\SniffResult;
use App\Models\Sniffing\SniffViolation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SniffResultRepository
{
    public function store(array $data): SniffResult
    {
        return DB::transaction(function () use ($data) {
            $result = SniffResult::create($data);
            
            if (isset($data['violations'])) {
                foreach ($data['violations'] as $violation) {
                    $result->violations()->create($violation);
                }
            }
            
            return $result;
        });
    }

    public function getAll(): Collection
    {
        return SniffResult::with('violations')->get();
    }

    public function getByFile(string $filePath): Collection
    {
        return SniffResult::with('violations')
            ->where('file_path', $filePath)
            ->get();
    }

    public function clearAll(): void
    {
        DB::transaction(function () {
            SniffViolation::truncate();
            SniffResult::truncate();
        });
    }

    public function clearByFile(string $filePath): void
    {
        DB::transaction(function () use ($filePath) {
            $results = SniffResult::where('file_path', $filePath)->get();
            foreach ($results as $result) {
                $result->violations()->delete();
                $result->delete();
            }
        });
    }

    public function getLatestResults(int $limit = 10): Collection
    {
        return SniffResult::with('violations')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getResultsByDateRange(string $startDate, string $endDate): Collection
    {
        return SniffResult::with('violations')
            ->whereBetween('sniff_date', [$startDate, $endDate])
            ->get();
    }

    public function getResultsBySeverity(string $severity): Collection
    {
        return SniffResult::with('violations')
            ->whereHas('violations', function ($query) use ($severity) {
                $query->where('severity', $severity);
            })
            ->get();
    }

    public function getStatistics(): array
    {
        return [
            'total_results' => SniffResult::count(),
            'total_violations' => SniffViolation::count(),
            'error_count' => SniffViolation::where('severity', 'error')->count(),
            'warning_count' => SniffViolation::where('severity', 'warning')->count(),
            'fixable_count' => SniffViolation::where('fixable', true)->count(),
            'fixed_count' => SniffViolation::where('fix_applied', true)->count(),
            'files_analyzed' => SniffResult::distinct('file_path')->count(),
        ];
    }

    public function getTrendData(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return SniffResult::select(
            DB::raw('DATE(sniff_date) as date'),
            DB::raw('COUNT(*) as total_runs'),
            DB::raw('SUM(error_count) as total_errors'),
            DB::raw('SUM(warning_count) as total_warnings')
        )
        ->where('sniff_date', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->toArray();
    }
}
