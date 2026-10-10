import { createRouter, createWebHistory } from 'vue-router'
import { useAdminAuth } from '../composables/useAdminAuth'
import AdminLayout from '../components/AdminLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/Login.vue'),
    meta: { guest: true, title: 'Sign in' },
  },
  {
    path: '/',
    component: AdminLayout,
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue'), meta: { title: 'Dashboard' } },
      { path: 'invitations', name: 'invitations', component: () => import('../views/Invitations.vue'), meta: { title: 'Invitations' } },
      { path: 'invitations/:id(\\d+)', name: 'invitation', component: () => import('../views/InvitationEdit.vue'), meta: { title: 'Invitation' } },
      { path: 'orders', name: 'orders', component: () => import('../views/Orders.vue'), meta: { title: 'Orders' } },
      { path: 'templates', name: 'templates', component: () => import('../views/Templates.vue'), meta: { title: 'Templates' } },
      { path: 'customers', name: 'customers', component: () => import('../views/Customers.vue'), meta: { title: 'Customers' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Everything but the sign-in page needs an admin; signed in, the sign-in page isn't needed.
router.beforeEach(async (to) => {
  const { fetchMe, isSignedIn } = useAdminAuth()
  await fetchMe()

  if (to.meta.guest) return isSignedIn.value ? { name: 'dashboard' } : true
  return isSignedIn.value ? true : { name: 'login', query: { redirect: to.fullPath } }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Invitations admin` : 'Invitations admin'
})

export default router
