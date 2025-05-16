<template>
  <div class="base-input" :class="{ 'base-input--error': error }">
    <label v-if="label" :for="id" class="base-input__label">{{ label }}</label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :min="min"
      :max="max"
      :step="step"
      :pattern="pattern"
      class="base-input__field"
      @input="handleInput"
      @blur="handleBlur"
    />
    <span v-if="error" class="base-input__error">{{ error }}</span>
    <span v-if="hint" class="base-input__hint">{{ hint }}</span>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseInput',
  props: {
    modelValue: {
      type: [String, Number],
      default: ''
    },
    label: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'text',
      validator: (value: string) => ['text', 'number', 'email', 'password', 'tel', 'url'].includes(value)
    },
    placeholder: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    },
    readonly: {
      type: Boolean,
      default: false
    },
    required: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: ''
    },
    min: {
      type: [String, Number],
      default: undefined
    },
    max: {
      type: [String, Number],
      default: undefined
    },
    step: {
      type: [String, Number],
      default: undefined
    },
    pattern: {
      type: String,
      default: undefined
    }
  },
  emits: ['update:modelValue', 'blur'],
  setup(props, { emit }) {
    const id = `input-${Math.random().toString(36).substr(2, 9)}`

    const handleInput = (event: Event) => {
      const target = event.target as HTMLInputElement
      emit('update:modelValue', target.value)
    }

    const handleBlur = (event: Event) => {
      emit('blur', event)
    }

    return {
      id,
      handleInput,
      handleBlur
    }
  }
})
</script>

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.base-input__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.base-input__field {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #1f2937;
  background-color: white;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.base-input__field:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.base-input__field:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.base-input__field:read-only {
  background-color: #f9fafb;
}

.base-input--error .base-input__field {
  border-color: #ef4444;
}

.base-input--error .base-input__field:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.base-input__error {
  font-size: 0.75rem;
  color: #ef4444;
}

.base-input__hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style> 