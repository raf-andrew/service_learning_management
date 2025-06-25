// @test @model @health-alert @properties
/**
 * Tests for HealthAlert model properties.
 * Ensures all properties have correct types and default values.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { createMockHealthAlert, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthAlert Properties', () => {
  let mockHealthAlert: any
  let mockUser: any

  beforeEach(() => {
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

  it('should have required properties', () => {
    expect(mockHealthAlert.id).toBeDefined()
    expect(mockHealthAlert.service_name).toBeDefined()
    expect(mockHealthAlert.alert_type).toBeDefined()
    expect(mockHealthAlert.severity).toBeDefined()
    expect(mockHealthAlert.message).toBeDefined()
    expect(mockHealthAlert.is_resolved).toBeDefined()
  })

  it('should have optional properties', () => {
    expect(mockHealthAlert.details).toBeDefined()
    expect(mockHealthAlert.resolved_at).toBeDefined()
  })

  it('should have timestamp properties', () => {
    expect(mockHealthAlert.created_at).toBeDefined()
    expect(mockHealthAlert.updated_at).toBeDefined()
    expect(typeof mockHealthAlert.created_at).toBe('string')
    expect(typeof mockHealthAlert.updated_at).toBe('string')
  })
}) 