# OkayCMS IBAN invoice (Opendatabot)

Payment extension for:
- **OkayCMS 4.x** — sources in `src_okay4/`
- **OkayCMS 3.x** — sources in `src_okay3/`

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
- `src_okay3/` — OkayCMS 3.x module sources (copied into `Okay/Modules/Opendatabot/IbanInvoice`)
- `dev/okay4/` — Docker sandbox store (OkayCMS 4.x)
- `dev/okay3/` — Docker sandbox store (OkayCMS 3.x)
- `iban_icons/` — logo/icon sources (optional)

## Install (as a store owner would)

Pick your OkayCMS version first:
- OkayCMS **4.x** → use sources from `src_okay4/`
- OkayCMS **3.x** → use sources from `src_okay3/`

1) Copy module sources into your store root:
   - copy from this repo:
     - for **OkayCMS 4.x**: `src_okay4/Okay/Modules/Opendatabot`
     - for **OkayCMS 3.x**: `src_okay3/Okay/Modules/Opendatabot`
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
     - ready-to-use logo:
       - OkayCMS **4.x**: `src_okay4/Okay/Modules/Opendatabot/IbanInvoice/assets/payment-icon-80x30.png`
       - OkayCMS **3.x**: `src_okay3/Okay/Modules/Opendatabot/IbanInvoice/assets/payment-icon-80x30.png`
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

### OkayCMS 4.x

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

### OkayCMS 3.x

```bash
cp dev/okay3/.env.example dev/okay3/.env

# Optional: change the OkayCMS 3.x git ref in dev/okay3/.env (default: OkayCMS 3.2.0)
# OKAY3_REF=7f205b74d5e64598286e04eb77603abcc59e0407  # OkayCMS 3.9.0

./dev/okay3/scripts/fetch-okaycms.sh

# If you already have dev/okay3/okaycms and want to re-fetch:
# rm -rf dev/okay3/okaycms && ./dev/okay3/scripts/fetch-okaycms.sh

docker compose --env-file dev/okay3/.env -f dev/okay3/docker-compose.yml up -d --build

# First run only:
docker compose --env-file dev/okay3/.env -f dev/okay3/docker-compose.yml exec php composer install

./dev/okay3/scripts/configure-okaycms.sh
./dev/okay3/scripts/import-db.sh
```

- Store: `http://localhost:8090/`
- Admin: `http://localhost:8090/backend/` (default `admin` / `1234`)
- Adminer: `http://localhost:8091/` (default server: `db`)

Note: in the 3.x sandbox use `localhost` (not `127.0.0.1`) — the bundled demo license is issued for `localhost`.

If backend links/buttons look broken (e.g. you see `Deprecated: get_magic_quotes_gpc()` inside URLs), re-run:
- `./dev/okay3/scripts/configure-okaycms.sh`
- `docker compose --env-file dev/okay3/.env -f dev/okay3/docker-compose.yml restart php nginx`

Reset sandbox (wipe DB + files):

```bash
docker compose --env-file dev/okay3/.env -f dev/okay3/docker-compose.yml down -v --remove-orphans
```

## Currency (UAH)

The payment method appears **only** when the storefront currency is **UAH**.

1) Admin → **Settings → Currency**: ensure **UAH** exists and is enabled
2) Switch storefront currency to UAH (or use incognito / clear currency cookies/session)

## References

- OkayCMS 4.x (official repo): https://github.com/OkayCMS/OkayCMS
- OkayCMS 3.x (official repo): https://github.com/OkayCMS/Okay3
- Opendatabot IBAN API: https://iban.opendatabot.ua/api-docs
- Opendatabot form example: https://iban.opendatabot.ua/create-invoice
