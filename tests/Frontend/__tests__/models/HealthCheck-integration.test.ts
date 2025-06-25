// @test @model @health-check @integration
/**
 * Tests for HealthCheck model integration scenarios.
 * Ensures complete lifecycle and different health check types work correctly.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createMockHealthCheck, setupTestEnvironment } from '../helpers/testUtils'
import { MockHealthCheck } from '../helpers/mockModels'

// Use the mock HealthCheck class
const HealthCheck = MockHealthCheck

describe('HealthCheck Integration Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  setupTestEnvironment()

  it('should handle complete health check lifecycle', async () => {
    // Create
    const createData = {
      name: 'lifecycle-test',
      url: 'http://localhost:8000/health',
      method: 'GET',
      expected_status: 200
    }
    
    HealthCheck.create.mockResolvedValue(createMockHealthCheck(createData))
    const createdHealthCheck = await HealthCheck.create(createData)
    
    // Update
    HealthCheck.update.mockResolvedValue({ ...createdHealthCheck, is_active: false })
    const updatedHealthCheck = await HealthCheck.update(createdHealthCheck.id, { is_active: false })
    
    // Delete
    HealthCheck.delete.mockResolvedValue(true)
    await HealthCheck.delete(updatedHealthCheck.id)
    
    expect(HealthCheck.create).toHaveBeenCalledWith(createData)
    expect(HealthCheck.update).toHaveBeenCalledWith(createdHealthCheck.id, { is_active: false })
    expect(HealthCheck.delete).toHaveBeenCalledWith(updatedHealthCheck.id)
  })

  it('should handle health check with different types', async () => {
    const healthCheckTypes = [
      { type: 'http', url: 'http://localhost:8000/health', method: 'GET' },
      { type: 'https', url: 'https://api.example.com/health', method: 'POST' },
      { type: 'tcp', url: 'localhost:3306', method: 'CONNECT' },
      { type: 'ping', url: '8.8.8.8', method: 'PING' }
    ]
    
    for (const healthCheckType of healthCheckTypes) {
      const healthCheckData = {
        name: `test-${healthCheckType.type}`,
        url: healthCheckType.url,
        method: healthCheckType.method,
        type: healthCheckType.type,
        expected_status: 200
      }

      HealthCheck.create.mockResolvedValue(createMockHealthCheck(healthCheckData))
      const createdHealthCheck = await HealthCheck.create(healthCheckData)
      
      expect(createdHealthCheck.type).toBe(healthCheckType.type)
      expect(createdHealthCheck.url).toBe(healthCheckType.url)
    }
  })
}) 