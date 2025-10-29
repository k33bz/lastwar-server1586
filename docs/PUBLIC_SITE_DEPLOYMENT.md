# Public Site Deployment Guide

**Version:** 3.2.0
**Last Updated:** 2025-10-28
**Deployment Type:** Static Frontend Only

---

## Overview

This guide covers deploying **only the public-facing website** without the admin panel. The public site is a fully static HTML/CSS/JavaScript application with no backend dependencies.

**Public Site URL:** https://www.example.com
**Deployment Method:** FTP, SFTP, or any static file hosting

---

## Table of Contents

1. [What Gets Deployed](#what-gets-deployed)
2. [Quick Start](#quick-start)
3. [Deployment Methods](#deployment-methods)
4. [Verification](#verification)
5. [Updating Data](#updating-data)
6. [Troubleshooting](#troubleshooting)

---

## What Gets Deployed

### Required Files

The public site consists of these files only:

```
Server1586-clean/
├── index.html              # Main page
├── css/
│   └── styles.css          # All styling
├── js/
│   └── app.js              # Frontend logic
├── data/
│   ├── council.js          # Timezone utilities
│   ├── alliances.json      # Alliance rankings
│   ├── rules.json          # Server rules
│   ├── amendments.json     # Rule amendments
│   ├── rotation-schedule.json  # Council rotation
│   ├── server-info.json    # Server metadata
│   ├── signature-history.json  # R5 history
│   └── power-history.csv   # Power trends data
├── version.json            # Version info (optional)
└── images/                 # Logo images (if any)
```

**Total Size:** ~150-200 KB (excluding images)

### External Dependencies (CDN)

These are loaded from CDN, **no local files needed:**
- Chart.js (v4.4.0) - for power trends visualization
- Chart.js Date Adapter (v3.0.0) - for timeline charts

### NOT Required for Public Site

The following directories are **NOT needed** and should not be deployed:

```
❌ admin/                    # Admin panel (separate deployment)
❌ scripts/                  # Deployment/utility scripts
❌ .github/                  # CI/CD workflows
❌ docs/                     # Documentation
❌ ocr/                      # Screenshot processing
❌ tesseract_training/       # OCR training data
❌ node_modules/             # If present
❌ vendor/                   # Composer dependencies
❌ *.md files                # Documentation files
❌ .env files                # Configuration
```

---

## Quick Start

### Prerequisites

**One of the following:**
- FTP client (FileZilla, WinSCP, Cyberduck)
- SFTP access
- Web hosting control panel (cPanel, Plesk)
- Git with FTP deployment
- Cloud storage (S3, Azure Blob, Netlify, Vercel)

**Development/Testing (optional):**
- Local web server for testing:
  - Python: `python -m http.server 8000`
  - Node.js: `npx http-server -p 8000`
  - PHP: `php -S localhost:8000`

### 3-Step Deployment

**1. Test Locally (Recommended)**
```bash
cd /path/to/Server1586-clean
python -m http.server 8000
# Visit http://localhost:8000
```

**2. Upload Files**
- Use FTP client to upload files to web server
- Preserve directory structure
- Upload to `public_html/` or `www/` directory

**3. Verify**
```bash
curl https://yourdomain.com/version.json
# Should return JSON with version info
```

---

## Deployment Methods

### Method 1: FTP with FileZilla (Most Common)

**Setup:**
1. Open FileZilla
2. Enter FTP credentials:
   - Host: `ftp.yourdomain.com`
   - Username: `your_ftp_user`
   - Password: `your_password`
   - Port: `21` (or `22` for SFTP)
3. Click "Quickconnect"

**Upload:**
1. Navigate to your site root (usually `public_html/`)
2. Create subdirectory if needed: `lastwar1586/` or `server1586/`
3. Upload these files:
   ```
   index.html
   css/ (entire folder)
   js/ (entire folder)
   data/ (entire folder - JSON files only)
   version.json
   images/ (if present)
   ```

**Permissions:**
- Files: `644` (read for everyone, write for owner)
- Directories: `755` (execute/read for everyone, write for owner)

---

### Method 2: Python Deployment Script

**Create a simple deployment script:**

```python
# deploy_public_site.py
import ftplib
import os

FTP_HOST = "ftp.yourdomain.com"
FTP_USER = "your_username"
FTP_PASS = "your_password"
REMOTE_DIR = "/public_html/server1586"

# Files to upload
files_to_upload = [
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

def upload_files():
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.cwd(REMOTE_DIR)

    for file_path in files_to_upload:
        print(f"Uploading {file_path}...")

        # Create directory if needed
        remote_dir = os.path.dirname(file_path)
        if remote_dir:
            try:
                ftp.mkd(remote_dir)
            except:
                pass  # Directory already exists

        # Upload file
        with open(file_path, 'rb') as f:
            ftp.storbinary(f'STOR {file_path}', f)

        print(f"  ✓ {file_path}")

    ftp.quit()
    print("\n✅ Deployment complete!")

if __name__ == "__main__":
    upload_files()
```

**Run:**
```bash
python deploy_public_site.py
```

---

### Method 3: Git with FTP-Deploy

**Using git-ftp:**

```bash
# Install git-ftp (one-time)
# Windows: Download from https://github.com/git-ftp/git-ftp
# Mac: brew install git-ftp
# Linux: apt-get install git-ftp

# Initialize (one-time)
git config git-ftp.url "ftp://ftp.yourdomain.com/public_html/server1586"
git config git-ftp.user "your_username"
git config git-ftp.password "your_password"

# Deploy
git ftp push --syncroot ./ --include-from public-site-files.txt
```

**Create `public-site-files.txt`:**
```
index.html
version.json
css/
js/
data/alliances.json
data/rules.json
data/amendments.json
data/rotation-schedule.json
data/server-info.json
data/signature-history.json
data/power-history.csv
data/council.js
images/
```

---

### Method 4: Static Site Hosts (Zero Config)

**Netlify Drop (Drag & Drop):**
1. Go to https://app.netlify.com/drop
2. Drag the folder containing your files
3. Site is live instantly with HTTPS

**Vercel (GitHub Integration):**
```bash
npm install -g vercel
vercel --prod
```

**GitHub Pages:**
```bash
# Enable GitHub Pages in repo settings
# Select 'mainline' branch and '/ (root)' folder
# Site available at: https://username.github.io/repo-name
```

**AWS S3 Static Hosting:**
```bash
aws s3 sync . s3://your-bucket-name \
  --exclude "admin/*" \
  --exclude "scripts/*" \
  --exclude ".git/*" \
  --exclude "*.md"
```

**Note:** For Netlify, Vercel, or GitHub Pages, create a `.gitignore` or deployment config to exclude admin files.

---

## Verification

### Post-Deployment Checklist

**1. Check Homepage**
```bash
curl -I https://yourdomain.com/
# Should return: HTTP/1.1 200 OK
```

**2. Verify JSON Files**
```bash
curl https://yourdomain.com/data/alliances.json
curl https://yourdomain.com/data/rules.json
curl https://yourdomain.com/version.json
# Each should return valid JSON
```

**3. Test in Browser**
- Visit https://yourdomain.com
- Check console for errors (F12 → Console)
- Verify all sections load:
  - ✅ Top 3 podium
  - ✅ Alliance grid (ranks 4-15)
  - ✅ Council voting members
  - ✅ Server rules
  - ✅ Power trends chart
  - ✅ Signatories list

**4. Test Navigation**
- Click hamburger menu (top-left)
- Scroll to test "Back to Top" button
- Check footer links
- Test all anchor links

**5. Mobile Test**
- Resize browser to mobile width
- Check responsive layout
- Verify hamburger menu works
- Test footer on mobile

---

## Updating Data

### Common Updates

**Update Alliance Rankings:**
1. Edit `data/alliances.json` locally
2. Upload to server
3. Clear browser cache: Ctrl+Shift+R

**Update Server Rules:**
1. Edit `data/rules.json` locally
2. Add amendment to `data/amendments.json` (optional)
3. Upload both files
4. Refresh page

**Update Power History:**
1. Edit `data/power-history.csv` locally
2. Upload to server
3. Power trends chart updates automatically

**Update Council Rotation:**
1. Run: `python scripts/update-rotation-schedule.py`
2. Upload `data/rotation-schedule.json`
3. Council section updates automatically

### Cache Busting

The site uses version-based cache busting:
- `data/alliances.json?v=3.2.0`
- When version.json changes, all cached files refresh

**Force immediate update:**
- Update `version.json` → `"version": "3.2.1"`
- Upload `version.json`
- All users get fresh data on next visit

---

## Troubleshooting

### Site Doesn't Load

**Check file upload:**
```bash
# Verify all files uploaded
curl -I https://yourdomain.com/index.html
curl -I https://yourdomain.com/css/styles.css
curl -I https://yourdomain.com/js/app.js
```

**Check permissions:**
- Files should be `644`
- Directories should be `755`

### JSON Errors in Console

**Validate JSON syntax locally:**
```bash
python -m json.tool data/alliances.json
python -m json.tool data/rules.json
# No output = valid JSON
# Error = fix syntax
```

**Check CORS (if serving from subdomain):**
- Add `.htaccess`:
  ```apache
  <IfModule mod_headers.c>
      Header set Access-Control-Allow-Origin "*"
  </IfModule>
  ```

### Chart Doesn't Load

**Check CDN access:**
- Open browser console (F12)
- Look for Chart.js load errors
- CDN URLs in `index.html` lines 84-86

**Check CSV format:**
```bash
python scripts/validate-csv.py
# Ensures power-history.csv is valid
```

### Styles Not Applied

**Clear browser cache:**
- Chrome: Ctrl+Shift+Del → Clear cached images/files
- Firefox: Ctrl+Shift+Del → Check "Cache"
- Safari: Cmd+Option+E

**Check CSS loaded:**
```bash
curl https://yourdomain.com/css/styles.css
# Should return CSS content
```

**Check version cache bust:**
- View page source
- Find: `<link href="css/styles.css?v=X.Y.Z">`
- Ensure version matches `version.json`

### Navigation Menu Not Working

**Check JavaScript loaded:**
- Open console (F12)
- Look for `app.js` errors
- Check: `data/council.js` loaded successfully

**Check hamburger button:**
```javascript
// Run in console:
document.getElementById('hamburgerMenu')
// Should return: <button> element
```

---

## Performance Optimization

### Enable Compression

**Add to `.htaccess`:**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript application/json
</IfModule>
```

### Enable Browser Caching

**Add to `.htaccess`:**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/html "access plus 1 hour"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/json "access plus 1 day"
</IfModule>
```

### Minify Files (Optional)

**CSS:**
```bash
# Using clean-css-cli
npm install -g clean-css-cli
cleancss -o css/styles.min.css css/styles.css
```

**JavaScript:**
```bash
# Using terser
npm install -g terser
terser js/app.js -o js/app.min.js -c -m
```

**Update `index.html` to use minified versions:**
```html
<link href="css/styles.min.css?v=3.2.0" rel="stylesheet">
<script src="js/app.min.js?v=3.2.0"></script>
```

---

## Security Notes

### What's Safe to Make Public

✅ **Safe files (no sensitive data):**
- All HTML, CSS, JavaScript
- All data/*.json files
- power-history.csv
- version.json

❌ **Never deploy these to public site:**
- `admin/.env` - Contains secrets
- `admin/secret_keys.json` - JWT keys
- `admin/*.php` - Server-side code
- `scripts/` - Deployment scripts
- `.git/` - Git history
- `.env*` files

### HTTPS Recommendation

Always use HTTPS for production:
- Most hosts provide free SSL (Let's Encrypt)
- Netlify/Vercel/GitHub Pages: HTTPS by default
- cPanel: Free AutoSSL available

---

## File Size Reference

```
index.html          ~15 KB
css/styles.css      ~25 KB
js/app.js           ~45 KB
data/alliances.json ~45 KB
data/rules.json     ~4 KB
data/amendments.json ~6 KB
data/rotation-schedule.json ~15 KB
data/server-info.json ~1 KB
data/signature-history.json ~8 KB
data/power-history.csv ~2 KB
data/council.js     ~5 KB
version.json        ~1 KB

Total: ~172 KB (uncompressed)
Gzipped: ~45-50 KB
```

Load time: < 1 second on 3G connection

---

## Quick Reference Commands

| Task | Command |
|------|---------|
| **Test locally** | `python -m http.server 8000` |
| **Validate JSON** | `python -m json.tool data/alliances.json` |
| **Check deployment** | `curl https://yourdomain.com/version.json` |
| **Update rankings** | Edit `data/alliances.json` → Upload |
| **Update rotation** | `python scripts/update-rotation-schedule.py` |
| **Clear cache** | Update `version.json` version number |

---

## Alternative Hosting Options

### Free Static Hosts

| Host | Bandwidth | SSL | Custom Domain | Deploy Method |
|------|-----------|-----|---------------|---------------|
| **Netlify** | 100 GB/mo | ✅ Free | ✅ Yes | Drag & drop / Git |
| **Vercel** | 100 GB/mo | ✅ Free | ✅ Yes | Git / CLI |
| **GitHub Pages** | Soft 100 GB/mo | ✅ Free | ✅ Yes | Git push |
| **Cloudflare Pages** | Unlimited | ✅ Free | ✅ Yes | Git / CLI |
| **Render** | 100 GB/mo | ✅ Free | ✅ Yes | Git |

All provide automatic deployments from Git repos.

### Paid Hosting ($3-10/mo)

- **Shared Hosting** (Hostinger, Bluehost, SiteGround)
- **VPS** (DigitalOcean, Linode, Vultr)
- **AWS S3 + CloudFront** (pay-as-you-go, ~$1-5/mo)

---

## Related Documentation

- **[README.md](../README.md)** - Project overview
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Full deployment guide (includes admin)
- **[CHANGELOG.md](CHANGELOG.md)** - Version history
- **[data/ALLIANCE_SCHEMA.md](../data/ALLIANCE_SCHEMA.md)** - Alliance data format

---

**Last Updated:** 2025-10-28
**Maintained By:** k33bz
**Public Site:** https://www.example.com
**Support:** https://github.com/k33bz/lastwar-server1586/issues
