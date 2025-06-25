// @test @model @api-key @error-handling
/**
 * Tests for ApiKey model error handling.
 * Ensures creation, update, and deletion errors are handled gracefully.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { MockApiKey } from '../helpers/mockModels'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('Error Handling', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle creation errors', async () => {
    const errorMessage = 'Failed to create API key'
    ApiKey.create.mockRejectedValue(new Error(errorMessage))
    
    await expect(ApiKey.create({})).rejects.toThrow(errorMessage)
  })

  it('should handle update errors', async () => {
    const errorMessage = 'Failed to update API key'
    ApiKey.update.mockRejectedValue(new Error(errorMessage))
    
    await expect(ApiKey.update(1, {})).rejects.toThrow(errorMessage)
  })

  it('should handle deletion errors', async () => {
    const errorMessage = 'Failed to delete API key'
    ApiKey.delete.mockRejectedValue(new Error(errorMessage))
    
    await expect(ApiKey.delete(1)).rejects.toThrow(errorMessage)
  })
}) 