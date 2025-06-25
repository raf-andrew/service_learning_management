// @test @model @api-key @status-methods
/**
 * Tests for ApiKey model status methods.
 * Ensures expired, valid, and active status checks work correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockApiKey, setupTestEnvironment } from '../helpers/testUtils'

describe('ApiKey Status Methods', () => {
  setupTestEnvironment()

  it('should check if API key is expired', () => {
    const expiredApiKey = createMockApiKey({
      expires_at: '2020-01-01T00:00:00Z'
    })
    
    expect(expiredApiKey.is_expired).toBe(true)
    
    const validApiKey = createMockApiKey({
      expires_at: '2030-01-01T00:00:00Z'
    })
    
    expect(validApiKey.is_expired).toBe(false)
  })

  it('should check if API key is valid', () => {
    const validApiKey = createMockApiKey({
      is_active: true,
      expires_at: '2030-01-01T00:00:00Z'
    })
    
    expect(validApiKey.is_valid).toBe(true)
    
    const invalidApiKey = createMockApiKey({
      is_active: false,
      expires_at: '2030-01-01T00:00:00Z'
    })
    
    expect(invalidApiKey.is_valid).toBe(false)
  })

  it('should check if API key is active', () => {
    const activeApiKey = createMockApiKey({ is_active: true })
    const inactiveApiKey = createMockApiKey({ is_active: false })
    
    expect(activeApiKey.is_active).toBe(true)
    expect(inactiveApiKey.is_active).toBe(false)
  })
}) 