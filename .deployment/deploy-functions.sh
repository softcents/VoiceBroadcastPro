#!/bin/bash

# ============================================================================
# DEPLOYMENT UTILITY FUNCTIONS
# ============================================================================
# Reusable functions for deployment operations

# -----------------------------------------------------------------------------
# LOGGING FUNCTIONS
# -----------------------------------------------------------------------------

log() {
    local message="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${COLOR_GRAY}[${timestamp}]${COLOR_NC} ${message}" | tee -a "$LOG_FILE"
}

log_info() {
    log "${COLOR_BLUE}ℹ${COLOR_NC} $1"
}

log_success() {
    log "${COLOR_GREEN}✓${COLOR_NC} $1"
}

log_warning() {
    log "${COLOR_YELLOW}⚠${COLOR_NC} $1"
}

log_error() {
    local message="$1"
    log "${COLOR_RED}✗${COLOR_NC} ${message}"
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: ${message}" >> "$ERROR_LOG"
}

log_step() {
    local step="$1"
    log "${COLOR_CYAN}▶${COLOR_NC} ${step}..."
}

# -----------------------------------------------------------------------------
# LOCK MANAGEMENT
# -----------------------------------------------------------------------------

acquire_lock() {
    local lock_file="$1"
    local timeout="$2"
    local elapsed=0
    
    log_info "Acquiring deployment lock..."
    
    while [ -f "$lock_file" ]; do
        if [ $elapsed -ge $timeout ]; then
            log_error "Deployment lock timeout after ${timeout}s"
            return 1
        fi
        
        log_warning "Another deployment is in progress. Waiting..."
        sleep 5
        elapsed=$((elapsed + 5))
    done
    
    # Create lock file with PID
    echo $$ > "$lock_file"
    log_success "Lock acquired (PID: $$)"
    return 0
}

release_lock() {
    local lock_file="$1"
    if [ -f "$lock_file" ]; then
        rm -f "$lock_file"
        log_success "Lock released"
    fi
}

# -----------------------------------------------------------------------------
# ERROR HANDLING
# -----------------------------------------------------------------------------

handle_error() {
    local exit_code=$?
    local line_number=$1
    
    log_error "Deployment failed at line ${line_number} with exit code ${exit_code}"
    
    # Run cleanup
    cleanup_on_error
    
    # Send failure notification
    send_notification "❌ Deployment Failed" "Error at line ${line_number}" "danger"
    
    exit $exit_code
}

cleanup_on_error() {
    log_warning "Running cleanup..."
    
    # Disable maintenance mode if it was enabled
    if [ -f "${DEPLOY_TARGET}/storage/framework/down" ]; then
        run_artisan "up" "Disabling maintenance mode"
    fi
    
    # Release lock
    release_lock "$LOCK_FILE"
}

# -----------------------------------------------------------------------------
# BACKUP & RESTORE
# -----------------------------------------------------------------------------

create_backup() {
    local backup_name="backup-$(date +%Y%m%d-%H%M%S)"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    log_step "Creating backup: ${backup_name}"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Backup database if enabled
    if [ "$ENABLE_DB_BACKUP" = true ]; then
        log_info "Backing up database..."
        sudo -u "$DEPLOY_USER" "$PHP_BIN" artisan db:backup --path="$backup_path" 2>&1 | tee -a "$LOG_FILE"
        
        if [ ${PIPESTATUS[0]} -ne 0 ]; then
            log_warning "Database backup failed, but continuing..."
        else
            log_success "Database backed up"
        fi
    fi
    
    # Store current git commit
    git --git-dir="$DEPLOY_GIT_DIR" rev-parse HEAD > "${backup_path}/commit.txt"
    
    log_success "Backup created: ${backup_name}"
    echo "$backup_name"
}

cleanup_old_backups() {
    log_step "Cleaning up old backups"
    
    cd "$BACKUP_DIR" || return
    
    # Keep only the last N backups
    ls -t | tail -n +$((KEEP_BACKUPS + 1)) | xargs -r rm -rf
    
    local count=$(ls -1 | wc -l)
    log_success "Kept ${count} most recent backups"
}

