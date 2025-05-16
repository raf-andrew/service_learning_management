import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BaseModal from '@/components/common/Modal.vue'

describe('BaseModal', () => {
  it('renders properly with default props', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true
      }
    })

    expect(wrapper.find('.fixed').exists()).toBe(true)
    expect(wrapper.find('h3').exists()).toBe(false)
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('renders with title', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true,
        title: 'Modal Title'
      }
    })

    expect(wrapper.find('h3').text()).toBe('Modal Title')
  })

  it('renders with different sizes', () => {
    const sizes = ['sm', 'md', 'lg', 'xl', '2xl']
    
    sizes.forEach(size => {
      const wrapper = mount(BaseModal, {
        props: {
          modelValue: true,
          size
        }
      })

      expect(wrapper.find('.relative').classes()).toContain(`max-w-${size}`)
    })
  })

  it('validates size prop', () => {
    const consoleSpy = vi.spyOn(console, 'warn')
    
    mount(BaseModal, {
      props: {
        modelValue: true,
        size: 'invalid'
      }
    })

    expect(consoleSpy).toHaveBeenCalled()
    consoleSpy.mockRestore()
  })

  it('hides close button when showClose is false', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true,
        showClose: false
      }
    })

    expect(wrapper.find('button').exists()).toBe(false)
  })

  it('emits update:modelValue and close events when close button is clicked', async () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true
      }
    })

    await wrapper.find('button').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits update:modelValue and close events when backdrop is clicked and closeOnBackdrop is true', async () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true,
        closeOnBackdrop: true
      }
    })

    await wrapper.find('.fixed').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('does not emit events when backdrop is clicked and closeOnBackdrop is false', async () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true,
        closeOnBackdrop: false
      }
    })

    await wrapper.find('.fixed').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('renders footer slot when provided', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true
      },
      slots: {
        footer: '<div class="footer-content">Footer Content</div>'
      }
    })

    expect(wrapper.find('.footer-content').exists()).toBe(true)
    expect(wrapper.find('.footer-content').text()).toBe('Footer Content')
  })

  it('does not render footer when no footer slot is provided', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true
      }
    })

    expect(wrapper.find('.bg-gray-50').exists()).toBe(false)
  })

  it('renders default slot content', () => {
    const wrapper = mount(BaseModal, {
      props: {
        modelValue: true
      },
      slots: {
        default: '<div class="modal-content">Modal Content</div>'
      }
    })

    expect(wrapper.find('.modal-content').exists()).toBe(true)
    expect(wrapper.find('.modal-content').text()).toBe('Modal Content')
  })
}) 