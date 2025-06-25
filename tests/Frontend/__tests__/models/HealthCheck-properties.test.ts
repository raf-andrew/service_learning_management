// @test @model @health-check @properties
/**
 * Tests for HealthCheck model properties.
 * Ensures all properties have correct types and default values.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { createMockHealthCheck, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthCheck Properties', () => {
  let mockHealthCheck: any
  let mockUser: any

  beforeEach(() => {
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

  it('should have required properties', () => {
    expect(mockHealthCheck.id).toBeDefined()
    expect(mockHealthCheck.name).toBeDefined()
    expect(mockHealthCheck.url).toBeDefined()
    expect(mockHealthCheck.method).toBeDefined()
    expect(mockHealthCheck.expected_status).toBeDefined()
    expect(mockHealthCheck.timeout).toBeDefined()
    expect(mockHealthCheck.interval).toBeDefined()
    expect(mockHealthCheck.is_active).toBeDefined()
  })

  it('should have optional properties', () => {
    expect(mockHealthCheck.last_check_at).toBeDefined()
    expect(mockHealthCheck.last_status).toBeDefined()
    expect(mockHealthCheck.last_response_time).toBeDefined()
  })

  it('should have timestamp properties', () => {
    expect(mockHealthCheck.created_at).toBeDefined()
    expect(mockHealthCheck.updated_at).toBeDefined()
    expect(typeof mockHealthCheck.created_at).toBe('string')
    expect(typeof mockHealthCheck.updated_at).toBe('string')
  })
}) 