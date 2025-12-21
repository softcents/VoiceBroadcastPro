# Deployment Scripts - Installation & Usage Guide

## 📋 Overview

This deployment system provides a robust, production-ready Git post-receive hook with:

- ✅ **Error handling** with automatic rollback
- ✅ **Deployment locking** to prevent concurrent deployments
- ✅ **Health checks** to verify successful deployment
- ✅ **Database backups** before migrations
- ✅ **Structured logging** for debugging
- ✅ **Configurable steps** with feature flags
- ✅ **Notification support** (Slack/Discord)

## 📁 Files

- **deploy-config.sh** - Configuration file with all settings
- **deploy-functions.sh** - Reusable utility functions
- **post-receive** - Main deployment hook script

## 🚀 Installation

### 1. Copy Files to Server

Upload the deployment scripts to your production server:

```bash
# On your local machine
cd /Users/bishwajit/Clients/voice/.deployment

# Upload to server
scp deploy-config.sh deploy-functions.sh root@voice.frolax.agency:/www/wwwroot/voice.frolax.agency/.deployment/
scp post-receive root@voice.frolax.agency:/www/repos/voice.git/hooks/
```

### 2. Make Scripts Executable

```bash
# SSH into server
ssh root@voice.frolax.agency

# Make scripts executable
chmod +x /www/wwwroot/voice.frolax.agency/.deployment/*.sh
chmod +x /www/repos/voice.git/hooks/post-receive
```

### 3. Configure Settings

Edit the configuration file on the server:

```bash
nano /www/wwwroot/voice.frolax.agency/.deployment/deploy-config.sh
```

Review and adjust:
- Paths (should be correct by default)
- PHP/Composer binary paths
- Enable/disable deployment steps
- Health check URL
- Notification webhooks (optional)

### 4. Create Required Directories

```bash
# Create log and backup directories
mkdir -p /www/wwwroot/voice.frolax.agency/storage/logs/deployments
mkdir -p /www/wwwroot/voice.frolax.agency/storage/backups

# Fix permissions
chown -R www:www /www/wwwroot/voice.frolax.agency/storage
```

### 5. Test Deployment

Push a commit to the main branch and verify:

```bash
# Check deployment log
tail -f /www/wwwroot/voice.frolax.agency/storage/logs/deployments/deploy-*.log

# Check for errors
cat /www/wwwroot/voice.frolax.agency/storage/logs/deployments/deploy-error.log
```

## ⚙️ Configuration Options

### Deployment Steps

Enable/disable specific steps in `deploy-config.sh`:

```bash
export STEP_CHECKOUT=true       # Checkout code from Git
export STEP_COMPOSER=true       # Run composer install
export STEP_NPM=false           # Build frontend assets (disabled by default)
export STEP_MIGRATIONS=true     # Run database migrations
export STEP_OPTIMIZE=true       # Optimize Laravel
export STEP_HORIZON=true        # Restart Horizon queues
export STEP_ASTERISK=true       # Restart Asterisk daemon
export STEP_SCRIBE=true         # Generate API docs
export STEP_HEALTH_CHECK=true   # Verify app health after deployment
```

### Health Check

Set up a health check endpoint in your Laravel app:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
```

Update the URL in config:

```bash
export HEALTH_CHECK_URL="https://voice.frolax.agency/api/health"
```

### Notifications

To enable Slack notifications:

```bash
export SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
```

To enable Discord notifications:

```bash
export DISCORD_WEBHOOK_URL="https://discord.com/api/webhooks/YOUR/WEBHOOK/URL"
```

## 🔄 Usage

### Normal Deployment

Simply push to the main branch:

```bash
git push production main
```

The deployment will:
1. Acquire deployment lock
2. Create backup
3. Enable maintenance mode
4. Deploy code
5. Run migrations
6. Restart services
7. Run health checks
8. Disable maintenance mode
9. Send notifications

### Manual Rollback

If you need to manually rollback:

```bash
# SSH into server
ssh root@voice.frolax.agency

# List available backups
ls -lt /www/wwwroot/voice.frolax.agency/storage/backups/

# The backup name format is: backup-YYYYMMDD-HHMMSS
# Example: backup-20251221-085555
```

Then you can manually restore from the backup by checking out the commit stored in the backup directory.

## 📊 Logs

Deployment logs are stored in:

```
/www/wwwroot/voice.frolax.agency/storage/logs/deployments/
```

- `deploy-YYYYMMDD-HHMMSS.log` - Individual deployment logs
- `deploy-error.log` - Consolidated error log

## 🛡️ Safety Features

### Deployment Lock

Prevents concurrent deployments. If a deployment is already in progress, new deployments will wait up to 10 minutes (`LOCK_TIMEOUT`).

### Automatic Rollback

If any step fails or health check doesn't pass, the deployment automatically rolls back to the previous version.

### Database Backups

Before running migrations, the database is backed up. Set `ENABLE_DB_BACKUP=true` in config.

> **Note**: You'll need to implement the `db:backup` and `db:restore` Artisan commands in your Laravel app, or disable this feature by setting `ENABLE_DB_BACKUP=false`.

## 🔧 Troubleshooting

### Deployment Hangs

Check if there's a stale lock file:

```bash
rm -f /www/wwwroot/voice.frolax.agency/storage/framework/deploy.lock
```

### Permission Errors

Fix ownership:

```bash
chown -R www:www /www/wwwroot/voice.frolax.agency
```

### Health Check Fails

1. Verify the health check URL is accessible
2. Check Laravel logs for errors
3. Disable health check temporarily: `export STEP_HEALTH_CHECK=false`

## 📝 Customization

### Adding Custom Steps

Edit `/www/wwwroot/voice.frolax.agency/.deployment/post-receive` and add your custom step:

```bash
step_custom_task() {
    log_step "Running custom task"
    
    # Your custom commands here
    
    log_success "Custom task completed"
}
```

Then call it in the `deploy()` function:

```bash
# After other steps
step_custom_task
```

## 🎯 Best Practices

1. **Always test locally first** before pushing to production
2. **Monitor deployment logs** after each deployment
3. **Keep backups** - don't reduce `KEEP_BACKUPS` below 5
4. **Use health checks** - they catch issues before users notice
5. **Enable notifications** - get immediate deployment status
6. **Review error logs** regularly for patterns

## 🆘 Emergency Procedures

### Stop a Running Deployment

If you need to stop a deployment in progress:

```bash
# Find the deployment process
ps aux | grep post-receive

# Kill the process (use the PID from the lock file)
kill $(cat /www/wwwroot/voice.frolax.agency/storage/framework/deploy.lock)

# Clean up
rm -f /www/wwwroot/voice.frolax.agency/storage/framework/deploy.lock
php artisan up  # Disable maintenance mode
```

### Manual Maintenance Mode

```bash
# Enable
php artisan down

# Disable
php artisan up
```
