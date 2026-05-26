<template>
  <div class="card p-5 flex items-start gap-4">
    <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0', iconBg]">
      <slot name="icon">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
      </slot>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
      <p class="text-2xl font-bold text-slate-900 mt-0.5 leading-tight">{{ value }}</p>
      <div v-if="change !== null && change !== undefined" class="flex items-center gap-1 mt-1">
        <span :class="['text-xs font-semibold', change >= 0 ? 'text-emerald-600' : 'text-red-500']">
          {{ change >= 0 ? '↑' : '↓' }} {{ Math.abs(change) }}%
        </span>
        <span class="text-xs text-slate-400">vs yesterday</span>
      </div>
      <p v-else-if="sub" class="text-xs text-slate-400 mt-1">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  change: { type: Number, default: null },
  sub:    { type: String, default: '' },
  color:  { type: String, default: 'primary' },
})

const bgMap = {
  primary: 'bg-primary-50 text-primary-600',
  emerald: 'bg-emerald-50 text-emerald-600',
  amber:   'bg-amber-50 text-amber-600',
  rose:    'bg-rose-50 text-rose-600',
  violet:  'bg-violet-50 text-violet-600',
  slate:   'bg-slate-100 text-slate-500',
}

const iconBg = bgMap[props.color] ?? bgMap.primary
</script>
