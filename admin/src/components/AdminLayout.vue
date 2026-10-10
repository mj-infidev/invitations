<template>
  <!-- Sidebar on desktop; a menu button on phones. -->
  <div class="layout" :class="{ 'menu-open': menuOpen }">
    <header class="topbar">
      <button type="button" class="menu-button" aria-label="Menu" :aria-expanded="String(menuOpen)" @click="menuOpen = !menuOpen">☰</button>
      <span class="brand">Invitations admin</span>
      <span class="who">{{ admin?.name }}</span>
      <button type="button" class="link-button" @click="signOut">Sign out</button>
    </header>

    <nav class="sidebar" aria-label="Admin" @click="menuOpen = false">
      <router-link v-for="item in NAV" :key="item.to" :to="item.to">{{ item.label }}</router-link>
    </nav>

    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminAuth } from '../composables/useAdminAuth'

const NAV = [
  { to: '/', label: 'Dashboard' },
  { to: '/invitations', label: 'Invitations' },
  { to: '/orders', label: 'Orders' },
  { to: '/templates', label: 'Templates' },
  { to: '/customers', label: 'Customers' },
]

const router = useRouter()
const { admin, logout } = useAdminAuth()
const menuOpen = ref(false)

async function signOut() {
  await logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 200px 1fr;
  grid-template-rows: auto 1fr;
  min-height: 100vh;
}

.topbar {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--line);
  background: var(--surface);
}

.brand { flex: 1; font-weight: 700; }
.who { color: var(--muted); font-size: 0.9rem; }
.menu-button { display: none; border: 0; background: none; font-size: 1.4rem; cursor: pointer; }

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 12px 8px;
  border-right: 1px solid var(--line);
  background: var(--surface);
}

.sidebar a {
  padding: 10px 12px;
  border-radius: 8px;
  color: var(--text);
  text-decoration: none;
}

.sidebar a.router-link-exact-active,
.sidebar a.router-link-active:not([href='/']) { background: var(--accent-soft); color: var(--accent); font-weight: 600; }

.content { min-width: 0; }

@media (max-width: 767px) {
  .layout { grid-template-columns: 1fr; }
  .menu-button { display: block; }
  .who { display: none; }
  .sidebar { display: none; border-right: 0; border-bottom: 1px solid var(--line); }
  .menu-open .sidebar { display: flex; }
}
</style>
