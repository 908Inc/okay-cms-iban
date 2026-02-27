# OkayCMS IBAN invoice (Opendatabot)

OkayCMS **4.x** payment module that creates an IBAN invoice via Opendatabot and redirects the customer to the invoice page.

Docs:
- Opendatabot IBAN API: https://iban.opendatabot.ua/api-docs
- Form example: https://iban.opendatabot.ua/create-invoice

## What it does

- Adds a payment handler: **IBAN invoice (Opendatabot)**
- On the payment step (after order is created) renders a `POST` form to a local module route:
  - `payment/Opendatabot/IbanInvoice/create-invoice`
- The module then makes a **server-side** request to Opendatabot (`https://iban.opendatabot.ua/api/invoice`) and redirects the customer to the invoice page.
- `apiKey` (`x-client-key`) is stored in the admin settings.
- Settings in admin payment method:
  - `IBAN`
  - `Code (TIN/EDRPOU)`
  - `apiKey` (`x-client-key`) (leave empty → public key is saved automatically)
  - `clientName` (`x-client-name`) (leave empty → `public` is saved automatically)
  - `purpose` (optional, defaults to `Оплата за замовлення №%order_id%`)

Limitations (MVP):
- **UAH only** (payment method is hidden when storefront currency is not UAH)
- No callback/webhook handling (order remains unpaid until you confirm manually)

## Install (store owner)

1) Copy the module folder into your store:
   - from this repo: `src_okay4/Okay/Modules/Opendatabot`
   - into your store root as: `Okay/Modules/Opendatabot`
2) In admin → **Modules** (`backend/index.php?controller=ModulesAdmin`):
   - Find `Opendatabot/IbanInvoice`
   - Click **Install** (if not installed) and make sure it is **Enabled**
3) In admin → **Settings → Payment** (`backend/index.php?controller=PaymentMethodsAdmin`):
   - Create a new payment method
   - (optional) Enable **Auto-redirect** to submit the payment form right after the order is created (otherwise customer lands on the order page and clicks the pay button there)
   - Set **Type** to `Opendatabot/IbanInvoice`
   - Set **Currency** to **UAH**
   - (optional) Upload payment logo image (recommended size: **80×30**)
     - Included in this repo: `src_okay4/Okay/Modules/Opendatabot/IbanInvoice/assets/payment-icon-80x30.png`
     - More icon variants (source images): `iban_icons/`
4) Fill module settings (same page):
   - `IBAN` — **required** receiver IBAN (e.g. `UAxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - `Code (TIN/EDRPOU)` — **required** receiver code: 8 digits (EDRPOU) or 10 digits (TIN)
   - `Key (x-client-key)` — leave empty to use the public key (will be saved automatically)
   - `Client name (x-client-name)` — leave empty to use `public` (will be saved automatically)
   - `Payment purpose (use %order_id%)` — optional; defaults to `Оплата за замовлення №%order_id%`
5) Save the payment method.

## Manual test

1) Place an order in **UAH** and choose **IBAN invoice (Opendatabot)**.
2) On the payment step/page you should see a button:
   - UA: **"Оплатити IBAN рахунок"**
   - EN: **"Pay IBAN invoice"**
3) Click it → you should be redirected to the Opendatabot invoice page.

## Dev (Docker)

Run all commands from the project root:

1) Copy env file:
   - `cp dev/okay4/.env.example dev/okay4/.env`
2) Fetch OkayCMS sources into `dev/okay4/okaycms/`:
   - `./dev/okay4/scripts/fetch-okaycms.sh`
3) Start containers:
   - `docker compose --env-file dev/okay4/.env -f dev/okay4/docker-compose.yml up -d --build`
4) Install OkayCMS dependencies (first run):
   - `docker compose --env-file dev/okay4/.env -f dev/okay4/docker-compose.yml exec php composer install`
5) Configure OkayCMS (`config/config.local.php`) for Docker:
   - `./dev/okay4/scripts/configure-okaycms.sh`
6) Import demo DB (drops/recreates tables):
   - `./dev/okay4/scripts/import-db.sh`
7) Open the store:
   - http://localhost:8080
8) Open Adminer (DB UI):
   - http://localhost:8081
   - Server: `db`
   - User/Password/DB: from `dev/okay4/.env`

Admin:
- URL: http://localhost:8080/backend/
- Login: `admin`
- Password: `1234`

If you run the installer, use DB settings from `dev/okay4/.env`:
- host: `db`
- port: `3306`
- database/user/password: `OKAY4_DB_*`

The module is mounted into the container at:
- `/var/www/okaycms/Okay/Modules/Opendatabot/IbanInvoice`
