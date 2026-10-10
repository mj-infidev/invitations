# Invitations: build plan

Oct 10, 2026 · @Msp!

## Overview

A website where a customer signs in, picks an animated invitation template, fills in the event details, pays once, and gets a link to share on WhatsApp. Guests open the link and scroll through animated scenes.

**In version 1**

- One template: Tamil Hindu wedding, with scroll-driven scenes
- Customer self-service: sign in, fill details, preview, pay, share
- Google sign-in
- Tamil and English
- Photo uploads
- Razorpay payment (India only)
- A share link that shows the couple's names and a picture in the WhatsApp preview
- An admin panel to see and fix orders

**Left for later**

- More templates and traditions
- Bride and groom avatar customizer
- Phone OTP login
- RSVP and guest count
- QR code download, coupons, refunds from the panel
- Selling outside India

## Tech stack and folders

The project uses the same three-folder layout as our existing projects: Vue for the two front ends, Laravel for the API, MySQL for data. Use the same Vue and Laravel versions as those projects.

```
invitations/
  client/   Vue app for customers: landing, sign-in, builder, my invitations
  admin/    Vue app for us: orders, invitations, templates, numbers
  api/      Laravel: REST API, MySQL, and the public invitation page (Blade)
  PLAN.md   this document, exported as Markdown
```

| Need | Tool |
| --- | --- |
| Scroll-driven scenes | GSAP with its ScrollTrigger plugin |
| Small looping animations | Lottie (`lottie-web`) |
| Google sign-in | Laravel Socialite |
| Session cookies for the Vue apps | Laravel Sanctum (SPA mode) |
| Payments | Razorpay REST API through Laravel's `Http` client, no SDK (as in nancofills) |
| Image resize and preview image | Intervention Image |
| File storage | Laravel filesystem, public disk to start |
| Translations | `vue-i18n` in the Vue apps, Laravel lang files in the API |

**The invitation viewer lives in `api/`, not `client/`.** The page guests open is a Laravel Blade page, and the animation code (the "viewer") is built by Laravel's own Vite setup in `api/resources/js/viewer/`. The builder in `client/` shows the preview by loading that same page in an iframe. This keeps the template code in one place.

**URL layout**

| URL | Served by |
| --- | --- |
| `oursite.com/` | `client/` |
| `oursite.com/i/{slug}` | `api/` (Blade invitation page) |
| `oursite.com/api/...` | `api/` |
| `admin.oursite.com/` | `admin/` |

The web server needs a rule that sends `/i/` and `/api/` to Laravel. Whether our hosting allows that is an open decision below.

## Customer flow

The customer can see and try everything before paying; payment only removes the watermark and makes the link public.

1. **Landing page.** Shows the template with a live demo invitation to scroll through.
2. **Sign in.** "Create my invitation" opens Google sign-in.
3. **Fill details.** One form, saved as a draft on every change:
   - Bride and groom names, in Tamil and in English
   - Parents' names for both sides
   - Muhurtham date and time
   - Venue name, address, and Google Maps link
   - Other events, such as reception: name, date, time, place
   - Photos of the couple
   - A short message from the family
   - Language shown to guests: Tamil, English, or both
4. **Preview.** The real invitation in an iframe, with a watermark.
5. **Pay.** Razorpay checkout with UPI, cards, and net banking.
6. **Share.** The link, a copy button, and a "Share on WhatsApp" button.
7. **Edit later.** The customer can correct details after paying, until the event date. The link stays the same.

## Database tables

Seven tables cover version 1. Rules for all of them:

- Charset `utf8mb4`, collation `utf8mb4_unicode_ci`, so Tamil text is stored correctly
- Money is stored in paise as an integer (₹499 = 49900), never as a decimal
- Every table has `id`, `created_at`, `updated_at`

**`users`** (customers)

| Column | Type | Notes |
| --- | --- | --- |
| name | string | From Google |
| email | string, unique, nullable | From Google |
| google\_id | string, unique, nullable |  |
| phone | string, unique, nullable | Empty for now; used when OTP login is added |
| avatar\_url | string, nullable |  |
| locale | enum: ta, en | Language of the site for this customer |

