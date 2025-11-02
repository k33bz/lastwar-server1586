# Centralized Versioning System

**Version:** 1.0.0
**Date:** 2025-10-19

---

## Overview

The Server 1586 project now uses a **centralized versioning system** with a single source of truth: `version.json`. This eliminates the need to manually update version numbers in multiple files.

### Benefits

✅ **Single Source of Truth** - Update version in one place
✅ **Consistency** - Same version across frontend, admin panel, and documentation
✅ **Easy Maintenance** - No more hunting for hardcoded version numbers
✅ **Automatic Updates** - Both static HTML and PHP pages load version dynamically
✅ **Changelog Integration** - Version links directly to changelog

---

## Architecture

### Central Version File

**Location:** `version.json` (project root)

```json
{
  "version": "3.4.0",
  "releaseDate": "2025-10-31",
  "components": {
    "frontend": {
      "version": "3.1.0",
      "html": "1.4.0",
      "js": "2.0.1",
      "css": "1.5.0"
    },
    "admin": {
      "version": "3.4.0",
      "php": "3.4.0",
      "migration_system": "1.0.0",
      "user_management": "2.0.0"
    },
    "scripts": {
      "rotation": "2.2.0"
    }
  },
  "features": {
    "multi_role_system": {
      "version": "1.0.0",
      "date": "2025-10-31",
      "description": "Multiple simultaneous roles per user"
    },
    "migration_system": {
      "version": "1.0.0",
      "date": "2025-10-19",
      "description": "Automatic version migration and schema upgrades"
    },
    "public_api": {
      "version": "1.0.0",
      "date": "2025-10-29",
      "description": "Read-only REST API with CORS support"
    }
  },
  "changelog": "docs/CHANGELOG.md",
  "migration_docs": "admin/MIGRATION_SYSTEM.md",
  "lastUpdated": "2025-10-31"
}
```

**Structure Overview:**
- **version**: Global project version (semantic versioning)
- **releaseDate**: Date of current release
- **components**: Individual component versions (frontend, admin, scripts)
- **features**: Feature-specific versions with dates and descriptions
- **changelog**: Path to main changelog file
- **migration_docs**: Path to migration documentation
- **lastUpdated**: Last modification date

### How It Works

**Frontend (index.html):**
- JavaScript fetches `version.json` on page load
- Updates meta tags dynamically
- Updates cache-busting query parameters
- Logs version to console

**Admin Panel (admin/includes/footer.php):**
- PHP loads `version.json` on every page load
- Displays version and release date in footer
- Links to changelog viewer

**Changelog Viewer (admin/changelog.php):**
- Loads version from `version.json`
- Parses `docs/CHANGELOG.md` (Markdown)
- Converts Markdown to HTML
- Displays formatted changelog with navigation

---

## Usage

### Updating the Version

**To release a new version:**

1. Edit `version.json`:
   ```json
   {
     "version": "3.1.0",
     "releaseDate": "2025-11-01",
     "lastUpdated": "2025-11-01"
   }
   ```

2. Update `docs/CHANGELOG.md`:
   ```markdown
   ## [3.1.0] - 2025-11-01

   ### Added
   - New feature description

   ### Changed
   - Updated feature description

   ### Fixed
   - Bug fix description
   ```

3. Commit and push:
   ```bash
   git add version.json docs/CHANGELOG.md
   git commit -m "Release v3.1.0

   - Feature 1
   - Feature 2

   See docs/CHANGELOG.md for full details"
   git push origin mainline
   ```

**That's it!** Both the frontend and admin panel will automatically use the new version.

---

## Files That Use Centralized Versioning

### Automatically Updated

✅ `index.html` - Loads version via JavaScript
✅ `admin/includes/footer.php` - Loads version via PHP
✅ `admin/changelog.php` - Displays version and changelog

### Manual Update Still Required

⚠️ `README.md` - Update version badge manually
⚠️ `DOCUMENTATION.md` - Update version number manually

---

## Changelog Viewer

### Accessing the Changelog

**Public URL:** `https://yourdomain.com/admin/changelog.php`

**Features:**
- ✅ Markdown to HTML conversion
- ✅ Syntax highlighting for code blocks
- ✅ Table support
- ✅ Anchor links for version headers
- ✅ Mobile responsive
- ✅ Works with or without admin authentication
- ✅ Breadcrumb navigation
- ✅ Links to main site and admin dashboard

### Markdown Support

