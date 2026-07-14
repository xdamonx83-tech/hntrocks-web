#!/usr/bin/env bash

set -u

ROOT_DIR="/home/users/hunthub/www/hnt.rocks"
SERVICE_DIR="$ROOT_DIR/services/local-translator"
VENV_DIR="$SERVICE_DIR/.venv"
PYTHON_BIN="${PYTHON_BIN:-python3.13}"
ARGOS_DATA_DIR="/home/users/hunthub/.local/share"

cd "$ROOT_DIR" || exit 1

systemctl stop hnt-local-translator 2>/dev/null || true
rm -rf "$VENV_DIR"

runuser -u hunthub -- env \
  HOME=/home/users/hunthub \
  XDG_DATA_HOME="$ARGOS_DATA_DIR" \
  "$PYTHON_BIN" -m venv "$VENV_DIR" || exit 1

runuser -u hunthub -- env \
  HOME=/home/users/hunthub \
  XDG_DATA_HOME="$ARGOS_DATA_DIR" \
  "$VENV_DIR/bin/python" -m pip install \
  --upgrade pip setuptools wheel || exit 1

# Install the CPU build first so Argos/Stanza do not pull CUDA/NVIDIA wheels.
runuser -u hunthub -- env \
  HOME=/home/users/hunthub \
  XDG_DATA_HOME="$ARGOS_DATA_DIR" \
  "$VENV_DIR/bin/python" -m pip install \
  torch \
  --index-url https://download.pytorch.org/whl/cpu || exit 1

runuser -u hunthub -- env \
  HOME=/home/users/hunthub \
  XDG_DATA_HOME="$ARGOS_DATA_DIR" \
  ARGOS_DEVICE_TYPE=cpu \
  "$VENV_DIR/bin/python" -m pip install \
  -r "$SERVICE_DIR/requirements.txt" || exit 1

runuser -u hunthub -- env \
  HOME=/home/users/hunthub \
  XDG_DATA_HOME="$ARGOS_DATA_DIR" \
  ARGOS_DEVICE_TYPE=cpu \
  "$VENV_DIR/bin/python" \
  "$SERVICE_DIR/install_models.py" || exit 1

chown -R hunthub:hunthub "$SERVICE_DIR" "$ARGOS_DATA_DIR/argos-translate" 2>/dev/null || true

cp "$SERVICE_DIR/hnt-local-translator.service" /etc/systemd/system/hnt-local-translator.service
systemctl daemon-reload
systemctl enable --now hnt-local-translator

sleep 5
curl -fsS http://127.0.0.1:8787/health
