#!/bin/bash
#
# Create GitHub Issues Script
#
# Automatically creates all issues defined in .github/ISSUES_TO_CREATE.md
# Requires: GitHub CLI (gh) installed and authenticated
#
# Usage: ./scripts/create-github-issues.sh
#

set -e

REPO="k33bz/lastwar-server1586"

echo "Creating GitHub Issues for Server 1586..."
echo "Repository: $REPO"
echo ""

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "Error: GitHub CLI (gh) is not installed"
    echo "Install from: https://cli.github.com/"
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "Error: Not authenticated with GitHub CLI"
    echo "Run: gh auth login"
    exit 1
fi

echo "Creating issues..."
echo ""

# Issue #1: Delete consolidated documentation files
gh issue create \
    --title "Delete consolidated documentation files" \
    --body "Delete the following markdown files that have been consolidated into \`docs/DEPLOYMENT.md\` and \`docs/CHANGELOG.md\`:

**Deployment docs (→ docs/DEPLOYMENT.md):**
- CICD-SETUP.md
- GITHUB-SETUP.md
- DEPLOYMENT-STATUS.md
- DEPLOYMENT-HISTORY.md
- DEPLOYMENT_NOTES.md
- DEPLOYMENT-POWEREDITOR.md

**Implementation summaries (→ docs/CHANGELOG.md):**
- ALLIANCE-MODAL-IMPLEMENTATION.md
- ALLIANCE-INFO-UPDATE-SUMMARY.md
- R5-SIGNATURE-HISTORY-IMPLEMENTATION.md
- SCREENSHOT-PROCESSING-SUMMARY.md
- CLEANUP-COMPLETE.md
- SANITIZATION-LOG.md

**Temporary/completed docs:**
- SESSION_SUMMARY.md
- AUDIT_LOGGING_TODO.md
- TODO-REVIEW.md

