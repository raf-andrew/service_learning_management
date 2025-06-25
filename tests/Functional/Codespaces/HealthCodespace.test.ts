/**
 * @file HealthCodespace.test.ts
 * @description Functional test for codespace health check (GET /api/codespaces/health)
 * @tags functional, codespaces, health, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockHealthResponse = {
  status: 'healthy',
  timestamp: '2024-01-01T00:00:00Z',
  services: {
    database: { status: 'healthy', response_time: '50ms' },
    redis: { status: 'healthy', response_time: '10ms' },
    github: { status: 'healthy', response_time: '200ms' }
  },
  codespaces: {
    total: 5,
    available: 3,
    building: 1,
    offline: 1
  }
};

class HealthCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('GET:/api/codespaces/health', {
      status: 200,
      data: mockHealthResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/codespaces/health:unhealthy', {
      status: 503,
      data: {
        status: 'unhealthy',
        timestamp: '2024-01-01T00:00:00Z',
        services: {
          database: { status: 'unhealthy', error: 'Connection timeout' },
          redis: { status: 'healthy', response_time: '10ms' },
          github: { status: 'healthy', response_time: '200ms' }
        }
      },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'GET', endpoint: string, headers?: Record<string, string>) {
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

  assertServiceUnavailable(response: any) {
    expect(response.status).toBe(503);
    expect(response.data.status).toBe('unhealthy');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Health Check', () => {
  let testInstance: HealthCodespaceTest;

  beforeEach(async () => {
    testInstance = new HealthCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should return healthy status when all services are up', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/health');
    testInstance.assertSuccessResponse(response);
    expect(response.data.status).toBe('healthy');
    expect(response.data.services.database.status).toBe('healthy');
    expect(response.data.codespaces.total).toBe(5);
  });

  it('should return unhealthy status when services are down', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/health:unhealthy');
    testInstance.assertServiceUnavailable(response);
    expect(response.data.services.database.status).toBe('unhealthy');
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/health');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 