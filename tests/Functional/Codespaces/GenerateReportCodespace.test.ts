/**
 * @file GenerateReportCodespace.test.ts
 * @description Functional test for generating codespace reports (POST /api/codespaces/reports/generate)
 * @tags functional, codespaces, reports, generate, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockReportResponse = {
  report_id: 'report_12345',
  status: 'generating',
  started_at: '2024-01-01T00:00:00Z',
  report_type: 'comprehensive',
  estimated_completion: '2024-01-01T00:10:00Z',
  includes: ['usage_stats', 'performance_metrics', 'security_analysis']
};

class GenerateReportCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces/reports/generate', {
      status: 202,
      data: mockReportResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces/reports/generate:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/reports/generate:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { report_type: ['Report type is required.'] } },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/reports/generate:quota_exceeded', {
      status: 429,
      data: { error: 'Report generation quota exceeded' },
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
    if (!data || !data.report_type) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (data.report_type === 'quota_exceeded') {
      return this.mockResponses.get(`${method}:${endpoint}:quota_exceeded`);
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

  assertTooManyRequests(response: any) {
    expect(response.status).toBe(429);
    expect(response.data.error).toContain('quota exceeded');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Generate Report', () => {
  let testInstance: GenerateReportCodespaceTest;

  beforeEach(async () => {
    testInstance = new GenerateReportCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should start report generation for authenticated user', async () => {
    const reportData = { report_type: 'comprehensive', date_range: 'last_30_days' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/generate', reportData);
    testInstance.assertSuccessResponse(response);
    expect(response.data.report_id).toBe('report_12345');
    expect(response.data.status).toBe('generating');
  });

  it('should require authentication', async () => {
    const reportData = { report_type: 'comprehensive' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/generate', reportData, { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should validate required fields', async () => {
    const reportData = { date_range: 'last_30_days' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/generate', reportData);
    testInstance.assertValidationError(response);
  });

  it('should return too many requests when quota exceeded', async () => {
    const reportData = { report_type: 'quota_exceeded' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/generate', reportData);
    testInstance.assertTooManyRequests(response);
  });

  it('should include security headers', async () => {
    const reportData = { report_type: 'comprehensive' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/generate', reportData);
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 