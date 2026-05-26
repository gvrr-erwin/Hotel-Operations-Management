<template>
  <Teleport to="body">
    <Transition name="modal">
      <div class="fixed inset-0 z-50 overflow-y-auto" v-if="modelValue">
        <div class="flex min-h-full items-end sm:items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="$emit('update:modelValue', false)" />
          <div :class="['relative bg-white rounded-2xl shadow-2xl w-full', sizeClass]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h3 class="text-base font-semibold text-slate-800">{{ title }}</h3>
              <button
                @click="$emit('update:modelValue', false)"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="px-6 py-5">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, required: true },
  title:      { type: String, default: '' },
  size:       { type: String, default: 'md' },
})
defineEmits(['update:modelValue'])

const sizes = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }
const sizeClass = sizes['md']
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
