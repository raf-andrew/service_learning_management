/**
 * @fileoverview Unit tests for HealthMonitorCommand
 * @tags unit,commands,health,monitoring,laravel,vitest
 * @related app/Console/Commands/HealthMonitorCommand.ts
 * @related app/Services/HealthMonitoringService.ts
 * @related app/Services/HealthCheckService.ts
 * @related app/Services/AlertService.ts
 *
 * Tests the HealthMonitorCommand, including status formatting, exit code determination,
 * alert handling, and health result display. Ensures all logic paths are covered for
 * health monitoring and alerting in the backend command infrastructure.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { HealthMonitorCommand } from '../../../app/Console/Commands/HealthMonitorCommand'
import { HealthMonitoringService, HealthStatus } from '../../../app/Services/HealthMonitoringService'
import { HealthCheckService } from '../../../app/Services/HealthCheckService'
import { AlertService } from '../../../app/Services/AlertService'

describe('HealthMonitorCommand', () => {
    let command: HealthMonitorCommand
    let mockHealthMonitoringService: HealthMonitoringService
    let mockHealthCheckService: HealthCheckService
    let mockAlertService: AlertService

    beforeEach(() => {
        mockHealthMonitoringService = {
            checkAllServices: vi.fn(),
            checkServiceHealth: vi.fn()
        }

        mockHealthCheckService = {
            performHealthCheck: vi.fn(),
            performAllHealthChecks: vi.fn()
        }

        mockAlertService = {
            sendAlert: vi.fn(),
            sendAlertWithOptions: vi.fn()
        }

        command = new HealthMonitorCommand(
            mockHealthMonitoringService,
            mockHealthCheckService,
            mockAlertService
        )

        // Mock console.table
        vi.spyOn(console, 'table').mockImplementation(() => {})
    })

    describe('formatStatus', () => {
        it('formats healthy status correctly', () => {
            expect(command['formatStatus'](true)).toBe('✅ Healthy')
            expect(command['formatStatus'](false)).toBe('❌ Unhealthy')
        })
    })

    describe('determineExitCode', () => {
        it('returns 0 when all services are healthy', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: { service: 'service1', healthy: true },
                service2: { service: 'service2', healthy: true }
            }
            expect(command['determineExitCode'](healthStatus)).toBe(0)
        })

        it('returns 1 when any service is unhealthy', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: { service: 'service1', healthy: true },
                service2: { service: 'service2', healthy: false }
            }
            expect(command['determineExitCode'](healthStatus)).toBe(1)
        })
    })

    describe('handleAlerts', () => {
        it('sends alert for unhealthy services', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: { service: 'service1', healthy: true },
                service2: { service: 'service2', healthy: false },
                service3: { service: 'service3', healthy: false }
            }

            command['handleAlerts'](healthStatus)

            expect(mockAlertService.sendAlert).toHaveBeenCalledWith(
                'Health Check Alert',
                'The following services are unhealthy: service2, service3',
                'warning'
            )
        })

        it('does not send alert when all services are healthy', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: { service: 'service1', healthy: true },
                service2: { service: 'service2', healthy: true }
            }

            command['handleAlerts'](healthStatus)

            expect(mockAlertService.sendAlert).not.toHaveBeenCalled()
        })
    })

    describe('displayHealthResults', () => {
        it('displays health results in table format', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: {
                    service: 'service1',
                    healthy: true,
                    last_check: '2024-03-20 10:00:00',
                    details: 'All systems operational'
                }
            }

            command['displayHealthResults'](healthStatus, false)

            expect(console.table).toHaveBeenCalledWith([
                {
                    Service: 'service1',
                    Status: '✅ Healthy',
                    'Last Check': '2024-03-20 10:00:00',
                    Details: 'Use --detailed for more info'
                }
            ])
        })

        it('shows detailed information when requested', () => {
            const healthStatus: Record<string, HealthStatus> = {
                service1: {
                    service: 'service1',
                    healthy: true,
                    last_check: '2024-03-20 10:00:00',
                    details: 'All systems operational'
                }
            }

            command['displayHealthResults'](healthStatus, true)

            expect(console.table).toHaveBeenCalledWith([
                {
                    Service: 'service1',
                    Status: '✅ Healthy',
                    'Last Check': '2024-03-20 10:00:00',
                    Details: 'All systems operational'
                }
            ])
        })
    })
}) 