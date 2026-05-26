<template>
  <form @submit.prevent="handleLogin" class="flex flex-col gap-5">
    <div>
      <h2 class="text-lg font-bold text-slate-800">Sign in to your account</h2>
      <p class="text-sm text-slate-500 mt-1">Enter your credentials to continue</p>
    </div>

    <AppAlert :message="errorMsg" type="error" />

    <div class="flex flex-col gap-4">
      <div>
        <label class="label">Username</label>
        <input
          v-model="form.username"
          type="text"
          class="input"
          placeholder="Enter your username"
          autocomplete="username"
          required
        />
      </div>
      <div>
        <label class="label">Password</label>
        <input
          v-model="form.password"
          type="password"
          class="input"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-full" :disabled="auth.loading">
      <AppSpinner v-if="auth.loading" size="sm" color="white" />
      {{ auth.loading ? 'Signing in...' : 'Sign In' }}
    </button>

    <!-- Demo credentials -->
    <div class="border-t border-slate-100 pt-4">
      <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Demo Accounts</p>
      <div class="grid grid-cols-2 gap-1.5">
        <button
          v-for="demo in demoAccounts"
          :key="demo.username"
          type="button"
          @click="fillDemo(demo)"
          class="text-left px-3 py-2 rounded-lg border border-slate-100 hover:border-primary-200 hover:bg-primary-50 transition-colors"
        >
          <span class="block text-xs font-semibold text-slate-700">{{ demo.label }}</span>
          <span class="block text-[10px] text-slate-400 font-mono">{{ demo.username }}</span>
        </button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'

const auth     = useAuthStore()
const router   = useRouter()
const errorMsg = ref('')

const form = reactive({ username: '', password: '' })

const demoAccounts = [
  { label: 'Admin',    username: 'admin',      password: 'admin123' },
  { label: 'Gen. Mgr',username: 'gm',          password: 'gm123' },
  { label: 'Asst. Mgr',username: 'assistant',  password: 'assistant123' },
  { label: 'Employee', username: 'employee1',  password: 'emp123' },
]

function fillDemo(demo) {
  form.username = demo.username
  form.password = demo.password
}

async function handleLogin() {
  errorMsg.value = ''
  const result = await auth.login(form)
  if (result.ok) {
    router.push(auth.defaultHome())
  } else {
    errorMsg.value = result.message
  }
}
</script>
