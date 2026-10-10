<template>
  <!--
    The home page: photographs only (art/home, as WebP in phone and desktop
    sizes), deep maroon and gold, Cormorant Garamond headings, few words.
    Sections reveal gently on scroll (GSAP); with reduced motion they're simply there.
  -->
  <div ref="root" class="home">
    <!-- Hero -->
    <section class="hero">
      <picture class="hero__photo" data-hero-photo>
        <source media="(max-width: 767px)" :srcset="`${photos.heroMobile640} 640w, ${photos.heroMobile941} 941w`" sizes="100vw" />
        <img :src="photos.heroDesktop1672" :srcset="`${photos.heroDesktop960} 960w, ${photos.heroDesktop1672} 1672w`" sizes="100vw" alt="A temple gopuram at sunset, with brass lamps and jasmine garlands" fetchpriority="high" />
      </picture>
      <div class="hero__shade" />

      <div class="hero__content">
        <h1 data-hero-line><span class="line">Wedding invitations,</span> <span class="line">brought to life</span></h1>
        <p class="hero__line" data-hero-line>An animated invitation your guests open on WhatsApp, and remember.</p>
        <div class="buttons" data-hero-line>
          <router-link :to="createLink" class="btn btn--gold">Create my invitation</router-link>
          <a href="#demo" class="btn btn--ghost" @click.prevent="scrollToDemo">See a demo</a>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="steps section">
      <div class="steps__photo" data-reveal>
        <img :src="photos.details1448" :srcset="`${photos.details760} 760w, ${photos.details1448} 1448w`" sizes="(min-width: 900px) 42vw, 100vw" alt="A lit brass lamp beside a silk saree and jasmine" loading="lazy" />
      </div>
      <div class="steps__text">
        <p class="eyebrow" data-reveal>How it works</p>
        <h2 data-reveal>Ready in an evening</h2>
        <ol class="steps__list">
          <li v-for="(step, i) in STEPS" :key="step.title" data-reveal>
            <span class="steps__number">0{{ i + 1 }}</span>
            <div>
              <h3>{{ step.title }}</h3>
              <p>{{ step.text }}</p>
            </div>
          </li>
        </ol>
      </div>
    </section>

    <!-- The demo, in a phone -->
    <section id="demo" ref="demoSection" class="demo section">
      <picture class="demo__photo">
        <img :src="photos.mandapam1448" :srcset="`${photos.mandapam800} 800w, ${photos.mandapam1448} 1448w`" sizes="100vw" alt="" loading="lazy" />
      </picture>
      <div class="demo__shade" />

      <div class="demo__text">
        <p class="eyebrow" data-reveal>The invitation</p>
        <h2 data-reveal><span class="line">Seven scenes.</span> <span class="line">One scroll.</span></h2>
        <p data-reveal>From the temple gopuram to the garland exchange, every detail unfolds as your guests scroll.</p>
        <a v-if="demoUrl" :href="demoUrl" target="_blank" rel="noopener" class="btn btn--ghost" data-reveal>Open the full demo</a>
      </div>

      <div class="demo__phone" data-reveal>
        <!-- Loaded only when the section comes near: the invitation is a page of its own. -->
        <PhonePreview v-if="demoUrl && showDemo" :url="demoUrl" :max-height="640" />
        <div v-else class="demo__placeholder" />
        <p class="demo__hint">Scroll inside the phone</p>
      </div>
    </section>

    <!-- Price -->
    <section class="price section">
      <p class="eyebrow" data-reveal>Pricing</p>
      <h2 data-reveal><span class="line">One price.</span> <span class="line">Everything included.</span></h2>
      <div class="price__card" data-reveal>
        <p class="price__name">{{ template?.name ?? 'Tamil Hindu wedding' }}</p>
        <p class="price__amount">{{ price }}</p>
        <p class="price__once">one payment</p>
        <ul>
          <li>The animated invitation</li>
          <li>Up to {{ template?.max_photos ?? 6 }} of your photos</li>
          <li>A link for WhatsApp</li>
          <li>Changes until the wedding day</li>
        </ul>
        <router-link :to="createLink" class="btn btn--gold">Create my invitation</router-link>
        <p class="price__free">Preview free. Pay only when you love it.</p>
      </div>
    </section>

    <footer class="footer">
      <p class="footer__brand">Invitations</p>
      <nav class="footer__links" aria-label="Footer">
        <router-link :to="createLink">Create</router-link>
        <a href="#demo" @click.prevent="scrollToDemo">Demo</a>
        <router-link to="/invitations">My invitations</router-link>
      </nav>
      <p class="footer__small">Animated wedding invitations · Made in India · © {{ year }}</p>
    </footer>
  </div>
