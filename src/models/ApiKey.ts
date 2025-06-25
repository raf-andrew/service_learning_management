/**
 * @fileoverview ApiKey model interface and implementation
 * @tags models,api,authentication
 * @description TypeScript interface and class for ApiKey model with Laravel-like functionality
 */

export interface ApiKeyData {
  id?: number
  key: string
  name: string
  user_id?: number
  permissions?: string[]
  is_active?: boolean
  expires_at?: string | null
  created_at?: string
  updated_at?: string
}

export class ApiKey {
  public id?: number
  public key: string = ''
  public name: string = ''
  public user_id: number | null = null
  public permissions: string[] | null = null
  public is_active: boolean | null = null
  public expires_at: string | null = null
  public created_at?: string
  public updated_at?: string

  public fillable = [
    'key',
    'name',
    'user_id',
    'permissions',
    'is_active',
    'expires_at'
  ]

  public hidden = ['key']

  constructor(data?: Partial<ApiKeyData>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<ApiKeyData>): this {
    Object.assign(this, data)
    return this
  }

  getTable(): string {
    return 'api_keys'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      key: ['required', 'string', 'max:255'],
      name: ['required', 'string', 'max:255'],
      user_id: ['integer', 'exists:users,id'],
      permissions: ['array'],
      is_active: ['boolean'],
      expires_at: ['date', 'after:now']
    }
  }

  // Relationships
  get user() {
    return { id: this.user_id, name: 'Test User' }
  }

  // Methods
  static generateKey(): string {
    const chars = '0123456789abcdef'
    let result = ''
    for (let i = 0; i < 32; i++) {
      result += chars[Math.floor(Math.random() * chars.length)]
    }
    return result
  }

  hasPermission(permission: string): boolean {
    return this.permissions?.includes(permission) ?? false
  }

  hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => this.hasPermission(permission))
  }

  hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => this.hasPermission(permission))
  }

  addPermission(permission: string): void {
    if (!this.permissions) {
      this.permissions = []
    }
    if (!this.hasPermission(permission)) {
      this.permissions.push(permission)
    }
  }

  removePermission(permission: string): void {
    if (this.permissions) {
      this.permissions = this.permissions.filter(p => p !== permission)
    }
  }

  get isExpired(): boolean {
    if (!this.expires_at) return false
    return new Date(this.expires_at) < new Date()
  }

  get isValid(): boolean {
    return this.is_active === true && !this.isExpired
  }

  get isInvalid(): boolean {
    return !this.isValid
  }

  activate(): void {
    this.is_active = true
  }

  deactivate(): void {
    this.is_active = false
  }

  expire(): void {
    this.expires_at = new Date().toISOString()
  }

  extendExpiration(days: number): void {
    const newExpiry = new Date()
    newExpiry.setDate(newExpiry.getDate() + days)
    this.expires_at = newExpiry.toISOString()
  }

  getDaysUntilExpiration(): number | null {
    if (!this.expires_at) return null
    const now = new Date()
    const expiry = new Date(this.expires_at)
    const diffTime = expiry.getTime() - now.getTime()
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  }

  getMaskedKey(): string {
    if (this.key.length <= 8) {
      return '*'.repeat(this.key.length)
    }
    return this.key.substring(0, 4) + '*'.repeat(this.key.length - 8) + this.key.substring(this.key.length - 4)
  }

  // Static methods
  static active() {
    return { where: () => ({ where: () => [] }) }
  }

  static inactive() {
    return { where: () => ({ where: () => [] }) }
  }

  static expired() {
    return { where: () => ({ where: () => [] }) }
  }

  static valid() {
    return { where: () => ({ where: () => [] }) }
  }

  static byUser(userId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static withPermission(permission: string) {
    return { where: () => ({ where: () => [] }) }
  }
} 