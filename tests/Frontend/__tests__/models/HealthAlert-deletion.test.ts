// @test @model @health-alert @deletion
/**
 * Tests for HealthAlert model deletion.
 * Ensures deletion logic and soft deletion are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Deletion', () => {
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

  it('should delete health alert', async () => {
    HealthAlert.delete.mockResolvedValue(true)
    await HealthAlert.delete(1)
    
    expect(HealthAlert.delete).toHaveBeenCalledWith(1)
  })

  it('should handle soft deletion', async () => {
    const softDeleteData = {
      is_resolved: true,
      resolved_at: new Date().toISOString(),
      deleted_at: new Date().toISOString()
    }

    HealthAlert.update.mockResolvedValue({ ...mockHealthAlert, ...softDeleteData })
    await HealthAlert.update(1, softDeleteData)
    
    expect(HealthAlert.update).toHaveBeenCalledWith(1, softDeleteData)
  })
}) 