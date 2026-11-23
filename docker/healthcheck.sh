#!/bin/sh
#
# Container healthcheck. Hits the Laravel health route registered in
# bootstrap/app.php (health: '/up').
#
curl \
    --fail \
    --silent \
    --show-error \
    --max-time 4 \
    --output /dev/null \
    "http://127.0.0.1:${HEALTHCHECK_PORT:-8080}/up"
