# Deployment Guide

This guide explains how to deploy the Server 1586 website to your hosting provider using FTP or SFTP with credentials stored in Windows Credential Manager.

## Prerequisites

Install required Python packages:
```bash
# For FTP deployment
pip install pywin32

# For SFTP deployment (alternative)
pip install pysftp keyring pywin32
```

## Setup

### 1. Store Credentials

**For FTP (Recommended):**
```powershell
cmdkey /generic:ftp_example.com /user:YOUR_USERNAME /pass:"YOUR_PASSWORD"
```

**For SFTP (Alternative):**
```powershell
cmdkey /generic:sftp_server1586 /user:YOUR_USERNAME /pass:"YOUR_PASSWORD"
```

Or manually via GUI:
1. Open Start Menu → Search "Credential Manager"
2. Click "Windows Credentials"
3. Click "Add a generic credential"
4. Fill in:
   - **Internet or network address**: `ftp_example.com` (or `sftp_server1586`)
   - **User name**: Your FTP/SFTP username
   - **Password**: Your FTP/SFTP password
5. Click OK

### 2. Configure Deployment Script

**For FTP:** Edit `scripts/deploy-ftp.py` and update these values:

```python
CREDENTIAL_NAME = "ftp_example.com"
FTP_HOST = "ftp.example.com"
FTP_PORT = 21
REMOTE_PATH = "/"
```

**For SFTP:** Edit `scripts/deploy-sftp.py` and update these values:

```python
CREDENTIAL_NAME = "sftp_server1586"
SFTP_HOST = "your-host.com"
SFTP_PORT = 22
REMOTE_PATH = "/public_html"
```

## Usage

### Deploy Website

**Using FTP (Recommended):**
```bash
python scripts/deploy-ftp.py
```

**Using SFTP (Alternative):**
```bash
python scripts/deploy-sftp.py
```

The script will:
1. Read credentials from Windows Credential Manager
2. Connect to your FTP/SFTP server
3. Upload all website files to the remote directory
4. Display a summary of uploaded/failed files

### Files Deployed

The script uploads these files:
- `index.html` / `index_remote.html`
- `.htaccess`
- `css/styles.css`
- `js/app.js`
- `data/council.js`
- `data/alliances.json`
- `data/rules.json`
- `data/amendments.json`
- `data/rotation-schedule.json`
- `data/rotation-schedule.js`
- `rotation-schedule.js`
- `logo_extractor.html`

### File Exclusions

The `.ftpignore` file controls which files are excluded from deployment:
- Git files (`.git/`, `.gitignore`)
- Development files (`*.py`, `*.md`, except root `.htaccess`)
- Test files, screenshots, local configs

## Security Notes

- **Credentials**: Never commit credentials to git. Windows Credential Manager keeps them encrypted.
- **FTP Security**: FTP transmits passwords in plain text. Use SFTP for better security.
- **SFTP Host Key Verification**: The SFTP script disables host key checking for simplicity. For production, you should verify the host key.
- **SSH Keys**: For better security, consider using SSH key authentication instead of passwords.

## Troubleshooting

### "Credential not found" error
Make sure you stored the credential with the exact name.

Verify it's stored:
```powershell
cmdkey /list | Select-String "ftp_example.com"
# or
cmdkey /list | Select-String "sftp_server1586"
```

### "Connection refused" error
- Verify host and port are correct
- Check your firewall allows outbound connections
- Confirm your hosting provider allows FTP/SFTP connections
- Try disabling VPN if connection fails

### "Login authentication failed" error
- Verify username format is correct (e.g., `user@domain.com`)
- Re-save credentials with correct username/password
- Check if account is properly configured on hosting provider

### "Permission denied" error
- Verify your username and password are correct
- Check that `REMOTE_PATH` exists and you have write permissions
- Verify FTP user has access to the web root directory

### "Module not found" error
Install missing packages:
```bash
pip install pywin32  # For FTP
pip install pysftp keyring pywin32  # For SFTP
```

### "Too many connections" error
- Wait a few minutes before retrying
- Check if previous connections were properly closed
- Contact hosting provider about connection limits

## Updating Credentials

To update stored credentials:
```powershell
# Remove old credential
cmdkey /delete:ftp_example.com

# Add new credential
cmdkey /generic:ftp_example.com /user:NEW_USERNAME /pass:"NEW_PASSWORD"
```

## Alternative: SSH Key Authentication (SFTP only)

For better security, you can use SSH keys instead of passwords:

1. Generate SSH key pair:
   ```bash
   ssh-keygen -t rsa -b 4096 -f ~/.ssh/server1586_sftp
   ```

2. Add public key to your hosting provider

3. Modify the SFTP script to use the private key instead of password
