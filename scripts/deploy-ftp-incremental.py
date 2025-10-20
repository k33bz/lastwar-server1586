#!/usr/bin/env python3
"""
Incremental FTP Deployment Script with Checksum/Timestamp Comparison
Only uploads files that have changed, dramatically speeding up deployments.

Features:
- MD5 checksum comparison (most reliable)
- Modification time comparison (fallback)
- Deployment state caching (.deploy-state.json)
- Progress indicators
- Detailed statistics

Documentation:
- Deployment Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/DEPLOYMENT.md
- Scripts README: https://github.com/k33bz/lastwar-server1586/blob/mainline/scripts/README.md

GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues

Usage:
- Standard: python deploy-ftp-incremental.py
- Force full upload: python deploy-ftp-incremental.py --force
- Checksum only: python deploy-ftp-incremental.py --checksum-only
"""

import ftplib
import os
import sys
import json
import hashlib
import time
from pathlib import Path
from datetime import datetime

# Configuration
FTP_HOST = os.environ.get('FTP_HOST', 'ftp.example.com')
FTP_PORT = 21
FTP_USER = os.environ.get('FTP_USER')
FTP_PASS = os.environ.get('FTP_PASS')
FTP_REMOTE_DIR = '/'

# Get project root
PROJECT_ROOT = Path(__file__).parent.parent
STATE_FILE = PROJECT_ROOT / '.deploy-state.json'

# Parse command line arguments
FORCE_UPLOAD = '--force' in sys.argv
CHECKSUM_ONLY = '--checksum-only' in sys.argv

def calculate_md5(file_path):
    """Calculate MD5 checksum of a file"""
    hash_md5 = hashlib.md5()
    try:
        with open(file_path, 'rb') as f:
            for chunk in iter(lambda: f.read(4096), b""):
                hash_md5.update(chunk)
        return hash_md5.hexdigest()
    except Exception as e:
        print(f"      [WARN] Could not calculate checksum for {file_path}: {e}")
        return None

def load_deployment_state():
    """Load previous deployment state (checksums and timestamps)"""
    if STATE_FILE.exists() and not FORCE_UPLOAD:
        try:
            with open(STATE_FILE, 'r') as f:
                return json.load(f)
        except Exception as e:
            print(f"[WARN] Could not load deployment state: {e}")
    return {'files': {}, 'last_deploy': None}

def save_deployment_state(state):
    """Save deployment state for next run"""
    state['last_deploy'] = datetime.now().isoformat()
    try:
        with open(STATE_FILE, 'w') as f:
            json.dump(state, f, indent=2)
    except Exception as e:
        print(f"[WARN] Could not save deployment state: {e}")

def load_ftpignore():
    """Load .ftpignore patterns"""
    ftpignore_path = PROJECT_ROOT / '.ftpignore'
    ignore_patterns = []
    allow_patterns = []

    if ftpignore_path.exists():
        with open(ftpignore_path, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):
                    if line.startswith('!'):
                        allow_patterns.append(line[1:])
                    else:
                        ignore_patterns.append(line)

    return ignore_patterns, allow_patterns

def matches_pattern(file_path, pattern):
    """Check if file_path matches the given pattern"""
    path_str = str(file_path)

    if pattern.endswith('/'):
        if pattern.rstrip('/') in path_str.split(os.sep):
            return True
    elif '*' in pattern:
        import fnmatch
        if fnmatch.fnmatch(path_str, pattern) or fnmatch.fnmatch(os.path.basename(path_str), pattern):
            return True
    else:
        if pattern in path_str or os.path.basename(path_str) == pattern:
            return True

    return False

def should_ignore(file_path, ignore_patterns, allow_patterns):
    """Check if file should be ignored"""
    for pattern in allow_patterns:
        if matches_pattern(file_path, pattern):
            return False

    for pattern in ignore_patterns:
        if matches_pattern(file_path, pattern):
            return True

    return False

