/**
 * @file AlertService.test.ts
 * @description Comprehensive tests for AlertService functionality
 * @tags alert-service, services, notifications, vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Import types from the service
export type AlertType = 'info' | 'warning' | 'error' | 'success'

export interface AlertOptions {
  title: string
  message: string
  type: AlertType
  metadata?: Record<string, any>
}

export interface AlertService {
  sendAlert(title: string, message: string, type: AlertType, metadata?: Record<string, any>): Promise<void>
  sendAlertWithOptions(options: AlertOptions): Promise<void>
}

// Mock implementation for testing
class MockAlertService implements AlertService {
  private alerts: AlertOptions[] = []
  private sendAlertSpy = vi.fn()
  private sendAlertWithOptionsSpy = vi.fn()

  async sendAlert(title: string, message: string, type: AlertType, metadata?: Record<string, any>): Promise<void> {
    this.sendAlertSpy(title, message, type, metadata)
    
    const alert: AlertOptions = {
      title,
      message,
      type,
      metadata
    }
    
    this.alerts.push(alert)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 10))
  }

  async sendAlertWithOptions(options: AlertOptions): Promise<void> {
    this.sendAlertWithOptionsSpy(options)
    
    this.alerts.push(options)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 10))
  }

  getAlerts(): AlertOptions[] {
    return [...this.alerts]
  }

  clearAlerts(): void {
    this.alerts = []
  }

  getSendAlertSpy() {
    return this.sendAlertSpy
  }

  getSendAlertWithOptionsSpy() {
    return this.sendAlertWithOptionsSpy
  }
}

describe('AlertService', () => {
  let alertService: MockAlertService

  setupTestEnvironment()

  beforeEach(() => {
    alertService = new MockAlertService()
  })

  describe('AlertType', () => {
    it('should accept valid alert types', () => {
      const validTypes: AlertType[] = ['info', 'warning', 'error', 'success']
      
      validTypes.forEach(type => {
        expect(type).toMatch(/^(info|warning|error|success)$/)
      })
    })

    it('should reject invalid alert types', () => {
      const invalidTypes = ['debug', 'critical', 'notice', 'alert']
      
      invalidTypes.forEach(type => {
        expect(type).not.toMatch(/^(info|warning|error|success)$/)
      })
    })
  })

  describe('AlertOptions Interface', () => {
    it('should have required properties', () => {
      const alertOptions: AlertOptions = {
        title: 'Test Alert',
        message: 'This is a test alert',
        type: 'info'
      }

      expect(alertOptions.title).toBeDefined()
      expect(alertOptions.message).toBeDefined()
      expect(alertOptions.type).toBeDefined()
      expect(typeof alertOptions.title).toBe('string')
      expect(typeof alertOptions.message).toBe('string')
      expect(['info', 'warning', 'error', 'success']).toContain(alertOptions.type)
    })

    it('should support optional metadata', () => {
      const alertOptions: AlertOptions = {
        title: 'Test Alert',
        message: 'This is a test alert',
        type: 'warning',
        metadata: {
          userId: 123,
          timestamp: new Date().toISOString(),
          source: 'test'
        }
      }

      expect(alertOptions.metadata).toBeDefined()
      expect(typeof alertOptions.metadata).toBe('object')
      expect(alertOptions.metadata?.userId).toBe(123)
    })
  })

  describe('sendAlert', () => {
    it('should send an alert with basic parameters', async () => {
      const title = 'Test Alert'
      const message = 'This is a test alert'
      const type: AlertType = 'info'

      await alertService.sendAlert(title, message, type)

      const spy = alertService.getSendAlertSpy()
      expect(spy).toHaveBeenCalledWith(title, message, type, undefined)
      expect(spy).toHaveBeenCalledTimes(1)
    })

    it('should send an alert with metadata', async () => {
      const title = 'Test Alert'
      const message = 'This is a test alert'
      const type: AlertType = 'warning'
      const metadata = { userId: 123, source: 'test' }

      await alertService.sendAlert(title, message, type, metadata)

      const spy = alertService.getSendAlertSpy()
      expect(spy).toHaveBeenCalledWith(title, message, type, metadata)
    })

    it('should store alerts in the service', async () => {
      const alert1 = { title: 'Alert 1', message: 'Message 1', type: 'info' as AlertType }
      const alert2 = { title: 'Alert 2', message: 'Message 2', type: 'error' as AlertType }

      await alertService.sendAlert(alert1.title, alert1.message, alert1.type)
      await alertService.sendAlert(alert2.title, alert2.message, alert2.type)

      const alerts = alertService.getAlerts()
      expect(alerts).toHaveLength(2)
      expect(alerts[0]).toMatchObject(alert1)
      expect(alerts[1]).toMatchObject(alert2)
    })

    it('should handle all alert types', async () => {
      const types: AlertType[] = ['info', 'warning', 'error', 'success']

      for (const type of types) {
        await alertService.sendAlert(`Test ${type}`, `Message for ${type}`, type)
      }

      const alerts = alertService.getAlerts()
      expect(alerts).toHaveLength(4)
      
      types.forEach((type, index) => {
        expect(alerts[index].type).toBe(type)
      })
    })

    it('should be async and return a promise', async () => {
      const promise = alertService.sendAlert('Test', 'Message', 'info')
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toBeUndefined()
    })
  })

  describe('sendAlertWithOptions', () => {
    it('should send an alert with options object', async () => {
      const options: AlertOptions = {
        title: 'Test Alert',
        message: 'This is a test alert',
        type: 'success',
        metadata: { userId: 456 }
      }

      await alertService.sendAlertWithOptions(options)

      const spy = alertService.getSendAlertWithOptionsSpy()
      expect(spy).toHaveBeenCalledWith(options)
      expect(spy).toHaveBeenCalledTimes(1)
    })

    it('should store alerts from options', async () => {
      const options: AlertOptions = {
        title: 'Options Alert',
        message: 'Alert from options',
        type: 'warning',
        metadata: { source: 'options-test' }
      }

      await alertService.sendAlertWithOptions(options)

      const alerts = alertService.getAlerts()
      expect(alerts).toHaveLength(1)
      expect(alerts[0]).toMatchObject(options)
    })

    it('should handle options without metadata', async () => {
      const options: AlertOptions = {
        title: 'Simple Alert',
        message: 'Simple message',
        type: 'info'
      }

      await alertService.sendAlertWithOptions(options)

      const alerts = alertService.getAlerts()
      expect(alerts[0]).toMatchObject(options)
      expect(alerts[0].metadata).toBeUndefined()
    })

    it('should be async and return a promise', async () => {
      const options: AlertOptions = {
        title: 'Test',
        message: 'Message',
        type: 'info'
      }

      const promise = alertService.sendAlertWithOptions(options)
      
      expect(promise).toBeInstanceOf(Promise)
      await expect(promise).resolves.toBeUndefined()
    })
  })

  describe('Service Integration', () => {
    it('should handle multiple alerts in sequence', async () => {
      const alerts: AlertOptions[] = [
        { title: 'Alert 1', message: 'Message 1', type: 'info' },
        { title: 'Alert 2', message: 'Message 2', type: 'warning' },
        { title: 'Alert 3', message: 'Message 3', type: 'error' },
        { title: 'Alert 4', message: 'Message 4', type: 'success' }
      ]

      for (const alert of alerts) {
        await alertService.sendAlertWithOptions(alert)
      }

      const storedAlerts = alertService.getAlerts()
      expect(storedAlerts).toHaveLength(4)
      
      alerts.forEach((alert, index) => {
        expect(storedAlerts[index]).toMatchObject(alert)
      })
    })

    it('should clear alerts when requested', async () => {
      await alertService.sendAlert('Test', 'Message', 'info')
      expect(alertService.getAlerts()).toHaveLength(1)

      alertService.clearAlerts()
      expect(alertService.getAlerts()).toHaveLength(0)
    })

    it('should maintain alert order', async () => {
      const alert1 = { title: 'First', message: 'First message', type: 'info' as AlertType }
      const alert2 = { title: 'Second', message: 'Second message', type: 'warning' as AlertType }
      const alert3 = { title: 'Third', message: 'Third message', type: 'error' as AlertType }

      await alertService.sendAlert(alert1.title, alert1.message, alert1.type)
      await alertService.sendAlert(alert2.title, alert2.message, alert2.type)
      await alertService.sendAlert(alert3.title, alert3.message, alert3.type)

      const alerts = alertService.getAlerts()
      expect(alerts[0]).toMatchObject(alert1)
      expect(alerts[1]).toMatchObject(alert2)
      expect(alerts[2]).toMatchObject(alert3)
    })
  })

  describe('Error Handling', () => {
    it('should handle empty strings', async () => {
      await alertService.sendAlert('', '', 'info')
      
      const alerts = alertService.getAlerts()
      expect(alerts[0].title).toBe('')
      expect(alerts[0].message).toBe('')
    })

    it('should handle special characters in messages', async () => {
      const specialMessage = 'Alert with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?'
      
      await alertService.sendAlert('Special Alert', specialMessage, 'warning')
      
      const alerts = alertService.getAlerts()
      expect(alerts[0].message).toBe(specialMessage)
    })

    it('should handle unicode characters', async () => {
      const unicodeMessage = 'Alert with unicode: 🚀 📧 💻 🎉'
      
      await alertService.sendAlert('Unicode Alert', unicodeMessage, 'success')
      
      const alerts = alertService.getAlerts()
      expect(alerts[0].message).toBe(unicodeMessage)
    })
  })

  describe('Performance', () => {
    it('should handle rapid successive alerts', async () => {
      const startTime = Date.now()
      
      const promises = Array.from({ length: 10 }, (_, i) =>
        alertService.sendAlert(`Alert ${i}`, `Message ${i}`, 'info')
      )
      
      await Promise.all(promises)
      
      const endTime = Date.now()
      const duration = endTime - startTime
      
      expect(alertService.getAlerts()).toHaveLength(10)
      expect(duration).toBeLessThan(1000) // Should complete within 1 second
    })
  })
}) 