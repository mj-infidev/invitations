<template>
  <!--
    Asks why before an action that is logged with a reason: disabling, mark
    paid, removing a photo. The reason goes into the admin log with the admin's
    name. `withReference` adds an optional payment reference (mark paid).
  -->
  <dialog ref="dialog" class="dialog" @close="$emit('close')">
    <form method="dialog" class="stack" @submit.prevent="submit">
      <h2>{{ title }}</h2>
      <p v-if="description" class="muted">{{ description }}</p>

      <label class="field">
        {{ reasonLabel }}
        <textarea v-model="reason" rows="3" :minlength="minLength" maxlength="500" required />
        <span class="muted small">At least {{ minLength }} characters. Saved in the history with your name.</span>
      </label>

      <label v-if="withReference" class="field">
        Payment reference (optional)
        <input v-model="reference" type="text" maxlength="100" placeholder="UPI transaction number, receipt…" />
      </label>

      <p v-if="error" class="notice" role="alert">{{ error }}</p>

      <div class="actions">
        <button type="button" class="button button--quiet" @click="close">Cancel</button>
        <button type="submit" class="button" :class="{ 'button--danger': danger }" :disabled="busy || reason.trim().length < minLength">
          {{ busy ? 'Working…' : confirmLabel }}
        </button>
      </div>
    </form>
  </dialog>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  reasonLabel: { type: String, default: 'Reason' },
  confirmLabel: { type: String, default: 'Confirm' },
  minLength: { type: Number, default: 5 },
  withReference: { type: Boolean, default: false },
  danger: { type: Boolean, default: false },
  /** Runs the action with { reason, reference }; a thrown error is shown in the dialog. */
  action: { type: Function, required: true },
})

const emit = defineEmits(['close', 'done'])

const dialog = ref(null)
const reason = ref('')
const reference = ref('')
const error = ref('')
const busy = ref(false)

onMounted(() => dialog.value.showModal())

function close() {
  dialog.value.close()
}

async function submit() {
  busy.value = true
  error.value = ''
  try {
    const result = await props.action({ reason: reason.value.trim(), reference: reference.value.trim() || undefined })
    emit('done', result)
    close()
  } catch (e) {
    error.value = e.messageFor?.('reason') ?? e.message
  } finally {
    busy.value = false
  }
}
</script>

<style scoped>
.dialog {
  width: min(92vw, 480px);
  padding: 20px;
  border: 0;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-dialog);
}

.dialog::backdrop { background: color-mix(in srgb, var(--color-shadow) 35%, transparent); }
.dialog h2 { margin: 0; font-size: 1.15rem; }
.actions { display: flex; justify-content: flex-end; gap: 10px; }
</style>
