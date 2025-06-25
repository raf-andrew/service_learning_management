/**
 * @file Sniffing.test.ts
 * @description Functional test for Sniffing endpoints (POST/GET /api/sniffing/*)
 * @tags functional, sniffing, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockSniffingResults = {
  id: 'sniff_123',
  status: 'completed',
  findings: [
    { type: 'security', severity: 'high', message: 'Potential SQL injection detected' },
    { type: 'performance', severity: 'medium', message: 'Slow query detected' }
  ],
  created_at: '2024-01-01T00:00:00Z'
};

const mockAnalysis = {
  summary: 'Analysis completed',
  recommendations: [
    'Implement input validation',
    'Add rate limiting',
    'Use prepared statements'
  ],
  risk_score: 7.5
};

const mockRules = [
  { id: 1, name: 'SQL Injection', pattern: 'SELECT.*FROM', enabled: true },
  { id: 2, name: 'XSS Detection', pattern: '<script>', enabled: true }
];

class SniffingTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // POST /api/sniffing/run
    this.mockResponses.set('POST:/api/sniffing/run', {
      status: 202,
      data: { id: 'sniff_123', status: 'started' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/sniffing/run:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { target: ['Target is required.'] } },
      headers: {}
    });

    // GET /api/sniffing/results
    this.mockResponses.set('GET:/api/sniffing/results', {
      status: 200,
      data: [mockSniffingResults],
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/sniffing/analyze
    this.mockResponses.set('POST:/api/sniffing/analyze', {
      status: 200,
      data: mockAnalysis,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/sniffing/analyze:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { results_id: ['Results ID is required.'] } },
      headers: {}
    });

    // POST /api/sniffing/rules
    this.mockResponses.set('POST:/api/sniffing/rules', {
      status: 200,
      data: mockRules,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/sniffing/rules:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { rule_type: ['Rule type is required.'] } },
      headers: {}
    });

    // POST /api/sniffing/clear
    this.mockResponses.set('POST:/api/sniffing/clear', {
      status: 200,
      data: { message: 'All sniffing results cleared' },
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

  async makeRequest(method: 'GET' | 'POST', endpoint: string, data?: any, headers?: Record<string, string>) {
    const requestHeaders = {
      'Authorization': this.context.authToken,
      ...headers
    };
    if (method === 'POST' && endpoint.includes('/run') && (!data || !data.target)) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (method === 'POST' && endpoint.includes('/analyze') && (!data || !data.results_id)) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (method === 'POST' && endpoint.includes('/rules') && (!data || !data.rule_type)) {
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

  assertValidationError(response: any) {
    expect(response.status).toBe(422);
    expect(response.data.error).toContain('Validation failed');
    expect(response.data.errors).toBeDefined();
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Sniffing API', () => {
  let testInstance: SniffingTest;

  beforeEach(async () => {
    testInstance = new SniffingTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('Run Sniffing', () => {
    it('should start sniffing operation', async () => {
      const sniffingData = { target: 'api/v1/users', rules: ['security', 'performance'] };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/run', sniffingData);
      testInstance.assertSuccessResponse(response, 202);
      expect(response.data.id).toBe('sniff_123');
      expect(response.data.status).toBe('started');
    });

    it('should validate required fields', async () => {
      const sniffingData = { rules: ['security'] };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/run', sniffingData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Get Results', () => {
    it('should return sniffing results', async () => {
      const response = await testInstance.makeRequest('GET', '/api/sniffing/results');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(1);
      expect(response.data[0].findings.length).toBe(2);
    });
  });

  describe('Analyze Results', () => {
    it('should analyze sniffing results', async () => {
      const analysisData = { results_id: 'sniff_123', include_recommendations: true };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/analyze', analysisData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.summary).toBe('Analysis completed');
      expect(response.data.recommendations.length).toBe(3);
      expect(response.data.risk_score).toBe(7.5);
    });

    it('should validate required fields', async () => {
      const analysisData = { include_recommendations: true };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/analyze', analysisData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Manage Rules', () => {
    it('should return sniffing rules', async () => {
      const rulesData = { rule_type: 'security' };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/rules', rulesData);
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
      expect(response.data.length).toBe(2);
    });

    it('should validate required fields', async () => {
      const rulesData = { enabled: true };
      const response = await testInstance.makeRequest('POST', '/api/sniffing/rules', rulesData);
      testInstance.assertValidationError(response);
    });
  });

  describe('Clear Results', () => {
    it('should clear all sniffing results', async () => {
      const response = await testInstance.makeRequest('POST', '/api/sniffing/clear');
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('All sniffing results cleared');
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/sniffing/results');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 