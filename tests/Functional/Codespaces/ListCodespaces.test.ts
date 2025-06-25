/**
 * @file ListCodespaces.test.ts
 * @description Functional test for listing codespaces (GET /api/codespaces)
 * @tags functional, codespaces, list, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockCodespaces = [
  { id: 1, name: 'codespace1', repository: 'test-org/repo1', status: 'Available' },
  { id: 2, name: 'codespace2', repository: 'test-org/repo2', status: 'Building' }
];

class ListCodespacesTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('GET:/api/codespaces', {
      status: 200,
      data: mockCodespaces,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/codespaces:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
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

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - List Codespaces', () => {
  let testInstance: ListCodespacesTest;

  beforeEach(async () => {
    testInstance = new ListCodespacesTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should list all codespaces for authenticated user', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces');
    testInstance.assertSuccessResponse(response);
    expect(Array.isArray(response.data)).toBe(true);
    expect(response.data.length).toBe(2);
    expect(response.data[0].name).toBe('codespace1');
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('GET', '/api/codespaces');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 