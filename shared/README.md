# shared/

Vue code imported by both `client/` and `admin/` through the `@shared` alias
(see each app's `vite.config.js`). There is no `package.json` here: an
`import 'vue'` resolves to the importing app's copy.

First planned resident: the invitation details form, used by the customer
builder (Step 4) and by the admin invitation edit screen (Step 6).
