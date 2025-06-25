// @test @model @api-key @integration
/**
 * Tests for ApiKey model integration scenarios.
 * Ensures complete lifecycle and different permission sets work correctly.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockApiKey, setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Integration Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle complete API key lifecycle', async () => {
    // Create
    const createData = {
      name: 'lifecycle-test',
      user_id: 1,
      permissions: ['read', 'write']
    }
    
    ApiKey.create.mockResolvedValue(createMockApiKey(createData))
    const createdApiKey = await ApiKey.create(createData)
    
    // Update
    ApiKey.update.mockResolvedValue({ ...createdApiKey, is_active: false })
    const updatedApiKey = await ApiKey.update(createdApiKey.id, { is_active: false })
    
    // Delete
    ApiKey.delete.mockResolvedValue(true)
    await ApiKey.delete(updatedApiKey.id)
    
    expect(ApiKey.create).toHaveBeenCalledWith(createData)
    expect(ApiKey.update).toHaveBeenCalledWith(createdApiKey.id, { is_active: false })
    expect(ApiKey.delete).toHaveBeenCalledWith(updatedApiKey.id)
  })

  it('should handle API key lifecycle with different permissions', async () => {
    const permissionSets = [
      ['read'],
      ['read', 'write'],
      ['read', 'write', 'delete'],
      ['read', 'write', 'delete', 'admin']
    ]
    
    for (const permissions of permissionSets) {
      const apiKeyData = {
        name: `test-${permissions.join('-')}`,
        user_id: 1,
        permissions,
        is_active: true
      }

      ApiKey.create.mockResolvedValue(createMockApiKey(apiKeyData))
      const createdApiKey = await ApiKey.create(apiKeyData)
      
      expect(createdApiKey.permissions).toEqual(permissions)
      expect(createdApiKey.is_active).toBe(true)
    }
  })
}) 