<template>
  <!--
    The site's top bar. On the home page it sits over the hero, fully
    transparent, and turns to frosted glass once the page scrolls; elsewhere
    it is frosted glass from the start and stays at the top (on the builder,
    which has its own sticky bar, it scrolls away). Phones get a menu button
    and a full-screen panel. Colours come from the theme: the home page's
    dark scheme over the hero, the light one elsewhere.
  -->
  <header
    ref="bar"
    class="nav"
    :class="{ 'nav--overlay': overlay, 'nav--scrolls': scrollsAway, 'is-glass': glass, 'is-open': open }"
    :data-theme="overlay ? 'dark' : null"
  >
    <div class="nav__inner">
      <router-link to="/" class="nav__brand" @click="close">Invitations</router-link>

      <nav class="nav__links" aria-label="Main">
        <router-link v-for="link in SECTIONS" :key="link.hash" :to="{ name: 'home', hash: link.hash }" class="nav__link" :class="{ 'is-current': current === link.hash }" :aria-current="current === link.hash ? 'location' : null">
          {{ link.label }}
        </router-link>
      </nav>

      <div class="nav__actions">
        <template v-if="ready">
          <div v-if="user" ref="account" class="nav__account">
            <router-link to="/invitations" class="nav__link">My invitations</router-link>
            <button
              type="button"
              class="nav__avatar"
              :aria-expanded="accountOpen"
              aria-haspopup="menu"
              aria-controls="nav-account-menu"
              :title="user.name"
              @click="accountOpen = !accountOpen"
            >
              <img v-if="user.avatar_url" :src="user.avatar_url" alt="" width="34" height="34" referrerpolicy="no-referrer" />
              <span v-else aria-hidden="true">{{ initial }}</span>
              <span class="sr-only">Your account</span>
            </button>
            <transition name="pop">
              <div v-if="accountOpen" id="nav-account-menu" class="nav__menu-card" role="menu">
                <p class="nav__who">
                  <strong>{{ user.name }}</strong>
                  <span v-if="user.email">{{ user.email }}</span>
                </p>
                <router-link to="/invitations" role="menuitem" @click="accountOpen = false">My invitations</router-link>
                <button type="button" role="menuitem" :disabled="signingOut" @click="signOut">Sign out</button>
              </div>
            </transition>
          </div>
          <a v-else :href="signInUrl(route.fullPath)" class="nav__link">Sign in</a>
        </template>
        <router-link :to="createLink" class="nav__cta">Create invitation</router-link>
      </div>

      <button
        type="button"
        class="nav__toggle"
        :aria-expanded="open"
        aria-controls="nav-panel"
        @click="open ? close() : (open = true)"
      >
        <span class="nav__toggle-lines" aria-hidden="true"><i /><i /></span>
        <span class="sr-only">{{ open ? 'Close menu' : 'Menu' }}</span>
      </button>
    </div>

    <!-- Phones: the whole screen, frosted, with the links set large. -->
    <transition name="panel">
      <div v-if="open" id="nav-panel" class="nav__panel" @keydown.esc="close">
        <nav class="nav__panel-links" aria-label="Main">
          <router-link
            v-for="(link, i) in SECTIONS"
            :key="link.hash"
            :to="{ name: 'home', hash: link.hash }"
            :style="{ '--i': i }"
            @click="close"
          >
            {{ link.label }}
          </router-link>
          <router-link v-if="user" to="/invitations" :style="{ '--i': SECTIONS.length }" @click="close">My invitations</router-link>
        </nav>
        <div class="nav__panel-foot">
          <router-link :to="createLink" class="nav__cta nav__cta--block" @click="close">Create invitation</router-link>
          <template v-if="ready">
            <button v-if="user" type="button" class="nav__panel-quiet" :disabled="signingOut" @click="signOut">Sign out</button>
            <a v-else :href="signInUrl(route.fullPath)" class="nav__panel-quiet">Sign in</a>
          </template>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useTemplates } from '../composables/useTemplates'

const SECTIONS = [
  { label: 'How it works', hash: '#how-it-works' },
  { label: 'Demo', hash: '#demo' },
  { label: 'Pricing', hash: '#pricing' },
]

const route = useRoute()
const router = useRouter()
const { user, ready, signInUrl, logout } = useAuth()
const { createLink } = useTemplates()

const bar = ref(null)
const account = ref(null)
const open = ref(false)
const accountOpen = ref(false)
const signingOut = ref(false)
const scrolled = ref(false)
/** On the home page, the section under the bar (#demo …), or null. */
const current = ref(null)

/** Over the home page's hero: transparent until the page scrolls. */
const overlay = computed(() => route.name === 'home')
/** Pages with their own sticky bar (route meta navScrollsAway). */
const scrollsAway = computed(() => !!route.meta.navScrollsAway)
const glass = computed(() => !overlay.value || scrolled.value || open.value)
const initial = computed(() => (user.value?.name ?? '?').trim().charAt(0).toUpperCase())

