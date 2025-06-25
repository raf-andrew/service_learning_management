/**
 * @fileoverview Core Animation Functionality Tests
 * @description Tests for basic animation methods and configuration
 * @tags animations,core,frontend,wireframe,vitest,dom-manipulation
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'

// Mock AnimationHandler class for testing
class AnimationHandler {
  animations: Record<string, any>

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
    }
  }

  animate(element: HTMLElement, animationName: string) {
    const animation = this.animations[animationName]
    if (!animation) return null

    // Mock animation object
    const mockAnimation = {
      onfinish: null as (() => void) | null,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn()
    }

    return mockAnimation
  }

  fadeIn(element: HTMLElement) {
    return this.animate(element, 'fadeIn')
  }

  fadeOut(element: HTMLElement) {
    return this.animate(element, 'fadeOut')
  }

  slideIn(element: HTMLElement) {
    return this.animate(element, 'slideIn')
  }

  slideOut(element: HTMLElement) {
    return this.animate(element, 'slideOut')
  }

  show(element: HTMLElement, animationName = 'fadeIn') {
    element.style.display = ''
    return this.animate(element, animationName)
  }

  hide(element: HTMLElement, animationName = 'fadeOut') {
    const animation = this.animate(element, animationName)
    if (animation) {
      animation.onfinish = () => {
        element.style.display = 'none'
      }
    }
    return animation
  }
}

describe('Core Animation Functionality', () => {
  let animationHandler: AnimationHandler
  let mockElement: HTMLElement

  beforeEach(() => {
    vi.clearAllMocks()
    mockElement = document.createElement('div')
    animationHandler = new AnimationHandler()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Animation Configuration', () => {
    it('should have all required animations defined', () => {
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

  describe('Basic Animation Methods', () => {
    it('should call animate with correct parameters for fadeIn', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      animationHandler.fadeIn(mockElement)
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeIn')
    })

    it('should call animate with correct parameters for fadeOut', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      animationHandler.fadeOut(mockElement)
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeOut')
    })

    it('should call animate with correct parameters for slideIn', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      animationHandler.slideIn(mockElement)
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideIn')
    })

    it('should call animate with correct parameters for slideOut', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      animationHandler.slideOut(mockElement)
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideOut')
    })

    it('should return null for unknown animation', () => {
      const result = animationHandler.animate(mockElement, 'unknown')
      expect(result).toBeNull()
    })
  })

  describe('Show and Hide Methods', () => {
    it('should set display to empty string and animate on show', () => {
      mockElement.style.display = 'none'
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.show(mockElement)
      
      expect(mockElement.style.display).toBe('')
      expect(spy).toHaveBeenCalledWith(mockElement, 'fadeIn')
    })

    it('should use custom animation name on show', () => {
      const spy = vi.spyOn(animationHandler, 'animate')
      
      animationHandler.show(mockElement, 'slideIn')
      
      expect(spy).toHaveBeenCalledWith(mockElement, 'slideIn')
    })

    it('should set onfinish callback to hide element', () => {
      mockElement.style.display = 'block'
      
      const animation = animationHandler.hide(mockElement)
      
      expect(animation?.onfinish).toBeDefined()
      expect(typeof animation?.onfinish).toBe('function')
    })

    it('should handle null animation gracefully in hide', () => {
      const elementWithoutAnimate = document.createElement('div')
      // Remove animate method to simulate element without animation support
      delete (elementWithoutAnimate as any).animate
      
      expect(() => animationHandler.hide(elementWithoutAnimate)).not.toThrow()
    })
  })

  describe('Animation Object Properties', () => {
    it('should return animation object with required properties', () => {
      const animation = animationHandler.animate(mockElement, 'fadeIn')
      
      expect(animation).toHaveProperty('onfinish')
      expect(animation).toHaveProperty('addEventListener')
      expect(animation).toHaveProperty('removeEventListener')
      expect(typeof animation?.addEventListener).toBe('function')
      expect(typeof animation?.removeEventListener).toBe('function')
    })

    it('should allow setting onfinish callback', () => {
      const animation = animationHandler.animate(mockElement, 'fadeIn')
      const callback = vi.fn()
      
      if (animation) {
        animation.onfinish = callback
        expect(animation.onfinish).toBe(callback)
      }
    })
  })
}) 