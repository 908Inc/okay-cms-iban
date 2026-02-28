#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env"
OKAYCMS_DIR="${ROOT_DIR}/okaycms"
CONFIG_LOCAL_FILE="${OKAYCMS_DIR}/config/config.local.php"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing env file: ${ENV_FILE}"
  echo "Create it first: cp ${ROOT_DIR}/.env.example ${ENV_FILE}"
  exit 1
fi

if [[ ! -f "${OKAYCMS_DIR}/index.php" ]]; then
  echo "OkayCMS not found at: ${OKAYCMS_DIR}"
  echo "Run: ${ROOT_DIR}/scripts/fetch-okaycms.sh"
  exit 1
fi

set -a
# shellcheck source=/dev/null
source "${ENV_FILE}"
set +a

DB_NAME="${OKAY3_DB_NAME:-okaycms3}"
DB_USER="${OKAY3_DB_USER:-okaycms3}"
DB_PASS="${OKAY3_DB_PASS:-okaycms3}"

mkdir -p "$(dirname "${CONFIG_LOCAL_FILE}")"

cat > "${CONFIG_LOCAL_FILE}" <<EOF
;<? exit(); ?>

[database]
db_server = db
db_user = "${DB_USER}"
db_password = "${DB_PASS}"
db_name = "${DB_NAME}"

[php]
debug_mode = true
EOF

echo "Wrote: ${CONFIG_LOCAL_FILE}"

# OkayCMS 3.x expects these directories to exist (git repo may not include them).
# - `compiled/` is used by Smarty (compiled templates)
# - `cache/css` and `cache/js` are used by TemplateConfig (compiled assets)
mkdir -p "${OKAYCMS_DIR}/compiled" "${OKAYCMS_DIR}/cache/css" "${OKAYCMS_DIR}/cache/js"

# OkayCMS 3.x enables `error_reporting(E_ALL)` in debug mode which includes deprecations on PHP 7.4+.
# Deprecation output breaks redirects (headers already sent). Keep debug on, but hide deprecations.
for entrypoint in "${OKAYCMS_DIR}/index.php" "${OKAYCMS_DIR}/backend/index.php"; do
  if [[ -f "${entrypoint}" ]]; then
    LC_ALL=C LANG=C perl -pi -e 's/error_reporting\s*\(\s*E_ALL\s*\)\s*;/error_reporting(E_ALL \& ~E_DEPRECATED \& ~E_USER_DEPRECATED);/g' "${entrypoint}"
  fi
done

# OkayCMS 3.x demo license contains `localhost` (without port), but in dev we run it on a high port
# (e.g. `localhost:8090`). The bundled license checker compares `getenv("HTTP_HOST")` literally and
# marks the license as invalid if a port is present. Patch it to ignore the `:<port>` suffix.
LICENSE_FILE="${OKAYCMS_DIR}/vendor/okaycms/license/src/OkayLicense/License.php"
if [[ -f "${LICENSE_FILE}" ]]; then
  LC_ALL=C LANG=C perl -pi -e 's/private static function sp417ef2\(\) \{ return getenv\(\x27HTTP_HOST\x27\); \}/private static function sp417ef2() { \$host = getenv(\x27HTTP_HOST\x27); if (\$host === false) { return \x27\x27; } return preg_replace(\x27~:\\d+\$~\x27, \x27\x27, \$host); }/g' "${LICENSE_FILE}"
fi

# Smarty templates in OkayCMS 3.x set `error_reporting` to `E_ALL & ~E_NOTICE`, which re-enables deprecations
# during template rendering and can corrupt generated URLs (e.g. `{url ...}` in the backend).
DESIGN_FILE="${OKAYCMS_DIR}/Okay/Core/Design.php"
if [[ -f "${DESIGN_FILE}" ]]; then
  LC_ALL=C LANG=C perl -pi -e 's/\$this->smarty->error_reporting\s*=\s*E_ALL\s*&\s*~E_NOTICE\s*;/\$this->smarty->error_reporting = E_ALL \& ~E_NOTICE \& ~E_DEPRECATED \& ~E_USER_DEPRECATED;/g' "${DESIGN_FILE}"
fi

# Fix a PHP 7.4 warning when opening "Add payment method" (no id provided):
# BackendPaymentsHelper::getPaymentMethod() assigns properties on `null`.
PAYMENTS_HELPER_FILE="${OKAYCMS_DIR}/backend/Helpers/BackendPaymentsHelper.php"
if [[ -f "${PAYMENTS_HELPER_FILE}" ]] && ! grep -q "if (empty(\\\$payment))" "${PAYMENTS_HELPER_FILE}"; then
  LC_ALL=C LANG=C perl -pi -e 's/\$payment\s*=\s*\$this->paymentMethodsEntity->get\(\$id\);\s*/\$payment = \$this->paymentMethodsEntity->get(\$id);\n        if (empty(\$payment)) { \$payment = new \\stdClass(); }\n        /' "${PAYMENTS_HELPER_FILE}"
fi
