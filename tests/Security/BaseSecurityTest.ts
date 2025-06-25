/**
 * @file BaseSecurityTest.ts
 * @description Base class for security testing functionality
 * @tags security, vulnerability, penetration, safety
 */

import { describe, beforeEach, afterEach, vi, expect } from 'vitest';
import { BaseFunctionalTest } from '../Functional/BaseFunctionalTest';

export interface SecurityTestContext extends TestContext {
  securityVulnerabilities: any[];
  penetrationResults: any[];
  securityMetrics: any;
}

export abstract class BaseSecurityTest extends BaseFunctionalTest {
  protected securityContext: SecurityTestContext;

  constructor() {
    super();
    this.securityContext = {
      ...this.context,
      securityVulnerabilities: [],
      penetrationResults: [],
      securityMetrics: {}
    };
  }

  /**
   * Test SQL injection vulnerabilities
   */
  protected async testSQLInjection(endpoint: string, parameter: string): Promise<any[]> {
    const sqlInjectionPayloads = [
      "' OR '1'='1",
      "'; DROP TABLE users; --",
      "' UNION SELECT * FROM users --",
      "admin'--",
      "1' OR '1' = '1' --",
      "'; EXEC xp_cmdshell('dir'); --"
    ];

    const results = [];
    
    for (const payload of sqlInjectionPayloads) {
      const response = await this.makeRequest('GET', `${endpoint}?${parameter}=${encodeURIComponent(payload)}`);
      
      results.push({
        payload,
        status: response.status,
        vulnerable: this.detectSQLInjectionVulnerability(response),
        response: response.data
      });
    }
    
    return results;
  }

  /**
   * Test XSS vulnerabilities
   */
  protected async testXSS(endpoint: string, parameter: string): Promise<any[]> {
    const xssPayloads = [
      "<script>alert('XSS')</script>",
      "<img src=x onerror=alert('XSS')>",
      "javascript:alert('XSS')",
      "<svg onload=alert('XSS')>",
      "'><script>alert('XSS')</script>",
      "<iframe src=javascript:alert('XSS')>"
    ];

    const results = [];
    
    for (const payload of xssPayloads) {
      const response = await this.makeRequest('POST', endpoint, {
        [parameter]: payload
      });
      
      results.push({
        payload,
        status: response.status,
        vulnerable: this.detectXSSVulnerability(response),
        response: response.data
      });
    }
    
    return results;
  }

  /**
   * Test CSRF vulnerabilities
   */
  protected async testCSRF(endpoint: string): Promise<any> {
    // Test without CSRF token
    const responseWithoutToken = await this.makeRequest('POST', endpoint, {
      action: 'delete',
      id: '123'
    });
    
    // Test with invalid CSRF token
    const responseWithInvalidToken = await this.makeRequest('POST', endpoint, {
      action: 'delete',
      id: '123',
      _token: 'invalid_token'
    });
    
    return {
      withoutToken: {
        status: responseWithoutToken.status,
        vulnerable: responseWithoutToken.status === 200
      },
      withInvalidToken: {
        status: responseWithInvalidToken.status,
        vulnerable: responseWithInvalidToken.status === 200
      }
    };
  }

  /**
   * Test authentication bypass
   */
  protected async testAuthenticationBypass(protectedEndpoint: string): Promise<any[]> {
    const bypassAttempts = [
      { headers: {} },
      { headers: { 'Authorization': '' } },
      { headers: { 'Authorization': 'Bearer invalid_token' } },
      { headers: { 'Authorization': 'Bearer expired_token' } },
      { headers: { 'X-API-Key': 'invalid_key' } },
      { headers: { 'X-User-ID': '1' } }
    ];

    const results = [];
    
    for (const attempt of bypassAttempts) {
      const response = await this.makeRequest('GET', protectedEndpoint, null, attempt.headers);
      
      results.push({
        attempt: attempt.headers,
        status: response.status,
        bypassed: response.status === 200,
        response: response.data
      });
    }
    
    return results;
  }

  /**
   * Test authorization bypass
   */
  protected async testAuthorizationBypass(endpoint: string, resourceId: string): Promise<any[]> {
    const bypassAttempts = [
      { user_id: '1', resource_id: resourceId },
      { user_id: '999', resource_id: resourceId },
      { admin: true, resource_id: resourceId },
      { role: 'admin', resource_id: resourceId }
    ];

    const results = [];
    
    for (const attempt of bypassAttempts) {
      const response = await this.makeRequest('GET', `${endpoint}/${attempt.resource_id}`, attempt);
      
      results.push({
        attempt,
        status: response.status,
        bypassed: response.status === 200,
        response: response.data
      });
    }
    
    return results;
  }

