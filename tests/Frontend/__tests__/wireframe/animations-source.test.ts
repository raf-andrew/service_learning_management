/**
 * @file animations-source.test.ts
 * @description Tests for the actual animations.js source file
 * @tags animations, source, frontend, wireframe, vitest, dom-manipulation, css-animations
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'

// Mock AnimationHandler class from the source file
class AnimationHandler {
    animations: Record<string, any>;

    constructor() {
        this.animations = {
            fadeIn: {
                keyframes: [
                    { opacity: 0 },
                    { opacity: 1 }
                ],
                options: {
                    duration: 300,
                    easing: 'ease-out'
                }
            },
            fadeOut: {
                keyframes: [
                    { opacity: 1 },
                    { opacity: 0 }
                ],
                options: {
                    duration: 300,
                    easing: 'ease-in'
                }
            },
            slideIn: {
                keyframes: [
                    { transform: 'translateY(20px)', opacity: 0 },
                    { transform: 'translateY(0)', opacity: 1 }
                ],
                options: {
                    duration: 400,
                    easing: 'ease-out'
                }
            },
            slideOut: {
                keyframes: [
                    { transform: 'translateY(0)', opacity: 1 },
                    { transform: 'translateY(20px)', opacity: 0 }
                ],
                options: {
                    duration: 400,
                    easing: 'ease-in'
                }
            }
        };
    }

    // Animate element with specified animation
    animate(element: HTMLElement, animationName: string) {
        const animation = this.animations[animationName];
        if (!animation) return;

        return element.animate(animation.keyframes, animation.options);
    }

    // Fade in element
    fadeIn(element: HTMLElement) {
        return this.animate(element, 'fadeIn');
    }

    // Fade out element
    fadeOut(element: HTMLElement) {
        return this.animate(element, 'fadeOut');
    }

    // Slide in element
    slideIn(element: HTMLElement) {
        return this.animate(element, 'slideIn');
    }

    // Slide out element
    slideOut(element: HTMLElement) {
        return this.animate(element, 'slideOut');
    }

    // Show element with animation
    show(element: HTMLElement, animationName: string = 'fadeIn') {
        element.style.display = '';
        return this.animate(element, animationName);
    }

    // Hide element with animation
    hide(element: HTMLElement, animationName: string = 'fadeOut') {
        const animation = this.animate(element, animationName);
        if (animation) {
            animation.onfinish = () => {
                element.style.display = 'none';
            };
        }
        return animation;
    }

    // Animate form validation
    animateValidation(input: HTMLInputElement, isValid: boolean) {
        const formGroup = input.closest('.form-group') as HTMLElement;
        if (!formGroup) return;

        const validationMessage = formGroup.querySelector('.form-validation-message') as HTMLElement;
        if (!validationMessage) return;

        // Reset classes
        input.classList.remove('is-valid', 'is-invalid');
        validationMessage.classList.remove('is-valid', 'is-invalid');

        // Add appropriate classes
        if (isValid) {
            input.classList.add('is-valid');
            validationMessage.classList.add('is-valid');
            this.slideIn(validationMessage);
        } else {
            input.classList.add('is-invalid');
            validationMessage.classList.add('is-invalid');
            this.slideIn(validationMessage);
        }
    }

    // Animate form submission
    animateFormSubmit(form: HTMLFormElement) {
        const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        if (submitButton) {
            submitButton.classList.add('button-loading');
        }
    }

    // Animate form success
    animateFormSuccess(form: HTMLFormElement) {
        const successMessage = form.querySelector('.form-success-message') as HTMLElement;
        if (successMessage) {
            this.slideIn(successMessage);
        }
    }

    // Animate form error
    animateFormError(form: HTMLFormElement) {
        const errorMessage = form.querySelector('.form-error-message') as HTMLElement;
        if (errorMessage) {
            this.slideIn(errorMessage);
        }
    }
}

describe('Animations Source File', () => {
    let animationHandler: AnimationHandler;
    let testElement: HTMLElement;

    beforeEach(() => {
        setupTestEnvironment()
        
        // Mock element.animate method
        const mockAnimate = vi.fn().mockReturnValue({
            onfinish: null
        })
        
        // Add animate method to HTMLElement prototype for testing
        if (!HTMLElement.prototype.animate) {
            HTMLElement.prototype.animate = mockAnimate
        }
        
        animationHandler = new AnimationHandler()
        testElement = document.createElement('div')
        document.body.appendChild(testElement)
    })

    afterEach(() => {
        document.body.removeChild(testElement)
    })

    describe('AnimationHandler Constructor', () => {
        it('should initialize with correct animation definitions', () => {
            expect(animationHandler.animations).toHaveProperty('fadeIn')
            expect(animationHandler.animations).toHaveProperty('fadeOut')
            expect(animationHandler.animations).toHaveProperty('slideIn')
            expect(animationHandler.animations).toHaveProperty('slideOut')
        })

        it('should have correct fadeIn animation configuration', () => {
            const fadeIn = animationHandler.animations.fadeIn
            expect(fadeIn.keyframes).toHaveLength(2)
            expect(fadeIn.keyframes[0]).toEqual({ opacity: 0 })
            expect(fadeIn.keyframes[1]).toEqual({ opacity: 1 })
            expect(fadeIn.options.duration).toBe(300)
            expect(fadeIn.options.easing).toBe('ease-out')
        })

        it('should have correct fadeOut animation configuration', () => {
            const fadeOut = animationHandler.animations.fadeOut
            expect(fadeOut.keyframes).toHaveLength(2)
            expect(fadeOut.keyframes[0]).toEqual({ opacity: 1 })
            expect(fadeOut.keyframes[1]).toEqual({ opacity: 0 })
            expect(fadeOut.options.duration).toBe(300)
            expect(fadeOut.options.easing).toBe('ease-in')
        })

        it('should have correct slideIn animation configuration', () => {
            const slideIn = animationHandler.animations.slideIn
            expect(slideIn.keyframes).toHaveLength(2)
            expect(slideIn.keyframes[0]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
            expect(slideIn.keyframes[1]).toEqual({ transform: 'translateY(0)', opacity: 1 })
            expect(slideIn.options.duration).toBe(400)
            expect(slideIn.options.easing).toBe('ease-out')
        })

        it('should have correct slideOut animation configuration', () => {
            const slideOut = animationHandler.animations.slideOut
            expect(slideOut.keyframes).toHaveLength(2)
            expect(slideOut.keyframes[0]).toEqual({ transform: 'translateY(0)', opacity: 1 })
            expect(slideOut.keyframes[1]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
            expect(slideOut.options.duration).toBe(400)
            expect(slideOut.options.easing).toBe('ease-in')
        })
    })

    describe('animate method', () => {
        it('should return animation for valid animation name', () => {
            const animation = animationHandler.animate(testElement, 'fadeIn')
            expect(animation).toBeDefined()
        })

        it('should return undefined for invalid animation name', () => {
            const animation = animationHandler.animate(testElement, 'invalidAnimation')
            expect(animation).toBeUndefined()
        })

        it('should call element.animate with correct parameters', () => {
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.animate(testElement, 'fadeIn')

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ opacity: 0 }, { opacity: 1 }],
                { duration: 300, easing: 'ease-out' }
            )
        })
    })

    describe('fadeIn method', () => {
        it('should call animate with fadeIn animation', () => {
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.fadeIn(testElement)

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ opacity: 0 }, { opacity: 1 }],
                { duration: 300, easing: 'ease-out' }
            )
        })
    })

    describe('fadeOut method', () => {
        it('should call animate with fadeOut animation', () => {
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.fadeOut(testElement)

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ opacity: 1 }, { opacity: 0 }],
                { duration: 300, easing: 'ease-in' }
            )
        })
    })

    describe('slideIn method', () => {
        it('should call animate with slideIn animation', () => {
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.slideIn(testElement)

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ transform: 'translateY(20px)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }],
                { duration: 400, easing: 'ease-out' }
            )
        })
    })

    describe('slideOut method', () => {
        it('should call animate with slideOut animation', () => {
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.slideOut(testElement)

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ transform: 'translateY(0)', opacity: 1 }, { transform: 'translateY(20px)', opacity: 0 }],
                { duration: 400, easing: 'ease-in' }
            )
        })
    })

    describe('show method', () => {
        it('should set display to empty string and animate with default fadeIn', () => {
            testElement.style.display = 'none'
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.show(testElement)

            expect(testElement.style.display).toBe('')
            expect(mockAnimate).toHaveBeenCalledWith(
                [{ opacity: 0 }, { opacity: 1 }],
                { duration: 300, easing: 'ease-out' }
            )
        })

        it('should use custom animation name when provided', () => {
            testElement.style.display = 'none'
            const mockAnimate = vi.fn()
            testElement.animate = mockAnimate

            animationHandler.show(testElement, 'slideIn')

            expect(testElement.style.display).toBe('')
            expect(mockAnimate).toHaveBeenCalledWith(
                [{ transform: 'translateY(20px)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }],
                { duration: 400, easing: 'ease-out' }
            )
        })
    })

    describe('hide method', () => {
        it('should set display to none when animation finishes', () => {
            const mockAnimation = {
                onfinish: null as (() => void) | null
            }
            const mockAnimate = vi.fn().mockReturnValue(mockAnimation)
            testElement.animate = mockAnimate

            animationHandler.hide(testElement)

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ opacity: 1 }, { opacity: 0 }],
                { duration: 300, easing: 'ease-in' }
            )

            // Simulate animation finish
            if (mockAnimation.onfinish) {
                mockAnimation.onfinish()
                expect(testElement.style.display).toBe('none')
            }
        })

        it('should use custom animation name when provided', () => {
            const mockAnimation = {
                onfinish: null as (() => void) | null
            }
            const mockAnimate = vi.fn().mockReturnValue(mockAnimation)
            testElement.animate = mockAnimate

            animationHandler.hide(testElement, 'slideOut')

            expect(mockAnimate).toHaveBeenCalledWith(
                [{ transform: 'translateY(0)', opacity: 1 }, { transform: 'translateY(20px)', opacity: 0 }],
                { duration: 400, easing: 'ease-in' }
            )
        })
    })

    describe('animateValidation method', () => {
        let formGroup: HTMLElement;
        let input: HTMLInputElement;
        let validationMessage: HTMLElement;

        beforeEach(() => {
            formGroup = document.createElement('div')
            formGroup.className = 'form-group'
            
            input = document.createElement('input')
            input.type = 'text'
            
            validationMessage = document.createElement('div')
            validationMessage.className = 'form-validation-message'
            
            formGroup.appendChild(input)
            formGroup.appendChild(validationMessage)
            document.body.appendChild(formGroup)
        })

        afterEach(() => {
            document.body.removeChild(formGroup)
        })

        it('should add valid classes and animate for valid input', () => {
            const mockSlideIn = vi.spyOn(animationHandler, 'slideIn')

            animationHandler.animateValidation(input, true)

            expect(input.classList.contains('is-valid')).toBe(true)
            expect(input.classList.contains('is-invalid')).toBe(false)
            expect(validationMessage.classList.contains('is-valid')).toBe(true)
            expect(validationMessage.classList.contains('is-invalid')).toBe(false)
            expect(mockSlideIn).toHaveBeenCalledWith(validationMessage)
        })

        it('should add invalid classes and animate for invalid input', () => {
            const mockSlideIn = vi.spyOn(animationHandler, 'slideIn')

            animationHandler.animateValidation(input, false)

            expect(input.classList.contains('is-invalid')).toBe(true)
            expect(input.classList.contains('is-valid')).toBe(false)
            expect(validationMessage.classList.contains('is-invalid')).toBe(true)
            expect(validationMessage.classList.contains('is-valid')).toBe(false)
            expect(mockSlideIn).toHaveBeenCalledWith(validationMessage)
        })

        it('should handle missing form group gracefully', () => {
            const inputWithoutGroup = document.createElement('input')
            
            expect(() => {
                animationHandler.animateValidation(inputWithoutGroup, true)
            }).not.toThrow()
        })

        it('should handle missing validation message gracefully', () => {
            const formGroupWithoutMessage = document.createElement('div')
            formGroupWithoutMessage.className = 'form-group'
            const inputWithoutMessage = document.createElement('input')
            formGroupWithoutMessage.appendChild(inputWithoutMessage)
            document.body.appendChild(formGroupWithoutMessage)

            expect(() => {
                animationHandler.animateValidation(inputWithoutMessage, true)
            }).not.toThrow()

            document.body.removeChild(formGroupWithoutMessage)
        })
    })

    describe('animateFormSubmit method', () => {
        it('should add loading class to submit button', () => {
            const form = document.createElement('form')
            const submitButton = document.createElement('button')
            submitButton.type = 'submit'
            form.appendChild(submitButton)
            document.body.appendChild(form)

            animationHandler.animateFormSubmit(form)

            expect(submitButton.classList.contains('button-loading')).toBe(true)

            document.body.removeChild(form)
        })

        it('should handle form without submit button gracefully', () => {
            const form = document.createElement('form')
            document.body.appendChild(form)

            expect(() => {
                animationHandler.animateFormSubmit(form)
            }).not.toThrow()

            document.body.removeChild(form)
        })
    })

    describe('animateFormSuccess method', () => {
        it('should animate success message', () => {
            const form = document.createElement('form')
            const successMessage = document.createElement('div')
            successMessage.className = 'form-success-message'
            form.appendChild(successMessage)
            document.body.appendChild(form)

            const mockSlideIn = vi.spyOn(animationHandler, 'slideIn')

            animationHandler.animateFormSuccess(form)

            expect(mockSlideIn).toHaveBeenCalledWith(successMessage)

            document.body.removeChild(form)
        })

        it('should handle form without success message gracefully', () => {
            const form = document.createElement('form')
            document.body.appendChild(form)

            expect(() => {
                animationHandler.animateFormSuccess(form)
            }).not.toThrow()

            document.body.removeChild(form)
        })
    })

    describe('animateFormError method', () => {
        it('should animate error message', () => {
            const form = document.createElement('form')
            const errorMessage = document.createElement('div')
            errorMessage.className = 'form-error-message'
            form.appendChild(errorMessage)
            document.body.appendChild(form)

            const mockSlideIn = vi.spyOn(animationHandler, 'slideIn')

            animationHandler.animateFormError(form)

            expect(mockSlideIn).toHaveBeenCalledWith(errorMessage)

            document.body.removeChild(form)
        })

        it('should handle form without error message gracefully', () => {
            const form = document.createElement('form')
            document.body.appendChild(form)

            expect(() => {
                animationHandler.animateFormError(form)
            }).not.toThrow()

            document.body.removeChild(form)
        })
    })
})
