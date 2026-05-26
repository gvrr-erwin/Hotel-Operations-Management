<template>
  <div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
      <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25" />
      </svg>
    </div>
    <p class="text-sm font-semibold text-slate-600">{{ title }}</p>
    <p class="text-xs text-slate-400 mt-1 max-w-xs">{{ description }}</p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, default: 'No data found' },
  description: { type: String, default: 'Nothing to display yet.' },
})
</script>
