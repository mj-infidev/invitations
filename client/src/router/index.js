import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/Home.vue'),
  },
  // Creates a draft and goes straight to the builder. Signing in comes first.
  {
    path: '/create',
    name: 'create',
    component: () => import('../views/Create.vue'),
    meta: { requiresAuth: true, title: 'New invitation' },
  },
  {
    path: '/invitations',
    name: 'invitations',
    component: () => import('../views/Invitations.vue'),
    meta: { requiresAuth: true, title: 'My invitations' },
  },
  {
    path: '/invitations/:id(\\d+)',
    name: 'builder',
    component: () => import('../views/Builder.vue'),
    // Has its own sticky bar: the site nav scrolls away here.
    meta: { requiresAuth: true, title: 'Edit invitation', navScrollsAway: true },
  },
  // After paying: the link, Copy, and Share on WhatsApp.
  {
    path: '/invitations/:id(\\d+)/share',
    name: 'share',
    component: () => import('../views/Share.vue'),
    meta: { requiresAuth: true, title: 'Share your invitation' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  // Links to a home page section (/#demo) land below the site nav. Coming from
  // another page, the home page needs a moment to be on screen first.
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (!to.hash) return { top: 0 }
    const target = { el: to.hash, top: 64, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }
    return to.name === from.name ? target : new Promise((resolve) => setTimeout(() => resolve(target), 350))
  },
})

// Pages that need an account send a guest to Google, and Google sends them
// back to the page they wanted.
router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true

  const { fetchMe, isSignedIn, signInUrl } = useAuth()
  await fetchMe()
  if (isSignedIn.value) return true

  window.location.assign(signInUrl(to.fullPath))
  return false
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Invitations` : 'Invitations'
})

export default router
