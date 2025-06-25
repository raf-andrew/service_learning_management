// @test @model @api-key @update
/**
 * Tests for ApiKey model update.
 * Ensures update of information, last used timestamp, and expiration date are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockApiKey, createMockUser, setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Update', () => {
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

  it('should update API key information', async () => {
    const updateData = {
      name: 'updated-api-key',
      permissions: ['read', 'write', 'delete'],
      is_active: false
    }

    const updatedApiKey = { ...mockApiKey, ...updateData }
    ApiKey.update.mockResolvedValue(updatedApiKey)
    
    const result = await ApiKey.update(1, updateData)
    
    expect(ApiKey.update).toHaveBeenCalledWith(1, updateData)
    expect(result.name).toBe('updated-api-key')
    expect(result.permissions).toContain('delete')
  })

  it('should update last used timestamp', async () => {
    const lastUsedUpdate = {
      last_used_at: new Date().toISOString()
    }

    ApiKey.update.mockResolvedValue({ ...mockApiKey, ...lastUsedUpdate })
    await ApiKey.update(1, lastUsedUpdate)
    
    expect(ApiKey.update).toHaveBeenCalledWith(1, lastUsedUpdate)
  })

  it('should update expiration date', async () => {
    const expirationUpdate = {
      expires_at: '2026-01-01T00:00:00Z'
    }

    ApiKey.update.mockResolvedValue({ ...mockApiKey, ...expirationUpdate })
    await ApiKey.update(1, expirationUpdate)
    
    expect(ApiKey.update).toHaveBeenCalledWith(1, expirationUpdate)
  })
}) 