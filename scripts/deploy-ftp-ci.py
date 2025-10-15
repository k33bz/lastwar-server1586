#!/usr/bin/env python3
"""
FTP Deployment Script for CI/CD (GitHub Actions)
Reads FTP credentials from environment variables instead of Windows Credential Manager
"""

import ftplib
import os
import sys
from pathlib import Path

# Configuration
FTP_HOST = os.environ.get('FTP_HOST', 'ftp.example.com')
FTP_PORT = 21
FTP_USER = os.environ.get('FTP_USER')
FTP_PASS = os.environ.get('FTP_PASS')
FTP_REMOTE_DIR = '/'

# Get project root (parent of scripts directory)
PROJECT_ROOT = Path(__file__).parent.parent

def load_ftpignore():
    """Load .ftpignore patterns (supports ! for negation/allow list)"""
    ftpignore_path = PROJECT_ROOT / '.ftpignore'
    ignore_patterns = []
    allow_patterns = []

    if ftpignore_path.exists():
        with open(ftpignore_path, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):
                    if line.startswith('!'):
                        # Negation pattern - explicitly allow this file
                        allow_patterns.append(line[1:])  # Remove the ! prefix
                    else:
                        ignore_patterns.append(line)

    total = len(ignore_patterns) + len(allow_patterns)
    print(f"[1/4] Loading .ftpignore patterns...")
    print(f"      [OK] Loaded {total} patterns ({len(ignore_patterns)} ignore, {len(allow_patterns)} allow)")
    return ignore_patterns, allow_patterns

def matches_pattern(file_path, pattern):
    """Check if file_path matches the given pattern"""
    path_str = str(file_path)

    # Handle directory patterns (ending with /)
    if pattern.endswith('/'):
        if pattern.rstrip('/') in path_str.split(os.sep):
            return True
    # Handle wildcard patterns
    elif '*' in pattern:
        import fnmatch
        if fnmatch.fnmatch(path_str, pattern) or fnmatch.fnmatch(os.path.basename(path_str), pattern):
            return True
    # Handle exact matches
    else:
        if pattern in path_str or os.path.basename(path_str) == pattern:
            return True

    return False

def should_ignore(file_path, ignore_patterns, allow_patterns):
    """Check if file should be ignored (supports ! negation for allow list)"""
    # First check if file is explicitly allowed (negation patterns)
    for pattern in allow_patterns:
        if matches_pattern(file_path, pattern):
            return False  # Explicitly allowed, don't ignore

    # Then check if file matches ignore patterns
    for pattern in ignore_patterns:
        if matches_pattern(file_path, pattern):
            return True  # Matched ignore pattern, ignore it

    return False  # No match, don't ignore

def get_files_to_upload(ignore_patterns, allow_patterns):
    """Get list of files to upload (excluding ignored files)"""
    files = []

    for root, dirs, filenames in os.walk(PROJECT_ROOT):
        # Skip ignored directories
        dirs[:] = [d for d in dirs if not should_ignore(os.path.join(root, d), ignore_patterns, allow_patterns)]

        for filename in filenames:
            file_path = Path(root) / filename
            relative_path = file_path.relative_to(PROJECT_ROOT)

            if not should_ignore(relative_path, ignore_patterns, allow_patterns):
                files.append(relative_path)

    print(f"[2/4] Scanning for files to upload...")
    print(f"      [OK] Found {len(files)} files to upload")
    return files

def ensure_remote_dir(ftp, remote_path):
    """Create remote directory if it doesn't exist"""
    dirs = remote_path.split('/')
    current_dir = ''

    for dir_name in dirs:
        if not dir_name:
            continue

        current_dir = f"{current_dir}/{dir_name}" if current_dir else dir_name

        try:
            ftp.cwd(current_dir)
        except ftplib.error_perm:
            try:
                ftp.mkd(current_dir)
                ftp.cwd(current_dir)
            except ftplib.error_perm:
                pass

def upload_files(ftp, files):
    """Upload files to FTP server"""
    uploaded = 0
    failed = 0

    print("[4/4] Uploading files...")

    for file_path in files:
        local_path = PROJECT_ROOT / file_path
        remote_path = str(file_path).replace(os.sep, '/')

        # Create remote directory if needed
        remote_dir = '/'.join(remote_path.split('/')[:-1])
        if remote_dir:
            try:
                ftp.cwd(FTP_REMOTE_DIR)
                ensure_remote_dir(ftp, remote_dir)
                ftp.cwd(FTP_REMOTE_DIR)
            except Exception as e:
                print(f"      [WARN] Could not create directory {remote_dir}: {e}")

        # Upload file
        try:
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {remote_path}', f)
            print(f"      [OK] {remote_path}")
            uploaded += 1
        except Exception as e:
            print(f"      [FAIL] {remote_path}: {e}")
            failed += 1

    return uploaded, failed

def main():
    """Main deployment function"""
    print("=" * 60)
    print("Server 1586 - FTP Deployment (CI/CD)")
    print("=" * 60)
    print()

    # Check for required environment variables
    if not FTP_USER or not FTP_PASS:
        print("[ERROR] Missing FTP credentials!")
        print("        FTP_USER and FTP_PASS environment variables are required")
        print(f"        FTP_HOST: {FTP_HOST}")
        print(f"        FTP_USER: {'<set>' if FTP_USER else '<not set>'}")
        print(f"        FTP_PASS: {'<set>' if FTP_PASS else '<not set>'}")
        sys.exit(1)

    print(f"[INFO] Deploying to {FTP_USER}@{FTP_HOST}")
    print(f"[DEBUG] FTP_HOST: {FTP_HOST}")
    print(f"[DEBUG] FTP_PORT: {FTP_PORT}")
    print(f"[DEBUG] FTP_USER: {FTP_USER}")

    # Load ignore and allow patterns
    ignore_patterns, allow_patterns = load_ftpignore()

    # Get files to upload
    files = get_files_to_upload(ignore_patterns, allow_patterns)

    if not files:
        print("[ERROR] No files to upload!")
        sys.exit(1)

    # Connect to FTP server
    print(f"[3/4] Connecting to {FTP_HOST}:{FTP_PORT}...")

    try:
        ftp = ftplib.FTP()
        print(f"      Attempting connection to {FTP_HOST}:{FTP_PORT}...")
        ftp.connect(FTP_HOST, FTP_PORT, timeout=30)
        print(f"      Connection established, attempting login...")
        ftp.login(FTP_USER, FTP_PASS)
        print(f"      [OK] Connected and logged in as {FTP_USER}")

        # Change to remote directory
        ftp.cwd(FTP_REMOTE_DIR)
        print(f"      [OK] Changed to {FTP_REMOTE_DIR}")

        # Upload files
        uploaded, failed = upload_files(ftp, files)

        # Close connection
        ftp.quit()

        # Print summary
        print()
        print("=" * 60)
        print("Deployment Summary:")
        print(f"  Uploaded: {uploaded} files")
        print(f"  Failed:   {failed} files")
        print("=" * 60)
        print()

        if failed > 0:
            print("[WARNING] Deployment completed with errors!")
            print(f"          {failed} file(s) failed to upload")
            sys.exit(1)
        else:
            print("[SUCCESS] Deployment completed successfully!")
            # Use APP_URL from environment if set, otherwise use generic message
            app_url = os.environ.get('APP_URL', 'production server')
            if app_url.startswith('http'):
                print(f"         Website: {app_url}")
            else:
                print(f"         Website deployed successfully")
            sys.exit(0)

    except Exception as e:
        print(f"[ERROR] FTP connection failed: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
