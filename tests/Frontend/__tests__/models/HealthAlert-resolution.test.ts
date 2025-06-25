// @test @model @health-alert @resolution
/**
 * Tests for HealthAlert model resolution.
 * Ensures alert resolution logic works correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockHealthAlert, setupTestEnvironment } from '../helpers/testUtils'

describe('HealthAlert Resolution', () => {
  setupTestEnvironment()

  it('should resolve an active alert', async () => {
    const mockHealthAlert = createMockHealthAlert({ is_resolved: false })
    const resolveResult = await mockHealthAlert.resolve()
    
    expect(resolveResult).toBe(true)
  })

  it('should not resolve an already resolved alert', async () => {
    const resolvedAlert = createMockHealthAlert({
      is_resolved: true,
      resolved_at: new Date().toISOString()
    })
    
    const resolveResult = await resolvedAlert.resolve()
    
    expect(resolveResult).toBe(false)
  })

  it('should update alert status when resolved', async () => {
    const mockHealthAlert = createMockHealthAlert({ is_resolved: false })
    await mockHealthAlert.resolve()
    
    expect(mockHealthAlert.is_resolved).toBe(true)
  })
}) 