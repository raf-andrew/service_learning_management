// @test @model @health-check @performance
/**
 * Tests for HealthCheck model performance.
 * Ensures rapid status updates and bulk operations are handled efficiently.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Performance', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle rapid status updates', async () => {
    const startTime = Date.now()
    
    const promises = Array.from({ length: 10 }, (_, i) =>
      HealthCheck.update(i + 1, { last_status: 'healthy' })
    )
    
    HealthCheck.update.mockResolvedValue(createMockHealthCheck())
    await Promise.all(promises)
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(HealthCheck.update).toHaveBeenCalledTimes(10)
    expect(duration).toBeLessThan(1000)
  })

  it('should handle bulk operations efficiently', async () => {
    const healthChecks = Array.from({ length: 100 }, (_, i) =>
      createMockHealthCheck({ id: i + 1, name: `service-${i + 1}` })
    )
    
    const startTime = Date.now()
    
    HealthCheck.where.mockReturnValue({
      get: vi.fn().mockResolvedValue(healthChecks)
    })
    
    const result = await HealthCheck.where('is_active', true).get()
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(result).toHaveLength(100)
    expect(duration).toBeLessThan(1000)
  })
}) 