**`admins`** (us; kept apart from customers)

| Column | Type | Notes |
| --- | --- | --- |
| name | string |  |
| email | string, unique |  |
| password | string | Hashed |

**`templates`**

| Column | Type | Notes |
| --- | --- | --- |
| slug | string, unique | `tamil-hindu-wedding` |
| name | string |  |
| category | string | `wedding` |
| tradition | string | `tamil_hindu` |
| price\_paise | unsigned integer |  |
| fields\_schema | JSON | The form fields this template needs |
| thumbnail\_path | string |  |
| max\_photos | unsigned tiny integer |  |
| is\_active | boolean | Hidden from customers when false |
| sort\_order | integer |  |

**`invitations`**

| Column | Type | Notes |
| --- | --- | --- |
| user\_id | foreign key → users |  |
| template\_id | foreign key → templates |  |
| slug | string, unique | Names plus random characters: `priya-karthik-x7k2` |
| status | enum: draft, published, expired, disabled |  |
| language | enum: ta, en, both | What guests see |
| title | string | For the WhatsApp preview: "Priya weds Karthik" |
| event\_date | date, indexed | Used for sorting and expiry |
| data | JSON | All template fields: names, parents, events, venue, message |
| og\_image\_path | string, nullable | Preview picture, made when published |
| preview\_token | string | Lets the owner view the draft |
| view\_count | unsigned integer |  |
| published\_at | timestamp, nullable |  |
| expires\_at | timestamp, nullable |  |
| deleted\_at | timestamp, nullable | Soft delete |

**`invitation_media`**

| Column | Type | Notes |
| --- | --- | --- |
| invitation\_id | foreign key → invitations | Delete with the invitation |
| type | enum: photo, music |  |
| path | string |  |
| sort\_order | integer |  |
| width, height | unsigned integer, nullable |  |
| size\_bytes | unsigned integer |  |

**`orders`**

| Column | Type | Notes |
| --- | --- | --- |
| user\_id | foreign key → users |  |
| invitation\_id | foreign key → invitations |  |
| amount\_paise | unsigned integer | Copied from the template at checkout |
| currency | string | `INR` |
| status | enum: created, paid, failed, refunded |  |
| razorpay\_order\_id | string, unique |  |
| razorpay\_payment\_id | string, nullable |  |
| paid\_at | timestamp, nullable |  |

**`payment_events`** (log of Razorpay webhooks)

| Column | Type | Notes |
| --- | --- | --- |
| event\_id | string, unique | Stops the same webhook being processed twice |
| type | string | `payment.captured` and so on |
| payload | JSON |  |
| processed\_at | timestamp, nullable |  |

Relations: a user has many invitations and orders; an invitation belongs to one template, has many media files, and has many orders (a failed payment leaves an order behind). Tables for RSVPs and coupons come later. Step 6 added an eighth table, `admin_actions` (the admin log), and `orders.paid_by_admin_id`; see Settled in Step 6.

**Settled in Step 1**

- `users.password` is a nullable column, unused while sign-in is Google only.
- `invitations.title` and `invitations.event_date` are nullable, because a new draft has no names or date yet. The server fills them from `data` on every save (see below).
- Deleting a customer or template that has invitations is blocked, and so is deleting a customer or invitation that has orders. Only `invitation_media` rows go with their invitation.
- Admins sign in through their own `admin` guard (Sanctum, `admins` table). The customer guard (`sanctum`) accepts only `users` tokens, so neither kind of token works on the other's routes.

## Template form fields (`fields_schema`)

`templates.fields_schema` describes the details form, and `invitations.data` holds the answers. The builder (Step 4) draws the form from the schema, the server validates `data` against it, and the admin edit screen (Step 6) reuses the same form. A new template needs only a new schema, not new form code.

