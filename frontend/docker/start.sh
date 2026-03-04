#!/bin/sh
set -e

cd /app

if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ]; then
  npm install
fi

npm run dev -- --host=0.0.0.0 --port=5173
