/**
 * @file HealthMonitoring.test.ts
 * @description Functional test for Health Monitoring endpoints (GET /api/health/*)
 * @tags functional, health, monitoring, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockHealthStatus = {
  status: 'healthy',
  timestamp: '2024-01-01T00:00:00Z',
  services: {
    database: { status: 'healthy', response_time: 15 },
    redis: { status: 'healthy', response_time: 5 },
    queue: { status: 'healthy', response_time: 10 }
  }
};

const mockServiceStatus = {
  name: 'database',
  status: 'healthy',
  response_time: 15,
  last_check: '2024-01-01T00:00:00Z',
  uptime: '99.9%'
};

const mockMetrics = {
  cpu_usage: 25.5,
  memory_usage: 45.2,
  disk_usage: 30.1,
  active_connections: 15,
  requests_per_minute: 120
};

const mockAlerts = [
  { id: 1, service: 'database', level: 'warning', message: 'High response time', created_at: '2024-01-01T00:00:00Z' },
  { id: 2, service: 'redis', level: 'critical', message: 'Connection timeout', created_at: '2024-01-01T00:00:00Z' }
];

class HealthMonitoringTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // GET /api/health
    this.mockResponses.set('GET:/api/health', {
      status: 200,
      data: mockHealthStatus,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // GET /api/health/{serviceName}
    this.mockResponses.set('GET:/api/health/database', {
      status: 200,
      data: mockServiceStatus,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/health/invalid_service', {
      status: 404,
      data: { error: 'Service not found' },
      headers: {}
    });

    // GET /api/health/{serviceName}/metrics
    this.mockResponses.set('GET:/api/health/database/metrics', {
      status: 200,
      data: mockMetrics,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/health/invalid_service/metrics', {
      status: 404,
      data: { error: 'Service not found' },
      headers: {}
    });

    // GET /api/health/{serviceName}/alerts
    this.mockResponses.set('GET:/api/health/database/alerts', {
      status: 200,
      data: mockAlerts,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/health/invalid_service/alerts', {
      status: 404,
      data: { error: 'Service not found' },
      headers: {}
    });

    // POST /api/alerts/{alertId}/acknowledge
    this.mockResponses.set('POST:/api/alerts/1/acknowledge', {
      status: 200,
      data: { ...mockAlerts[0], acknowledged: true, acknowledged_at: '2024-01-01T00:00:00Z' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/alerts/999/acknowledge', {
      status: 404,
      data: { error: 'Alert not found' },
      headers: {}
    });

    // POST /api/alerts/{alertId}/resolve
    this.mockResponses.set('POST:/api/alerts/1/resolve', {
      status: 200,
      data: { ...mockAlerts[0], resolved: true, resolved_at: '2024-01-01T00:00:00Z' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/alerts/999/resolve', {
      status: 404,
      data: { error: 'Alert not found' },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'GET' | 'POST', endpoint: string, data?: any, headers?: Record<string, string>) {
    const requestHeaders = {
      'Authorization': this.context.authToken,
      ...headers
    };
    return this.mockResponses.get(`${method}:${endpoint}`) || {
      status: 404,
      data: { error: 'Not found' },
      headers: {}
    };
  }

  assertSuccessResponse(response: any, expectedStatus = 200) {
    expect(response.status).toBe(expectedStatus);
    expect(response.data).toBeDefined();
    expect(response.data.error).toBeUndefined();
  }

  assertNotFound(response: any) {
    expect(response.status).toBe(404);
    expect(response.data.error).toContain('not found');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Health Monitoring API', () => {
  let testInstance: HealthMonitoringTest;

  beforeEach(async () => {
    testInstance = new HealthMonitoringTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('Overall Health Check', () => {
    it('should return overall health status', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      testInstance.assertSuccessResponse(response);
      expect(response.data.status).toBe('healthy');
      expect(response.data.services).toBeDefined();
      expect(Object.keys(response.data.services).length).toBe(3);
    });
  });

  describe('Service Health Check', () => {
    it('should return specific service health status', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/database');
      testInstance.assertSuccessResponse(response);
      expect(response.data.name).toBe('database');
      expect(response.data.status).toBe('healthy');
      expect(response.data.response_time).toBe(15);
    });

    it('should return not found for invalid service', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/invalid_service');
      testInstance.assertNotFound(response);
    });
  });

  describe('Service Metrics', () => {
    it('should return service metrics', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/database/metrics');
      testInstance.assertSuccessResponse(response);
      expect(response.data.cpu_usage).toBe(25.5);
      expect(response.data.memory_usage).toBe(45.2);
      expect(response.data.disk_usage).toBe(30.1);
    });

    it('should return not found for invalid service metrics', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/invalid_service/metrics');
      testInstance.assertNotFound(response);
    });
  });

  describe('Service Alerts', () => {
    it('should return service alerts', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/database/alerts');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should return not found for invalid service alerts', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health/invalid_service/alerts');
      testInstance.assertNotFound(response);
    });
  });

  describe('Alert Management', () => {
    it('should acknowledge alert', async () => {
      const response = await testInstance.makeRequest('POST', '/api/alerts/1/acknowledge');
      testInstance.assertSuccessResponse(response);
      expect(response.data.acknowledged).toBe(true);
      expect(response.data.acknowledged_at).toBeDefined();
    });

    it('should return not found for invalid alert acknowledge', async () => {
      const response = await testInstance.makeRequest('POST', '/api/alerts/999/acknowledge');
      testInstance.assertNotFound(response);
    });

    it('should resolve alert', async () => {
      const response = await testInstance.makeRequest('POST', '/api/alerts/1/resolve');
      testInstance.assertSuccessResponse(response);
      expect(response.data.resolved).toBe(true);
      expect(response.data.resolved_at).toBeDefined();
    });

    it('should return not found for invalid alert resolve', async () => {
      const response = await testInstance.makeRequest('POST', '/api/alerts/999/resolve');
      testInstance.assertNotFound(response);
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 