```json
{
  "version": 1,
  "groups": [
    {
      "key": "couple",
      "label": { "en": "The couple", "ta": "மணமக்கள்" },
      "fields": [
        { "key": "bride.name", "type": "text", "bilingual": true, "required": true, "max": 60,
          "label": { "en": "Bride's name", "ta": "மணமகள் பெயர்" } }
      ]
    }
  ]
}
```

**Groups** are the form's sections, in order. Each has a `key`, a `label`, and its `fields`.

**Field properties**

| Property | Meaning |
| --- | --- |
| `key` | Where the answer goes in `data`, as a dot path: `bride.name` is `data.bride.name`. Inside a repeater, keys are relative to one item. |
| `type` | `text`, `textarea`, `date` (`YYYY-MM-DD`), `time` (`HH:MM`, 24-hour), `url`, or `repeater` |
| `label` | `{ "en": …, "ta": … }`; the form shows the one matching the site language |
| `help` | Optional hint under the field, same shape as `label` |
| `required` | Must be filled before payment. A draft may be saved with gaps. |
| `max` | Longest allowed text, in characters |
| `bilingual` | The answer is `{ "ta": …, "en": … }` and the form shows a Tamil box and an English box. `required` then applies only to the languages in `invitations.language`. Names are never auto-translated. |
| `fields`, `max_items` | Repeater only: the fields of one item, and how many items are allowed. The answer is a list of objects. |

**Kept outside `data`:** photos are rows in `invitation_media` (at most `templates.max_photos`), and the language shown to guests is the `invitations.language` column.

**Reserved key `avatar`:** the cartoon couple of the closing scene. It is not a form field: the builder must keep it untouched when saving, and a later couple customizer will edit it. Values are option names, never colours; `App\Support\Avatar` lists the options and maps them to colours, and anything missing or unknown falls back to the default.

```json
"avatar": {
  "version": 1,
  "bride": { "skin": "medium", "hair": "braid", "hair_color": "black", "outfit": "silk_saree", "outfit_color": "maroon", "jewellery": "temple_gold" },
  "groom": { "skin": "medium", "hair": "short", "hair_color": "black", "outfit": "veshti_shirt", "outfit_color": "cream", "jewellery": "chain" },
  "garland": "jasmine_rose"
}
```

**Copied to columns on every save:** `title` = "{bride.name.en} weds {groom.name.en}" (Tamil names when English is empty), and `event_date` = `muhurtham.date`.

**Tamil Hindu wedding `data` keys:** `bride.name`, `groom.name`, `bride.parents`, `groom.parents`, `message`, `muhurtham.date`, `muhurtham.time`, `muhurtham.tamil_date` (free text, not bilingual), `venue.name`, `venue.address`, `venue.map_url`, and `events[]` with `name`, `date`, `time`, `place`. The full schema is in `api/database/seeders/TemplateSeeder.php`, and filled-in demo data is in `DemoInvitationSeeder.php`.

## API routes

Four groups, each with its own access rule. A customer can reach only their own invitations and orders; this is checked on the server with Laravel policies.

