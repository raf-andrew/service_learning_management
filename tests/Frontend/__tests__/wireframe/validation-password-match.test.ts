/**
 * @file validation-password-match.test.ts
 * @description Password match validation tests for wireframe functionality
 * @tags validation, password-match, frontend, wireframe, vitest, laravel-validation
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { mockValidation } from '../helpers/validationHelpers'

describe('Password Match Validation', () => {
  beforeEach(() => {
    setupTestEnvironment()
  })

  describe('Matching Passwords', () => {
    it('should validate identical passwords', () => {
      expect(mockValidation.passwordMatch('password123', 'password123')).toBe(true)
      expect(mockValidation.passwordMatch('p@ssw0rd', 'p@ssw0rd')).toBe(true)
      expect(mockValidation.passwordMatch('12345678', '12345678')).toBe(true)
    })

    it('should validate empty passwords as matching', () => {
      expect(mockValidation.passwordMatch('', '')).toBe(true)
    })

    it('should validate passwords with special characters', () => {
      expect(mockValidation.passwordMatch('p@ss!w0rd', 'p@ss!w0rd')).toBe(true)
      expect(mockValidation.passwordMatch('pass#word', 'pass#word')).toBe(true)
      expect(mockValidation.passwordMatch('pass$word', 'pass$word')).toBe(true)
    })

    it('should validate passwords with spaces', () => {
      expect(mockValidation.passwordMatch('pass word', 'pass word')).toBe(true)
      expect(mockValidation.passwordMatch('my password', 'my password')).toBe(true)
    })

    it('should validate passwords with unicode characters', () => {
      expect(mockValidation.passwordMatch('pässwörd', 'pässwörd')).toBe(true)
      expect(mockValidation.passwordMatch('пароль123', 'пароль123')).toBe(true)
      expect(mockValidation.passwordMatch('密码123', '密码123')).toBe(true)
    })
  })

  describe('Non-Matching Passwords', () => {
    it('should reject different passwords', () => {
      expect(mockValidation.passwordMatch('password123', 'password124')).toBe(false)
      expect(mockValidation.passwordMatch('password', 'password1')).toBe(false)
      expect(mockValidation.passwordMatch('pass', 'password')).toBe(false)
    })

    it('should reject case-sensitive differences', () => {
      expect(mockValidation.passwordMatch('Password', 'password')).toBe(false)
      expect(mockValidation.passwordMatch('PASSWORD', 'password')).toBe(false)
      expect(mockValidation.passwordMatch('PassWord', 'password')).toBe(false)
    })

    it('should reject when one password is empty', () => {
      expect(mockValidation.passwordMatch('', 'password')).toBe(false)
      expect(mockValidation.passwordMatch('password', '')).toBe(false)
    })

    it('should reject when passwords have different whitespace', () => {
      expect(mockValidation.passwordMatch('password', ' password')).toBe(false)
      expect(mockValidation.passwordMatch('password', 'password ')).toBe(false)
      expect(mockValidation.passwordMatch(' password', 'password')).toBe(false)
      expect(mockValidation.passwordMatch('password ', 'password')).toBe(false)
    })
  })

  describe('Edge Cases', () => {
    it('should handle very long passwords', () => {
      const longPassword = 'a'.repeat(1000)
      expect(mockValidation.passwordMatch(longPassword, longPassword)).toBe(true)
      expect(mockValidation.passwordMatch(longPassword, longPassword + '1')).toBe(false)
    })

    it('should handle single character passwords', () => {
      expect(mockValidation.passwordMatch('a', 'a')).toBe(true)
      expect(mockValidation.passwordMatch('a', 'b')).toBe(false)
    })

    it('should handle null and undefined values', () => {
      expect(mockValidation.passwordMatch(null as any, null as any)).toBe(true)
      expect(mockValidation.passwordMatch(undefined as any, undefined as any)).toBe(true)
      expect(mockValidation.passwordMatch('password', null as any)).toBe(false)
      expect(mockValidation.passwordMatch(null as any, 'password')).toBe(false)
      expect(mockValidation.passwordMatch('password', undefined as any)).toBe(false)
      expect(mockValidation.passwordMatch(undefined as any, 'password')).toBe(false)
    })

    it('should handle numbers as strings', () => {
      expect(mockValidation.passwordMatch('123', '123')).toBe(true)
      expect(mockValidation.passwordMatch('123', 123 as any)).toBe(false)
      expect(mockValidation.passwordMatch(123 as any, '123')).toBe(false)
    })
  })

  describe('Laravel-Style Validation', () => {
    it('should match Laravel confirmed validation rules', () => {
      // Laravel's confirmed rule checks for exact string match
      expect(mockValidation.passwordMatch('password', 'password')).toBe(true)
      expect(mockValidation.passwordMatch('password', 'Password')).toBe(false)
      expect(mockValidation.passwordMatch('password', 'password ')).toBe(false)
    })

    it('should handle common password confirmation scenarios', () => {
      const scenarios = [
        { password: 'password123', confirm: 'password123', expected: true },
        { password: 'password123', confirm: 'password124', expected: false },
        { password: 'Password123', confirm: 'password123', expected: false },
        { password: 'password123', confirm: 'PASSWORD123', expected: false },
        { password: 'password 123', confirm: 'password123', expected: false },
        { password: 'password123', confirm: 'password 123', expected: false }
      ]

      scenarios.forEach(({ password, confirm, expected }) => {
        expect(mockValidation.passwordMatch(password, confirm)).toBe(expected)
      })
    })
  })

  describe('Form Integration', () => {
    it('should work with password confirmation fields', () => {
      // Simulate password confirmation field validation
      const passwordField = 'MySecurePassword123!'
      const confirmField = 'MySecurePassword123!'
      
      expect(mockValidation.passwordMatch(passwordField, confirmField)).toBe(true)
    })

    it('should handle real-world password scenarios', () => {
      const realPasswords = [
        { password: 'qwerty123', confirm: 'qwerty123', expected: true },
        { password: 'admin123', confirm: 'admin123', expected: true },
        { password: 'letmein123', confirm: 'letmein123', expected: true },
        { password: 'MyP@ssw0rd', confirm: 'MyP@ssw0rd', expected: true },
        { password: 'Secure123#', confirm: 'Secure123#', expected: true }
      ]

      realPasswords.forEach(({ password, confirm, expected }) => {
        expect(mockValidation.passwordMatch(password, confirm)).toBe(expected)
      })
    })

    it('should handle common user mistakes', () => {
      const commonMistakes = [
        { password: 'password123', confirm: 'password124', expected: false },
        { password: 'password123', confirm: 'password12', expected: false },
        { password: 'password123', confirm: 'password1234', expected: false },
        { password: 'password123', confirm: 'Password123', expected: false },
        { password: 'password123', confirm: 'PASSWORD123', expected: false }
      ]

      commonMistakes.forEach(({ password, confirm, expected }) => {
        expect(mockValidation.passwordMatch(password, confirm)).toBe(expected)
      })
    })
  })
}) 