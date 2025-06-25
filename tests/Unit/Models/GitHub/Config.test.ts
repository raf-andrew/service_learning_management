/**
 * @fileoverview Unit tests for GitHub Config model
 * @tags unit,models,github,config
 * @description Tests for GitHub Config model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { Config } from '../../../../src/models/GitHub/Config'

describe('GitHub Config Model', () => {
  let config: Config

  beforeEach(() => {
    config = new Config()
  })

  describe('@unit @models @github @config - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(config.fillable).toContain('key')
      expect(config.fillable).toContain('value')
      expect(config.fillable).toContain('type')
      expect(config.fillable).toContain('description')
    })

    it('should have correct table name', () => {
      expect(config.getTable()).toBe('github_configs')
    })

    it('should have correct primary key', () => {
      expect(config.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @github @config - Creation', () => {
    it('should create config with valid data', () => {
      const data = {
        key: 'api_token',
        value: 'ghp_123456789',
        type: 'string',
        description: 'GitHub API token'
      }

      config.fill(data)
      expect(config.key).toBe('api_token')
      expect(config.value).toBe('ghp_123456789')
      expect(config.type).toBe('string')
      expect(config.description).toBe('GitHub API token')
    })

    it('should handle missing optional fields', () => {
      const data = {
        key: 'test_key',
        value: 'test_value'
      }
      config.fill(data)
      expect(config.key).toBe('test_key')
      expect(config.value).toBe('test_value')
      expect(config.type === null || typeof config.type === 'undefined').toBe(true)
      expect(config.description === null || typeof config.description === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @github @config - Validation', () => {
    it('should validate required fields', () => {
      const rules = config.getValidationRules()
      expect(rules.key).toContain('required')
      expect(rules.value).toContain('required')
    })

    it('should validate key format', () => {
      const rules = config.getValidationRules()
      expect(rules.key).toContain('string')
      expect(rules.key).toContain('max:255')
    })

    it('should validate type values', () => {
      const rules = config.getValidationRules()
      expect(rules.type).toContain('in:string,integer,boolean,array,object')
    })
  })

  describe('@unit @models @github @config - Relationships', () => {
    it('should have repository relationship', () => {
      expect(config.repository).toBeDefined()
    })

    it('should have user relationship', () => {
      expect(config.user).toBeDefined()
    })
  })

  describe('@unit @models @github @config - Scopes', () => {
    it('should have by key scope', () => {
      const configs = Config.byKey('api_token')
      expect(configs).toBeDefined()
    })

    it('should have by type scope', () => {
      const configs = Config.byType('string')
      expect(configs).toBeDefined()
    })

    it('should have by repository scope', () => {
      const configs = Config.byRepository(1)
      expect(configs).toBeDefined()
    })

    it('should have by user scope', () => {
      const configs = Config.byUser(1)
      expect(configs).toBeDefined()
    })
  })

  describe('@unit @models @github @config - Methods', () => {
    it('should get typed value for string', () => {
      config.type = 'string'
      config.value = 'test_value'
      expect(config.getTypedValue()).toBe('test_value')
    })

    it('should get typed value for integer', () => {
      config.type = 'integer'
      config.value = '123'
      expect(config.getTypedValue()).toBe(123)
    })

    it('should get typed value for boolean', () => {
      config.type = 'boolean'
      config.value = 'true'
      expect(config.getTypedValue()).toBe(true)

      config.value = 'false'
      expect(config.getTypedValue()).toBe(false)
    })

    it('should get typed value for array', () => {
      config.type = 'array'
      config.value = '["item1", "item2"]'
      expect(config.getTypedValue()).toEqual(['item1', 'item2'])
    })

    it('should get typed value for object', () => {
      config.type = 'object'
      config.value = '{"key": "value"}'
      expect(config.getTypedValue()).toEqual({ key: 'value' })
    })

    it('should set typed value', () => {
      config.type = 'integer'
      config.setTypedValue(456)
      expect(config.value).toBe('456')
    })

    it('should check if config is sensitive', () => {
      config.key = 'api_token'
      expect(config.isSensitive()).toBe(true)

      config.key = 'theme'
      expect(config.isSensitive()).toBe(false)
    })

    it('should mask sensitive values', () => {
      config.key = 'api_token'
      config.value = 'ghp_123456789'
      expect(config.getMaskedValue()).toBe('ghp_*********')
    })

    it('should not mask non-sensitive values', () => {
      config.key = 'theme'
      config.value = 'dark'
      expect(config.getMaskedValue()).toBe('dark')
    })
  })

  describe('@unit @models @github @config - Error Handling', () => {
    it('should handle invalid JSON gracefully', () => {
      config.type = 'object'
      config.value = 'invalid json'
      expect(() => config.getTypedValue()).not.toThrow()
      expect(config.getTypedValue()).toBe('invalid json')
    })

    it('should handle null type gracefully', () => {
      config.type = null
      config.value = 'test'
      expect(config.getTypedValue()).toBe('test')
    })

    it('should handle null value gracefully', () => {
      config.value = null
      expect(config.getTypedValue()).toBeNull()
    })
  })

  describe('@unit @models @github @config - Performance', () => {
    it('should handle large objects efficiently', () => {
      const largeObject = {}
      for (let i = 0; i < 1000; i++) {
        largeObject[`key${i}`] = `value${i}`
      }

      const startTime = performance.now()
      config.type = 'object'
      config.value = JSON.stringify(largeObject)
      config.getTypedValue()
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 