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

DB_NAME="${OKAY4_DB_NAME:-okaycms}"
DB_USER="${OKAY4_DB_USER:-okaycms}"
DB_PASS="${OKAY4_DB_PASS:-okaycms}"

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

