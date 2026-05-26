<template>
  <span :class="['badge', variantClass]">
    <slot />
  </span>
</template>

<script setup>
const props = defineProps({
  variant: { type: String, default: 'slate' },
})

const variants = {
  slate:   'bg-slate-100 text-slate-600',
  primary: 'bg-primary-100 text-primary-700',
  emerald: 'bg-emerald-100 text-emerald-700',
  amber:   'bg-amber-100 text-amber-700',
  red:     'bg-red-100 text-red-700',
  rose:    'bg-rose-100 text-rose-700',
  violet:  'bg-violet-100 text-violet-700',
  sky:     'bg-sky-100 text-sky-700',
  indigo:  'bg-indigo-100 text-indigo-700',
}

const variantClass = variants[props.variant] ?? variants.slate
</script>
