/**
 * @fileoverview Unit tests for GitHub Repository model
 * @tags unit,models,github,repository
 * @description Tests for GitHub Repository model functionality including CRUD operations, relationships, and validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { Repository } from '../../../../src/models/GitHub/Repository'

describe('GitHub Repository Model', () => {
  let repository: Repository

  beforeEach(() => {
    repository = new Repository()
  })

  describe('@unit @models @github @repository - Basic Properties', () => {
    it('should have correct table name', () => {
      expect(repository.getTable()).toBe('github_repositories')
    })

    it('should have correct primary key', () => {
      expect(repository.getKeyName()).toBe('id')
    })
  })

  describe('@unit @models @github @repository - Creation', () => {
    it('should create repository with valid data', () => {
      const data = {
        name: 'test-repo',
        full_name: 'test/test-repo',
        default_branch: 'main',
        settings: {
          private: false,
          description: 'Test repository',
          homepage: 'https://test.com',
          has_issues: true,
          has_wiki: true,
          has_pages: false
        },
        permissions: {
          admin: true,
          push: true,
          pull: true
        }
      }

      repository.fill(data)
      expect(repository.name).toBe('test-repo')
      expect(repository.full_name).toBe('test/test-repo')
      expect(repository.default_branch).toBe('main')
      expect(repository.settings?.private).toBe(false)
      expect(repository.permissions?.admin).toBe(true)
    })

    it('should handle missing optional fields', () => {
      const data = {
        name: 'test-repo'
      }

      repository.fill(data)
      expect(repository.name).toBe('test-repo')
      expect(repository.full_name).toBeUndefined()
      expect(repository.settings).toBeUndefined()
      expect(repository.permissions).toBeUndefined()
    })
  })

  describe('@unit @models @github @repository - Validation', () => {
    it('should validate required fields', () => {
      const rules = repository.getValidationRules()
      expect(rules.name).toContain('required')
    })

    it('should validate string fields', () => {
      const rules = repository.getValidationRules()
      expect(rules.name).toContain('string')
      expect(rules.full_name).toContain('string')
      expect(rules.default_branch).toContain('string')
    })

    it('should validate max lengths', () => {
      const rules = repository.getValidationRules()
      expect(rules.name).toContain('max:255')
      expect(rules.full_name).toContain('max:500')
      expect(rules.default_branch).toContain('max:100')
    })
  })

  describe('@unit @models @github @repository - Relationships', () => {
    it('should have user relationship', () => {
      expect(repository.user).toBeDefined()
      expect(repository.user.id).toBe(1)
      expect(repository.user.name).toBe('Test User')
    })

    it('should have features relationship', () => {
      expect(repository.features).toBeDefined()
      expect(Array.isArray(repository.features)).toBe(true)
    })

    it('should have configs relationship', () => {
      expect(repository.configs).toBeDefined()
      expect(Array.isArray(repository.configs)).toBe(true)
    })
  })

  describe('@unit @models @github @repository - Scopes', () => {
    it('should have public scope', () => {
      const publicRepos = Repository.public()
      expect(publicRepos).toBeDefined()
      expect(publicRepos.where).toBeDefined()
    })

    it('should have private scope', () => {
      const privateRepos = Repository.private()
      expect(privateRepos).toBeDefined()
      expect(privateRepos.where).toBeDefined()
    })

    it('should have by user scope', () => {
      const userRepos = Repository.byUser(1)
      expect(userRepos).toBeDefined()
      expect(userRepos.where).toBeDefined()
    })
  })

  describe('@unit @models @github @repository - Methods', () => {
    it('should get full URL', () => {
      repository.full_name = 'test/test-repo'
      expect(repository.getFullUrl()).toBe('https://github.com/test/test-repo')
    })

    it('should handle missing full_name for URL', () => {
      repository.full_name = undefined
      expect(repository.getFullUrl()).toBe('')
    })

    it('should check if repository is public', () => {
      repository.settings = { private: false }
      expect(repository.isPublic()).toBe(true)

      repository.settings = { private: true }
      expect(repository.isPublic()).toBe(false)
    })

    it('should check if repository is private', () => {
      repository.settings = { private: true }
      expect(repository.isPrivate()).toBe(true)

      repository.settings = { private: false }
      expect(repository.isPrivate()).toBe(false)
    })

    it('should handle missing settings for privacy checks', () => {
      repository.settings = undefined
      expect(repository.isPublic()).toBe(false)
      expect(repository.isPrivate()).toBe(false)
    })
  })

  describe('@unit @models @github @repository - GitHub Integration', () => {
    it('should sync from GitHub', async () => {
      const result = await repository.syncFromGitHub()
      expect(result).toBe(repository)
    })

    it('should update GitHub settings', async () => {
      const result = await repository.updateGitHubSettings()
      expect(result).toBe(repository)
    })
  })

  describe('@unit @models @github @repository - Error Handling', () => {
    it('should handle null values gracefully', () => {
      repository.settings = null
      expect(repository.isPublic()).toBe(false)
      expect(repository.isPrivate()).toBe(false)
    })

    it('should handle undefined values gracefully', () => {
      repository.permissions = undefined
      expect(repository.permissions).toBeUndefined()
    })
  })

  describe('@unit @models @github @repository - Performance', () => {
    it('should handle large settings objects efficiently', () => {
      const largeSettings = {
        private: false,
        description: 'A'.repeat(1000),
        homepage: 'https://example.com',
        has_issues: true,
        has_wiki: true,
        has_pages: false,
        extra: Array.from({ length: 100 }, (_, i) => ({ key: `value${i}` }))
      }

      const startTime = performance.now()
      repository.settings = largeSettings
      repository.isPublic()
      const endTime = performance.now()

      expect(endTime - startTime).toBeLessThan(100) // Should complete within 100ms
    })
  })
}) 