#!/usr/bin/env python3
"""
FTP Update Production users.json

This script:
1. Downloads users.json from production
2. Adds powereditor field to all users
3. Uploads the updated file back to production

Requirements:
    pip install python-dotenv

Usage:
    python ftp-update-users.py

Environment variables required in .env:
    FTP_HOST=your-ftp-host
    FTP_USER=your-ftp-username
    FTP_PASS=your-ftp-password
    FTP_PATH=path-to-admin-directory (e.g., public_html/admin)
"""

import json
import os
import sys
from ftplib import FTP
from datetime import datetime
from pathlib import Path

# Try to load dotenv
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    print("⚠️  Warning: python-dotenv not installed. Using environment variables only.")
    print("   Install with: pip install python-dotenv")

def download_users_json(ftp, remote_path, local_path):
    """Download users.json from production."""
    print(f"📥 Downloading: {remote_path}/users.json")

    try:
        with open(local_path, 'wb') as f:
            ftp.retrbinary(f'RETR {remote_path}/users.json', f.write)
        print(f"✅ Downloaded to: {local_path}")
        return True
    except Exception as e:
        print(f"❌ Error downloading: {e}")
        return False

def upload_users_json(ftp, remote_path, local_path):
    """Upload users.json to production."""
    print(f"📤 Uploading: {local_path} to {remote_path}/users.json")

    try:
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_path}/users.json', f)
        print(f"✅ Uploaded successfully")
        return True
    except Exception as e:
        print(f"❌ Error uploading: {e}")
        return False

def add_powereditor_field(users_file_path):
    """Add powereditor field to all users if missing."""

    # Load JSON
    try:
        with open(users_file_path, 'r') as f:
            data = json.load(f)
    except json.JSONDecodeError as e:
        print(f"❌ Error: Invalid JSON in file: {e}")
        return False

    # Check structure
    if 'users' not in data or not isinstance(data['users'], list):
        print("❌ Error: Invalid users.json structure (missing 'users' array)")
        return False

    # Update users
    modified = False
    print("\n👥 Checking users:")
    for user in data['users']:
        email = user.get('email', 'unknown')
        if 'powereditor' not in user:
            user['powereditor'] = False
            modified = True
            print(f"  ➕ Added powereditor=false to: {email}")
        else:
            print(f"  ✓ Already has powereditor field: {email}")

    if not modified:
        print("\n✅ All users already have powereditor field. No changes needed.")
        return False

    # Write updated file
    try:
        with open(users_file_path, 'w') as f:
            json.dump(data, f, indent=2)
        print(f"\n✅ Updated file written")
        return True
    except Exception as e:
        print(f"❌ Error writing file: {e}")
        return False

def main():
    print("=" * 70)
    print("FTP Production users.json Updater")
    print("=" * 70)
    print()

    # Get FTP credentials from environment
    ftp_host = os.getenv('FTP_HOST')
    ftp_user = os.getenv('FTP_USER')
    ftp_pass = os.getenv('FTP_PASS')
    ftp_path = os.getenv('FTP_PATH', 'public_html/admin')

    if not all([ftp_host, ftp_user, ftp_pass]):
        print("❌ Error: Missing FTP credentials in environment variables")
        print("\nRequired environment variables:")
        print("  FTP_HOST - FTP server hostname")
        print("  FTP_USER - FTP username")
        print("  FTP_PASS - FTP password")
        print("  FTP_PATH - Path to admin directory (optional, default: public_html/admin)")
        print("\nYou can set these in a .env file in the project root.")
        sys.exit(1)

    # Create temp directory for downloads
    temp_dir = Path('temp')
    temp_dir.mkdir(exist_ok=True)

    timestamp = datetime.now().strftime('%Y%m%d-%H%M%S')
    local_file = temp_dir / f'users.json'
    backup_file = temp_dir / f'users-prod-backup-{timestamp}.json'

    print(f"🌐 Connecting to FTP: {ftp_host}")
    print(f"👤 Username: {ftp_user}")
    print(f"📁 Remote path: {ftp_path}")
    print()

    try:
        # Connect to FTP
        ftp = FTP(ftp_host)
        ftp.login(ftp_user, ftp_pass)
        print("✅ Connected to FTP server")
        print()

        # Download current users.json
        if not download_users_json(ftp, ftp_path, local_file):
            sys.exit(1)

        # Create backup
        print(f"\n📦 Creating local backup: {backup_file}")
        with open(local_file, 'r') as src, open(backup_file, 'w') as dst:
            dst.write(src.read())
        print("✅ Backup created")

        # Update users.json
        print("\n🔧 Updating users.json...")
        if not add_powereditor_field(local_file):
            print("\nℹ️  No changes needed. Exiting.")
            ftp.quit()
            sys.exit(0)

        # Confirm upload
        print("\n" + "=" * 70)
        response = input("📤 Ready to upload to production. Continue? (yes/no): ")
        if response.lower() != 'yes':
            print("❌ Upload cancelled by user")
            print(f"📋 Updated file saved locally at: {local_file}")
            print(f"📋 Backup saved at: {backup_file}")
            ftp.quit()
            sys.exit(0)

        # Upload to production
        print()
        if upload_users_json(ftp, ftp_path, local_file):
            print("\n" + "=" * 70)
            print("✅ SUCCESS - Production users.json updated!")
            print("=" * 70)
            print(f"\n📋 Local backup saved at: {backup_file}")
            print("\nNext steps:")
            print("1. Test: https://www.lastwar1586.online/admin/users.json")
            print("   Expected: 403 Forbidden")
            print("2. Test: https://www.lastwar1586.online/admin/dashboard.php")
            print("   Expected: Dashboard loads normally")
            print()
        else:
            print("\n❌ Upload failed")
            print(f"📋 Updated file saved locally at: {local_file}")
            print(f"📋 Backup saved at: {backup_file}")

        ftp.quit()

    except Exception as e:
        print(f"\n❌ FTP Error: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
