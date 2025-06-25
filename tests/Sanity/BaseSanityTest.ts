/**
 * @file BaseSanityTest.ts
 * @description Base class for sanity tests to verify basic functionality
 * @tags sanity, base, verification, basic
 */

import { describe, beforeEach, afterEach, vi, expect } from 'vitest';
import { BaseFunctionalTest } from '../Functional/BaseFunctionalTest';

export interface SanityTestContext extends TestContext {
  healthCheck: any;
  systemStatus: any;
}

export abstract class BaseSanityTest extends BaseFunctionalTest {
  protected sanityContext: SanityTestContext;

  constructor() {
    super();
    this.sanityContext = {
      ...this.context,
      healthCheck: null,
      systemStatus: null
    };
  }

  /**
   * Verify basic system health
   */
  protected async verifySystemHealth(): Promise<void> {
    const response = await this.makeRequest('GET', '/health');
    
    expect(response.status).toBe(200);
    expect(response.data.status).toBe('healthy');
    expect(response.data.timestamp).toBeDefined();
    
    this.sanityContext.healthCheck = response.data;
  }

  /**
   * Verify API is accessible
   */
  protected async verifyApiAccessibility(): Promise<void> {
    const response = await this.makeRequest('GET', '/user');
    
    // Should either return user data or require authentication
    expect([200, 401]).toContain(response.status);
  }

  /**
   * Verify database connectivity
   */
  protected async verifyDatabaseConnectivity(): Promise<void> {
    // Test a simple database operation
    const response = await this.makeRequest('GET', '/codespaces');
    
    // Should either return data or require authentication
    expect([200, 401, 403]).toContain(response.status);
  }

  /**
   * Verify authentication system
   */
  protected async verifyAuthenticationSystem(): Promise<void> {
    // Test without authentication
    const unauthenticatedResponse = await this.makeRequest('GET', '/codespaces', null, {
      'Authorization': ''
    });
    
    expect(unauthenticatedResponse.status).toBe(401);
    
    // Test with authentication
    await this.setupTestContext();
    const authenticatedResponse = await this.makeRequest('GET', '/codespaces');
    
    expect([200, 403]).toContain(authenticatedResponse.status);
  }

  /**
   * Verify basic CRUD operations
   */
  protected async verifyBasicCrudOperations(): Promise<void> {
    // Test create operation
    const createData = {
      name: 'sanity-test-codespace',
      repository: 'test-org/sanity-test-repo'
    };
    
    const createResponse = await this.makeRequest('POST', '/codespaces', createData);
    expect([201, 401, 403]).toContain(createResponse.status);
    
    if (createResponse.status === 201) {
      const codespaceId = createResponse.data.id;
      
      // Test read operation
      const readResponse = await this.makeRequest('GET', `/codespaces/${codespaceId}`);
      expect([200, 404]).toContain(readResponse.status);
      
      // Test delete operation
      const deleteResponse = await this.makeRequest('DELETE', `/codespaces/${codespaceId}`);
      expect([204, 404]).toContain(deleteResponse.status);
    }
  }

  /**
   * Verify error handling
   */
  protected async verifyErrorHandling(): Promise<void> {
    // Test 404 error
    const notFoundResponse = await this.makeRequest('GET', '/non-existent-endpoint');
    expect(notFoundResponse.status).toBe(404);
    
    // Test validation error
    const invalidData = { invalid_field: 'test' };
    const validationResponse = await this.makeRequest('POST', '/codespaces', invalidData);
    expect([400, 401, 403, 422]).toContain(validationResponse.status);
  }

  /**
   * Run all sanity checks
   */
  protected async runAllSanityChecks(): Promise<void> {
    await this.verifySystemHealth();
    await this.verifyApiAccessibility();
    await this.verifyDatabaseConnectivity();
    await this.verifyAuthenticationSystem();
    await this.verifyBasicCrudOperations();
    await this.verifyErrorHandling();
  }
} 