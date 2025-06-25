/**
 * @fileoverview HealthAlert model interface and implementation
 * @tags models,health,alerts
 * @description TypeScript interface and class for HealthAlert model with Laravel-like functionality
 */

export interface HealthAlertData {
  id?: number
  title: string
  message?: string
  level?: string
  status?: string
  resolved_at?: string | null
  metadata?: Record<string, any>
  service_name?: string
  type?: string
  created_at?: string
  updated_at?: string
}

export class HealthAlert {
  public id?: number
  public title: string = ''
  public message: string | null = null
  public level: string | null = null
  public status: string | null = null
  public resolved_at: string | null = null
  public metadata: Record<string, any> | null = null
  public service_name: string | null = null
  public type: string | null = null
  public created_at?: string
  public updated_at?: string

  public fillable = [
    'title',
    'message',
    'level',
    'status',
    'resolved_at',
    'metadata',
    'service_name',
    'type'
  ]

  constructor(data?: Partial<HealthAlertData>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<HealthAlertData>): this {
    Object.assign(this, data)
    return this
  }

  getTable(): string {
    return 'health_alerts'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      title: ['required', 'string', 'max:255'],
      message: ['string', 'max:1000'],
      level: ['string', 'in:info,warning,critical'],
      status: ['string', 'max:100'],
      metadata: ['array'],
      service_name: ['string', 'max:255'],
      type: ['string', 'max:100']
    }
  }

  // Methods
  isResolved(): boolean {
    return this.resolved_at !== null
  }

  isActive(): boolean {
    return this.resolved_at === null
  }

  isCritical(): boolean {
    return this.level === 'critical'
  }

  isWarning(): boolean {
    return this.level === 'warning'
  }

  isInfo(): boolean {
    return this.level === 'info'
  }

  resolve(): boolean {
    if (this.isResolved()) {
      return false
    }
    this.resolved_at = new Date().toISOString()
    return true
  }

  getMetadataValue(key: string): any {
    return this.metadata?.[key] ?? null
  }

  setMetadataValue(key: string, value: any): void {
    if (!this.metadata) {
      this.metadata = {}
    }
    this.metadata[key] = value
  }

  hasMetadata(): boolean {
    return this.metadata !== null && Object.keys(this.metadata || {}).length > 0
  }

  getAlertAge(): number {
    if (!this.created_at) return 0
    const created = new Date(this.created_at)
    const now = new Date()
    return Math.floor((now.getTime() - created.getTime()) / 1000)
  }

  getResolutionTime(): number | null {
    if (!this.resolved_at || !this.created_at) return null
    const created = new Date(this.created_at)
    const resolved = new Date(this.resolved_at)
    return Math.floor((resolved.getTime() - created.getTime()) / 1000)
  }

  // Static methods
  static active() {
    return { where: () => ({ where: () => [] }) }
  }

  static critical() {
    return { where: () => ({ where: () => [] }) }
  }

  static warning() {
    return { where: () => ({ where: () => [] }) }
  }

  static ofType(type: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static forService(serviceName: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static unresolved() {
    return { where: () => ({ where: () => [] }) }
  }

  static resolved() {
    return { where: () => ({ where: () => [] }) }
  }
} 