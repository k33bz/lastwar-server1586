# GitHub Issues to Create

**Date:** 2025-10-19
**Total Issues:** 20

---

## 🧹 Cleanup & Maintenance (3 issues)

### Issue #1: Delete consolidated documentation files
**Labels:** `documentation`, `cleanup`, `good first issue`
**Priority:** Low

**Description:**

Delete the following markdown files that have been consolidated into `docs/DEPLOYMENT.md` and `docs/CHANGELOG.md`:

**Deployment docs (→ docs/DEPLOYMENT.md):**
- [ ] CICD-SETUP.md
- [ ] GITHUB-SETUP.md
- [ ] DEPLOYMENT-STATUS.md
- [ ] DEPLOYMENT-HISTORY.md
- [ ] DEPLOYMENT_NOTES.md
- [ ] DEPLOYMENT-POWEREDITOR.md

**Implementation summaries (→ docs/CHANGELOG.md):**
- [ ] ALLIANCE-MODAL-IMPLEMENTATION.md
- [ ] ALLIANCE-INFO-UPDATE-SUMMARY.md
- [ ] R5-SIGNATURE-HISTORY-IMPLEMENTATION.md
- [ ] SCREENSHOT-PROCESSING-SUMMARY.md
- [ ] CLEANUP-COMPLETE.md
- [ ] SANITIZATION-LOG.md

**Temporary/completed docs:**
- [ ] SESSION_SUMMARY.md
- [ ] AUDIT_LOGGING_TODO.md
- [ ] TODO-REVIEW.md

**Steps:**
1. Verify all content is in consolidated docs
2. Check for broken links
3. Run deletion commands from `docs/FILES_TO_DELETE.md`
4. Commit with message: "docs: Remove consolidated documentation files"

**Reference:** See `docs/FILES_TO_DELETE.md` for complete deletion guide

---

### Issue #2: Update .ftpignore to exclude docs/ directory
**Labels:** `deployment`, `configuration`
**Priority:** Medium

**Description:**

The new `docs/` directory contains documentation that doesn't need to be deployed to production. Update `.ftpignore` to exclude it.

**Changes needed:**

```diff
# .ftpignore
+ docs/
+ *.md
+ !README.md
```

**Rationale:**
- Documentation files are for developers, not production
- Reduces deployment size
- Faster FTP uploads
- README.md should still be deployed for reference

---

### Issue #3: Add version.json to deployment workflow
**Labels:** `deployment`, `versioning`
**Priority:** High

**Description:**

Ensure `version.json` is included in FTP deployment and verify it's accessible on production.

**Steps:**
1. Check `.ftpignore` doesn't exclude `*.json`
2. Deploy and verify: `curl https://yourdomain.com/version.json`
3. Add deployment verification step to GitHub Actions workflow
4. Document in `docs/DEPLOYMENT.md`

---

## 🚀 Features & Enhancements (10 issues)

### Issue #4: Implement dashboard statistics caching
**Labels:** `enhancement`, `performance`, `admin`
**Priority:** Medium

**Description:**

Admin dashboard currently calculates statistics on every page load. Implement 60-second cache to improve performance.

**Current behavior:**
- Reads 4 JSON files on every page load
- Calculates active users, trends, security status
- No caching

**Proposed solution:**
```php
// admin/dashboard.php
$cache_file = __DIR__ . '/.cache/dashboard_stats.json';
$cache_ttl = 60; // seconds

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
    $stats = json_decode(file_get_contents($cache_file), true);
} else {
    $stats = calculate_dashboard_stats();
    file_put_contents($cache_file, json_encode($stats));
}
```

**Benefits:**
- Faster page loads
- Reduced disk I/O
- Better scalability

**Related:** docs/CHANGELOG.md mentions this as planned for v3.1.0

---

### Issue #5: Auto-generate alliance-count-history.json for trend tracking
**Labels:** `enhancement`, `data`, `automation`
**Priority:** Low

**Description:**

Dashboard shows alliance trend but requires manual creation of `alliance-count-history.json`. Automate this.

**Proposed solution:**

Create `scripts/update-alliance-history.py`:
```python
# Reads current alliance count
# Appends to alliance-count-history.json with timestamp
# Runs daily via cron or GitHub Actions
```

**Data structure:**
```json
[
  {
    "date": "2025-10-19",
    "count": 15,
    "timestamp": "2025-10-19T12:00:00Z"
  }
]
```

**Integration:**
- Add to GitHub Actions workflow (daily schedule)
- Dashboard automatically shows trend

---

### Issue #6: Add email notifications for security events
**Labels:** `enhancement`, `security`, `admin`
**Priority:** Medium