| Method | Path | Who | Purpose |
| --- | --- | --- | --- |
| GET | `/i/{slug}` | Anyone | The invitation page (Blade, not JSON) |
| GET | `/api/templates` | Anyone | List active templates |
| GET | `/api/templates/{slug}` | Anyone | One template with its form fields |
| GET | `/api/auth/google/redirect` | Anyone | Start Google sign-in |
| GET | `/api/auth/google/callback` | Anyone | Finish sign-in with a session cookie, return to the client |
| POST | `/api/webhooks/razorpay` | Razorpay | Payment events, signature checked |
| GET | `/api/me` | Anyone | The signed-in customer, or `null` |
| POST | `/api/auth/logout` | Customer |  |
| GET | `/api/invitations` | Customer | My invitations |
| POST | `/api/invitations` | Customer | Create a draft from a template |
| GET | `/api/invitations/{id}` | Customer | One invitation |
| PUT | `/api/invitations/{id}` | Customer | Save details |
| DELETE | `/api/invitations/{id}` | Customer | Delete a draft |
| POST | `/api/invitations/{id}/media` | Customer | Upload a photo |
| DELETE | `/api/invitations/{id}/media/{mediaId}` | Customer | Remove a photo |
| POST | `/api/invitations/{id}/checkout` | Customer | Create a Razorpay order |
| POST | `/api/payments/verify` | Customer | Confirm payment after checkout |
| GET | `/api/admin/csrf-cookie` | Anyone | Start the admin session (its own cookie) |
| POST | `/api/admin/login`, `/api/admin/logout` | Admin | Email and password; logout |
| GET | `/api/admin/me` | Anyone | The signed-in admin, or `null` |
| GET | `/api/admin/stats` | Admin | Sales and counts |
| GET | `/api/admin/invitations` | Admin | Search and list all |
| GET, PUT | `/api/admin/invitations/{id}` | Admin | Everything the edit screen needs; fix details |
| POST | `/api/admin/invitations/{id}/disable`, `/enable` | Admin | With a reason |
| POST | `/api/admin/invitations/{id}/mark-paid` | Admin | Paid outside Razorpay: makes an order and marks it paid |
| DELETE | `/api/admin/invitations/{id}/media/{mediaId}` | Admin | Remove a photo, with a reason |
| GET | `/api/admin/orders` | Admin | List with status filter and search |
| POST | `/api/admin/orders/{id}/mark-paid` | Admin | Unlock by hand, with a reason |
| GET, PUT | `/api/admin/templates` | Admin | Prices, on or off, photo limit |
| GET | `/api/admin/users`, `/api/admin/users/{id}` | Admin | Customer list; one customer's invitations |

## Invitation page and WhatsApp preview

Laravel serves `/i/{slug}` as a Blade page with the couple's details already in the HTML, so WhatsApp can build its preview card without running JavaScript.

The Blade page contains:

- Preview tags filled from the database:

  ```html
  <meta property="og:title" content="Priya weds Karthik">
  <meta property="og:description" content="12 November 2026 · Madurai">
  <meta property="og:image" content="https://oursite.com/storage/og/priya-karthik-x7k2.jpg">
  ```
- `<html lang="ta">` or `lang="en"`, matching the invitation's language
- The invitation's `data` as inline JSON for the viewer to read
- The viewer script and styles, loaded with `@vite`
- `<meta name="robots" content="noindex">`, so invitations stay out of Google search

**Rules**

- A draft opens only with `?preview={preview_token}` and shows a watermark. Without the token it returns 404.
- A published invitation opens for anyone with the link and adds 1 to `view_count`.
- An expired or disabled invitation shows a simple "this invitation has ended" page.
- The preview picture is made when the invitation is published: 1200 × 630 pixels, JPEG, under about 300 KB, with the names and date drawn on the template's cover art. It is remade when the customer edits names or date.

## Animation template: Tamil Hindu wedding

The invitation is seven full-screen scenes. Each scene is pinned while the guest scrolls, and the scroll position plays its animation; then the next scene takes over. The scene list is a first proposal to adjust.

| # | Scene | What the guest sees | Data shown |
| --- | --- | --- | --- |
| 1 | Opening | Pillaiyar suzhi (உ) at the top, a kuthuvilakku lamp lights, the temple gopuram rises in layers | None |
| 2 | The couple | Thoranam and banana trees frame the entrance; the names fade in | Bride and groom names |
| 3 | Families | A kolam draws itself line by line around the text | Parents' names, family message |
| 4 | Muhurtham | Date and time appear with a countdown | Date, time, optional Tamil calendar date |
| 5 | Venue | The mandapam slides in with an "Open in Maps" button | Venue, address, map link, other events |
| 6 | Photos | The couple's photos arrive one by one in frames | Uploaded photos |
| 7 | Welcome | A cartoon couple exchange garlands and fold hands | Closing line, "made with" brand link |

**How it is built**

