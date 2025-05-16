import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BaseButton from '@/components/common/Button.vue'

describe('BaseButton', () => {
  it('renders properly with default props', () => {
    const wrapper = mount(BaseButton, {
      slots: {
        default: 'Click me'
      }
    })

    expect(wrapper.text()).toBe('Click me')
    expect(wrapper.classes()).toContain('base-button--primary')
    expect(wrapper.classes()).toContain('base-button--medium')
  })

  it('renders with different variants', () => {
    const variants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info']
    
    variants.forEach(variant => {
      const wrapper = mount(BaseButton, {
        props: { variant },
        slots: { default: 'Click me' }
      })

      expect(wrapper.classes()).toContain(`base-button--${variant}`)
    })
  })

  it('renders with different sizes', () => {
    const sizes = ['small', 'medium', 'large']
    
    sizes.forEach(size => {
      const wrapper = mount(BaseButton, {
        props: { size },
        slots: { default: 'Click me' }
      })

      expect(wrapper.classes()).toContain(`base-button--${size}`)
    })
  })

  it('shows loading state', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
      slots: { default: 'Click me' }
    })

    expect(wrapper.classes()).toContain('base-button--loading')
    expect(wrapper.find('.base-button__loader').exists()).toBe(true)
    expect(wrapper.find('.base-button__content').exists()).toBe(false)
  })

  it('shows disabled state', () => {
    const wrapper = mount(BaseButton, {
      props: { disabled: true },
      slots: { default: 'Click me' }
    })

    expect(wrapper.classes()).toContain('base-button--disabled')
    expect(wrapper.attributes('disabled')).toBeDefined()
  })

  it('emits click event when clicked', async () => {
    const wrapper = mount(BaseButton, {
      slots: { default: 'Click me' }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })

  it('does not emit click event when disabled', async () => {
    const wrapper = mount(BaseButton, {
      props: { disabled: true },
      slots: { default: 'Click me' }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('does not emit click event when loading', async () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
      slots: { default: 'Click me' }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('validates variant prop', () => {
    const consoleSpy = vi.spyOn(console, 'warn')
    
    mount(BaseButton, {
      props: { variant: 'invalid' },
      slots: { default: 'Click me' }
    })

    expect(consoleSpy).toHaveBeenCalled()
    consoleSpy.mockRestore()
  })

  it('validates size prop', () => {
    const consoleSpy = vi.spyOn(console, 'warn')
    
    mount(BaseButton, {
      props: { size: 'invalid' },
      slots: { default: 'Click me' }
    })

    expect(consoleSpy).toHaveBeenCalled()
    consoleSpy.mockRestore()
  })
}) 