**Description:**

Security monitor detects threats but doesn't notify admins. Implement email alerts for critical events.

**Events to notify:**
- Failed login attempts (>5 in 10 minutes)
- IP blocking triggered
- Emergency key rotation
- Suspicious activity detected

**Implementation:**
```php
// admin/security_monitor.php
function notify_security_event($event_type, $details) {
    $admins = get_admin_emails();
    $subject = "Security Alert: $event_type";
    $body = format_security_email($event_type, $details);

    foreach ($admins as $email) {
        send_email($email, $subject, $body);
    }
}
```

**Configuration:**
```env
# .env
SECURITY_NOTIFICATIONS=true
SECURITY_EMAIL_THRESHOLD=5  # Failed logins before alert
```

---

### Issue #7: API rate limiting dashboard
**Labels:** `enhancement`, `admin`, `monitoring`
**Priority:** Low

**Description:**

Rate limiting is implemented (`admin/includes/api_helpers.php`) but there's no UI to monitor it.

**Proposed features:**
- View current rate limit status per IP
- See blocked requests
- Whitelist/blacklist management
- Rate limit statistics (requests/hour)
- Configurable limits per endpoint

**UI mockup:**
```
Rate Limiting Dashboard
├── Current Status (requests in last hour)
├── Blocked IPs (with unblock button)
├── Top Requesters
├── Rate Limit Rules
└── Configuration
```

---

### Issue #8: Real-time audit log updates (WebSocket)
**Labels:** `enhancement`, `admin`, `real-time`
**Priority:** Low

**Description:**

Audit log viewer requires manual refresh. Implement real-time updates using WebSocket or Server-Sent Events.

**Technologies:**
- **WebSocket:** Full duplex, real-time
- **SSE:** Simpler, server-to-client only
- **Long polling:** Fallback option

**Implementation (SSE):**
```php
// admin/audit_log_stream.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

while (true) {
    $new_logs = get_new_audit_logs($last_id);
    if (!empty($new_logs)) {
        echo "data: " . json_encode($new_logs) . "\n\n";
        flush();
    }
    sleep(2);
}
```

**UI:**
```javascript
// admin/security_audit.php
const eventSource = new EventSource('audit_log_stream.php');
eventSource.onmessage = (event) => {
    const logs = JSON.parse(event.data);
    prependLogs(logs);
};
```

---

### Issue #9: Version badge in README.md
**Labels:** `enhancement`, `documentation`
**Priority:** Low

**Description:**

Add dynamic version badge to README.md using shields.io or similar service.

**Options:**

**1. Static badge (manual update):**
```markdown
![Version](https://img.shields.io/badge/version-3.0.0-blue)
```

**2. Dynamic badge (from version.json):**
```markdown
![Version](https://img.shields.io/endpoint?url=https://yourdomain.com/version-badge.json)
```

Create `version-badge.json`:
```json
{
  "schemaVersion": 1,
  "label": "version",
  "message": "3.0.0",
  "color": "blue"
}
```

**3. GitHub Release badge:**
```markdown
![Release](https://img.shields.io/github/v/release/k33bz/lastwar-server1586)
```

---

### Issue #10: Changelog RSS feed
**Labels:** `enhancement`, `documentation`
**Priority:** Low

**Description:**

Allow users to subscribe to changelog updates via RSS.

**Implementation:**

Create `admin/changelog.rss.php`:
```php
<?php
header('Content-Type: application/rss+xml; charset=utf-8');

$changelog = parse_changelog('docs/CHANGELOG.md');

echo '<?xml version="1.0" encoding="UTF-8" ?>';
echo '<rss version="2.0">';
echo '<channel>';
echo '<title>Server 1586 Changelog</title>';
echo '<link>https://yourdomain.com</link>';

foreach ($changelog['releases'] as $release) {
    echo '<item>';
    echo '<title>Version ' . $release['version'] . '</title>';
    echo '<description>' . htmlspecialchars($release['description']) . '</description>';
    echo '<pubDate>' . date(DATE_RSS, strtotime($release['date'])) . '</pubDate>';
    echo '</item>';
}

echo '</channel>';
echo '</rss>';
?>
```

---

### Issue #11: Desktop notifications on version change
**Labels:** `enhancement`, `frontend`
**Priority:** Low

**Description:**

Notify users when a new version is deployed (for users with the site open).

**Implementation:**

