#!/bin/bash

# =====================================================
# SIMAK PTUN - Automated Maintenance Script
# Pengadilan Tata Usaha Negara Banjarmasin
# 
# This script performs automated maintenance tasks
# Run via cron job: 0 2 * * * /path/to/maintenance.sh
# =====================================================

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$APP_DIR/logs/maintenance.log"
BACKUP_DIR="$APP_DIR/backups"
TEMP_DIR="$APP_DIR/temp"
UPLOAD_DIR="$APP_DIR/assets/uploads"
MYSQL_USER="simak_user"
MYSQL_PASS="your_db_password"
MYSQL_DB="simak_ptun"
MAX_LOG_AGE=30  # days
MAX_BACKUP_AGE=7  # days

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

# Start maintenance
log_message "INFO" "=== SIMAK PTUN Automated Maintenance Started ==="

# Function: Database Backup
backup_database() {
    log_message "INFO" "Starting database backup..."
    
    local backup_file="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    if mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        "$MYSQL_DB" > "$backup_file"; then
        
        # Compress backup
        gzip "$backup_file"
        log_message "SUCCESS" "Database backup created: ${backup_file}.gz"
        
        # Set proper permissions
        chmod 600 "${backup_file}.gz"
        
    else
        log_message "ERROR" "Database backup failed!"
        return 1
    fi
}

# Function: Cleanup old backups
cleanup_old_backups() {
    log_message "INFO" "Cleaning up old backups (older than $MAX_BACKUP_AGE days)..."
    
    if find "$BACKUP_DIR" -name "*.gz" -type f -mtime +$MAX_BACKUP_AGE -delete; then
        local count=$(find "$BACKUP_DIR" -name "*.gz" -type f -mtime +$MAX_BACKUP_AGE | wc -l)
        log_message "SUCCESS" "Removed $count old backup files"
    else
        log_message "ERROR" "Failed to cleanup old backups"
    fi
}

# Function: Cleanup old logs
cleanup_old_logs() {
    log_message "INFO" "Cleaning up old log files (older than $MAX_LOG_AGE days)..."
    
    # Keep maintenance.log but truncate if too large
    if [[ -f "$LOG_FILE" && $(stat -f%z "$LOG_FILE" 2>/dev/null || stat -c%s "$LOG_FILE" 2>/dev/null) -gt 10485760 ]]; then
        tail -n 1000 "$LOG_FILE" > "${LOG_FILE}.tmp"
        mv "${LOG_FILE}.tmp" "$LOG_FILE"
        log_message "INFO" "Maintenance log truncated to last 1000 lines"
    fi
    
    # Clean other log files
    find "$APP_DIR/logs" -name "*.log" -not -name "maintenance.log" -type f -mtime +$MAX_LOG_AGE -delete
    
    log_message "SUCCESS" "Log cleanup completed"
}

# Function: Cleanup temporary files
cleanup_temp_files() {
    log_message "INFO" "Cleaning up temporary files..."
    
    # Clean temp directory
    if [[ -d "$TEMP_DIR" ]]; then
        find "$TEMP_DIR" -type f -mtime +1 -delete
        find "$TEMP_DIR" -type d -empty -delete
    fi
    
    # Clean PHP session files older than 1 day
    if [[ -d "/tmp" ]]; then
        find /tmp -name "sess_*" -type f -mtime +1 -delete 2>/dev/null || true
    fi
    
    # Clean upload temp files
    if [[ -d "$UPLOAD_DIR/temp" ]]; then
        find "$UPLOAD_DIR/temp" -type f -mtime +1 -delete
    fi
    
    log_message "SUCCESS" "Temporary files cleanup completed"
}

# Function: Database optimization
optimize_database() {
    log_message "INFO" "Optimizing database tables..."
    
    # Get list of tables
    local tables=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -N -e "SHOW TABLES FROM $MYSQL_DB")
    
    for table in $tables; do
        log_message "INFO" "Optimizing table: $table"
        mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "OPTIMIZE TABLE $MYSQL_DB.$table" >/dev/null 2>&1
    done
    
    # Analyze tables for better query performance
    mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "ANALYZE TABLE $MYSQL_DB.surat_masuk, $MYSQL_DB.surat_keluar, $MYSQL_DB.users, $MYSQL_DB.activity_logs" >/dev/null 2>&1
    
    log_message "SUCCESS" "Database optimization completed"
}

