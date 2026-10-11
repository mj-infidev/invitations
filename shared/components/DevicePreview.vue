<template>
  <!--
    The real invitation page in a frame, as a phone (390×844, upright) or a
    desktop browser window (1440×900), scaled down to fit. The page inside lays
    itself out exactly as on that screen; only the drawing is smaller.
    A toolbar switches the device, reloads, and opens it full screen.
    Below `buttonBelow` px of window width it is only a button that opens the
    page in a new tab. Changing `reloadKey` reloads it.
  -->
  <a v-if="asButton" :href="url" target="_blank" rel="noopener" class="preview-button">
    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5" /><path d="M11 18.5h2" /></svg>
    {{ buttonLabel }}
  </a>

  <div v-else ref="root" class="preview" :class="[`preview--${device}`, `preview--align-${align}`, { 'is-fullscreen': fullscreen }]">
    <div class="preview__toolbar" role="toolbar" aria-label="Preview">
      <span v-if="label" class="preview__label">{{ label }}</span>
      <div class="preview__devices" role="group" aria-label="Screen">
        <button type="button" :aria-pressed="device === 'phone'" title="Phone" @click="setDevice('phone')">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5" /><path d="M11 18.5h2" /></svg>
          <span class="sr-only">Phone</span>
        </button>
        <button type="button" :aria-pressed="device === 'desktop'" title="Desktop" @click="setDevice('desktop')">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="4" width="19" height="13" rx="2" /><path d="M8.5 20.5h7M12 17v3.5" /></svg>
          <span class="sr-only">Desktop</span>
        </button>
      </div>
      <button type="button" class="preview__tool" title="Reload" @click="ownReloads++">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v4.5h-4.5" /></svg>
        <span class="sr-only">Reload</span>
      </button>
      <button type="button" class="preview__tool" title="Full screen" @click="openFullscreen">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 4H20v5.5M20 4l-6.5 6.5M9.5 20H4v-5.5M4 20l6.5-6.5" /></svg>
        <span class="sr-only">Full screen</span>
      </button>
    </div>

    <div ref="box" class="preview__stage" :style="{ height: fullscreen ? null : `${frameHeight}px` }">
      <div class="preview__device" :style="fullscreen ? null : { width: `${screen.width * scale + chrome.x}px` }">
        <div v-if="device === 'desktop'" class="preview__bar" aria-hidden="true">
          <i /><i /><i />
          <span>{{ label || 'Preview' }}</span>
        </div>
        <div class="preview__screen" :style="fullscreen ? null : { width: `${screen.width * scale}px`, height: `${screen.height * scale}px` }">
          <iframe
            :key="`${reloadKey}-${ownReloads}`"
            :src="src"
            :title="label ? `${label} preview` : 'Invitation preview'"
            :style="fullscreen ? null : { transform: `scale(${scale})` }"
            :width="fullscreen ? null : screen.width"
            :height="fullscreen ? null : screen.height"
          />
        </div>
      </div>
      <button v-if="fullscreen" type="button" class="preview__close" @click="closeFullscreen">Close</button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  url: { type: String, required: true },
  reloadKey: { type: Number, default: 0 },
  /** Tallest the frame may be, in px. */
  maxHeight: { type: Number, default: Infinity },
  /** A name for the toolbar and the desktop window's title bar. */
  label: { type: String, default: '' },
  /** Windows narrower than this (px) get only a button that opens the page. 0: never. */
  buttonBelow: { type: Number, default: 0 },
  buttonLabel: { type: String, default: 'Open the preview' },
  /** Where the toolbar and device sit across the width: 'center', or 'start' so the toolbar stays put when the device changes. */
  align: { type: String, default: 'center' },
})

/** 'phone' or 'desktop'; v-model:device to follow or set it. */
const device = defineModel('device', { type: String, default: 'phone' })

const SCREENS = { phone: { width: 390, height: 844 }, desktop: { width: 1440, height: 900 } }
// Space the frame adds around the scaled page: the phone's bezel, the window's title bar.
const CHROME = { phone: { x: 20, y: 20 }, desktop: { x: 2, y: 30 } }

const root = ref(null)
const box = ref(null)
const boxWidth = ref(SCREENS.phone.width)
const ownReloads = ref(0)
const fullscreen = ref(false)
const windowWidth = ref(window.innerWidth)

const asButton = computed(() => windowWidth.value < props.buttonBelow)
const screen = computed(() => SCREENS[device.value])
const chrome = computed(() => CHROME[device.value])
const scale = computed(() => Math.min(
  1,
  (boxWidth.value - chrome.value.x) / screen.value.width,
  (props.maxHeight - chrome.value.y) / screen.value.height,
))
const frameHeight = computed(() => screen.value.height * scale.value + chrome.value.y)

// A new address per reload: the browser loads it fresh rather than from its history.
const src = computed(() => `${props.url}${props.url.includes('?') ? '&' : '?'}r=${props.reloadKey + ownReloads.value}`)

function setDevice(name) {
  device.value = name
}

// Full screen shows the page at the screen's real size. Where the browser has no
// full screen for elements (iPhone Safari), the page opens in a new tab instead.
function openFullscreen() {
  if (root.value?.requestFullscreen) root.value.requestFullscreen().catch(() => window.open(props.url, '_blank', 'noopener'))
  else window.open(props.url, '_blank', 'noopener')
}

function closeFullscreen() {
  if (document.fullscreenElement) document.exitFullscreen()
}

