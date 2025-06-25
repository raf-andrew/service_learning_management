/**
 * @file SaveReportCodespace.test.ts
 * @description Functional test for saving codespace reports (POST /api/codespaces/reports/save)
 * @tags functional, codespaces, reports, save, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockSaveResponse = {
  report_id: 'report_12345',
  status: 'saved',
  saved_at: '2024-01-01T00:00:00Z',
  file_path: '/reports/codespace_report_12345.pdf',
  file_size: '2.5MB',
  download_url: 'https://api.example.com/reports/download/report_12345'
};

class SaveReportCodespaceTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    this.mockResponses.set('POST:/api/codespaces/reports/save', {
      status: 200,
      data: mockSaveResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/codespaces/reports/save:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/reports/save:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { report_id: ['Report ID is required.'] } },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/reports/save:not_found', {
      status: 404,
      data: { error: 'Report not found' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/codespaces/reports/save:storage_full', {
      status: 507,
      data: { error: 'Insufficient storage space' },
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
    if (!data || !data.report_id) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (data.report_id === 'not_found') {
      return this.mockResponses.get(`${method}:${endpoint}:not_found`);
    }
    if (data.report_id === 'storage_full') {
      return this.mockResponses.get(`${method}:${endpoint}:storage_full`);
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

  assertNotFound(response: any) {
    expect(response.status).toBe(404);
    expect(response.data.error).toContain('not found');
  }

  assertInsufficientStorage(response: any) {
    expect(response.status).toBe(507);
    expect(response.data.error).toContain('Insufficient storage');
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Codespaces API - Save Report', () => {
  let testInstance: SaveReportCodespaceTest;

  beforeEach(async () => {
    testInstance = new SaveReportCodespaceTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  it('should save report for authenticated user', async () => {
    const saveData = { report_id: 'report_12345', format: 'pdf' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData);
    testInstance.assertSuccessResponse(response);
    expect(response.data.status).toBe('saved');
    expect(response.data.file_path).toBeDefined();
    expect(response.data.download_url).toBeDefined();
  });

  it('should require authentication', async () => {
    const saveData = { report_id: 'report_12345' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData, { 'Authorization': '' });
    testInstance.assertUnauthorized(response);
  });

  it('should validate required fields', async () => {
    const saveData = { format: 'pdf' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData);
    testInstance.assertValidationError(response);
  });

  it('should return not found for invalid report id', async () => {
    const saveData = { report_id: 'not_found' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData);
    testInstance.assertNotFound(response);
  });

  it('should return insufficient storage when storage is full', async () => {
    const saveData = { report_id: 'storage_full' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData);
    testInstance.assertInsufficientStorage(response);
  });

  it('should include security headers', async () => {
    const saveData = { report_id: 'report_12345' };
    const response = await testInstance.makeRequest('POST', '/api/codespaces/reports/save', saveData);
    expect(response.headers['x-content-type-options']).toBe('nosniff');
    expect(response.headers['x-frame-options']).toBe('DENY');
    expect(response.headers['x-xss-protection']).toBe('1; mode=block');
  });
}); 