```javascript
// js/app.js
let currentVersion = null;

async function checkVersion() {
    const response = await fetch('version.json');
    const data = await response.json();

    if (currentVersion && currentVersion !== data.version) {
        if (Notification.permission === 'granted') {
            new Notification('Server 1586 Updated', {
                body: `New version ${data.version} is available. Refresh to update.`,
                icon: 'images/server-logo.png'
            });
        }
    }

    currentVersion = data.version;
}

// Check every 5 minutes
setInterval(checkVersion, 5 * 60 * 1000);
```

---

### Issue #12: Version comparison tool
**Labels:** `enhancement`, `admin`, `tools`
**Priority:** Low

**Description:**

Create a tool to compare changes between versions.

**Features:**
- View differences between any two versions
- File-by-file change summary
- Added/removed features
- Migration guide generation

**UI:**
```
Version Comparison Tool
  From: [v2.0.0 ▼]
  To:   [v3.0.0 ▼]

  [Compare Versions]

  Results:
  ├── Breaking Changes (3)
  ├── New Features (12)
  ├── Bug Fixes (8)
  ├── Files Changed (45)
  └── Migration Required: Yes
```

---

### Issue #13: Automatic release notes generation
**Labels:** `enhancement`, `automation`, `documentation`
**Priority:** Low

**Description:**

Automatically generate release notes from commit messages and changelog.

**Workflow:**

1. Parse commits since last release
2. Extract conventional commit types (feat, fix, docs, etc.)
3. Generate categorized release notes
4. Optionally create GitHub Release

**Implementation:**

```bash
# scripts/generate-release-notes.py
# Reads git log
# Parses conventional commits
# Generates Markdown release notes
```

**Integration with GitHub Actions:**

```yaml
# .github/workflows/release.yml
on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - name: Generate release notes
        run: python scripts/generate-release-notes.py

      - name: Create GitHub Release
        uses: actions/create-release@v1
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ github.ref }}
          body_path: RELEASE_NOTES.md
```

---

## 🐛 Bug Fixes & Improvements (4 issues)

### Issue #14: Replace all remaining alert() and confirm() calls
**Labels:** `bug`, `ui/ux`, `admin`
**Priority:** Medium

**Description:**

Some alert() and confirm() calls remain in the codebase. Replace them with modal dialogs.

**Reference:** `admin/ALERT-TO-MODAL-REPLACEMENTS.md`

**Files to update:**
- `admin/admin_api.php:473`
- `admin/user_management.php:917`
- `admin/security_monitor.php:909`
- `admin/alliances_power.php:713,730`
- `admin/security_keys.php:241,258`
- `admin/generate_magic_link.php:327`
- `admin/security_audit.php:636,663`
- `admin/user_management.php` (multiple locations)

**Pattern:**

Replace:
```javascript
if (confirm('Are you sure?')) {
    performAction();
}
```

With:
```javascript
const confirmed = await confirmAction('Are you sure?', 'Confirm Action', {
    dangerMode: true
});
if (confirmed) {
    performAction();
}
```

---

### Issue #15: Add loading states to all API calls
**Labels:** `enhancement`, `ui/ux`, `admin`
**Priority:** Medium

**Description:**

Many admin panel operations don't show loading indicators, leading to user confusion.

**Add loading states for:**
- Alliance power updates
- User management operations
- Backup operations
- Security monitor scans
- Audit log loading

**Implementation:**

```javascript
// admin/includes/scripts.js
function showLoading(message = 'Loading...') {
    const loader = document.createElement('div');
    loader.id = 'globalLoader';
    loader.className = 'loader-overlay';
    loader.innerHTML = `
        <div class="loader-content">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(loader);
}

function hideLoading() {
    document.getElementById('globalLoader')?.remove();
}

// Usage
showLoading('Updating alliance power...');
fetch('alliances_power_api.php', {...})
    .finally(() => hideLoading());
