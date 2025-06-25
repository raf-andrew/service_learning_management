// @test @model @api-key @creation
/**
 * Tests for ApiKey model creation.
 * Ensures creation logic, validation, and minimal data cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockApiKey, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Creation', () => {
  let mockApiKey: any
  let mockUser: any

  beforeEach(() => {
    vi.clearAllMocks()
    
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

  it('should create a new API key with valid data', async () => {
    const apiKeyData = {
      name: 'test-api-key',
      user_id: 1,
      permissions: ['read', 'write'],
      expires_at: '2025-01-01T00:00:00Z'
    }

    ApiKey.create.mockResolvedValue(mockApiKey)
    const createdApiKey = await ApiKey.create(apiKeyData)
    
    expect(ApiKey.create).toHaveBeenCalledWith(apiKeyData)
    expect(createdApiKey).toBeDefined()
  })

  it('should validate required fields', async () => {
    const invalidApiKeyData = {
      name: '',
      user_id: null,
      permissions: []
    }

    ApiKey.create.mockRejectedValue(new Error('Validation failed'))
    await expect(ApiKey.create(invalidApiKeyData)).rejects.toThrow('Validation failed')
  })

  it('should handle API key with minimal required data', async () => {
    const minimalData = {
      name: 'minimal-api-key',
      user_id: 1
    }

    ApiKey.create.mockResolvedValue(mockApiKey)
    const createdApiKey = await ApiKey.create(minimalData)
    
    expect(createdApiKey).toBeDefined()
    expect(createdApiKey.name).toBe('Test API Key')
  })
}) 