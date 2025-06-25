/**
 * @file BaseMCPTest.ts
 * @description Base class for Model Context Protocol (MCP) tests
 * @tags mcp, protocol, model-context, communication
 */

import { describe, beforeEach, afterEach, vi, expect } from 'vitest';
import { BaseFunctionalTest } from '../Functional/BaseFunctionalTest';

export interface MCPTestContext extends TestContext {
  mcpConnection: any;
  protocolVersion: string;
  capabilities: any[];
}

export abstract class BaseMCPTest extends BaseFunctionalTest {
  protected mcpContext: MCPTestContext;

  constructor() {
    super();
    this.mcpContext = {
      ...this.context,
      mcpConnection: null,
      protocolVersion: '1.0',
      capabilities: []
    };
  }

  /**
   * Test MCP connection establishment
   */
  protected async testMCPConnection(serverUrl: string): Promise<boolean> {
    try {
      const response = await this.makeRequest('POST', '/mcp/connect', {
        server_url: serverUrl,
        protocol_version: this.mcpContext.protocolVersion
      });
      
      expect(response.status).toBe(200);
      expect(response.data.connected).toBe(true);
      expect(response.data.protocol_version).toBe(this.mcpContext.protocolVersion);
      
      this.mcpContext.mcpConnection = response.data.connection_id;
      return true;
    } catch (error) {
      return false;
    }
  }

  /**
   * Test MCP capability negotiation
   */
  protected async testMCPCapabilities(): Promise<any[]> {
    const response = await this.makeRequest('GET', '/mcp/capabilities');
    
    expect(response.status).toBe(200);
    expect(response.data.capabilities).toBeDefined();
    expect(Array.isArray(response.data.capabilities)).toBe(true);
    
    this.mcpContext.capabilities = response.data.capabilities;
    return response.data.capabilities;
  }

  /**
   * Test MCP message exchange
   */
  protected async testMCPMessageExchange(message: any): Promise<any> {
    const response = await this.makeRequest('POST', '/mcp/message', {
      connection_id: this.mcpContext.mcpConnection,
      message: message
    });
    
    expect(response.status).toBe(200);
    expect(response.data.response).toBeDefined();
    
    return response.data.response;
  }

  /**
   * Test MCP resource management
   */
  protected async testMCPResourceManagement(resourceType: string, resourceData: any): Promise<any> {
    const response = await this.makeRequest('POST', '/mcp/resources', {
      connection_id: this.mcpContext.mcpConnection,
      type: resourceType,
      data: resourceData
    });
    
    expect(response.status).toBe(200);
    expect(response.data.resource_id).toBeDefined();
    
    return response.data;
  }

  /**
   * Test MCP tool execution
   */
  protected async testMCPToolExecution(toolName: string, parameters: any): Promise<any> {
    const response = await this.makeRequest('POST', '/mcp/tools/execute', {
      connection_id: this.mcpContext.mcpConnection,
      tool: toolName,
      parameters: parameters
    });
    
    expect(response.status).toBe(200);
    expect(response.data.result).toBeDefined();
    
    return response.data.result;
  }

  /**
   * Test MCP error handling
   */
  protected async testMCPErrorHandling(invalidRequest: any): Promise<any> {
    const response = await this.makeRequest('POST', '/mcp/message', invalidRequest);
    
    expect(response.status).toBeGreaterThanOrEqual(400);
    expect(response.data.error).toBeDefined();
    expect(response.data.error_code).toBeDefined();
    
    return response.data;
  }

  /**
   * Test MCP connection stability
   */
  protected async testMCPConnectionStability(duration: number = 30000): Promise<boolean> {
    const startTime = Date.now();
    const testMessages = [
      { type: 'ping', data: 'test1' },
      { type: 'ping', data: 'test2' },
      { type: 'ping', data: 'test3' }
    ];
    
    let successCount = 0;
    
    while (Date.now() - startTime < duration) {
      for (const message of testMessages) {
        try {
          const response = await this.testMCPMessageExchange(message);
          if (response.status === 'success') {
            successCount++;
          }
        } catch (error) {
          // Connection failed
          return false;
        }
      }
      
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
    
    const successRate = successCount / (testMessages.length * Math.floor(duration / 1000));
    return successRate > 0.8; // 80% success rate
  }

  /**
   * Test MCP protocol compliance
   */
  protected async testMCPProtocolCompliance(): Promise<any> {
    const complianceTests = [
      { test: 'message_format', expected: true },
      { test: 'error_handling', expected: true },
      { test: 'capability_negotiation', expected: true },
      { test: 'resource_management', expected: true }
    ];
    
    const results = [];
    
    for (const test of complianceTests) {
      const response = await this.makeRequest('POST', '/mcp/compliance', {
        test: test.test
      });
      
      results.push({
        test: test.test,
        passed: response.status === 200 && response.data.compliant === test.expected
      });
    }
    
    const passedTests = results.filter(r => r.passed).length;
    expect(passedTests).toBe(complianceTests.length);
    
    return results;
  }

  /**
   * Test MCP security features
   */
  protected async testMCPSecurity(): Promise<any> {
    const securityTests = [
      { test: 'authentication', expected: true },
      { test: 'authorization', expected: true },
      { test: 'encryption', expected: true },
      { test: 'input_validation', expected: true }
    ];
    
    const results = [];
    
    for (const test of securityTests) {
      const response = await this.makeRequest('POST', '/mcp/security', {
        test: test.test
      });
      
      results.push({
        test: test.test,
        passed: response.status === 200 && response.data.secure === test.expected
      });
    }
    
    const passedTests = results.filter(r => r.passed).length;
    expect(passedTests).toBe(securityTests.length);
    
    return results;
  }

  /**
   * Clean up MCP connection
   */
  protected async cleanupMCPConnection(): Promise<void> {
    if (this.mcpContext.mcpConnection) {
      await this.makeRequest('POST', '/mcp/disconnect', {
        connection_id: this.mcpContext.mcpConnection
      });
      
      this.mcpContext.mcpConnection = null;
    }
  }
} 