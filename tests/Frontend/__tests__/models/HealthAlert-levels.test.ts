// @test @model @health-alert @levels
/**
 * Tests for HealthAlert model severity levels.
 * Ensures different severity levels and critical level checks work correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockHealthAlert, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthAlert Levels', () => {
  setupTestEnvironment()

  it('should handle different severity levels', () => {
    const severities = ['info', 'warning', 'critical', 'error']
    
    severities.forEach(severity => {
      const alertWithSeverity = createMockHealthAlert({ severity })
      expect(alertWithSeverity.severity).toBe(severity)
    })
  })

  it('should check critical level alerts', () => {
    const criticalAlert = createMockHealthAlert({ severity: 'critical' })
    const warningAlert = createMockHealthAlert({ severity: 'warning' })
    
    expect(criticalAlert.severity).toBe('critical')
    expect(warningAlert.severity).toBe('warning')
  })
}) 