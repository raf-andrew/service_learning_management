/**
 * @fileoverview GitHub Feature model interface
 * @tags models,github,feature
 * @description TypeScript interface for GitHub Feature model
 */

export interface GitHubFeature {
  id?: number
  name: string
  enabled?: boolean
  conditions?: Array<{
    type: 'environment' | 'config' | 'github'
    value?: any
    key?: string
    action?: string
  }>
  description?: string
  created_at?: string
  updated_at?: string
}

export class Feature {
  public id?: number
  public name: string = ''
  public enabled: boolean | null = null
  public config: Record<string, any> | null = null
  public description: string | null = null
  public created_at?: string
  public updated_at?: string
  public fillable = ['name', 'description', 'enabled', 'config']

  constructor(data?: Partial<GitHubFeature>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<GitHubFeature>): this {
    Object.assign(this, data)
    if (typeof this.description === 'undefined') this.description = null
    if (typeof this.enabled === 'undefined') this.enabled = null
    if (typeof this.config === 'undefined') this.config = null
    return this
  }

  getTable(): string {
    return 'github_features'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      name: ['required', 'string', 'max:255'],
      enabled: ['boolean'],
      config: ['json']
    }
  }

  get repository() {
    return { id: 1, name: 'test-repo' }
  }

  get user() {
    return { id: 1, name: 'Test User' }
  }

  static enabled() {
    return { where: () => ({ where: () => [] }) }
  }

  static disabled() {
    return { where: () => ({ where: () => [] }) }
  }

  static byRepository(repoId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static byUser(userId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  isEnabled(): boolean {
    return !!this.enabled
  }

  isDisabled(): boolean {
    return !this.isEnabled()
  }

  enable(): void {
    this.enabled = true
  }

  disable(): void {
    this.enabled = false
  }

  toggle(): void {
    this.enabled = !this.enabled
  }

  getConfigValue(key: string): any {
    if (!this.config) return null
    return this.config[key] ?? null
  }

  setConfigValue(key: string, value: any): void {
    if (!this.config) this.config = {}
    this.config[key] = value
  }

  private evaluateCondition(condition: any): boolean {
    const type = condition.type
    const value = condition.value
    switch (type) {
      case 'environment':
        return process.env.NODE_ENV === value
      case 'config':
        return this.getConfigValue(condition.key) === value
      case 'github':
        return this.evaluateGitHubCondition(condition)
      default:
        return false
    }
  }

  private evaluateGitHubCondition(condition: any): boolean {
    const action = condition.action
    const value = condition.value
    switch (action) {
      case 'branch':
        return process.env.GITHUB_BRANCH === value
      case 'token':
        return !!process.env.GITHUB_TOKEN
      default:
        return false
    }
  }
} 