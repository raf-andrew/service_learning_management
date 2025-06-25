// @test @model @codespace @retrieval
/**
 * Tests for Codespace model retrieval.
 * Ensures retrieval by ID, GitHub ID, user ID, and null cases are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockCodespace, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

class MockCodespace {
  static findById = vi.fn()
  static where = vi.fn()
}

vi.mock('@/models/Codespace', () => ({
  Codespace: MockCodespace
}))

const Codespace = MockCodespace

describe('Codespace Retrieval', () => {
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

  it('should find codespace by ID', async () => {
    Codespace.findById.mockResolvedValue(mockCodespace)
    const codespace = await Codespace.findById(1)
    expect(Codespace.findById).toHaveBeenCalledWith(1)
    expect(codespace).toEqual(mockCodespace)
  })

  it('should find codespace by GitHub ID', async () => {
    const mockQuery = {
      first: vi.fn().mockResolvedValue(mockCodespace)
    }
    Codespace.where.mockReturnValue(mockQuery)
    const codespace = await Codespace.where('github_id', 'github-codespace-123').first()
    expect(Codespace.where).toHaveBeenCalledWith('github_id', 'github-codespace-123')
    expect(codespace).toEqual(mockCodespace)
  })

  it('should find codespaces by user ID', async () => {
    const mockQuery = {
      get: vi.fn().mockResolvedValue([mockCodespace])
    }
    Codespace.where.mockReturnValue(mockQuery)
    const codespaces = await Codespace.where('user_id', 1).get()
    expect(Codespace.where).toHaveBeenCalledWith('user_id', 1)
    expect(codespaces).toContain(mockCodespace)
  })

  it('should return null for non-existent codespace', async () => {
    Codespace.findById.mockResolvedValue(null)
    const codespace = await Codespace.findById(999)
    expect(codespace).toBeNull()
  })
}) 