#!/bin/bash

# Database backup script for ERP system
# This script creates daily backups and cleans up old ones

set -e

# Configuration
BACKUP_DIR="/backups"
DB_HOST="postgres"
DB_USER="postgres"
DB_NAME="hondukash_erp"
RETENTION_DAYS=7

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Generate backup filename with timestamp
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_FILE"

echo "Starting backup of database $DB_NAME..."

# Create the backup
pg_dump -h $DB_HOST -U $DB_USER -d $DB_NAME > $BACKUP_PATH

# Compress the backup
gzip $BACKUP_PATH

echo "Backup created: ${BACKUP_PATH}.gz"

# Clean up old backups (older than RETENTION_DAYS)
echo "Cleaning up backups older than $RETENTION_DAYS days..."
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup process completed successfully"

# List current backups
echo "Current backups:"
ls -la $BACKUP_DIR/backup_*.sql.gz 2>/dev/null || echo "No backups found"