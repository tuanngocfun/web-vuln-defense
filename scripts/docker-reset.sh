#!/bin/bash

# Go to the parent directory of the script location
cd "$(dirname "$0")/.."

# Stop and remove containers, networks, and volumes
docker compose down --volumes

# Rebuild and start containers, watch for changes
docker compose up --watch --build
