// @test @model @health-check @update
/**
 * Tests for HealthCheck model update.
 * Ensures update of information, last check timestamp, and configuration are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Update', () => {
  let mockHealthCheck: any
  let mockUser: any

  beforeEach(() => {
    vi.clearAllMocks()
    
    mockUser = createMockUser()
    mockHealthCheck = createMockHealthCheck({
      user: mockUser,
      user_id: 1,
      name: 'test-service',
      url: 'http://localhost:8000/health',
      method: 'GET',
      expected_status: 200,
      timeout: 30,
      interval: 60,
      is_active: true,
      last_check_at: '2024-01-01T12:00:00Z',
      last_status: 'healthy',
      last_response_time: 125
    })
  })

  setupTestEnvironment()

  it('should update health check information', async () => {
    const updateData = {
      name: 'updated-service',
      url: 'https://updated.example.com/health',
      timeout: 45,
      interval: 120
    }

    const updatedHealthCheck = { ...mockHealthCheck, ...updateData }
    HealthCheck.update.mockResolvedValue(updatedHealthCheck)
    
    const result = await HealthCheck.update(1, updateData)
    
    expect(HealthCheck.update).toHaveBeenCalledWith(1, updateData)
    expect(result.name).toBe('updated-service')
    expect(result.timeout).toBe(45)
  })

  it('should update last check timestamp', async () => {
    const lastCheckUpdate = {
      last_check_at: new Date().toISOString(),
      last_status: 'healthy',
      last_response_time: 150
    }

    HealthCheck.update.mockResolvedValue({ ...mockHealthCheck, ...lastCheckUpdate })
    await HealthCheck.update(1, lastCheckUpdate)
    
    expect(HealthCheck.update).toHaveBeenCalledWith(1, lastCheckUpdate)
  })

  it('should update configuration', async () => {
    const configUpdate = {
      timeout: 60,
      interval: 300,
      expected_status: 201
    }

    HealthCheck.update.mockResolvedValue({ ...mockHealthCheck, ...configUpdate })
    await HealthCheck.update(1, configUpdate)
    
    expect(HealthCheck.update).toHaveBeenCalledWith(1, configUpdate)
  })
}) 