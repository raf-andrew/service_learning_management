/**
 * @fileoverview Unit tests for Codespace model
 * @tags unit,models,codespace,github
 * @description Tests for Codespace model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { Codespace } from '../../../src/models/Codespace'

describe('Codespace Model', () => {
  let codespace: Codespace

  beforeEach(() => {
    codespace = new Codespace()
  })

  describe('@unit @models @codespace @github - Basic Properties', () => {
    it('should have correct fillable properties', () => {
      expect(codespace.fillable).toContain('name')
      expect(codespace.fillable).toContain('github_id')
      expect(codespace.fillable).toContain('user_id')
      expect(codespace.fillable).toContain('environment')
      expect(codespace.fillable).toContain('size')
      expect(codespace.fillable).toContain('status')
      expect(codespace.fillable).toContain('url')
    })

    it('should have correct table name', () => {
      expect(codespace.getTable()).toBe('codespaces')
    })

    it('should have correct primary key', () => {
      expect(codespace.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @codespace @github - Creation', () => {
    it('should create codespace with valid data', () => {
      const data = {
        name: 'test-codespace',
        github_id: '123456789',
        user_id: 1,
        environment: 'production',
        size: '2GB',
        status: 'running',
        url: 'https://github.com/codespaces/test-codespace'
      }

      codespace.fill(data)
      expect(codespace.name).toBe('test-codespace')
      expect(codespace.github_id).toBe('123456789')
      expect(codespace.user_id).toBe(1)
      expect(codespace.environment).toBe('production')
      expect(codespace.size).toBe('2GB')
      expect(codespace.status).toBe('running')
      expect(codespace.url).toBe('https://github.com/codespaces/test-codespace')
    })

    it('should handle missing optional fields', () => {
      const data = {
        name: 'simple-codespace'
      }

      codespace.fill(data)
      expect(codespace.name).toBe('simple-codespace')
      expect(codespace.github_id === null || typeof codespace.github_id === 'undefined').toBe(true)
      expect(codespace.user_id === null || typeof codespace.user_id === 'undefined').toBe(true)
      expect(codespace.environment === null || typeof codespace.environment === 'undefined').toBe(true)
      expect(codespace.size === null || typeof codespace.size === 'undefined').toBe(true)
      expect(codespace.status === null || typeof codespace.status === 'undefined').toBe(true)
      expect(codespace.url === null || typeof codespace.url === 'undefined').toBe(true)
    })
  })

  describe('@unit @models @codespace @github - Validation', () => {
    it('should validate required fields', () => {
      const rules = codespace.getValidationRules()
      expect(rules.name).toContain('required')
    })

    it('should validate string fields', () => {
      const rules = codespace.getValidationRules()
      expect(rules.name).toContain('string')
      expect(rules.github_id).toContain('string')
      expect(rules.environment).toContain('string')
      expect(rules.size).toContain('string')
      expect(rules.status).toContain('string')
      expect(rules.url).toContain('url')
    })

    it('should validate integer fields', () => {
      const rules = codespace.getValidationRules()
      expect(rules.user_id).toContain('integer')
    })
  })

  describe('@unit @models @codespace @github - Relationships', () => {
    it('should have user relationship', () => {
      codespace.user_id = 1
      expect(codespace.user).toBeDefined()
      expect(codespace.user.id).toBe(1)
      expect(codespace.user.name).toBe('Test User')
    })
  })

  describe('@unit @models @codespace @github - Status Methods', () => {
    it('should check if codespace is active', () => {
      codespace.status = 'active'
      expect(codespace.isActive()).toBe(true)

      codespace.status = 'inactive'
      expect(codespace.isActive()).toBe(false)
    })

    it('should check if codespace is inactive', () => {
      codespace.status = 'inactive'
      expect(codespace.isInactive()).toBe(true)

      codespace.status = 'active'
      expect(codespace.isInactive()).toBe(false)
    })

    it('should check if codespace is starting', () => {
      codespace.status = 'starting'
      expect(codespace.isStarting()).toBe(true)

      codespace.status = 'running'
      expect(codespace.isStarting()).toBe(false)
    })

    it('should check if codespace is stopping', () => {
      codespace.status = 'stopping'
      expect(codespace.isStopping()).toBe(true)

      codespace.status = 'stopped'
      expect(codespace.isStopping()).toBe(false)
    })

    it('should check if codespace is stopped', () => {
      codespace.status = 'stopped'
      expect(codespace.isStopped()).toBe(true)

      codespace.status = 'running'
      expect(codespace.isStopped()).toBe(false)
    })

    it('should check if codespace is running', () => {
      codespace.status = 'running'
      expect(codespace.isRunning()).toBe(true)

      codespace.status = 'stopped'
      expect(codespace.isRunning()).toBe(false)
    })

    it('should check if codespace is failed', () => {
      codespace.status = 'failed'
      expect(codespace.isFailed()).toBe(true)

      codespace.status = 'running'
      expect(codespace.isFailed()).toBe(false)
    })

    it('should check if codespace is pending', () => {
      codespace.status = 'pending'
      expect(codespace.isPending()).toBe(true)

      codespace.status = 'running'
      expect(codespace.isPending()).toBe(false)
    })
  })

  describe('@unit @models @codespace @github - Action Methods', () => {
    it('should check if codespace can start', () => {
      codespace.status = 'stopped'
      expect(codespace.canStart()).toBe(true)

      codespace.status = 'inactive'
      expect(codespace.canStart()).toBe(true)

      codespace.status = 'failed'
      expect(codespace.canStart()).toBe(true)

      codespace.status = 'running'
      expect(codespace.canStart()).toBe(false)
    })

    it('should check if codespace can stop', () => {
      codespace.status = 'running'
      expect(codespace.canStop()).toBe(true)

      codespace.status = 'active'
      expect(codespace.canStop()).toBe(true)

      codespace.status = 'stopped'
      expect(codespace.canStop()).toBe(false)
    })

    it('should check if codespace can delete', () => {
      codespace.status = 'stopped'
      expect(codespace.canDelete()).toBe(true)

      codespace.status = 'inactive'
      expect(codespace.canDelete()).toBe(true)

      codespace.status = 'failed'
      expect(codespace.canDelete()).toBe(true)

      codespace.status = 'running'
      expect(codespace.canDelete()).toBe(false)
    })
  })

  describe('@unit @models @codespace @github - Environment Methods', () => {
    it('should get environment name', () => {
      codespace.environment = 'production'
      expect(codespace.getEnvironmentName()).toBe('production')

      codespace.environment = null
      expect(codespace.getEnvironmentName()).toBe('default')
    })
  })

  describe('@unit @models @codespace @github - Size Methods', () => {
    it('should get size in MB for GB', () => {
      codespace.size = '2GB'
      expect(codespace.getSizeInMB()).toBe(2048)

      codespace.size = '1.5GB'
      expect(codespace.getSizeInMB()).toBe(1536)
    })

    it('should get size in MB for MB', () => {
      codespace.size = '512MB'
      expect(codespace.getSizeInMB()).toBe(512)

      codespace.size = '1024MB'
      expect(codespace.getSizeInMB()).toBe(1024)
    })

    it('should return 0 for invalid size', () => {
      codespace.size = 'invalid'
      expect(codespace.getSizeInMB()).toBe(0)

      codespace.size = null
      expect(codespace.getSizeInMB()).toBe(0)
    })
  })

  describe('@unit @models @codespace @github - Time Methods', () => {
    it('should get age in minutes', () => {
      const pastDate = new Date()
      pastDate.setMinutes(pastDate.getMinutes() - 30)
      codespace.created_at = pastDate.toISOString()

      const ageInMinutes = codespace.getAgeInMinutes()
      expect(ageInMinutes).toBe(30)
    })

    it('should get age in hours', () => {
      const pastDate = new Date()
      pastDate.setHours(pastDate.getHours() - 2)
      codespace.created_at = pastDate.toISOString()

      const ageInHours = codespace.getAgeInHours()
      expect(ageInHours).toBe(2)
    })

    it('should get age in days', () => {
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 3)
      codespace.created_at = pastDate.toISOString()

      const ageInDays = codespace.getAgeInDays()
      expect(ageInDays).toBe(3)
    })

    it('should check if codespace is older than', () => {
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 5)
      codespace.created_at = pastDate.toISOString()

      expect(codespace.isOlderThan(3)).toBe(true)
      expect(codespace.isOlderThan(7)).toBe(false)
    })

    it('should check if codespace is newer than', () => {
      const pastDate = new Date()
      pastDate.setHours(pastDate.getHours() - 3)
      codespace.created_at = pastDate.toISOString()

      expect(codespace.isNewerThan(1)).toBe(false)
      expect(codespace.isNewerThan(5)).toBe(true)
    })

    it('should return 0 for age if no created_at', () => {
      codespace.created_at = undefined
      expect(codespace.getAgeInMinutes()).toBe(0)
      expect(codespace.getAgeInHours()).toBe(0)
      expect(codespace.getAgeInDays()).toBe(0)
    })
  })

  describe('@unit @models @codespace @github - URL Methods', () => {
    it('should get GitHub URL', () => {
      codespace.url = 'https://github.com/codespaces/test-codespace'
      expect(codespace.getGitHubUrl()).toBe('https://github.com/codespaces/test-codespace')
    })

    it('should generate GitHub URL if not provided', () => {
      codespace.name = 'test-codespace'
      codespace.url = null
      expect(codespace.getGitHubUrl()).toBe('https://github.com/codespaces/test-codespace')
    })
  })

  describe('@unit @models @codespace @github - Scopes', () => {
    it('should have active scope', () => {
      const activeCodespaces = Codespace.active()
      expect(activeCodespaces).toBeDefined()
      expect(activeCodespaces.where).toBeDefined()
    })

    it('should have inactive scope', () => {
      const inactiveCodespaces = Codespace.inactive()
      expect(inactiveCodespaces).toBeDefined()
      expect(inactiveCodespaces.where).toBeDefined()
    })

    it('should have running scope', () => {
      const runningCodespaces = Codespace.running()
      expect(runningCodespaces).toBeDefined()
      expect(runningCodespaces.where).toBeDefined()
    })

    it('should have stopped scope', () => {
      const stoppedCodespaces = Codespace.stopped()
      expect(stoppedCodespaces).toBeDefined()
      expect(stoppedCodespaces.where).toBeDefined()
    })

    it('should have failed scope', () => {
      const failedCodespaces = Codespace.failed()
      expect(failedCodespaces).toBeDefined()
      expect(failedCodespaces.where).toBeDefined()
    })

    it('should have by user scope', () => {
      const userCodespaces = Codespace.byUser(1)
      expect(userCodespaces).toBeDefined()
      expect(userCodespaces.where).toBeDefined()
    })

    it('should have by environment scope', () => {
      const envCodespaces = Codespace.byEnvironment('production')
      expect(envCodespaces).toBeDefined()
      expect(envCodespaces.where).toBeDefined()
    })

    it('should have by status scope', () => {
      const statusCodespaces = Codespace.byStatus('running')
      expect(statusCodespaces).toBeDefined()
      expect(statusCodespaces.where).toBeDefined()
    })

    it('should have older than scope', () => {
      const oldCodespaces = Codespace.olderThan(7)
      expect(oldCodespaces).toBeDefined()
      expect(oldCodespaces.where).toBeDefined()
    })

    it('should have newer than scope', () => {
      const newCodespaces = Codespace.newerThan(24)
      expect(newCodespaces).toBeDefined()
      expect(newCodespaces.where).toBeDefined()
    })
  })

  describe('@unit @models @codespace @github - Error Handling', () => {
    it('should handle null values gracefully', () => {
      codespace.status = null
      expect(codespace.isActive()).toBe(false)
      expect(codespace.isRunning()).toBe(false)
      expect(codespace.canStart()).toBe(false)
    })

    it('should handle undefined values gracefully', () => {
      codespace.size = undefined
      expect(codespace.getSizeInMB()).toBe(0)
    })
  })

  describe('@unit @models @codespace @github - Performance', () => {
    it('should handle age calculations efficiently', () => {
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 30)
      codespace.created_at = pastDate.toISOString()

      const startTime = performance.now()
      codespace.getAgeInMinutes()
      codespace.getAgeInHours()
      codespace.getAgeInDays()
      codespace.isOlderThan(7)
      codespace.isNewerThan(24)
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 
