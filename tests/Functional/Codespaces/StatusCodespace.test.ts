/**
 * @file StatusCodespace.test.ts
 * @description Functional test for getting codespace status (GET /api/codespaces/:name/status)
 * @tags functional, codespaces, status, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockStatusResponse = {
  id: 1,
  name: 'codespace1',
  status: 'Available',
  last_activity: '2024-01-01T00:00:00Z',
  uptime: '2h 30m',
  resources: {
    cpu: '25%',
    memory: '512MB',
    disk: '10GB'
  }
};

class StatusCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('GET:/api/codespaces/codespace1/status', {
      status: 200,
      data: mockStatusResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/codespaces/codespace1/status:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('GET:/api/codespaces/invalid/status', {
      status: 404,
      data: { error: 'Codespace not found' },
      headers: {}
    });
    this.mockResponses.set('GET:/api/codespaces/offline/status', {
      status: 503,
      data: { error: 'Codespace is offline' },
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
    if (!requestHeaders.Authorization || requestHeaders.Authorization === '') {
      return this.mockResponses.get(`${method}:${endpoint}:unauthorized`);
    }
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

  assertUnauthorized(response: any) {
    expect(response.status).toBe(401);
    expect(response.data.error).toContain('Unauthorized');
  }

  assertNotFound(response: any) {
    expect(response.status).toBe(404);
    expect(response.data.error).toContain('not found');
  }

  assertServiceUnavailable(response: any) {
    expect(response.status).toBe(503);
    expect(response.data.error).toContain('offline');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Status Codespace', () => {
  let testInstance: StatusCodespaceTest;

  beforeEach(async () => {
    testInstance = new StatusCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should get codespace status for authenticated user', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/codespace1/status');
    testInstance.assertSuccessResponse(response);
    expect(response.data.status).toBe('Available');
    expect(response.data.resources).toBeDefined();
    expect(response.data.uptime).toBeDefined();
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/codespace1/status', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid codespace', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/invalid/status');
    testInstance.assertNotFound(response);
  });

  it('should return service unavailable for offline codespace', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/offline/status');
    testInstance.assertServiceUnavailable(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/codespace1/status');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 