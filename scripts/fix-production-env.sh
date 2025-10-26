#!/bin/bash
#
# Fix Production .env File - Disable Automatic Key Rotation
#
# This script updates the production .env file to disable automatic key rotation
# Run this on the production server via SSH
#
# Usage:
#   bash fix-production-env.sh
#

ENV_FILE="admin/.env"

echo "=========================================="
echo "Production .env Fix - Disable Auto Rotation"
echo "=========================================="
echo ""

# Check if .env file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Error: $ENV_FILE not found"
    echo "Current directory: $(pwd)"
    exit 1
fi

# Backup current .env
BACKUP_FILE="${ENV_FILE}.backup.$(date +%Y_%m_%d_%H_%M_%S)"
cp "$ENV_FILE" "$BACKUP_FILE"
echo "✓ Created backup: $BACKUP_FILE"

# Check if AUTO_KEY_ROTATION_ENABLED exists
if grep -q "AUTO_KEY_ROTATION_ENABLED" "$ENV_FILE"; then
    echo "✓ Found AUTO_KEY_ROTATION_ENABLED setting"

    # Update the value to false
    sed -i 's/^AUTO_KEY_ROTATION_ENABLED=.*/AUTO_KEY_ROTATION_ENABLED=false/' "$ENV_FILE"
    echo "✓ Updated AUTO_KEY_ROTATION_ENABLED=false"
else
    echo "! AUTO_KEY_ROTATION_ENABLED not found, adding it..."

    # Add the setting before APP_ENV line
    sed -i '/^APP_ENV=/i # JWT Secret Key Rotation Configuration\nAUTO_KEY_ROTATION_ENABLED=false    # Disabled - use manual rotation only\nKEY_ROTATION_INTERVAL_DAYS=90      # Only used if enabled\nKEY_ROTATION_GRACE_PERIOD=300      # 5 minutes grace period\n' "$ENV_FILE"
    echo "✓ Added AUTO_KEY_ROTATION_ENABLED=false"
fi

# Verify the change
echo ""
echo "Current AUTO_KEY_ROTATION_ENABLED setting:"
grep "AUTO_KEY_ROTATION_ENABLED" "$ENV_FILE" || echo "(Not found)"

echo ""
echo "=========================================="
echo "Fix Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Check if there's a cron job:"
echo "   crontab -l | grep cron_key_rotation"
echo ""
echo "2. If found, disable it:"
echo "   crontab -e"
echo "   (Comment out or remove the key rotation line)"
echo ""
