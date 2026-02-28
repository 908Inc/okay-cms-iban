#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST_DIR="${ROOT_DIR}/okaycms"
ENV_FILE="${ROOT_DIR}/.env"
DEFAULT_REF="7562e74b456761475fb4a9386bc4995d94f1ecdd" # OkayCMS 3.2.0

PRESET_REF="${OKAY3_REF-}"

if [[ -f "${ENV_FILE}" ]]; then
  set -a
  # shellcheck source=/dev/null
  source "${ENV_FILE}"
  set +a
fi

if [[ -n "${PRESET_REF}" ]]; then
  REF="${PRESET_REF}"
else
  REF="${OKAY3_REF:-${DEFAULT_REF}}"
fi

if [[ -d "${DEST_DIR}/.git" ]]; then
  echo "OkayCMS already exists at: ${DEST_DIR}"
  exit 0
fi

rm -rf "${DEST_DIR}"
mkdir -p "${DEST_DIR}"

echo "Cloning OkayCMS 3.x (ref: ${REF}) into ${DEST_DIR} ..."
git clone --depth 1 https://github.com/OkayCMS/Okay3.git "${DEST_DIR}"

if [[ "${REF}" != "master" ]]; then
  git -C "${DEST_DIR}" fetch --depth 1 origin "${REF}"
  git -C "${DEST_DIR}" checkout --detach FETCH_HEAD
fi

echo "Done."
