#!/bin/bash
set -euo pipefail

BASEDIR=$(dirname "$0")
ENV_FILE=$BASEDIR"/public/environment.js"

if [ ! -f "$ENV_FILE" ]; then
    cp $BASEDIR/public/environment.docker.dist "$ENV_FILE"
fi

npm ci

if [ $CI -eq 'true' ]; then
  npm run build
  npm run preview
else
  npm run dev
fi
