/**
 * @file BaseChaosTest.ts
 * @description Base class for chaos engineering tests to verify system resilience
 * @tags chaos, resilience, error-handling, stress
 */

import { describe, beforeEach, afterEach, vi, expect } from 'vitest';
import { BaseFunctionalTest } from '../Functional/BaseFunctionalTest';

export interface ChaosTestContext extends TestContext {
  stressMetrics: any;
  failurePoints: string[];
}

export abstract class BaseChaosTest extends BaseFunctionalTest {
  protected chaosContext: ChaosTestContext;

  constructor() {
    super();
    this.chaosContext = {
      ...this.context,
      stressMetrics: {},
      failurePoints: []
    };
  }

  /**
   * Simulate high load on the system
   */
  protected async simulateHighLoad(endpoint: string, concurrentRequests: number = 10): Promise<any[]> {
    const promises = Array.from({ length: concurrentRequests }, () =>
      this.makeRequest('GET', endpoint)
    );
    
    const results = await Promise.allSettled(promises);
    return results.map(result => 
      result.status === 'fulfilled' ? result.value : { status: 500, error: result.reason }
    );
  }

  /**
   * Simulate network latency
   */
  protected async simulateNetworkLatency(delayMs: number = 1000): Promise<void> {
    await new Promise(resolve => setTimeout(resolve, delayMs));
  }

  /**
   * Simulate partial network failure
   */
  protected async simulatePartialNetworkFailure(failureRate: number = 0.3): Promise<boolean> {
    return Math.random() < failureRate;
  }

  /**
   * Simulate malformed requests
   */
  protected async simulateMalformedRequests(endpoint: string): Promise<any[]> {
    const malformedRequests = [
      { method: 'POST', data: null },
      { method: 'POST', data: 'invalid json' },
      { method: 'POST', data: { invalid_field: 'test' } },
      { method: 'GET', headers: { 'Content-Type': 'invalid' } },
      { method: 'POST', data: { very_large_field: 'x'.repeat(10000) } }
    ];

    const results = [];
    for (const request of malformedRequests) {
      const response = await this.makeRequest(
        request.method as any,
        endpoint,
        request.data,
        request.headers
      );
      results.push(response);
    }

    return results;
  }

  /**
   * Simulate concurrent modifications
   */
  protected async simulateConcurrentModifications(
    endpoint: string,
    data: any,
    concurrentModifications: number = 5
  ): Promise<any[]> {
    const promises = Array.from({ length: concurrentModifications }, (_, index) =>
      this.makeRequest('PUT', endpoint, { ...data, version: index })
    );
    
    const results = await Promise.allSettled(promises);
    return results.map(result => 
      result.status === 'fulfilled' ? result.value : { status: 500, error: result.reason }
    );
  }

  /**
   * Simulate resource exhaustion
   */
  protected async simulateResourceExhaustion(): Promise<void> {
    // Create many resources to exhaust system resources
    const promises = Array.from({ length: 50 }, (_, index) => {
      const data = {
        name: `exhaustion-test-${index}`,
        repository: `test-org/exhaustion-test-${index}`
      };
      return this.makeRequest('POST', '/codespaces', data);
    });
    
    await Promise.allSettled(promises);
  }

  /**
   * Simulate authentication failures
   */
  protected async simulateAuthenticationFailures(): Promise<any[]> {
    const invalidTokens = [
      '',
      'invalid_token',
      'Bearer invalid',
      'Bearer expired_token',
      'Bearer malformed_token_123'
    ];

    const results = [];
    for (const token of invalidTokens) {
      const response = await this.makeRequest('GET', '/codespaces', null, {
        'Authorization': token
      });
      results.push(response);
    }

    return results;
  }

  /**
   * Simulate rate limiting
   */
  protected async simulateRateLimiting(endpoint: string, requestsPerSecond: number = 100): Promise<any[]> {
    const promises = Array.from({ length: requestsPerSecond }, () =>
      this.makeRequest('GET', endpoint)
    );
    
    const results = await Promise.allSettled(promises);
    return results.map(result => 
      result.status === 'fulfilled' ? result.value : { status: 500, error: result.reason }
    );
  }

  /**
   * Verify system recovery after chaos
   */
  protected async verifySystemRecovery(): Promise<void> {
    // Wait for system to stabilize
    await this.simulateNetworkLatency(2000);
    
    // Test basic functionality
    const response = await this.makeRequest('GET', '/codespaces');
    expect([200, 401, 403]).toContain(response.status);
  }

  /**
   * Record failure points
   */
  protected recordFailurePoint(point: string): void {
    this.chaosContext.failurePoints.push(point);
  }

  /**
   * Get failure analysis
   */
  protected getFailureAnalysis(): any {
    return {
      totalFailures: this.chaosContext.failurePoints.length,
      failurePoints: this.chaosContext.failurePoints,
      stressMetrics: this.chaosContext.stressMetrics
    };
  }
} 