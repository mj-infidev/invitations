<template>
  <SiteNav />

  <p v-if="signInFailed" class="notice notice--top" role="alert">
    Sign-in didn't finish. Please try again.
  </p>

  <router-view />
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SiteNav from './components/SiteNav.vue'
import { useAuth } from './composables/useAuth'

const route = useRoute()
const router = useRouter()
const { fetchMe } = useAuth()

const signInFailed = ref(false)

fetchMe()

// Back from Google with ?signin=failed: say so once, and tidy the address.
watch(() => route.query.signin, (value) => {
  if (value !== 'failed') return
  signInFailed.value = true
  const { signin: _done, ...query } = route.query
  router.replace({ query })
})
</script>
