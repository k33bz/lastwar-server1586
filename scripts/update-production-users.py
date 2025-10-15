#!/usr/bin/env python3
"""
Update Production users.json with powereditor Field

This script adds the "powereditor": false field to all users in users.json
if it doesn't already exist.

Usage:
    python update-production-users.py <path-to-users.json>

Example:
    python update-production-users.py ~/Downloads/users.json
"""

import json
import sys
import os
from datetime import datetime

def add_powereditor_field(users_file_path):
    """Add powereditor field to all users if missing."""

    # Check if file exists
    if not os.path.exists(users_file_path):
        print(f"❌ Error: File not found: {users_file_path}")
        sys.exit(1)

    # Create backup
    backup_path = f"{users_file_path}.backup-{datetime.now().strftime('%Y%m%d-%H%M%S')}"
    print(f"📦 Creating backup: {backup_path}")

    with open(users_file_path, 'r') as f:
        original_content = f.read()

    with open(backup_path, 'w') as f:
        f.write(original_content)

    print("✅ Backup created successfully")

    # Load JSON
    try:
        with open(users_file_path, 'r') as f:
            data = json.load(f)
    except json.JSONDecodeError as e:
        print(f"❌ Error: Invalid JSON in file: {e}")
        sys.exit(1)

    # Check structure
    if 'users' not in data or not isinstance(data['users'], list):
        print("❌ Error: Invalid users.json structure (missing 'users' array)")
        sys.exit(1)

    # Update users
    modified = False
    for user in data['users']:
        if 'powereditor' not in user:
            user['powereditor'] = False
            modified = True
            print(f"  ➕ Added powereditor=false to: {user.get('email', 'unknown')}")
        else:
            print(f"  ✓ Already has powereditor field: {user.get('email', 'unknown')}")

    if not modified:
        print("\n✅ All users already have powereditor field. No changes needed.")
        return

    # Write updated file
    try:
        with open(users_file_path, 'w') as f:
            json.dump(data, f, indent=2)
        print(f"\n✅ Successfully updated: {users_file_path}")
        print(f"📋 Backup saved at: {backup_path}")
    except Exception as e:
        print(f"❌ Error writing file: {e}")
        print(f"📋 Original file preserved. Backup at: {backup_path}")
        sys.exit(1)

def main():
    if len(sys.argv) != 2:
        print("Usage: python update-production-users.py <path-to-users.json>")
        print("\nExample:")
        print("  python update-production-users.py ~/Downloads/users.json")
        sys.exit(1)

    users_file = sys.argv[1]

    print("=" * 60)
    print("Production users.json Updater")
    print("Adding powereditor field to all users")
    print("=" * 60)
    print()

    add_powereditor_field(users_file)

    print()
    print("=" * 60)
    print("Next Steps:")
    print("=" * 60)
    print("1. Verify the updated file looks correct")
    print("2. Upload to production: lastwar1586.online/admin/users.json")
    print("3. Test: https://www.lastwar1586.online/admin/users.json (should be 403)")
    print("4. Test: https://www.lastwar1586.online/admin/dashboard.php (should work)")
    print()

if __name__ == '__main__':
    main()
