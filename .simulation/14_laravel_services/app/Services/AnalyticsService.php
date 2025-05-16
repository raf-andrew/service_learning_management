<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    protected $analyticsEvent;
    protected $analyticsReport;

    public function __construct(AnalyticsEvent $analyticsEvent, AnalyticsReport $analyticsReport)
    {
        $this->analyticsEvent = $analyticsEvent;
        $this->analyticsReport = $analyticsReport;
    }

    public function trackEvent(string $eventType, array $data = [], ?User $user = null)
    {
        try {
            $event = $this->analyticsEvent->create([
                'event_type' => $eventType,
                'user_id' => $user ? $user->id : null,
                'data' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // Cache the event for quick access
            $this->cacheEvent($event);

            return $event;
        } catch (\Exception $e) {
            Log::error('Failed to track event: ' . $e->getMessage());
            throw new \Exception('Failed to track event: ' . $e->getMessage());
        }
    }

    public function generateReport(string $reportType, array $parameters = [])
    {
        try {
            $report = $this->analyticsReport->create([
                'report_type' => $reportType,
                'parameters' => $parameters,
                'status' => 'processing'
            ]);

            // Generate report data based on type
            $data = $this->generateReportData($reportType, $parameters);

            // Update report with generated data
            $report->update([
                'data' => $data,
                'status' => 'completed'
            ]);

            return $report;
        } catch (\Exception $e) {
            Log::error('Failed to generate report: ' . $e->getMessage());
            throw new \Exception('Failed to generate report: ' . $e->getMessage());
        }
    }

    public function getUserAnalytics(User $user, array $filters = [])
    {
        try {
            $query = $this->analyticsEvent->where('user_id', $user->id);

            if (isset($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            return [
                'events' => $query->get(),
                'summary' => $this->generateUserSummary($user, $filters)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user analytics: ' . $e->getMessage());
            throw new \Exception('Failed to get user analytics: ' . $e->getMessage());
        }
    }

    public function getCourseAnalytics(Course $course, array $filters = [])
    {
        try {
            $query = $this->analyticsEvent->where('data->course_id', $course->id);

            if (isset($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            return [
                'events' => $query->get(),
                'summary' => $this->generateCourseSummary($course, $filters)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get course analytics: ' . $e->getMessage());
            throw new \Exception('Failed to get course analytics: ' . $e->getMessage());
        }
    }

    public function getPaymentAnalytics(array $filters = [])
    {
        try {
            $query = Payment::query();

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            return [
                'payments' => $query->get(),
                'summary' => $this->generatePaymentSummary($filters)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get payment analytics: ' . $e->getMessage());
            throw new \Exception('Failed to get payment analytics: ' . $e->getMessage());
        }
    }

    protected function generateReportData(string $reportType, array $parameters)
    {
        switch ($reportType) {
            case 'user_activity':
                return $this->generateUserActivityReport($parameters);
            case 'course_performance':
                return $this->generateCoursePerformanceReport($parameters);
            case 'payment_summary':
                return $this->generatePaymentSummaryReport($parameters);
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }
    }

    protected function generateUserActivityReport(array $parameters)
    {
        $query = $this->analyticsEvent->query();

        if (isset($parameters['user_id'])) {
            $query->where('user_id', $parameters['user_id']);
        }

        if (isset($parameters['start_date'])) {
            $query->where('created_at', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('created_at', '<=', $parameters['end_date']);
        }

        return [
            'total_events' => $query->count(),
            'events_by_type' => $query->groupBy('event_type')
                ->select('event_type', DB::raw('count(*) as count'))
                ->get(),
            'events_by_date' => $query->groupBy(DB::raw('DATE(created_at)'))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->get()
        ];
    }

    protected function generateCoursePerformanceReport(array $parameters)
    {
        $query = $this->analyticsEvent->where('event_type', 'course_interaction');

        if (isset($parameters['course_id'])) {
            $query->where('data->course_id', $parameters['course_id']);
        }

        if (isset($parameters['start_date'])) {
            $query->where('created_at', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('created_at', '<=', $parameters['end_date']);
        }

        return [
            'total_interactions' => $query->count(),
            'interactions_by_type' => $query->groupBy('data->interaction_type')
                ->select('data->interaction_type as type', DB::raw('count(*) as count'))
                ->get(),
            'interactions_by_date' => $query->groupBy(DB::raw('DATE(created_at)'))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->get()
        ];
    }

    protected function generatePaymentSummaryReport(array $parameters)
    {
        $query = Payment::query();

        if (isset($parameters['start_date'])) {
            $query->where('created_at', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('created_at', '<=', $parameters['end_date']);
        }

        return [
            'total_revenue' => $query->where('status', 'completed')->sum('amount'),
            'payments_by_status' => $query->groupBy('status')
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->get(),
            'payments_by_date' => $query->groupBy(DB::raw('DATE(created_at)'))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->get()
        ];
    }

    protected function generateUserSummary(User $user, array $filters)
    {
        $query = $this->analyticsEvent->where('user_id', $user->id);

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_events' => $query->count(),
            'events_by_type' => $query->groupBy('event_type')
                ->select('event_type', DB::raw('count(*) as count'))
                ->get(),
            'last_activity' => $query->latest()->first()
        ];
    }

    protected function generateCourseSummary(Course $course, array $filters)
    {
        $query = $this->analyticsEvent->where('data->course_id', $course->id);

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_interactions' => $query->count(),
            'interactions_by_type' => $query->groupBy('event_type')
                ->select('event_type', DB::raw('count(*) as count'))
                ->get(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'last_activity' => $query->latest()->first()
        ];
    }

    protected function generatePaymentSummary(array $filters)
    {
        $query = Payment::query();

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_revenue' => $query->where('status', 'completed')->sum('amount'),
            'payments_by_status' => $query->groupBy('status')
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->get(),
            'average_payment' => $query->where('status', 'completed')->avg('amount')
        ];
    }

    protected function cacheEvent($event)
    {
        $cacheKey = "analytics_event_{$event->id}";
        Cache::put($cacheKey, $event, now()->addHours(24));
    }
} 