#!/bin/bash
set -e

cd /opt/quantum

echo "[deploy] git status (pred):"
git status -sb || true

echo "[deploy] git pull --ff-only origin main"
git pull --ff-only origin main

echo "[deploy] hotovo."

