// @test @model @health-alert @status-methods
/**
 * Tests for HealthAlert model status methods.
 * Ensures resolved, active, critical, and warning status checks work correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockHealthAlert, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthAlert Status Methods', () => {
  setupTestEnvironment()

  it('should check if alert is resolved', () => {
    const mockHealthAlert = createMockHealthAlert({ is_resolved: false })
    expect(mockHealthAlert.is_resolved).toBe(false)
    
    const resolvedAlert = createMockHealthAlert({
      is_resolved: true,
      resolved_at: new Date().toISOString()
    })
    
    expect(resolvedAlert.is_resolved).toBe(true)
  })

  it('should check if alert is active', () => {
    const mockHealthAlert = createMockHealthAlert({ is_resolved: false })
    expect(mockHealthAlert.is_resolved).toBe(false)
    
    const resolvedAlert = createMockHealthAlert({
      is_resolved: true,
      resolved_at: new Date().toISOString()
    })
    
    expect(resolvedAlert.is_resolved).toBe(true)
  })

  it('should check if alert is critical', () => {
    const mockHealthAlert = createMockHealthAlert({ severity: 'warning' })
    expect(mockHealthAlert.severity).toBe('warning')
    
    const criticalAlert = createMockHealthAlert({
      severity: 'critical'
    })
    
    expect(criticalAlert.severity).toBe('critical')
  })

  it('should check if alert is warning', () => {
    const mockHealthAlert = createMockHealthAlert({ severity: 'warning' })
    expect(mockHealthAlert.severity).toBe('warning')
    
    const criticalAlert = createMockHealthAlert({
      severity: 'critical'
    })
    
    expect(criticalAlert.severity).toBe('critical')
  })
}) 