The changelog viewer supports standard Markdown syntax:

- **Headers** (`#`, `##`, `###`)
- **Bold** (`**text**`)
- **Italic** (`*text*`)
- **Code blocks** (` ``` `)
- **Inline code** (`` `code` ``)
- **Links** (`[text](url)`)
- **Lists** (ordered and unordered)
- **Checkboxes** (`- [ ]`, `- [x]`)
- **Tables** (basic support)
- **Blockquotes** (`> text`)
- **Horizontal rules** (`---`)

---

## Component Versioning

The `version.json` file tracks versions for individual components:

### Frontend Components
- **HTML:** Structural changes to index.html
- **JavaScript:** Changes to app.js or council.js
- **CSS:** Changes to styles.css

### Admin Components
- **PHP:** Changes to admin panel PHP files

### Scripts
- **Rotation:** Changes to rotation schedule script

### When to Update Component Versions

**Increment when:**
- Making breaking changes (major version)
- Adding new features (minor version)
- Fixing bugs (patch version)

**Example:**
```json
{
  "components": {
    "frontend": {
      "version": "3.1.0",  ← Overall frontend version
      "js": "2.1.0"        ← JS had feature added
    }
  }
}
```

---

## Feature Versioning

**As of v3.4.0**, `version.json` includes a `features` section to track major features independently of component versions. This provides granular version history for significant functionality.

### Feature Structure

Each feature entry includes:
- **version**: Feature version (semantic versioning)
- **date**: Implementation date
- **description**: Brief feature description
- **Additional metadata**: Files, changes, database schema, etc.

**Example:**
```json
{
  "features": {
    "multi_role_system": {
      "version": "1.0.0",
      "date": "2025-10-31",
      "description": "Multiple simultaneous roles per user with APE as independent role",
      "database_schema": "v3",
      "migration": "3.4.0",
      "changes": [
        "Users can have multiple roles: admin, r5, r4, ape, none, disabled",
        "APE role can be assigned independently without R4/R5",
        "No alliance requirement for APE-only users"
      ],
      "files": [
        "admin/user_management.php",
        "admin/user_management_api.php"
      ]
    }
  }
}
```

### When to Add Feature Entries

Add feature entries for:
- ✅ Major new functionality (migration system, API, authentication)
- ✅ Breaking changes requiring migration
- ✅ Features requiring documentation
- ✅ Features with database schema changes
- ❌ Minor bug fixes or tweaks (use component versioning only)

---

## Changelog Synchronization

The changelog is now **fully synchronized** with `version.json`, tracking both global versions and component/feature versions.

### Synchronization Model

**1. Global Version Releases**
```markdown
## [3.4.0] - 2025-10-31

### Added
- Multi-role system (v1.0.0)
- User management enhancements

### Changed
- Database schema upgraded to v3
```

**2. Component Version Changes**
```markdown
### Component Updates
- **Frontend:** v3.1.0
  - HTML: v1.4.0 (navigation improvements)
  - JS: v2.0.1 (bug fixes)
  - CSS: v1.5.0 (responsive design)
- **Admin:** v3.4.0
  - PHP: v3.4.0 (multi-role system)
  - Migration System: v1.0.0 (initial release)
```

**3. Feature Version Entries**
```markdown
### Features

#### Multi-Role System (v1.0.0)
**Implemented:** 2025-10-31

- Users can have multiple simultaneous roles
- APE role can be assigned independently
- Automatic migration from v2 schema
```

### Keeping Versions in Sync

**Process:**
1. Update `version.json` with new version numbers
2. Update `docs/CHANGELOG.md` with matching version entry
3. Include component versions in changelog if updated
4. Document features with their specific versions
5. Commit both files together

**Example commit:**
```bash
git add version.json docs/CHANGELOG.md
git commit -m "Release v3.4.0: Multi-role system

- Added multi-role system (v1.0.0)
- Updated admin panel (v3.4.0)
- Database schema v3 with automatic migration

Component versions:
- admin.php: 3.4.0
- user_management: 2.0.0
- migration_system: 1.0.0

See docs/CHANGELOG.md for full details"
```

---

## Cache Busting

The version system automatically updates cache-busting query parameters:

**Before (hardcoded):**
```html
<link rel="stylesheet" href="css/styles.css?v=2.0.0">
```

**After (dynamic):**
```html
<link rel="stylesheet" href="css/styles.css?v=3.0.0">
<!-- Updated automatically from version.json -->
```

This ensures browsers load the latest files after deployments.

---

## Integration with CI/CD

### GitHub Actions Workflow

The version system integrates with automated deployment:

**`.github/workflows/deploy.yml`:**
1. Tests pass ✅
2. Deploy files to production ✅
3. `version.json` is deployed ✅
4. Frontend loads new version automatically ✅
5. Admin panel loads new version automatically ✅

No manual intervention needed!

---

## Footer Display

### Frontend (index.html)

The footer displays version information in the browser console:

```
Server 1586 v3.0.0 - Released 2025-10-16
```

### Admin Panel (admin/includes/footer.php)

The footer displays:

```
System Info
  Version: v3.0.0
  Released: Oct 16, 2025
  Security Level: Enterprise

Links:
  📋 Changelog  |  GitHub Repository  |  Report Issue  |  Security Info
```

Clicking "📋 Changelog" opens `admin/changelog.php`.

---

## Backward Compatibility

### Legacy Version References

Some files still reference versions in comments:

```php
/**
 * User Management
 *
 * @version 3.0.0
 * @date 2025-10-16
 */
```

These can remain for documentation purposes but should **not** be used as the source of truth.

### Migration from Old System

**Old System:**
- Version hardcoded in each file
- Manually updated on every change
- Easy to miss files
- Versions could drift out of sync

**New System:**
- Single `version.json` file
- Update once
- Auto-propagates everywhere
- Always in sync

---

## Troubleshooting

### Version Not Updating on Frontend

**Symptom:** Browser still shows old version

**Solution:**
1. Clear browser cache (Ctrl+Shift+R)
2. Verify `version.json` exists in deployment
3. Check browser console for fetch errors
4. Verify JSON is valid: `python -m json.tool version.json`

### Version Not Updating in Admin

**Symptom:** Footer shows old version

**Solution:**
1. Verify `version.json` path is correct: `../../version.json`
2. Check file permissions (should be readable)
3. Check PHP error logs
4. Test manually: `<?php var_dump(file_get_contents('../../version.json')); ?>`

### Changelog Not Displaying

**Symptom:** Changelog page is blank or shows errors

**Solution:**
1. Verify `docs/CHANGELOG.md` exists
2. Check Markdown syntax (must be valid)
3. Check PHP error logs for parsing errors
4. Test Markdown converter separately

---

## Best Practices

### Version Numbering

Follow [Semantic Versioning](https://semver.org/):

**Format:** `MAJOR.MINOR.PATCH`

**Increment:**
- **MAJOR** - Breaking changes (1.0.0 → 2.0.0)
- **MINOR** - New features, backward compatible (1.0.0 → 1.1.0)
- **PATCH** - Bug fixes, backward compatible (1.0.0 → 1.0.1)

**Examples:**
```
3.0.0 → 3.0.1  (bug fix)
3.0.1 → 3.1.0  (new feature)
3.1.0 → 4.0.0  (breaking change)
```

### Changelog Maintenance

**Keep changelog up to date:**
- Document every release
- Use consistent format
- Link related issues/PRs
- Include migration guides for breaking changes

**Changelog Entry Template:**
```markdown
## [X.Y.Z] - YYYY-MM-DD

### Added
- New feature descriptions

### Changed
- Modified feature descriptions

### Fixed
- Bug fix descriptions

### Deprecated
- Features marked for removal

### Removed
- Deleted features

### Security
- Security improvements
```

### Release Process

1. **Update version.json**
2. **Update docs/CHANGELOG.md**
3. **Test locally**
4. **Commit with descriptive message**
5. **Push to trigger deployment**
6. **Verify deployment**
7. **Check version on live site**

---

## Related Documentation

- **[docs/CHANGELOG.md](CHANGELOG.md)** - Complete version history
- **[docs/DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment guide
- **[README.md](../README.md)** - Project overview
- **[DOCUMENTATION.md](../DOCUMENTATION.md)** - Master documentation index

---

## Future Enhancements

### Planned Features

- [ ] Version API endpoint (`/api/version.json`)
- [ ] Version comparison tool
- [ ] Automatic release notes generation
- [ ] Version badge in README (shields.io)
- [ ] Desktop notification on version change
- [ ] Changelog RSS feed
- [ ] Version history graph

---

**Last Updated:** 2025-10-19
**Maintained By:** k33bz
**Version System:** v1.0.0
