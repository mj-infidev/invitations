<template>
  <!--
    The home page: photographs only (art/home, as WebP in phone and desktop
    sizes), the site theme's dark scheme (assets/theme.css), few words, and
    nothing tied to one tradition: the templates carry their own wording.
    Sections reveal gently on scroll (GSAP); with reduced motion they're simply there.
  -->
  <div ref="root" class="home" data-theme="dark">
    <!-- Hero -->
    <section class="hero">
      <picture class="hero__photo" data-hero-photo>
        <source media="(max-width: 767px)" :srcset="`${photos.heroMobile640} 640w, ${photos.heroMobile941} 941w`" sizes="100vw" />
        <img :src="photos.heroDesktop1672" :srcset="`${photos.heroDesktop960} 960w, ${photos.heroDesktop1672} 1672w`" sizes="100vw" alt="Roses, jasmine and pearls around a cream invitation card, lit by warm candlelight" fetchpriority="high" />
      </picture>
      <div class="hero__shade" />

      <div class="hero__content">
        <h1 data-hero-line><span class="line">Wedding invitations,</span> <span class="line">brought to life</span></h1>
        <p class="hero__line" data-hero-line>An animated invitation your guests open on WhatsApp, and remember.</p>
        <div class="buttons" data-hero-line>
          <router-link :to="createLink" class="btn btn--primary">Create my invitation</router-link>
          <a href="#demo" class="btn btn--ghost" @click.prevent="scrollToDemo">See a demo</a>
        </div>
        <p class="hero__templates" data-hero-line>
          <span class="hero__templates-label">Available now</span>
          {{ templateNames }}<span class="hero__templates-soon"> · More coming soon</span>
        </p>
      </div>
    </section>

    <!-- How it works -->
    <section class="steps section">
      <div class="steps__photo" data-reveal>
        <img :src="photos.details1122" :srcset="`${photos.details560} 560w, ${photos.details1122} 1122w`" sizes="(min-width: 900px) 42vw, 100vw" alt="A blank invitation card and envelope on dark silk, with roses, a gold ribbon and two rings" loading="lazy" />
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

    <!-- The demo, in a phone or a desktop window; on phones, a button that opens it -->
    <section id="demo" ref="demoSection" class="demo section">
      <picture class="demo__photo">
        <img :src="photos.mandapam1672" :srcset="`${photos.mandapam800} 800w, ${photos.mandapam1672} 1672w`" sizes="100vw" alt="" loading="lazy" />
      </picture>
      <div class="demo__shade" />

      <div class="demo__text">
        <p class="eyebrow" data-reveal>The invitation</p>
        <h2 data-reveal><span class="line">Seven scenes.</span> <span class="line">One scroll.</span></h2>
        <p data-reveal>From the first scene to the final welcome, every detail unfolds as your guests scroll.</p>
      </div>

      <div class="demo__phone" data-reveal :style="{ '--frame-h': `${demoFrameHeight}px` }">
        <!-- Loaded only when the section comes near: the invitation is a page of its own. -->
        <DevicePreview
          v-if="demoUrl && showDemo"
          v-model:device="demoDevice"
          :url="demoUrl"
          :max-height="demoFrameHeight"
          :label="template?.name ?? 'Tamil Hindu wedding'"
          :button-below="768"
          button-label="Open the full demo"
        />
        <div v-else class="demo__placeholder" />
        <p class="demo__hint">{{ demoDevice === 'desktop' ? 'Scroll inside the window' : 'Scroll inside the phone' }}</p>
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
        <router-link :to="createLink" class="btn btn--primary">Create my invitation</router-link>
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import DevicePreview from '@shared/components/DevicePreview.vue'
import { api } from '../lib/api'
import heroDesktop960 from '../assets/home/hero-desktop-960.webp'
import heroDesktop1672 from '../assets/home/hero-desktop-1672.webp'
import heroMobile640 from '../assets/home/hero-mobile-640.webp'
import heroMobile941 from '../assets/home/hero-mobile-941.webp'
import details560 from '../assets/home/details-560.webp'
import details1122 from '../assets/home/details-1122.webp'
import mandapam800 from '../assets/home/mandapam-800.webp'
import mandapam1672 from '../assets/home/mandapam-1672.webp'

