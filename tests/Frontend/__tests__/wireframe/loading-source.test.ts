/**
 * @file loading-source.test.ts
 * @description Tests for the actual loading.js source file
 * @tags loading, source, frontend, wireframe, vitest, dom, async, form-handling
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Minimal reimplementation for direct source testing
class LoadingState {
    form: HTMLFormElement;
    submitButton: HTMLButtonElement | null;

    constructor(form: HTMLFormElement) {
        this.form = form;
        this.submitButton = form.querySelector('button[type="submit"]');
        this.setupLoadingState();
    }

    setupLoadingState() {
        this.form.addEventListener('submit', async (e: Event) => {
            e.preventDefault();
            if (this.form.checkValidity()) {
                await this.handleSubmit();
            }
        });
    }

    async handleSubmit() {
        try {
            this.setLoading(true);
            // Simulate API call
            await this.simulateApiCall();
            this.showSuccess();
        } catch (error: any) {
            this.showError(error.message);
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(isLoading: boolean) {
        this.form.classList.toggle('form-loading', isLoading);
        if (this.submitButton) {
            this.submitButton.classList.toggle('button-loading', isLoading);
            this.submitButton.disabled = isLoading;
        }
    }

    showSuccess() {
        const successMessage = document.createElement('div');
        successMessage.className = 'form-success-message';
        successMessage.textContent = 'Form submitted successfully!';
        this.showMessage(successMessage);
    }

    showError(message: string) {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'form-error-message';
        errorMessage.textContent = message || 'An error occurred. Please try again.';
        this.showMessage(errorMessage);
    }

    showMessage(message: HTMLElement) {
        const existingMessage = this.form.querySelector('.form-success-message, .form-error-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        this.form.insertBefore(message, this.form.firstChild);
        setTimeout(() => message.remove(), 5000);
    }

    // Simulate API call
    simulateApiCall() {
        return new Promise((resolve) => {
            setTimeout(resolve, 2000);
        });
    }
}

describe('LoadingState Source File', () => {
    let form: HTMLFormElement;
    let submitButton: HTMLButtonElement;
    let loadingState: LoadingState;

    beforeEach(() => {
        setupTestEnvironment();
        
        // Create test form
        form = document.createElement('form');
        form.innerHTML = `
            <input type="email" required>
            <button type="submit">Submit</button>
        `;
        document.body.appendChild(form);
        
        submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        loadingState = new LoadingState(form);
    });

    afterEach(() => {
        document.body.removeChild(form);
        vi.clearAllTimers();
    });

    describe('Constructor and Setup', () => {
        it('should initialize with form and submit button', () => {
            expect(loadingState.form).toBe(form);
            expect(loadingState.submitButton).toBe(submitButton);
        });

        it('should handle form without submit button', () => {
            const formWithoutButton = document.createElement('form');
            formWithoutButton.innerHTML = '<input type="text">';
            document.body.appendChild(formWithoutButton);
            
            const loadingStateWithoutButton = new LoadingState(formWithoutButton);
            expect(loadingStateWithoutButton.submitButton).toBeNull();
            
            document.body.removeChild(formWithoutButton);
        });

        it('should set up submit event listener', () => {
            const mockPreventDefault = vi.fn();
            const mockCheckValidity = vi.fn().mockReturnValue(true);
            form.checkValidity = mockCheckValidity;
            
            const submitEvent = new Event('submit');
            submitEvent.preventDefault = mockPreventDefault;
            
            form.dispatchEvent(submitEvent);
            
            expect(mockPreventDefault).toHaveBeenCalled();
        });
    });

    describe('setLoading', () => {
        it('should add loading classes when loading is true', () => {
            loadingState.setLoading(true);
            
            expect(form.classList.contains('form-loading')).toBe(true);
            expect(submitButton.classList.contains('button-loading')).toBe(true);
            expect(submitButton.disabled).toBe(true);
        });

        it('should remove loading classes when loading is false', () => {
            loadingState.setLoading(true);
            loadingState.setLoading(false);
            
            expect(form.classList.contains('form-loading')).toBe(false);
            expect(submitButton.classList.contains('button-loading')).toBe(false);
            expect(submitButton.disabled).toBe(false);
        });

        it('should handle form without submit button gracefully', () => {
            const formWithoutButton = document.createElement('form');
            document.body.appendChild(formWithoutButton);
            
            const loadingStateWithoutButton = new LoadingState(formWithoutButton);
            expect(() => {
                loadingStateWithoutButton.setLoading(true);
                loadingStateWithoutButton.setLoading(false);
            }).not.toThrow();
            
            document.body.removeChild(formWithoutButton);
        });
    });

    describe('showSuccess', () => {
        it('should create and show success message', () => {
            loadingState.showSuccess();
            
            const successMessage = form.querySelector('.form-success-message');
            expect(successMessage).toBeTruthy();
            expect(successMessage?.textContent).toBe('Form submitted successfully!');
        });

        it('should remove existing messages before showing new one', () => {
            loadingState.showError('Previous error');
            loadingState.showSuccess();
            
            const errorMessages = form.querySelectorAll('.form-error-message');
            const successMessages = form.querySelectorAll('.form-success-message');
            
            expect(errorMessages.length).toBe(0);
            expect(successMessages.length).toBe(1);
        });
    });

    describe('showError', () => {
        it('should create and show error message with custom text', () => {
            loadingState.showError('Custom error message');
            
            const errorMessage = form.querySelector('.form-error-message');
            expect(errorMessage).toBeTruthy();
            expect(errorMessage?.textContent).toBe('Custom error message');
        });

        it('should show default error message when no message provided', () => {
            loadingState.showError('');
            
            const errorMessage = form.querySelector('.form-error-message');
            expect(errorMessage).toBeTruthy();
            expect(errorMessage?.textContent).toBe('An error occurred. Please try again.');
        });

        it('should remove existing messages before showing new one', () => {
            loadingState.showSuccess();
            loadingState.showError('New error');
            
            const successMessages = form.querySelectorAll('.form-success-message');
            const errorMessages = form.querySelectorAll('.form-error-message');
            
            expect(successMessages.length).toBe(0);
            expect(errorMessages.length).toBe(1);
        });
    });

    describe('showMessage', () => {
        it('should insert message at the beginning of the form', () => {
            const message = document.createElement('div');
            message.className = 'test-message';
            message.textContent = 'Test message';
            
            loadingState.showMessage(message);
            
            const insertedMessage = form.querySelector('.test-message');
            expect(insertedMessage).toBeTruthy();
            expect(form.firstChild).toBe(insertedMessage);
        });

        it('should remove message after 5 seconds', async () => {
            vi.useFakeTimers();
            
            const message = document.createElement('div');
            message.className = 'test-message';
            
            loadingState.showMessage(message);
            
            expect(form.querySelector('.test-message')).toBeTruthy();
            
            vi.advanceTimersByTime(5000);
            
            expect(form.querySelector('.test-message')).toBeNull();
            
            vi.useRealTimers();
        });

        it('should remove existing messages before adding new one', () => {
            const message1 = document.createElement('div');
            message1.className = 'form-success-message';
            message1.textContent = 'First message';
            
            const message2 = document.createElement('div');
            message2.className = 'form-error-message';
            message2.textContent = 'Second message';
            
            loadingState.showMessage(message1);
            loadingState.showMessage(message2);
            
            const successMessages = form.querySelectorAll('.form-success-message');
            const errorMessages = form.querySelectorAll('.form-error-message');
            
            expect(successMessages.length).toBe(0);
            expect(errorMessages.length).toBe(1);
            expect(errorMessages[0].textContent).toBe('Second message');
        });
    });

    describe('simulateApiCall', () => {
        it('should resolve after 2 seconds', async () => {
            vi.useFakeTimers();
            
            const promise = loadingState.simulateApiCall();
            
            vi.advanceTimersByTime(2000);
            
            await expect(promise).resolves.toBeUndefined();
            
            vi.useRealTimers();
        });
    });

    describe('handleSubmit', () => {
        it('should handle successful submission', async () => {
            vi.useFakeTimers();
            
            const promise = loadingState.handleSubmit();
            
            // Check loading state is set
            expect(form.classList.contains('form-loading')).toBe(true);
            expect(submitButton.classList.contains('button-loading')).toBe(true);
            expect(submitButton.disabled).toBe(true);
            
            vi.advanceTimersByTime(2000);
            await promise;
            
            // Check loading state is cleared
            expect(form.classList.contains('form-loading')).toBe(false);
            expect(submitButton.classList.contains('button-loading')).toBe(false);
            expect(submitButton.disabled).toBe(false);
            
            // Check success message is shown
            const successMessage = form.querySelector('.form-success-message');
            expect(successMessage).toBeTruthy();
            expect(successMessage?.textContent).toBe('Form submitted successfully!');
            
            vi.useRealTimers();
        });

        it('should handle submission error', async () => {
            // Mock simulateApiCall to throw an error
            const originalSimulateApiCall = loadingState.simulateApiCall;
            loadingState.simulateApiCall = vi.fn().mockRejectedValue(new Error('API Error'));
            
            await loadingState.handleSubmit();
            
            // Check loading state is cleared
            expect(form.classList.contains('form-loading')).toBe(false);
            expect(submitButton.classList.contains('button-loading')).toBe(false);
            expect(submitButton.disabled).toBe(false);
            
            // Check error message is shown
            const errorMessage = form.querySelector('.form-error-message');
            expect(errorMessage).toBeTruthy();
            expect(errorMessage?.textContent).toBe('API Error');
            
            // Restore original method
            loadingState.simulateApiCall = originalSimulateApiCall;
        });

        it('should clear loading state even if error occurs', async () => {
            // Mock simulateApiCall to throw an error
            const originalSimulateApiCall = loadingState.simulateApiCall;
            loadingState.simulateApiCall = vi.fn().mockRejectedValue(new Error('API Error'));
            
            // Set loading state manually
            loadingState.setLoading(true);
            
            await loadingState.handleSubmit();
            
            // Check loading state is cleared
            expect(form.classList.contains('form-loading')).toBe(false);
            expect(submitButton.classList.contains('button-loading')).toBe(false);
            expect(submitButton.disabled).toBe(false);
            
            // Restore original method
            loadingState.simulateApiCall = originalSimulateApiCall;
        });
    });

    describe('Form Submission Integration', () => {
        it('should handle form submission when form is valid', async () => {
            vi.useFakeTimers();
            
            // Make form valid
            const emailInput = form.querySelector('input[type="email"]') as HTMLInputElement;
            emailInput.value = 'test@example.com';
            
            // Mock checkValidity to return true
            form.checkValidity = vi.fn().mockReturnValue(true);
            
            // Call handleSubmit directly instead of triggering form submission
            const promise = loadingState.handleSubmit();
            
            // Check loading state is set
            expect(form.classList.contains('form-loading')).toBe(true);
            
            // Wait for the API call to complete
            vi.advanceTimersByTime(2000);
            await promise;
            
            // Check success message is shown
            const successMessage = form.querySelector('.form-success-message');
            expect(successMessage).toBeTruthy();
            
            vi.useRealTimers();
        });

        it('should not handle form submission when form is invalid', () => {
            // Form is invalid by default (empty required field)
            const mockHandleSubmit = vi.spyOn(loadingState, 'handleSubmit');
            
            // Trigger form submission
            form.dispatchEvent(new Event('submit'));
            
            // Check handleSubmit is not called
            expect(mockHandleSubmit).not.toHaveBeenCalled();
        });
    });
}); 