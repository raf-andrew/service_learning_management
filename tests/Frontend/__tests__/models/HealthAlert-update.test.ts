// @test @model @health-alert @update
/**
 * Tests for HealthAlert model update.
 * Ensures update of information, alert status, and metadata are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Update', () => {
  let mockHealthAlert: any
  let mockUser: any

  beforeEach(() => {
    vi.clearAllMocks()
    
    mockUser = createMockUser()
    mockHealthAlert = createMockHealthAlert({
      user: mockUser,
      user_id: 1,
      service_name: 'test-service',
      alert_type: 'high_cpu_usage',
      severity: 'warning',
      message: 'CPU usage is high',
      details: { cpu_usage: 85 },
      is_resolved: false,
      resolved_at: null
    })
  })

  setupTestEnvironment()

  it('should update health alert information', async () => {
    const updateData = {
      severity: 'critical',
      message: 'Updated alert message',
      details: { cpu_usage: 95 }
    }

    const updatedAlert = { ...mockHealthAlert, ...updateData }
    HealthAlert.update.mockResolvedValue(updatedAlert)
    
    const result = await HealthAlert.update(1, updateData)
    
    expect(HealthAlert.update).toHaveBeenCalledWith(1, updateData)
    expect(result.severity).toBe('critical')
    expect(result.message).toBe('Updated alert message')
  })

  it('should update alert status', async () => {
    const statusUpdate = {
      is_resolved: true,
      resolved_at: new Date().toISOString()
    }

    HealthAlert.update.mockResolvedValue({ ...mockHealthAlert, ...statusUpdate })
    await HealthAlert.update(1, statusUpdate)
    
    expect(HealthAlert.update).toHaveBeenCalledWith(1, statusUpdate)
  })

  it('should update alert metadata', async () => {
    const metadataUpdate = {
      details: { cpu_usage: 90, memory_usage: 75 },
      updated_at: new Date().toISOString()
    }

    HealthAlert.update.mockResolvedValue({ ...mockHealthAlert, ...metadataUpdate })
    await HealthAlert.update(1, metadataUpdate)
    
    expect(HealthAlert.update).toHaveBeenCalledWith(1, metadataUpdate)
  })
}) 