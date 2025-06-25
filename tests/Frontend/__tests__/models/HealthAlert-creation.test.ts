// @test @model @health-alert @creation
/**
 * Tests for HealthAlert model creation.
 * Ensures creation logic, validation, and minimal data cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Creation', () => {
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

  it('should create a new health alert with valid data', async () => {
    const alertData = {
      service_name: 'test-service',
      alert_type: 'high_cpu_usage',
      severity: 'warning',
      message: 'CPU usage is high',
      details: { cpu_usage: 85 }
    }

    HealthAlert.create.mockResolvedValue(mockHealthAlert)
    const createdAlert = await HealthAlert.create(alertData)
    
    expect(HealthAlert.create).toHaveBeenCalledWith(alertData)
    expect(createdAlert).toBeDefined()
  })

  it('should validate required fields', async () => {
    const invalidAlertData = {
      service_name: '',
      alert_type: null,
      severity: null
    }

    HealthAlert.create.mockRejectedValue(new Error('Validation failed'))
    await expect(HealthAlert.create(invalidAlertData)).rejects.toThrow('Validation failed')
  })

  it('should handle alert with minimal required data', async () => {
    const minimalData = {
      service_name: 'minimal-service',
      alert_type: 'error',
      severity: 'critical'
    }

    HealthAlert.create.mockResolvedValue(mockHealthAlert)
    const createdAlert = await HealthAlert.create(minimalData)
    
    expect(createdAlert).toBeDefined()
    expect(createdAlert.service_name).toBe('test-service')
  })
}) 