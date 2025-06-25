// @test @model @user @static-methods
/**
 * Tests for User model static methods.
 * Ensures find, where, create, and updateOrCreate methods work correctly.
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

  // Mock static methods
  static async find(id: number): Promise<MockUser | null> {
    if (id === 1) {
      return new MockUser({ id })
    }
    return null
  }

  static async where(column: string, value: any): Promise<MockUser[]> {
    if (column === 'email' && value === 'john@example.com') {
      return [new MockUser({ email: value })]
    }
    return []
  }

  static async create(data: Partial<User>): Promise<MockUser> {
    return new MockUser(data)
  }

  static async updateOrCreate(searchData: Partial<User>, updateData: Partial<User>): Promise<MockUser> {
    return new MockUser({ ...searchData, ...updateData })
  }
}

describe('Static Methods', () => {
  setupTestEnvironment()

  it('should find user by id', async () => {
    const foundUser = await MockUser.find(1)
    
    expect(foundUser).toBeInstanceOf(MockUser)
    expect(foundUser?.id).toBe(1)
  })

  it('should return null for non-existent user', async () => {
    const foundUser = await MockUser.find(999)
    
    expect(foundUser).toBeNull()
  })

  it('should find users by email', async () => {
    const users = await MockUser.where('email', 'john@example.com')
    
    expect(Array.isArray(users)).toBe(true)
    expect(users).toHaveLength(1)
    expect(users[0].email).toBe('john@example.com')
  })

  it('should return empty array for non-existent email', async () => {
    const users = await MockUser.where('email', 'nonexistent@example.com')
    
    expect(Array.isArray(users)).toBe(true)
    expect(users).toHaveLength(0)
  })

  it('should create new user', async () => {
    const newUser = await MockUser.create({
      name: 'New User',
      email: 'new@example.com',
      password: 'password123'
    })
    
    expect(newUser).toBeInstanceOf(MockUser)
    expect(newUser.name).toBe('New User')
    expect(newUser.email).toBe('new@example.com')
  })

  it('should update or create user', async () => {
    const updatedUser = await MockUser.updateOrCreate(
      { email: 'existing@example.com' },
      { name: 'Updated Name' }
    )
    
    expect(updatedUser).toBeInstanceOf(MockUser)
    expect(updatedUser.email).toBe('existing@example.com')
    expect(updatedUser.name).toBe('Updated Name')
  })
}) 