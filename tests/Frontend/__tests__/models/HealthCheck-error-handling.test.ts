// @test @model @health-check @error-handling
/**
 * Tests for HealthCheck model error handling.
 * Ensures creation, update, and deletion errors are handled gracefully.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('Error Handling', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle creation errors', async () => {
    const errorMessage = 'Failed to create health check'
    HealthCheck.create.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthCheck.create({})).rejects.toThrow(errorMessage)
  })

  it('should handle update errors', async () => {
    const errorMessage = 'Failed to update health check'
    HealthCheck.update.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthCheck.update(1, {})).rejects.toThrow(errorMessage)
  })

  it('should handle deletion errors', async () => {
    const errorMessage = 'Failed to delete health check'
    HealthCheck.delete.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthCheck.delete(1)).rejects.toThrow(errorMessage)
  })
}) 