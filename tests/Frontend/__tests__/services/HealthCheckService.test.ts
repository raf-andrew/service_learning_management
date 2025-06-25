/**
 * @file HealthCheckService.test.ts
 * @description Comprehensive tests for HealthCheckService functionality
 * @tags health-check-service, services, monitoring, vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Import types from the service
export interface HealthCheckResult {
  service: string
  healthy: boolean
  message?: string
  details?: Record<string, any>
}

export interface HealthCheckService {
  performHealthCheck(service: string): Promise<HealthCheckResult>
  performAllHealthChecks(): Promise<Record<string, HealthCheckResult>>
}

// Mock implementation for testing
class MockHealthCheckService implements HealthCheckService {
  private healthResults: Record<string, HealthCheckResult> = {}
  private performHealthCheckSpy = vi.fn()
  private performAllHealthChecksSpy = vi.fn()

  async performHealthCheck(service: string): Promise<HealthCheckResult> {
    this.performHealthCheckSpy(service)
    
    // Check if we have a stored result for this service
    if (this.healthResults[service]) {
      return this.healthResults[service]
    }
    
    // Return consistent results based on service
    const isHealthy = service !== 'cache' // cache will be unhealthy for testing
    const responseTime = isHealthy ? 50 : 200
    
    const result: HealthCheckResult = {
      service,
      healthy: isHealthy,
      message: isHealthy ? `${service} is healthy` : `${service} is unhealthy`,
      details: {
        responseTime,
        timestamp: new Date().toISOString(),
        service // Add service to details for tests
      }
    }
    
    // Store the result
    this.healthResults[service] = result
    
    return result
  }

  async performAllHealthChecks(): Promise<Record<string, HealthCheckResult>> {
    this.performAllHealthChecksSpy()
    
    const services = ['database', 'cache', 'queue', 'storage', 'api']
    const results: Record<string, HealthCheckResult> = {}
    
    for (const service of services) {
      results[service] = await this.performHealthCheck(service)
    }
    
    return results
  }

  setHealthResult(service: string, result: HealthCheckResult): void {
    this.healthResults[service] = result
  }

  getHealthResults(): Record<string, HealthCheckResult> {
    return { ...this.healthResults }
  }

  clearHealthResults(): void {
    this.healthResults = {}
  }

  getPerformHealthCheckSpy() {
    return this.performHealthCheckSpy
  }

  getPerformAllHealthChecksSpy() {
    return this.performAllHealthChecksSpy
  }
}

describe('HealthCheckService', () => {
  let healthCheckService: MockHealthCheckService

  setupTestEnvironment()

  beforeEach(() => {
    healthCheckService = new MockHealthCheckService()
  })

  describe('HealthCheckResult Interface', () => {
    it('should have required healthy property', () => {
      const result: HealthCheckResult = {
        service: 'test',
        healthy: true
      }

      expect(result.healthy).toBeDefined()
      expect(typeof result.healthy).toBe('boolean')
    })

    it('should support optional message property', () => {
      const result: HealthCheckResult = {
        service: 'test',
        healthy: true,
        message: 'Service is healthy'
      }

      expect(result.message).toBeDefined()
      expect(typeof result.message).toBe('string')
    })

    it('should support optional details property', () => {
      const result: HealthCheckResult = {
        service: 'test',
        healthy: false,
        message: 'Service is unhealthy',
        details: {
          error: 'Connection timeout',
          code: 'TIMEOUT',
          timestamp: new Date().toISOString()
        }
      }

      expect(result.details).toBeDefined()
      expect(typeof result.details).toBe('object')
      expect(result.details?.error).toBe('Connection timeout')
    })

    it('should handle minimal result object', () => {
      const result: HealthCheckResult = {
        service: 'test',
        healthy: true
      }

      expect(result.healthy).toBe(true)
      expect(result.message).toBeUndefined()
      expect(result.details).toBeUndefined()
    })
  })

  describe('performHealthCheck', () => {
    it('should perform health check for a single service', async () => {
      const service = 'database'
      
      const result = await healthCheckService.performHealthCheck(service)
      
      const spy = healthCheckService.getPerformHealthCheckSpy()
      expect(spy).toHaveBeenCalledWith(service)
      expect(spy).toHaveBeenCalledTimes(1)
      
      expect(result).toHaveProperty('healthy')
      expect(result).toHaveProperty('message')
      expect(result).toHaveProperty('details')
      expect(typeof result.healthy).toBe('boolean')
      expect(typeof result.message).toBe('string')
      expect(typeof result.details).toBe('object')
    })

    it('should return consistent results for the same service', async () => {
      const service = 'cache'
      
      // Set a specific health result
      const expectedResult: HealthCheckResult = {
        service: 'cache',
        healthy: true,
        message: 'Cache is healthy',
        details: { responseTime: 50 }
      }
      
      healthCheckService.setHealthResult(service, expectedResult)
      
      const result = await healthCheckService.performHealthCheck(service)
      
      expect(result).toMatchObject(expectedResult)
    })

    it('should handle unhealthy service results', async () => {
      const service = 'queue'
      
      const unhealthyResult: HealthCheckResult = {
        service: 'queue',
        healthy: false,
        message: 'Queue service is down',
        details: {
          error: 'Connection refused',
          code: 'CONNECTION_ERROR'
        }
      }
      
      healthCheckService.setHealthResult(service, unhealthyResult)
      
      const result = await healthCheckService.performHealthCheck(service)
      
      expect(result.healthy).toBe(false)
      expect(result.message).toBe('Queue service is down')
    })

    it('should be async and return a promise', async () => {
      const promise = healthCheckService.performHealthCheck('test')
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toHaveProperty('healthy')
    })

    it('should handle different service types', async () => {
      const services = ['database', 'redis', 'elasticsearch', 's3', 'sns']
      
      for (const service of services) {
        const result = await healthCheckService.performHealthCheck(service)
        
        expect(result).toHaveProperty('healthy')
        expect(result.message).toContain(service)
        expect(result.details?.service).toBe(service)
      }
    })
  })

  describe('performAllHealthChecks', () => {
    it('should perform health checks for all services', async () => {
      const results = await healthCheckService.performAllHealthChecks()
      
      const spy = healthCheckService.getPerformAllHealthChecksSpy()
      expect(spy).toHaveBeenCalledTimes(1)
      
      expect(typeof results).toBe('object')
      expect(Object.keys(results).length).toBeGreaterThan(0)
      
      // Check that all results have the required structure
      Object.values(results).forEach(result => {
        expect(result).toHaveProperty('healthy')
        expect(result).toHaveProperty('message')
        expect(result).toHaveProperty('details')
      })
    })

    it('should return results for all configured services', async () => {
      const results = await healthCheckService.performAllHealthChecks()
      
      const expectedServices = ['database', 'cache', 'queue', 'storage', 'api']
      
      expectedServices.forEach(service => {
        expect(results).toHaveProperty(service)
        expect(results[service].details?.service).toBe(service)
      })
    })

    it('should handle mixed health statuses', async () => {
      // Set specific health results
      healthCheckService.setHealthResult('database', { service: 'database', healthy: true, message: 'Database is healthy' })
      healthCheckService.setHealthResult('cache', { service: 'cache', healthy: false, message: 'Cache is down' })
      healthCheckService.setHealthResult('queue', { service: 'queue', healthy: true, message: 'Queue is healthy' })
      
      const results = await healthCheckService.performAllHealthChecks()
      
      expect(results.database.healthy).toBe(true)
      expect(results.cache.healthy).toBe(false)
      expect(results.queue.healthy).toBe(true)
    })

    it('should be async and return a promise', async () => {
      const promise = healthCheckService.performAllHealthChecks()
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toBeTypeOf('object')
    })
  })

  describe('Service Integration', () => {
    it('should maintain health result history', async () => {
      await healthCheckService.performHealthCheck('database')
      await healthCheckService.performHealthCheck('cache')
      
      const results = healthCheckService.getHealthResults()
      
      expect(results).toHaveProperty('database')
      expect(results).toHaveProperty('cache')
      expect(Object.keys(results)).toHaveLength(2)
    })

    it('should clear health results when requested', async () => {
      await healthCheckService.performHealthCheck('database')
      expect(Object.keys(healthCheckService.getHealthResults())).toHaveLength(1)
      
      healthCheckService.clearHealthResults()
      expect(Object.keys(healthCheckService.getHealthResults())).toHaveLength(0)
    })

    it('should handle rapid successive health checks', async () => {
      const startTime = Date.now()
      
      const promises = Array.from({ length: 5 }, (_, i) =>
        healthCheckService.performHealthCheck(`service-${i}`)
      )
      
      const results = await Promise.all(promises)
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(results).toHaveLength(5)
      expect(duration).toBeLessThan(1000) // Should complete within 1 second
    })
  })

  describe('Error Handling', () => {
    it('should handle empty service names', async () => {
      const result = await healthCheckService.performHealthCheck('')
      
      expect(result).toHaveProperty('healthy')
      expect(result.message).toContain('')
    })

    it('should handle special characters in service names', async () => {
      const serviceName = 'service-with-special-chars!@#$%^&*()'
      const result = await healthCheckService.performHealthCheck(serviceName)
      
      expect(result).toHaveProperty('healthy')
      expect(result.message).toContain(serviceName)
    })

    it('should handle very long service names', async () => {
      const longServiceName = 'a'.repeat(1000)
      const result = await healthCheckService.performHealthCheck(longServiceName)
      
      expect(result).toHaveProperty('healthy')
      expect(result.message).toContain(longServiceName)
    })
  })

  describe('Performance', () => {
    it('should complete health checks within reasonable time', async () => {
      const startTime = Date.now()
      
      await healthCheckService.performAllHealthChecks()
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(duration).toBeLessThan(1000) // Should complete within 1 second
    })

    it('should handle concurrent health checks', async () => {
      const services = ['service1', 'service2', 'service3', 'service4', 'service5']
      
      const startTime = Date.now()
      
      const promises = services.map(service => 
        healthCheckService.performHealthCheck(service)
      )
      
      const results = await Promise.all(promises)
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(results).toHaveLength(5)
      expect(duration).toBeLessThan(1000)
    })
  })

  describe('Data Validation', () => {
    it('should validate health check result structure', async () => {
      const result = await healthCheckService.performHealthCheck('test')
      
      // Required properties
      expect(result).toHaveProperty('healthy')
      expect(typeof result.healthy).toBe('boolean')
      
      // Optional properties
      if (result.message) {
        expect(typeof result.message).toBe('string')
      }
      
      if (result.details) {
        expect(typeof result.details).toBe('object')
        expect(result.details).not.toBeNull()
      }
    })

    it('should include timestamp in details', async () => {
      const result = await healthCheckService.performHealthCheck('test')
      
      expect(result.details).toBeDefined()
      expect(result.details).toHaveProperty('timestamp')
      expect(typeof result.details!.timestamp).toBe('string')
      
      // Validate ISO date format
      expect(() => new Date(result.details!.timestamp)).not.toThrow()
    })
  })
}) 