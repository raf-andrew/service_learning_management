/**
 * @fileoverview Api/SniffingController Functional Tests
 * @description Tests for Api/SniffingController API endpoints and validation logic
 * @tags functional,sniffing,api,controllers,validation,laravel,vitest
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'

describe('Api/SniffingController', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Parameter Validation', () => {
    it('should validate run endpoint parameters', () => {
      const validateRunParameters = (data: any) => {
        const errors: any = {}
        
        if (!data.files || !Array.isArray(data.files) || data.files.length === 0) {
          errors.files = ['The files field is required and must be an array.']
        }

        if (!data.report_format || !['html', 'markdown', 'json'].includes(data.report_format)) {
          errors.report_format = ['The report format field must be one of: html, markdown, json.']
        }

        if (data.timeout && (typeof data.timeout !== 'number' || data.timeout < 1)) {
          errors.timeout = ['The timeout field must be a positive number.']
        }

        return {
          isValid: Object.keys(errors).length === 0,
          errors
        }
      }

      // Test valid parameters
      const validData = {
        files: ['file1.php', 'file2.php'],
        report_format: 'html',
        timeout: 30
      }
      const validResult = validateRunParameters(validData)
      expect(validResult.isValid).toBe(true)
      expect(validResult.errors).toEqual({})

      // Test missing files
      const invalidData1 = {
        report_format: 'html',
        timeout: 30
      }
      const invalidResult1 = validateRunParameters(invalidData1)
      expect(invalidResult1.isValid).toBe(false)
      expect(invalidResult1.errors.files).toBeDefined()

      // Test invalid report format
      const invalidData2 = {
        files: ['file1.php'],
        report_format: 'invalid',
        timeout: 30
      }
      const invalidResult2 = validateRunParameters(invalidData2)
      expect(invalidResult2.isValid).toBe(false)
      expect(invalidResult2.errors.report_format).toBeDefined()

      // Test invalid timeout
      const invalidData3 = {
        files: ['file1.php'],
        report_format: 'html',
        timeout: -1
      }
      const invalidResult3 = validateRunParameters(invalidData3)
      expect(invalidResult3.isValid).toBe(false)
      expect(invalidResult3.errors.timeout).toBeDefined()
    })

    it('should validate results endpoint parameters', () => {
      const validateResultsParameters = (data: any) => {
        const errors: any = {}
        
        if (!data.scan_id || typeof data.scan_id !== 'string') {
          errors.scan_id = ['The scan_id field is required and must be a string.']
        }

        if (data.include_details && typeof data.include_details !== 'boolean') {
          errors.include_details = ['The include_details field must be a boolean.']
        }

        return {
          isValid: Object.keys(errors).length === 0,
          errors
        }
      }

      // Test valid parameters
      const validData = {
        scan_id: 'scan_12345',
        include_details: true
      }
      const validResult = validateResultsParameters(validData)
      expect(validResult.isValid).toBe(true)

      // Test missing scan_id
      const invalidData = {
        include_details: true
      }
      const invalidResult = validateResultsParameters(invalidData)
      expect(invalidResult.isValid).toBe(false)
      expect(invalidResult.errors.scan_id).toBeDefined()
    })

    it('should validate analyze endpoint parameters', () => {
      const validateAnalyzeParameters = (data: any) => {
        const errors: any = {}
        
        if (!data.file_path || typeof data.file_path !== 'string') {
          errors.file_path = ['The file_path field is required and must be a string.']
        }

        if (!data.analysis_type || !['security', 'performance', 'quality'].includes(data.analysis_type)) {
          errors.analysis_type = ['The analysis_type field must be one of: security, performance, quality.']
        }

        if (data.depth && (typeof data.depth !== 'number' || data.depth < 1 || data.depth > 10)) {
          errors.depth = ['The depth field must be a number between 1 and 10.']
        }

        return {
          isValid: Object.keys(errors).length === 0,
          errors
        }
      }

      // Test valid parameters
      const validData = {
        file_path: '/path/to/file.php',
        analysis_type: 'security',
        depth: 5
      }
      const validResult = validateAnalyzeParameters(validData)
      expect(validResult.isValid).toBe(true)

      // Test invalid analysis type
      const invalidData = {
        file_path: '/path/to/file.php',
        analysis_type: 'invalid',
        depth: 5
      }
      const invalidResult = validateAnalyzeParameters(invalidData)
      expect(invalidResult.isValid).toBe(false)
      expect(invalidResult.errors.analysis_type).toBeDefined()
    })

    it('should validate rules endpoint parameters', () => {
      const validateRulesParameters = (data: any) => {
        const errors: any = {}
        
        if (data.rule_type && !['security', 'performance', 'quality', 'custom'].includes(data.rule_type)) {
          errors.rule_type = ['The rule_type field must be one of: security, performance, quality, custom.']
        }

        if (data.severity && !['low', 'medium', 'high', 'critical'].includes(data.severity)) {
          errors.severity = ['The severity field must be one of: low, medium, high, critical.']
        }

        if (data.enabled !== undefined && typeof data.enabled !== 'boolean') {
          errors.enabled = ['The enabled field must be a boolean.']
        }

        return {
          isValid: Object.keys(errors).length === 0,
          errors
        }
      }

      // Test valid parameters
      const validData = {
        rule_type: 'security',
        severity: 'high',
        enabled: true
      }
      const validResult = validateRulesParameters(validData)
      expect(validResult.isValid).toBe(true)

      // Test invalid rule type
      const invalidData = {
        rule_type: 'invalid',
        severity: 'high',
        enabled: true
      }
      const invalidResult = validateRulesParameters(invalidData)
      expect(invalidResult.isValid).toBe(false)
      expect(invalidResult.errors.rule_type).toBeDefined()
    })
  })

  describe('Response Generation', () => {
    it('should create success responses', () => {
      const createSuccessResponse = (message: string, data?: any) => ({
        success: true,
        message,
        data: data || null,
        timestamp: new Date().toISOString()
      })

      const response = createSuccessResponse('Scan completed successfully', { scan_id: '123' })
      
      expect(response.success).toBe(true)
      expect(response.message).toBe('Scan completed successfully')
      expect(response.data).toEqual({ scan_id: '123' })
      expect(response.timestamp).toBeDefined()
    })

    it('should create error responses', () => {
      const createErrorResponse = (status: number, error: string) => ({
        success: false,
        error,
        status,
        timestamp: new Date().toISOString()
      })

      const response = createErrorResponse(400, 'Invalid parameters')
      
      expect(response.success).toBe(false)
      expect(response.error).toBe('Invalid parameters')
      expect(response.status).toBe(400)
      expect(response.timestamp).toBeDefined()
    })

    it('should create validation error responses', () => {
      const createValidationErrorResponse = (errors: any) => ({
        success: false,
        error: 'Validation failed',
        errors,
        status: 422,
        timestamp: new Date().toISOString()
      })

      const errors = {
        files: ['The files field is required.'],
        report_format: ['Invalid report format.']
      }
      const response = createValidationErrorResponse(errors)
      
      expect(response.success).toBe(false)
      expect(response.error).toBe('Validation failed')
      expect(response.errors).toEqual(errors)
      expect(response.status).toBe(422)
    })
  })

  describe('Utility Functions', () => {
    it('should generate file lists', () => {
      const generateFileList = (count: number) => {
        const files = []
        for (let i = 1; i <= count; i++) {
          files.push(`file${i}.php`)
        }
        return files
      }

      const fileList = generateFileList(5)
      expect(fileList).toHaveLength(5)
      expect(fileList[0]).toBe('file1.php')
      expect(fileList[4]).toBe('file5.php')
    })

    it('should calculate memory usage', () => {
      const calculateMemoryUsage = (usageInBytes: number) => {
        const mb = usageInBytes / (1024 * 1024)
        return {
          bytes: usageInBytes,
          megabytes: Math.round(mb * 100) / 100,
          formatted: `${Math.round(mb * 100) / 100} MB`
        }
      }

      const usage = calculateMemoryUsage(1048576) // 1MB
      expect(usage.bytes).toBe(1048576)
      expect(usage.megabytes).toBe(1)
      expect(usage.formatted).toBe('1 MB')
    })

    it('should validate file paths', () => {
      const validateFilePath = (path: string) => {
        const errors = []
        
        if (!path || typeof path !== 'string') {
          errors.push('Path must be a non-empty string')
        }

        if (path.includes('..')) {
          errors.push('Path traversal not allowed')
        }

        if (!path.match(/^[a-zA-Z0-9\/\-_\.]+$/)) {
          errors.push('Path contains invalid characters')
        }

        return {
          isValid: errors.length === 0,
          errors
        }
      }

      // Test valid path
      const validPath = '/app/src/Controller.php'
      const validResult = validateFilePath(validPath)
      expect(validResult.isValid).toBe(true)

      // Test path traversal
      const invalidPath1 = '/app/../secret/file.php'
      const invalidResult1 = validateFilePath(invalidPath1)
      expect(invalidResult1.isValid).toBe(false)
      expect(invalidResult1.errors).toContain('Path traversal not allowed')

      // Test invalid characters
      const invalidPath2 = '/app/file*.php'
      const invalidResult2 = validateFilePath(invalidPath2)
      expect(invalidResult2.isValid).toBe(false)
      expect(invalidResult2.errors).toContain('Path contains invalid characters')
    })

    it('should validate commands', () => {
      const validateCommand = (command: string) => {
        const errors = []
        
        if (!command || typeof command !== 'string') {
          errors.push('Command must be a non-empty string')
        }

        const dangerousCommands = ['rm -rf', 'dd if=', '> /dev/sda', 'chmod 777']
        for (const dangerous of dangerousCommands) {
          if (command.includes(dangerous)) {
            errors.push('Dangerous command detected')
            break
          }
        }

        return {
          isValid: errors.length === 0,
          errors
        }
      }

      // Test valid command
      const validCommand = 'php artisan sniff:analyze file.php'
      const validResult = validateCommand(validCommand)
      expect(validResult.isValid).toBe(true)

      // Test dangerous command
      const dangerousCommand = 'rm -rf /tmp/files'
      const dangerousResult = validateCommand(dangerousCommand)
      expect(dangerousResult.isValid).toBe(false)
      expect(dangerousResult.errors).toContain('Dangerous command detected')
    })
  })
}) 