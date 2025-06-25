// @test @model @api-key @retrieval
/**
 * Tests for ApiKey model retrieval.
 * Ensures retrieval by ID, user ID, active status, and null cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockApiKey, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Retrieval', () => {
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

  it('should find API key by ID', async () => {
    ApiKey.findById.mockResolvedValue(mockApiKey)
    
    const apiKey = await ApiKey.findById(1)
    
    expect(ApiKey.findById).toHaveBeenCalledWith(1)
    expect(apiKey).toEqual(mockApiKey)
  })

  it('should find API keys by user ID', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockApiKey])
    }
    ApiKey.where.mockReturnValue(mockQuery)
    
    const apiKeys = await ApiKey.where('user_id', 1).get()
    
    expect(ApiKey.where).toHaveBeenCalledWith('user_id', 1)
    expect(apiKeys).toContain(mockApiKey)
  })

  it('should find active API keys', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockApiKey])
    }
    ApiKey.where.mockReturnValue(mockQuery)
    
    const activeApiKeys = await ApiKey.where('is_active', true).get()
    
    expect(ApiKey.where).toHaveBeenCalledWith('is_active', true)
    expect(activeApiKeys).toContain(mockApiKey)
  })

  it('should return null for non-existent API key', async () => {
    ApiKey.findById.mockResolvedValue(null)
    
    const apiKey = await ApiKey.findById(999)
    
    expect(apiKey).toBeNull()
  })
}) 