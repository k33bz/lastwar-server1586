#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Run FTP Update with Windows Credential Manager Integration

This script:
1. Retrieves FTP password from Windows Credential Manager
2. Downloads users.json from production
3. Adds powereditor field to all users
4. Uploads the updated file back to production
"""

import json
import os
import sys
import subprocess
from ftplib import FTP
from datetime import datetime
from pathlib import Path

# Fix Windows console encoding for emojis
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def get_windows_credential(target_name):
    """Retrieve password from Windows Generic Credential Manager using PowerShell."""

    # Use the PowerShell script to get generic credentials
    script_path = Path(__file__).parent / "get-generic-credential.ps1"

    try:
        result = subprocess.run(
            ["powershell", "-ExecutionPolicy", "Bypass", "-File", str(script_path), "-TargetName", target_name],
            capture_output=True,
            text=True,
            check=True
        )
        password = result.stdout.strip()
        if password:
            return password
        else:
            return None
    except subprocess.CalledProcessError as e:
        print(f"❌ Error retrieving credential: {e.stderr}")
        return None

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
    print("with Windows Credential Manager Integration")
    print("=" * 70)
    print()

    # FTP configuration
    target_name = "ftp_lastwar1586.online"
    ftp_host = "ftp.k33bz.com"
    ftp_user = "ftpuploader@lastwar1586.online"
    ftp_path = "admin"

    # Retrieve password from Windows Credential Manager
    print(f"🔑 Retrieving FTP password from Windows Credential Manager")
    print(f"   Target: {target_name}")
    print(f"   User: {ftp_user}")

    ftp_pass = get_windows_credential(target_name)

    if not ftp_pass:
        print("\n❌ Failed to retrieve FTP password from Credential Manager")
        print("\nPlease ensure the credential exists:")
        print(f"   Target: {target_name}")
        print(f"   User: {ftp_user}")
        sys.exit(1)

    print(f"✅ Password retrieved: {ftp_pass[:4]}{'*' * (len(ftp_pass) - 4)}")
    print()

    # Create temp directory for downloads
    temp_dir = Path('temp')
    temp_dir.mkdir(exist_ok=True)

    timestamp = datetime.now().strftime('%Y%m%d-%H%M%S')
    local_file = temp_dir / 'users.json'
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
            ftp.quit()
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
            print("3. Test: Login and verify power editor features work")
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
