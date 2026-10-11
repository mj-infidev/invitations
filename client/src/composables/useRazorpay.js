/**
 * Razorpay Checkout: loads checkout.js once, opens it for an order the API
 * created, and resolves with what happened:
 *  - { status: 'paid', response }   Razorpay's three signed values, for /api/payments/verify
 *  - { status: 'failed', reason }   an attempt failed and the customer then closed the window
 *  - { status: 'closed' }           closed without paying
 * Razorpay lets the customer retry inside its window after a failure, so a
 * failure only counts once the window is closed.
 */
const SCRIPT = 'https://checkout.razorpay.com/v1/checkout.js'

let loading = null

function loadCheckout() {
  loading ??= new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = SCRIPT
    script.onload = resolve
    script.onerror = () => {
      loading = null // try again next time
      reject(new Error("Couldn't open the payment window. Check your connection and try again."))
    }
    document.head.appendChild(script)
  })
  return loading
}

/** @param {object} checkout  The `data` of POST /api/invitations/{id}/checkout. */
export async function openCheckout(checkout) {
  await loadCheckout()

  return new Promise((resolve) => {
    let reason = null
    const razorpay = new window.Razorpay({
      key: checkout.key_id,
      order_id: checkout.order_id,
      amount: checkout.amount,
      currency: checkout.currency,
      name: checkout.name,
      description: checkout.description,
      prefill: checkout.prefill,
      // Razorpay's window in the site's accent colour, from theme.css.
      theme: { color: getComputedStyle(document.documentElement).getPropertyValue('--color-accent').trim() },
      handler: (response) => resolve({ status: 'paid', response }),
      modal: {
        ondismiss: () => resolve(reason ? { status: 'failed', reason } : { status: 'closed' }),
      },
    })
    razorpay.on('payment.failed', (event) => {
      reason = event.error?.description || 'The payment did not go through.'
    })
    razorpay.open()
  })
}
