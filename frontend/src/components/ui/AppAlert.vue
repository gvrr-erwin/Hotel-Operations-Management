<template>
  <div v-if="message" :class="['flex items-start gap-3 rounded-xl px-4 py-3 text-sm', variantClass]">
    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path v-if="type === 'success'" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
    <span>{{ message }}</span>
  </div>
</template>

<script setup>
const props = defineProps({
  message: { type: String, default: '' },
  type:    { type: String, default: 'error' },
})

const variants = {
  error:   'bg-red-50 text-red-700 border border-red-200',
  success: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  warning: 'bg-amber-50 text-amber-700 border border-amber-200',
  info:    'bg-sky-50 text-sky-700 border border-sky-200',
}

const variantClass = variants[props.type] ?? variants.error
</script>