const onFullscreenChange = () => { fullscreen.value = document.fullscreenElement === root.value }
const onResize = () => { windowWidth.value = window.innerWidth }

let observer = null
const observe = () => {
  observer?.disconnect()
  if (!box.value) return
  observer = new ResizeObserver(([entry]) => { boxWidth.value = entry.contentRect.width })
  observer.observe(box.value)
}

onMounted(() => {
  observe()
  window.addEventListener('resize', onResize, { passive: true })
  document.addEventListener('fullscreenchange', onFullscreenChange)
})
// The frame comes and goes with the button; watch for it.
watch(asButton, () => queueMicrotask(observe), { flush: 'post' })
onBeforeUnmount(() => {
  observer?.disconnect()
  window.removeEventListener('resize', onResize)
  document.removeEventListener('fullscreenchange', onFullscreenChange)
})
</script>

<style scoped>
.preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  width: 100%;
}

/* ── Toolbar: one light pill, whatever the page behind it ── */

.preview__toolbar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px;
  border-radius: var(--radius-pill);
  background: var(--color-preview-toolbar);
  color: var(--color-preview-text);
  box-shadow: var(--shadow-toolbar);
  font: 600 0.88rem/1 var(--font-body);
}

.preview__label {
  max-width: 14rem;
  padding: 0 12px 0 10px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  border-right: 1px solid color-mix(in srgb, var(--color-preview-text) 12%, transparent);
}

.preview__devices {
  display: flex;
  padding: 3px;
  border-radius: var(--radius-pill);
  background: color-mix(in srgb, var(--color-preview-text) 6%, transparent);
}

.preview button {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: 0;
  border-radius: var(--radius-round);
  background: transparent;
  color: inherit;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s;
}

.preview__devices button { width: 34px; height: 30px; }
.preview__devices button[aria-pressed='true'] { background: var(--color-preview-active); color: var(--color-preview-active-text); }
.preview .preview__tool { border: 1px solid color-mix(in srgb, var(--color-preview-text) 12%, transparent); }
.preview button:hover:not([aria-pressed='true']) { background: color-mix(in srgb, var(--color-preview-text) 8%, transparent); }
.preview button:focus-visible,
.preview-button:focus-visible { outline: 2px solid var(--color-preview-active); outline-offset: 2px; }

svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* ── The device ── */

.preview__stage {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
}

/* A soft glow behind the device. */
.preview__stage::before {
  content: '';
  position: absolute;
  inset: 4% 8%;
  border-radius: var(--radius-round);
  background: color-mix(in srgb, var(--color-preview-glow) 28%, transparent);
  filter: blur(60px);
  pointer-events: none;
}

.preview--align-start { align-items: flex-start; }
.preview--align-start .preview__stage { justify-content: flex-start; }

.preview__device { position: relative; }

.preview--phone .preview__device {
  padding: 10px;
  border-radius: var(--radius-device);
  background: var(--color-preview-device);
  box-shadow: var(--shadow-device);
}

.preview--phone .preview__screen { border-radius: var(--radius-device-screen); }

.preview--desktop .preview__device {
  padding: 0 1px 1px;
  border-radius: var(--radius-window);
  background: var(--color-preview-window);
  box-shadow: var(--shadow-lg);
}

.preview--desktop .preview__screen { border-radius: 0 0 calc(var(--radius-window) - 1px) calc(var(--radius-window) - 1px); }

.preview__bar {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 30px;
  padding: 0 12px;
  color: color-mix(in srgb, var(--color-preview-text) 55%, transparent);
  font: 500 0.7rem/1 var(--font-body);
}

.preview__bar i { width: 9px; height: 9px; border-radius: var(--radius-round); background: var(--color-window-close); }
.preview__bar i:nth-child(2) { background: var(--color-window-minimise); }
.preview__bar i:nth-child(3) { background: var(--color-window-zoom); }
.preview__bar span { margin-left: 10px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

.preview__screen {
  overflow: hidden;
  background: var(--color-preview-device);
}

iframe {
  display: block;
  border: 0;
  transform-origin: 0 0;
}

/* ── Full screen: the page at the screen's own size ── */

.preview.is-fullscreen { gap: 0; background: var(--color-preview-fullscreen); }
.preview.is-fullscreen .preview__toolbar { display: none; }
.preview.is-fullscreen .preview__stage,
.preview.is-fullscreen .preview__device,
.preview.is-fullscreen .preview__screen,
.preview.is-fullscreen iframe { width: 100%; height: 100%; padding: 0; border-radius: 0; box-shadow: none; }
.preview.is-fullscreen .preview__stage::before,
.preview.is-fullscreen .preview__bar { display: none; }

.preview .preview__close {
  position: absolute;
  top: 16px;
  left: 16px;
  width: auto;
  height: 40px;
  padding: 0 18px;
  border-radius: var(--radius-pill);
  background: var(--color-preview-toolbar);
  color: var(--color-preview-text);
  font: 600 0.88rem/1 var(--font-body);
  box-shadow: var(--shadow-md);
}

/* ── Narrow windows: only a button ── */

.preview-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 52px;
  padding: 14px 28px;
  border-radius: var(--radius-pill);
  background: var(--preview-button-bg, var(--color-preview-toolbar));
  color: var(--preview-button-ink, var(--color-preview-text));
  font-weight: 600;
  text-decoration: none;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}
</style>
