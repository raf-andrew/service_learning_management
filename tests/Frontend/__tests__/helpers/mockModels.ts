/**
 * @file mockModels.ts
 * @description Comprehensive mock model classes for testing
 * @tags mock-models, test-helpers, laravel-models
 */

import { vi } from 'vitest'

// Base Model class
export class MockModel {
  static find = vi.fn()
  static findById = vi.fn()
  static create = vi.fn()
  static update = vi.fn()
  static delete = vi.fn()
  static where = vi.fn()
  static with = vi.fn()
  static belongsTo = vi.fn()
  static hasMany = vi.fn()
  static hasOne = vi.fn()
  static scopeActive = vi.fn()
  static scopeByUser = vi.fn()
  static scopeByStatus = vi.fn()
  static scopeCritical = vi.fn()
  static scopeWarning = vi.fn()
  static scopeOfType = vi.fn()
  static scopeForService = vi.fn()
  static scopeByType = vi.fn()
  static active = vi.fn()
  static is_active = true
  static is_resolved = false
  static resolved_at = null
  static configuration = {}
}

// ApiKey Model
export class MockApiKey extends MockModel {
  static generateKey = vi.fn()
  static scopeByUser = vi.fn()
  static scopeByStatus = vi.fn()
  static scopeActive = vi.fn()
}

// HealthAlert Model
export class MockHealthAlert extends MockModel {
  static scopeActive = vi.fn()
  static scopeCritical = vi.fn()
  static scopeWarning = vi.fn()
  static scopeOfType = vi.fn()
  static scopeForService = vi.fn()
}

// HealthCheck Model
export class MockHealthCheck extends MockModel {
  static scopeActive = vi.fn()
  static scopeByType = vi.fn()
  static scopeByStatus = vi.fn()
}

// Codespace Model
export class MockCodespace extends MockModel {
  static scopeActive = vi.fn()
  static scopeByUser = vi.fn()
  static scopeByStatus = vi.fn()
}

// User Model
export class MockUser extends MockModel {
  static scopeActive = vi.fn()
  static scopeByRole = vi.fn()
  static scopeByStatus = vi.fn()
}

// HealthMetric Model
export class MockHealthMetric extends MockModel {
  static scopeActive = vi.fn()
  static scopeByService = vi.fn()
  static scopeByType = vi.fn()
}

// Service Model
export class MockService extends MockModel {
  static scopeActive = vi.fn()
  static scopeByType = vi.fn()
  static scopeByStatus = vi.fn()
}

// Notification Model
export class MockNotification extends MockModel {
  static scopeActive = vi.fn()
  static scopeByType = vi.fn()
  static scopeByUser = vi.fn()
}

// Log Model
export class MockLog extends MockModel {
  static scopeActive = vi.fn()
  static scopeByLevel = vi.fn()
  static scopeByService = vi.fn()
}

// Setting Model
export class MockSetting extends MockModel {
  static scopeActive = vi.fn()
  static scopeByGroup = vi.fn()
  static scopeByKey = vi.fn()
}

// Permission Model
export class MockPermission extends MockModel {
  static scopeActive = vi.fn()
  static scopeByRole = vi.fn()
  static scopeByResource = vi.fn()
}

// Role Model
export class MockRole extends MockModel {
  static scopeActive = vi.fn()
  static scopeByUser = vi.fn()
  static scopeByPermission = vi.fn()
}

// Export all mock models
export const MockModels = {
  User: MockUser,
  Codespace: MockCodespace,
  ApiKey: MockApiKey,
  HealthAlert: MockHealthAlert,
  HealthCheck: MockHealthCheck
} 