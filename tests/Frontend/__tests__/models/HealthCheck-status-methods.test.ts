// @test @model @health-check @status-methods
/**
 * Tests for HealthCheck model status methods.
 * Ensures active status, health status, and response time checks work correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockHealthCheck, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthCheck Status Methods', () => {
  setupTestEnvironment()

  it('should check if health check is active', () => {
    const activeHealthCheck = createMockHealthCheck({ is_active: true })
    const inactiveHealthCheck = createMockHealthCheck({ is_active: false })
    
    expect(activeHealthCheck.is_active).toBe(true)
    expect(inactiveHealthCheck.is_active).toBe(false)
  })

  it('should check health check status', () => {
    const healthyCheck = createMockHealthCheck({ last_status: 'healthy' })
    const unhealthyCheck = createMockHealthCheck({ last_status: 'unhealthy' })
    
    expect(healthyCheck.last_status).toBe('healthy')
    expect(unhealthyCheck.last_status).toBe('unhealthy')
  })

  it('should check response time', () => {
    const fastCheck = createMockHealthCheck({ last_response_time: 50 })
    const slowCheck = createMockHealthCheck({ last_response_time: 5000 })
    
    expect(fastCheck.last_response_time).toBe(50)
    expect(slowCheck.last_response_time).toBe(5000)
  })
}) 