/**
 * @fileoverview Unit tests for HealthAlert model
 * @tags unit,models,health,alerts
 * @description Tests for HealthAlert model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { HealthAlert } from '../../../src/models/HealthAlert'

describe('HealthAlert Model', () => {
  let healthAlert: HealthAlert

  beforeEach(() => {
    healthAlert = new HealthAlert()
  })

  describe('@unit @models @health @alerts - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(healthAlert.fillable).toContain('title')
      expect(healthAlert.fillable).toContain('message')
      expect(healthAlert.fillable).toContain('level')
      expect(healthAlert.fillable).toContain('status')
      expect(healthAlert.fillable).toContain('resolved_at')
      expect(healthAlert.fillable).toContain('metadata')
      expect(healthAlert.fillable).toContain('service_name')
      expect(healthAlert.fillable).toContain('type')
    })

    it('should have correct table name', () => {
      expect(healthAlert.getTable()).toBe('health_alerts')
    })

    it('should have correct primary key', () => {
      expect(healthAlert.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @health @alerts - Creation', () => {
    it('should create health alert with valid data', () => {
      const data = {
        title: 'Database Connection Failed',
        message: 'Unable to connect to database server',
        level: 'critical',
        status: 'active',
        metadata: { retry_count: 3, last_error: 'Connection timeout' },
        service_name: 'database-service',
        type: 'connection'
      }

      healthAlert.fill(data)
      expect(healthAlert.title).toBe('Database Connection Failed')
      expect(healthAlert.message).toBe('Unable to connect to database server')
      expect(healthAlert.level).toBe('critical')
      expect(healthAlert.status).toBe('active')
      expect(healthAlert.metadata).toEqual({ retry_count: 3, last_error: 'Connection timeout' })
      expect(healthAlert.service_name).toBe('database-service')
      expect(healthAlert.type).toBe('connection')
    })

    it('should handle missing optional fields', () => {
      const data = {
        title: 'Simple Alert'
      }

      healthAlert.fill(data)
      expect(healthAlert.title).toBe('Simple Alert')
      expect(healthAlert.message === null || typeof healthAlert.message === 'undefined').toBe(true)
      expect(healthAlert.level === null || typeof healthAlert.level === 'undefined').toBe(true)
      expect(healthAlert.metadata === null || typeof healthAlert.metadata === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @health @alerts - Validation', () => {
    it('should validate required fields', () => {
      const rules = healthAlert.getValidationRules()
      expect(rules.title).toContain('required')
    })

    it('should validate string fields', () => {
      const rules = healthAlert.getValidationRules()
      expect(rules.title).toContain('string')
      expect(rules.message).toContain('string')
      expect(rules.level).toContain('string')
      expect(rules.status).toContain('string')
      expect(rules.service_name).toContain('string')
      expect(rules.type).toContain('string')
    })

    it('should validate level values', () => {
      const rules = healthAlert.getValidationRules()
      expect(rules.level).toContain('in:info,warning,critical')
    })

    it('should validate array fields', () => {
      const rules = healthAlert.getValidationRules()
      expect(rules.metadata).toContain('array')
    })
  })

  describe('@unit @models @health @alerts - Status Methods', () => {
    it('should check if alert is resolved', () => {
      healthAlert.resolved_at = null
      expect(healthAlert.isResolved()).toBe(false)

      healthAlert.resolved_at = '2023-01-01T00:00:00Z'
      expect(healthAlert.isResolved()).toBe(true)
    })

    it('should check if alert is active', () => {
      healthAlert.resolved_at = null
      expect(healthAlert.isActive()).toBe(true)

      healthAlert.resolved_at = '2023-01-01T00:00:00Z'
      expect(healthAlert.isActive()).toBe(false)
    })
  })

  describe('@unit @models @health @alerts - Level Methods', () => {
    it('should check if alert is critical', () => {
      healthAlert.level = 'critical'
      expect(healthAlert.isCritical()).toBe(true)

      healthAlert.level = 'warning'
      expect(healthAlert.isCritical()).toBe(false)
    })

    it('should check if alert is warning', () => {
      healthAlert.level = 'warning'
      expect(healthAlert.isWarning()).toBe(true)

      healthAlert.level = 'info'
      expect(healthAlert.isWarning()).toBe(false)
    })

    it('should check if alert is info', () => {
      healthAlert.level = 'info'
      expect(healthAlert.isInfo()).toBe(true)

      healthAlert.level = 'warning'
      expect(healthAlert.isInfo()).toBe(false)
    })
  })

  describe('@unit @models @health @alerts - Resolution Methods', () => {
    it('should resolve alert', () => {
      healthAlert.resolved_at = null
      const result = healthAlert.resolve()
      
      expect(result).toBe(true)
      expect(healthAlert.resolved_at).toBeDefined()
      expect(healthAlert.isResolved()).toBe(true)
    })

    it('should not resolve already resolved alert', () => {
      healthAlert.resolved_at = '2023-01-01T00:00:00Z'
      const result = healthAlert.resolve()
      
      expect(result).toBe(false)
    })
  })

  describe('@unit @models @health @alerts - Metadata Methods', () => {
    it('should check if alert has metadata', () => {
      healthAlert.metadata = { key: 'value' }
      expect(healthAlert.hasMetadata()).toBe(true)

      healthAlert.metadata = null
      expect(healthAlert.hasMetadata()).toBe(false)

      healthAlert.metadata = {}
      expect(healthAlert.hasMetadata()).toBe(false)
    })

    it('should get metadata value', () => {
      healthAlert.metadata = { retry_count: 3, error: 'timeout' }
      expect(healthAlert.getMetadataValue('retry_count')).toBe(3)
      expect(healthAlert.getMetadataValue('error')).toBe('timeout')
      expect(healthAlert.getMetadataValue('nonexistent')).toBeNull()
    })

    it('should set metadata value', () => {
      healthAlert.metadata = { retry_count: 1 }
      healthAlert.setMetadataValue('error', 'connection failed')
      expect(healthAlert.metadata).toEqual({ retry_count: 1, error: 'connection failed' })
    })

    it('should create metadata if it does not exist', () => {
      healthAlert.metadata = null
      healthAlert.setMetadataValue('retry_count', 5)
      expect(healthAlert.metadata).toEqual({ retry_count: 5 })
    })
  })

  describe('@unit @models @health @alerts - Time Methods', () => {
    it('should get alert age in seconds', () => {
      const pastDate = new Date()
      pastDate.setHours(pastDate.getHours() - 1)
      healthAlert.created_at = pastDate.toISOString()

      const age = healthAlert.getAlertAge()
      expect(age).toBeGreaterThan(3500) // Should be around 3600 seconds (1 hour)
      expect(age).toBeLessThan(3700)
    })

    it('should get resolution time in seconds', () => {
      const createdDate = new Date()
      createdDate.setHours(createdDate.getHours() - 2)
      const resolvedDate = new Date()
      resolvedDate.setHours(resolvedDate.getHours() - 1)

      healthAlert.created_at = createdDate.toISOString()
      healthAlert.resolved_at = resolvedDate.toISOString()

      const resolutionTime = healthAlert.getResolutionTime()
      expect(resolutionTime).toBeGreaterThan(3500) // Should be around 3600 seconds (1 hour)
      expect(resolutionTime).toBeLessThan(3700)
    })

    it('should return null for resolution time if not resolved', () => {
      healthAlert.created_at = new Date().toISOString()
      healthAlert.resolved_at = null

      const resolutionTime = healthAlert.getResolutionTime()
      expect(resolutionTime).toBeNull()
    })
  })

  describe('@unit @models @health @alerts - Scopes', () => {
    it('should have active scope', () => {
      const activeAlerts = HealthAlert.active()
      expect(activeAlerts).toBeDefined()
      expect(activeAlerts.where).toBeDefined()
    })

    it('should have critical scope', () => {
      const criticalAlerts = HealthAlert.critical()
      expect(criticalAlerts).toBeDefined()
      expect(criticalAlerts.where).toBeDefined()
    })

    it('should have warning scope', () => {
      const warningAlerts = HealthAlert.warning()
      expect(warningAlerts).toBeDefined()
      expect(warningAlerts.where).toBeDefined()
    })

    it('should have of type scope', () => {
      const typeAlerts = HealthAlert.ofType('connection')
      expect(typeAlerts).toBeDefined()
      expect(typeAlerts.where).toBeDefined()
    })

    it('should have for service scope', () => {
      const serviceAlerts = HealthAlert.forService('database-service')
      expect(serviceAlerts).toBeDefined()
      expect(serviceAlerts.where).toBeDefined()
    })

    it('should have unresolved scope', () => {
      const unresolvedAlerts = HealthAlert.unresolved()
      expect(unresolvedAlerts).toBeDefined()
      expect(unresolvedAlerts.where).toBeDefined()
    })

    it('should have resolved scope', () => {
      const resolvedAlerts = HealthAlert.resolved()
      expect(resolvedAlerts).toBeDefined()
      expect(resolvedAlerts.where).toBeDefined()
    })
  })

  describe('@unit @models @health @alerts - Error Handling', () => {
    it('should handle null values gracefully', () => {
      healthAlert.metadata = null
      expect(healthAlert.hasMetadata()).toBe(false)
      expect(healthAlert.getMetadataValue('key')).toBeNull()
    })

    it('should handle undefined values gracefully', () => {
      healthAlert.level = undefined
      expect(healthAlert.isCritical()).toBe(false)
      expect(healthAlert.isWarning()).toBe(false)
      expect(healthAlert.isInfo()).toBe(false)
    })
  })

  describe('@unit @models @health @alerts - Performance', () => {
    it('should handle large metadata objects efficiently', () => {
      const largeMetadata = {}
      for (let i = 0; i < 1000; i++) {
        largeMetadata[`key${i}`] = `value${i}`
      }

      const startTime = performance.now()
      healthAlert.metadata = largeMetadata
      healthAlert.hasMetadata()
      healthAlert.getMetadataValue('key500')
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 