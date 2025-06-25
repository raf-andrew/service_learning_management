/**
 * @file Authentication.test.ts
 * @description Functional test for Authentication endpoints (POST/GET /api/auth/*)
 * @tags functional, auth, authentication, api, laravel, mock
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  created_at: '2024-01-01T00:00:00Z'
};

const mockAuthResponse = {
  user: mockUser,
  token: 'jwt_token_123',
  expires_at: '2024-01-01T01:00:00Z'
};

const mockBackupCodes = [
  '12345678',
  '87654321',
  '11223344',
  '44332211'
];

class AuthenticationTest {
  protected context: any;
  protected mockResponses: Map<string, any>;

  constructor() {
    this.context = { authToken: '' };
    this.mockResponses = new Map();
    this.setupMockResponses();
  }

  private setupMockResponses(): void {
    // POST /api/auth/login
    this.mockResponses.set('POST:/api/auth/login', {
      status: 200,
      data: mockAuthResponse,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/auth/login:invalid_credentials', {
      status: 401,
      data: { error: 'Invalid credentials' },
      headers: {}
    });
    this.mockResponses.set('POST:/api/auth/login:validation_error', {
      status: 422,
      data: { error: 'Validation failed', errors: { email: ['Email is required.'], password: ['Password is required.'] } },
      headers: {}
    });

    // POST /api/auth/logout
    this.mockResponses.set('POST:/api/auth/logout', {
      status: 200,
      data: { message: 'Successfully logged out' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/auth/logout:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/auth/refresh
    this.mockResponses.set('POST:/api/auth/refresh', {
      status: 200,
      data: { token: 'new_jwt_token_456', expires_at: '2024-01-01T02:00:00Z' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // GET /api/auth/me
    this.mockResponses.set('GET:/api/auth/me', {
      status: 200,
      data: mockUser,
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('GET:/api/auth/me:unauthorized', {
      status: 401,
      data: { error: 'Unauthorized' },
      headers: {}
    });

    // POST /api/auth/2fa/enable
    this.mockResponses.set('POST:/api/auth/2fa/enable', {
      status: 200,
      data: { qr_code: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', secret: 'JBSWY3DPEHPK3PXP' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/2fa/disable
    this.mockResponses.set('POST:/api/auth/2fa/disable', {
      status: 200,
      data: { message: '2FA disabled successfully' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/2fa/verify
    this.mockResponses.set('POST:/api/auth/2fa/verify', {
      status: 200,
      data: { verified: true },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });
    this.mockResponses.set('POST:/api/auth/2fa/verify:invalid_code', {
      status: 400,
      data: { error: 'Invalid 2FA code' },
      headers: {}
    });

    // GET /api/auth/2fa/backup-codes
    this.mockResponses.set('GET:/api/auth/2fa/backup-codes', {
      status: 200,
      data: { codes: mockBackupCodes },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/2fa/regenerate-backup-codes
    this.mockResponses.set('POST:/api/auth/2fa/regenerate-backup-codes', {
      status: 200,
      data: { codes: ['11111111', '22222222', '33333333', '44444444'] },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/password/forgot
    this.mockResponses.set('POST:/api/auth/password/forgot', {
      status: 200,
      data: { message: 'Password reset email sent' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/password/reset
    this.mockResponses.set('POST:/api/auth/password/reset', {
      status: 200,
      data: { message: 'Password reset successfully' },
      headers: {
        'x-content-type-options': 'nosniff',
        'x-frame-options': 'DENY',
        'x-xss-protection': '1; mode=block'
      }
    });

    // POST /api/auth/password/change
    this.mockResponses.set('POST:/api/auth/password/change', {
      status: 200,
      data: { message: 'Password changed successfully' },
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
    
    // Handle authentication requirements
    if (endpoint.includes('/logout') || endpoint.includes('/me') || endpoint.includes('/2fa/') || endpoint.includes('/password/change')) {
      if (!requestHeaders.Authorization || requestHeaders.Authorization === '') {
        return this.mockResponses.get(`${method}:${endpoint}:unauthorized`);
      }
    }
    
    // Handle validation errors
    if (method === 'POST' && endpoint.includes('/login') && (!data || !data.email || !data.password)) {
      return this.mockResponses.get(`${method}:${endpoint}:validation_error`);
    }
    if (method === 'POST' && endpoint.includes('/login') && data?.email === 'invalid@example.com') {
      return this.mockResponses.get(`${method}:${endpoint}:invalid_credentials`);
    }
    if (method === 'POST' && endpoint.includes('/2fa/verify') && data?.code === '000000') {
      return this.mockResponses.get(`${method}:${endpoint}:invalid_code`);
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

  assertBadRequest(response: any) {
    expect(response.status).toBe(400);
    expect(response.data.error).toBeDefined();
  }

  async cleanup() {
    this.context = { authToken: '' };
  }
}

describe('Authentication API', () => {
  let testInstance: AuthenticationTest;

  beforeEach(async () => {
    testInstance = new AuthenticationTest();
    await testInstance.setupTestContext();
  });

  afterEach(async () => {
    await testInstance.cleanup();
  });

  describe('Login', () => {
    it('should login user successfully', async () => {
      const loginData = { email: 'test@example.com', password: 'password123' };
      const response = await testInstance.makeRequest('POST', '/api/auth/login', loginData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.user.email).toBe('test@example.com');
      expect(response.data.token).toBe('jwt_token_123');
    });

    it('should validate required fields', async () => {
      const loginData = { email: 'test@example.com' };
      const response = await testInstance.makeRequest('POST', '/api/auth/login', loginData);
      testInstance.assertValidationError(response);
    });

    it('should reject invalid credentials', async () => {
      const loginData = { email: 'invalid@example.com', password: 'wrongpassword' };
      const response = await testInstance.makeRequest('POST', '/api/auth/login', loginData);
      expect(response.status).toBe(401);
      expect(response.data.error).toBe('Invalid credentials');
    });
  });

  describe('Logout', () => {
    it('should logout user successfully', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/logout');
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('Successfully logged out');
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/logout', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Refresh Token', () => {
    it('should refresh token successfully', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/refresh');
      testInstance.assertSuccessResponse(response);
      expect(response.data.token).toBe('new_jwt_token_456');
    });
  });

  describe('Get Current User', () => {
    it('should return current user', async () => {
      const response = await testInstance.makeRequest('GET', '/api/auth/me');
      testInstance.assertSuccessResponse(response);
      expect(response.data.email).toBe('test@example.com');
    });

    it('should require authentication', async () => {
      const response = await testInstance.makeRequest('GET', '/api/auth/me', null, { 'Authorization': '' });
      testInstance.assertUnauthorized(response);
    });
  });

  describe('Two-Factor Authentication', () => {
    it('should enable 2FA', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/2fa/enable');
      testInstance.assertSuccessResponse(response);
      expect(response.data.qr_code).toBeDefined();
      expect(response.data.secret).toBe('JBSWY3DPEHPK3PXP');
    });

    it('should disable 2FA', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/2fa/disable');
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('2FA disabled successfully');
    });

    it('should verify 2FA code', async () => {
      const verifyData = { code: '123456' };
      const response = await testInstance.makeRequest('POST', '/api/auth/2fa/verify', verifyData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.verified).toBe(true);
    });

    it('should reject invalid 2FA code', async () => {
      const verifyData = { code: '000000' };
      const response = await testInstance.makeRequest('POST', '/api/auth/2fa/verify', verifyData);
      testInstance.assertBadRequest(response);
    });

    it('should get backup codes', async () => {
      const response = await testInstance.makeRequest('GET', '/api/auth/2fa/backup-codes');
      testInstance.assertSuccessResponse(response);
      expect(response.data.codes.length).toBe(4);
    });

    it('should regenerate backup codes', async () => {
      const response = await testInstance.makeRequest('POST', '/api/auth/2fa/regenerate-backup-codes');
      testInstance.assertSuccessResponse(response);
      expect(response.data.codes.length).toBe(4);
    });
  });

  describe('Password Management', () => {
    it('should send forgot password email', async () => {
      const forgotData = { email: 'test@example.com' };
      const response = await testInstance.makeRequest('POST', '/api/auth/password/forgot', forgotData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('Password reset email sent');
    });

    it('should reset password', async () => {
      const resetData = { token: 'reset_token_123', password: 'newpassword123', password_confirmation: 'newpassword123' };
      const response = await testInstance.makeRequest('POST', '/api/auth/password/reset', resetData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('Password reset successfully');
    });

    it('should change password', async () => {
      const changeData = { current_password: 'oldpassword', password: 'newpassword123', password_confirmation: 'newpassword123' };
      const response = await testInstance.makeRequest('POST', '/api/auth/password/change', changeData);
      testInstance.assertSuccessResponse(response);
      expect(response.data.message).toBe('Password changed successfully');
    });
  });

  describe('Security Headers', () => {
    it('should include security headers in all responses', async () => {
      const response = await testInstance.makeRequest('GET', '/api/auth/me');
      expect(response.headers['x-content-type-options']).toBe('nosniff');
      expect(response.headers['x-frame-options']).toBe('DENY');
      expect(response.headers['x-xss-protection']).toBe('1; mode=block');
    });
  });
}); 