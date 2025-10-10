#!/usr/bin/env python3
"""
Deploy Server 1586 Website via SFTP

Reads credentials from Windows Credential Manager and uploads website files
to the hosting provider via SFTP.

Usage: python scripts/deploy-sftp.py

Requirements:
    pip install paramiko keyring pywin32
"""

import os
import sys
import keyring
from pathlib import Path

# Configuration
CREDENTIAL_NAME = "sftp_server1586"  # Name used in Windows Credential Manager
SFTP_HOST = "example.com"  # SFTP host
SFTP_PORT = 22
REMOTE_PATH = "/public_html"  # Remote path (update if different)

# Local files to upload
PROJECT_DIR = Path(__file__).parent.parent
FILES_TO_UPLOAD = [
    "index.html",
    "css/styles.css",
    "js/app.js",
    "data/council.js",
    "data/alliances.json",
    "data/rules.json",
    "data/amendments.json",
    "data/rotation-schedule.json",
]


def get_credentials():
    """Retrieve SFTP credentials from Windows Credential Manager."""
    try:
        # For Windows, we need to use win32cred to read generic credentials
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

    except Exception as e:
        print(f"[ERROR] Failed to retrieve credentials: {e}")
        return None, None


def upload_files(username, password):
    """Upload website files via SFTP."""
    try:
        import paramiko

        print("=" * 60)
        print("Server 1586 - SFTP Deployment")
        print("=" * 60)

        print(f"\n[1/3] Connecting to {SFTP_HOST}:{SFTP_PORT}...")

        # Create SSH client
        transport = paramiko.Transport((SFTP_HOST, SFTP_PORT))
        transport.connect(username=username, password=password)

        sftp = paramiko.SFTPClient.from_transport(transport)
        print(f"      [OK] Connected as {username}")

        # Change to remote directory
        print(f"\n[2/3] Changing to remote directory: {REMOTE_PATH}")
        try:
            sftp.chdir(REMOTE_PATH)
            print(f"      [OK] Changed to {REMOTE_PATH}")
        except Exception as e:
            print(f"      [ERROR] Failed to change directory: {e}")
            sftp.close()
            transport.close()
            return False

        # Upload files
        print(f"\n[3/3] Uploading files...")
        uploaded_count = 0
        failed_count = 0

        for file_path in FILES_TO_UPLOAD:
            local_file = PROJECT_DIR / file_path

            if not local_file.exists():
                print(f"      [SKIP] {file_path} (not found)")
                failed_count += 1
                continue

            try:
                # Create remote directories if needed
                remote_file = file_path.replace("\\", "/")
                remote_dir = os.path.dirname(remote_file)

                if remote_dir:
                    # Try to create directory structure
                    parts = remote_dir.split('/')
                    current_path = ''
                    for part in parts:
                        current_path = f"{current_path}/{part}" if current_path else part
                        try:
                            sftp.stat(current_path)
                        except FileNotFoundError:
                            sftp.mkdir(current_path)

                # Upload file
                sftp.put(str(local_file), remote_file)
                print(f"      [OK] {file_path}")
                uploaded_count += 1
            except Exception as e:
                print(f"      [ERROR] {file_path}: {e}")
                failed_count += 1

        # Close connections
        sftp.close()
        transport.close()

        print("\n" + "=" * 60)
        print(f"Deployment Summary:")
        print(f"  Uploaded: {uploaded_count} files")
        print(f"  Failed:   {failed_count} files")
        print("=" * 60)

        return failed_count == 0

    except ImportError:
        print("\n[ERROR] paramiko not installed. Please run:")
        print("        pip install paramiko")
        return False
    except Exception as e:
        print(f"\n[ERROR] SFTP connection failed: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    """Main deployment function."""
    # Check if required packages are installed
    try:
        import keyring
        import paramiko
        import win32cred
    except ImportError as e:
        print("[ERROR] Missing required packages. Please run:")
        print("        pip install paramiko keyring pywin32")
        sys.exit(1)

    # Get credentials from Windows Credential Manager
    username, password = get_credentials()

    if not username or not password:
        print("\n[ERROR] Could not retrieve credentials")
        sys.exit(1)

    # Upload files
    success = upload_files(username, password)

    if success:
        print("\n[SUCCESS] Deployment completed successfully!")
        print(f"         Website: https://{SFTP_HOST}")
        sys.exit(0)
    else:
        print("\n[ERROR] Deployment failed")
        sys.exit(1)


if __name__ == "__main__":
    main()
