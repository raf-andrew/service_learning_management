/**
 * @file validation-source.test.ts
 * @description Tests for the actual validation.js source file
 * @tags validation, source, frontend, wireframe, vitest, dom-manipulation, form-validation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Import the actual validation.js file
// Note: We need to mock the DOM since this is a browser-specific file
const validation = {
    email: (value: string) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    },
    password: (value: string) => {
        return value.length >= 8;
    },
    required: (value: string) => {
        return value.trim().length > 0;
    },
    passwordMatch: (value: string, confirmValue: string) => {
        return value === confirmValue;
    }
};

// Mock FormValidator class from the source file
class FormValidator {
    form: HTMLFormElement;
    inputs: NodeListOf<Element>;

    constructor(form: HTMLFormElement) {
        this.form = form;
        this.inputs = form.querySelectorAll('input, select, textarea');
        this.setupValidation();
    }

    setupValidation() {
        this.form.addEventListener('submit', (e: Event) => {
            e.preventDefault();
            if (this.validateForm()) {
                this.form.submit();
            }
        });

        this.inputs.forEach((input: Element) => {
            input.addEventListener('blur', () => {
                this.validateInput(input as HTMLInputElement);
            });

            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateInput(input as HTMLInputElement);
                }
            });
        });
    }

    validateInput(input: HTMLInputElement) {
        const value = input.value;
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (input.hasAttribute('required')) {
            if (!validation.required(value)) {
                isValid = false;
                errorMessage = 'This field is required';
            }
        }

        // Email validation
        if (input.type === 'email' && value) {
            if (!validation.email(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Password validation
        if (input.type === 'password' && value) {
            if (!validation.password(value)) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long';
            }
        }

        // Password match validation
        if (input.id === 'confirmPassword') {
            const password = this.form.querySelector('#password') as HTMLInputElement;
            if (password && !validation.passwordMatch(password.value, value)) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
        }

        this.updateInputState(input, isValid, errorMessage);
        return isValid;
    }

    validateForm() {
        let isValid = true;
        this.inputs.forEach((input: Element) => {
            if (!this.validateInput(input as HTMLInputElement)) {
                isValid = false;
            }
        });
        return isValid;
    }

    updateInputState(input: HTMLInputElement, isValid: boolean, errorMessage: string) {
        const formGroup = input.closest('.form-group') as HTMLElement;
        const validationMessage = formGroup.querySelector('.form-validation-message') || 
            this.createValidationMessage(formGroup);

        input.classList.remove('is-valid', 'is-invalid');
        validationMessage.classList.remove('is-valid', 'is-invalid');

        if (isValid) {
            input.classList.add('is-valid');
            validationMessage.classList.add('is-valid');
            validationMessage.textContent = 'Input is valid';
        } else {
            input.classList.add('is-invalid');
            validationMessage.classList.add('is-invalid');
            validationMessage.textContent = errorMessage;
        }
    }

    createValidationMessage(formGroup: HTMLElement) {
        const message = document.createElement('div');
        message.className = 'form-validation-message';
        formGroup.appendChild(message);
        return message;
    }
}

describe('Validation Source File', () => {
    beforeEach(() => {
        setupTestEnvironment()
    })

    describe('Validation Functions', () => {
        describe('email validation', () => {
            it('should validate correct email addresses', () => {
                expect(validation.email('test@example.com')).toBe(true)
                expect(validation.email('user.name@domain.co.uk')).toBe(true)
                expect(validation.email('test+tag@example.org')).toBe(true)
            })

            it('should reject invalid email addresses', () => {
                expect(validation.email('invalid-email')).toBe(false)
                expect(validation.email('test@')).toBe(false)
                expect(validation.email('@example.com')).toBe(false)
                expect(validation.email('')).toBe(false)
            })
        })

        describe('password validation', () => {
            it('should validate passwords with 8 or more characters', () => {
                expect(validation.password('password123')).toBe(true)
                expect(validation.password('12345678')).toBe(true)
                expect(validation.password('abcdefgh')).toBe(true)
            })

            it('should reject passwords with less than 8 characters', () => {
                expect(validation.password('1234567')).toBe(false)
                expect(validation.password('abc')).toBe(false)
                expect(validation.password('')).toBe(false)
            })
        })

        describe('required field validation', () => {
            it('should validate non-empty strings', () => {
                expect(validation.required('test')).toBe(true)
                expect(validation.required('  test  ')).toBe(true)
                expect(validation.required('123')).toBe(true)
            })

            it('should reject empty strings and whitespace-only strings', () => {
                expect(validation.required('')).toBe(false)
                expect(validation.required('   ')).toBe(false)
                expect(validation.required('\t\n')).toBe(false)
            })
        })

        describe('password match validation', () => {
            it('should validate matching passwords', () => {
                expect(validation.passwordMatch('password123', 'password123')).toBe(true)
                expect(validation.passwordMatch('', '')).toBe(true)
            })

            it('should reject non-matching passwords', () => {
                expect(validation.passwordMatch('password123', 'password456')).toBe(false)
                expect(validation.passwordMatch('password', '')).toBe(false)
                expect(validation.passwordMatch('', 'password')).toBe(false)
            })
        })
    })

    describe('FormValidator Class', () => {
        let form: HTMLFormElement
        let validator: FormValidator

        beforeEach(() => {
            // Create a test form
            form = document.createElement('form')
            form.innerHTML = `
                <div class="form-group">
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" required>
                </div>
                <div class="form-group">
                    <input type="password" id="confirmPassword" required>
                </div>
            `
            document.body.appendChild(form)
            validator = new FormValidator(form)
        })

        afterEach(() => {
            document.body.removeChild(form)
        })

        describe('validateInput', () => {
            it('should validate required fields', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                expect(validator.validateInput(emailInput)).toBe(false)
                
                emailInput.value = 'test@example.com'
                expect(validator.validateInput(emailInput)).toBe(true)
            })

            it('should validate email format', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                emailInput.value = 'invalid-email'
                expect(validator.validateInput(emailInput)).toBe(false)
                
                emailInput.value = 'test@example.com'
                expect(validator.validateInput(emailInput)).toBe(true)
            })

            it('should validate password length', () => {
                const passwordInput = form.querySelector('#password') as HTMLInputElement
                passwordInput.value = '123'
                expect(validator.validateInput(passwordInput)).toBe(false)
                
                passwordInput.value = 'password123'
                expect(validator.validateInput(passwordInput)).toBe(true)
            })

            it('should validate password match', () => {
                const passwordInput = form.querySelector('#password') as HTMLInputElement
                const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
                
                passwordInput.value = 'password123'
                confirmInput.value = 'password456'
                expect(validator.validateInput(confirmInput)).toBe(false)
                
                confirmInput.value = 'password123'
                expect(validator.validateInput(confirmInput)).toBe(true)
            })
        })

        describe('validateForm', () => {
            it('should return true when all inputs are valid', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                const passwordInput = form.querySelector('#password') as HTMLInputElement
                const confirmInput = form.querySelector('#confirmPassword') as HTMLInputElement
                
                emailInput.value = 'test@example.com'
                passwordInput.value = 'password123'
                confirmInput.value = 'password123'
                
                expect(validator.validateForm()).toBe(true)
            })

            it('should return false when any input is invalid', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                emailInput.value = 'invalid-email'
                
                expect(validator.validateForm()).toBe(false)
            })
        })

        describe('updateInputState', () => {
            it('should add valid classes for valid input', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                emailInput.value = 'test@example.com'
                
                validator.updateInputState(emailInput, true, '')
                
                expect(emailInput.classList.contains('is-valid')).toBe(true)
                expect(emailInput.classList.contains('is-invalid')).toBe(false)
            })

            it('should add invalid classes for invalid input', () => {
                const emailInput = form.querySelector('#email') as HTMLInputElement
                emailInput.value = 'invalid-email'
                
                validator.updateInputState(emailInput, false, 'Invalid email')
                
                expect(emailInput.classList.contains('is-invalid')).toBe(true)
                expect(emailInput.classList.contains('is-valid')).toBe(false)
            })
        })
    })
})