</template>

<script setup>
import '@fontsource/cormorant-garamond/500.css'
import '@fontsource/cormorant-garamond/600.css'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PhonePreview from '@shared/components/PhonePreview.vue'
import { api } from '../lib/api'
import heroDesktop960 from '../assets/home/hero-desktop-960.webp'
import heroDesktop1672 from '../assets/home/hero-desktop-1672.webp'
import heroMobile640 from '../assets/home/hero-mobile-640.webp'
import heroMobile941 from '../assets/home/hero-mobile-941.webp'
import details760 from '../assets/home/details-760.webp'
import details1448 from '../assets/home/details-1448.webp'
import mandapam800 from '../assets/home/mandapam-800.webp'
import mandapam1448 from '../assets/home/mandapam-1448.webp'

gsap.registerPlugin(ScrollTrigger)

const photos = { heroDesktop960, heroDesktop1672, heroMobile640, heroMobile941, details760, details1448, mandapam800, mandapam1448 }

const STEPS = [
  { title: 'Tell your story', text: 'Names, muhurtham, venue and photos. Saved as you type.' },
  { title: 'See it first', text: 'Preview the real invitation, free, before you pay.' },
  { title: 'Share the link', text: 'Pay once, send it on WhatsApp, edit until the big day.' },
]

const root = ref(null)
const demoSection = ref(null)
const template = ref(null)
const showDemo = ref(false)
const year = new Date().getFullYear()

const createLink = computed(() => ({ name: 'create', query: { template: template.value?.slug ?? 'tamil-hindu-wedding' } }))
const demoUrl = computed(() => template.value?.demo_url ?? null)
const price = computed(() => (template.value ? `₹${(template.value.price_paise / 100).toLocaleString('en-IN')}` : '₹499'))

function scrollToDemo() {
  demoSection.value?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
}

let mm = null
let observer = null

onMounted(async () => {
  // Gentle reveals; none at all for guests who asked for less motion.
  mm = gsap.matchMedia()
  mm.add('(prefers-reduced-motion: no-preference)', () => {
    gsap.from('[data-hero-photo] img', { scale: 1.08, duration: 2.4, ease: 'power2.out' })
    gsap.from('[data-hero-line]', { y: 28, opacity: 0, duration: 1.1, ease: 'power3.out', stagger: 0.14, delay: 0.2 })

    gsap.utils.toArray(root.value.querySelectorAll('[data-reveal]')).forEach((element) => {
      gsap.from(element, {
        y: 36,
        opacity: 0,
        duration: 1,
        ease: 'power2.out',
        scrollTrigger: { trigger: element, start: 'top 88%', once: true },
      })
    })
  })

  // The demo invitation loads only when its section is a screen away.
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      showDemo.value = true
      observer.disconnect()
    }
  }, { rootMargin: '100% 0px' })
  observer.observe(demoSection.value)

  try {
    template.value = (await api('/api/templates')).data[0] ?? null
  } catch {
    // The page still works on its defaults; Create goes to the usual template.
  }
})

onBeforeUnmount(() => {
  mm?.revert()
  observer?.disconnect()
})
</script>

