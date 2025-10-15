#!/usr/bin/env python3
"""
Retrieve FTP credentials from Windows Credential Manager

Retrieves the password for ftp_lastwar1586.online from Windows Credential Manager
and sets up environment variables for the FTP update script.
"""

import subprocess
import sys

def get_windows_credential(target_name):
    """Retrieve password from Windows Credential Manager using PowerShell."""

    powershell_script = f"""
    [void][Windows.Security.Credentials.PasswordVault,Windows.Security.Credentials,ContentType=WindowsRuntime]
    $vault = New-Object Windows.Security.Credentials.PasswordVault
    try {{
        $cred = $vault.Retrieve("{target_name}", "ftpuploader@lastwar1586.online")
        $cred.RetrievePassword()
        Write-Output $cred.Password
    }} catch {{
        Write-Error "Credential not found: {target_name}"
        exit 1
    }}
    """

    try:
        result = subprocess.run(
            ["powershell", "-Command", powershell_script],
            capture_output=True,
            text=True,
            check=True
        )
        password = result.stdout.strip()
        if password:
            return password
        else:
            print("❌ Error: No password returned from Credential Manager")
            return None
    except subprocess.CalledProcessError as e:
        print(f"❌ Error retrieving credential: {e.stderr}")
        return None

def main():
    print("=" * 70)
    print("Windows Credential Manager - FTP Password Retrieval")
    print("=" * 70)
    print()

    target_name = "ftp_lastwar1586.online"
    print(f"🔑 Retrieving credential: {target_name}")
    print(f"👤 Username: ftpuploader@lastwar1586.online")

    password = get_windows_credential(target_name)

    if password:
        print("✅ Password retrieved successfully")
        print()
        print("FTP Credentials:")
        print(f"  Host: ftp.k33bz.com")
        print(f"  User: ftpuploader@lastwar1586.online")
        print(f"  Pass: {password[:4]}{'*' * (len(password) - 4)}")
        print(f"  Path: /admin")
        print()
        return {
            'FTP_HOST': 'ftp.k33bz.com',
            'FTP_USER': 'ftpuploader@lastwar1586.online',
            'FTP_PASS': password,
            'FTP_PATH': 'admin'
        }
    else:
        print("❌ Failed to retrieve password")
        sys.exit(1)

if __name__ == '__main__':
    creds = main()
    print("Environment variables ready for FTP script")