# Function: Check disk space
check_disk_space() {
    log_message "INFO" "Checking disk space..."
    
    local disk_usage=$(df -h "$APP_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [[ $disk_usage -gt 85 ]]; then
        log_message "WARNING" "Disk space usage is high: ${disk_usage}%"
        
        # Send alert email (if mail is configured)
        if command -v mail >/dev/null 2>&1; then
            echo "SIMAK PTUN Alert: Disk space usage is ${disk_usage}% on $(hostname)" | \
            mail -s "SIMAK PTUN: High Disk Usage Alert" admin@ptun-banjarmasin.go.id
        fi
        
    elif [[ $disk_usage -gt 95 ]]; then
        log_message "ERROR" "Disk space critically low: ${disk_usage}%"
        return 1
    else
        log_message "SUCCESS" "Disk space usage: ${disk_usage}% (OK)"
    fi
}

# Function: Check application health
check_app_health() {
    log_message "INFO" "Checking application health..."
    
    # Check if web server is running
    if pgrep -x "apache2" >/dev/null || pgrep -x "httpd" >/dev/null || pgrep -x "nginx" >/dev/null; then
        log_message "SUCCESS" "Web server is running"
    else
        log_message "ERROR" "Web server is not running!"
        
        # Try to restart Apache (adjust for your system)
        if command -v systemctl >/dev/null 2>&1; then
            systemctl restart apache2 2>/dev/null || systemctl restart httpd 2>/dev/null || systemctl restart nginx 2>/dev/null
            log_message "INFO" "Attempted to restart web server"
        fi
    fi
    
    # Check MySQL connection
    if mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "SELECT 1" "$MYSQL_DB" >/dev/null 2>&1; then
        log_message "SUCCESS" "Database connection OK"
    else
        log_message "ERROR" "Database connection failed!"
        return 1
    fi
    
    # Check critical directories
    local critical_dirs=("$APP_DIR/config" "$APP_DIR/includes" "$BACKUP_DIR" "$LOG_FILE")
    for dir in "${critical_dirs[@]}"; do
        if [[ ! -e "$dir" ]]; then
            log_message "ERROR" "Critical path missing: $dir"
            return 1
        fi
    done
    
    log_message "SUCCESS" "Application health check completed"
}

# Function: Update file permissions
fix_permissions() {
    log_message "INFO" "Fixing file permissions..."
    
    # Set proper ownership (adjust www-data for your system)
    if command -v chown >/dev/null 2>&1; then
        chown -R www-data:www-data "$UPLOAD_DIR" 2>/dev/null || true
        chown -R www-data:www-data "$TEMP_DIR" 2>/dev/null || true
        chown -R www-data:www-data "$APP_DIR/logs" 2>/dev/null || true
    fi
    
    # Set directory permissions
    find "$UPLOAD_DIR" -type d -exec chmod 755 {} \; 2>/dev/null || true
    find "$UPLOAD_DIR" -type f -exec chmod 644 {} \; 2>/dev/null || true
    
    # Set backup permissions
    find "$BACKUP_DIR" -type f -name "*.gz" -exec chmod 600 {} \; 2>/dev/null || true
    
    log_message "SUCCESS" "File permissions updated"
}

# Function: Generate statistics
generate_stats() {
    log_message "INFO" "Generating maintenance statistics..."
    
    # Count files in upload directory
    local upload_count=$(find "$UPLOAD_DIR" -type f | wc -l)
    
    # Get database size
    local db_size=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size (MB)'
        FROM information_schema.tables
        WHERE table_schema='$MYSQL_DB'
    " -N 2>/dev/null || echo "Unknown")
    
    # Get backup directory size
    local backup_size=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1 || echo "Unknown")
    
    log_message "STATS" "Upload files: $upload_count"
    log_message "STATS" "Database size: ${db_size}MB"
    log_message "STATS" "Backup directory size: $backup_size"
}

# Function: Security check
security_check() {
    log_message "INFO" "Running basic security checks..."
    
    # Check for suspicious files
    local suspicious_files=$(find "$APP_DIR" -name "*.php" -type f -exec grep -l "eval\|base64_decode\|shell_exec\|system\(" {} \; 2>/dev/null | head -5)
    
    if [[ -n "$suspicious_files" ]]; then
        log_message "WARNING" "Suspicious files found (may be false positive):"
        echo "$suspicious_files" | while read -r file; do
            log_message "WARNING" "  - $file"
        done
    fi
    
    # Check file permissions on sensitive files
    local config_perms=$(stat -c "%a" "$APP_DIR/config/database.php" 2>/dev/null || echo "000")
    if [[ "$config_perms" != "644" && "$config_perms" != "600" ]]; then
        log_message "WARNING" "Config file permissions may be too open: $config_perms"
    fi
    
    log_message "SUCCESS" "Security check completed"
}

# Function: Send maintenance report
send_report() {
    if command -v mail >/dev/null 2>&1 && [[ -n "$1" ]]; then
        local status=$1
        local subject="SIMAK PTUN Maintenance Report - $status"
        
        {
            echo "SIMAK PTUN Automated Maintenance Report"
            echo "======================================"
            echo "Date: $(date)"
            echo "Status: $status"
            echo ""
            echo "Recent log entries:"
            tail -n 20 "$LOG_FILE"
        } | mail -s "$subject" admin@ptun-banjarmasin.go.id
    fi
}

# Main execution
main() {
    local exit_code=0
    
    # Create necessary directories
    mkdir -p "$BACKUP_DIR" "$TEMP_DIR" "$APP_DIR/logs"
    
    # Run maintenance tasks
    check_disk_space || exit_code=1
    check_app_health || exit_code=1
    
    if [[ $exit_code -eq 0 ]]; then
        backup_database || exit_code=1
        cleanup_old_backups
        cleanup_old_logs  
        cleanup_temp_files
        optimize_database
        fix_permissions
        security_check
        generate_stats
    fi
    
    # Log completion
    if [[ $exit_code -eq 0 ]]; then
        log_message "SUCCESS" "=== SIMAK PTUN Automated Maintenance Completed Successfully ==="
        send_report "SUCCESS"
    else
        log_message "ERROR" "=== SIMAK PTUN Automated Maintenance Completed with Errors ==="
        send_report "ERROR"
    fi
    
    echo ""
    echo -e "${GREEN}Maintenance completed. Check log file: $LOG_FILE${NC}"
    
    exit $exit_code
}

# Trap to ensure cleanup on script exit
trap 'log_message "INFO" "Maintenance script interrupted"' INT TERM

# Run main function
main "$@"
