/**
 * @file validation-email.test.ts
 * @description Email validation tests for wireframe functionality
 * @tags validation, email, frontend, wireframe, vitest, laravel-validation
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { mockValidation, testData } from '../helpers/validationHelpers'

describe('Email Validation', () => {
  beforeEach(() => {
    setupTestEnvironment()
  })

  describe('Valid Email Addresses', () => {
    it('should validate standard email addresses', () => {
      testData.validEmails.forEach(email => {
        expect(mockValidation.email(email)).toBe(true)
      })
    })

    it('should validate emails with subdomains', () => {
      expect(mockValidation.email('user@subdomain.example.com')).toBe(true)
      expect(mockValidation.email('test@mail.example.co.uk')).toBe(true)
    })

    it('should validate emails with special characters', () => {
      expect(mockValidation.email('user+tag@example.com')).toBe(true)
      expect(mockValidation.email('user.name@example.com')).toBe(true)
      expect(mockValidation.email('user_name@example.com')).toBe(true)
    })

    it('should validate emails with numbers', () => {
      expect(mockValidation.email('user123@example.com')).toBe(true)
      expect(mockValidation.email('123user@example.com')).toBe(true)
      expect(mockValidation.email('user@123example.com')).toBe(true)
    })
  })

  describe('Invalid Email Addresses', () => {
    it('should reject malformed email addresses', () => {
      testData.invalidEmails.forEach(email => {
        expect(mockValidation.email(email)).toBe(false)
      })
    })

    it('should reject emails without domain', () => {
      expect(mockValidation.email('user@')).toBe(false)
      expect(mockValidation.email('user')).toBe(false)
    })

    it('should reject emails without local part', () => {
      expect(mockValidation.email('@example.com')).toBe(false)
      expect(mockValidation.email('@')).toBe(false)
    })

    it('should reject emails with invalid TLD', () => {
      expect(mockValidation.email('user@example')).toBe(false)
      expect(mockValidation.email('user@example.c')).toBe(false)
    })

    it('should reject emails with spaces', () => {
      expect(mockValidation.email('user name@example.com')).toBe(false)
      expect(mockValidation.email('user@example .com')).toBe(false)
      expect(mockValidation.email(' user@example.com')).toBe(false)
      expect(mockValidation.email('user@example.com ')).toBe(false)
    })
  })

  describe('Edge Cases', () => {
    it('should reject emails with consecutive dots', () => {
      expect(mockValidation.email('user..name@example.com')).toBe(false)
      expect(mockValidation.email('user@example..com')).toBe(false)
      expect(mockValidation.email('user@.example.com')).toBe(false)
      expect(mockValidation.email('user@example.')).toBe(false)
    })

    it('should reject empty strings', () => {
      expect(mockValidation.email('')).toBe(false)
    })

    it('should handle very long email addresses', () => {
      const longEmail = 'a'.repeat(50) + '@' + 'b'.repeat(50) + '.com'
      expect(mockValidation.email(longEmail)).toBe(true)
    })

    it('should handle special domain characters', () => {
      expect(mockValidation.email('user@example-domain.com')).toBe(true)
      expect(mockValidation.email('user@example_domain.com')).toBe(false)
    })
  })

  describe('Laravel-Style Validation', () => {
    it('should match Laravel email validation rules', () => {
      // Laravel's email validation is quite permissive
      expect(mockValidation.email('user@example.com')).toBe(true)
      expect(mockValidation.email('user+tag@example.com')).toBe(true)
      expect(mockValidation.email('user.name@example.com')).toBe(true)
    })

    it('should handle international domains', () => {
      expect(mockValidation.email('user@example.co.uk')).toBe(true)
      expect(mockValidation.email('user@example.org')).toBe(true)
      expect(mockValidation.email('user@example.net')).toBe(true)
    })
  })
}) 