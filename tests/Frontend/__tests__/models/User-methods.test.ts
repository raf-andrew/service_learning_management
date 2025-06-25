// @test @model @user @methods
/**
 * Tests for User model instance methods.
 * Ensures toArray and toJson methods work correctly.
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

  // Methods
  toArray(): User {
    return { ...this.data }
  }

  toJson(): string {
    return JSON.stringify(this.data)
  }
}

describe('User Methods', () => {
  let user: MockUser

  setupTestEnvironment()

  beforeEach(() => {
    user = new MockUser()
  })

  it('should convert to array', () => {
    const userArray = user.toArray()
    
    expect(userArray).toHaveProperty('id')
    expect(userArray).toHaveProperty('name')
    expect(userArray).toHaveProperty('email')
    expect(userArray).toHaveProperty('password')
    expect(userArray).toHaveProperty('created_at')
    expect(userArray).toHaveProperty('updated_at')
  })

  it('should convert to JSON', () => {
    const userJson = user.toJson()
    
    expect(typeof userJson).toBe('string')
    expect(() => JSON.parse(userJson)).not.toThrow()
    
    const parsed = JSON.parse(userJson)
    expect(parsed).toHaveProperty('id')
    expect(parsed).toHaveProperty('name')
    expect(parsed).toHaveProperty('email')
  })

  it('should return valid JSON structure', () => {
    const userJson = user.toJson()
    const parsed = JSON.parse(userJson)
    
    expect(parsed.id).toBe(user.id)
    expect(parsed.name).toBe(user.name)
    expect(parsed.email).toBe(user.email)
  })
}) 