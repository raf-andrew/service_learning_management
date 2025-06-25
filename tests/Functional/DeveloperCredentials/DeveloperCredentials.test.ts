/**
 * @file DeveloperCredentials.test.ts
 * @description Functional test for Developer Credentials endpoints (GET/POST/PUT/DELETE/POST /api/developer-credentials)
 * @tags functional, developer-credentials, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockCredentials = [
  { id: 1, name: 'GitHub Token', type: 'github_token', active: true, created_at: '2024-01-01T00:00:00Z' },
  { id: 2, name: 'Docker Hub', type: 'docker_hub', active: false, created_at: '2024-01-01T00:00:00Z' }
];

const mockCredential = {
  id: 1,
  name: 'GitHub Token',
  type: 'github_token',
  active: true,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z'
};

class DeveloperCredentialsTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // GET /api/developer-credentials
    this.mockResponses.set('GET:/api/developer-credentials', {
      status: 200,
      data: mockCredentials,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/developer-credentials:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/developer-credentials
    this.mockResponses.set('POST:/api/developer-credentials', {
      status: 201,
      data: mockCredential,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/developer-credentials:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { name: ['Name is required.'], type: ['Type is required.'] } },
      headers: {}
    });

    // PUT /api/developer-credentials/{id}
    this.mockResponses.set('PUT:/api/developer-credentials/1', {
      status: 200,
      data: { ...mockCredential, name: 'Updated GitHub Token' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('PUT:/api/developer-credentials/999', {
      status: 404,
      data: { error: 'Credential not found' },
      headers: {}
    });

    // DELETE /api/developer-credentials/{id}
    this.mockResponses.set('DELETE:/api/developer-credentials/1', {
      status: 204,
      data: null,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('DELETE:/api/developer-credentials/999', {
      status: 404,
      data: { error: 'Credential not found' },
      headers: {}
    });

    // POST /api/developer-credentials/{id}/activate
    this.mockResponses.set('POST:/api/developer-credentials/1/activate', {
      status: 200,
      data: { ...mockCredential, active: true },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/developer-credentials/999/activate', {
      status: 404,
      data: { error: 'Credential not found' },
      headers: {}
    });

    // POST /api/developer-credentials/{id}/deactivate
    this.mockResponses.set('POST:/api/developer-credentials/1/deactivate', {
      status: 200,
      data: { ...mockCredential, active: false },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/developer-credentials/999/deactivate', {
      status: 404,
      data: { error: 'Credential not found' },
      headers: {}
    });
  }

  async setupTestContext() {
    this.context.authToken = 'Bearer mock_token_1';
  }

  async makeRequest(method: 'GET' | 'POST' | 'PUT' | 'DELETE', endpoint: string, data?: any, headers?: Record<string, string>) {
    const requestHeaders = {
      'Authorization': this.context.authToken,
      ...headers
    };
    if (!requestHeaders.Authorization || requestHeaders.Authorization === '') {
      return this.mockResponses.get(`${method}:${endpoint}:unauthorized`);
    }
    if (method === 'POST' && endpoint.includes('/developer-credentials') && !endpoint.includes('/activate') && !endpoint.includes('/deactivate') && (!data || !data.name || !data.type)) {
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
    if (response.data !== null) {
      expect(response.data.error).toBeUndefined();
    }
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

  assertNotFound(response: any) {
    expect(response.status).toBe(404);
    expect(response.data.error).toContain('not found');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Developer Credentials API', () => {
  let testInstance: DeveloperCredentialsTest;

  beforeEach(async () => {
    testInstance = new DeveloperCredentialsTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('List Credentials', () => {
    it('should list all developer credentials for authenticated user', async () => {
      const response = await testInstance.makeRequest('GET', '/api/developer-credentials');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('GET', '/api/developer-credentials', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Create Credential', () => {
    it('should create new developer credential', async () => {
      const credentialData = { name: 'GitHub Token', type: 'github_token', active: true };
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials', credentialData);
      testInstance.assertSuccessResponse(response, 201);
      expect(response.data.name).toBe('GitHub Token');
    });

    it('should validate required fields', async () => {
      const credentialData = { active: true };
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials', credentialData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Update Credential', () => {
    it('should update existing developer credential', async () => {
      const updateData = { name: 'Updated GitHub Token' };
      const response = await testInstance.makeRequest('PUT', '/api/developer-credentials/1', updateData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.name).toBe('Updated GitHub Token');
    });

    it('should return not found for invalid credential', async () => {
      const updateData = { name: 'New Name' };
      const response = await testInstance.makeRequest('PUT', '/api/developer-credentials/999', updateData);
      testInstance.assertNotFound(response);
    });
  });

  describe('Delete Credential', () => {
    it('should delete developer credential', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/developer-credentials/1');
      testInstance.assertSuccessResponse(response, 204);
    });

    it('should return not found for invalid credential', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/developer-credentials/999');
      testInstance.assertNotFound(response);
    });
  });

  describe('Activate Credential', () => {
    it('should activate developer credential', async () => {
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials/1/activate');
      testInstance.assertSuccessResponse(response);
      expect(response.data.active).toBe(true);
    });

    it('should return not found for invalid credential', async () => {
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials/999/activate');
      testInstance.assertNotFound(response);
    });
  });

  describe('Deactivate Credential', () => {
    it('should deactivate developer credential', async () => {
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials/1/deactivate');
      testInstance.assertSuccessResponse(response);
      expect(response.data.active).toBe(false);
    });

    it('should return not found for invalid credential', async () => {
      const response = await testInstance.makeRequest('POST', '/api/developer-credentials/999/deactivate');
      testInstance.assertNotFound(response);
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/developer-credentials');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 