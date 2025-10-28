#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Create Public Site ZIP Archive
Version: 1.0.0
Date: 2025-10-28

Creates a downloadable ZIP archive containing only the public-facing website files.
Perfect for GitHub Releases or direct download distribution.

Usage:
    python scripts/create-public-site-zip.py

Output:
    server1586-public-v{version}.zip

The ZIP contains only static files needed to run the public website:
- HTML, CSS, JavaScript
- Data files (JSON, CSV)
- Version information
- README for the public site
"""

import os
import sys
import json
import zipfile
import datetime
from pathlib import Path
from io import TextIOWrapper

# Fix Unicode output on Windows
if sys.platform == 'win32':
    sys.stdout = TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

# Public site files to include
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
    ".htaccess",  # If present
]

# Optional directories (include if they exist)
OPTIONAL_DIRS = [
    "images/",
]


def get_version():
    """Read version from version.json."""
    try:
        with open('version.json', 'r') as f:
            data = json.load(f)
            return data.get('version', '0.0.0')
    except Exception as e:
        print(f"⚠️  Warning: Could not read version.json: {e}")
        return "0.0.0"


def create_readme_for_zip():
    """Create a README specifically for the public site ZIP."""
    readme_content = f"""# Server 1586 - Public Website

**Version:** {get_version()}
**Release Date:** {datetime.datetime.now().strftime('%Y-%m-%d')}

## What's Included

This ZIP contains the complete public-facing website for Server 1586.

### Files:
- `index.html` - Main website page
- `version.json` - Version information
- `css/styles.css` - All styling
- `js/app.js` - Frontend JavaScript
- `data/*.json` - Alliance, rules, and rotation data
- `data/*.csv` - Power history data
- `data/council.js` - Timezone utilities

### Size: ~170 KB (uncompressed)

## Quick Start

### Option 1: Upload via FTP
1. Extract this ZIP file
2. Upload all files to your web server (public_html/ or www/)
3. Visit your domain

### Option 2: Local Testing
1. Extract this ZIP file
2. Run a local web server:
   ```bash
   # Python
   python -m http.server 8000

   # PHP
   php -S localhost:8000

   # Node.js
   npx http-server -p 8000
   ```
3. Visit http://localhost:8000

### Option 3: Static Hosting
Upload to any static host:
- **Netlify**: Drag & drop at https://app.netlify.com/drop
- **Vercel**: `npm install -g vercel && vercel`
- **GitHub Pages**: Enable in repo settings
- **AWS S3**: Upload and enable static website hosting

## Requirements

- **No backend required** - Pure static HTML/CSS/JavaScript
- **No build process** - Ready to deploy as-is
- **No dependencies** - All external libraries loaded from CDN
- Works on any web server (Apache, Nginx, IIS, etc.)

## Updating Data

To update alliance rankings or server rules:

1. Edit the JSON files in the `data/` directory
2. Re-upload the modified files to your server
3. Clear browser cache (Ctrl+Shift+R)

## Complete Documentation

For full deployment instructions, see:
- **Public Site Deployment Guide**: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_SITE_DEPLOYMENT.md
- **GitHub Repository**: https://github.com/k33bz/lastwar-server1586

## Support

- **Issues**: https://github.com/k33bz/lastwar-server1586/issues
- **Discussions**: https://github.com/k33bz/lastwar-server1586/discussions

## License

This project is open source. See LICENSE file in the GitHub repository.

---

**Built with [Claude Code](https://claude.com/claude-code)**
"""
    return readme_content


def get_file_size_mb(file_path):
    """Get file size in MB."""
    size = os.path.getsize(file_path)
    return size / (1024 * 1024)


def create_public_site_zip():
    """Create ZIP archive of public site files."""
    version = get_version()
    zip_filename = f"server1586-public-v{version}.zip"

    print("=" * 70)
    print("  Create Public Site ZIP Archive")
    print("=" * 70)
    print()
    print(f"📦 Creating: {zip_filename}")
    print(f"📌 Version: {version}")
    print()

    # Check if output already exists
    if os.path.exists(zip_filename):
        print(f"⚠️  {zip_filename} already exists")
        response = input("   Overwrite? (y/n): ")
        if response.lower() != 'y':
            print("❌ Cancelled")
            return
        os.remove(zip_filename)

    print("📋 Checking files...")

    # Check which files exist
    existing_files = []
    missing_files = []
    total_size = 0

    for file_path in PUBLIC_FILES:
        if os.path.exists(file_path):
            existing_files.append(file_path)
            size = os.path.getsize(file_path)
            total_size += size
            print(f"  ✓ {file_path} ({size:,} bytes)")
        else:
            if file_path != ".htaccess":  # .htaccess is optional
                missing_files.append(file_path)
                print(f"  ⚠️  Missing: {file_path}")

    # Check optional directories
    for dir_path in OPTIONAL_DIRS:
        if os.path.exists(dir_path):
            for root, dirs, files in os.walk(dir_path):
                for file in files:
                    full_path = os.path.join(root, file)
                    existing_files.append(full_path)
                    size = os.path.getsize(full_path)
                    total_size += size
            print(f"  ✓ {dir_path} (included)")
        else:
            print(f"  ℹ️  {dir_path} (not found, skipped)")

    if missing_files:
        print()
        print(f"⚠️  {len(missing_files)} required files missing")
        print("   Please run from repository root")
        return

    print()
    print(f"📊 Total files: {len(existing_files)}")
    print(f"📏 Total size: {total_size:,} bytes ({total_size / 1024:.1f} KB)")
    print()

    # Create ZIP
    print("📦 Creating ZIP archive...")

    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        # Add README
        readme_content = create_readme_for_zip()
        zipf.writestr('README.txt', readme_content)
        print("  ✓ Added README.txt")

        # Add all files
        for file_path in existing_files:
            zipf.write(file_path, file_path)
            print(f"  ✓ Added {file_path}")

    # Get final ZIP size
    zip_size = os.path.getsize(zip_filename)
    compression_ratio = (1 - zip_size / total_size) * 100 if total_size > 0 else 0

    print()
    print("=" * 70)
    print("  ✅ ZIP Archive Created Successfully!")
    print("=" * 70)
    print()
    print(f"📦 File: {zip_filename}")
    print(f"📏 Size: {zip_size:,} bytes ({zip_size / 1024:.1f} KB)")
    print(f"🗜️  Compression: {compression_ratio:.1f}%")
    print()
    print("Next steps:")
    print("  1. Test the ZIP by extracting and running locally")
    print("  2. Create a GitHub Release and attach this ZIP")
    print("  3. Use: gh release create v{} {} --title \"v{}\"".format(
        version, zip_filename, version))
    print()
    print("Or manually:")
    print(f"  1. Go to: https://github.com/k33bz/lastwar-server1586/releases/new")
    print(f"  2. Tag: v{version}")
    print(f"  3. Upload: {zip_filename}")
    print()


if __name__ == "__main__":
    # Change to repository root
    script_dir = os.path.dirname(os.path.abspath(__file__))
    repo_root = os.path.dirname(script_dir)
    os.chdir(repo_root)

    create_public_site_zip()
