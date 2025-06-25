/**
 * @file animations-config.test.ts
 * @description Animation configuration tests for wireframe functionality
 * @tags animations, config, frontend, wireframe, vitest, dom-manipulation
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { setupTestEnvironment } from '../helpers/testUtils'
import { MockAnimationHandler, animationTestData } from '../helpers/animationHelpers'

describe('Animation Configuration', () => {
  let animationHandler: MockAnimationHandler

  beforeEach(() => {
    setupTestEnvironment()
    animationHandler = new MockAnimationHandler()
  })

  describe('Animation Definitions', () => {
    it('should have all required animations defined', () => {
      animationTestData.validAnimations.forEach(animationName => {
        expect(animationHandler.animations).toHaveProperty(animationName)
      })
    })

    it('should have correct fadeIn animation configuration', () => {
      const fadeIn = animationHandler.animations.fadeIn
      const expected = animationTestData.animationConfigs.fadeIn
      
      expect(fadeIn.keyframes).toHaveLength(2)
      expect(fadeIn.keyframes[0]).toEqual({ opacity: 0 })
      expect(fadeIn.keyframes[1]).toEqual({ opacity: 1 })
      expect(fadeIn.options.duration).toBe(expected.duration)
      expect(fadeIn.options.easing).toBe(expected.easing)
    })

    it('should have correct fadeOut animation configuration', () => {
      const fadeOut = animationHandler.animations.fadeOut
      const expected = animationTestData.animationConfigs.fadeOut
      
      expect(fadeOut.keyframes).toHaveLength(2)
      expect(fadeOut.keyframes[0]).toEqual({ opacity: 1 })
      expect(fadeOut.keyframes[1]).toEqual({ opacity: 0 })
      expect(fadeOut.options.duration).toBe(expected.duration)
      expect(fadeOut.options.easing).toBe(expected.easing)
    })

    it('should have correct slideIn animation configuration', () => {
      const slideIn = animationHandler.animations.slideIn
      const expected = animationTestData.animationConfigs.slideIn
      
      expect(slideIn.keyframes).toHaveLength(2)
      expect(slideIn.keyframes[0]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
      expect(slideIn.keyframes[1]).toEqual({ transform: 'translateY(0)', opacity: 1 })
      expect(slideIn.options.duration).toBe(expected.duration)
      expect(slideIn.options.easing).toBe(expected.easing)
    })

    it('should have correct slideOut animation configuration', () => {
      const slideOut = animationHandler.animations.slideOut
      const expected = animationTestData.animationConfigs.slideOut
      
      expect(slideOut.keyframes).toHaveLength(2)
      expect(slideOut.keyframes[0]).toEqual({ transform: 'translateY(0)', opacity: 1 })
      expect(slideOut.keyframes[1]).toEqual({ transform: 'translateY(20px)', opacity: 0 })
      expect(slideOut.options.duration).toBe(expected.duration)
      expect(slideOut.options.easing).toBe(expected.easing)
    })
  })

  describe('Animation Structure', () => {
    it('should have consistent animation structure', () => {
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        expect(animation).toHaveProperty('keyframes')
        expect(animation).toHaveProperty('options')
        expect(animation.options).toHaveProperty('duration')
        expect(animation.options).toHaveProperty('easing')
        
        expect(Array.isArray(animation.keyframes)).toBe(true)
        expect(animation.keyframes.length).toBeGreaterThan(0)
        expect(typeof animation.options.duration).toBe('number')
        expect(typeof animation.options.easing).toBe('string')
      })
    })

    it('should have valid keyframe structure', () => {
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        animation.keyframes.forEach((keyframe: any, index: number) => {
          expect(typeof keyframe).toBe('object')
          expect(keyframe).not.toBeNull()
          
          // Each keyframe should have at least one property
          const properties = Object.keys(keyframe)
          expect(properties.length).toBeGreaterThan(0)
          
          // Properties should be valid CSS properties
          properties.forEach(prop => {
            const value = keyframe[prop]
            expect(typeof value === 'string' || typeof value === 'number').toBe(true)
          })
        })
      })
    })

    it('should have valid animation options', () => {
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        expect(animation.options.duration).toBeGreaterThan(0)
        expect(animation.options.easing).toMatch(/^(ease|ease-in|ease-out|ease-in-out|linear)$/)
      })
    })
  })

  describe('Animation Performance', () => {
    it('should have reasonable animation durations', () => {
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        // Durations should be between 100ms and 1000ms for good UX
        expect(animation.options.duration).toBeGreaterThanOrEqual(100)
        expect(animation.options.duration).toBeLessThanOrEqual(1000)
      })
    })

    it('should use appropriate easing functions', () => {
      const fadeAnimations = ['fadeIn', 'fadeOut']
      const slideAnimations = ['slideIn', 'slideOut']
      
      // Fade animations should use ease-in/ease-out
      fadeAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        expect(['ease-in', 'ease-out']).toContain(animation.options.easing)
      })
      
      // Slide animations should use ease-in/ease-out
      slideAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        expect(['ease-in', 'ease-out']).toContain(animation.options.easing)
      })
    })
  })

  describe('Animation Accessibility', () => {
    it('should support reduced motion preferences', () => {
      // In a real implementation, this would check for prefers-reduced-motion
      // For now, we just ensure animations are not too jarring
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        // Animations should not have extreme transforms
        animation.keyframes.forEach((keyframe: any) => {
          if (keyframe.transform) {
            // Check that transforms are reasonable
            expect(keyframe.transform).not.toMatch(/translateY\([0-9]{3,}px\)/)
            expect(keyframe.transform).not.toMatch(/scale\([0-9]\.[0-9]{2,}\)/)
          }
        })
      })
    })

    it('should have smooth transitions', () => {
      animationTestData.validAnimations.forEach(animationName => {
        const animation = animationHandler.animations[animationName]
        
        // Animations should have at least 2 keyframes for smooth transitions
        expect(animation.keyframes.length).toBeGreaterThanOrEqual(2)
        
        // Keyframes should have consistent property sets
        const firstKeyframe = animation.keyframes[0]
        const lastKeyframe = animation.keyframes[animation.keyframes.length - 1]
        
        const firstProps = Object.keys(firstKeyframe)
        const lastProps = Object.keys(lastKeyframe)
        
        // Should have some common properties
        expect(firstProps.some(prop => lastProps.includes(prop))).toBe(true)
      })
    })
  })
}) 