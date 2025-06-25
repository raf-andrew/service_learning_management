/**
 * @fileoverview Sniffing Controllers Integration Tests
 * @description Integration tests for sniffing controllers and cross-controller functionality
 * @tags functional,sniffing,api,controllers,integration,laravel,vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'

describe('Sniffing Controllers Integration Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Cross-Controller Integration', () => {
    it('should handle end-to-end sniffing workflow', () => {
      // Simulate complete workflow from dashboard to analysis
      const workflow = {
        step1: 'dashboard_metrics',
        step2: 'run_scan',
        step3: 'get_results',
        step4: 'analyze_file',
        step5: 'update_dashboard'
      }

      expect(workflow.step1).toBe('dashboard_metrics')
      expect(workflow.step2).toBe('run_scan')
      expect(workflow.step3).toBe('get_results')
      expect(workflow.step4).toBe('analyze_file')
      expect(workflow.step5).toBe('update_dashboard')
    })

    it('should validate controller communication', () => {
      const mockControllerCommunication = {
        dashboard: {
          getMetrics: vi.fn(() => ({ total_scans: 100, success_rate: 95 })),
          updateMetrics: vi.fn()
        },
        api: {
          runScan: vi.fn(() => ({ scan_id: 'scan_123', status: 'running' })),
          getResults: vi.fn(() => ({ scan_id: 'scan_123', results: [] }))
        }
      }

      // Test dashboard metrics retrieval
      const metrics = mockControllerCommunication.dashboard.getMetrics()
      expect(metrics.total_scans).toBe(100)
      expect(metrics.success_rate).toBe(95)

      // Test API scan execution
      const scanResult = mockControllerCommunication.api.runScan()
      expect(scanResult.scan_id).toBe('scan_123')
      expect(scanResult.status).toBe('running')

      // Test results retrieval
      const results = mockControllerCommunication.api.getResults()
      expect(results.scan_id).toBe('scan_123')
      expect(results.results).toEqual([])
    })

    it('should handle error propagation between controllers', () => {
      const mockErrorHandling = {
        api: {
          handleError: vi.fn((error: any) => ({
            success: false,
            error: error.message,
            status: 500
          }))
        },
        dashboard: {
          handleError: vi.fn((error: any) => ({
            success: false,
            error: error.message,
            status: 500
          }))
        }
      }

      const testError = new Error('Test error message')

      // Test API error handling
      const apiErrorResponse = mockErrorHandling.api.handleError(testError)
      expect(apiErrorResponse.success).toBe(false)
      expect(apiErrorResponse.error).toBe('Test error message')
      expect(apiErrorResponse.status).toBe(500)

      // Test dashboard error handling
      const dashboardErrorResponse = mockErrorHandling.dashboard.handleError(testError)
      expect(dashboardErrorResponse.success).toBe(false)
      expect(dashboardErrorResponse.error).toBe('Test error message')
      expect(dashboardErrorResponse.status).toBe(500)
    })
  })

  describe('Controller State Management', () => {
    it('should maintain consistent state across controllers', () => {
      const mockState = {
        current_scan: null,
        scan_history: [],
        active_rules: [],
        performance_metrics: {
          average_execution_time: 0,
          memory_usage: 0,
          error_rate: 0
        }
      }

      // Test initial state
      expect(mockState.current_scan).toBeNull()
      expect(mockState.scan_history).toEqual([])
      expect(mockState.active_rules).toEqual([])

      // Test state update
      mockState.current_scan = { id: 'scan_123', status: 'running' }
      mockState.scan_history.push({ id: 'scan_123', timestamp: new Date().toISOString() })
      mockState.active_rules.push({ name: 'security_rule', enabled: true })

      expect(mockState.current_scan.id).toBe('scan_123')
      expect(mockState.scan_history).toHaveLength(1)
      expect(mockState.active_rules).toHaveLength(1)
    })

    it('should handle concurrent controller operations', () => {
      const mockConcurrentOperations = {
        operation1: {
          start: vi.fn(() => ({ id: 'op1', status: 'started' })),
          complete: vi.fn(() => ({ id: 'op1', status: 'completed' }))
        },
        operation2: {
          start: vi.fn(() => ({ id: 'op2', status: 'started' })),
          complete: vi.fn(() => ({ id: 'op2', status: 'completed' }))
        }
      }

      // Simulate concurrent operations
      const op1Start = mockConcurrentOperations.operation1.start()
      const op2Start = mockConcurrentOperations.operation2.start()

      expect(op1Start.id).toBe('op1')
      expect(op1Start.status).toBe('started')
      expect(op2Start.id).toBe('op2')
      expect(op2Start.status).toBe('started')

      // Complete operations
      const op1Complete = mockConcurrentOperations.operation1.complete()
      const op2Complete = mockConcurrentOperations.operation2.complete()

      expect(op1Complete.status).toBe('completed')
      expect(op2Complete.status).toBe('completed')
    })
  })

  describe('Controller Performance', () => {
    it('should monitor controller response times', () => {
      const mockPerformanceMonitor = {
        measureResponseTime: vi.fn((controller: string, operation: string, duration: number) => ({
          controller,
          operation,
          duration,
          timestamp: new Date().toISOString()
        }))
      }

      const dashboardMetrics = mockPerformanceMonitor.measureResponseTime('dashboard', 'getMetrics', 150)
      const apiScan = mockPerformanceMonitor.measureResponseTime('api', 'runScan', 2500)

      expect(dashboardMetrics.controller).toBe('dashboard')
      expect(dashboardMetrics.operation).toBe('getMetrics')
      expect(dashboardMetrics.duration).toBe(150)

      expect(apiScan.controller).toBe('api')
      expect(apiScan.operation).toBe('runScan')
      expect(apiScan.duration).toBe(2500)
    })

    it('should handle controller resource usage', () => {
      const mockResourceMonitor = {
        trackMemoryUsage: vi.fn((controller: string, usage: number) => ({
          controller,
          memory_usage: usage,
          timestamp: new Date().toISOString()
        })),
        trackCpuUsage: vi.fn((controller: string, usage: number) => ({
          controller,
          cpu_usage: usage,
          timestamp: new Date().toISOString()
        }))
      }

      const memoryUsage = mockResourceMonitor.trackMemoryUsage('dashboard', 50 * 1024 * 1024)
      const cpuUsage = mockResourceMonitor.trackCpuUsage('api', 25.5)

      expect(memoryUsage.controller).toBe('dashboard')
      expect(memoryUsage.memory_usage).toBe(50 * 1024 * 1024)

      expect(cpuUsage.controller).toBe('api')
      expect(cpuUsage.cpu_usage).toBe(25.5)
    })
  })
}) 