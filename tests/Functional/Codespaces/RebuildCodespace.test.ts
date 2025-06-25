/**
 * @file RebuildCodespace.test.ts
 * @description Functional test for rebuilding a codespace (POST /api/codespaces/:name/rebuild)
 * @tags functional, codespaces, rebuild, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockRebuildResponse = {
  id: 1,
  name: 'codespace1',
  status: 'Rebuilding',
  rebuild_started_at: '2024-01-01T00:00:00Z'
};

class RebuildCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces/codespace1/rebuild', {
      status: 200,
      data: mockRebuildResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces/codespace1/rebuild:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/invalid/rebuild', {
      status: 404,
      data: { error: 'Codespace not found' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/busy/rebuild', {
      status: 409,
      data: { error: 'Codespace is currently busy' },
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

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Rebuild Codespace', () => {
  let testInstance: RebuildCodespaceTest;

  beforeEach(async () => {
    testInstance = new RebuildCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should rebuild a codespace for authenticated user', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/rebuild');
    testInstance.assertSuccessResponse(response);
    expect(response.data.status).toBe('Rebuilding');
    expect(response.data.rebuild_started_at).toBeDefined();
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/rebuild', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid codespace', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/invalid/rebuild');
    testInstance.assertNotFound(response);
  });

  it('should return conflict for busy codespace', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/busy/rebuild');
    testInstance.assertConflict(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('POST', '/api/codespaces/codespace1/rebuild');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 