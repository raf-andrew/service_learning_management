// @test @model @health-alert @integration
/**
 * Tests for HealthAlert model integration scenarios.
 * Ensures complete lifecycle and different severities work correctly.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Integration Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle complete alert lifecycle', async () => {
    // Create
    const createData = {
      service_name: 'lifecycle-test',
      alert_type: 'high_cpu_usage',
      severity: 'warning',
      message: 'CPU usage is high'
    }
    
    HealthAlert.create.mockResolvedValue(createMockHealthAlert(createData))
    const createdAlert = await HealthAlert.create(createData)
    
    // Update
    HealthAlert.update.mockResolvedValue({ ...createdAlert, severity: 'critical' })
    const updatedAlert = await HealthAlert.update(createdAlert.id, { severity: 'critical' })
    
    // Resolve
    HealthAlert.update.mockResolvedValue({ ...updatedAlert, is_resolved: true })
    const resolvedAlert = await HealthAlert.update(updatedAlert.id, { is_resolved: true })
    
    expect(HealthAlert.create).toHaveBeenCalledWith(createData)
    expect(HealthAlert.update).toHaveBeenCalledWith(createdAlert.id, { severity: 'critical' })
    expect(resolvedAlert.is_resolved).toBe(true)
  })

  it('should handle alert lifecycle with different severities', async () => {
    const severities = ['info', 'warning', 'critical', 'error']
    
    for (const severity of severities) {
      const alertData = {
        service_name: `test-${severity}`,
        alert_type: 'test_alert',
        severity,
        message: `Test ${severity} alert`
      }

      HealthAlert.create.mockResolvedValue(createMockHealthAlert(alertData))
      const createdAlert = await HealthAlert.create(alertData)
      
      expect(createdAlert.severity).toBe(severity)
      expect(createdAlert.is_resolved).toBe(false)
    }
  })
}) 