function close() {
  if (!open.value) return
  open.value = false
  // Back to the button that opened it.
  nextTick(() => bar.value?.querySelector('.nav__toggle')?.focus())
}

async function signOut() {
  signingOut.value = true
  try {
    await logout()
    accountOpen.value = false
    open.value = false
    router.push('/')
  } finally {
    signingOut.value = false
  }
}

const onScroll = () => {
  scrolled.value = window.scrollY > 24
  if (route.name !== 'home') {
    current.value = null
    return
  }
  // The section whose band covers the line just below the bar.
  const line = 120
  current.value = SECTIONS.map((link) => link.hash).find((hash) => {
    const box = document.querySelector(hash)?.getBoundingClientRect()
    return box && box.top <= line && box.bottom > line
  }) ?? null
}
const onPointerDown = (event) => {
  if (accountOpen.value && !account.value?.contains(event.target)) accountOpen.value = false
}
const onKey = (event) => {
  if (event.key !== 'Escape') return
  accountOpen.value = false
  close()
}

// The open panel holds the page still, and closes on any page change.
watch(open, (isOpen) => {
  document.documentElement.classList.toggle('nav-locked', isOpen)
  if (isOpen) nextTick(() => bar.value?.querySelector('.nav__panel a')?.focus())
})
watch(() => route.fullPath, () => {
  open.value = false
  accountOpen.value = false
  nextTick(onScroll)
})

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  document.addEventListener('pointerdown', onPointerDown)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  document.removeEventListener('pointerdown', onPointerDown)
  document.removeEventListener('keydown', onKey)
  document.documentElement.classList.remove('nav-locked')
})
</script>

<style scoped>
.nav {
  --nav-h: 76px;
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  position: sticky;
  top: 0;
  z-index: 50;
  color: var(--color-text);
  border-bottom: 1px solid transparent;
  transition: background-color 0.4s var(--ease), border-color 0.4s var(--ease), backdrop-filter 0.4s var(--ease), box-shadow 0.4s var(--ease);
}

.nav--overlay { position: fixed; inset: 0 0 auto; }
.nav--scrolls { position: relative; }

/* Frosted glass: the page colour, translucent, blurring what passes beneath. */
.nav.is-glass {
  background: color-mix(in srgb, var(--color-bg) 72%, transparent);
  border-bottom-color: var(--color-line);
  backdrop-filter: blur(18px) saturate(1.4);
  -webkit-backdrop-filter: blur(18px) saturate(1.4);
}

/* A blurred bar would hold the fixed panel inside itself: while the panel is
   open, the panel does the blurring. */
.nav.is-open {
  background: color-mix(in srgb, var(--color-bg) 94%, transparent);
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
}

.nav--overlay.is-glass { --nav-h: 64px; box-shadow: 0 10px 30px color-mix(in srgb, var(--color-shadow) 25%, transparent); }

.nav__inner {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 24px;
  height: var(--nav-h);
  /* The same column as the home page, so the wordmark lines up with the words below. */
  padding-inline: max(clamp(20px, 6vw, 96px), (100% - 1200px) / 2);
  transition: height 0.4s var(--ease);
}

/* ── Wordmark ── */

.nav__brand {
  justify-self: start;
  color: var(--color-text);
  font-family: var(--font-heading);
  font-size: 1.6rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1;
  text-decoration: none;
}

/* ── Links: a fine accent line draws out from the middle on hover ── */

.nav__links { display: flex; gap: clamp(24px, 3vw, 40px); }

.nav__link {
  position: relative;
  padding: 6px 0;
  color: color-mix(in srgb, var(--color-text) 78%, transparent);
  font-size: 0.92rem;
  letter-spacing: 0.03em;
  text-decoration: none;
  transition: color 0.25s;
}

.nav__link::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 1px;
  background: var(--color-accent);
  transform: scaleX(0);
  transition: transform 0.35s var(--ease);
}

.nav__link:hover { color: var(--color-text); }
.nav__link:hover::after,
.nav__link.is-current::after { transform: scaleX(1); }
.nav__link.is-current { color: var(--color-text); }

/* ── Account and the call to action ── */

.nav__actions {
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 22px;
}

.nav__account { position: relative; display: flex; align-items: center; gap: 18px; }

.nav__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 42px;
  padding: 0 22px;
  border-radius: var(--radius-pill);
  background: var(--color-accent);
  color: var(--color-accent-text);
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-decoration: none;
  white-space: nowrap;
  transition: background-color 0.25s, transform 0.25s var(--ease), box-shadow 0.25s;
}

.nav__cta:hover {
  background: var(--color-accent-light, var(--color-accent));
  box-shadow: 0 6px 18px color-mix(in srgb, var(--color-accent) 30%, transparent);
  transform: translateY(-1px);
}

