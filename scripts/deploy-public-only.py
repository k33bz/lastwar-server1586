#!/usr/bin/env python3
"""
Deploy Public Site Only
Version: 1.0.0
Date: 2025-10-28

Simple deployment script for uploading only the public-facing static website
(no admin panel or backend files).

Usage:
    python scripts/deploy-public-only.py

Configuration:
    Set environment variables or edit FTP credentials below:
    - FTP_HOST
    - FTP_USER
    - FTP_PASS
    - REMOTE_DIR (optional, defaults to public_html)
"""

import ftplib
import os
import sys

# FTP Configuration
FTP_HOST = os.environ.get('FTP_HOST', 'ftp.k33bz.com')
FTP_USER = os.environ.get('FTP_USER', 'ftpuploader@lastwar1586.online')
FTP_PASS = os.environ.get('FTP_PASS', '')
REMOTE_DIR = os.environ.get('REMOTE_DIR', '/')

# Public site files only (no admin, no scripts)
PUBLIC_FILES = [
    "index.html",
    "version.json",
    "css/styles.css",
    "js/app.js",
    "data/council.js",
    "data/alliances.json",
    "data/rules.json",
    "data/amendments.json",
    "data/rotation-schedule.json",
    "data/server-info.json",
    "data/signature-history.json",
    "data/power-history.csv",
]


def create_remote_directory(ftp, path):
    """Create a directory on the FTP server if it doesn't exist."""
    dirs = path.split('/')
    current = ''
    for d in dirs:
        if not d:
            continue
        current += '/' + d if current else d
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass  # Directory already exists


def upload_file(ftp, local_path, remote_path):
    """Upload a single file to the FTP server."""
    try:
        # Create remote directory if needed
        remote_dir = os.path.dirname(remote_path)
        if remote_dir:
            create_remote_directory(ftp, remote_dir)

        # Upload file
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_path}', f)
        return True
    except Exception as e:
        print(f"  ✗ Error: {e}")
        return False


def get_file_size(file_path):
    """Get human-readable file size."""
    size = os.path.getsize(file_path)
    for unit in ['B', 'KB', 'MB', 'GB']:
        if size < 1024.0:
            return f"{size:.1f} {unit}"
        size /= 1024.0
    return f"{size:.1f} TB"


def deploy_public_site():
    """Deploy only the public-facing files."""
    print("=" * 60)
    print("  Public Site Deployment (Static Frontend Only)")
    print("=" * 60)
    print()

    # Check credentials
    if not FTP_PASS:
        print("❌ Error: FTP_PASS not set")
        print()
        print("Set environment variable:")
        print("  Windows: set FTP_PASS=your_password")
        print("  Linux/Mac: export FTP_PASS=your_password")
        print()
        print("Or edit this script to hardcode credentials (not recommended)")
        sys.exit(1)

    # Verify files exist
    print("📋 Checking files...")
    missing_files = []
    total_size = 0
    for file_path in PUBLIC_FILES:
        if not os.path.exists(file_path):
            missing_files.append(file_path)
            print(f"  ✗ Missing: {file_path}")
        else:
            size = os.path.getsize(file_path)
            total_size += size
            print(f"  ✓ {file_path} ({get_file_size(file_path)})")

    if missing_files:
        print()
        print(f"❌ Error: {len(missing_files)} files missing")
        sys.exit(1)

    print()
    print(f"📦 Total size: {get_file_size(os.path.join('.', 'dummy')) if total_size < 1024 else f'{total_size / 1024:.1f} KB'}")
    print()

    # Connect to FTP
    print(f"🔌 Connecting to {FTP_HOST}...")
    try:
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"  ✓ Connected as {FTP_USER}")
    except Exception as e:
        print(f"  ✗ Connection failed: {e}")
        sys.exit(1)

    # Change to remote directory
    try:
        if REMOTE_DIR != '/':
            ftp.cwd(REMOTE_DIR)
            print(f"  ✓ Changed to {REMOTE_DIR}")
    except Exception as e:
        print(f"  ✗ Cannot access {REMOTE_DIR}: {e}")
        ftp.quit()
        sys.exit(1)

    print()
    print("📤 Uploading files...")

    # Upload each file
    success_count = 0
    failed_count = 0

    for file_path in PUBLIC_FILES:
        print(f"  {file_path}...", end=" ")
        if upload_file(ftp, file_path, file_path):
            print("✓")
            success_count += 1
        else:
            print("✗")
            failed_count += 1

    # Close connection
    ftp.quit()

    # Summary
    print()
    print("=" * 60)
    print("  Deployment Summary")
    print("=" * 60)
    print(f"  ✓ Uploaded: {success_count} files")
    if failed_count > 0:
        print(f"  ✗ Failed: {failed_count} files")
    print()

    if failed_count == 0:
        print("✅ Public site deployment complete!")
        print()
        print("Next steps:")
        print("  1. Visit your website to verify")
        print("  2. Check browser console for errors (F12)")
        print("  3. Test all sections load correctly")
        print()
        print("See docs/PUBLIC_SITE_DEPLOYMENT.md for verification checklist")
    else:
        print("⚠️  Deployment completed with errors")
        print()
        print("Check the failed files and try again")
        sys.exit(1)


if __name__ == "__main__":
    # Change to repository root
    script_dir = os.path.dirname(os.path.abspath(__file__))
    repo_root = os.path.dirname(script_dir)
    os.chdir(repo_root)

    deploy_public_site()
