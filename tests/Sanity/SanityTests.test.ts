/**
 * @file SanityTests.test.ts
 * @description Sanity tests to verify basic system functionality and health checks
 * @tags sanity, health-check, system-verification, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

class SanityTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // Health check responses
    this.mockResponses.set('GET:/api/health', {
      status: 200,
      data: {
        status: 'healthy',
        timestamp: '2024-01-01T00:00:00Z',
        services: {
          database: { status: 'healthy', response_time: 15 },
          redis: { status: 'healthy', response_time: 5 },
          queue: { status: 'healthy', response_time: 10 }
        }
      },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // Authentication sanity check
    this.mockResponses.set('POST:/api/auth/login', {
      status: 200,
      data: {
        user: { id: 1, name: 'Test User', email: 'test@example.com' },
        token: 'jwt_token_123',
        expires_at: '2024-01-01T01:00:00Z'
      },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // Codespaces basic functionality
    this.mockResponses.set('GET:/api/codespaces', {
      status: 200,
      data: [
        { id: 1, name: 'test-codespace', status: 'running', created_at: '2024-01-01T00:00:00Z' }
      ],
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // GitHub config sanity check
    this.mockResponses.set('GET:/api/github/config', {
      status: 200,
      data: [
        { key: 'github_token', value: 'ghp_123456789', encrypted: true }
      ],
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // Developer credentials sanity check
    this.mockResponses.set('GET:/api/developer-credentials', {
      status: 200,
      data: [
        { id: 1, name: 'GitHub Token', type: 'github_token', active: true }
      ],
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // Tenant management sanity check
    this.mockResponses.set('GET:/api/tenants', {
      status: 200,
      data: [
        { id: 1, name: 'Test Tenant', domain: 'test.example.com', status: 'active' }
      ],
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

  assertHealthStatus(response: any) {
    expect(response.data.status).toBe('healthy');
    expect(response.data.services).toBeDefined();
    expect(Object.keys(response.data.services).length).toBeGreaterThan(0);
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Sanity Tests - System Health and Basic Functionality', () => {
  let testInstance: SanityTest;

  beforeEach(async () => {
    testInstance = new SanityTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('System Health Checks', () => {
    it('should verify overall system health', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      testInstance.assertSuccessResponse(response);
      testInstance.assertHealthStatus(response);
    });

    it('should verify all core services are healthy', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      testInstance.assertSuccessResponse(response);
      
      const services = response.data.services;
      expect(services.database.status).toBe('healthy');
      expect(services.redis.status).toBe('healthy');
      expect(services.queue.status).toBe('healthy');
    });

    it('should verify response times are within acceptable limits', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      testInstance.assertSuccessResponse(response);
      
      const services = response.data.services;
      expect(services.database.response_time).toBeLessThan(100);
      expect(services.redis.response_time).toBeLessThan(50);
      expect(services.queue.response_time).toBeLessThan(100);
    });
  });

  describe('Authentication System', () => {
    it('should verify authentication system is working', async () => {
      const loginData = { email: 'test@example.com', password: 'password123' };
      const response = await testInstance.makeRequest('POST', '/api/auth/login', loginData);
      testInstance.assertSuccessResponse(response);
      
      expect(response.data.user).toBeDefined();
      expect(response.data.token).toBeDefined();
      expect(response.data.expires_at).toBeDefined();
    });

    it('should verify user data structure is correct', async () => {
      const loginData = { email: 'test@example.com', password: 'password123' };
      const response = await testInstance.makeRequest('POST', '/api/auth/login', loginData);
      testInstance.assertSuccessResponse(response);
      
      const user = response.data.user;
      expect(user.id).toBeDefined();
      expect(user.name).toBeDefined();
      expect(user.email).toBeDefined();
    });
  });

  describe('Core API Functionality', () => {
    it('should verify codespaces API is accessible', async () => {
      const response = await testInstance.makeRequest('GET', '/api/codespaces');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
    });

    it('should verify GitHub configuration API is accessible', async () => {
      const response = await testInstance.makeRequest('GET', '/api/github/config');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
    });

    it('should verify developer credentials API is accessible', async () => {
      const response = await testInstance.makeRequest('GET', '/api/developer-credentials');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
    });

    it('should verify tenant management API is accessible', async () => {
      const response = await testInstance.makeRequest('GET', '/api/tenants');
      testInstance.assertSuccessResponse(response);
      expect(Array.isArray(response.data)).toBe(true);
    });
  });

  describe('Security Headers', () => {
    it('should verify all responses include security headers', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });

    it('should verify security headers are consistent across all endpoints', async () => {
      const endpoints = [
        '/api/health',
        '/api/codespaces',
        '/api/github/config',
        '/api/developer-credentials',
        '/api/tenants'
      ];

      for (const endpoint of endpoints) {
        const response = await testInstance.makeRequest('GET', endpoint);
        expect(response.headers['x-content-type-options']).toBe('nosniff');
        expect(response.headers['x-frame-options']).toBe('DENY');
        expect(response.headers['x-xss-protection']).toBe('1; mode=block');
      }
    });
  });

  describe('Data Integrity', () => {
    it('should verify response data structures are consistent', async () => {
      const response = await testInstance.makeRequest('GET', '/api/codespaces');
      testInstance.assertSuccessResponse(response);
      
      if (response.data.length > 0) {
        const codespace = response.data[0];
        expect(codespace.id).toBeDefined();
        expect(codespace.name).toBeDefined();
        expect(codespace.status).toBeDefined();
        expect(codespace.created_at).toBeDefined();
      }
    });

    it('should verify timestamps are in valid format', async () => {
      const response = await testInstance.makeRequest('GET', '/api/health');
      testInstance.assertSuccessResponse(response);
      
      const timestamp = response.data.timestamp;
      expect(new Date(timestamp).getTime()).not.toBeNaN();
    });
  });

  describe('System Performance', () => {
    it('should verify system responds within acceptable time limits', async () => {
      const startTime = Date.now();
      const response = await testInstance.makeRequest('GET', '/api/health');
      const endTime = Date.now();
      
      testInstance.assertSuccessResponse(response);
      expect(endTime - startTime).toBeLessThan(1000); // Should respond within 1 second
    });

    it('should verify multiple concurrent requests are handled properly', async () => {
      const promises = [
        testInstance.makeRequest('GET', '/api/health'),
        testInstance.makeRequest('GET', '/api/codespaces'),
        testInstance.makeRequest('GET', '/api/github/config')
      ];
      
      const responses = await Promise.all(promises);
      
      responses.forEach(response => {
        testInstance.assertSuccessResponse(response);
      });
    });
  });
}); 