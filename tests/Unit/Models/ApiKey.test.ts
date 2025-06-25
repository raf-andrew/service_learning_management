/**
 * @fileoverview Unit tests for ApiKey model
 * @tags unit,models,api,authentication
 * @description Tests for ApiKey model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { ApiKey } from '../../../src/models/ApiKey'

describe('ApiKey Model', () => {
  let apiKey: ApiKey

  beforeEach(() => {
    apiKey = new ApiKey()
  })

  describe('@unit @models @api @authentication - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(apiKey.fillable).toContain('key')
      expect(apiKey.fillable).toContain('name')
      expect(apiKey.fillable).toContain('user_id')
      expect(apiKey.fillable).toContain('permissions')
      expect(apiKey.fillable).toContain('is_active')
      expect(apiKey.fillable).toContain('expires_at')
    })

    it('should have correct hidden properties', () => {
      expect(apiKey.hidden).toContain('key')
    })

    it('should have correct table name', () => {
      expect(apiKey.getTable()).toBe('api_keys')
    })

    it('should have correct primary key', () => {
      expect(apiKey.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @api @authentication - Creation', () => {
    it('should create API key with valid data', () => {
      const data = {
        key: 'sk_test_1234567890abcdef',
        name: 'Test API Key',
        user_id: 1,
        permissions: ['read', 'write'],
        is_active: true,
        expires_at: '2024-12-31T23:59:59Z'
      }

      apiKey.fill(data)
      expect(apiKey.key).toBe('sk_test_1234567890abcdef')
      expect(apiKey.name).toBe('Test API Key')
      expect(apiKey.user_id).toBe(1)
      expect(apiKey.permissions).toEqual(['read', 'write'])
      expect(apiKey.is_active).toBe(true)
      expect(apiKey.expires_at).toBe('2024-12-31T23:59:59Z')
    })

    it('should handle missing optional fields', () => {
      const data = {
        key: 'sk_test_1234567890abcdef',
        name: 'Simple API Key'
      }

      apiKey.fill(data)
      expect(apiKey.key).toBe('sk_test_1234567890abcdef')
      expect(apiKey.name).toBe('Simple API Key')
      expect(apiKey.user_id === null || typeof apiKey.user_id === 'undefined').toBe(true)
      expect(apiKey.permissions === null || typeof apiKey.permissions === 'undefined').toBe(true)
      expect(apiKey.is_active === null || typeof apiKey.is_active === 'undefined').toBe(true)
      expect(apiKey.expires_at === null || typeof apiKey.expires_at === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @api @authentication - Validation', () => {
    it('should validate required fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.key).toContain('required')
      expect(rules.name).toContain('required')
    })

    it('should validate string fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.key).toContain('string')
      expect(rules.name).toContain('string')
    })

    it('should validate integer fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.user_id).toContain('integer')
    })

    it('should validate boolean fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.is_active).toContain('boolean')
    })

    it('should validate array fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.permissions).toContain('array')
    })

    it('should validate date fields', () => {
      const rules = apiKey.getValidationRules()
      expect(rules.expires_at).toContain('date')
    })
  })

  describe('@unit @models @api @authentication - Relationships', () => {
    it('should have user relationship', () => {
      apiKey.user_id = 1
      expect(apiKey.user).toBeDefined()
      expect(apiKey.user.id).toBe(1)
      expect(apiKey.user.name).toBe('Test User')
    })
  })

  describe('@unit @models @api @authentication - Key Generation', () => {
    it('should generate API key', () => {
      const key = ApiKey.generateKey()
      expect(key).toBeDefined()
      expect(key.length).toBe(32)
      expect(/^[0-9a-f]+$/.test(key)).toBe(true)
    })

    it('should generate unique keys', () => {
      const key1 = ApiKey.generateKey()
      const key2 = ApiKey.generateKey()
      expect(key1).not.toBe(key2)
    })
  })

  describe('@unit @models @api @authentication - Permission Methods', () => {
    it('should check if API key has permission', () => {
      apiKey.permissions = ['read', 'write', 'delete']
      expect(apiKey.hasPermission('read')).toBe(true)
      expect(apiKey.hasPermission('write')).toBe(true)
      expect(apiKey.hasPermission('delete')).toBe(true)
      expect(apiKey.hasPermission('admin')).toBe(false)
    })

    it('should check if API key has any permission', () => {
      apiKey.permissions = ['read', 'write']
      expect(apiKey.hasAnyPermission(['read', 'admin'])).toBe(true)
      expect(apiKey.hasAnyPermission(['delete', 'admin'])).toBe(false)
    })

    it('should check if API key has all permissions', () => {
      apiKey.permissions = ['read', 'write', 'delete']
      expect(apiKey.hasAllPermissions(['read', 'write'])).toBe(true)
      expect(apiKey.hasAllPermissions(['read', 'admin'])).toBe(false)
    })

    it('should add permission', () => {
      apiKey.permissions = ['read']
      apiKey.addPermission('write')
      expect(apiKey.permissions).toEqual(['read', 'write'])
    })

    it('should not add duplicate permission', () => {
      apiKey.permissions = ['read']
      apiKey.addPermission('read')
      expect(apiKey.permissions).toEqual(['read'])
    })

    it('should remove permission', () => {
      apiKey.permissions = ['read', 'write', 'delete']
      apiKey.removePermission('write')
      expect(apiKey.permissions).toEqual(['read', 'delete'])
    })

    it('should handle null permissions gracefully', () => {
      apiKey.permissions = null
      expect(apiKey.hasPermission('read')).toBe(false)
      expect(apiKey.hasAnyPermission(['read'])).toBe(false)
      expect(apiKey.hasAllPermissions(['read'])).toBe(false)
    })
  })

  describe('@unit @models @api @authentication - Status Methods', () => {
    it('should check if API key is expired', () => {
      apiKey.expires_at = null
      expect(apiKey.isExpired).toBe(false)

      const futureDate = new Date()
      futureDate.setDate(futureDate.getDate() + 1)
      apiKey.expires_at = futureDate.toISOString()
      expect(apiKey.isExpired).toBe(false)

      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 1)
      apiKey.expires_at = pastDate.toISOString()
      expect(apiKey.isExpired).toBe(true)
    })

    it('should check if API key is valid', () => {
      apiKey.is_active = true
      apiKey.expires_at = null
      expect(apiKey.isValid).toBe(true)

      apiKey.is_active = false
      expect(apiKey.isValid).toBe(false)

      apiKey.is_active = true
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 1)
      apiKey.expires_at = pastDate.toISOString()
      expect(apiKey.isValid).toBe(false)
    })

    it('should check if API key is invalid', () => {
      apiKey.is_active = true
      apiKey.expires_at = null
      expect(apiKey.isInvalid).toBe(false)

      apiKey.is_active = false
      expect(apiKey.isInvalid).toBe(true)
    })
  })

  describe('@unit @models @api @authentication - Activation Methods', () => {
    it('should activate API key', () => {
      apiKey.is_active = false
      apiKey.activate()
      expect(apiKey.is_active).toBe(true)
    })

    it('should deactivate API key', () => {
      apiKey.is_active = true
      apiKey.deactivate()
      expect(apiKey.is_active).toBe(false)
    })

    it('should expire API key', () => {
      apiKey.expires_at = null
      apiKey.expire()
      expect(apiKey.expires_at).toBeDefined()
      // Check that the expiration date is in the past (or very recent)
      const expiryDate = new Date(apiKey.expires_at!)
      const now = new Date()
      expect(expiryDate.getTime()).toBeLessThanOrEqual(now.getTime() + 1000) // Allow 1 second tolerance
    })

    it('should extend expiration', () => {
      const originalDate = new Date()
      originalDate.setDate(originalDate.getDate() + 1)
      apiKey.expires_at = originalDate.toISOString()

      apiKey.extendExpiration(7)
      const newExpiry = new Date(apiKey.expires_at!)
      const expectedExpiry = new Date()
      expectedExpiry.setDate(expectedExpiry.getDate() + 7)

      // Use a more reasonable tolerance for time comparisons
      expect(newExpiry.getTime()).toBeCloseTo(expectedExpiry.getTime(), -3)
    })
  })

  describe('@unit @models @api @authentication - Time Methods', () => {
    it('should get days until expiration', () => {
      const futureDate = new Date()
      futureDate.setDate(futureDate.getDate() + 5)
      apiKey.expires_at = futureDate.toISOString()

      const daysUntilExpiry = apiKey.getDaysUntilExpiration()
      expect(daysUntilExpiry).toBe(5)
    })

    it('should return null for days until expiration if no expiry', () => {
      apiKey.expires_at = null
      const daysUntilExpiry = apiKey.getDaysUntilExpiration()
      expect(daysUntilExpiry).toBeNull()
    })
  })

  describe('@unit @models @api @authentication - Security Methods', () => {
    it('should get masked key', () => {
      apiKey.key = 'sk_test_1234567890abcdef'
      const maskedKey = apiKey.getMaskedKey()
      expect(maskedKey).toBe('sk_t****************cdef')
    })

    it('should mask short keys', () => {
      apiKey.key = 'short'
      const maskedKey = apiKey.getMaskedKey()
      expect(maskedKey).toBe('*****')
    })
  })

  describe('@unit @models @api @authentication - Scopes', () => {
    it('should have active scope', () => {
      const activeKeys = ApiKey.active()
      expect(activeKeys).toBeDefined()
      expect(activeKeys.where).toBeDefined()
    })

    it('should have inactive scope', () => {
      const inactiveKeys = ApiKey.inactive()
      expect(inactiveKeys).toBeDefined()
      expect(inactiveKeys.where).toBeDefined()
    })

    it('should have expired scope', () => {
      const expiredKeys = ApiKey.expired()
      expect(expiredKeys).toBeDefined()
      expect(expiredKeys.where).toBeDefined()
    })

    it('should have valid scope', () => {
      const validKeys = ApiKey.valid()
      expect(validKeys).toBeDefined()
      expect(validKeys.where).toBeDefined()
    })

    it('should have by user scope', () => {
      const userKeys = ApiKey.byUser(1)
      expect(userKeys).toBeDefined()
      expect(userKeys.where).toBeDefined()
    })

    it('should have with permission scope', () => {
      const permissionKeys = ApiKey.withPermission('read')
      expect(permissionKeys).toBeDefined()
      expect(permissionKeys.where).toBeDefined()
    })
  })

  describe('@unit @models @api @authentication - Error Handling', () => {
    it('should handle null values gracefully', () => {
      apiKey.permissions = null
      expect(apiKey.hasPermission('read')).toBe(false)
      expect(apiKey.hasAnyPermission(['read'])).toBe(false)
    })

    it('should handle undefined values gracefully', () => {
      apiKey.is_active = undefined
      expect(apiKey.isValid).toBe(false)
      expect(apiKey.isInvalid).toBe(true)
    })
  })

  describe('@unit @models @api @authentication - Performance', () => {
    it('should handle large permission arrays efficiently', () => {
      const largePermissions = Array.from({ length: 1000 }, (_, i) => `permission_${i}`)
      
      const startTime = performance.now()
      apiKey.permissions = largePermissions
      apiKey.hasPermission('permission_500')
      apiKey.hasAnyPermission(['permission_500', 'permission_501'])
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 