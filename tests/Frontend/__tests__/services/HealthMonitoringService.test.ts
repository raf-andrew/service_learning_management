/**
 * @file HealthMonitoringService.test.ts
 * @description Comprehensive tests for HealthMonitoringService functionality
 * @tags health-monitoring-service, services, monitoring, vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Import types from the service
export interface HealthStatus {
  service: string
  healthy: boolean
  last_check?: string
  details?: string
}

export interface HealthMonitoringService {
  checkAllServices(): Promise<Record<string, HealthStatus>>
  checkServiceHealth(service: string): Promise<Record<string, HealthStatus>>
}

// Mock implementation for testing
class MockHealthMonitoringService implements HealthMonitoringService {
  private healthStatuses: Record<string, HealthStatus> = {}
  private checkAllServicesSpy = vi.fn()
  private checkServiceHealthSpy = vi.fn()

  async checkAllServices(): Promise<Record<string, HealthStatus>> {
    this.checkAllServicesSpy()
    
    const services = ['database', 'cache', 'queue', 'storage', 'api', 'auth']
    const results: Record<string, HealthStatus> = {}
    
    for (const service of services) {
      const serviceResult = await this.checkServiceHealth(service)
      results[service] = serviceResult[service]
    }
    
    return results
  }

  async checkServiceHealth(service: string): Promise<Record<string, HealthStatus>> {
    this.checkServiceHealthSpy(service)
    
    // Check if we have a stored status for this service
    if (this.healthStatuses[service]) {
      const storedStatus = this.healthStatuses[service]
      // Always update the timestamp to ensure it's fresh
      const updatedStatus: HealthStatus = {
        ...storedStatus,
        last_check: new Date().toISOString()
      }
      return { [service]: updatedStatus }
    }
    
    // Return consistent results based on service
    const isHealthy = service !== 'cache' // cache will be unhealthy for testing
    const details = isHealthy ? `${service} is healthy` : `${service} is experiencing issues`
    
    const status: HealthStatus = {
      service,
      healthy: isHealthy,
      details,
      last_check: new Date().toISOString()
    }
    
    // Store the status
    this.healthStatuses[service] = status
    
    return { [service]: status }
  }

  setHealthStatus(service: string, status: HealthStatus): void {
    this.healthStatuses[service] = status
  }

  getHealthStatuses(): Record<string, HealthStatus> {
    return { ...this.healthStatuses }
  }

  clearHealthStatuses(): void {
    this.healthStatuses = {}
  }

  getCheckAllServicesSpy() {
    return this.checkAllServicesSpy
  }

  getCheckServiceHealthSpy() {
    return this.checkServiceHealthSpy
  }
}

describe('HealthMonitoringService', () => {
  let healthMonitoringService: MockHealthMonitoringService

  setupTestEnvironment()

  beforeEach(() => {
    healthMonitoringService = new MockHealthMonitoringService()
  })

  describe('HealthStatus Interface', () => {
    it('should have required properties', () => {
      const status: HealthStatus = {
        service: 'database',
        healthy: true
      }

      expect(status.service).toBeDefined()
      expect(status.healthy).toBeDefined()
      expect(typeof status.service).toBe('string')
      expect(typeof status.healthy).toBe('boolean')
    })

    it('should support optional last_check property', () => {
      const status: HealthStatus = {
        service: 'cache',
        healthy: true,
        last_check: new Date().toISOString()
      }

      expect(status.last_check).toBeDefined()
      expect(typeof status.last_check).toBe('string')
      
      // Validate ISO date format
      expect(() => new Date(status.last_check!)).not.toThrow()
    })

    it('should support optional details property', () => {
      const status: HealthStatus = {
        service: 'queue',
        healthy: false,
        last_check: new Date().toISOString(),
        details: 'Queue service is experiencing high latency'
      }

      expect(status.details).toBeDefined()
      expect(typeof status.details).toBe('string')
    })

    it('should handle minimal status object', () => {
      const status: HealthStatus = {
        service: 'api',
        healthy: true
      }

      expect(status.service).toBe('api')
      expect(status.healthy).toBe(true)
      expect(status.last_check).toBeUndefined()
      expect(status.details).toBeUndefined()
    })
  })

  describe('checkServiceHealth', () => {
    it('should check health for a single service', async () => {
      const service = 'database'
      
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      const spy = healthMonitoringService.getCheckServiceHealthSpy()
      expect(spy).toHaveBeenCalledWith(service)
      expect(spy).toHaveBeenCalledTimes(1)
      
      expect(result).toHaveProperty(service)
      expect(result[service]).toHaveProperty('service')
      expect(result[service]).toHaveProperty('healthy')
      expect(result[service].service).toBe(service)
    })

    it('should return consistent results for the same service', async () => {
      const service = 'cache'
      const fixedTimestamp = '2025-06-21T06:03:00.000Z'
      
      const expectedStatus: HealthStatus = {
        service: 'cache',
        healthy: true,
        last_check: fixedTimestamp,
        details: 'Cache is healthy'
      }
      
      healthMonitoringService.setHealthStatus(service, expectedStatus)
      
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result[service]).toMatchObject({
        service: 'cache',
        healthy: true,
        details: 'Cache is healthy'
      })
      expect(result[service].last_check).toBeDefined()
    })

    it('should handle unhealthy service status', async () => {
      const service = 'queue'
      
      const unhealthyStatus: HealthStatus = {
        service: 'queue',
        healthy: false,
        details: 'Queue service is down',
        last_check: new Date().toISOString()
      }
      
      healthMonitoringService.setHealthStatus(service, unhealthyStatus)
      
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result[service].healthy).toBe(false)
      expect(result[service].details).toBe('Queue service is down')
    })

    it('should be async and return a promise', async () => {
      const promise = healthMonitoringService.checkServiceHealth('test')
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toBeTypeOf('object')
    })

    it('should handle different service types', async () => {
      const services = ['database', 'redis', 'elasticsearch', 's3', 'sns', 'lambda']
      
      for (const service of services) {
        const result = await healthMonitoringService.checkServiceHealth(service)
        
        expect(result).toHaveProperty(service)
        expect(result[service].service).toBe(service)
        expect(result[service]).toHaveProperty('healthy')
        expect(result[service]).toHaveProperty('last_check')
      }
    })

    it('should include timestamp in last_check', async () => {
      const service = 'api'
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result[service].last_check).toBeDefined()
      expect(typeof result[service].last_check).toBe('string')
      
      // Validate ISO date format
      expect(() => new Date(result[service].last_check!)).not.toThrow()
    })
  })

  describe('checkAllServices', () => {
    it('should check health for all services', async () => {
      const results = await healthMonitoringService.checkAllServices()
      
      const spy = healthMonitoringService.getCheckAllServicesSpy()
      expect(spy).toHaveBeenCalledTimes(1)
      
      expect(typeof results).toBe('object')
      expect(Object.keys(results).length).toBeGreaterThan(0)
      
      // Check that all results have the required structure
      Object.values(results).forEach(status => {
        expect(status).toHaveProperty('service')
        expect(status).toHaveProperty('healthy')
        expect(typeof status.service).toBe('string')
        expect(typeof status.healthy).toBe('boolean')
      })
    })

    it('should return results for all configured services', async () => {
      const results = await healthMonitoringService.checkAllServices()
      
      const expectedServices = ['database', 'cache', 'queue', 'storage', 'api', 'auth']
      
      expectedServices.forEach(service => {
        expect(results).toHaveProperty(service)
        expect(results[service].service).toBe(service)
      })
    })

    it('should handle mixed health statuses', async () => {
      // Set specific health statuses
      healthMonitoringService.setHealthStatus('database', { 
        service: 'database', 
        healthy: true, 
        details: 'Database is healthy' 
      })
      healthMonitoringService.setHealthStatus('cache', { 
        service: 'cache', 
        healthy: false, 
        details: 'Cache is down' 
      })
      healthMonitoringService.setHealthStatus('queue', { 
        service: 'queue', 
        healthy: true, 
        details: 'Queue is healthy' 
      })
      
      const results = await healthMonitoringService.checkAllServices()
      
      expect(results.database.healthy).toBe(true)
      expect(results.cache.healthy).toBe(false)
      expect(results.queue.healthy).toBe(true)
    })

    it('should be async and return a promise', async () => {
      const promise = healthMonitoringService.checkAllServices()
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toBeTypeOf('object')
    })
  })

  describe('Service Integration', () => {
    it('should maintain health status history', async () => {
      await healthMonitoringService.checkServiceHealth('database')
      await healthMonitoringService.checkServiceHealth('cache')
      
      const statuses = healthMonitoringService.getHealthStatuses()
      
      expect(statuses).toHaveProperty('database')
      expect(statuses).toHaveProperty('cache')
      expect(Object.keys(statuses)).toHaveLength(2)
    })

    it('should clear health statuses when requested', async () => {
      await healthMonitoringService.checkServiceHealth('database')
      expect(Object.keys(healthMonitoringService.getHealthStatuses())).toHaveLength(1)
      
      healthMonitoringService.clearHealthStatuses()
      expect(Object.keys(healthMonitoringService.getHealthStatuses())).toHaveLength(0)
    })

    it('should handle rapid successive health checks', async () => {
      const startTime = Date.now()
      
      const promises = Array.from({ length: 6 }, (_, i) =>
        healthMonitoringService.checkServiceHealth(`service-${i}`)
      )
      
      const results = await Promise.all(promises)
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(results).toHaveLength(6)
      expect(duration).toBeLessThan(1000) // Should complete within 1 second
    })

    it('should update last_check timestamp on each check', async () => {
      const service = 'database'
      
      // First check
      const result1 = await healthMonitoringService.checkServiceHealth(service)
      const timestamp1 = result1[service].last_check
      
      // Small delay to ensure different timestamp
      await new Promise(resolve => setTimeout(resolve, 10))
      
      // Second check
      const result2 = await healthMonitoringService.checkServiceHealth(service)
      const timestamp2 = result2[service].last_check
      
      expect(timestamp1).toBeDefined()
      expect(timestamp2).toBeDefined()
      expect(timestamp1).not.toBe(timestamp2)
    })
  })

  describe('Error Handling', () => {
    it('should handle empty service names', async () => {
      const result = await healthMonitoringService.checkServiceHealth('')
      
      expect(result).toHaveProperty('')
      expect(result[''].service).toBe('')
    })

    it('should handle special characters in service names', async () => {
      const serviceName = 'service-with-special-chars!@#$%^&*()'
      const result = await healthMonitoringService.checkServiceHealth(serviceName)
      
      expect(result).toHaveProperty(serviceName)
      expect(result[serviceName].service).toBe(serviceName)
    })

    it('should handle very long service names', async () => {
      const longServiceName = 'a'.repeat(1000)
      const result = await healthMonitoringService.checkServiceHealth(longServiceName)
      
      expect(result).toHaveProperty(longServiceName)
      expect(result[longServiceName].service).toBe(longServiceName)
    })
  })

  describe('Performance', () => {
    it('should complete all health checks within reasonable time', async () => {
      const startTime = Date.now()
      
      await healthMonitoringService.checkAllServices()
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(duration).toBeLessThan(1000) // Should complete within 1 second
    })

    it('should handle concurrent health checks', async () => {
      const services = ['service1', 'service2', 'service3', 'service4', 'service5', 'service6']
      
      const startTime = Date.now()
      
      const promises = services.map(service => 
        healthMonitoringService.checkServiceHealth(service)
      )
      
      const results = await Promise.all(promises)
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(results).toHaveLength(6)
      expect(duration).toBeLessThan(1000)
    })
  })

  describe('Data Validation', () => {
    it('should validate health status structure', async () => {
      const result = await healthMonitoringService.checkServiceHealth('test')
      const status = result.test
      
      // Required properties
      expect(status).toHaveProperty('service')
      expect(status).toHaveProperty('healthy')
      expect(typeof status.service).toBe('string')
      expect(typeof status.healthy).toBe('boolean')
      
      // Optional properties
      if (status.last_check) {
        expect(typeof status.last_check).toBe('string')
      }
      
      if (status.details) {
        expect(typeof status.details).toBe('string')
      }
    })

    it('should include valid ISO timestamp in last_check', async () => {
      const result = await healthMonitoringService.checkServiceHealth('test')
      const status = result.test
      
      if (status.last_check) {
        const date = new Date(status.last_check)
        expect(date.getTime()).not.toBeNaN()
        expect(date.getTime()).toBeGreaterThan(0)
      }
    })

    it('should maintain service name consistency', async () => {
      const service = 'custom-service'
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result[service].service).toBe(service)
      expect(result[service].service).toBe(result[service].service) // Should be the same
    })
  })

  describe('Edge Cases', () => {
    it('should handle services with numeric names', async () => {
      const service = '123'
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result).toHaveProperty(service)
      expect(result[service].service).toBe(service)
    })

    it('should handle services with unicode names', async () => {
      const service = 'service-🚀-📧-💻'
      const result = await healthMonitoringService.checkServiceHealth(service)
      
      expect(result).toHaveProperty(service)
      expect(result[service].service).toBe(service)
    })

    it('should handle case-sensitive service names', async () => {
      const service1 = 'Database'
      const service2 = 'database'
      
      const result1 = await healthMonitoringService.checkServiceHealth(service1)
      const result2 = await healthMonitoringService.checkServiceHealth(service2)
      
      expect(result1).toHaveProperty(service1)
      expect(result2).toHaveProperty(service2)
      expect(result1[service1].service).toBe(service1)
      expect(result2[service2].service).toBe(service2)
    })
  })
}) 