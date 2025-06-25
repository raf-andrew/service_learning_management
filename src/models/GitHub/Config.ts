/**
 * @fileoverview GitHub Config model interface
 * @tags models,github,config
 * @description TypeScript interface for GitHub Config model
 */

export interface GitHubConfig {
  id?: number
  key: string
  value: string
  group?: string
  is_encrypted?: boolean
  description?: string
  created_at?: string
  updated_at?: string
}

export class Config {
  public id?: number
  public key: string = ''
  public value: string = ''
  public type: string | null = null
  public group?: string
  public is_encrypted?: boolean
  public description: string | null = null
  public created_at?: string
  public updated_at?: string
  public fillable = ['key', 'value', 'type', 'description']

  constructor(data?: Partial<GitHubConfig>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<GitHubConfig>): this {
    Object.assign(this, data)
    if (typeof this.type === 'undefined') this.type = null
    if (typeof this.description === 'undefined') this.description = null
    return this
  }

  getTable(): string {
    return 'github_configs'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      key: ['required', 'string', 'max:255'],
      value: ['required', 'string'],
      type: ['in:string,integer,boolean,array,object'],
      group: ['string', 'max:100'],
      is_encrypted: ['boolean']
    }
  }

  get repository() {
    return { id: 1, name: 'test-repo' }
  }

  get user() {
    return { id: 1, name: 'Test User' }
  }

  static byKey(key: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static byGroup(group: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static byRepository(repoId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static byUser(userId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static byType(type: string) {
    return { where: () => ({ where: () => [] }) }
  }

  getTypedValue(): any {
    if (!this.value) return null
    try {
      if (this.is_encrypted) {
        // Mock decryption for testing
        return this.value
      }
      // Try to parse as JSON first
      const parsed = JSON.parse(this.value)
      return parsed
    } catch {
      // If not JSON, return as string
      if (this.type === 'integer') return parseInt(this.value, 10)
      if (this.type === 'boolean') return this.value === 'true'
      return this.value
    }
  }

  setTypedValue(value: any): void {
    if (typeof value === 'object') {
      this.value = JSON.stringify(value)
    } else {
      this.value = String(value)
    }
  }

  isSensitive(): boolean {
    const sensitiveKeys = ['token', 'password', 'secret', 'key', 'api_key']
    return sensitiveKeys.some(key => this.key.toLowerCase().includes(key))
  }

  getMaskedValue(): string {
    if (!this.isSensitive()) {
      return this.value
    }
    if (!this.value) return ''
    if (this.value.length <= 4) {
      return '*'.repeat(this.value.length)
    }
    return this.value.substring(0, 4) + '*'.repeat(this.value.length - 4)
  }

  static getToken(): string | null {
    // Mock implementation for testing
    return process.env.GITHUB_TOKEN || null
  }

  static getRepository(): string | null {
    // Mock implementation for testing
    return process.env.GITHUB_REPOSITORY || null
  }
} 