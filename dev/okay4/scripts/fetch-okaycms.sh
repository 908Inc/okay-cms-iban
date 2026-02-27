#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST_DIR="${ROOT_DIR}/okaycms"

if [[ -d "${DEST_DIR}/.git" ]]; then
  echo "OkayCMS already exists at: ${DEST_DIR}"
  exit 0
fi

rm -rf "${DEST_DIR}"
mkdir -p "${DEST_DIR}"

echo "Cloning OkayCMS (tag: 4.0.1) into ${DEST_DIR} ..."
git clone --depth 1 --branch 4.0.1 https://github.com/OkayCMS/OkayCMS.git "${DEST_DIR}"

echo "Done."

