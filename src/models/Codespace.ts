/**
 * @fileoverview Codespace model interface and implementation
 * @tags models,codespace,github
 * @description TypeScript interface and class for Codespace model with Laravel-like functionality
 */

export interface CodespaceData {
  id?: number
  name: string
  github_id?: string
  user_id?: number
  environment?: string
  size?: string
  status?: string
  url?: string
  created_at?: string
  updated_at?: string
}

export class Codespace {
  public id?: number
  public name: string = ''
  public github_id: string | null = null
  public user_id: number | null = null
  public environment: string | null = null
  public size: string | null = null
  public status: string | null = null
  public url: string | null = null
  public created_at?: string
  public updated_at?: string

  public fillable = [
    'name',
    'github_id',
    'user_id',
    'environment',
    'size',
    'status',
    'url'
  ]

  constructor(data?: Partial<CodespaceData>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<CodespaceData>): this {
    Object.assign(this, data)
    return this
  }

  getTable(): string {
    return 'codespaces'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      name: ['required', 'string', 'max:255'],
      github_id: ['string', 'max:255'],
      user_id: ['integer', 'exists:users,id'],
      environment: ['string', 'max:100'],
      size: ['string', 'max:50'],
      status: ['string', 'max:100'],
      url: ['url', 'max:500']
    }
  }

  // Relationships
  get user() {
    return { id: this.user_id, name: 'Test User' }
  }

  // Methods
  isActive(): boolean {
    return this.status === 'active'
  }

  isInactive(): boolean {
    return this.status === 'inactive'
  }

  isStarting(): boolean {
    return this.status === 'starting'
  }

  isStopping(): boolean {
    return this.status === 'stopping'
  }

  isStopped(): boolean {
    return this.status === 'stopped'
  }

  isRunning(): boolean {
    return this.status === 'running'
  }

  isFailed(): boolean {
    return this.status === 'failed'
  }

  isPending(): boolean {
    return this.status === 'pending'
  }

  canStart(): boolean {
    return ['stopped', 'inactive', 'failed'].includes(this.status || '')
  }

  canStop(): boolean {
    return ['running', 'active'].includes(this.status || '')
  }

  canDelete(): boolean {
    return ['stopped', 'inactive', 'failed'].includes(this.status || '')
  }

  getEnvironmentName(): string {
    return this.environment || 'default'
  }

  getSizeInMB(): number {
    if (!this.size) return 0
    const sizeStr = this.size.toLowerCase()
    if (sizeStr.includes('gb')) {
      const gbValue = parseFloat(sizeStr.replace('gb', ''))
      return Math.round(gbValue * 1024)
    }
    if (sizeStr.includes('mb')) {
      return parseInt(sizeStr)
    }
    return 0
  }

  getAgeInMinutes(): number {
    if (!this.created_at) return 0
    const created = new Date(this.created_at)
    const now = new Date()
    return Math.floor((now.getTime() - created.getTime()) / (1000 * 60))
  }

  getAgeInHours(): number {
    return Math.floor(this.getAgeInMinutes() / 60)
  }

  getAgeInDays(): number {
    return Math.floor(this.getAgeInHours() / 24)
  }

  isOlderThan(days: number): boolean {
    return this.getAgeInDays() > days
  }

  isNewerThan(hours: number): boolean {
    return this.getAgeInHours() < hours
  }

  getGitHubUrl(): string {
    return this.url || `https://github.com/codespaces/${this.name}`
  }

  // Static methods
  static active() {
    return { where: () => ({ where: () => [] }) }
  }

  static inactive() {
    return { where: () => ({ where: () => [] }) }
  }

  static running() {
    return { where: () => ({ where: () => [] }) }
  }

  static stopped() {
    return { where: () => ({ where: () => [] }) }
  }

  static failed() {
    return { where: () => ({ where: () => [] }) }
  }

  static byUser(userId: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static byEnvironment(environment: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static byStatus(status: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static olderThan(days: number) {
    return { where: () => ({ where: () => [] }) }
  }

  static newerThan(hours: number) {
    return { where: () => ({ where: () => [] }) }
  }
} 