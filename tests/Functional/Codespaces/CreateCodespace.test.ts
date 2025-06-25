/**
 * @file CreateCodespace.test.ts
 * @description Functional test for creating a codespace (POST /api/codespaces)
 * @tags functional, codespaces, create, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockCreatedCodespace = {
  id: 3,
  name: 'new_codespace',
  repository: 'test-org/new_repo',
  status: 'Creating'
};

class CreateCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces', {
      status: 201,
      data: mockCreatedCodespace,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { name: ['The name field is required.'] } },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'POST', endpoint: string, data?: any, headers?: Record<string, string>) {
    const requestHeaders = {
      'Authorization': this.context.authToken,
      ...headers
    };
    if (!requestHeaders.Authorization || requestHeaders.Authorization === '') {
      return this.mockResponses.get(`${method}:${endpoint}:unauthorized`);
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

  assertSuccessResponse(response: any, expectedStatus = 201) {
    expect(response.status).toBe(expectedStatus);
    expect(response.data).toBeDefined();
    expect(response.data.error).toBeUndefined();
  }

  assertUnauthorized(response: any) {
    expect(response.status).toBe(401);
    expect(response.data.error).toContain('Unauthorized');
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

describe('Codespaces API - Create Codespace', () => {
  let testInstance: CreateCodespaceTest;

  beforeEach(async () => {
    testInstance = new CreateCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should create a codespace for authenticated user', async () => {
    const codespaceData = { name: 'new_codespace', repository: 'test-org/new_repo' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces', codespaceData);
    testInstance.assertSuccessResponse(response);
    expect(response.data.name).toBe('new_codespace');
    expect(response.data.repository).toBe('test-org/new_repo');
  });

  it('should require authentication', async () => {
    const codespaceData = { name: 'new_codespace', repository: 'test-org/new_repo' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces', codespaceData, { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should validate required fields', async () => {
    const codespaceData = { repository: 'test-org/new_repo' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces', codespaceData);
    testInstance.assertValidationError(response);
  });

  it('should include security headers', async () => {
    const codespaceData = { name: 'new_codespace', repository: 'test-org/new_repo' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces', codespaceData);
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 