#!/bin/bash

# ============================================================================
# DEPLOYMENT CONFIGURATION
# ============================================================================
# This file contains all configuration for the deployment process.
# Modify these values to match your server setup.

# -----------------------------------------------------------------------------
# PATHS & DIRECTORIES
# -----------------------------------------------------------------------------
export DEPLOY_TARGET='/www/wwwroot/voice.frolax.agency'
export DEPLOY_GIT_DIR='/www/repos/voice.git'
export DEPLOY_BRANCH='main'

# -----------------------------------------------------------------------------
# PHP & BINARIES
# -----------------------------------------------------------------------------
export PHP_BIN='/www/server/php/84/bin/php'
export COMPOSER_BIN='/usr/bin/composer'
export NODE_PATH='/www/server/nodejs/v22.21.0/bin'

# -----------------------------------------------------------------------------
# DEPLOYMENT USER
# -----------------------------------------------------------------------------
export DEPLOY_USER='www'

# -----------------------------------------------------------------------------
# LOGGING
# -----------------------------------------------------------------------------
export LOG_DIR="${DEPLOY_TARGET}/storage/logs/deployments"
export LOG_FILE="${LOG_DIR}/deploy-$(date +%Y%m%d-%H%M%S).log"
export ERROR_LOG="${LOG_DIR}/deploy-error.log"

# -----------------------------------------------------------------------------
# DEPLOYMENT LOCKS
# -----------------------------------------------------------------------------
export LOCK_FILE="${DEPLOY_TARGET}/storage/framework/deploy.lock"
export LOCK_TIMEOUT=600  # 10 minutes

# -----------------------------------------------------------------------------
# HEALTH CHECK
# -----------------------------------------------------------------------------
export HEALTH_CHECK_URL="https://voice.frolax.agency/api/health"
export HEALTH_CHECK_TIMEOUT=30
export HEALTH_CHECK_RETRIES=3

# -----------------------------------------------------------------------------
# BACKUP & ROLLBACK
# -----------------------------------------------------------------------------
export BACKUP_DIR="${DEPLOY_TARGET}/storage/backups"
export KEEP_BACKUPS=5  # Number of backups to keep
export ENABLE_DB_BACKUP=true

# -----------------------------------------------------------------------------
# DEPLOYMENT STEPS (Enable/Disable)
# -----------------------------------------------------------------------------
export STEP_CHECKOUT=true
export STEP_COMPOSER=true
export STEP_NPM=false         # Set to true if using frontend assets
export STEP_MIGRATIONS=true
export STEP_OPTIMIZE=true
export STEP_HORIZON=true
export STEP_ASTERISK=true
export STEP_SCRIBE=true
export STEP_HEALTH_CHECK=true

# -----------------------------------------------------------------------------
# COMPOSER OPTIONS
# -----------------------------------------------------------------------------
export COMPOSER_OPTS="--quiet --no-dev --optimize-autoloader --prefer-dist"

# -----------------------------------------------------------------------------
# NPM/PNPM SETTINGS
# -----------------------------------------------------------------------------
export USE_PNPM=false  # Set to true if using pnpm instead of npm
export NPM_BUILD_CMD="build"

# -----------------------------------------------------------------------------
# NOTIFICATIONS (Optional - leave empty to disable)
# -----------------------------------------------------------------------------
export SLACK_WEBHOOK_URL=""
export DISCORD_WEBHOOK_URL=""

# -----------------------------------------------------------------------------
# COLORS
# -----------------------------------------------------------------------------
export COLOR_RED='\033[0;31m'
export COLOR_GREEN='\033[0;32m'
export COLOR_YELLOW='\033[1;33m'
export COLOR_BLUE='\033[0;34m'
export COLOR_CYAN='\033[0;36m'
export COLOR_GRAY='\033[0;90m'
export COLOR_NC='\033[0m'  # No Color
