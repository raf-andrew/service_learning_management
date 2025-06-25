/**
 * @file UpdateCodespace.test.ts
 * @description Functional test for updating a codespace (PUT /api/codespaces/:id)
 * @tags functional, codespaces, update, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockUpdatedCodespace = {
  id: 1,
  name: 'updated_codespace',
  repository: 'test-org/updated_repo',
  status: 'Available'
};

class UpdateCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('PUT:/api/codespaces/1', {
      status: 200,
      data: mockUpdatedCodespace,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('PUT:/api/codespaces/1:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('PUT:/api/codespaces/999', {
      status: 404,
      data: { error: 'Codespace not found' },
      headers: {}
    });
    this.mockResponses.set('PUT:/api/codespaces/1:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { name: ['The name field is required.'] } },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'PUT', endpoint: string, data?: any, headers?: Record<string, string>) {
    const requestHeaders = {
      'Authorization': this.context.authToken,
      ...headers
    };
    if (!requestHeaders.Authorization || requestHeaders.Authorization === '') {
      return this.mockResponses.get(`${method}:${endpoint}:unauthorized`);
    }
    if (endpoint.includes('/999')) {
      return this.mockResponses.get(`${method}:${endpoint}`);
    }
    if (!data || !data.name) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
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

  assertValidationError(response: any) {
    expect(response.status).toBe(422);
    expect(response.data.error).toContain('Validation failed');
    expect(response.data.errors).toBeDefined();
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Update Codespace', () => {
  let testInstance: UpdateCodespaceTest;

  beforeEach(async () => {
    testInstance = new UpdateCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should update a codespace for authenticated user', async () => {
    const updateData = { name: 'updated_codespace', repository: 'test-org/updated_repo' };
    const response = await testInstance.makeRequest('PUT', '/api/codespaces/1', updateData);
    testInstance.assertSuccessResponse(response);
    expect(response.data.name).toBe('updated_codespace');
    expect(response.data.repository).toBe('test-org/updated_repo');
  });

  it('should require authentication', async () => {
    const updateData = { name: 'updated_codespace', repository: 'test-org/updated_repo' };
    const response = await testInstance.makeRequest('PUT', '/api/codespaces/1', updateData, { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should return not found for invalid id', async () => {
    const updateData = { name: 'updated_codespace', repository: 'test-org/updated_repo' };
    const response = await testInstance.makeRequest('PUT', '/api/codespaces/999', updateData);
    testInstance.assertNotFound(response);
  });

  it('should validate required fields', async () => {
    const updateData = { repository: 'test-org/updated_repo' };
    const response = await testInstance.makeRequest('PUT', '/api/codespaces/1', updateData);
    testInstance.assertValidationError(response);
  });

  it('should include security headers', async () => {
    const updateData = { name: 'updated_codespace', repository: 'test-org/updated_repo' };
    const response = await testInstance.makeRequest('PUT', '/api/codespaces/1', updateData);
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 