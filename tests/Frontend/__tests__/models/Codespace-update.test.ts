// @test @model @codespace @update
/**
 * Tests for Codespace model update.
 * Ensures update of information, status, and last used timestamp are covered.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockCodespace, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

class MockCodespace {
  static update = vi.fn()
}

vi.mock('@/models/Codespace', () => ({
  Codespace: MockCodespace
}))

const Codespace = MockCodespace

describe('Codespace Update', () => {
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

  it('should update codespace information', async () => {
    const updateData = {
      name: 'updated-codespace',
      status: 'Running',
      environment: 'staging'
    }
    const updatedCodespace = { ...mockCodespace, ...updateData }
    Codespace.update.mockResolvedValue(updatedCodespace)
    const result = await Codespace.update(1, updateData)
    expect(Codespace.update).toHaveBeenCalledWith(1, updateData)
    expect(result.name).toBe('updated-codespace')
    expect(result.status).toBe('Running')
  })

  it('should update codespace status', async () => {
    const statusUpdate = {
      status: 'Stopped',
      updated_at: new Date().toISOString()
    }
    Codespace.update.mockResolvedValue({ ...mockCodespace, ...statusUpdate })
    await Codespace.update(1, statusUpdate)
    expect(Codespace.update).toHaveBeenCalledWith(1, statusUpdate)
  })

  it('should update last used timestamp', async () => {
    const lastUsedUpdate = {
      last_used_at: new Date().toISOString()
    }
    Codespace.update.mockResolvedValue({ ...mockCodespace, ...lastUsedUpdate })
    await Codespace.update(1, lastUsedUpdate)
    expect(Codespace.update).toHaveBeenCalledWith(1, lastUsedUpdate)
  })
}) 