  /**
   * Test input validation
   */
  protected async testInputValidation(endpoint: string, field: string): Promise<any[]> {
    const maliciousInputs = [
      { value: '<script>alert("XSS")</script>', type: 'XSS' },
      { value: "' OR '1'='1", type: 'SQL Injection' },
      { value: '../../../etc/passwd', type: 'Path Traversal' },
      { value: 'x'.repeat(10000), type: 'Buffer Overflow' },
      { value: 'test@test.com<script>alert("XSS")</script>', type: 'Email XSS' },
      { value: 'javascript:alert("XSS")', type: 'JavaScript Injection' }
    ];

    const results = [];
    
    for (const input of maliciousInputs) {
      const response = await this.makeRequest('POST', endpoint, {
        [field]: input.value
      });
      
      results.push({
        input: input.value,
        type: input.type,
        status: response.status,
        validated: response.status === 422 || response.status === 400,
        response: response.data
      });
    }
    
    return results;
  }

  /**
   * Test rate limiting
   */
  protected async testRateLimiting(endpoint: string, limit: number = 10): Promise<any> {
    const requests = Array.from({ length: limit + 5 }, () =>
      this.makeRequest('GET', endpoint)
    );
    
    const results = await Promise.allSettled(requests);
    const responses = results.map(result => 
      result.status === 'fulfilled' ? result.value : { status: 500, error: result.reason }
    );
    
    const successCount = responses.filter(r => r.status === 200).length;
    const rateLimitedCount = responses.filter(r => r.status === 429).length;
    
    return {
      totalRequests: responses.length,
      successfulRequests: successCount,
      rateLimitedRequests: rateLimitedCount,
      limitRespected: successCount <= limit
    };
  }

  /**
   * Test sensitive data exposure
   */
  protected async testSensitiveDataExposure(endpoint: string): Promise<any> {
    const response = await this.makeRequest('GET', endpoint);
    
    const sensitivePatterns = [
      /password/i,
      /token/i,
      /secret/i,
      /key/i,
      /credential/i,
      /private/i
    ];
    
    const exposedData = [];
    
    if (response.status === 200) {
      const responseText = JSON.stringify(response.data);
      
      for (const pattern of sensitivePatterns) {
        if (pattern.test(responseText)) {
          exposedData.push(pattern.source);
        }
      }
    }
    
    return {
      status: response.status,
      exposedData,
      hasExposure: exposedData.length > 0
    };
  }

  /**
   * Test secure headers
   */
  protected async testSecureHeaders(endpoint: string): Promise<any> {
    const response = await this.makeRequest('GET', endpoint);
    
    const requiredHeaders = [
      'X-Content-Type-Options',
      'X-Frame-Options',
      'X-XSS-Protection',
      'Strict-Transport-Security',
      'Content-Security-Policy'
    ];
    
    const presentHeaders = [];
    const missingHeaders = [];
    
    for (const header of requiredHeaders) {
      if (response.headers[header.toLowerCase()]) {
        presentHeaders.push(header);
      } else {
        missingHeaders.push(header);
      }
    }
    
    return {
      presentHeaders,
      missingHeaders,
      secure: missingHeaders.length === 0
    };
  }

  /**
   * Detect SQL injection vulnerability
   */
  private detectSQLInjectionVulnerability(response: any): boolean {
    const errorPatterns = [
      /sql syntax/i,
      /mysql error/i,
      /oracle error/i,
      /postgresql error/i,
      /sqlite error/i
    ];
    
    const responseText = JSON.stringify(response.data);
    
    return errorPatterns.some(pattern => pattern.test(responseText));
  }

  /**
   * Detect XSS vulnerability
   */
  private detectXSSVulnerability(response: any): boolean {
    const responseText = JSON.stringify(response.data);
    
    return responseText.includes('<script>') || 
           responseText.includes('javascript:') ||
           responseText.includes('onerror=');
  }

  /**
   * Record security vulnerability
   */
  protected recordVulnerability(type: string, details: any): void {
    this.securityContext.securityVulnerabilities.push({
      type,
      details,
      timestamp: new Date().toISOString()
    });
  }

  /**
   * Get security report
   */
  protected getSecurityReport(): any {
    return {
      totalVulnerabilities: this.securityContext.securityVulnerabilities.length,
      vulnerabilities: this.securityContext.securityVulnerabilities,
      metrics: this.securityContext.securityMetrics
    };
  }
} 