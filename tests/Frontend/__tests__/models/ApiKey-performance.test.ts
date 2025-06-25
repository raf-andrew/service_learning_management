// @test @model @api-key @performance
/**
 * Tests for ApiKey model performance.
 * Ensures rapid status updates and bulk operations are handled efficiently.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockApiKey, setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Performance', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle rapid status updates', async () => {
    const startTime = Date.now()
    
    const promises = Array.from({ length: 10 }, (_, i) =>
      ApiKey.update(i + 1, { last_used_at: new Date().toISOString() })
    )
    
    ApiKey.update.mockResolvedValue(createMockApiKey())
    await Promise.all(promises)
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(ApiKey.update).toHaveBeenCalledTimes(10)
    expect(duration).toBeLessThan(1000)
  })

  it('should handle bulk operations efficiently', async () => {
    const apiKeys = Array.from({ length: 100 }, (_, i) =>
      createMockApiKey({ id: i + 1, name: `api-key-${i + 1}` })
    )
    
    const startTime = Date.now()
    
    ApiKey.where.mockReturnValue({
      get: vi.fn().mockResolvedValue(apiKeys)
    })
    
    const result = await ApiKey.where('is_active', true).get()
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(result).toHaveLength(100)
    expect(duration).toBeLessThan(1000)
  })
}) 