```

---

### Issue #16: Improve mobile responsiveness for admin panel
**Labels:** `enhancement`, `ui/ux`, `mobile`, `admin`
**Priority:** Medium

**Description:**

Admin panel works on mobile but has some usability issues:

**Issues:**
- Tables overflow on small screens
- Dropdown menus too wide
- Modal dialogs don't fit screen
- Forms have small tap targets
- Navigation menu needs hamburger on mobile

**Improvements needed:**
1. **Responsive tables:** Horizontal scroll or card layout on mobile
2. **Mobile navigation:** Hamburger menu for header nav
3. **Touch-friendly:** Larger buttons (min 44x44px)
4. **Modal sizing:** Max 90vw on mobile
5. **Form optimization:** Stack labels above inputs on mobile

**CSS changes:**

```css
@media (max-width: 768px) {
    /* Responsive table */
    table {
        display: block;
        overflow-x: auto;
    }

    /* Mobile navigation */
    .nav-dropdown {
        position: static;
        width: 100%;
    }

    /* Larger tap targets */
    button, .btn {
        min-height: 44px;
        min-width: 44px;
    }
}
```

---

### Issue #17: Add form validation to all admin forms
**Labels:** `enhancement`, `validation`, `admin`
**Priority:** Medium

**Description:**

Some admin forms lack client-side validation. Add comprehensive validation.

**Forms to validate:**
- User management (email format, required fields)
- Alliance power editor (numeric values, ranges)
- Alliance editor (required fields)
- Security settings (valid IPs, port ranges)

**Implementation:**

```javascript
// admin/includes/scripts.js
function validateForm(formId, rules) {
    const form = document.getElementById(formId);

    form.addEventListener('submit', (e) => {
        let valid = true;

        for (const [field, validators] of Object.entries(rules)) {
            const input = form.elements[field];
            const value = input.value;

            for (const validator of validators) {
                if (!validator.test(value)) {
                    valid = false;
                    showFieldError(input, validator.message);
                }
            }
        }

        if (!valid) {
            e.preventDefault();
        }
    });
}

// Usage
validateForm('addUserForm', {
    email: [
        { test: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), message: 'Invalid email format' },
        { test: (v) => v.length > 0, message: 'Email is required' }
    ],
    role: [
        { test: (v) => ['admin', 'r5', 'r4'].includes(v), message: 'Invalid role' }
    ]
});
```

---

## 🔒 Security Enhancements (2 issues)

### Issue #18: Implement CSRF protection
**Labels:** `security`, `enhancement`, `admin`
**Priority:** High

**Description:**

Admin panel currently lacks CSRF (Cross-Site Request Forgery) protection. Add CSRF tokens to all forms.

**Implementation:**

**1. Generate CSRF token:**
```php
// admin/includes/csrf.php
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

**2. Add to forms:**
```php
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <!-- form fields -->
</form>
```

**3. Verify on submission:**
```php
// All API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}
```

---

### Issue #19: Add Content Security Policy (CSP) headers
**Labels:** `security`, `enhancement`
**Priority:** Medium

**Description:**

Implement Content Security Policy headers to prevent XSS attacks.

**Implementation:**

```php
// admin/includes/header.php or .htaccess
header("Content-Security-Policy:
    default-src 'self';
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
    style-src 'self' 'unsafe-inline';
    img-src 'self' data: https:;
    font-src 'self';
    connect-src 'self';
    frame-ancestors 'none';
");
```

**Apache (.htaccess):**
```apache
<IfModule mod_headers.c>
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;"
</IfModule>
```

**Benefits:**
- Prevents XSS attacks
- Blocks unauthorized scripts
- Prevents clickjacking
- Improves security score

---

## 📚 Documentation (1 issue)

### Issue #20: Create video tutorials for common tasks
**Labels:** `documentation`, `tutorial`, `good first issue`
**Priority:** Low

**Description:**

Create short video tutorials (or GIF recordings) for common admin tasks.

**Tutorials to create:**

1. **"How to update alliance power"** (2 min)
   - Login to admin
   - Navigate to Alliance Power Editor
   - Edit power values
   - Save changes

2. **"How to add a new user"** (1 min)
   - Navigate to User Management
   - Click "Add User"
   - Fill in details
   - Assign permissions

3. **"How to view audit logs"** (1 min)
   - Navigate to Security Audit
   - Filter logs
   - Export logs

4. **"How to create a backup"** (1 min)
   - Navigate to Backups
   - Click "Create Backup"
   - Download backup

5. **"How to update rotation schedule"** (2 min)
   - Update alliances.json
   - Run rotation script
   - Deploy changes

**Tools:**
- ScreenToGif (Windows)
- Kap (macOS)
- Peek (Linux)
- OBS Studio (all platforms)

**Format:**
- 1080p or 720p
- Silent or with captions
- Under 3 minutes each
- Host on GitHub wiki or YouTube

**Location:**
- Store in `docs/tutorials/`
- Link from README.md and admin guide

---

## Summary

**Total Issues:** 20

**By Category:**
- 🧹 Cleanup: 3
- 🚀 Features: 10
- 🐛 Bug Fixes: 4
- 🔒 Security: 2
- 📚 Documentation: 1

**By Priority:**
- High: 2
- Medium: 7
- Low: 11

**Good First Issues:** 2 (#1, #20)

**Next Steps:**
1. Review and prioritize issues
2. Create issues in GitHub
3. Assign labels and milestones
4. Create project board for tracking
5. Start with high-priority items

---

**Generated:** 2025-10-19
**Project:** Server 1586
**Repository:** https://github.com/k33bz/lastwar-server1586