**Steps:**
1. Verify all content is in consolidated docs
2. Check for broken links
3. Run deletion commands from \`docs/FILES_TO_DELETE.md\`
4. Commit with message: \"docs: Remove consolidated documentation files\"

**Reference:** See \`docs/FILES_TO_DELETE.md\` for complete deletion guide" \
    --label "documentation,cleanup,good first issue" \
    --repo "$REPO"

echo "✓ Created issue #1: Delete consolidated documentation files"

# Issue #2: Update .ftpignore
gh issue create \
    --title "Update .ftpignore to exclude docs/ directory" \
    --body "The new \`docs/\` directory contains documentation that doesn't need to be deployed to production. Update \`.ftpignore\` to exclude it.

**Changes needed:**

\`\`\`diff
# .ftpignore
+ docs/
+ *.md
+ !README.md
\`\`\`

**Rationale:**
- Documentation files are for developers, not production
- Reduces deployment size
- Faster FTP uploads
- README.md should still be deployed for reference" \
    --label "deployment,configuration" \
    --repo "$REPO"

echo "✓ Created issue #2: Update .ftpignore to exclude docs/"

# Issue #3: Add version.json to deployment
gh issue create \
    --title "Add version.json to deployment workflow verification" \
    --body "Ensure \`version.json\` is included in FTP deployment and verify it's accessible on production.

**Steps:**
1. Check \`.ftpignore\` doesn't exclude \`*.json\`
2. Deploy and verify: \`curl https://yourdomain.com/version.json\`
3. Add deployment verification step to GitHub Actions workflow
4. Document in \`docs/DEPLOYMENT.md\`

**Priority:** High - Required for centralized versioning system to work" \
    --label "deployment,versioning" \
    --repo "$REPO"

echo "✓ Created issue #3: Add version.json to deployment workflow"

# Issue #4: Dashboard caching
gh issue create \
    --title "Implement dashboard statistics caching" \
    --body "Admin dashboard currently calculates statistics on every page load. Implement 60-second cache to improve performance.

**Current behavior:**
- Reads 4 JSON files on every page load
- Calculates active users, trends, security status
- No caching

**Proposed solution:**
\`\`\`php
// admin/dashboard.php
\$cache_file = __DIR__ . '/.cache/dashboard_stats.json';
\$cache_ttl = 60; // seconds

if (file_exists(\$cache_file) && (time() - filemtime(\$cache_file)) < \$cache_ttl) {
    \$stats = json_decode(file_get_contents(\$cache_file), true);
} else {
    \$stats = calculate_dashboard_stats();
    file_put_contents(\$cache_file, json_encode(\$stats));
}
\`\`\`

**Benefits:**
- Faster page loads
- Reduced disk I/O
- Better scalability

**Related:** Mentioned in docs/CHANGELOG.md as planned for v3.1.0" \
    --label "enhancement,performance,admin" \
    --repo "$REPO"

echo "✓ Created issue #4: Dashboard statistics caching"

# Issue #5: Alliance history automation
gh issue create \
    --title "Auto-generate alliance-count-history.json for trend tracking" \
    --body "Dashboard shows alliance trend but requires manual creation of \`alliance-count-history.json\`. Automate this.

**Proposed solution:**

Create \`scripts/update-alliance-history.py\`:
\`\`\`python
# Reads current alliance count
# Appends to alliance-count-history.json with timestamp
# Runs daily via cron or GitHub Actions
\`\`\`

**Data structure:**
\`\`\`json
[
  {
    \"date\": \"2025-10-19\",
    \"count\": 15,
    \"timestamp\": \"2025-10-19T12:00:00Z\"
  }
]
\`\`\`

**Integration:**
- Add to GitHub Actions workflow (daily schedule)
- Dashboard automatically shows trend" \
    --label "enhancement,data,automation" \
    --repo "$REPO"

echo "✓ Created issue #5: Auto-generate alliance-count-history.json"

# Issue #6: Email notifications
gh issue create \
    --title "Add email notifications for security events" \
    --body "Security monitor detects threats but doesn't notify admins. Implement email alerts for critical events.

**Events to notify:**
- Failed login attempts (>5 in 10 minutes)
- IP blocking triggered
- Emergency key rotation
- Suspicious activity detected

**Configuration:**
\`\`\`env
# .env
SECURITY_NOTIFICATIONS=true
SECURITY_EMAIL_THRESHOLD=5
\`\`\`" \
    --label "enhancement,security,admin" \
    --repo "$REPO"

echo "✓ Created issue #6: Email notifications for security events"

# Issue #7: Rate limiting dashboard
gh issue create \
    --title "API rate limiting dashboard" \
    --body "Rate limiting is implemented but there's no UI to monitor it.

**Proposed features:**
- View current rate limit status per IP
- See blocked requests
- Whitelist/blacklist management
- Rate limit statistics (requests/hour)
- Configurable limits per endpoint" \
    --label "enhancement,admin,monitoring" \
    --repo "$REPO"

echo "✓ Created issue #7: Rate limiting dashboard"

# Issue #8: Real-time audit logs
gh issue create \
    --title "Real-time audit log updates (WebSocket/SSE)" \
    --body "Audit log viewer requires manual refresh. Implement real-time updates using Server-Sent Events or WebSocket.

**Recommended:** Server-Sent Events (simpler, server-to-client)

**Benefits:**
- Live updates without page refresh
- Better user experience
- Faster incident response" \
    --label "enhancement,admin,real-time" \
    --repo "$REPO"

echo "✓ Created issue #8: Real-time audit log updates"

# Issue #9: Version badge
gh issue create \
    --title "Add version badge to README.md" \
    --body "Add dynamic version badge to README.md using shields.io.

**Options:**

1. Static badge (manual update):
\`\`\`markdown
![Version](https://img.shields.io/badge/version-3.0.0-blue)
\`\`\`

2. GitHub Release badge:
\`\`\`markdown
![Release](https://img.shields.io/github/v/release/k33bz/lastwar-server1586)
\`\`\`

3. Dynamic from version.json:
\`\`\`markdown
![Version](https://img.shields.io/endpoint?url=https://yourdomain.com/version-badge.json)
\`\`\`" \
    --label "enhancement,documentation" \
    --repo "$REPO"

echo "✓ Created issue #9: Version badge in README"

# Issue #10: Changelog RSS
gh issue create \
    --title "Create changelog RSS feed" \
    --body "Allow users to subscribe to changelog updates via RSS.

Create \`admin/changelog.rss.php\` that parses \`docs/CHANGELOG.md\` and generates RSS feed.

**Benefits:**
- Users can subscribe to updates
- Automated notifications
- Better engagement" \
    --label "enhancement,documentation" \
    --repo "$REPO"

echo "✓ Created issue #10: Changelog RSS feed"

# Issue #11: Desktop notifications
gh issue create \
    --title "Add desktop notifications on version change" \
    --body "Notify users when a new version is deployed (for users with the site open).

**Implementation:**
- Check version.json every 5 minutes
- Show browser notification if version changed
- Prompt user to refresh

**Benefits:**
- Users always on latest version
- Reduced support requests
- Better UX" \
    --label "enhancement,frontend" \
    --repo "$REPO"

echo "✓ Created issue #11: Desktop notifications on version change"

# Issue #12: Version comparison
gh issue create \
    --title "Create version comparison tool" \
    --body "Create a tool to compare changes between versions.

**Features:**
- View differences between any two versions
- File-by-file change summary
- Added/removed features
- Migration guide generation

**UI:**
\`\`\`
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
\`\`\`" \
    --label "enhancement,admin,tools" \
    --repo "$REPO"

echo "✓ Created issue #12: Version comparison tool"

# Issue #13: Release notes generation
gh issue create \
    --title "Automatic release notes generation" \
    --body "Automatically generate release notes from commit messages and changelog.

**Workflow:**
1. Parse commits since last release
2. Extract conventional commit types (feat, fix, docs, etc.)
3. Generate categorized release notes
4. Optionally create GitHub Release

**Integration:**
- GitHub Actions workflow on tag push
- Creates release with auto-generated notes" \
    --label "enhancement,automation,documentation" \
    --repo "$REPO"

echo "✓ Created issue #13: Automatic release notes generation"

# Issue #14: Replace alert/confirm
gh issue create \
    --title "Replace all remaining alert() and confirm() calls with modals" \
    --body "Some alert() and confirm() calls remain in the codebase. Replace them with modal dialogs.

**Reference:** \`admin/ALERT-TO-MODAL-REPLACEMENTS.md\`

**Files to update:**
- admin/admin_api.php:473
- admin/user_management.php:917
- admin/security_monitor.php:909
- admin/alliances_power.php:713,730
- admin/security_keys.php:241,258
- admin/generate_magic_link.php:327
- admin/security_audit.php:636,663

**Pattern:** Use \`confirmAction()\` and \`alertModal()\` from scripts.js" \
    --label "bug,ui/ux,admin" \
    --repo "$REPO"

echo "✓ Created issue #14: Replace alert/confirm with modals"

# Issue #15: Loading states
gh issue create \
    --title "Add loading states to all API calls" \
    --body "Many admin panel operations don't show loading indicators, leading to user confusion.

**Add loading states for:**
- Alliance power updates
- User management operations
- Backup operations
- Security monitor scans
- Audit log loading

**Implementation:** Create global loading overlay with spinner" \
    --label "enhancement,ui/ux,admin" \
    --repo "$REPO"

echo "✓ Created issue #15: Add loading states"

# Issue #16: Mobile responsiveness
gh issue create \
    --title "Improve mobile responsiveness for admin panel" \
    --body "Admin panel works on mobile but has some usability issues.

**Improvements needed:**
1. Responsive tables (horizontal scroll or card layout)
2. Mobile navigation (hamburger menu)
3. Touch-friendly buttons (min 44x44px)
4. Modal sizing (max 90vw on mobile)
5. Form optimization (stack labels on mobile)

**Priority:** Medium - affects mobile users" \
    --label "enhancement,ui/ux,mobile,admin" \
    --repo "$REPO"

echo "✓ Created issue #16: Mobile responsiveness improvements"

# Issue #17: Form validation
gh issue create \
    --title "Add form validation to all admin forms" \
    --body "Some admin forms lack client-side validation. Add comprehensive validation.

**Forms to validate:**
- User management (email format, required fields)
- Alliance power editor (numeric values, ranges)
- Alliance editor (required fields)
- Security settings (valid IPs, port ranges)

**Implementation:** Create reusable validation helper in scripts.js" \
    --label "enhancement,validation,admin" \
    --repo "$REPO"

echo "✓ Created issue #17: Add form validation"

# Issue #18: CSRF protection
gh issue create \
    --title "Implement CSRF protection" \
    --body "Admin panel currently lacks CSRF (Cross-Site Request Forgery) protection. Add CSRF tokens to all forms.

**Implementation:**
1. Generate CSRF token in session
2. Add hidden input to all forms
3. Verify token on all POST requests
4. Return 403 if invalid

**Priority:** High - security vulnerability

**Files to update:**
- Create admin/includes/csrf.php
- Update all forms
- Update all API endpoints" \
    --label "security,enhancement,admin" \
    --repo "$REPO"

echo "✓ Created issue #18: CSRF protection"

# Issue #19: CSP headers
gh issue create \
    --title "Add Content Security Policy (CSP) headers" \
    --body "Implement Content Security Policy headers to prevent XSS attacks.

**Implementation:**
\`\`\`php
header(\"Content-Security-Policy:
    default-src 'self';
    script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
    style-src 'self' 'unsafe-inline';
    img-src 'self' data: https:;
\");
\`\`\`

**Benefits:**
- Prevents XSS attacks
- Blocks unauthorized scripts
- Prevents clickjacking
- Improves security score" \
    --label "security,enhancement" \
    --repo "$REPO"

echo "✓ Created issue #19: Content Security Policy headers"

# Issue #20: Video tutorials
gh issue create \
    --title "Create video tutorials for common tasks" \
    --body "Create short video tutorials (or GIF recordings) for common admin tasks.

**Tutorials to create:**
1. How to update alliance power (2 min)
2. How to add a new user (1 min)
3. How to view audit logs (1 min)
4. How to create a backup (1 min)
5. How to update rotation schedule (2 min)

**Tools:**
- ScreenToGif, OBS Studio, or similar
- Host on GitHub wiki or YouTube

**Location:** Store in \`docs/tutorials/\`" \
    --label "documentation,tutorial,good first issue" \
    --repo "$REPO"

echo "✓ Created issue #20: Video tutorials"

echo ""
echo "========================================="
echo "✅ Successfully created 20 GitHub issues!"
echo "========================================="
echo ""
echo "View issues at: https://github.com/$REPO/issues"
echo ""
echo "Next steps:"
echo "1. Review and prioritize issues"
echo "2. Assign labels and milestones"
echo "3. Create project board for tracking"
echo "4. Start with high-priority items"
echo ""
