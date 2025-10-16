# Deployment Guide

This guide explains how to deploy the Server 1586 website to your hosting provider using FTP with credentials stored in Windows Credential Manager.

## 📍 Navigation
- **← Back to Scripts**: [README.md](README.md)
- **← Back to Main**: [../README.md](../README.md)
- **📚 Full Documentation**: [../DOCUMENTATION.md](../DOCUMENTATION.md)
- **🔧 Admin Deployment**: [../admin/DEPLOYMENT.md](../admin/DEPLOYMENT.md)

## Prerequisites

Install required Python packages:
```bash
pip install pywin32
```

## Setup

### 1. Store Credentials

```powershell
cmdkey /generic:ftp_example.com /user:YOUR_USERNAME /pass:"YOUR_PASSWORD"
```

Or manually via GUI:
1. Open Start Menu → Search "Credential Manager"
2. Click "Windows Credentials"
3. Click "Add a generic credential"
4. Fill in:
   - **Internet or network address**: `ftp_example.com`
   - **User name**: Your FTP username
   - **Password**: Your FTP password
5. Click OK

### 2. Configure Deployment Script

Edit `scripts/deploy-ftp.py` and verify these settings:

```python
CREDENTIAL_NAME = "ftp_example.com"
FTP_HOST = "ftp.example.com"
FTP_PORT = 21
REMOTE_PATH = "/"
```

## Usage

### Deploy Website

```bash
python scripts/deploy-ftp.py
```

The script will:
1. Read credentials from Windows Credential Manager
2. Connect to your FTP server
3. Upload website files (respects `.ftpignore` exclusions)
4. Display a summary of uploaded/failed files

### Files Deployed

The script automatically scans and uploads:
- `index.html`
- `.htaccess`
- `css/` directory (all CSS files)
- `js/` directory (all JavaScript files)
- `data/` directory (all JSON and JS files)
- Images with extensions: `.png`, `.jpg`, `.jpeg`, `.gif`, `.svg`

### File Exclusions

The `.ftpignore` file controls which files are excluded from deployment:
- Git files (`.git/`, `.gitignore`)
- Documentation files (`*.md`)
- Scripts directory (`scripts/`)
- Development files (`*.py`)
- Images directory (logos not yet implemented)
- Test files, screenshots, local configs

## Security Notes

- **Credentials**: Never commit credentials to git. Windows Credential Manager keeps them encrypted.
- **FTP Security**: FTP transmits passwords in plain text. For better security, ensure your hosting provider supports FTPS (FTP over TLS).

## Troubleshooting

### "Credential not found" error
Make sure you stored the credential with the exact name.

Verify it's stored:
```powershell
cmdkey /list | Select-String "ftp_example.com"
```

### "Connection refused" error
- Verify host and port are correct (default FTP port: 21)
- Check your firewall allows outbound FTP connections
- Confirm your hosting provider allows FTP connections
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
pip install pywin32
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

## Version History

- **v1.4.2** (2025-10-09): Current deployment using FTP
- Successfully deployed to https://www.example.com
