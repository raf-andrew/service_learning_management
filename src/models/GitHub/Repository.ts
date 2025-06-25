/**
 * @fileoverview GitHub Repository model interface
 * @tags models,github,repository
 * @description TypeScript interface for GitHub Repository model
 */

export interface GitHubRepository {
  id?: number
  name: string
  full_name?: string
  default_branch?: string
  settings?: {
    private?: boolean
    description?: string
    homepage?: string
    has_issues?: boolean
    has_wiki?: boolean
    has_pages?: boolean
  }
  permissions?: {
    admin?: boolean
    push?: boolean
    pull?: boolean
  }
  created_at?: string
  updated_at?: string
}

export class Repository {
  public id?: number
  public name: string = ''
  public full_name?: string
  public default_branch?: string
  public settings?: any
  public permissions?: any
  public created_at?: string
  public updated_at?: string

  constructor(data?: Partial<GitHubRepository>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<GitHubRepository>): this {
    Object.assign(this, data)
    return this
  }

  getTable(): string {
    return 'github_repositories'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      name: ['required', 'string', 'max:255'],
      full_name: ['string', 'max:500'],
      default_branch: ['string', 'max:100']
    }
  }

  get user() {
    return { id: 1, name: 'Test User' }
  }

  get features() {
    return []
  }

  get configs() {
    return []
  }

  static public() {
    return { where: () => ({ where: () => [] }) }
  }

  static private() {
    return { where: () => ({ where: () => [] }) }
  }

  static byUser(userId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  getFullUrl(): string {
    return this.full_name ? `https://github.com/${this.full_name}` : ''
  }

  isPublic(): boolean {
    return this.settings?.private === false
  }

  isPrivate(): boolean {
    return this.settings?.private === true
  }

  async syncFromGitHub(): Promise<this> {
    // Mock implementation for testing
    return this
  }

  async updateGitHubSettings(): Promise<this> {
    // Mock implementation for testing
    return this
  }
} 