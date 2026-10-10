<template>
  <!-- Over the home page's full-screen photo, the header is transparent. -->
  <header class="site-header" :class="{ 'site-header--overlay': route.name === 'home' }">
    <router-link to="/" class="brand">Invitations</router-link>
    <nav v-if="ready" class="site-header__nav">
      <template v-if="user">
        <router-link to="/invitations">My invitations</router-link>
        <img v-if="user.avatar_url" :src="user.avatar_url" alt="" width="32" height="32" class="avatar" referrerpolicy="no-referrer" />
        <button type="button" class="link-button" :disabled="signingOut" @click="signOut">Sign out</button>
      </template>
      <a v-else :href="signInUrl(route.fullPath)">Sign in</a>
    </nav>
  </header>

  <p v-if="signInFailed" class="notice notice--top" role="alert">
    Sign-in didn't finish. Please try again.
  </p>

  <router-view />
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from './composables/useAuth'

const route = useRoute()
const router = useRouter()
const { user, ready, fetchMe, signInUrl, logout } = useAuth()

const signInFailed = ref(false)
const signingOut = ref(false)

fetchMe()

// Back from Google with ?signin=failed: say so once, and tidy the address.
watch(() => route.query.signin, (value) => {
  if (value !== 'failed') return
  signInFailed.value = true
  const { signin: _done, ...query } = route.query
  router.replace({ query })
})

async function signOut() {
  signingOut.value = true
  try {
    await logout()
    router.push('/')
  } finally {
    signingOut.value = false
  }
}
</script>
