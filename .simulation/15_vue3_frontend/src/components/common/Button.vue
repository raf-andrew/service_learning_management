<template>
  <button
    :class="[
      'base-button',
      `base-button--${variant}`,
      `base-button--${size}`,
      { 'base-button--loading': loading },
      { 'base-button--disabled': disabled }
    ]"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <span v-if="loading" class="base-button__loader"></span>
    <span v-else class="base-button__content">
      <slot></slot>
    </span>
  </button>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseButton',
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: (value: string) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value)
    },
    size: {
      type: String,
      default: 'medium',
      validator: (value: string) => ['small', 'medium', 'large'].includes(value)
    },
    loading: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  setup(props, { emit }) {
    const handleClick = (event: MouseEvent) => {
      if (!props.disabled && !props.loading) {
        emit('click', event)
      }
    }

    return {
      handleClick
    }
  }
})
</script>

<style scoped>
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border: none;
  outline: none;
}

.base-button--primary {
  background-color: #4f46e5;
  color: white;
}

.base-button--primary:hover:not(:disabled) {
  background-color: #4338ca;
}

.base-button--secondary {
  background-color: #6b7280;
  color: white;
}

.base-button--secondary:hover:not(:disabled) {
  background-color: #4b5563;
}

.base-button--success {
  background-color: #10b981;
  color: white;
}

.base-button--success:hover:not(:disabled) {
  background-color: #059669;
}

.base-button--danger {
  background-color: #ef4444;
  color: white;
}

.base-button--danger:hover:not(:disabled) {
  background-color: #dc2626;
}

.base-button--warning {
  background-color: #f59e0b;
  color: white;
}

.base-button--warning:hover:not(:disabled) {
  background-color: #d97706;
}

.base-button--info {
  background-color: #3b82f6;
  color: white;
}

.base-button--info:hover:not(:disabled) {
  background-color: #2563eb;
}

.base-button--small {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.base-button--medium {
  padding: 0.5rem 1rem;
  font-size: 1rem;
}

.base-button--large {
  padding: 0.75rem 1.5rem;
  font-size: 1.125rem;
}

.base-button--loading {
  cursor: wait;
}

.base-button--disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.base-button__loader {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style> 