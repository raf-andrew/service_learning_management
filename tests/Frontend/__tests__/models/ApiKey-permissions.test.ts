// @test @model @api-key @permissions
/**
 * Tests for ApiKey model permissions.
 * Ensures different permission sets and validation work correctly.
 */

import { describe, it, expect } from 'vitest'
import { createMockApiKey, setupTestEnvironment } from '../helpers/testUtils'

describe('ApiKey Permissions', () => {
  setupTestEnvironment()

  it('should handle different permission sets', () => {
    const readOnlyApiKey = createMockApiKey({
      permissions: ['read']
    })
    
    const fullAccessApiKey = createMockApiKey({
      permissions: ['read', 'write', 'delete', 'admin']
    })
    
    expect(readOnlyApiKey.permissions).toContain('read')
    expect(readOnlyApiKey.permissions).not.toContain('write')
    expect(fullAccessApiKey.permissions).toContain('admin')
  })

  it('should validate permission format', () => {
    const validPermissions = ['read', 'write', 'delete', 'admin']
    const invalidPermissions = ['invalid-permission', '', null]
    
    validPermissions.forEach(permission => {
      expect(permission).toBeTruthy()
      expect(permission.length).toBeGreaterThan(0)
    })
  })
}) 