def file_needs_upload(local_path, relative_path, prev_state):
    """Determine if file needs to be uploaded"""
    # Force upload if requested
    if FORCE_UPLOAD:
        return True, "force"

    relative_str = str(relative_path).replace(os.sep, '/')

    # New file (not in previous state)
    if relative_str not in prev_state['files']:
        return True, "new"

    prev_file = prev_state['files'][relative_str]

    # Check modification time
    current_mtime = os.path.getmtime(local_path)
    prev_mtime = prev_file.get('mtime', 0)

    if not CHECKSUM_ONLY and current_mtime <= prev_mtime:
        # File hasn't been modified since last deploy
        return False, "unchanged"

    # Calculate checksum for changed files
    current_checksum = calculate_md5(local_path)
    prev_checksum = prev_file.get('checksum')

    if current_checksum and current_checksum == prev_checksum:
        # Checksum matches - file unchanged
        return False, "checksum-match"

    # File has changed
    return True, "modified"

def get_files_to_upload(ignore_patterns, allow_patterns, prev_state):
    """Get list of files that need uploading"""
    all_files = []
    files_to_upload = []
    skipped = []

    # Scan all files
    for root, dirs, filenames in os.walk(PROJECT_ROOT):
        dirs[:] = [d for d in dirs if not should_ignore(os.path.join(root, d), ignore_patterns, allow_patterns)]

        for filename in filenames:
            file_path = Path(root) / filename
            relative_path = file_path.relative_to(PROJECT_ROOT)

            if not should_ignore(relative_path, ignore_patterns, allow_patterns):
                all_files.append(relative_path)

                needs_upload, reason = file_needs_upload(file_path, relative_path, prev_state)

                if needs_upload:
                    files_to_upload.append({
                        'path': relative_path,
                        'size': os.path.getsize(file_path),
                        'reason': reason
                    })
                else:
                    skipped.append({
                        'path': relative_path,
                        'reason': reason
                    })

    return all_files, files_to_upload, skipped

def ensure_remote_dir(ftp, remote_path):
    """Create remote directory if it doesn't exist"""
    if not remote_path:
        return

    dirs = remote_path.split('/')
    current_path = ''

    for dir_name in dirs:
        if not dir_name:
            continue

        current_path = f"{current_path}/{dir_name}" if current_path else dir_name

        try:
            ftp.cwd('/' + current_path)
        except ftplib.error_perm:
            try:
                ftp.mkd('/' + current_path)
            except ftplib.error_perm:
                pass

def upload_files(ftp, files_to_upload, new_state):
    """Upload files to FTP server and update state"""
    uploaded = 0
    failed = 0
    total_size = sum(f['size'] for f in files_to_upload)
    uploaded_size = 0

    print(f"[4/5] Uploading {len(files_to_upload)} changed files ({format_size(total_size)})...")

    for file_info in files_to_upload:
        file_path = file_info['path']
        local_path = PROJECT_ROOT / file_path
        remote_path = str(file_path).replace(os.sep, '/')
        reason = file_info['reason']

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

            uploaded_size += file_info['size']
            progress = (uploaded_size / total_size) * 100 if total_size > 0 else 0

            print(f"      [{progress:5.1f}%] {remote_path} ({format_size(file_info['size'])}) [{reason}]")
            uploaded += 1

            # Update state
            new_state['files'][remote_path] = {
                'checksum': calculate_md5(local_path),
                'mtime': os.path.getmtime(local_path),
                'size': file_info['size'],
                'uploaded_at': datetime.now().isoformat()
            }

        except Exception as e:
            print(f"      [FAIL] {remote_path}: {e}")
            failed += 1

    return uploaded, failed

def format_size(bytes):
    """Format bytes to human readable size"""
    for unit in ['B', 'KB', 'MB', 'GB']:
        if bytes < 1024.0:
            return f"{bytes:.1f} {unit}"
        bytes /= 1024.0
    return f"{bytes:.1f} TB"

