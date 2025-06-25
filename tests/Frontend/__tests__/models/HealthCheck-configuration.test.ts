// @test @model @health-check @configuration
/**
 * Tests for HealthCheck model configuration.
 * Ensures different HTTP methods, status codes, and timeout values are handled correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockHealthCheck, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthCheck Configuration', () => {
  setupTestEnvironment()

  it('should handle different HTTP methods', () => {
    const methods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD']
    
    methods.forEach(method => {
      const healthCheckWithMethod = createMockHealthCheck({ method })
      expect(healthCheckWithMethod.method).toBe(method)
    })
  })

  it('should handle different expected status codes', () => {
    const statusCodes = [200, 201, 204, 301, 302]
    
    statusCodes.forEach(status => {
      const healthCheckWithStatus = createMockHealthCheck({ expected_status: status })
      expect(healthCheckWithStatus.expected_status).toBe(status)
    })
  })

  it('should handle different timeout values', () => {
    const timeouts = [5, 10, 30, 60, 120]
    
    timeouts.forEach(timeout => {
      const healthCheckWithTimeout = createMockHealthCheck({ timeout })
      expect(healthCheckWithTimeout.timeout).toBe(timeout)
    })
  })
}) 