- One GSAP timeline per scene, tied to scroll with ScrollTrigger (`pin: true`, `scrub: true`).
- Artwork is drawn in separate layers (sky, gopuram, pillars, foreground) and each layer moves at a different speed for depth.
- Small loops such as the lamp flame and swinging garlands are Lottie files.
- Animate only position, scale, rotation, and opacity. Other properties stutter on budget phones.
- Artwork is SVG where possible, otherwise WebP. Each scene loads its images as the guest gets near it.
- Guests who have "reduce motion" turned on get simple fades with no pinning.
- Background music is optional and starts only when the guest taps a button, because browsers block sound that plays by itself.
- Design for a phone held upright first, then widen for tablets and desktops.

**Artwork**

The code is the smaller half; the look depends on the illustrations. Build with simple placeholder shapes first so coding can go ahead, then swap in the real layered artwork. Who draws it is an open decision below.

## Tamil language support

Tamil works throughout: in the database, on the site, and inside the animated scenes.

- **Storage.** `utf8mb4` tables store Tamil text as typed. Nothing else is needed in MySQL.
- **Fonts.** Use Tamil web fonts from Google Fonts, for example Noto Serif Tamil for names and headings and Noto Sans Tamil for body text. Host the font files on our own server and load only the weights used, since Tamil fonts are larger than English ones.
- **Site text.** Buttons, labels, and messages are translated with `vue-i18n` in the Vue apps and Laravel lang files in the API. The customer switches between தமிழ் and English.
- **Invitation text.** The customer types names and messages in Tamil with their phone's Tamil keyboard. The form has a Tamil field and an English field for each name. Never auto-translate names.
- **Dates.** Show Tamil day and month names in Tamil invitations. The Tamil calendar date (for example ஐப்பசி 26) is a free-text field the family fills in, not calculated.

**Cautions for animation and layout**

- Never animate Tamil text letter by letter. Tamil letters join with vowel signs, and splitting them breaks the word on screen. Animate by word or by line.
- Tamil text runs longer than the same text in English. Let names wrap to two lines and use a line height of about 1.6.
- Test every scene with long names in both languages.

## Admin panel

The admin app in `admin/` has six screens in version 1. Admins log in with email and password from the `admins` table, separate from customers.

| Screen | What it does |
| --- | --- |
| Login | Email and password |
| Dashboard | Sales today and this month, paid orders, drafts, total views |
| Invitations | Search by name, email, or slug; open one; fix details; copy link; disable |
| Orders | Filter by paid, failed, created; mark an order paid by hand |
| Templates | Set price, turn a template on or off |
| Customers | List with their invitations |

Fixing a customer's details will be the most-used feature, so the invitation edit screen reuses the same form as the customer builder.

## Payments

An invitation is published only after our server has confirmed the payment with Razorpay. The browser saying "paid" is never enough.

1. The customer clicks Pay. The client calls `POST /api/invitations/{id}/checkout`.
2. The server reads the price from the `templates` table, creates a Razorpay order, saves a row in `orders` with status `created`, and returns the order ID. The price never comes from the browser.
3. The client opens Razorpay Checkout with that order ID.
4. After payment, the client sends the payment ID, order ID, and signature to `POST /api/payments/verify`. The server checks the signature with our Razorpay secret.
5. Razorpay also calls `POST /api/webhooks/razorpay` with `payment.captured`. This covers the case where the customer closes the browser before step 4. The server checks the webhook signature and records the event in `payment_events`.
6. Whichever of steps 4 and 5 arrives first marks the order `paid`, sets the invitation to `published`, and makes the preview picture. The second one finds the order already paid and does nothing.

Build and test all of this with Razorpay's test mode keys. Keys live in `.env`, never in the code. We never see or store card details; Razorpay handles them.

## Security and privacy

We hold families' names, photos, and event addresses, so these rules apply from the first step.

