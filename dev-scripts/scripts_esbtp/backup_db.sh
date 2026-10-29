#!/bin/bash

echo "==================================================="
echo "ESBTP Database Backup Script"
echo "==================================================="
echo

# Get current date and time for filename
timestamp=$(date +"%Y-%m-%d_%H-%M-%S")
backup_dir="../storage/app/backups"
backup_file="${backup_dir}/esbtp_backup_${timestamp}.sql"

# Create backup directory if it doesn't exist
if [ ! -d "$backup_dir" ]; then
    echo "Creating backup directory..."
    mkdir -p "$backup_dir"
fi

echo
echo "Reading database configuration from .env file..."
cd ..

# Extract database configuration from .env file
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
else
    echo "Error: .env file not found!"
    exit 1
fi

echo
echo "Creating database backup..."
echo "Database: $DB_DATABASE"
echo "Backup file: $backup_file"
echo

# Create the backup using mysqldump
mysqldump --host=$DB_HOST --port=$DB_PORT --user=$DB_USERNAME --password=$DB_PASSWORD $DB_DATABASE > "$backup_file"

if [ $? -eq 0 ]; then
    echo
    echo "==================================================="
    echo "Database backup completed successfully!"
    echo "==================================================="
    echo
    echo "Backup saved to: $backup_file"
else
    echo
    echo "==================================================="
    echo "Database backup failed!"
    echo "==================================================="
    echo
    echo "Please check your database configuration and ensure mysqldump is in your PATH."
fi

echo
cd scripts_esbtp
read -p "Press Enter to exit..." 