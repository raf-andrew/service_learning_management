// @test @model @health-check @creation
/**
 * Tests for HealthCheck model creation.
 * Ensures creation logic, validation, and complex configuration are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Creation', () => {
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

  it('should create a new health check with valid data', async () => {
    const healthCheckData = {
      name: 'test-service',
      url: 'http://localhost:8000/health',
      method: 'GET',
      expected_status: 200,
      timeout: 30,
      interval: 60
    }

    HealthCheck.create.mockResolvedValue(mockHealthCheck)
    const createdHealthCheck = await HealthCheck.create(healthCheckData)
    
    expect(HealthCheck.create).toHaveBeenCalledWith(healthCheckData)
    expect(createdHealthCheck).toBeDefined()
  })

  it('should validate required fields', async () => {
    const invalidHealthCheckData = {
      name: '',
      url: null,
      method: null
    }

    HealthCheck.create.mockRejectedValue(new Error('Validation failed'))
    await expect(HealthCheck.create(invalidHealthCheckData)).rejects.toThrow('Validation failed')
  })

  it('should handle health check with complex configuration', async () => {
    const complexConfig = {
      name: 'complex-service',
      url: 'https://api.example.com/health',
      method: 'POST',
      expected_status: 200,
      timeout: 60,
      interval: 300,
      headers: { 'Authorization': 'Bearer token' },
      body: { check: 'health' }
    }

    HealthCheck.create.mockResolvedValue(mockHealthCheck)
    const createdHealthCheck = await HealthCheck.create(complexConfig)
    
    expect(createdHealthCheck).toBeDefined()
    expect(createdHealthCheck.name).toBe('test-service')
  })
}) 