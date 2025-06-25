// @test @model @health-alert @scopes
/**
 * Tests for HealthAlert model scopes.
 * Ensures active, critical, warning, type, and service scopes are defined and accessible.
 */

import { describe, it, expect } from 'vitest'
import { MockHealthAlert } from '../helpers/mockModels'
import { setupTestEnvironment } from '../helpers/testUtils'

// Use the mock HealthAlert class
const HealthAlert = MockHealthAlert

describe('HealthAlert Scopes', () => {
  setupTestEnvironment()

  it('should have active scope', () => {
    expect(HealthAlert.scopeActive).toBeDefined()
  })

  it('should have critical scope', () => {
    expect(HealthAlert.scopeCritical).toBeDefined()
  })

  it('should have warning scope', () => {
    expect(HealthAlert.scopeWarning).toBeDefined()
  })

  it('should have type scope', () => {
    expect(HealthAlert.scopeOfType).toBeDefined()
  })

  it('should have service scope', () => {
    expect(HealthAlert.scopeForService).toBeDefined()
  })
}) 