<style scoped>
.home {
  --maroon-950: #140306;
  --maroon-900: #1f0509;
  --maroon-800: #2b0810;
  --gold: #d4a84f;
  --gold-light: #ecd08f;
  --ivory: #f6eee0;
  --ivory-dim: rgb(246 238 224 / 0.72);
  --serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;

  background: var(--maroon-900);
  color: var(--ivory);
}

h1, h2, h3 { font-family: var(--serif); font-weight: 600; margin: 0; }

/* A heading of two short sentences: one per line. */
.line { display: block; }

h2 {
  font-size: clamp(2.4rem, 5.4vw, 4.4rem);
  line-height: 1.05;
  letter-spacing: -0.01em;
}

.eyebrow {
  margin: 0 0 18px;
  color: var(--gold);
  font-size: 0.78rem;
  letter-spacing: 0.32em;
  text-transform: uppercase;
}

.section { padding: clamp(88px, 13vw, 168px) clamp(20px, 6vw, 96px); }

/* ── Buttons ─────────────────────────────────────────────── */

.buttons { display: flex; flex-wrap: wrap; gap: 14px; }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 52px;
  padding: 14px 30px;
  border-radius: 999px;
  font-size: 0.98rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-decoration: none;
  transition: background-color 0.2s, color 0.2s, border-color 0.2s;
}

.btn--gold { background: var(--gold); color: var(--maroon-950); }
.btn--gold:hover { background: var(--gold-light); }
.btn--ghost { border: 1px solid rgb(212 168 79 / 0.7); color: var(--ivory); }
.btn--ghost:hover { border-color: var(--gold-light); color: var(--gold-light); }
.btn:focus-visible { outline: 2px solid var(--gold-light); outline-offset: 3px; }

/* ── Hero ─────────────────────────────────────────────────── */

.hero {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 100vh;
  min-height: 100svh;
  overflow: hidden;
}

.hero__photo,
.hero__photo img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.hero__photo img { object-fit: cover; object-position: 70% center; }

/* Dark on the left, where the words are; the temple stays bright on the right. */
.hero__shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgb(20 3 6 / 0.94) 0%, rgb(20 3 6 / 0.78) 34%, rgb(20 3 6 / 0.12) 68%, rgb(20 3 6 / 0) 100%),
    linear-gradient(0deg, rgb(20 3 6 / 0.6) 0%, rgb(20 3 6 / 0) 30%);
}

.hero__content {
  position: relative;
  max-width: 1000px;
  padding: 120px clamp(20px, 6vw, 96px) 80px;
}

.hero h1 {
  font-size: clamp(3rem, 5.6vw, 5.4rem);
  line-height: 1;
  letter-spacing: -0.015em;
}

.hero__line {
  margin: 24px 0 40px;
  max-width: 30ch;
  color: var(--ivory-dim);
  font-size: clamp(1.05rem, 1.6vw, 1.25rem);
  line-height: 1.6;
}

/* ── How it works ────────────────────────────────────────── */

.steps {
  display: grid;
  gap: clamp(48px, 7vw, 112px);
  align-items: center;
  background: var(--maroon-800);
}

.steps__photo img {
  display: block;
  width: 100%;
  aspect-ratio: 4 / 5;
  object-fit: cover;
  object-position: 30% center;
  border-radius: 4px;
}

.steps__list {
  margin: 48px 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 36px;
}

.steps__list li { display: flex; gap: 24px; align-items: baseline; }

.steps__number {
  flex: none;
  width: 2.2ch;
  font-family: var(--serif);
  font-size: 2.6rem;
  line-height: 1;
  color: var(--gold);
}

.steps h3 { font-size: clamp(1.6rem, 2.4vw, 2rem); line-height: 1.15; }
.steps__list p { margin: 8px 0 0; color: var(--ivory-dim); line-height: 1.6; }

/* ── Demo ─────────────────────────────────────────────────── */

.demo {
  position: relative;
  display: grid;
  gap: clamp(48px, 7vw, 96px);
  align-items: center;
  overflow: hidden;
}

