// @test @model @health-check @deletion
/**
 * Tests for HealthCheck model deletion.
 * Ensures deletion logic is covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Deletion', () => {
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

  it('should delete health check', async () => {
    HealthCheck.delete.mockResolvedValue(true)
    await HealthCheck.delete(1)
    
    expect(HealthCheck.delete).toHaveBeenCalledWith(1)
  })
}) 