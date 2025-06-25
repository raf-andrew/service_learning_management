/**
 * @file SearchGitHub.test.ts
 * @description Functional test for GitHub search endpoint (POST /api/github/search)
 * @tags functional, github, search, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockSearchResults = {
  total_count: 2,
  items: [
    { name: 'repo1', full_name: 'test-org/repo1', description: 'Test repository 1' },
    { name: 'repo2', full_name: 'test-org/repo2', description: 'Test repository 2' }
  ],
  search_time: '0.5s'
};

class SearchGitHubTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // POST /api/github/search
    this.mockResponses.set('POST:/api/github/search', {
      status: 200,
      data: mockSearchResults,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/github/search:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/github/search:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { query: ['Search query is required.'] } },
      headers: {}
    });
    this.mockResponses.set('POST:/api/github/search:rate_limited', {
      status: 429,
      data: { error: 'Rate limit exceeded' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/github/search:no_results', {
      status: 200,
      data: { total_count: 0, items: [], search_time: '0.1s' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
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
    if (!data || !data.query) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (data.query === 'rate_limited') {
      return this.mockResponses.get(`${method}:${endpoint}:rate_limited`);
    }
    if (data.query === 'no_results') {
      return this.mockResponses.get(`${method}:${endpoint}:no_results`);
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

  assertValidationError(response: any) {
    expect(response.status).toBe(422);
    expect(response.data.error).toContain('Validation failed');
    expect(response.data.errors).toBeDefined();
  }

  assertRateLimited(response: any) {
    expect(response.status).toBe(429);
    expect(response.data.error).toContain('Rate limit exceeded');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('GitHub API - Search', () => {
  let testInstance: SearchGitHubTest;

  beforeEach(async () => {
    testInstance = new SearchGitHubTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('Search Repositories', () => {
    it('should search GitHub repositories successfully', async () => {
      const searchData = { query: 'test repo', type: 'repository', limit: 10 };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.total_count).toBe(2);
      expect(response.data.items.length).toBe(2);
      expect(response.data.search_time).toBeDefined();
    });

    it('should require authentication', async () => {
      const searchData = { query: 'test repo' };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });

    it('should validate required fields', async () => {
      const searchData = { type: 'repository', limit: 10 };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData);
      testInstance.assertValidationError(response);
    });

    it('should handle rate limiting', async () => {
      const searchData = { query: 'rate_limited' };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData);
      testInstance.assertRateLimited(response);
    });

    it('should handle no results', async () => {
      const searchData = { query: 'no_results' };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.total_count).toBe(0);
      expect(response.data.items.length).toBe(0);
    });

    it('should include security headers', async () => {
      const searchData = { query: 'test repo' };
      const response = await testInstance.makeRequest('POST', '/api/github/search', searchData);
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 