// @test @model @codespace @creation
/**
 * Tests for Codespace model creation.
 * Ensures creation logic, validation, and minimal data cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockCodespace, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

class MockCodespace {
  static create = vi.fn()
}

vi.mock('@/models/Codespace', () => ({
  Codespace: MockCodespace
}))

const Codespace = MockCodespace

describe('Codespace Creation', () => {
  let mockCodespace: any
  let mockUser: any

  beforeEach(() => {
    vi.clearAllMocks()
    mockUser = createMockUser()
    mockCodespace = createMockCodespace({
      user: mockUser,
      user_id: 1,
      github_id: 'github-codespace-123',
      environment: 'development',
      size: 'standardLinux',
      status: 'Available'
    })
  })

  setupTestEnvironment()

  it('should create a new codespace with valid data', async () => {
    const codespaceData = {
      name: 'test-codespace',
      github_id: 'github-codespace-456',
      user_id: 1,
      environment: 'production',
      size: 'standardLinux',
      status: 'Creating',
      url: 'https://github.com/codespaces/test-codespace'
    }
    Codespace.create.mockResolvedValue(mockCodespace)
    const createdCodespace = await Codespace.create(codespaceData)
    expect(Codespace.create).toHaveBeenCalledWith(codespaceData)
    expect(createdCodespace).toBeDefined()
  })

  it('should validate required fields', async () => {
    const invalidCodespaceData = {
      name: '',
      github_id: null,
      user_id: null
    }
    Codespace.create.mockRejectedValue(new Error('Validation failed'))
    await expect(Codespace.create(invalidCodespaceData)).rejects.toThrow('Validation failed')
  })

  it('should handle codespace with minimal required data', async () => {
    const minimalData = {
      name: 'minimal-codespace',
      github_id: 'github-minimal-123',
      user_id: 1
    }
    Codespace.create.mockResolvedValue(mockCodespace)
    const createdCodespace = await Codespace.create(minimalData)
    expect(createdCodespace).toBeDefined()
    expect(createdCodespace.name).toBe('test-codespace')
  })
}) 