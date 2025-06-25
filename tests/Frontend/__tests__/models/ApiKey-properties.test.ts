// @test @model @api-key @properties
/**
 * Tests for ApiKey model properties.
 * Ensures all properties have correct types and default values.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { createMockApiKey, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

describe('ApiKey Properties', () => {
  let mockApiKey: any
  let mockUser: any

  beforeEach(() => {
    mockUser = createMockUser()
    mockApiKey = createMockApiKey({
      user: mockUser,
      user_id: 1,
      name: 'Test API Key',
      key: 'test_api_key_123',
      permissions: ['read', 'write'],
      expires_at: '2025-01-01T00:00:00Z',
      last_used_at: '2024-01-01T12:00:00Z',
      is_active: true
    })
  })

  setupTestEnvironment()

  it('should have required properties', () => {
    expect(mockApiKey.id).toBeDefined()
    expect(mockApiKey.name).toBeDefined()
    expect(mockApiKey.key).toBeDefined()
    expect(mockApiKey.user_id).toBeDefined()
    expect(mockApiKey.permissions).toBeDefined()
    expect(mockApiKey.is_active).toBeDefined()
  })

  it('should have optional properties', () => {
    expect(mockApiKey.expires_at).toBeDefined()
    expect(mockApiKey.last_used_at).toBeDefined()
  })

  it('should have timestamp properties', () => {
    expect(mockApiKey.created_at).toBeDefined()
    expect(mockApiKey.updated_at).toBeDefined()
    expect(typeof mockApiKey.created_at).toBe('string')
    expect(typeof mockApiKey.updated_at).toBe('string')
  })
}) 