rollback_deployment() {
    local backup_name="$1"
    
    log_warning "Rolling back deployment to: ${backup_name}"
    
    # Restore from backup
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    if [ ! -d "$backup_path" ]; then
        log_error "Backup not found: ${backup_name}"
        return 1
    fi
    
    # Get commit hash
    if [ -f "${backup_path}/commit.txt" ]; then
        local commit=$(cat "${backup_path}/commit.txt")
        log_info "Checking out commit: ${commit}"
        
        sudo git --work-tree="$DEPLOY_TARGET" --git-dir="$DEPLOY_GIT_DIR" checkout -f "$commit"
        
        # Restore database if backup exists
        if [ -f "${backup_path}/database.sql" ]; then
            log_info "Restoring database..."
            sudo -u "$DEPLOY_USER" "$PHP_BIN" artisan db:restore --path="${backup_path}/database.sql"
        fi
        
        log_success "Rollback completed"
        return 0
    else
        log_error "Backup metadata not found"
        return 1
    fi
}

# -----------------------------------------------------------------------------
# HEALTH CHECKS
# -----------------------------------------------------------------------------

check_health() {
    local url="$1"
    local timeout="$2"
    local retries="$3"
    
    log_step "Running health check"
    
    for i in $(seq 1 $retries); do
        log_info "Health check attempt ${i}/${retries}..."
        
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$timeout" "$url")
        
        if [ "$http_code" = "200" ]; then
            log_success "Health check passed (HTTP ${http_code})"
            return 0
        else
            log_warning "Health check failed (HTTP ${http_code})"
            
            if [ $i -lt $retries ]; then
                sleep 3
            fi
        fi
    done
    
    log_error "Health check failed after ${retries} attempts"
    return 1
}

# -----------------------------------------------------------------------------
# ARTISAN HELPERS
# -----------------------------------------------------------------------------

run_artisan() {
    local command="$1"
    local description="${2:-Running artisan ${command}}"
    
    log_step "$description"
    
    cd "$DEPLOY_TARGET" || return 1
    
    sudo -u "$DEPLOY_USER" "$PHP_BIN" artisan $command 2>&1 | tee -a "$LOG_FILE"
    
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        log_success "${description} completed"
        return 0
    else
        log_error "${description} failed"
        return 1
    fi
}

# -----------------------------------------------------------------------------
# NOTIFICATIONS
# -----------------------------------------------------------------------------

send_notification() {
    local title="$1"
    local message="$2"
    local level="${3:-info}"  # info, success, warning, danger
    
    # Slack notification
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        local color="good"
        [ "$level" = "warning" ] && color="warning"
        [ "$level" = "danger" ] && color="danger"
        
        curl -X POST "$SLACK_WEBHOOK_URL" \
            -H 'Content-Type: application/json' \
            -d "{\"attachments\":[{\"color\":\"${color}\",\"title\":\"${title}\",\"text\":\"${message}\"}]}" \
            > /dev/null 2>&1
    fi
    
    # Discord notification
    if [ -n "$DISCORD_WEBHOOK_URL" ]; then
        curl -X POST "$DISCORD_WEBHOOK_URL" \
            -H 'Content-Type: application/json' \
            -d "{\"content\":\"**${title}**\n${message}\"}" \
            > /dev/null 2>&1
    fi
}

# -----------------------------------------------------------------------------
# DIRECTORY SETUP
# -----------------------------------------------------------------------------

ensure_directories() {
    log_step "Ensuring required directories exist"
    
    mkdir -p "$LOG_DIR"
    mkdir -p "$BACKUP_DIR"
    mkdir -p "${DEPLOY_TARGET}/storage/framework"
    
    # Fix permissions
    sudo chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "$LOG_DIR" "$BACKUP_DIR"
    
    log_success "Directories ready"
}

# -----------------------------------------------------------------------------
# PERMISSION MANAGEMENT
# -----------------------------------------------------------------------------

fix_permissions() {
    log_step "Fixing file permissions"
    
    cd "$DEPLOY_TARGET" || return 1
    
    # Change ownership (exclude .user.ini)
    sudo find . -not -name '.user.ini' -exec chown "${DEPLOY_USER}:${DEPLOY_USER}" {} +
    
    log_success "Permissions fixed"
}
