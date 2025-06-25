<?php

namespace App\Modules\Soc2\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Modules\Soc2\Services\ValidationService;
use App\Modules\Soc2\Services\ReportService;
use App\Modules\Soc2\Models\Certification;
use App\Modules\Soc2\Models\ControlAssessment;
use App\Modules\Soc2\Models\RiskAssessment;
use App\Modules\Soc2\Models\AuditLog;
use App\Modules\Soc2\Models\ComplianceReport;

class Soc2ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register SOC2 configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/soc2.php', 'soc2'
        );

        // Register core services
        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService(
                $app->make('App\\Modules\\Shared\\AuditService')
            );
        });

        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService(
                $app->make(ValidationService::class),
                $app->make('App\\Modules\\Shared\\AuditService')
            );
        });

        // Register exception handler
        $this->app->singleton('soc2.exception.handler', function ($app) {
            return function (\Exception $e) {
                \Log::error('SOC2 Exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'error' => 'SOC2 Error',
                    'message' => $e->getMessage()
                ], 500);
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/soc2.php' => config_path('soc2.php'),
        ], 'soc2-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'soc2-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register routes
        $this->registerRoutes();

        // Register policies
        $this->registerPolicies();

        // Register macros
        $this->registerMacros();

        // Register event listeners
        $this->registerEventListeners();

        // Register commands
        $this->registerCommands();

        // Register blade directives
        $this->registerBladeDirectives();

        // Register validation rules
        $this->registerValidationRules();
    }

    /**
     * Register routes
     */
    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->group(function () {
            Route::prefix('soc2')->name('soc2.')->group(function () {
                // Certification routes
                Route::get('/certifications', function () {
                    $certifications = Certification::with(['controlAssessments', 'riskAssessments'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);
                    
                    return response()->json($certifications);
                })->name('certifications.index');

                Route::post('/certifications', function () {
                    $data = request()->validate([
                        'name' => 'required|string|max:255',
                        'certification_type' => 'required|in:Type I,Type II',
                        'start_date' => 'required|date',
                        'end_date' => 'required|date|after:start_date',
                        'auditor_name' => 'required|string|max:255',
                        'scope_description' => 'required|string',
                        'trust_service_criteria' => 'required|array',
                    ]);

                    $certification = Certification::create($data + [
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'certification' => $certification
                    ], 201);
                })->name('certifications.store');

                Route::get('/certifications/{certification}', function (Certification $certification) {
                    return response()->json($certification->load([
                        'controlAssessments',
                        'riskAssessments',
                        'complianceReports',
                        'auditLogs'
                    ]));
                })->name('certifications.show');

                // Control Assessment routes
                Route::post('/certifications/{certification}/control-assessments', function (Certification $certification) {
                    $data = request()->validate([
                        'control_id' => 'required|string',
                        'control_name' => 'required|string|max:255',
                        'control_description' => 'required|string',
                        'control_category' => 'required|in:Security,Availability,Processing Integrity,Confidentiality,Privacy',
                        'assessment_date' => 'required|date',
                        'compliance_score' => 'required|numeric|min:0|max:100',
                        'assessment_status' => 'required|in:compliant,non_compliant,partially_compliant,not_applicable',
                        'assessor_name' => 'required|string|max:255',
                    ]);

                    $assessment = $certification->controlAssessments()->create($data);

                    return response()->json([
                        'success' => true,
                        'assessment' => $assessment
                    ], 201);
                })->name('control-assessments.store');

                // Risk Assessment routes
                Route::post('/certifications/{certification}/risk-assessments', function (Certification $certification) {
                    $data = request()->validate([
                        'risk_id' => 'required|string',
                        'risk_name' => 'required|string|max:255',
                        'risk_description' => 'required|string',
                        'risk_category' => 'required|in:Security,Availability,Processing Integrity,Confidentiality,Privacy',
                        'risk_level' => 'required|in:low,medium,high,critical',
                        'likelihood' => 'required|integer|min:1|max:5',
                        'impact' => 'required|integer|min:1|max:5',
                        'assessment_date' => 'required|date',
                        'assessor_name' => 'required|string|max:255',
                    ]);

                    $data['risk_score'] = $data['likelihood'] * $data['impact'];

                    $assessment = $certification->riskAssessments()->create($data);

                    return response()->json([
                        'success' => true,
                        'assessment' => $assessment
                    ], 201);
                })->name('risk-assessments.store');

                // Compliance Report routes
                Route::post('/certifications/{certification}/compliance-reports', function (Certification $certification) {
                    $data = request()->validate([
                        'report_type' => 'required|in:initial,periodic,final,exception',
                        'report_period_start' => 'required|date',
                        'report_period_end' => 'required|date|after:report_period_start',
                        'report_date' => 'required|date',
                        'executive_summary' => 'required|string',
                        'recommendations' => 'array',
                    ]);

                    $reportService = app(ReportService::class);
                    $report = $reportService->generateComplianceReport($certification, $data);

                    return response()->json([
                        'success' => true,
                        'report' => $report
                    ], 201);
                })->name('compliance-reports.store');

                // Validation routes
                Route::post('/validate/certification/{certification}', function (Certification $certification) {
                    $validationService = app(ValidationService::class);
                    $result = $validationService->validateCertification($certification);
                    
                    return response()->json($result);
                })->name('validate.certification');

                Route::post('/validate/control-assessment/{assessment}', function (ControlAssessment $assessment) {
                    $validationService = app(ValidationService::class);
                    $result = $validationService->validateControlAssessment($assessment);
                    
                    return response()->json($result);
                })->name('validate.control-assessment');

                // System routes
                Route::get('/system/stats', function () {
                    $stats = [
                        'total_certifications' => Certification::count(),
                        'active_certifications' => Certification::active()->count(),
                        'expired_certifications' => Certification::expired()->count(),
                        'total_control_assessments' => ControlAssessment::count(),
                        'compliant_controls' => ControlAssessment::compliant()->count(),
                        'non_compliant_controls' => ControlAssessment::nonCompliant()->count(),
                        'total_risk_assessments' => RiskAssessment::count(),
                        'high_risks' => RiskAssessment::highRisk()->count(),
                        'total_compliance_reports' => ComplianceReport::count(),
                        'approved_reports' => ComplianceReport::approved()->count(),
                        'total_audit_logs' => AuditLog::count(),
                        'compliance_relevant_logs' => AuditLog::complianceRelevant()->count(),
                    ];
                    
                    return response()->json($stats);
                })->name('system.stats');

                Route::get('/system/dashboard', function () {
                    $validationService = app(ValidationService::class);
                    $reportService = app(ReportService::class);
                    
                    $dashboard = [
                        'compliance_overview' => $validationService->getComplianceOverview(),
                        'recent_reports' => $reportService->getRecentReports(),
                        'pending_reviews' => $reportService->getPendingReviews(),
                        'critical_findings' => $validationService->getCriticalFindings(),
                        'upcoming_deadlines' => $validationService->getUpcomingDeadlines(),
                    ];
                    
                    return response()->json($dashboard);
                })->name('system.dashboard');
            });
        });
    }

    /**
     * Register policies
     */
    private function registerPolicies(): void
    {
        Gate::define('soc2.manage', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('soc2.manage');
        });

        Gate::define('soc2.view', function ($user) {
            return $user->hasPermission('soc2.view');
        });

        Gate::define('soc2.create', function ($user) {
            return $user->hasPermission('soc2.create');
        });

        Gate::define('soc2.update', function ($user) {
            return $user->hasPermission('soc2.update');
        });

        Gate::define('soc2.delete', function ($user) {
            return $user->hasPermission('soc2.delete');
        });

        Gate::define('soc2.approve', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('soc2.approve');
        });

        Gate::define('soc2.export', function ($user) {
            return $user->hasPermission('soc2.export');
        });
    }

    /**
     * Register macros
     */
    private function registerMacros(): void
    {
        // Add SOC2 methods to User model
        \Illuminate\Database\Eloquent\Builder::macro('withSoc2Certifications', function () {
            return $this->with('soc2Certifications');
        });

        \Illuminate\Database\Eloquent\Builder::macro('withSoc2AuditLogs', function () {
            return $this->with('soc2AuditLogs');
        });
    }

    /**
     * Register event listeners
     */
    private function registerEventListeners(): void
    {
        // Listen for certification status changes
        Event::listen('eloquent.updated: App\Modules\Soc2\Models\Certification', function ($certification) {
            try {
                $auditService = app('App\\Modules\\Shared\\AuditService');
                $auditService->log('certification_updated', [
                    'certification_id' => $certification->id,
                    'status' => $certification->status,
                    'user_id' => auth()->id(),
                ]);
                
                Log::info('SOC2 certification updated', [
                    'certification_id' => $certification->id,
                    'status' => $certification->status,
                    'user_id' => auth()->id()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log SOC2 certification update', [
                    'certification_id' => $certification->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // Listen for control assessment creation
        Event::listen('eloquent.created: App\Modules\Soc2\Models\ControlAssessment', function ($assessment) {
            try {
                $auditService = app('App\\Modules\\Shared\\AuditService');
                $auditService->log('control_assessment_created', [
                    'assessment_id' => $assessment->id,
                    'certification_id' => $assessment->certification_id,
                    'control_name' => $assessment->control_name,
                    'compliance_score' => $assessment->compliance_score,
                    'user_id' => auth()->id(),
                ]);
                
                Log::info('SOC2 control assessment created', [
                    'assessment_id' => $assessment->id,
                    'control_name' => $assessment->control_name,
                    'compliance_score' => $assessment->compliance_score
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log SOC2 control assessment creation', [
                    'assessment_id' => $assessment->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // Listen for risk assessment creation
        Event::listen('eloquent.created: App\Modules\Soc2\Models\RiskAssessment', function ($assessment) {
            try {
                $auditService = app('App\\Modules\\Shared\\AuditService');
                $auditService->log('risk_assessment_created', [
                    'assessment_id' => $assessment->id,
                    'certification_id' => $assessment->certification_id,
                    'risk_name' => $assessment->risk_name,
                    'risk_level' => $assessment->risk_level,
                    'risk_score' => $assessment->risk_score,
                    'user_id' => auth()->id(),
                ]);
                
                Log::info('SOC2 risk assessment created', [
                    'assessment_id' => $assessment->id,
                    'risk_name' => $assessment->risk_name,
                    'risk_level' => $assessment->risk_level
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log SOC2 risk assessment creation', [
                    'assessment_id' => $assessment->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    /**
     * Register commands
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add SOC2 commands here when created
            ]);
        }
    }

    /**
     * Register blade directives
     */
    private function registerBladeDirectives(): void
    {
        \Blade::directive('soc2Enabled', function () {
            return "<?php echo config('soc2.enabled') ? 'true' : 'false'; ?>";
        });

        \Blade::directive('soc2ComplianceScore', function ($certificationId) {
            return "<?php echo App\\Modules\\Soc2\\Models\\Certification::find({$certificationId})?->getOverallScore() ?? 0; ?>";
        });

        \Blade::directive('soc2CertificationStatus', function ($certificationId) {
            return "<?php echo App\\Modules\\Soc2\\Models\\Certification::find({$certificationId})?->getStatusDisplay() ?? 'Unknown'; ?>";
        });
    }

    /**
     * Register validation rules
     */
    private function registerValidationRules(): void
    {
        \Validator::extend('soc2_compliance_score', function ($attribute, $value, $parameters, $validator) {
            return is_numeric($value) && $value >= 0 && $value <= 100;
        }, 'The :attribute must be a valid SOC2 compliance score between 0 and 100.');

        \Validator::extend('soc2_risk_level', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['low', 'medium', 'high', 'critical']);
        }, 'The :attribute must be a valid SOC2 risk level.');

        \Validator::extend('soc2_certification_type', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['Type I', 'Type II']);
        }, 'The :attribute must be a valid SOC2 certification type.');

        \Validator::extend('soc2_trust_service_criteria', function ($attribute, $value, $validator) {
            if (!is_array($value)) {
                return false;
            }
            
            $validCriteria = ['Security', 'Availability', 'Processing Integrity', 'Confidentiality', 'Privacy'];
            
            foreach ($value as $criterion) {
                if (!in_array($criterion, $validCriteria)) {
                    return false;
                }
            }
            
            return true;
        }, 'The :attribute must contain valid SOC2 trust service criteria.');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ValidationService::class,
            ReportService::class,
            'soc2.exception.handler'
        ];
    }
} 