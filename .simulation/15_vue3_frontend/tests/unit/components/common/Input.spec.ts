import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BaseInput from '@/components/common/Input.vue'

describe('BaseInput', () => {
  it('renders properly with default props', () => {
    const wrapper = mount(BaseInput)

    expect(wrapper.find('input').exists()).toBe(true)
    expect(wrapper.find('label').exists()).toBe(false)
    expect(wrapper.find('.base-input__error').exists()).toBe(false)
    expect(wrapper.find('.base-input__hint').exists()).toBe(false)
  })

  it('renders with label', () => {
    const wrapper = mount(BaseInput, {
      props: {
        label: 'Username'
      }
    })

    expect(wrapper.find('label').text()).toBe('Username')
  })

  it('renders with error message', () => {
    const wrapper = mount(BaseInput, {
      props: {
        error: 'This field is required'
      }
    })

    expect(wrapper.find('.base-input__error').text()).toBe('This field is required')
    expect(wrapper.classes()).toContain('base-input--error')
  })

  it('renders with hint message', () => {
    const wrapper = mount(BaseInput, {
      props: {
        hint: 'Enter your username'
      }
    })

    expect(wrapper.find('.base-input__hint').text()).toBe('Enter your username')
  })

  it('renders with different input types', () => {
    const types = ['text', 'number', 'email', 'password', 'tel', 'url']
    
    types.forEach(type => {
      const wrapper = mount(BaseInput, {
        props: { type }
      })

      expect(wrapper.find('input').attributes('type')).toBe(type)
    })
  })

  it('validates input type prop', () => {
    const consoleSpy = vi.spyOn(console, 'warn')
    
    mount(BaseInput, {
      props: { type: 'invalid' }
    })

    expect(consoleSpy).toHaveBeenCalled()
    consoleSpy.mockRestore()
  })

  it('emits update:modelValue event on input', async () => {
    const wrapper = mount(BaseInput)
    const input = wrapper.find('input')
    
    await input.setValue('test value')
    
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['test value'])
  })

  it('emits blur event on blur', async () => {
    const wrapper = mount(BaseInput)
    const input = wrapper.find('input')
    
    await input.trigger('blur')
    
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('applies disabled state', () => {
    const wrapper = mount(BaseInput, {
      props: { disabled: true }
    })

    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
  })

  it('applies readonly state', () => {
    const wrapper = mount(BaseInput, {
      props: { readonly: true }
    })

    expect(wrapper.find('input').attributes('readonly')).toBeDefined()
  })

  it('applies required state', () => {
    const wrapper = mount(BaseInput, {
      props: { required: true }
    })

    expect(wrapper.find('input').attributes('required')).toBeDefined()
  })

  it('applies min and max attributes for number type', () => {
    const wrapper = mount(BaseInput, {
      props: {
        type: 'number',
        min: 0,
        max: 100
      }
    })

    expect(wrapper.find('input').attributes('min')).toBe('0')
    expect(wrapper.find('input').attributes('max')).toBe('100')
  })

  it('applies step attribute for number type', () => {
    const wrapper = mount(BaseInput, {
      props: {
        type: 'number',
        step: 0.1
      }
    })

    expect(wrapper.find('input').attributes('step')).toBe('0.1')
  })

  it('applies pattern attribute', () => {
    const wrapper = mount(BaseInput, {
      props: {
        pattern: '[A-Za-z]{3}'
      }
    })

    expect(wrapper.find('input').attributes('pattern')).toBe('[A-Za-z]{3}')
  })
}) 