#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Setup colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Starting Deployment Automation ===${NC}"

# 1. Local Git Push
echo -e "${YELLOW}[1/4] Ensuring local changes are pushed to Github...${NC}"
git push origin main || echo -e "${YELLOW}Warning: Git push failed, trying remote update anyway...${NC}"

# 2. Remote Git Pull
echo -e "${YELLOW}[2/4] Pulling latest changes on VPS...${NC}"
ssh signalstack "cd /home/fazley/apps/dailylog && git pull"

# 3. Rebuild Containers
echo -e "${YELLOW}[3/4] Rebuilding production containers on VPS...${NC}"
ssh signalstack "cd /home/fazley/apps/dailylog && docker compose -f docker-compose.prod.yml up -d --build"

# 4. Clear and Cache Laravel Application Assets
echo -e "${YELLOW}[4/4] Optimizing and caching application config, routes, and views on VPS...${NC}"
ssh signalstack "docker exec dailylog_prod_app php artisan config:cache"
ssh signalstack "docker exec dailylog_prod_app php artisan route:cache"
ssh signalstack "docker exec dailylog_prod_app php artisan view:cache"

echo -e "${GREEN}=== Deployment Completed Successfully! ===${NC}"
