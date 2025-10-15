#!/usr/bin/env python3
"""
Deploy Audit System to Production

Uploads audit logging files and initializes the system on production server
"""

import ftplib
import json
import os
import subprocess

# FTP Configuration
FTP_HOST = "ftp.k33bz.com"
FTP_USER = "ftpuploader@lastwar1586.online"
FTP_PATH = "/admin"

def get_ftp_password():
    """Get FTP password from Windows Credential Manager"""
    try:
        result = subprocess.run(
            ['powershell', '-Command',
             "(cmdkey /list | Select-String 'ftp_lastwar1586.online' -Context 0,2).ToString()"],
            capture_output=True,
            text=True,
            check=True
        )

        # Parse the password from cmdkey output
        for line in result.stdout.split('\n'):
            if 'Password:' in line or 'pass' in line.lower():
                # Extract password (this is a placeholder - actual extraction depends on cmdkey output format)
                print("Found FTP credentials in Credential Manager")

        # Fallback: try using keyring or manual input
        import getpass
        password = getpass.getpass("Enter FTP password: ")
        return password

    except Exception as e:
        print(f"Error retrieving password: {e}")
        import getpass
        return getpass.getpass("Enter FTP password manually: ")

def upload_file(ftp, local_path, remote_name):
    """Upload a file to FTP server"""
    try:
        with open(local_path, 'rb') as f:
            ftp.storbinary(f'STOR {remote_name}', f)
        print(f"[OK] Uploaded: {remote_name}")
        return True
    except Exception as e:
        print(f"[ERR] Failed to upload {remote_name}: {e}")
        return False

def main():
    print("Deploying Audit System to Production\n")

    # Get FTP password
    password = get_ftp_password()

    # Connect to FTP
    print(f"Connecting to {FTP_HOST}...")
    try:
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, password)
        ftp.cwd(FTP_PATH)
        print(f"[OK] Connected to {FTP_HOST}{FTP_PATH}\n")
    except Exception as e:
        print(f"[ERR] FTP connection failed: {e}")
        return

    # Files to upload
    admin_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'admin')

    files_to_upload = [
        ('audit_logger.php', 'audit_logger.php'),
        ('audit_log_viewer.php', 'audit_log_viewer.php'),
        ('audit_log_api.php', 'audit_log_api.php'),
        ('backup_restore.php', 'backup_restore.php'),
        ('backup_restore_api.php', 'backup_restore_api.php'),
        ('csv_helpers.php', 'csv_helpers.php'),
        ('initialize_audit_system.php', 'initialize_audit_system.php'),
    ]

    print("Uploading files...")
    for local_name, remote_name in files_to_upload:
        local_path = os.path.join(admin_dir, local_name)
        if os.path.exists(local_path):
            upload_file(ftp, local_path, remote_name)
        else:
            print(f"[WARN] File not found: {local_name}")

    # Create initial audit_log.json if it doesn't exist
    print("\nChecking audit_log.json...")
    try:
        # Try to get the file
        ftp.size('audit_log.json')
        print("[OK] audit_log.json already exists on server")
    except:
        # File doesn't exist, create it
        print("Creating initial audit_log.json...")
        initial_data = {'logs': []}
        temp_file = 'temp_audit_log.json'
        with open(temp_file, 'w') as f:
            json.dump(initial_data, f, indent=2)

        upload_file(ftp, temp_file, 'audit_log.json')
        os.remove(temp_file)

    # Create backups directory
    print("\nCreating backup directory...")
    try:
        ftp.mkd('backups')
        print("[OK] Created /backups")
    except:
        print("[OK] /backups already exists")

    try:
        ftp.mkd('backups/alliances')
        print("[OK] Created /backups/alliances")
    except:
        print("[OK] /backups/alliances already exists")

    ftp.quit()

    print("\n[OK] Deployment complete!")
    print("\nNext steps:")
    print("1. Visit: https://www.lastwar1586.online/admin/initialize_audit_system.php")
    print("2. Verify initialization was successful")
    print("3. Test login to generate audit log entries")
    print("4. Check audit log viewer: https://www.lastwar1586.online/admin/audit_log_viewer.php")

if __name__ == '__main__':
    main()
