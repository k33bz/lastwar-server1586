#!/usr/bin/env python3
"""
Deploy Server 1586 Website via FTP

Reads credentials from Windows Credential Manager and uploads website files
to the hosting provider via FTP. Respects .ftpignore for excluding files.

Usage: python scripts/deploy-ftp.py

Requirements:
    pip install pywin32
"""

import os
import sys
from ftplib import FTP
from pathlib import Path
import fnmatch

# Configuration
CREDENTIAL_NAME = "ftp_example.com"  # Name used in Windows Credential Manager
FTP_HOST = "ftp.example.com"  # FTP host
FTP_PORT = 21
REMOTE_PATH = "/"  # Remote path (should be web root directory)

# Project directories
PROJECT_DIR = Path(__file__).parent.parent
FTPIGNORE_FILE = PROJECT_DIR / ".ftpignore"


def load_ftpignore():
    """Load .ftpignore patterns."""
    patterns = []

    if FTPIGNORE_FILE.exists():
        with open(FTPIGNORE_FILE, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                # Skip empty lines and comments
                if line and not line.startswith('#'):
                    patterns.append(line)

    return patterns


def should_ignore(file_path, ignore_patterns):
    """Check if a file should be ignored based on .ftpignore patterns."""
    file_str = str(file_path).replace('\\', '/')

    for pattern in ignore_patterns:
        # Handle negation patterns (!)
        if pattern.startswith('!'):
            # If pattern matches after removing !, don't ignore
            if fnmatch.fnmatch(file_str, pattern[1:]):
                return False
        else:
            # Check if pattern matches
            if fnmatch.fnmatch(file_str, pattern) or fnmatch.fnmatch(file_str, f'*/{pattern}'):
                return True
            # Check directory patterns
            if pattern.endswith('/') and file_str.startswith(pattern):
                return True

    return False


def get_files_to_upload(ignore_patterns):
    """Scan project directory and get list of files to upload."""
    files_to_upload = []

    # File extensions to include
    include_extensions = {'.html', '.css', '.js', '.json', '.csv', '.txt', '.xml', '.ico', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.htaccess'}

    # Directories to scan
    scan_dirs = ['css', 'js', 'data']

    # Add root files (including .htaccess)
    for file in PROJECT_DIR.glob('*'):
        if file.is_file():
            rel_path = file.relative_to(PROJECT_DIR)
            # Include .htaccess and files with allowed extensions
            if (file.name == '.htaccess' or file.suffix in include_extensions) and not should_ignore(rel_path, ignore_patterns):
                files_to_upload.append(rel_path)

    # Add files from subdirectories
    for dir_name in scan_dirs:
        dir_path = PROJECT_DIR / dir_name
        if dir_path.exists():
            for file in dir_path.rglob('*'):
                if file.is_file():
                    rel_path = file.relative_to(PROJECT_DIR)
                    if not should_ignore(rel_path, ignore_patterns) and file.suffix in include_extensions:
                        files_to_upload.append(rel_path)

    return sorted(files_to_upload)


def get_credentials():
    """Retrieve FTP credentials from Windows Credential Manager."""
    try:
        import win32cred

        # Read the credential
        cred = win32cred.CredRead(
            TargetName=CREDENTIAL_NAME,
            Type=win32cred.CRED_TYPE_GENERIC
        )

        username = cred['UserName']
        # Password is stored as bytes, decode it
        password = cred['CredentialBlob'].decode('utf-16-le')

        return username, password

    except ImportError:
        print("[ERROR] win32cred not available. Install pywin32:")
        print("        pip install pywin32")
        return None, None
    except Exception as e:
        print(f"[ERROR] Credential '{CREDENTIAL_NAME}' not found in Windows Credential Manager")
        print(f"        Please add it using:")
        print(f"        cmdkey /generic:{CREDENTIAL_NAME} /user:USERNAME /pass:PASSWORD")
        print(f"        Error details: {e}")
        return None, None


def upload_files(username, password):
    """Upload website files via FTP."""
    try:
        print("=" * 60)
        print("Server 1586 - FTP Deployment")
        print("=" * 60)

        # Load ignore patterns
        print(f"\n[1/4] Loading .ftpignore patterns...")
        ignore_patterns = load_ftpignore()
        if ignore_patterns:
            print(f"      [OK] Loaded {len(ignore_patterns)} ignore patterns")
        else:
            print(f"      [INFO] No .ftpignore file found, uploading all files")

        # Get files to upload
        print(f"\n[2/4] Scanning for files to upload...")
        files_to_upload = get_files_to_upload(ignore_patterns)
        print(f"      [OK] Found {len(files_to_upload)} files to upload")

        print(f"\n[3/4] Connecting to {FTP_HOST}:{FTP_PORT}...")

        # Create FTP connection with passive mode
        ftp = FTP()
        ftp.connect(FTP_HOST, FTP_PORT, timeout=60)
        ftp.set_pasv(True)  # Enable passive mode (required by some servers)
        ftp.login(username, password)

        print(f"      [OK] Connected as {username}")

        # Change to remote directory
        try:
            ftp.cwd(REMOTE_PATH)
            print(f"      [OK] Changed to {REMOTE_PATH}")
        except Exception as e:
            print(f"      [INFO] Using current directory: {ftp.pwd()}")

        # Upload files
        print(f"\n[4/4] Uploading files...")
        uploaded_count = 0
        failed_count = 0

        for file_path in files_to_upload:
            local_file = PROJECT_DIR / file_path

            if not local_file.exists():
                print(f"      [SKIP] {file_path} (not found)")
                failed_count += 1
                continue

            try:
                # Create remote directories if needed
                remote_file = str(file_path).replace("\\", "/")
                remote_dir = os.path.dirname(remote_file)

                if remote_dir:
                    # Try to create directory structure
                    parts = remote_dir.split('/')
                    current_path = ftp.pwd()

                    for part in parts:
                        try:
                            ftp.cwd(part)
                        except:
                            # Directory doesn't exist, create it
                            try:
                                ftp.mkd(part)
                                ftp.cwd(part)
                            except Exception as e:
                                print(f"      [WARNING] Could not create directory {part}: {e}")

                    # Return to base remote path
                    ftp.cwd(current_path)

                # Upload file
                with open(local_file, 'rb') as f:
                    ftp.storbinary(f'STOR {remote_file}', f)

                print(f"      [OK] {file_path}")
                uploaded_count += 1
            except Exception as e:
                print(f"      [ERROR] {file_path}: {e}")
                failed_count += 1

        # Close connection
        ftp.quit()

        print("\n" + "=" * 60)
        print(f"Deployment Summary:")
        print(f"  Uploaded: {uploaded_count} files")
        print(f"  Failed:   {failed_count} files")
        print("=" * 60)

        return failed_count == 0

    except Exception as e:
        print(f"\n[ERROR] FTP connection failed: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    """Main deployment function."""
    # Check if required packages are installed
    try:
        import win32cred
    except ImportError:
        print("[ERROR] Missing required package. Please run:")
        print("        pip install pywin32")
        sys.exit(1)

    # Get credentials from Windows Credential Manager
    username, password = get_credentials()

    if not username or not password:
        print("\n[ERROR] Could not retrieve credentials")
        sys.exit(1)

    print(f"[INFO] Using credentials: {username}")

    # Upload files
    success = upload_files(username, password)

    if success:
        print("\n[SUCCESS] Deployment completed successfully!")
        print(f"         Website: https://www.example.com")
        sys.exit(0)
    else:
        print("\n[ERROR] Deployment failed")
        sys.exit(1)


if __name__ == "__main__":
    main()
