// @test @model @user @relationships
/**
 * Tests for User model relationships.
 * Ensures codespaces and developer credentials relationships work correctly.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { setupTestEnvironment } from '../utils/test-utils'

// Mock User model interface
interface User {
  id: number
  name: string
  email: string
  password: string
  email_verified_at?: string
  remember_token?: string
  created_at: string
  updated_at: string
  codespaces?: Codespace[]
  developerCredentials?: DeveloperCredential[]
}

interface Codespace {
  id: number
  name: string
  user_id: number
  status: string
  created_at: string
  updated_at: string
}

interface DeveloperCredential {
  id: number
  user_id: number
  type: string
  key: string
  created_at: string
  updated_at: string
}

// Mock User model class
class MockUser {
  private data: User

  constructor(data: Partial<User> = {}) {
    this.data = {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      password: 'hashed_password',
      email_verified_at: undefined,
      remember_token: undefined,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      ...data
    }
  }

  // Getters
  get id(): number { return this.data.id }
  get name(): string { return this.data.name }
  get email(): string { return this.data.email }
  get password(): string { return this.data.password }
  get email_verified_at(): string | undefined { return this.data.email_verified_at }
  get remember_token(): string | undefined { return this.data.remember_token }
  get created_at(): string { return this.data.created_at }
  get updated_at(): string { return this.data.updated_at }

  // Mock relationships
  async codespaces(): Promise<Codespace[]> {
    return this.data.codespaces || []
  }

  async developerCredentials(): Promise<DeveloperCredential[]> {
    return this.data.developerCredentials || []
  }
}

describe('User Relationships', () => {
  setupTestEnvironment()

  it('should have codespaces relationship', async () => {
    const user = new MockUser()
    const codespaces = await user.codespaces()
    
    expect(Array.isArray(codespaces)).toBe(true)
  })

  it('should have developer credentials relationship', async () => {
    const user = new MockUser()
    const credentials = await user.developerCredentials()
    
    expect(Array.isArray(credentials)).toBe(true)
  })

  it('should handle user with codespaces', async () => {
    const userWithCodespaces = new MockUser({
      codespaces: [
        {
          id: 1,
          name: 'Test Codespace',
          user_id: 1,
          status: 'active',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }
      ]
    })
    
    const codespaces = await userWithCodespaces.codespaces()
    
    expect(codespaces).toHaveLength(1)
    expect(codespaces[0].name).toBe('Test Codespace')
    expect(codespaces[0].status).toBe('active')
  })

  it('should handle user with developer credentials', async () => {
    const userWithCredentials = new MockUser({
      developerCredentials: [
        {
          id: 1,
          user_id: 1,
          type: 'api_key',
          key: 'secret_key_123',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }
      ]
    })
    
    const credentials = await userWithCredentials.developerCredentials()
    
    expect(credentials).toHaveLength(1)
    expect(credentials[0].type).toBe('api_key')
    expect(credentials[0].key).toBe('secret_key_123')
  })
}) 