gsap.registerPlugin(ScrollTrigger)

const photos = { heroDesktop960, heroDesktop1672, heroMobile640, heroMobile941, details560, details1122, mandapam800, mandapam1672 }

const STEPS = [
  { title: 'Tell your story', text: 'Names, date and time, venue and photos. Saved as you type.' },
  { title: 'See it first', text: 'Preview the real invitation, free, before you pay.' },
  { title: 'Share the link', text: 'Pay once, send it on WhatsApp, edit until the big day.' },
]

const root = ref(null)
const demoSection = ref(null)
const templates = ref([])
// The first template is the one the page shows and links to.
const template = computed(() => templates.value[0] ?? null)
const showDemo = ref(false)
const demoDevice = ref('phone')
// The phone and the window share one height: 640 px, or less on short screens
// (a 720p laptop), so the whole frame stays in view.
const frameHeightFor = () => Math.round(Math.min(640, Math.max(420, window.innerHeight - 160)))
const demoFrameHeight = ref(frameHeightFor())
const onResize = () => { demoFrameHeight.value = frameHeightFor() }
const year = new Date().getFullYear()

// "Tamil Hindu wedding", or every template's name once there are more.
const templateNames = computed(() => templates.value.map((t) => t.name).join(' · ') || 'Tamil Hindu wedding')

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

  window.addEventListener('resize', onResize, { passive: true })

  // The demo invitation loads only when its section is a screen away.
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      showDemo.value = true
      observer.disconnect()
    }
  }, { rootMargin: '100% 0px' })
  observer.observe(demoSection.value)

  try {
    templates.value = (await api('/api/templates')).data
  } catch {
    // The page still works on its defaults; Create goes to the usual template.
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  mm?.revert()
  observer?.disconnect()
})
</script>

<style scoped>
/* Colours, fonts, radii and shadows: the site theme's dark scheme (assets/theme.css). */
.home {
  --column: 1200px;
  --gutter: clamp(20px, 6vw, 96px);

  background: var(--color-bg);
  color: var(--color-text);
}

h1, h2, h3 { font-family: var(--font-heading); font-weight: 600; margin: 0; }

/* A heading of two short sentences: one per line. */
.line { display: block; }

h2 {
  font-size: clamp(2.4rem, 5.4vw, 4.4rem);
  line-height: 1.05;
  letter-spacing: -0.01em;
}

.eyebrow {
  margin: 0 0 18px;
  color: var(--color-accent);
  font-size: 0.78rem;
  letter-spacing: 0.32em;
  text-transform: uppercase;
}

/* Photos run edge to edge; the content keeps to a centred column of at most --column. */
.section {
  padding-block: clamp(88px, 13vw, 168px);
  padding-inline: max(var(--gutter), (100% - var(--column)) / 2);
}

/* ── Buttons ─────────────────────────────────────────────── */

.buttons { display: flex; flex-wrap: wrap; gap: 14px; }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 52px;
  padding: 14px 30px;
  border-radius: var(--radius-pill);
  font-size: 0.98rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-decoration: none;
  transition: background-color 0.2s, color 0.2s, border-color 0.2s;
}

.btn--primary { background: var(--color-accent); color: var(--color-accent-text); }
.btn--primary:hover { background: var(--color-accent-light); }
.btn--ghost { border: 1px solid color-mix(in srgb, var(--color-accent) 70%, transparent); color: var(--color-text); }
.btn--ghost:hover { border-color: var(--color-accent-light); color: var(--color-accent-light); }
.btn:focus-visible { outline: 2px solid var(--color-accent-light); outline-offset: 3px; }

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

.hero__photo img { object-fit: cover; object-position: center; }

