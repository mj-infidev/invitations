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
    meta: { requiresAuth: true, title: 'Edit invitation' },
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