def main():
    """Main deployment function"""
    start_time = time.time()

    print("=" * 70)
    print("Server 1586 - Incremental FTP Deployment")
    print("=" * 70)
    print()

    if FORCE_UPLOAD:
        print("[INFO] Force upload mode - all files will be uploaded")
    if CHECKSUM_ONLY:
        print("[INFO] Checksum-only mode - ignoring modification times")

    # Check for required environment variables
    if not FTP_USER or not FTP_PASS:
        print("[ERROR] Missing FTP credentials!")
        sys.exit(1)

    print(f"[INFO] Deploying to {FTP_USER}@{FTP_HOST}")
    print()

    # Load previous deployment state
    print("[1/5] Loading deployment state...")
    prev_state = load_deployment_state()

    if prev_state.get('last_deploy'):
        print(f"      [OK] Last deployment: {prev_state['last_deploy']}")
        print(f"      [OK] {len(prev_state['files'])} files in state cache")
    else:
        print(f"      [INFO] No previous deployment state - full upload required")
    print()

    # Load ignore patterns
    print("[2/5] Loading .ftpignore patterns...")
    ignore_patterns, allow_patterns = load_ftpignore()
    total_patterns = len(ignore_patterns) + len(allow_patterns)
    print(f"      [OK] Loaded {total_patterns} patterns")
    print()

    # Scan files and determine what needs uploading
    print("[3/5] Analyzing files...")
    all_files, files_to_upload, skipped = get_files_to_upload(ignore_patterns, allow_patterns, prev_state)

    print(f"      [OK] Total files: {len(all_files)}")
    print(f"      [OK] Files to upload: {len(files_to_upload)}")
    print(f"      [OK] Files skipped (unchanged): {len(skipped)}")

    if len(files_to_upload) == 0:
        print()
        print("=" * 70)
        print("✅ No files need uploading - everything is up to date!")
        print("=" * 70)
        sys.exit(0)

    # Show skip reasons breakdown
    if skipped:
        skip_reasons = {}
        for item in skipped:
            reason = item['reason']
            skip_reasons[reason] = skip_reasons.get(reason, 0) + 1

        print()
        print("      Skip reasons:")
        for reason, count in skip_reasons.items():
            print(f"        - {reason}: {count} files")

    print()

    # Connect to FTP server
    print(f"[INFO] Connecting to {FTP_HOST}:{FTP_PORT}...")

    try:
        ftp = ftplib.FTP()
        ftp.connect(FTP_HOST, FTP_PORT, timeout=30)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"      [OK] Connected as {FTP_USER}")
        ftp.cwd(FTP_REMOTE_DIR)
        print(f"      [OK] Changed to {FTP_REMOTE_DIR}")
        print()

        # Create new state for this deployment
        new_state = {'files': prev_state['files'].copy()}

        # Upload files
        uploaded, failed = upload_files(ftp, files_to_upload, new_state)

        # Close connection
        ftp.quit()

        # Save deployment state
        if failed == 0:
            print()
            print("[5/5] Saving deployment state...")
            save_deployment_state(new_state)
            print(f"      [OK] State saved to {STATE_FILE.name}")

        # Calculate deployment time
        elapsed_time = time.time() - start_time

        # Print summary
        print()
        print("=" * 70)
        print("Deployment Summary:")
        print(f"  Total files:   {len(all_files)}")
        print(f"  Uploaded:      {uploaded} files")
        print(f"  Skipped:       {len(skipped)} files (unchanged)")
        print(f"  Failed:        {failed} files")
        print(f"  Time savings:  ~{int((len(skipped) / max(len(all_files), 1)) * 100)}% faster")
        print(f"  Duration:      {elapsed_time:.1f} seconds")
        print("=" * 70)
        print()

        if failed > 0:
            print("[WARNING] Deployment completed with errors!")
            print(f"          {failed} file(s) failed to upload")
            sys.exit(1)
        else:
            print("[SUCCESS] Incremental deployment completed successfully!")
            app_url = os.environ.get('APP_URL', 'production server')
            if app_url.startswith('http'):
                print(f"          Website: {app_url}")
            sys.exit(0)

    except Exception as e:
        print(f"[ERROR] Deployment failed: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()
