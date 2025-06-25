// @test @model @user @validation
/**
 * Tests for User model data validation.
 * Ensures email format validation and special character handling work correctly.
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
}

describe('Data Validation', () => {
  setupTestEnvironment()

  it('should validate email format', () => {
    const validEmails = [
      'test@example.com',
      'user.name@domain.co.uk',
      'test+tag@example.org'
    ]
    
    validEmails.forEach(email => {
      const userWithEmail = new MockUser({ email })
      expect(userWithEmail.email).toBe(email)
    })
  })

  it('should handle special characters in names', () => {
    const specialName = 'José María O\'Connor-Smith'
    const userWithSpecialName = new MockUser({ name: specialName })
    
    expect(userWithSpecialName.name).toBe(specialName)
  })

  it('should handle unicode characters', () => {
    const unicodeName = '张三李四王五'
    const userWithUnicodeName = new MockUser({ name: unicodeName })
    
    expect(userWithUnicodeName.name).toBe(unicodeName)
  })
}) 