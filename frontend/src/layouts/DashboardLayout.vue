<template>
  <div class="min-h-screen bg-slate-50">
    <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="lg:pl-64 flex flex-col min-h-screen">
      <AppTopbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 px-4 sm:px-6 py-6">
        <RouterView />
      </main>
    </div>
  </div>
  <ToastContainer />
</template>

<script setup>
import { ref } from 'vue'
import { RouterView } from 'vue-router'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'

const sidebarOpen = ref(false)
</script>
