# GitHub Releases Guide

**Last Updated:** 2025-10-28

This guide explains how to create GitHub Releases with downloadable ZIP files for easy distribution of the public website.

---

## Overview

GitHub Releases allow you to:
- ✅ Package and distribute specific versions of your software
- ✅ Attach binary files (ZIP archives, installers, etc.)
- ✅ Generate automatic release notes from commits
- ✅ Mark releases as stable or pre-release
- ✅ Provide easy download links for users

**Our use case:** Distribute the public website as a downloadable ZIP for users who want to host it without cloning the entire repository.

---

## Creating a Public Site ZIP

### Step 1: Generate the ZIP Archive

Run the packaging script from the repository root:

```bash
python scripts/create-public-site-zip.py
```

**Output:**
```
server1586-public-v3.2.0.zip  (~50 KB compressed)
```

**What's included:**
- index.html, CSS, JavaScript
- All data files (JSON, CSV)
- README with quick start instructions
- Version information

**What's excluded:**
- Admin panel files
- Scripts and documentation
- Git history and configuration
- Development files

---

## Creating a GitHub Release

### Method 1: GitHub CLI (Recommended)

**Install GitHub CLI:**
```bash
# Windows (using winget)
winget install --id GitHub.cli

# Or download from: https://cli.github.com/
```

**Create Release:**
```bash
# Get current version
VERSION=$(python -c "import json; print(json.load(open('version.json'))['version'])")

# Create release with ZIP
gh release create v${VERSION} \
  server1586-public-v${VERSION}.zip \
  --title "Server 1586 v${VERSION}" \
  --notes "Public website release for Server 1586.

## What's New
- Navigation system with hamburger menu and footer
- Power trends chart enhancements
- Mobile responsive improvements

## Download
Download \`server1586-public-v${VERSION}.zip\` to deploy the website to your own hosting.

See [Public Site Deployment Guide](https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_SITE_DEPLOYMENT.md) for setup instructions."
```

**With auto-generated notes:**
```bash
gh release create v3.2.0 \
  server1586-public-v3.2.0.zip \
  --title "Server 1586 v3.2.0" \
  --generate-notes
```

---

### Method 2: GitHub Web Interface

**1. Navigate to Releases:**
- Go to: https://github.com/k33bz/lastwar-server1586/releases
- Click **"Draft a new release"**

**2. Create Tag:**
- **Tag version:** `v3.2.0` (must start with 'v')
- **Target:** `mainline` branch

**3. Fill Release Details:**

**Title:**
```
Server 1586 v3.2.0 - Navigation System & Power Trends
```

**Description:**
```markdown
Public website release for Server 1586.

## 🎉 What's New in v3.2.0

### Navigation System (v1.0.0)
- ✨ Hamburger menu with slide-in navigation
- 🦶 Professional footer with quick links
- ⬆️ Back to top button
- 🔒 Admin login button

### Power Trends Enhancements
- 📊 Interactive alliance slider (3-50 alliances)
- 🖱️ Hover highlighting system
- 📅 ISO 8601 datetime format

### Improvements
- 📱 Mobile responsive navigation
- 🔗 Enhanced section anchor linking
- 🎨 Smooth scroll animations

## 📥 Download

Download `server1586-public-v3.2.0.zip` to deploy the website to your own hosting.

**Size:** ~50 KB (compressed) | ~170 KB (uncompressed)

## 🚀 Quick Start

1. Extract ZIP file
2. Upload to web server (FTP, cPanel, etc.)
3. Visit your domain

**See [Public Site Deployment Guide](https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_SITE_DEPLOYMENT.md) for detailed instructions.**

## 📖 Documentation

- [Full Deployment Guide](https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_SITE_DEPLOYMENT.md)
- [Changelog](https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/CHANGELOG.md)
- [README](https://github.com/k33bz/lastwar-server1586/blob/mainline/README.md)

## 🐛 Report Issues

Found a bug? [Open an issue](https://github.com/k33bz/lastwar-server1586/issues)

---

**Built with [Claude Code](https://claude.com/claude-code)**
```

