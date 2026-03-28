#!/bin/bash
###############################################################################
# QueenLib Production Rollback Script
# This script recovers from a failed deployment
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
PROJECT_PATH="/var/www/queenlib"
BACKUP_PATH="/backups/queenlib"

###############################################################################
# Utility Functions
###############################################################################

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✓ $1${NC}"
}

error() {
    echo -e "${RED}✗ $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

###############################################################################
# Rollback Functions
###############################################################################

validate_rollback() {
    log "Validating rollback parameters..."

    if [ -z "$1" ]; then
        error "Usage: $0 <timestamp>"
        error "Example: $0 20260327_020000"
    fi

    TIMESTAMP="$1"

    # Check if backup exists
    if [ ! -d "${PROJECT_PATH}-backup-${TIMESTAMP}" ]; then
        error "Backup not found: ${PROJECT_PATH}-backup-${TIMESTAMP}"
    fi

    # Check if database backup exists
    if [ ! -f "$BACKUP_PATH/db_backup_${TIMESTAMP}.sql.gz" ]; then
        warning "Database backup not found: $BACKUP_PATH/db_backup_${TIMESTAMP}.sql.gz"
    fi

    success "Rollback parameters validated"
}

confirm_rollback() {
    log ""
    warning "ROLLBACK CONFIRMATION REQUIRED"
    log "This will restore the deployment from: $TIMESTAMP"
    log ""
    read -p "Are you sure you want to rollback? (yes/no): " confirm

    if [ "$confirm" != "yes" ]; then
        log "Rollback cancelled"
        exit 0
    fi
}

stop_apache() {
    log "Stopping Apache..."
    systemctl stop apache2
    sleep 2
    success "Apache stopped"
}

restore_application() {
    log "Restoring application files..."

    # Remove current version
    if [ -d "$PROJECT_PATH" ]; then
        mv "$PROJECT_PATH" "${PROJECT_PATH}-failed-$(date +%s)"
    fi

    # Restore from backup
    cp -r "${PROJECT_PATH}-backup-${TIMESTAMP}" "$PROJECT_PATH"

    # Restore permissions
    chown -R www-data:www-data "$PROJECT_PATH"
    chmod -R 755 "$PROJECT_PATH"
    find "$PROJECT_PATH" -type f -exec chmod 644 {} \;
    find "$PROJECT_PATH" -type d -exec chmod 755 {} \;

    chmod 600 "$PROJECT_PATH/.env.production"

    success "Application files restored"
}

restore_database() {
    log "Restoring database..."

    if [ ! -f "$BACKUP_PATH/db_backup_${TIMESTAMP}.sql.gz" ]; then
        warning "Database backup not found, skipping database restore"
        return
    fi

    # Get database credentials
    DB_USER=$(grep "DB_USER=" "$PROJECT_PATH/.env.production" | cut -d '=' -f2)
    DB_PASS=$(grep "DB_PASS=" "$PROJECT_PATH/.env.production" | cut -d '=' -f2)
    DB_NAME=$(grep "DB_NAME=" "$PROJECT_PATH/.env.production" | cut -d '=' -f2)

    # Restore database
    zcat "$BACKUP_PATH/db_backup_${TIMESTAMP}.sql.gz" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"

    success "Database restored"
}

start_apache() {
    log "Starting Apache..."
    systemctl start apache2
    sleep 3

    if ! systemctl is-active --quiet apache2; then
        error "Failed to start Apache"
    fi

    success "Apache started successfully"
}

verify_rollback() {
    log "Verifying rollback..."

    # Test health endpoint
    HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health-check.php 2>/dev/null || echo "000")

    if [ "$HEALTH_CHECK" = "200" ]; then
        success "Health check passed (HTTP $HEALTH_CHECK)"
    else
        warning "Health check returned HTTP $HEALTH_CHECK"
    fi

    success "Rollback verification complete"
}

create_notification() {
    log "Creating notification..."

    NOTICE_FILE="/var/log/queenlib/rollback_notice_$(date +%s).txt"

    cat > "$NOTICE_FILE" << EOF
ROLLBACK NOTIFICATION - $(date '+%Y-%m-%d %H:%M:%S')

Timestamp: $TIMESTAMP
Action: Application rolled back to previous deployment
Reason: Deployment failure/manual rollback

Rollback Details:
- Application restored from: ${PROJECT_PATH}-backup-${TIMESTAMP}
- Database restored from: $BACKUP_PATH/db_backup_${TIMESTAMP}.sql.gz

Failed Deployment Backup:
- Location: ${PROJECT_PATH}-failed-*

Next Steps:
1. Verify application is working correctly
2. Check error logs: /var/log/apache2/queenlib_error.log
3. Investigate what caused the deployment failure
4. Fix issues and redeploy

Contact: DevOps Team
EOF

    success "Notification created: $NOTICE_FILE"
}

###############################################################################
# Main Execution
###############################################################################

main() {
    log "=========================================="
    log "QueenLib Production Rollback Script"
    log "=========================================="
    log ""

    validate_rollback "$1"
    confirm_rollback
    stop_apache
    restore_application
    restore_database
    start_apache
    verify_rollback
    create_notification

    log ""
    success "=========================================="
    success "✓ ROLLBACK SUCCESSFUL"
    success "=========================================="
    log ""
    log "Application has been rolled back to: $TIMESTAMP"
    log "Failed deployment backed up to: ${PROJECT_PATH}-failed-*"
    log ""
    warning "⚠️  Please verify the application is working correctly"
    log ""
}

main "$1"
