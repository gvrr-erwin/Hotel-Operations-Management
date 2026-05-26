<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-primary-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-primary-600 flex items-center justify-center shadow-lg mb-4">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Hotel Operations</h1>
        <p class="text-sm text-slate-400 mt-1">Management System</p>
      </div>
      <!-- Card -->
      <div class="card p-8">
        <RouterView />
      </div>
      <p class="text-center text-xs text-slate-500 mt-6">© 2026 Hotel Operations Management</p>
    </div>
  </div>
  <ToastContainer />
</template>

<script setup>
import { RouterView } from 'vue-router'
import ToastContainer from '@/components/ui/ToastContainer.vue'
</script>
