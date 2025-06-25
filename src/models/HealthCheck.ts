/**
 * @fileoverview HealthCheck model interface and implementation
 * @tags models,health,monitoring
 * @description TypeScript interface and class for HealthCheck model with Laravel-like functionality
 */

export interface HealthCheckData {
  id?: number
  name: string
  type?: string
  target?: string
  config?: Record<string, any>
  timeout?: number
  retry_attempts?: number
  retry_delay?: number
  is_active?: boolean
  last_check?: string
  status?: string
  message?: string
  created_at?: string
  updated_at?: string
}

export class HealthCheck {
  public id?: number
  public name: string = ''
  public type: string | null = null
  public target: string | null = null
  public config: Record<string, any> | null = null
  public timeout: number | null = null
  public retry_attempts: number | null = null
  public retry_delay: number | null = null
  public is_active: boolean | null = null
  public last_check: string | null = null
  public status: string | null = null
  public message: string | null = null
  public created_at?: string
  public updated_at?: string

  public fillable = [
    'name',
    'type',
    'target',
    'config',
    'timeout',
    'retry_attempts',
    'retry_delay',
    'is_active',
    'last_check',
    'status',
    'message'
  ]

  constructor(data?: Partial<HealthCheckData>) {
    if (data) {
      this.fill(data)
    }
  }

  fill(data: Partial<HealthCheckData>): this {
    Object.assign(this, data)
    return this
  }

  getTable(): string {
    return 'health_checks'
  }

  getKeyName(): string {
    return 'id'
  }

  getValidationRules(): Record<string, string[]> {
    return {
      name: ['required', 'string', 'max:255'],
      type: ['string', 'max:100'],
      target: ['string', 'max:500'],
      config: ['array'],
      timeout: ['integer', 'min:1'],
      retry_attempts: ['integer', 'min:0'],
      retry_delay: ['integer', 'min:0'],
      is_active: ['boolean'],
      status: ['string', 'max:100'],
      message: ['string', 'max:1000']
    }
  }

  // Relationships
  get results() {
    return []
  }

  get metrics() {
    return []
  }

  get alerts(): Array<{ resolved_at?: string | null }> {
    return []
  }

  // Methods
  getLatestResult(): any {
    return this.results.length > 0 ? this.results[0] : null
  }

  getLatestMetrics(): any {
    return this.metrics.length > 0 ? this.metrics[0] : null
  }

  getActiveAlerts(): Array<{ resolved_at?: string | null }> {
    return this.alerts.filter(alert => !alert.resolved_at)
  }

  isActive(): boolean {
    return this.is_active === true
  }

  isInactive(): boolean {
    return this.is_active === false
  }

  isHealthy(): boolean {
    return this.status === 'healthy'
  }

  isUnhealthy(): boolean {
    return this.status === 'unhealthy'
  }

  isWarning(): boolean {
    return this.status === 'warning'
  }

  isCritical(): boolean {
    return this.status === 'critical'
  }

  hasConfig(): boolean {
    return this.config !== null && Object.keys(this.config || {}).length > 0
  }

  getConfigValue(key: string): any {
    return this.config?.[key] ?? null
  }

  setConfigValue(key: string, value: any): void {
    if (!this.config) {
      this.config = {}
    }
    this.config[key] = value
  }

  getTimeout(): number {
    return this.timeout ?? 30
  }

  getRetryAttempts(): number {
    return this.retry_attempts ?? 3
  }

  getRetryDelay(): number {
    return this.retry_delay ?? 5
  }

  shouldRetry(): boolean {
    return this.getRetryAttempts() > 0
  }

  // Static methods
  static active() {
    return { where: () => ({ where: () => [] }) }
  }

  static inactive() {
    return { where: () => ({ where: () => [] }) }
  }

  static healthy() {
    return { where: () => ({ where: () => [] }) }
  }

  static unhealthy() {
    return { where: () => ({ where: () => [] }) }
  }

  static byType(type: string) {
    return { where: () => ({ where: () => [] }) }
  }

  static byStatus(status: string) {
    return { where: () => ({ where: () => [] }) }
  }
} 