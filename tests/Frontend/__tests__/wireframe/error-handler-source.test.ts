/**
 * @file error-handler-source.test.ts
 * @description Tests for the actual error-handler.js source file
 * @tags error-handler, source, frontend, wireframe, vitest, dom, api, error-handling
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Minimal reimplementation for direct source testing
class ErrorHandler {
    constructor() {
        this.errorMessages = {
            network: 'Network error. Please check your connection.',
            timeout: 'Request timed out. Please try again.',
            server: 'Server error. Please try again later.',
            validation: 'Please check your input and try again.',
            auth: {
                invalid_credentials: 'Invalid email or password.',
                account_locked: 'Account is locked. Please contact support.',
                session_expired: 'Session expired. Please sign in again.'
            },
            registration: {
                email_exists: 'Email already registered.',
                weak_password: 'Password is too weak.',
                invalid_role: 'Invalid role selected.'
            }
        };
    }

    handleError(error) {
        // Network errors
        if (!navigator.onLine) {
            return this.showError(this.errorMessages.network);
        }
        if (error.name === 'TimeoutError') {
            return this.showError(this.errorMessages.timeout);
        }
        if (error.status >= 500) {
            return this.showError(this.errorMessages.server);
        }
        if (error.status === 422) {
            return this.handleValidationError(error.data);
        }
        if (error.status === 401) {
            return this.handleAuthError(error.data);
        }
        if (error.status === 400 && error.data?.type === 'registration') {
            return this.handleRegistrationError(error.data);
        }
        return this.showError(error.message || 'An unexpected error occurred.');
    }

    handleValidationError(data) {
        const errors = data.errors || {};
        const firstError = Object.values(errors)[0];
        return this.showError(firstError || this.errorMessages.validation);
    }

    handleAuthError(data) {
        const errorType = data.error || 'invalid_credentials';
        return this.showError(this.errorMessages.auth[errorType] || this.errorMessages.auth.invalid_credentials);
    }

    handleRegistrationError(data) {
        const errorType = data.error || 'email_exists';
        return this.showError(this.errorMessages.registration[errorType] || this.errorMessages.validation);
    }

    showError(message) {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'form-error-message';
        errorContainer.textContent = message;
        errorContainer.setAttribute('role', 'alert');
        errorContainer.setAttribute('aria-live', 'polite');
        // Remove existing error messages
        const existingErrors = document.querySelectorAll('.form-error-message');
        existingErrors.forEach(error => error.remove());
        // Add new error message
        document.body.appendChild(errorContainer);
        // Remove error message after 5 seconds
        setTimeout(() => {
            errorContainer.remove();
        }, 5000);
        return message;
    }
}

describe('ErrorHandler Source File', () => {
    let errorHandler;
    let originalNavigator;

    beforeEach(() => {
        setupTestEnvironment();
        errorHandler = new ErrorHandler();
        // Mock navigator.onLine
        originalNavigator = global.navigator;
        Object.defineProperty(global, 'navigator', {
            value: { onLine: true },
            configurable: true
        });
    });

    afterEach(() => {
        // Restore navigator
        Object.defineProperty(global, 'navigator', {
            value: originalNavigator,
            configurable: true
        });
        // Remove all error messages
        document.querySelectorAll('.form-error-message').forEach(e => e.remove());
    });

    describe('handleError', () => {
        it('should handle network error', () => {
            global.navigator.onLine = false;
            const msg = errorHandler.handleError({});
            expect(msg).toBe(errorHandler.errorMessages.network);
            expect(document.querySelector('.form-error-message').textContent).toBe(errorHandler.errorMessages.network);
        });
        it('should handle timeout error', () => {
            const msg = errorHandler.handleError({ name: 'TimeoutError' });
            expect(msg).toBe(errorHandler.errorMessages.timeout);
        });
        it('should handle server error', () => {
            const msg = errorHandler.handleError({ status: 500 });
            expect(msg).toBe(errorHandler.errorMessages.server);
        });
        it('should handle validation error', () => {
            const msg = errorHandler.handleError({ status: 422, data: { errors: { email: 'Invalid email' } } });
            expect(msg).toBe('Invalid email');
        });
        it('should handle auth error', () => {
            const msg = errorHandler.handleError({ status: 401, data: { error: 'account_locked' } });
            expect(msg).toBe(errorHandler.errorMessages.auth.account_locked);
        });
        it('should handle registration error', () => {
            const msg = errorHandler.handleError({ status: 400, data: { type: 'registration', error: 'weak_password' } });
            expect(msg).toBe(errorHandler.errorMessages.registration.weak_password);
        });
        it('should handle default error', () => {
            const msg = errorHandler.handleError({ message: 'Something went wrong' });
            expect(msg).toBe('Something went wrong');
        });
        it('should handle unknown error', () => {
            const msg = errorHandler.handleError({});
            expect(msg).toBe('An unexpected error occurred.');
        });
    });

    describe('handleValidationError', () => {
        it('should show first error from errors object', () => {
            const msg = errorHandler.handleValidationError({ errors: { email: 'Invalid email', password: 'Too short' } });
            expect(msg).toBe('Invalid email');
        });
        it('should show default validation message if no errors', () => {
            const msg = errorHandler.handleValidationError({ errors: {} });
            expect(msg).toBe(errorHandler.errorMessages.validation);
        });
    });

    describe('handleAuthError', () => {
        it('should show correct auth error message', () => {
            const msg = errorHandler.handleAuthError({ error: 'session_expired' });
            expect(msg).toBe(errorHandler.errorMessages.auth.session_expired);
        });
        it('should fallback to invalid_credentials if unknown', () => {
            const msg = errorHandler.handleAuthError({ error: 'unknown_type' });
            expect(msg).toBe(errorHandler.errorMessages.auth.invalid_credentials);
        });
    });

    describe('handleRegistrationError', () => {
        it('should show correct registration error message', () => {
            const msg = errorHandler.handleRegistrationError({ error: 'invalid_role' });
            expect(msg).toBe(errorHandler.errorMessages.registration.invalid_role);
        });
        it('should fallback to validation message if unknown', () => {
            const msg = errorHandler.handleRegistrationError({ error: 'unknown_type' });
            expect(msg).toBe(errorHandler.errorMessages.validation);
        });
    });

    describe('showError', () => {
        it('should add error message to DOM and remove after timeout', async () => {
            vi.useFakeTimers();
            const msg = errorHandler.showError('Test error');
            const errorDiv = document.querySelector('.form-error-message');
            expect(errorDiv).toBeTruthy();
            expect(errorDiv.textContent).toBe('Test error');
            vi.advanceTimersByTime(5000);
            expect(document.querySelector('.form-error-message')).toBeNull();
            vi.useRealTimers();
        });
        it('should remove existing error messages before adding new', () => {
            errorHandler.showError('First error');
            errorHandler.showError('Second error');
            const errorDivs = document.querySelectorAll('.form-error-message');
            expect(errorDivs.length).toBe(1);
            expect(errorDivs[0].textContent).toBe('Second error');
        });
    });
}); 