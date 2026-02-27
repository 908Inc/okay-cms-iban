#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env"
COMPOSE_FILE="${ROOT_DIR}/docker-compose.yml"
OKAYCMS_DIR="${ROOT_DIR}/okaycms"
SQL_FILE="${OKAYCMS_DIR}/1DB_changes/okay_clean.sql"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing env file: ${ENV_FILE}"
  echo "Create it first: cp ${ROOT_DIR}/.env.example ${ENV_FILE}"
  exit 1
fi

if [[ ! -f "${SQL_FILE}" ]]; then
  echo "SQL dump not found: ${SQL_FILE}"
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

echo "Waiting for DB..."
db_ready=0
for i in {1..30}; do
  if docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" exec -T db \
    mysql --connect-timeout=1 -u"${DB_USER}" -p"${DB_PASS}" -e "SELECT 1" >/dev/null 2>&1; then
    db_ready=1
    break
  fi
  sleep 1
done

if [[ "${db_ready}" != "1" ]]; then
  echo "DB is not ready after 30 seconds."
  exit 1
fi

echo "Importing ${SQL_FILE} into database \"${DB_NAME}\"..."
docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" exec -T db \
  mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${SQL_FILE}"

echo "Done."
