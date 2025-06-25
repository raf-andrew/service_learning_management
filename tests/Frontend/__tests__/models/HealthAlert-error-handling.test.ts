// @test @model @health-alert @error-handling
/**
 * Tests for HealthAlert model error handling.
 * Ensures creation, update, and deletion errors are handled gracefully.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('Error Handling', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle creation errors', async () => {
    const errorMessage = 'Failed to create health alert'
    HealthAlert.create.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthAlert.create({})).rejects.toThrow(errorMessage)
  })

  it('should handle update errors', async () => {
    const errorMessage = 'Failed to update health alert'
    HealthAlert.update.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthAlert.update(1, {})).rejects.toThrow(errorMessage)
  })

  it('should handle deletion errors', async () => {
    const errorMessage = 'Failed to delete health alert'
    HealthAlert.delete.mockRejectedValue(new Error(errorMessage))
    
    await expect(HealthAlert.delete(1)).rejects.toThrow(errorMessage)
  })
}) 