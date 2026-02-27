# OkayCMS IBAN invoice (Opendatabot)

Payment extension for:
- **OkayCMS 4.x** — sources in `src_okay4/`

Creates an IBAN invoice via Opendatabot and redirects the customer to the invoice page.

## What it does

- Adds a checkout payment method: **IBAN invoice (Opendatabot)**
- Builds invoice payload from the order
- Creates invoice via Opendatabot API and redirects to the invoice page
- Admin settings:
  - IBAN
  - Code (TIN/EDRPOU)
  - API key (`x-client-key`) (optional; leave empty → public key is saved automatically)
  - Client name (`x-client-name`) (optional; leave empty → `public` is saved automatically)
  - Payment purpose template (supports `%order_id%`)
  - Auto-redirect (OkayCMS payment setting)

Limitations (current MVP):
- **UAH only**
- No callback/webhook handling

## Repo layout

- `src_okay4/` — OkayCMS 4.x module sources (copied into `Okay/Modules/Opendatabot/IbanInvoice`)
- `dev/okay4/` — Docker sandbox store (OkayCMS 4.x)
- `iban_icons/` — logo/icon sources (optional)

## Install (as a store owner would)

1) Copy module sources into your store root:
   - copy from this repo: `src_okay4/Okay/Modules/Opendatabot`
   - into your store as: `Okay/Modules/Opendatabot`
2) Admin → **Modules** (`backend/index.php?controller=ModulesAdmin`):
   - Find `Opendatabot/IbanInvoice`
   - Click **Install** (if not installed) and make sure it is **Enabled**
3) Admin → **Settings → Payment** (`backend/index.php?controller=PaymentMethodsAdmin`):
   - Create a new payment method
   - Set **Type** to `Opendatabot/IbanInvoice`
   - Set **Currency** to **UAH**
   - (optional) Enable **Auto-redirect** to submit the payment form right after the order is created
     - if disabled, customer lands on the order page and clicks the pay button there
   - (optional) Upload payment logo image (recommended size: **80×30**)
     - ready-to-use logo: `src_okay4/Okay/Modules/Opendatabot/IbanInvoice/assets/payment-icon-80x30.png`
     - more icon variants (source images): `iban_icons/`
4) Fill module settings (same page):
   - `IBAN` — **required** receiver IBAN (e.g. `UAxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - `Code (TIN/EDRPOU)` — **required** receiver code: 8 digits (EDRPOU) or 10 digits (TIN)
   - `Key (x-client-key)` — leave empty to use the public key (saved automatically)
   - `Client name (x-client-name)` — leave empty to use `public` (saved automatically)
   - `Payment purpose` — optional; supports `%order_id%` (default: `Оплата за замовлення №%order_id%`)
5) Save the payment method.

## Docker sandbox (for development)

Prereqs:
- Docker Desktop / Docker Engine + Compose v2

```bash
cp dev/okay4/.env.example dev/okay4/.env
./dev/okay4/scripts/fetch-okaycms.sh
docker compose --env-file dev/okay4/.env -f dev/okay4/docker-compose.yml up -d --build

# First run only:
docker compose --env-file dev/okay4/.env -f dev/okay4/docker-compose.yml exec php composer install

./dev/okay4/scripts/configure-okaycms.sh
./dev/okay4/scripts/import-db.sh
```

- Store: `http://localhost:8080/`
- Admin: `http://localhost:8080/backend/` (default `admin` / `1234`)
- Adminer: `http://localhost:8081/` (default server: `db`)

This mode mounts local module sources into the container:
- `/var/www/okaycms/Okay/Modules/Opendatabot/IbanInvoice`

Reset sandbox (wipe DB + files):

```bash
docker compose --env-file dev/okay4/.env -f dev/okay4/docker-compose.yml down -v --remove-orphans
```

## Currency (UAH)

The payment method appears **only** when the storefront currency is **UAH**.

1) Admin → **Settings → Currency**: ensure **UAH** exists and is enabled
2) Switch storefront currency to UAH (or use incognito / clear currency cookies/session)

## References

- OkayCMS (official repo): https://github.com/OkayCMS/OkayCMS
- Opendatabot IBAN API: https://iban.opendatabot.ua/api-docs
- Opendatabot form example: https://iban.opendatabot.ua/create-invoice

## Roadmap

- OkayCMS 3.x support (separate implementation).
