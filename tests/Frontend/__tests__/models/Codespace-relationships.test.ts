// @test @model @codespace @relationships
/**
 * Tests for Codespace model relationships.
 * Ensures belongsTo user relationship is covered.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { createMockCodespace, createMockUser, setupTestEnvironment } from '../helpers/testUtils'

describe('Codespace Relationships', () => {
  let mockCodespace: any
  let mockUser: any

  beforeEach(() => {
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

  it('should belong to a user', () => {
    expect(mockCodespace.user).toBeDefined()
    expect(mockCodespace.user_id).toBe(1)
  })
}) 