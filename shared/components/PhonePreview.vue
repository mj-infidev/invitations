<template>
  <!--
    The real invitation page (with its preview token) in a 390×844 frame, the
    size of a phone held upright, scaled down to fit. The page inside lays
    itself out exactly as on a phone; only the drawing is smaller.
    Changing `reloadKey` reloads it.
  -->
  <div ref="box" class="phone" :style="{ height: `${PHONE_HEIGHT * scale}px` }">
    <div class="phone__screen" :style="{ width: `${PHONE_WIDTH * scale}px`, height: `${PHONE_HEIGHT * scale}px` }">
      <iframe
        :key="reloadKey"
        :src="src"
        title="Invitation preview"
        :style="{ transform: `scale(${scale})` }"
        :width="PHONE_WIDTH"
        :height="PHONE_HEIGHT"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  url: { type: String, required: true },
  reloadKey: { type: Number, default: 0 },
  /** Tallest the frame may be, in px. */
  maxHeight: { type: Number, default: Infinity },
})

const PHONE_WIDTH = 390
const PHONE_HEIGHT = 844

const box = ref(null)
const width = ref(PHONE_WIDTH)
const scale = computed(() => Math.min(1, width.value / PHONE_WIDTH, props.maxHeight / PHONE_HEIGHT))

// A new address per reload: the browser loads it fresh rather than from its history.
const src = computed(() => `${props.url}${props.url.includes('?') ? '&' : '?'}r=${props.reloadKey}`)

let observer = null
onMounted(() => {
  observer = new ResizeObserver(([entry]) => { width.value = entry.contentRect.width })
  observer.observe(box.value)
})
onBeforeUnmount(() => observer?.disconnect())
</script>

<style scoped>
.phone {
  display: flex;
  justify-content: center;
  width: 100%;
}

.phone__screen {
  overflow: hidden;
  border-radius: 24px;
  outline: 8px solid #1f1a19;
  outline-offset: -1px;
  background: #1f1a19;
  box-shadow: 0 10px 30px rgb(0 0 0 / 0.18);
}

iframe {
  display: block;
  border: 0;
  transform-origin: 0 0;
}
</style>