- **Access checks on the server.** Every customer route checks that the invitation or order belongs to the signed-in user. Every admin route checks the admin login. Hiding a page in Vue is not a check.
- **Links that can't be guessed.** Slugs end in random characters, and drafts need the preview token.
- **Uploads.** Accept only JPEG, PNG, and WebP, up to 5 MB each. Re-save every photo on the server at a smaller size; this also removes hidden location data from the file.
- **Rate limits.** Limit sign-in, upload, and checkout calls per user and per IP address.
- **Webhooks.** Reject any Razorpay webhook whose signature does not match.
- **Secrets.** Google, Razorpay, and database keys stay in `.env`, which is never committed. Commit `.env.example` with empty values.
- **Retention.** Invitations expire a set time after the event date, and their photos are deleted then. The period is an open decision below.
- **Admin view.** Admins see customer data only on the screens that need it.

## Build order

Nine steps, one per coding session. The animated page comes second, before login and payments, because it is the product; if it does not look good, nothing else matters.

| Step | What gets built | Done when |
| --- | --- | --- |
| 1 | Project setup: three folders, Laravel install, `.env.example`, `utf8mb4` config, migrations and models for all seven tables, seeders (one template, one admin, one demo invitation) | `php artisan migrate --seed` runs clean and the tables show in Adminer |
| 2 | Invitation page: `/i/{slug}` Blade route, preview tags, viewer with GSAP, all seven scenes with placeholder art | The demo invitation scrolls smoothly on a budget Android phone |
| 3 | Google sign-in with Socialite and Sanctum; `/api/me` | A customer can sign in and out from `client/` |
| 4 | Builder in `client/`: template list, details form, photo upload, preview iframe, my invitations | A customer can create a draft and see it in preview with the watermark |
| 5 | Razorpay in test mode: checkout, verify, webhook, publish, preview picture | A test payment publishes the invitation and the WhatsApp card shows names and picture |
| 6 | Admin panel: the six screens | We can find an order, fix a spelling, and mark an order paid |
| 7 | Design pass: real layered artwork for all seven scenes, text size and hierarchy, a proper desktop layout, the "has ended" page showing one language | Every scene has its final art and reads well on a budget phone and on a desktop |
| 8 | Tamil: site translations, fonts, Tamil dates, long-name tests | The whole flow works in Tamil from sign-in to shared link |
| 9 | Speed tuning, live Razorpay keys, launch | First real customer |

**Notes for later steps**

- **Step 8:** the Tamil words on the invitation page (`api/lang/ta/invitation.php`) and the form labels need a native speaker's check.
- **Step 8:** admin search does not find Tamil names, because `invitations.data` stores Tamil escaped (`ப…`). Store JSON with Tamil unescaped (`JSON_UNESCAPED_UNICODE`) and fix the existing rows with a migration that re-saves them.
- **Before launch:** Google blocks its sign-in inside in-app browsers (Instagram, Facebook), where many links opened from those apps land. The sign-in page needs an "open in your browser" hint, and this is a reason to add phone OTP later.
- **Step 7 (design pass):** consider drawing the WhatsApp picture in the customer's browser at publish, so Tamil names can appear on it. The server draws it with GD, which can't shape Tamil (vowel signs land in the wrong place), so today the picture carries the English names, and a Tamil-only invitation's picture shows a large உ and the date instead.
- **Before launch:** the live server needs PHP's **GD extension** (photos and the WhatsApp picture are drawn with it; locally it was switched on in `C:\xampp\php\php.ini`, with the original kept as `php.ini.backup`), a `memory_limit` of at least 256M for large phone photos, and `php artisan storage:link`.
- **Before launch:** live Razorpay keys, and a webhook in the live dashboard: `https://<domain>/api/webhooks/razorpay`, events `payment.captured` and `payment.failed`, with the same secret as `RAZORPAY_WEBHOOK_SECRET`. How long an invitation stays live after its event (`expires_at`) is still open; it is left empty at publish.
- **Before launch:** the Google OAuth client needs the production redirect URI (`https://<domain>/api/auth/google/callback`), and the consent screen must be published out of Testing.

**Settled in Step 2**

