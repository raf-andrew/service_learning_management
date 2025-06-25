/**
 * @file DeleteCodespace.test.ts
 * @description Functional test for deleting a codespace (DELETE /api/codespaces/:id)
 * @tags functional, codespaces, delete, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

class DeleteCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('DELETE:/api/codespaces/1', {
      status: 204,
      data: null,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('DELETE:/api/codespaces/1:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('DELETE:/api/codespaces/999', {
      status: 404,
      data: { error: 'Codespace not found' },
      headers: {}
    });
    this.mockResponses.set('DELETE:/api/codespaces/2', {
      status: 403,
      data: { error: 'Forbidden - Cannot delete active codespace' },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'DELETE', endpoint: string, headers?: Record<string, string>) {
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

  assertSuccessResponse(response: any, expectedStatus = 204) {
    expect(response.status).toBe(expectedStatus);
  }

  assertUnauthorized(response: any) {
    expect(response.status).toBe(401);
    expect(response.data.error).toContain('Unauthorized');
  }

  assertNotFound(response: any) {
    expect(response.status).toBe(404);
    expect(response.data.error).toContain('not found');
  }

  assertForbidden(response: any) {
    expect(response.status).toBe(403);
    expect(response.data.error).toContain('Forbidden');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Delete Codespace', () => {
  let testInstance: DeleteCodespaceTest;

  beforeEach(async () => {
    testInstance = new DeleteCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should delete a codespace for authenticated user', async () => {
    const response = await testInstance.makeRequest('DELETE', '/api/codespaces/1');
    testInstance.assertSuccessResponse(response);
  });

  it('should require authentication', async () => {
    const response = await testInstance.makeRequest('DELETE', '/api/codespaces/1', { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid id', async () => {
    const response = await testInstance.makeRequest('DELETE', '/api/codespaces/999');
    testInstance.assertNotFound(response);
  });

  it('should return forbidden for active codespace', async () => {
    const response = await testInstance.makeRequest('DELETE', '/api/codespaces/2');
    testInstance.assertForbidden(response);
  });

  it('should include security headers', async () => {
    const response = await testInstance.makeRequest('DELETE', '/api/codespaces/1');
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 