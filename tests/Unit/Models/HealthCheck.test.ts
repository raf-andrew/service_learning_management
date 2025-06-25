/**
 * @fileoverview Unit tests for HealthCheck model
 * @tags unit,models,health,monitoring
 * @description Tests for HealthCheck model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { HealthCheck } from '../../../src/models/HealthCheck'

describe('HealthCheck Model', () => {
  let healthCheck: HealthCheck

  beforeEach(() => {
    healthCheck = new HealthCheck()
  })

  describe('@unit @models @health @monitoring - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(healthCheck.fillable).toContain('name')
      expect(healthCheck.fillable).toContain('type')
      expect(healthCheck.fillable).toContain('target')
      expect(healthCheck.fillable).toContain('config')
      expect(healthCheck.fillable).toContain('timeout')
      expect(healthCheck.fillable).toContain('retry_attempts')
      expect(healthCheck.fillable).toContain('retry_delay')
      expect(healthCheck.fillable).toContain('is_active')
      expect(healthCheck.fillable).toContain('last_check')
      expect(healthCheck.fillable).toContain('status')
      expect(healthCheck.fillable).toContain('message')
    })

    it('should have correct table name', () => {
      expect(healthCheck.getTable()).toBe('health_checks')
    })

    it('should have correct primary key', () => {
      expect(healthCheck.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @health @monitoring - Creation', () => {
    it('should create health check with valid data', () => {
      const data = {
        name: 'database-check',
        type: 'http',
        target: 'https://api.example.com/health',
        config: { method: 'GET', headers: { 'Authorization': 'Bearer token' } },
        timeout: 30,
        retry_attempts: 3,
        retry_delay: 5,
        is_active: true,
        status: 'healthy',
        message: 'Database is responding normally'
      }

      healthCheck.fill(data)
      expect(healthCheck.name).toBe('database-check')
      expect(healthCheck.type).toBe('http')
      expect(healthCheck.target).toBe('https://api.example.com/health')
      expect(healthCheck.config).toEqual({ method: 'GET', headers: { 'Authorization': 'Bearer token' } })
      expect(healthCheck.timeout).toBe(30)
      expect(healthCheck.retry_attempts).toBe(3)
      expect(healthCheck.retry_delay).toBe(5)
      expect(healthCheck.is_active).toBe(true)
      expect(healthCheck.status).toBe('healthy')
      expect(healthCheck.message).toBe('Database is responding normally')
    })

    it('should handle missing optional fields', () => {
      const data = {
        name: 'simple-check'
      }

      healthCheck.fill(data)
      expect(healthCheck.name).toBe('simple-check')
      expect(healthCheck.type === null || typeof healthCheck.type === 'undefined').toBe(true)
      expect(healthCheck.target === null || typeof healthCheck.target === 'undefined').toBe(true)
      expect(healthCheck.config === null || typeof healthCheck.config === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @health @monitoring - Validation', () => {
    it('should validate required fields', () => {
      const rules = healthCheck.getValidationRules()
      expect(rules.name).toContain('required')
    })

    it('should validate string fields', () => {
      const rules = healthCheck.getValidationRules()
      expect(rules.name).toContain('string')
      expect(rules.type).toContain('string')
      expect(rules.target).toContain('string')
      expect(rules.status).toContain('string')
      expect(rules.message).toContain('string')
    })

    it('should validate integer fields', () => {
      const rules = healthCheck.getValidationRules()
      expect(rules.timeout).toContain('integer')
      expect(rules.retry_attempts).toContain('integer')
      expect(rules.retry_delay).toContain('integer')
    })

    it('should validate boolean fields', () => {
      const rules = healthCheck.getValidationRules()
      expect(rules.is_active).toContain('boolean')
    })

    it('should validate array fields', () => {
      const rules = healthCheck.getValidationRules()
      expect(rules.config).toContain('array')
    })
  })

  describe('@unit @models @health @monitoring - Relationships', () => {
    it('should have results relationship', () => {
      expect(healthCheck.results).toBeDefined()
      expect(Array.isArray(healthCheck.results)).toBe(true)
    })

    it('should have metrics relationship', () => {
      expect(healthCheck.metrics).toBeDefined()
      expect(Array.isArray(healthCheck.metrics)).toBe(true)
    })

    it('should have alerts relationship', () => {
      expect(healthCheck.alerts).toBeDefined()
      expect(Array.isArray(healthCheck.alerts)).toBe(true)
    })
  })

  describe('@unit @models @health @monitoring - Status Methods', () => {
    it('should check if health check is active', () => {
      healthCheck.is_active = true
      expect(healthCheck.isActive()).toBe(true)

      healthCheck.is_active = false
      expect(healthCheck.isActive()).toBe(false)
    })

    it('should check if health check is inactive', () => {
      healthCheck.is_active = false
      expect(healthCheck.isInactive()).toBe(true)

      healthCheck.is_active = true
      expect(healthCheck.isInactive()).toBe(false)
    })

    it('should check if health check is healthy', () => {
      healthCheck.status = 'healthy'
      expect(healthCheck.isHealthy()).toBe(true)

      healthCheck.status = 'unhealthy'
      expect(healthCheck.isHealthy()).toBe(false)
    })

    it('should check if health check is unhealthy', () => {
      healthCheck.status = 'unhealthy'
      expect(healthCheck.isUnhealthy()).toBe(true)

      healthCheck.status = 'healthy'
      expect(healthCheck.isUnhealthy()).toBe(false)
    })

    it('should check if health check is warning', () => {
      healthCheck.status = 'warning'
      expect(healthCheck.isWarning()).toBe(true)

      healthCheck.status = 'healthy'
      expect(healthCheck.isWarning()).toBe(false)
    })

    it('should check if health check is critical', () => {
      healthCheck.status = 'critical'
      expect(healthCheck.isCritical()).toBe(true)

      healthCheck.status = 'healthy'
      expect(healthCheck.isCritical()).toBe(false)
    })
  })

  describe('@unit @models @health @monitoring - Configuration Methods', () => {
    it('should check if health check has config', () => {
      healthCheck.config = { method: 'GET' }
      expect(healthCheck.hasConfig()).toBe(true)

      healthCheck.config = null
      expect(healthCheck.hasConfig()).toBe(false)

      healthCheck.config = {}
      expect(healthCheck.hasConfig()).toBe(false)
    })

    it('should get config value', () => {
      healthCheck.config = { method: 'POST', timeout: 60 }
      expect(healthCheck.getConfigValue('method')).toBe('POST')
      expect(healthCheck.getConfigValue('timeout')).toBe(60)
      expect(healthCheck.getConfigValue('nonexistent')).toBeNull()
    })

    it('should set config value', () => {
      healthCheck.config = { method: 'GET' }
      healthCheck.setConfigValue('timeout', 30)
      expect(healthCheck.config).toEqual({ method: 'GET', timeout: 30 })
    })

    it('should create config if it does not exist', () => {
      healthCheck.config = null
      healthCheck.setConfigValue('method', 'GET')
      expect(healthCheck.config).toEqual({ method: 'GET' })
    })
  })

  describe('@unit @models @health @monitoring - Timeout and Retry Methods', () => {
    it('should get timeout with default', () => {
      healthCheck.timeout = null
      expect(healthCheck.getTimeout()).toBe(30)

      healthCheck.timeout = 60
      expect(healthCheck.getTimeout()).toBe(60)
    })

    it('should get retry attempts with default', () => {
      healthCheck.retry_attempts = null
      expect(healthCheck.getRetryAttempts()).toBe(3)

      healthCheck.retry_attempts = 5
      expect(healthCheck.getRetryAttempts()).toBe(5)
    })

    it('should get retry delay with default', () => {
      healthCheck.retry_delay = null
      expect(healthCheck.getRetryDelay()).toBe(5)

      healthCheck.retry_delay = 10
      expect(healthCheck.getRetryDelay()).toBe(10)
    })

    it('should check if health check should retry', () => {
      healthCheck.retry_attempts = 0
      expect(healthCheck.shouldRetry()).toBe(false)

      healthCheck.retry_attempts = 3
      expect(healthCheck.shouldRetry()).toBe(true)
    })
  })

  describe('@unit @models @health @monitoring - Relationship Methods', () => {
    it('should get latest result', () => {
      const mockResults = [{ id: 1, status: 'success' }, { id: 2, status: 'failed' }]
      vi.spyOn(healthCheck, 'results', 'get').mockReturnValue(mockResults)

      const result = healthCheck.getLatestResult()
      expect(result).toEqual({ id: 1, status: 'success' })
    })

    it('should get latest metrics', () => {
      const mockMetrics = [{ id: 1, value: 100 }, { id: 2, value: 200 }]
      vi.spyOn(healthCheck, 'metrics', 'get').mockReturnValue(mockMetrics)

      const metrics = healthCheck.getLatestMetrics()
      expect(metrics).toEqual({ id: 1, value: 100 })
    })

    it('should get active alerts', () => {
      const mockAlerts = [
        { id: 1, resolved_at: null },
        { id: 2, resolved_at: '2023-01-01T00:00:00Z' },
        { id: 3, resolved_at: null }
      ]
      vi.spyOn(healthCheck, 'alerts', 'get').mockReturnValue(mockAlerts)

      const activeAlerts = healthCheck.getActiveAlerts()
      expect(activeAlerts).toHaveLength(2)
      expect(activeAlerts[0].id).toBe(1)
      expect(activeAlerts[1].id).toBe(3)
    })
  })

  describe('@unit @models @health @monitoring - Scopes', () => {
    it('should have active scope', () => {
      const activeChecks = HealthCheck.active()
      expect(activeChecks).toBeDefined()
      expect(activeChecks.where).toBeDefined()
    })

    it('should have inactive scope', () => {
      const inactiveChecks = HealthCheck.inactive()
      expect(inactiveChecks).toBeDefined()
      expect(inactiveChecks.where).toBeDefined()
    })

    it('should have healthy scope', () => {
      const healthyChecks = HealthCheck.healthy()
      expect(healthyChecks).toBeDefined()
      expect(healthyChecks.where).toBeDefined()
    })

    it('should have unhealthy scope', () => {
      const unhealthyChecks = HealthCheck.unhealthy()
      expect(unhealthyChecks).toBeDefined()
      expect(unhealthyChecks.where).toBeDefined()
    })

    it('should have by type scope', () => {
      const typeChecks = HealthCheck.byType('http')
      expect(typeChecks).toBeDefined()
      expect(typeChecks.where).toBeDefined()
    })

    it('should have by status scope', () => {
      const statusChecks = HealthCheck.byStatus('healthy')
      expect(statusChecks).toBeDefined()
      expect(statusChecks.where).toBeDefined()
    })
  })

  describe('@unit @models @health @monitoring - Error Handling', () => {
    it('should handle null values gracefully', () => {
      healthCheck.config = null
      expect(healthCheck.hasConfig()).toBe(false)
      expect(healthCheck.getConfigValue('key')).toBeNull()
    })

    it('should handle undefined values gracefully', () => {
      healthCheck.status = undefined
      expect(healthCheck.isHealthy()).toBe(false)
      expect(healthCheck.isUnhealthy()).toBe(false)
    })
  })

  describe('@unit @models @health @monitoring - Performance', () => {
    it('should handle large config objects efficiently', () => {
      const largeConfig = {}
      for (let i = 0; i < 1000; i++) {
        largeConfig[`key${i}`] = `value${i}`
      }

      const startTime = performance.now()
      healthCheck.config = largeConfig
      healthCheck.hasConfig()
      healthCheck.getConfigValue('key500')
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 