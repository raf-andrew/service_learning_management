/**
 * @file ShowCodespace.test.ts
 * @description Functional test for showing a single codespace (GET /api/codespaces/:id)
 * @tags functional, codespaces, show, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockCodespace = {
  id: 1,
  name: 'codespace1',
  repository: 'test-org/repo1',
  status: 'Available'
};

class ShowCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('GET:/api/codespaces/1', {
      status: 200,
      data: mockCodespace,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/codespaces/1:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('GET:/api/codespaces/999', {
      status: 404,
      data: { error: 'Codespace not found' },
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

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Show Codespace', () => {
  let testInstance: ShowCodespaceTest;

  beforeEach(async () => {
    testInstance = new ShowCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should show a codespace for authenticated user', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/1');
    testInstance.assertSuccessResponse(response);
    expect(response.data.name).toBe('codespace1');
    expect(response.data.repository).toBe('test-org/repo1');
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/1', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid id', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/999');
    testInstance.assertNotFound(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces/1');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 