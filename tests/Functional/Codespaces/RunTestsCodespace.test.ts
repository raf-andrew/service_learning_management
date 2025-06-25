/**
 * @file RunTestsCodespace.test.ts
 * @description Functional test for running codespace tests (POST /api/codespaces/tests)
 * @tags functional, codespaces, tests, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockTestResponse = {
  test_run_id: 'test_12345',
  status: 'running',
  started_at: '2024-01-01T00:00:00Z',
  tests: {
    total: 10,
    passed: 8,
    failed: 1,
    skipped: 1
  },
  estimated_completion: '2024-01-01T00:05:00Z'
};

class RunTestsCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces/tests', {
      status: 202,
      data: mockTestResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces/tests:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/tests:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { test_suite: ['Test suite is required.'] } },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/tests:busy', {
      status: 409,
      data: { error: 'Another test run is already in progress' },
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
    if (!data || !data.test_suite) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (data.test_suite === 'busy_suite') {
      return this.mockResponses.get(`${method}:${endpoint}:busy`);
    }
    return this.mockResponses.get(`${method}:${endpoint}`) || {
      status: 404,
      data: { error: 'Not found' },
      headers: {}
    };
  }

  assertSuccessResponse(response: any, expectedStatus = 202) {
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

  assertConflict(response: any) {
    expect(response.status).toBe(409);
    expect(response.data.error).toContain('already in progress');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Run Tests', () => {
  let testInstance: RunTestsCodespaceTest;

  beforeEach(async () => {
    testInstance = new RunTestsCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should start test run for authenticated user', async () => {
    const testData = { test_suite: 'unit_tests', environment: 'development' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/tests', testData);
    testInstance.assertSuccessResponse(response);
    expect(response.data.test_run_id).toBe('test_12345');
    expect(response.data.status).toBe('running');
  });

  it('should require authentication', async () => {
    const testData = { test_suite: 'unit_tests' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/tests', testData, { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should validate required fields', async () => {
    const testData = { environment: 'development' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/tests', testData);
    testInstance.assertValidationError(response);
  });

  it('should return conflict when another test is running', async () => {
    const testData = { test_suite: 'busy_suite' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/tests', testData);
    testInstance.assertConflict(response);
  });

  it('should include security headers', async () => {
    const testData = { test_suite: 'unit_tests' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/tests', testData);
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 