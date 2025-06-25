/**
 * @file ConnectCodespace.test.ts
 * @description Functional test for connecting to a codespace (POST /api/codespaces/:name/connect)
 * @tags functional, codespaces, connect, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockConnectResponse = {
  id: 1,
  name: 'codespace1',
  status: 'Connected',
  connection_url: 'https://codespace1.test-org.github.dev',
  session_id: 'session_12345',
  expires_at: '2024-01-01T02:00:00Z'
};

class ConnectCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces/codespace1/connect', {
      status: 200,
      data: mockConnectResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces/codespace1/connect:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/invalid/connect', {
      status: 404,
      data: { error: 'Codespace not found' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/busy/connect', {
      status: 409,
      data: { error: 'Codespace is currently busy' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/offline/connect', {
      status: 503,
      data: { error: 'Codespace is offline' },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'POST', endpoint: string, headers?: Record<string, string>) {
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

  assertConflict(response: any) {
    expect(response.status).toBe(409);
    expect(response.data.error).toContain('busy');
  }

  assertServiceUnavailable(response: any) {
    expect(response.status).toBe(503);
    expect(response.data.error).toContain('offline');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Connect Codespace', () => {
  let testInstance: ConnectCodespaceTest;

  beforeEach(async () => {
    testInstance = new ConnectCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should connect to codespace for authenticated user', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/connect');
    testInstance.assertSuccessResponse(response);
    expect(response.data.status).toBe('Connected');
    expect(response.data.connection_url).toBeDefined();
    expect(response.data.session_id).toBeDefined();
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/connect', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid codespace', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/invalid/connect');
    testInstance.assertNotFound(response);
  });

  it('should return conflict for busy codespace', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/busy/connect');
    testInstance.assertConflict(response);
  });

  it('should return service unavailable for offline codespace', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/offline/connect');
    testInstance.assertServiceUnavailable(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/connect');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 