/* Dark on the left, where the words are, and the foot of the photo fading into
   the page; the flowers stay bright on the right. */
.hero__shade {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(0deg, var(--color-bg) 0%, color-mix(in srgb, var(--color-bg) 70%, transparent) 8%, transparent 26%),
    linear-gradient(90deg, color-mix(in srgb, var(--color-bg) 88%, transparent) 0%, color-mix(in srgb, var(--color-bg) 62%, transparent) 32%, color-mix(in srgb, var(--color-bg) 8%, transparent) 60%, transparent 100%),
    linear-gradient(180deg, color-mix(in srgb, var(--color-bg) 45%, transparent) 0%, transparent 14%);
}

/* The words start where the column below does. */
.hero__content {
  position: relative;
  box-sizing: border-box;
  width: 100%;
  padding: 120px max(var(--gutter), (100% - var(--column)) / 2) 80px;
}

.hero h1 {
  font-size: clamp(3rem, 5.6vw, 5.4rem);
  line-height: 1;
  letter-spacing: -0.015em;
}

.hero__templates {
  margin: 28px 0 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.hero__templates-label {
  margin-right: 10px;
  color: var(--color-accent-soft);
  font-size: 0.72rem;
  letter-spacing: 0.28em;
  text-transform: uppercase;
}

.hero__templates-soon { color: var(--color-text-faint); white-space: nowrap; }

.hero__line {
  margin: 24px 0 40px;
  max-width: 30ch;
  color: var(--color-text-muted);
  font-size: clamp(1.05rem, 1.6vw, 1.25rem);
  line-height: 1.6;
}

/* ── How it works ────────────────────────────────────────── */

.steps {
  display: grid;
  gap: clamp(48px, 7vw, 112px);
  align-items: center;
  background: var(--color-bg);
}

.steps__photo img {
  display: block;
  width: 100%;
  aspect-ratio: 4 / 5;
  object-fit: cover;
  object-position: center;
  border-radius: var(--radius-xs);
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
  font-family: var(--font-heading);
  font-size: 2.6rem;
  line-height: 1;
  color: var(--color-accent);
}

.steps h3 { font-size: clamp(1.6rem, 2.4vw, 2rem); line-height: 1.15; }
.steps__list p { margin: 8px 0 0; color: var(--color-text-muted); line-height: 1.6; }

/* ── Demo ─────────────────────────────────────────────────── */

/* Wider than the page's column: the preview takes the room the other sections leave empty. */
.demo {
  --column: 1600px;
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
/* Faded into the page at the top and bottom, so the photo has no edge; darker on the left for the words. */
.demo__shade {
  background:
    linear-gradient(180deg, var(--color-bg) 0%, transparent 18%, transparent 82%, var(--color-bg) 100%),
    linear-gradient(90deg, color-mix(in srgb, var(--color-bg) 88%, transparent) 0%, color-mix(in srgb, var(--color-bg) 66%, transparent) 45%, color-mix(in srgb, var(--color-bg) 45%, transparent) 100%);
}

.demo__text,
.demo__phone { position: relative; }

.demo__text { max-width: 480px; }
.demo__text > p:not(.eyebrow) { margin: 24px 0 36px; color: var(--color-text-muted); font-size: 1.1rem; line-height: 1.7; }

.demo__phone {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  width: 100%;
}

.demo__placeholder { height: var(--frame-h, 640px); max-width: 100%; aspect-ratio: 390 / 844; border-radius: var(--radius-device); background: color-mix(in srgb, var(--color-shadow) 35%, transparent); }

/* The narrow-window button, in the page's gold. */
.demo__phone { --preview-button-bg: var(--color-accent); --preview-button-ink: var(--color-accent-text); }
.demo__hint { margin: 0; color: var(--color-text-muted); font-size: 0.85rem; letter-spacing: 0.08em; }

/* ── Price ────────────────────────────────────────────────── */

.price { text-align: center; background: var(--color-bg); }
.price h2 { margin: 0 auto; }

.price__card {
  display: flex;
  flex-direction: column;
  align-items: center;
  max-width: 420px;
  margin: 64px auto 0;
  padding: 56px clamp(24px, 5vw, 48px);
  border: 1px solid color-mix(in srgb, var(--color-accent) 35%, transparent);
  border-radius: var(--radius-sm);
  background: var(--color-surface);
}

.price__name { margin: 0; color: var(--color-accent); font-size: 0.8rem; letter-spacing: 0.28em; text-transform: uppercase; }
.price__amount { margin: 18px 0 0; font-family: var(--font-heading); font-size: clamp(4rem, 9vw, 5.5rem); font-weight: 500; line-height: 1; }
.price__once { margin: 6px 0 32px; color: var(--color-text-muted); }

.price__card ul {
  width: 100%;
  margin: 0 0 36px;
  padding: 28px 0 0;
  border-top: 1px solid color-mix(in srgb, var(--color-accent) 25%, transparent);
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 14px;
  color: var(--color-text-muted);
}

.price__free { margin: 18px 0 0; color: var(--color-text-muted); font-size: 0.9rem; }

/* ── Footer ───────────────────────────────────────────────── */

.footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  padding: 64px 20px 48px;
  border-top: 1px solid color-mix(in srgb, var(--color-accent) 18%, transparent);
  background: var(--color-surface);
  text-align: center;
}

.footer__brand { margin: 0; font-family: var(--font-heading); font-size: 1.8rem; font-weight: 600; }
.footer__links { display: flex; gap: 28px; }
.footer__links a { color: var(--color-text-muted); text-decoration: none; }
.footer__links a:hover { color: var(--color-accent-light); }
.footer__small { margin: 0; color: var(--color-text-faint); font-size: 0.82rem; }

/* ── Wider screens ────────────────────────────────────────── */

@media (min-width: 900px) {
  .hero h1 .line { white-space: nowrap; }
  .steps { grid-template-columns: 5fr 7fr; }
  /* The words on the left, the preview in all the width left over. The preview's
     column has a set width and height whichever device shows, and the toolbar is
     centred over it, so switching between phone and window changes nothing around it. */
  .demo {
    grid-template-columns: minmax(0, clamp(300px, 30vw, 460px)) minmax(0, 1fr);
    column-gap: clamp(48px, 6vw, 112px);
  }
  /* Toolbar, frame and hint. */
  .demo__phone { min-height: calc(var(--frame-h) + 100px); }
}

/* ── Phones ───────────────────────────────────────────────── */

@media (max-width: 767px) {
  /* The phone photo is dark in its upper half and has its flowers at the foot:
     the words sit high, and the foot fades into the page. */
  .hero { align-items: flex-start; }
  .hero__photo img { object-position: center bottom; }

  .hero__shade {
    background:
      linear-gradient(180deg, color-mix(in srgb, var(--color-bg) 78%, transparent) 0%, color-mix(in srgb, var(--color-bg) 62%, transparent) 45%, color-mix(in srgb, var(--color-bg) 40%, transparent) 60%, transparent 74%, transparent 82%, var(--color-bg) 100%);
  }

  .hero__content { padding-top: 88px; padding-bottom: 48px; }
  .hero__line { margin: 18px 0 28px; }
  .hero__templates { margin-top: 22px; }
  .hero__templates-label { display: block; margin: 0 0 6px; }
  .buttons .btn { flex: 1 1 100%; }
  .steps__photo img { aspect-ratio: 4 / 3; }
  .demo__shade {
    background:
      linear-gradient(180deg, var(--color-bg) 0%, transparent 14%, transparent 86%, var(--color-bg) 100%),
      color-mix(in srgb, var(--color-bg) 80%, transparent);
  }

  /* Phones get only the button that opens the demo. */
  .demo__placeholder,
  .demo__hint { display: none; }
  .demo__phone { justify-self: start; width: auto; }
  .demo { row-gap: 0; }
}
</style>
