<template>
  <main class="login">
    <form class="card stack" @submit.prevent="submit">
      <h1>Invitations admin</h1>

      <label class="field">
        Email
        <input v-model="email" type="email" autocomplete="username" required autofocus />
      </label>

      <label class="field">
        Password
        <span class="password">
          <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required />
          <button type="button" class="link-button" :aria-pressed="String(showPassword)" @click="showPassword = !showPassword">
            {{ showPassword ? 'Hide' : 'Show' }}
          </button>
        </span>
      </label>

      <p v-if="error" class="notice" role="alert">{{ error }}</p>

      <button type="submit" class="button" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
    </form>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminAuth } from '../composables/useAdminAuth'

const route = useRoute()
const router = useRouter()
const { login } = useAdminAuth()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await login(email.value, password.value)
    const redirect = route.query.redirect
    router.replace(typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/')
  } catch (e) {
    error.value = e.messageFor?.('email') ?? "Couldn't sign in. Please try again."
    password.value = ''
  } finally {
    busy.value = false
  }
}
</script>

<style scoped>
.login { display: grid; place-items: center; min-height: 100vh; padding: 16px; }
.login form { width: min(100%, 380px); }
.login h1 { font-size: 1.3rem; margin: 0; }
.password { display: flex; align-items: center; gap: 10px; }
.password input { flex: 1; }
</style>