.nav__avatar {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  padding: 0;
  overflow: hidden;
  border: 1px solid var(--color-line-strong);
  border-radius: var(--radius-round);
  background: var(--color-surface);
  color: var(--color-text);
  font: 600 0.9rem/1 var(--font-body);
  cursor: pointer;
  transition: border-color 0.25s;
}

.nav__avatar:hover,
.nav__avatar[aria-expanded='true'] { border-color: var(--color-accent); }
.nav__avatar img { width: 100%; height: 100%; object-fit: cover; }

.nav__menu-card {
  position: absolute;
  top: calc(100% + 14px);
  right: 0;
  display: flex;
  flex-direction: column;
  min-width: 240px;
  padding: 8px;
  border: 1px solid var(--color-line);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  box-shadow: var(--shadow-lg);
}

.nav__who {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: 0 0 6px;
  padding: 10px 12px 12px;
  border-bottom: 1px solid var(--color-line);
  font-size: 0.9rem;
}

.nav__who span { color: var(--color-text-muted); font-size: 0.82rem; overflow-wrap: anywhere; }

.nav__menu-card a,
.nav__menu-card button {
  padding: 10px 12px;
  border: 0;
  border-radius: var(--radius-md);
  background: none;
  color: var(--color-text);
  font: inherit;
  font-size: 0.92rem;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
}

.nav__menu-card a:hover,
.nav__menu-card button:hover { background: color-mix(in srgb, var(--color-accent) 10%, transparent); }

/* ── Phone menu button: two lines that cross into an X ── */

.nav__toggle {
  display: none;
  justify-self: end;
  width: 44px;
  height: 44px;
  padding: 0;
  border: 0;
  background: none;
  color: var(--color-text);
  cursor: pointer;
}

.nav__toggle-lines { position: relative; display: block; width: 24px; height: 12px; margin: 0 auto; }

.nav__toggle-lines i {
  position: absolute;
  left: 0;
  width: 100%;
  height: 1.5px;
  border-radius: var(--radius-pill);
  background: currentColor;
  transition: transform 0.35s var(--ease), top 0.35s var(--ease);
}

.nav__toggle-lines i:first-child { top: 0; }
.nav__toggle-lines i:last-child { top: 100%; }
.nav.is-open .nav__toggle-lines i:first-child { top: 50%; transform: rotate(45deg); }
.nav.is-open .nav__toggle-lines i:last-child { top: 50%; transform: rotate(-45deg); }

/* ── Phone panel ── */

.nav__panel {
  position: fixed;
  inset: var(--nav-h) 0 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 32px;
  padding: 32px clamp(20px, 6vw, 96px) max(32px, env(safe-area-inset-bottom));
  overflow-y: auto;
  background: color-mix(in srgb, var(--color-bg) 94%, transparent);
  backdrop-filter: blur(24px) saturate(1.4);
  -webkit-backdrop-filter: blur(24px) saturate(1.4);
}

.nav__panel-links { display: flex; flex-direction: column; }

.nav__panel-links a {
  padding: 14px 0;
  border-bottom: 1px solid var(--color-line);
  color: var(--color-text);
  font-family: var(--font-heading);
  font-size: 2.1rem;
  font-weight: 500;
  line-height: 1.1;
  text-decoration: none;
  /* Each link rises in a moment after the one above it. */
  animation: rise 0.6s var(--ease) both;
  animation-delay: calc(0.06s + var(--i, 0) * 0.06s);
}

.nav__panel-foot { display: flex; flex-direction: column; align-items: center; gap: 16px; }
.nav__cta--block { width: 100%; height: 52px; font-size: 1rem; }

.nav__panel-quiet {
  padding: 8px;
  border: 0;
  background: none;
  color: var(--color-text-muted);
  font: inherit;
  text-decoration: none;
  cursor: pointer;
}

@keyframes rise {
  from { opacity: 0; transform: translateY(14px); }
}

.panel-enter-active,
.panel-leave-active { transition: opacity 0.35s var(--ease); }
.panel-enter-from,
.panel-leave-to { opacity: 0; }

.pop-enter-active,
.pop-leave-active { transition: opacity 0.2s, transform 0.2s var(--ease); }
.pop-enter-from,
.pop-leave-to { opacity: 0; transform: translateY(-6px); }

.nav a:focus-visible,
.nav button:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 4px; border-radius: var(--radius-sm); }

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

/* ── Phones and small tablets ── */

@media (max-width: 899px) {
  .nav { --nav-h: 64px; }
  .nav__inner { grid-template-columns: 1fr auto; }
  .nav__links,
  .nav__actions { display: none; }
  .nav__toggle { display: block; }
  .nav__brand { font-size: 1.45rem; }
}

@media (prefers-reduced-motion: reduce) {
  .nav,
  .nav *,
  .nav__panel-links a { transition: none !important; animation: none !important; }
}
</style>
