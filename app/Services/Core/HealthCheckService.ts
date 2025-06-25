export interface HealthCheckResult {
    healthy: boolean;
    message?: string;
    details?: Record<string, any>;
}

export interface HealthCheckService {
    performHealthCheck(service: string): Promise<HealthCheckResult>;
    performAllHealthChecks(): Promise<Record<string, HealthCheckResult>>;
} 