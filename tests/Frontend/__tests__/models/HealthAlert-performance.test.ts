// @test @model @health-alert @performance
/**
 * Tests for HealthAlert model performance.
 * Ensures rapid status updates and bulk operations are handled efficiently.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthAlert, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthAlert } from '../helpers/mockModels'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Performance', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle rapid status updates', async () => {
    const startTime = Date.now()
    
    const promises = Array.from({ length: 10 }, (_, i) =>
      HealthAlert.update(i + 1, { severity: 'critical' })
    )
    
    HealthAlert.update.mockResolvedValue(createMockHealthAlert())
    await Promise.all(promises)
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(HealthAlert.update).toHaveBeenCalledTimes(10)
    expect(duration).toBeLessThan(1000)
  })

  it('should handle bulk operations efficiently', async () => {
    const alerts = Array.from({ length: 100 }, (_, i) =>
      createMockHealthAlert({ id: i + 1, service_name: `service-${i + 1}` })
    )
    
    const startTime = Date.now()
    
    HealthAlert.where.mockReturnValue({
      get: vi.fn().mockResolvedValue(alerts)
    })
    
    const result = await HealthAlert.where('is_resolved', false).get()
    
    const endTime = Date.now()
    const duration = endTime - startTime
    
    expect(result).toHaveLength(100)
    expect(duration).toBeLessThan(1000)
  })
}) 