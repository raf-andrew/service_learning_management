/**
 * @file FeaturesGitHub.test.ts
 * @description Functional test for GitHub features endpoints (GET/POST/PUT/DELETE/POST /api/github/features)
 * @tags functional, github, features, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockFeatures = [
  { name: 'codespaces', enabled: true, description: 'GitHub Codespaces integration' },
  { name: 'actions', enabled: false, description: 'GitHub Actions integration' }
];

const mockFeature = {
  name: 'codespaces',
  enabled: true,
  description: 'GitHub Codespaces integration',
  created_at: '2024-01-01T00:00:00Z'
};

class FeaturesGitHubTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // GET /api/github/features
    this.mockResponses.set('GET:/api/github/features', {
      status: 200,
      data: mockFeatures,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/github/features:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/github/features
    this.mockResponses.set('POST:/api/github/features', {
      status: 201,
      data: mockFeature,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/features:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { name: ['Name is required.'], description: ['Description is required.'] } },
      headers: {}
    });

    // PUT /api/github/features/{name}
    this.mockResponses.set('PUT:/api/github/features/codespaces', {
      status: 200,
      data: { ...mockFeature, description: 'Updated description' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('PUT:/api/github/features/invalid_feature', {
      status: 404,
      data: { error: 'Feature not found' },
      headers: {}
    });

    // DELETE /api/github/features/{name}
    this.mockResponses.set('DELETE:/api/github/features/codespaces', {
      status: 204,
      data: null,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('DELETE:/api/github/features/invalid_feature', {
      status: 404,
      data: { error: 'Feature not found' },
      headers: {}
    });

    // POST /api/github/features/{name}/toggle
    this.mockResponses.set('POST:/api/github/features/codespaces/toggle', {
      status: 200,
      data: { ...mockFeature, enabled: false },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/features/invalid_feature/toggle', {
      status: 404,
      data: { error: 'Feature not found' },
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
    if (method === 'POST' && endpoint.includes('/features') && !endpoint.includes('/toggle') && (!data || !data.name || !data.description)) {
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

describe('GitHub API - Features Management', () => {
  let testInstance: FeaturesGitHubTest;

  beforeEach(async () => {
    testInstance = new FeaturesGitHubTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('List Features', () => {
    it('should list all GitHub features for authenticated user', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/features');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/features', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Create Feature', () => {
    it('should create new GitHub feature', async () => {
      const featureData = { name: 'codespaces', description: 'GitHub Codespaces integration', enabled: true };
      const response = await testInstance.makeRequest('POST', '/api/github/features', featureData);
      testInstance.assertSuccessResponse(response, 201);
      expect(response.data.name).toBe('codespaces');
    });

    it('should validate required fields', async () => {
      const featureData = { enabled: true };
      const response = await testInstance.makeRequest('POST', '/api/github/features', featureData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Update Feature', () => {
    it('should update existing GitHub feature', async () => {
      const updateData = { description: 'Updated description' };
      const response = await testInstance.makeRequest('PUT', '/api/github/features/codespaces', updateData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.description).toBe('Updated description');
    });

    it('should return not found for invalid feature', async () => {
      const updateData = { description: 'New description' };
      const response = await testInstance.makeRequest('PUT', '/api/github/features/invalid_feature', updateData);
      testInstance.assertNotFound(response);
    });
  });

  describe('Delete Feature', () => {
    it('should delete GitHub feature', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/features/codespaces');
      testInstance.assertSuccessResponse(response, 204);
    });

    it('should return not found for invalid feature', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/features/invalid_feature');
      testInstance.assertNotFound(response);
    });
  });

  describe('Toggle Feature', () => {
    it('should toggle GitHub feature status', async () => {
      const response = await testInstance.makeRequest('POST', '/api/github/features/codespaces/toggle');
      testInstance.assertSuccessResponse(response);
      expect(response.data.enabled).toBe(false);
    });

    it('should return not found for invalid feature', async () => {
      const response = await testInstance.makeRequest('POST', '/api/github/features/invalid_feature/toggle');
      testInstance.assertNotFound(response);
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/features');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 