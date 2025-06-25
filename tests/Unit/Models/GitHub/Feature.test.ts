/**
 * @fileoverview Unit tests for GitHub Feature model
 * @tags unit,models,github,feature
 * @description Tests for GitHub Feature model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { Feature } from '../../../../src/models/GitHub/Feature'

describe('GitHub Feature Model', () => {
  let feature: Feature

  beforeEach(() => {
    feature = new Feature()
  })

  describe('@unit @models @github @feature - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(feature.fillable).toContain('name')
      expect(feature.fillable).toContain('description')
      expect(feature.fillable).toContain('enabled')
      expect(feature.fillable).toContain('config')
    })

    it('should have correct table name', () => {
      expect(feature.getTable()).toBe('github_features')
    })

    it('should have correct primary key', () => {
      expect(feature.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @github @feature - Creation', () => {
    it('should create feature with valid data', () => {
      const data = {
        name: 'dark-mode',
        description: 'Dark mode feature',
        enabled: true,
        config: { theme: 'dark' }
      }

      feature.fill(data)
      expect(feature.name).toBe('dark-mode')
      expect(feature.description).toBe('Dark mode feature')
      expect(feature.enabled).toBe(true)
      expect(feature.config).toEqual({ theme: 'dark' })
    })

    it('should handle missing optional fields', () => {
      const data = {
        name: 'test-feature'
      }
      feature.fill(data)
      expect(feature.name).toBe('test-feature')
      expect(feature.description === null || typeof feature.description === 'undefined').toBe(true)
      expect(feature.enabled === null || typeof feature.enabled === 'undefined').toBe(true)
      expect(feature.config === null || typeof feature.config === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @github @feature - Validation', () => {
    it('should validate required fields', () => {
      const rules = feature.getValidationRules()
      expect(rules.name).toContain('required')
    })

    it('should validate boolean fields', () => {
      const rules = feature.getValidationRules()
      expect(rules.enabled).toContain('boolean')
    })

    it('should validate JSON fields', () => {
      const rules = feature.getValidationRules()
      expect(rules.config).toContain('json')
    })
  })

  describe('@unit @models @github @feature - Relationships', () => {
    it('should have repository relationship', () => {
      expect(feature.repository).toBeDefined()
    })

    it('should have user relationship', () => {
      expect(feature.user).toBeDefined()
    })
  })

  describe('@unit @models @github @feature - Scopes', () => {
    it('should have enabled scope', () => {
      const enabledFeatures = Feature.enabled()
      expect(enabledFeatures).toBeDefined()
    })

    it('should have disabled scope', () => {
      const disabledFeatures = Feature.disabled()
      expect(disabledFeatures).toBeDefined()
    })

    it('should have by repository scope', () => {
      const repoFeatures = Feature.byRepository(1)
      expect(repoFeatures).toBeDefined()
    })

    it('should have by user scope', () => {
      const userFeatures = Feature.byUser(1)
      expect(userFeatures).toBeDefined()
    })
  })

  describe('@unit @models @github @feature - Methods', () => {
    it('should check if feature is enabled', () => {
      feature.enabled = true
      expect(feature.isEnabled()).toBe(true)

      feature.enabled = false
      expect(feature.isEnabled()).toBe(false)
    })

    it('should check if feature is disabled', () => {
      feature.enabled = false
      expect(feature.isDisabled()).toBe(true)

      feature.enabled = true
      expect(feature.isDisabled()).toBe(false)
    })

    it('should enable feature', () => {
      feature.enabled = false
      feature.enable()
      expect(feature.enabled).toBe(true)
    })

    it('should disable feature', () => {
      feature.enabled = true
      feature.disable()
      expect(feature.enabled).toBe(false)
    })

    it('should toggle feature', () => {
      feature.enabled = false
      feature.toggle()
      expect(feature.enabled).toBe(true)

      feature.toggle()
      expect(feature.enabled).toBe(false)
    })

    it('should get config value', () => {
      feature.config = { theme: 'dark', language: 'en' }
      expect(feature.getConfigValue('theme')).toBe('dark')
      expect(feature.getConfigValue('language')).toBe('en')
      expect(feature.getConfigValue('nonexistent')).toBeNull()
    })

    it('should set config value', () => {
      feature.config = { theme: 'light' }
      feature.setConfigValue('language', 'es')
      expect(feature.config).toEqual({ theme: 'light', language: 'es' })
    })
  })

  describe('@unit @models @github @feature - Error Handling', () => {
    it('should handle null config gracefully', () => {
      feature.config = null
      expect(feature.getConfigValue('theme')).toBeNull()
      expect(() => feature.setConfigValue('theme', 'dark')).not.toThrow()
    })

    it('should handle null enabled value', () => {
      feature.enabled = null
      expect(feature.isEnabled()).toBe(false)
      expect(feature.isDisabled()).toBe(true)
    })
  })

  describe('@unit @models @github @feature - Performance', () => {
    it('should handle large config objects efficiently', () => {
      const largeConfig = {}
      for (let i = 0; i < 1000; i++) {
        largeConfig[`key${i}`] = `value${i}`
      }

      const startTime = performance.now()
      feature.config = largeConfig
      feature.getConfigValue('key500')
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 