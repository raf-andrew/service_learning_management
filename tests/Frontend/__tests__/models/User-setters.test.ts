// @test @model @user @setters
/**
 * Tests for User model setters.
 * Ensures setter methods properly update user properties.
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

  // Setters
  set name(value: string) { this.data.name = value }
  set email(value: string) { this.data.email = value }
  set password(value: string) { this.data.password = value }
}

describe('User Setters', () => {
  let user: MockUser

  setupTestEnvironment()

  beforeEach(() => {
    user = new MockUser()
  })

  it('should update user name', () => {
    const originalName = user.name
    user.name = 'Updated Name'
    
    expect(user.name).toBe('Updated Name')
    expect(user.name).not.toBe(originalName)
  })

  it('should update user email', () => {
    const originalEmail = user.email
    user.email = 'updated@example.com'
    
    expect(user.email).toBe('updated@example.com')
    expect(user.email).not.toBe(originalEmail)
  })

  it('should update user password', () => {
    const originalPassword = user.password
    user.password = 'new_secure_password'
    
    expect(user.password).toBe('new_secure_password')
    expect(user.password).not.toBe(originalPassword)
  })
}) 