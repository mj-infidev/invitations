<template>
  <!-- Step 3: just enough to sign in and out. The landing page and builder come in Step 4. -->
  <main class="home">
    <h1>Invitations</h1>

    <p v-if="signInFailed" class="notice" role="alert">
      Sign-in didn't finish. Please try again.
    </p>

    <p v-if="!ready" class="muted">Loading…</p>

    <div v-else-if="user" class="account">
      <img v-if="user.avatar_url" :src="user.avatar_url" alt="" width="48" height="48" referrerpolicy="no-referrer" />
      <div>
        <p class="name">{{ user.name }}</p>
        <p v-if="user.email" class="muted">{{ user.email }}</p>
      </div>
      <button type="button" :disabled="signingOut" @click="signOut">Sign out</button>
    </div>

    <a v-else class="google" :href="signInUrl(route.fullPath)">Sign in with Google</a>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const router = useRouter()
const { user, ready, fetchMe, signInUrl, logout } = useAuth()

const signInFailed = ref(false)
const signingOut = ref(false)

onMounted(() => {
  fetchMe()

  // Back from Google with ?signin=failed: say so once, and tidy the address.
  if (route.query.signin === 'failed') {
    signInFailed.value = true
    const { signin, ...query } = route.query
    router.replace({ query })
  }
})

async function signOut() {
  signingOut.value = true
  try {
    await logout()
  } finally {
    signingOut.value = false
  }
}
</script>

<style scoped>
.home {
  max-width: 28rem;
  margin: 15vh auto 0;
  padding: 0 16px;
  font-family: system-ui, sans-serif;
  text-align: center;
}

.muted { color: #6b5b55; }

.notice {
  padding: 10px 14px;
  border-radius: 8px;
  background: #fde8e8;
  color: #8c1d3a;
}

.account {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
  text-align: left;
}

.account img { border-radius: 50%; }
.name { font-weight: 600; margin: 0; }
.account .muted { margin: 0; font-size: 0.9rem; }

.google,
button {
  display: inline-block;
  min-height: 44px;
  padding: 10px 20px;
  border: 1px solid #8c1d3a;
  border-radius: 999px;
  background: #8c1d3a;
  color: #fff;
  font: inherit;
  text-decoration: none;
  cursor: pointer;
}

button {
  background: transparent;
  color: #8c1d3a;
}

button:disabled { opacity: 0.6; cursor: default; }
</style>
