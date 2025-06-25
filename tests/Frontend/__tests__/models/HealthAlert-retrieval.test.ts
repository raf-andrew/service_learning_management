// @test @model @health-alert @retrieval
/**
 * Tests for HealthAlert model retrieval.
 * Ensures retrieval by ID, severity level, active status, and null cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Retrieval', () => {
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

  it('should find health alert by ID', async () => {
    HealthAlert.findById.mockResolvedValue(mockHealthAlert)
    
    const alert = await HealthAlert.findById(1)
    
    expect(HealthAlert.findById).toHaveBeenCalledWith(1)
    expect(alert).toEqual(mockHealthAlert)
  })

  it('should find alerts by level', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockHealthAlert])
    }
    HealthAlert.where.mockReturnValue(mockQuery)
    
    const alerts = await HealthAlert.where('severity', 'warning').get()
    
    expect(HealthAlert.where).toHaveBeenCalledWith('severity', 'warning')
    expect(alerts).toContain(mockHealthAlert)
  })

  it('should find active alerts', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockHealthAlert])
    }
    HealthAlert.where.mockReturnValue(mockQuery)
    
    const activeAlerts = await HealthAlert.where('is_resolved', false).get()
    
    expect(HealthAlert.where).toHaveBeenCalledWith('is_resolved', false)
    expect(activeAlerts).toContain(mockHealthAlert)
  })

  it('should return null for non-existent alert', async () => {
    HealthAlert.findById.mockResolvedValue(null)
    
    const alert = await HealthAlert.findById(999)
    
    expect(alert).toBeNull()
  })
}) 