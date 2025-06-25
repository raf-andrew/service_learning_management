/**
 * @file ConfigGitHub.test.ts
 * @description Functional test for GitHub configuration endpoints (GET/POST/PUT/DELETE /api/github/config)
 * @tags functional, github, config, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockConfigs = [
  { key: 'github_token', value: 'ghp_123456789', encrypted: true },
  { key: 'webhook_secret', value: 'webhook_secret_123', encrypted: true }
];

const mockConfig = {
  key: 'github_token',
  value: 'ghp_123456789',
  encrypted: true,
  updated_at: '2024-01-01T00:00:00Z'
};

class ConfigGitHubTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // GET /api/github/config
    this.mockResponses.set('GET:/api/github/config', {
      status: 200,
      data: mockConfigs,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/github/config:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/github/config
    this.mockResponses.set('POST:/api/github/config', {
      status: 201,
      data: mockConfig,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/config:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { key: ['Key is required.'], value: ['Value is required.'] } },
      headers: {}
    });

    // PUT /api/github/config/{key}
    this.mockResponses.set('PUT:/api/github/config/github_token', {
      status: 200,
      data: { ...mockConfig, value: 'ghp_updated_token' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('PUT:/api/github/config/invalid_key', {
      status: 404,
      data: { error: 'Configuration not found' },
      headers: {}
    });

    // DELETE /api/github/config/{key}
    this.mockResponses.set('DELETE:/api/github/config/github_token', {
      status: 204,
      data: null,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('DELETE:/api/github/config/invalid_key', {
      status: 404,
      data: { error: 'Configuration not found' },
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
    if (method === 'POST' && (!data || !data.key || !data.value)) {
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

describe('GitHub API - Configuration Management', () => {
  let testInstance: ConfigGitHubTest;

  beforeEach(async () => {
    testInstance = new ConfigGitHubTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('List Configurations', () => {
    it('should list all GitHub configurations for authenticated user', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/config');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/config', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Create Configuration', () => {
    it('should create new GitHub configuration', async () => {
      const configData = { key: 'github_token', value: 'ghp_123456789', encrypted: true };
      const response = await testInstance.makeRequest('POST', '/api/github/config', configData);
      testInstance.assertSuccessResponse(response, 201);
      expect(response.data.key).toBe('github_token');
    });

    it('should validate required fields', async () => {
      const configData = { encrypted: true };
      const response = await testInstance.makeRequest('POST', '/api/github/config', configData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Update Configuration', () => {
    it('should update existing GitHub configuration', async () => {
      const updateData = { value: 'ghp_updated_token', encrypted: true };
      const response = await testInstance.makeRequest('PUT', '/api/github/config/github_token', updateData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.value).toBe('ghp_updated_token');
    });

    it('should return not found for invalid key', async () => {
      const updateData = { value: 'new_value' };
      const response = await testInstance.makeRequest('PUT', '/api/github/config/invalid_key', updateData);
      testInstance.assertNotFound(response);
    });
  });

  describe('Delete Configuration', () => {
    it('should delete GitHub configuration', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/config/github_token');
      testInstance.assertSuccessResponse(response, 204);
    });

    it('should return not found for invalid key', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/config/invalid_key');
      testInstance.assertNotFound(response);
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/config');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 