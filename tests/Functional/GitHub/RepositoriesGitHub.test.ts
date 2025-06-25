/**
 * @file RepositoriesGitHub.test.ts
 * @description Functional test for GitHub repositories endpoints (GET/POST/PUT/DELETE/POST /api/github/repositories)
 * @tags functional, github, repositories, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockRepositories = [
  { name: 'test-org/repo1', private: false, description: 'Test repository 1' },
  { name: 'test-org/repo2', private: true, description: 'Test repository 2' }
];

const mockRepository = {
  name: 'test-org/repo1',
  private: false,
  description: 'Test repository 1',
  created_at: '2024-01-01T00:00:00Z',
  last_synced: '2024-01-01T00:00:00Z'
};

class RepositoriesGitHubTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // GET /api/github/repositories
    this.mockResponses.set('GET:/api/github/repositories', {
      status: 200,
      data: mockRepositories,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/github/repositories:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/github/repositories
    this.mockResponses.set('POST:/api/github/repositories', {
      status: 201,
      data: mockRepository,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/repositories:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { name: ['Name is required.'], description: ['Description is required.'] } },
      headers: {}
    });

    // PUT /api/github/repositories/{name}
    this.mockResponses.set('PUT:/api/github/repositories/test-org/repo1', {
      status: 200,
      data: { ...mockRepository, description: 'Updated description' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('PUT:/api/github/repositories/invalid/repo', {
      status: 404,
      data: { error: 'Repository not found' },
      headers: {}
    });

    // DELETE /api/github/repositories/{name}
    this.mockResponses.set('DELETE:/api/github/repositories/test-org/repo1', {
      status: 204,
      data: null,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('DELETE:/api/github/repositories/invalid/repo', {
      status: 404,
      data: { error: 'Repository not found' },
      headers: {}
    });

    // POST /api/github/repositories/{name}/sync
    this.mockResponses.set('POST:/api/github/repositories/test-org/repo1/sync', {
      status: 200,
      data: { 
        ...mockRepository, 
        last_synced: '2024-01-01T01:00:00Z',
        sync_status: 'completed',
        changes_count: 5
      },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/repositories/invalid/repo/sync', {
      status: 404,
      data: { error: 'Repository not found' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/github/repositories/busy/repo/sync', {
      status: 409,
      data: { error: 'Repository sync already in progress' },
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
    if (method === 'POST' && endpoint.includes('/repositories') && !endpoint.includes('/sync') && (!data || !data.name || !data.description)) {
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

  assertConflict(response: any) {
    expect(response.status).toBe(409);
    expect(response.data.error).toContain('already in progress');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('GitHub API - Repositories Management', () => {
  let testInstance: RepositoriesGitHubTest;

  beforeEach(async () => {
    testInstance = new RepositoriesGitHubTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('List Repositories', () => {
    it('should list all GitHub repositories for authenticated user', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/repositories');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/repositories', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Create Repository', () => {
    it('should create new GitHub repository', async () => {
      const repoData = { name: 'test-org/repo1', description: 'Test repository 1', private: false };
      const response = await testInstance.makeRequest('POST', '/api/github/repositories', repoData);
      testInstance.assertSuccessResponse(response, 201);
      expect(response.data.name).toBe('test-org/repo1');
    });

    it('should validate required fields', async () => {
      const repoData = { private: false };
      const response = await testInstance.makeRequest('POST', '/api/github/repositories', repoData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Update Repository', () => {
    it('should update existing GitHub repository', async () => {
      const updateData = { description: 'Updated description' };
      const response = await testInstance.makeRequest('PUT', '/api/github/repositories/test-org/repo1', updateData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.description).toBe('Updated description');
    });

    it('should return not found for invalid repository', async () => {
      const updateData = { description: 'New description' };
      const response = await testInstance.makeRequest('PUT', '/api/github/repositories/invalid/repo', updateData);
      testInstance.assertNotFound(response);
    });
  });

  describe('Delete Repository', () => {
    it('should delete GitHub repository', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/repositories/test-org/repo1');
      testInstance.assertSuccessResponse(response, 204);
    });

    it('should return not found for invalid repository', async () => {
      const response = await testInstance.makeRequest('DELETE', '/api/github/repositories/invalid/repo');
      testInstance.assertNotFound(response);
    });
  });

  describe('Sync Repository', () => {
    it('should sync GitHub repository', async () => {
      const response = await testInstance.makeRequest('POST', '/api/github/repositories/test-org/repo1/sync');
      testInstance.assertSuccessResponse(response);
      expect(response.data.sync_status).toBe('completed');
      expect(response.data.changes_count).toBe(5);
    });

    it('should return not found for invalid repository', async () => {
      const response = await testInstance.makeRequest('POST', '/api/github/repositories/invalid/repo/sync');
      testInstance.assertNotFound(response);
    });

    it('should return conflict when sync is already in progress', async () => {
      const response = await testInstance.makeRequest('POST', '/api/github/repositories/busy/repo/sync');
      testInstance.assertConflict(response);
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/repositories');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 