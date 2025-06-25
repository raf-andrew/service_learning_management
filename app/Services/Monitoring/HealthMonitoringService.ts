export interface HealthStatus {
    service: string;
    healthy: boolean;
    last_check?: string;
    details?: string;
}

export interface HealthMonitoringService {
    checkAllServices(): Promise<Record<string, HealthStatus>>;
    checkServiceHealth(service: string): Promise<Record<string, HealthStatus>>;
} 