**4. Attach ZIP File:**
- Drag and drop `server1586-public-v3.2.0.zip` to the attachment area
- Or click "Attach binaries" and select the file

**5. Publish:**
- ✅ Check "Set as the latest release"
- ❌ Uncheck "Set as a pre-release" (unless it's beta)
- Click **"Publish release"**

---

## Automated Release Creation

### GitHub Actions Workflow

You can automate release creation on version tags:

**Create `.github/workflows/release.yml`:**

```yaml
name: Create Release

on:
  push:
    tags:
      - 'v*'  # Triggered on version tags (v3.2.0, v3.3.0, etc.)

jobs:
  release:
    name: Create GitHub Release
    runs-on: ubuntu-latest
    permissions:
      contents: write

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Python
        uses: actions/setup-python@v5
        with:
          python-version: '3.11'

      - name: Create public site ZIP
        run: python scripts/create-public-site-zip.py

      - name: Get version
        id: version
        run: |
          VERSION=$(python -c "import json; print(json.load(open('version.json'))['version'])")
          echo "version=$VERSION" >> $GITHUB_OUTPUT

      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: server1586-public-v${{ steps.version.outputs.version }}.zip
          generate_release_notes: true
          draft: false
          prerelease: false
          name: Server 1586 v${{ steps.version.outputs.version }}
          body: |
            Public website release for Server 1586.

            ## 📥 Download

            Download the ZIP file below to deploy the website to your own hosting.

            **See [Public Site Deployment Guide](https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_SITE_DEPLOYMENT.md) for setup instructions.**
```

**Trigger:**
```bash
# Create and push a tag
git tag v3.2.0
git push origin v3.2.0

# Or create tag from specific commit
git tag v3.2.0 9cf207b
git push origin v3.2.0
```

---

## Release Workflow

### Standard Release Process

1. **Update Version:**
   ```bash
   # Edit version.json
   # Update docs/CHANGELOG.md
   git add version.json docs/CHANGELOG.md
   git commit -m "chore: Bump version to 3.2.0"
   git push origin mainline
   ```

2. **Create ZIP:**
   ```bash
   python scripts/create-public-site-zip.py
   ```

3. **Test ZIP:**
   ```bash
   # Extract and test locally
   unzip server1586-public-v3.2.0.zip -d test-release
   cd test-release
   python -m http.server 8000
   # Visit http://localhost:8000 and verify
   ```

4. **Create Release:**
   ```bash
   gh release create v3.2.0 \
     server1586-public-v3.2.0.zip \
     --title "Server 1586 v3.2.0" \
     --generate-notes
   ```

5. **Verify Release:**
   - Visit: https://github.com/k33bz/lastwar-server1586/releases/latest
   - Test download link
   - Check file size and contents

---

## Release Types

### Stable Release (Production)
```bash
gh release create v3.2.0 \
  server1586-public-v3.2.0.zip \
  --title "Server 1586 v3.2.0" \
  --generate-notes
```

### Pre-release (Beta/RC)
```bash
gh release create v3.3.0-beta.1 \
  server1586-public-v3.3.0-beta.1.zip \
  --title "Server 1586 v3.3.0 Beta 1" \
  --prerelease \
  --notes "Beta release for testing. Not recommended for production."
```

### Draft Release
```bash
gh release create v3.2.0 \
  server1586-public-v3.2.0.zip \
  --title "Server 1586 v3.2.0" \
  --draft \
  --notes "Draft release for review"
```

---

## Managing Releases

### List Releases
```bash
gh release list
```

### View Release
```bash
gh release view v3.2.0
```

### Edit Release
```bash
gh release edit v3.2.0 --notes "Updated release notes"
```

### Delete Release
```bash
gh release delete v3.2.0 --yes
```

### Upload Additional Files
```bash
gh release upload v3.2.0 additional-file.zip
```

---

## Download Links

### Direct Download URL Format

**Latest release:**
```
https://github.com/k33bz/lastwar-server1586/releases/latest/download/server1586-public-v3.2.0.zip
```

**Specific version:**
```
https://github.com/k33bz/lastwar-server1586/releases/download/v3.2.0/server1586-public-v3.2.0.zip
```

**All releases page:**
```
https://github.com/k33bz/lastwar-server1586/releases
```

### Badge for README

Add a download badge to README.md:

```markdown
[![Download Latest Release](https://img.shields.io/github/v/release/k33bz/lastwar-server1586?label=Download&color=blue)](https://github.com/k33bz/lastwar-server1586/releases/latest)
```

Result:
[![Download Latest Release](https://img.shields.io/github/v/release/k33bz/lastwar-server1586?label=Download&color=blue)](https://github.com/k33bz/lastwar-server1586/releases/latest)

---

## Best Practices

### Version Numbering
- Follow Semantic Versioning: `MAJOR.MINOR.PATCH`
- `v3.2.0` = Version 3.2.0
- Always prefix with 'v' for tags

### Release Cadence
- **Major releases (v4.0.0):** Breaking changes
- **Minor releases (v3.2.0):** New features
- **Patch releases (v3.2.1):** Bug fixes

### Release Notes
- ✅ Summarize changes clearly
- ✅ Include "What's New" section
- ✅ Provide download instructions
- ✅ Link to documentation
- ✅ Mention breaking changes
- ❌ Don't include technical implementation details

### File Naming
- Use consistent naming: `server1586-public-v{version}.zip`
- Include version in filename
- Lowercase, hyphen-separated

### Testing
- Always test ZIP before creating release
- Verify all files are included
- Check file sizes are reasonable
- Test extraction and deployment

---

## Troubleshooting

### "Tag already exists"
```bash
# Delete tag locally and remotely
git tag -d v3.2.0
git push origin :refs/tags/v3.2.0

# Recreate tag
git tag v3.2.0
git push origin v3.2.0
```

### "Permission denied" (GitHub CLI)
```bash
# Authenticate again
gh auth login
```

### "File too large"
GitHub has a 2GB limit per file. Our ZIP is ~50 KB, so this shouldn't be an issue.

### ZIP doesn't contain all files
- Check you're running script from repository root
- Verify all files exist before creating ZIP
- Review PUBLIC_FILES list in `scripts/create-public-site-zip.py`

---

## Quick Reference

| Task | Command |
|------|---------|
| **Create ZIP** | `python scripts/create-public-site-zip.py` |
| **Create release** | `gh release create v3.2.0 file.zip --generate-notes` |
| **List releases** | `gh release list` |
| **View release** | `gh release view v3.2.0` |
| **Delete release** | `gh release delete v3.2.0 --yes` |
| **Download URL** | `https://github.com/USER/REPO/releases/download/TAG/FILE` |

---

## Examples

### Creating v3.2.0 Release

```bash
# 1. Create ZIP
python scripts/create-public-site-zip.py

# 2. Test locally
unzip server1586-public-v3.2.0.zip -d test
cd test && python -m http.server 8000

# 3. Create release
gh release create v3.2.0 \
  server1586-public-v3.2.0.zip \
  --title "Server 1586 v3.2.0 - Navigation & Power Trends" \
  --notes "Public website with new navigation system and power trends enhancements.

Download server1586-public-v3.2.0.zip to deploy to your hosting.

See docs/PUBLIC_SITE_DEPLOYMENT.md for instructions."

# 4. Verify
gh release view v3.2.0
```

---

## Related Documentation

- [Public Site Deployment Guide](PUBLIC_SITE_DEPLOYMENT.md)
- [Changelog](CHANGELOG.md)
- [Deployment Guide](DEPLOYMENT.md)

---

**Last Updated:** 2025-10-28
**Maintained By:** k33bz
