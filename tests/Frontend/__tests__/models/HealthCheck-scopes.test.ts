// @test @model @health-check @scopes
/**
 * Tests for HealthCheck model scopes.
 * Ensures active, type, and status scopes are defined and accessible.
 */

import { describe, it, expect } from 'vitest'
import { MockHealthCheck } from '../helpers/mockModels'
import { setupTestEnvironment } from '../helpers/testUtils'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Scopes', () => {
  setupTestEnvironment()

  it('should have active scope', () => {
    expect(HealthCheck.scopeActive).toBeDefined()
  })

  it('should have type scope', () => {
    expect(HealthCheck.scopeByType).toBeDefined()
  })

  it('should have status scope', () => {
    expect(HealthCheck.scopeByStatus).toBeDefined()
  })
}) 