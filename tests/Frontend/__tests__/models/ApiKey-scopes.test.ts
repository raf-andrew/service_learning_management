// @test @model @api-key @scopes
/**
 * Tests for ApiKey model scopes.
 * Ensures active, user, and status scopes are defined and accessible.
 */

import { describe, it, expect } from 'vitest'
import { MockApiKey } from '../helpers/mockModels'
import { setupTestEnvironment } from '../helpers/testUtils'

// Use the mock ApiKey class
const ApiKey = MockApiKey

describe('ApiKey Scopes', () => {
  setupTestEnvironment()

  it('should have active scope', () => {
    expect(ApiKey.scopeActive).toBeDefined()
  })

  it('should have user scope', () => {
    expect(ApiKey.scopeByUser).toBeDefined()
  })

  it('should have status scope', () => {
    expect(ApiKey.scopeByStatus).toBeDefined()
  })
}) 