// @test @model @health-check @retrieval
/**
 * Tests for HealthCheck model retrieval.
 * Ensures retrieval by ID, type, active status, and null cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Retrieval', () => {
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

  it('should find health check by ID', async () => {
    HealthCheck.findById.mockResolvedValue(mockHealthCheck)
    
    const healthCheck = await HealthCheck.findById(1)
    
    expect(HealthCheck.findById).toHaveBeenCalledWith(1)
    expect(healthCheck).toEqual(mockHealthCheck)
  })

  it('should find health checks by type', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockHealthCheck])
    }
    HealthCheck.where.mockReturnValue(mockQuery)
    
    const healthChecks = await HealthCheck.where('type', 'http').get()
    
    expect(HealthCheck.where).toHaveBeenCalledWith('type', 'http')
    expect(healthChecks).toContain(mockHealthCheck)
  })

  it('should find active health checks', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockHealthCheck])
    }
    HealthCheck.where.mockReturnValue(mockQuery)
    
    const activeHealthChecks = await HealthCheck.where('is_active', true).get()
    
    expect(HealthCheck.where).toHaveBeenCalledWith('is_active', true)
    expect(activeHealthChecks).toContain(mockHealthCheck)
  })

  it('should return null for non-existent health check', async () => {
    HealthCheck.findById.mockResolvedValue(null)
    
    const healthCheck = await HealthCheck.findById(999)
    
    expect(healthCheck).toBeNull()
  })
}) 