/**
 * @file HealthMonitorCommand.ts
 * @description Laravel command for monitoring system health
 * @tags health, command, laravel, backend, monitoring
 */

import { HealthMonitoringService, HealthStatus } from '@/app/Services/HealthMonitoringService'
import { HealthCheckService } from '@/app/Services/HealthCheckService'
import { AlertService, AlertType } from '@/app/Services/AlertService'

export class HealthMonitorCommand {
    constructor(
        private readonly healthMonitoringService: HealthMonitoringService,
        private readonly healthCheckService: HealthCheckService,
        private readonly alertService: AlertService
    ) {}

    /**
     * Execute the command
     */
    async handle(options: { service?: string; detailed: boolean }): Promise<number> {
        try {
            console.info('Starting health monitoring...')

            const healthStatus = options.service
                ? await this.healthMonitoringService.checkServiceHealth(options.service)
                : await this.healthMonitoringService.checkAllServices()

            this.displayHealthResults(healthStatus, options.detailed)
            this.handleAlerts(healthStatus)

            return this.determineExitCode(healthStatus)
        } catch (error: unknown) {
            if (error instanceof Error) {
                console.error(`Error: ${error.message}`)
            } else {
                console.error('Unknown error', error)
            }
            return 1;
        }
    }

    /**
     * Display health results in table format
     */
    private displayHealthResults(healthStatus: Record<string, HealthStatus>, showDetailed: boolean): void {
        const rows = Object.entries(healthStatus).map(([service, status]) => ({
            Service: service,
            Status: this.formatStatus(status.healthy),
            'Last Check': status.last_check || 'N/A',
            Details: showDetailed ? (status.details || 'N/A') : 'Use --detailed for more info'
        }))
        console.table(rows)
    }

    /**
     * Format health status for display
     */
    private formatStatus(healthy: boolean): string {
        return healthy ? '✅ Healthy' : '❌ Unhealthy'
    }

    /**
     * Handle alerts for unhealthy services
     */
    private handleAlerts(healthStatus: Record<string, HealthStatus>): void {
        const unhealthyServices = Object.entries(healthStatus)
            .filter(([_, status]) => !status.healthy)
            .map(([service]) => service)

        if (unhealthyServices.length > 0) {
            this.alertService.sendAlert(
                'Health Check Alert',
                `The following services are unhealthy: ${unhealthyServices.join(', ')}`,
                'warning' as AlertType
            )
        }
    }

    /**
     * Determine exit code based on health status
     */
    private determineExitCode(healthStatus: Record<string, HealthStatus>): number {
        return Object.values(healthStatus).every(status => status.healthy) ? 0 : 1
    }
} 