<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <div class="flex min-h-screen items-center justify-center p-4">
          <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            aria-hidden="true"
          ></div>

          <div
            class="relative transform overflow-hidden rounded-lg bg-white shadow-xl transition-all"
            :class="[`max-w-${size}`]"
            @click.stop
          >
            <div class="px-4 pt-5 pb-4 sm:p-6">
              <div class="flex items-start justify-between">
                <h3
                  v-if="title"
                  class="text-lg font-medium leading-6 text-gray-900"
                >
                  {{ title }}
                </h3>
                <button
                  v-if="showClose"
                  type="button"
                  class="ml-3 inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  @click="handleClose"
                >
                  <span class="sr-only">Close</span>
                  <svg
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>

              <div class="mt-2">
                <slot></slot>
              </div>
            </div>

            <div
              v-if="$slots.footer"
              class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
            >
              <slot name="footer"></slot>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseModal',
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'md',
      validator: (value: string) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(value)
    },
    showClose: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:modelValue', 'close'],
  setup(props, { emit }) {
    const handleClose = () => {
      emit('update:modelValue', false)
      emit('close')
    }

    const handleBackdropClick = () => {
      if (props.closeOnBackdrop) {
        handleClose()
      }
    }

    return {
      handleClose,
      handleBackdropClick
    }
  }
})
</script>

<style scoped>
.max-w-sm {
  max-width: 24rem;
}

.max-w-md {
  max-width: 28rem;
}

.max-w-lg {
  max-width: 32rem;
}

.max-w-xl {
  max-width: 36rem;
}

.max-w-2xl {
  max-width: 42rem;
}
</style> 