.demo__photo,
.demo__photo img,
.demo__shade {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.demo__photo img { object-fit: cover; }
.demo__shade { background: linear-gradient(90deg, rgb(20 3 6 / 0.9) 0%, rgb(20 3 6 / 0.72) 50%, rgb(20 3 6 / 0.5) 100%); }

.demo__text,
.demo__phone { position: relative; }

.demo__text { max-width: 480px; }
.demo__text > p:not(.eyebrow) { margin: 24px 0 36px; color: var(--ivory-dim); font-size: 1.1rem; line-height: 1.7; }

.demo__phone {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  width: min(100%, 340px);
  justify-self: center;
}

.demo__placeholder { width: 100%; aspect-ratio: 390 / 844; border-radius: 24px; background: rgb(0 0 0 / 0.35); }
.demo__hint { margin: 0; color: var(--ivory-dim); font-size: 0.85rem; letter-spacing: 0.08em; }

/* ── Price ────────────────────────────────────────────────── */

.price { text-align: center; background: var(--maroon-900); }
.price h2 { margin: 0 auto; }

.price__card {
  display: flex;
  flex-direction: column;
  align-items: center;
  max-width: 420px;
  margin: 64px auto 0;
  padding: 56px clamp(24px, 5vw, 48px);
  border: 1px solid rgb(212 168 79 / 0.35);
  border-radius: 6px;
  background: var(--maroon-800);
}

.price__name { margin: 0; color: var(--gold); font-size: 0.8rem; letter-spacing: 0.28em; text-transform: uppercase; }
.price__amount { margin: 18px 0 0; font-family: var(--serif); font-size: clamp(4rem, 9vw, 5.5rem); font-weight: 500; line-height: 1; }
.price__once { margin: 6px 0 32px; color: var(--ivory-dim); }

.price__card ul {
  width: 100%;
  margin: 0 0 36px;
  padding: 28px 0 0;
  border-top: 1px solid rgb(212 168 79 / 0.25);
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 14px;
  color: var(--ivory-dim);
}

.price__free { margin: 18px 0 0; color: var(--ivory-dim); font-size: 0.9rem; }

/* ── Footer ───────────────────────────────────────────────── */

.footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  padding: 64px 20px 48px;
  border-top: 1px solid rgb(212 168 79 / 0.18);
  background: var(--maroon-950);
  text-align: center;
}

.footer__brand { margin: 0; font-family: var(--serif); font-size: 1.8rem; font-weight: 600; }
.footer__links { display: flex; gap: 28px; }
.footer__links a { color: var(--ivory-dim); text-decoration: none; }
.footer__links a:hover { color: var(--gold-light); }
.footer__small { margin: 0; color: rgb(246 238 224 / 0.45); font-size: 0.82rem; }

/* ── Wider screens ────────────────────────────────────────── */

@media (min-width: 900px) {
  .hero h1 .line { white-space: nowrap; }
  .steps { grid-template-columns: 5fr 7fr; }
  .demo { grid-template-columns: 1fr auto; }
  .demo__phone { width: 340px; }
}

/* ── Phones ───────────────────────────────────────────────── */

@media (max-width: 767px) {
  .hero { align-items: flex-end; }
  .hero__photo img { object-position: center 30%; }

  /* The words sit low, over the dark floor; the temple stays clear above. */
  .hero__shade {
    background: linear-gradient(0deg, rgb(20 3 6 / 0.96) 0%, rgb(20 3 6 / 0.82) 38%, rgb(20 3 6 / 0.15) 66%, rgb(20 3 6 / 0.45) 100%);
  }

  .hero__content { padding-bottom: max(56px, env(safe-area-inset-bottom)); }
  .buttons .btn { flex: 1 1 100%; }
  .steps__photo img { aspect-ratio: 4 / 3; }
  .demo__shade { background: rgb(20 3 6 / 0.8); }
}
</style>