- The page is server-rendered Blade with every scene in its finished state; the viewer script only animates it. With reduced motion, or if the script fails, guests still see the whole invitation.
- Scroll catch-up is `scrub: 0.5` rather than `true`: it smooths the steps between touch-scroll events.
- The kolam draws by animating `stroke-dashoffset`, the one exception to "position, scale, rotation and opacity only". It is a small SVG, so the repaint is cheap.
- The demo (`priya-karthik-demo`) moves its dates at every page load so the muhurtham is 45 days away, and drops its Tamil calendar date, which would no longer match.
- A published invitation with no photos skips the photo scene; the demo and draft previews show empty frames instead.
- Views are not counted for the owner's preview link or for WhatsApp and other apps fetching the link for a preview card.
- The invitation page runs without a session, so guest visits write nothing and set no cookies.
- An expired or disabled invitation answers 410 with the "has ended" page.
- A root `.htaccess` blocks Apache from serving the project folder, as in nancofills; XAMPP was serving `api/.env` and `.git` to the local network.
- Tamil and English never share a screen. A "both" invitation shows one language at a time, with a small தமிழ் / English switch, opening in English for now. A language left blank is not filled from the other, so the Step 4 form must require every language the invitation shows.
- In Tamil view every character, digits and punctuation included, is drawn by a Tamil font. Fonts are self-hosted: Tiro Tamil (Tamil names and headings, kept until the Step 8 review), Anek Tamil (Tamil body), Great Vibes (English names), Cormorant Garamond (English headings and body, set a size up).

**Settled in Step 3**

- Customers sign in with Google through Socialite's redirect flow and stay signed in with an HttpOnly session cookie (Sanctum SPA mode), not API tokens. "Remember me" is on.
- Two session guards: `web` for customers, `admin` for admins. Customer routes use `auth:web`, admin routes `auth:admin`, never `auth:sanctum`. Customer sign-out leaves an admin signed in on the same cookie.
- The two Google routes live in `routes/web.php` (Socialite needs the session between them) but answer under `/api/auth/google/`.
- An account is found by Google id, then by email only when Google has verified it; an unverified email is never stored.
- The two Vue apps' addresses are in Sanctum's stateful list (`config/sanctum.php`, from `FRONTEND_CLIENT_URL` and `FRONTEND_ADMIN_URL`).
- Sign-in cannot be tested from a phone on the local network: Google refuses redirect addresses on a private IP.

**Settled in Step 4**

- The builder saves the whole form 0.8 s after the customer stops typing, one save at a time. Drafts save with gaps; the server answers with the "still needed" list (required fields for every language the invitation shows).
- Saving keeps only the template's fields plus `avatar`, and fills `title` and `event_date`. Map links must be http(s).
- A new draft gets a random placeholder slug; the name-based link is set at publish (Step 5).
- Photos: JPEG, PNG or WebP by content, up to 5 MB, within `max_photos` (checked under a row lock). Each is re-saved as WebP, quality 80, at most 1600 px, turned upright, with metadata stripped, under `storage/app/public/invitations/{id}/`.
- Deleting a draft removes its media rows and files in code, then soft-deletes it. Only drafts can be deleted; a published invitation can be edited until its event date.
- Preview: the real page with the preview token in a 390×844 frame. Desktop reloads it 3 s after the last save; phones load it when the Preview tab opens. It keeps its scroll position across reloads, and only our own sites may frame an invitation (`frame-ancestors`).
- Phone form: collapsible sections, one open at a time, with a tick on each section that has nothing required missing. Desktop shows every section open.
- The form renderer (`shared/components/TemplateFields.vue`, `FormSection.vue`) is shared, for the admin edit screen in Step 6.

**Settled in Step 5**

