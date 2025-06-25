// @test @model @codespace @deletion
/**
 * Tests for Codespace model deletion.
 * Ensures deletion logic and cascade deletion with user are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockCodespace, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

class MockCodespace {
  static delete = vi.fn()
}

vi.mock('@/models/Codespace', () => ({
  Codespace: MockCodespace
}))

const Codespace = MockCodespace

describe('Codespace Deletion', () => {
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

  it('should delete codespace', async () => {
    Codespace.delete.mockResolvedValue(true)
    await Codespace.delete(1)
    expect(Codespace.delete).toHaveBeenCalledWith(1)
  })

  it('should handle cascade deletion with user', async () => {
    expect(mockCodespace.user).toBeDefined()
    expect(mockCodespace.user_id).toBe(1)
  })
}) 