<template>
  <AppModal :modelValue="modelValue" :title="title" @update:modelValue="$emit('update:modelValue', $event)">
    <p class="text-sm text-slate-600 mb-1">{{ message }}</p>
    <p v-if="hint" class="text-xs text-slate-400 mb-5">{{ hint }}</p>
    <div v-else class="mb-5" />
    <div class="flex justify-end gap-2">
      <button class="btn btn-secondary" @click="$emit('update:modelValue', false)">Cancel</button>
      <button :class="['btn', danger ? 'btn-danger' : 'btn-primary']" @click="$emit('confirm')">
        {{ confirmLabel }}
      </button>
    </div>
  </AppModal>
</template>

<script setup>
import AppModal from './AppModal.vue'

defineProps({
  modelValue:   { type: Boolean, required: true },
  title:        { type: String, default: 'Confirm' },
  message:      { type: String, default: 'Are you sure?' },
  hint:         { type: String, default: '' },
  confirmLabel: { type: String, default: 'Confirm' },
  danger:       { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'confirm'])
</script>