- Razorpay through its REST API with Laravel's `Http` client (`App\Support\Razorpay`), not the SDK. Capture is set on each order (`payment_capture: 1`), not by the account setting: the test account is shared.
- Checkout creates the Razorpay order at the template's price and an `orders` row (`created`). Verify (from the browser: HMAC of `order_id|payment_id` with the key secret) and the webhook (HMAC of the raw body with the webhook secret) both go through `PaymentRecorder`, under a lock on the order: whichever arrives first marks it paid and publishes; the second changes nothing.
- Every webhook is logged in `payment_events`; a repeated event id is ignored. Unknown orders (the shared test account) and unhandled events answer 200. A captured amount that doesn't match the order is logged and not published. `payment.failed` marks an unpaid order `failed`, which a later success can still turn `paid`.
- Publishing: a name-based link (English names, else `wedding-` for a Tamil-only invitation) plus 4 random characters, fixed for good; status `published`, no watermark. The WhatsApp picture (1200 × 630 JPEG, about 55 KB) is drawn after the payment commits, and redrawn under a new file name only when the names or date change.
- Locally, Razorpay can't reach the webhook: `php artisan payments:replay-webhook {razorpay_order_id}` fetches the order's real test payment, signs the event with `RAZORPAY_WEBHOOK_SECRET` and posts it to our webhook.
- After paying: a Share page with the link, Copy, and Share on WhatsApp (a short message in the invitation's language). A cancelled or failed payment leaves the draft as it was, with a message; if the verify call fails, the builder waits up to a minute for the webhook before saying so.
- Razorpay's Checkout asks for a mobile number before paying (we don't collect one).

**Settled in Step 6**

- The admin panel has its own session cookie, separate from customers': `invitations_admin_session`, sent only to `/api/admin`, 8 hours, ending when the browser closes, `SameSite=Strict`, no "remember me"; its CSRF cookie is `XSRF-TOKEN-ADMIN` (`UseAdminSession`, our `ValidateCsrfToken` and `EnsureFrontendRequestsAreStateful`). Admin routes use `auth:admin`; a customer session gets 401 there.
- Sign-in: the same message for a wrong email or password; 5 failures per email and address lock it for a minute. Admins are added with `php artisan admin:create` (name, email, password typed twice); there is no screen for managing admins yet.
- Every admin action is logged in a new eighth table, `admin_actions` (admin, action, subject, reason, before and after per changed field, IP, time), written by `App\Support\AdminLog` in the same transaction as the change; the app never edits or deletes a row. Sign-ins and failed sign-ins are logged too.
- Mark paid by hand goes through the same `PaymentRecorder` as Razorpay (same publishing, link and picture), with a required reason (10+ characters) and an optional reference. `orders.paid_by_admin_id` records the admin; `orders.razorpay_order_id` is nullable for orders made by hand. A draft with no order gets one at the template's price.
- The invitation edit screen reuses the builder's shared form (`TemplateFields`, `FormSection`, and now `PhonePreview`), saves with an explicit Save that lists every change, and logs each changed field. Admins can edit in any status; customers stop at the event date. Disabling, enabling and removing a photo need a reason.
- Searching invitations covers the title (English names), link and customer; Tamil names aren't searchable yet, because `data` stores them escaped.

**First message to give Claude in the IDE**

Save this document as `PLAN.md` in the project's root folder, then send:

```
Read PLAN.md fully. Do Step 1 of the build order only.
Follow the folder layout, package versions, and coding conventions
of our existing project at <path to an existing project>.
List anything in the plan that is unclear before you start,
and stop for my review when Step 1 is done.
```

## Open decisions

None of these block Step 1. The first three are needed before Step 2 goes live on a server.

- [ ] **Hosting.** Can our server send `/i/` and `/api/` to Laravel on the main domain? If not, invitation links would sit on a subdomain.
- [ ] **Domain and brand name.** The name appears at the bottom of every invitation.
- [x] **Versions.** Same as nancofills: Laravel 12 with Sanctum 4 on PHP 8.2; Vue 3.5, Vite 8, vue-router 4, Pinia 4.
- [ ] **Artwork.** Who draws the layered temple, mandapam, and couple illustrations.
- [ ] **Price.** What one invitation costs.
- [ ] **Photos.** How many photos a customer can upload.
- [ ] **Retention.** How long after the event an invitation stays live.
- [ ] **Edits after payment.** Free until the event date, or limited to a number of changes.
- [ ] **Razorpay account.** Live payments need Razorpay's business verification; test mode works without it.
- [ ] **Scene list.** Confirm or change the seven scenes.
