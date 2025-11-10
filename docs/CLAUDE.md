# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Task Tracking & Documentation

**IMPORTANT:** This project uses **GitHub Issues** for all task tracking. Do NOT use TODO comments in code or the TodoWrite tool.

### Task Management
- **GitHub Issues:** https://github.com/k33bz/lastwar-server1586/issues
- **Milestones:** Used for versioning (v3.1.0, v3.2.0, v4.0.0, Cleanup & Maintenance)
- **Labels:** Categorize issues (documentation, bug, enhancement, security, etc.)
- **NO TODO comments:** Create GitHub Issues instead

### Documentation Linking in Code
All major code files should include GitHub documentation links in their headers. Use this pattern:

```php
/**
 * File Name
 * Version: X.Y.Z
 * Brief description
 *
 * Documentation:
 * - Relevant Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/path/to/doc.md
 * - Related Feature: https://github.com/k33bz/lastwar-server1586/blob/mainline/path/to/doc2.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * Changelog:
 * vX.Y.Z (DATE) - Description of changes
 */
```

**Example:** See `admin/dashboard.php` lines 1-25 for the documentation header pattern.

### Reporting Issues or Requesting Features
1. Create a GitHub Issue with appropriate labels
2. Assign to relevant milestone if applicable
3. Link related issues using `#issue-number`
4. Close issues via commit messages: `Closes #123` or `Fixes #123`

---

## Complete Project Overview

**Last War Server 1586** is a comprehensive management system for a Last War mobile game server, consisting of three integrated components:

### 🌐 1. Public Website (Static Frontend)
- **Technology:** Vanilla HTML/CSS/JavaScript (no build process)
- **Purpose:** Display alliance rankings, server rules, and council rotation schedule
- **Features:**
  - Top 15 alliance rankings with podium design
  - Rotating council system (5 permanent + 2 rotating members)
  - Server rules with amendment tracking and version history
  - Power trends chart with time-based visualization
  - Multi-timezone support with automatic DST detection
  - Responsive mobile-friendly design
- **Data Source:** Reads from JSON files in `data/` directory
- **Deployment:** Static files, can be hosted anywhere (cPanel, Netlify, S3, etc.)

### 🔐 2. Admin Panel (PHP Backend)
- **Technology:** PHP 8.0+ with Composer dependencies
- **Purpose:** Secure data management interface for authorized users
- **Authentication:** Passwordless magic link system with JWT tokens
- **Features:**
  - **Alliance Management:** Full CRUD operations for alliance data, power values, R4/R5 info
  - **User Management:** Multi-role RBAC system (admin, r5, r4, ape/powereditor)
  - **Security Monitoring:** CloudTrail-like audit logging, real-time threat detection, IP blocking
  - **Discord Integration:** Channel management, webhook testing, rate limit controls
  - **Season 2 Manager:** Event calendar, scheduling, recurring events
  - **Votes Management:** Council voting history and administration
  - **Security Tools:** MFA setup, JWT key rotation, CSRF protection, backup/restore
- **Data Storage:** File-based JSON storage in `data/` directory
- **Access Control:** Role-based permissions with audit logging for all actions

### 🤖 3. Discord Bot (Node.js)
- **Technology:** Discord.js v14, Node.js 18+
- **Purpose:** Council voting system via Discord DMs
- **Features:**
  - `/vote` slash command for creating votes and managing requests
  - DM-based voting workflow (council members vote via private messages)
  - Cryptographic vote integrity using SHA-256 hash chains (blockchain-inspired)
  - Automatic vote finalization (24h timer or when all votes submitted)
  - Vote request system with 12-hour auto-approval
  - Integration with website via webhook (posts results to Discord + website)
  - R4 vote delegation support (R4s can vote when R5 is absent)
- **Data Integration:** Reads council membership from `data/rotation-schedule.json` and `data/alliances.json`
- **Vote Storage:** Writes to `data/discord-votes.json` and `data/discord-vote-requests.json`
- **Deployment:** Runs on cPanel using Node.js app (see `bot/CPANEL_SETUP.md`)

---

## System Architecture & Integration

### Data Flow Pattern

```
┌─────────────────────┐
│   Public Website    │
│   (HTML/CSS/JS)     │
│   - Read Only       │
└──────────┬──────────┘
           │ fetch()
           ▼
┌─────────────────────┐      ┌─────────────────────┐
│   data/ Directory   │◄─────┤   Admin Panel       │
│   (JSON Files)      │      │   (PHP Backend)     │
│   - Single Source   │      │   - Read/Write      │
│     of Truth        │      │   - CRUD Operations │
└──────────┬──────────┘      └─────────────────────┘
           │
           ▼
┌─────────────────────┐      ┌─────────────────────┐
│   Discord Bot       │◄────►│   Discord API       │
│   (Node.js)         │      │   - Webhooks        │
│   - Read council    │      │   - DMs             │
│   - Write votes     │      │   - Slash commands  │
└─────────────────────┘      └─────────────────────┘
```

### Shared Data Files (data/ Directory)

**Alliance Data:**
- `alliances.json` - Top 15 alliances (power, R5, R4s, signatures) - **Primary alliance data**
- `alliance-tags.json` - Tag definitions for alliances
- `alliance-tag-assignments.json` - Tag assignments to alliances
- `tag-categories.json` - Tag category definitions
- `tag-suggestions.json` - Tag suggestion pool
- `power-history.csv` - Historical power trends for charting
- `signature-history.json` - R5 leadership change tracking

**Council & Governance:**
- `council.json` - President designation and roles
- `rotation-schedule.json` - Pre-generated weekly council rotation (52+ weeks)
- `rules.json` - Server rules structure
- `amendments.json` - Rule change history with versioning
- `votes.json` - Council vote history (website votes)

**Discord Integration:**
- `discord-votes.json` - Discord bot vote records with hash chain
- `discord-vote-requests.json` - Pending vote requests
- `discord-channels.json` - Channel configurations and webhooks
- `discord-history.json` - Discord announcement history

**Season 2 Events:**
- `season2_config.json` - Season 2 configuration
- `season2_calendar.json` - Event calendar and schedule
- `season2_event_templates.json` - Reusable event templates

**User & Server Info:**
- `user-profiles.json` - User Discord IDs and profile data
- `server-info.json` - Server Discord metadata

### Component Responsibilities

**Public Website** (Read-only consumer):
- Displays alliance rankings from `alliances.json`
- Shows council rotation from `rotation-schedule.json`
- Displays rules from `rules.json` with amendments from `amendments.json`
- Never modifies data files

**Admin Panel** (Primary data owner):
- **Owns and modifies:** All JSON files in `data/` directory
- **Creates:** Alliance data, rotation schedules, rules, amendments
- **Manages:** User accounts (in `admin/users.json`), security settings
- **Monitors:** Audit logs, security events, backups
- **Integrates:** Discord channels, webhooks, rate limits

**Discord Bot** (Limited read/write):
- **Reads:** `alliances.json` (for R5/R4 Discord IDs), `council.json` (president), `rotation-schedule.json` (current council)
- **Writes:** `discord-votes.json`, `discord-vote-requests.json`
- **Never modifies:** Alliance data, council assignments, rotation schedule

---

## Admin Panel (v3.9.0+)

### Authentication System

**Magic Link (Passwordless Login):**
1. User enters email on login page
2. Server generates JWT token with `magic: true` flag
3. Token sent via email as one-time login link
4. User clicks link → token validated → session JWT issued
5. Session JWT stored in HTTP-only secure cookie
6. All API calls require `credentials: 'include'` to send JWT cookie

**JWT Architecture:**
- **Token Types:**
  - Magic link tokens: Short-lived (15 min), single-use, `magic: true`
  - Session tokens: Long-lived (30 days), reusable, contains user claims
- **Token Claims:** `email`, `roles` (array), `alliances` (array), `mfa_verified` (bool), `iat`, `exp`
- **Storage:** HTTP-only secure cookies (prevents XSS)
- **Validation:** `require_jwt_session()` for pages, `require_jwt_session_api()` for APIs

**API Authentication Pattern:**
```javascript
// All admin panel fetch() calls MUST include credentials
fetch('/api/endpoint', {
    method: 'POST',
    credentials: 'include',  // Required to send JWT cookie
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});
```

**Important:** Using `require_jwt_session()` in API endpoints causes HTML redirects on auth failure. Use `require_jwt_session_api()` which returns JSON errors instead.

### Multi-Role System (v3.4.0+)

**Available Roles:**
- `admin` - Full system access to all features
- `r5` - Alliance leader (can edit alliance data + sign rules)
- `r4` - Alliance officer (can edit alliance data, no rule signing)
- `ape` - Alliance Power Editor (can edit power values for all alliances, no alliance assignment required)
- `president` - Council president (can approve/reject vote requests, auto-assigned based on `council.json`)
- `none` - Read-only access
- `disabled` - Account suspended

**Multi-Role Support:**
- Users can have multiple roles: `["r5", "ape"]`
- APE can be standalone (no alliance assignment needed)
- Roles stored as array in `users.json`: `"roles": ["r5", "ape"]`
- Role checking: `has_role($token, 'admin')` or `has_role($token, ['admin', 'r5'])`
- Primary role for display: `get_primary_role($token)` (first in hierarchy: admin > president > r5 > r4 > ape > none)

### Security Features

**1. Multi-Factor Authentication (MFA):**
- TOTP-based (Google Authenticator, Authy compatible)
- Backup codes (10 single-use codes)
- Per-user enforcement
- MFA setup page with QR code generation

**2. CSRF Protection:**
- All POST/PUT/DELETE/PATCH endpoints require CSRF token
- Token in hidden field or `X-CSRF-Token` header
- Validated server-side before processing

**3. Audit Logging (CloudTrail-like):**
- All user actions logged to `admin/audit-log.json`
- Fields: timestamp, email, action, resource, IP, user_agent, outcome, details
- Real-time viewer in `admin/security_audit.php`
- Filtering by user, action type, date range, outcome

**4. JWT Key Rotation:**
- Automatic 90-day rotation schedule
- Grace period for old keys (30 days)
- Emergency rotation capability
- Managed in `admin/key_rotation_admin_panel.php`

**5. Email Masking:**
- PII protection in logs and displays
- Format: `u***@example.com` (first letter + stars + domain)
- Function: `mask_email($email)`

**6. Security Monitoring:**
- Failed login tracking
- IP blocking for repeated failures
- Rate limiting per user/IP
- Real-time threat detection

**7. Backup & Restore:**
- Automatic backups before data modifications
- Manual backup creation
- Point-in-time recovery
- Backup verification and integrity checks
- Managed in `admin/security_backups.php`

### Key Admin Features

**Dashboard (`admin/dashboard.php`):**
- Six tabs: Overview, Alliance Management, Discord Integration, Season 2 Events, Votes Management, Security & System
- Dark mode toggle with localStorage persistence
- Keyboard shortcuts (1-6 for tabs, T for theme toggle)
- Real-time metrics and statistics
- Recent activity feed

**Alliance Management:**
- `alliance_edit.php` - Edit alliance data, R4/R5 management, rule signing
- `alliances_power.php` - Bulk power editing, APE role feature
- `allies_api.php` - Member management API
- R4 management with Discord IDs and vote delegation

**Discord Integration:**
- `discord_channels.php` - Channel management UI (alliance + global channels)
- `discord_templates.php` - Message templates for announcements
- `discord_scheduled.php` - Scheduled message calendar
- `discord_recurring.php` - Recurring message patterns
- `discord_rate_limits.php` - User rate limit controls

**Season 2 Manager:**
- Event calendar with week navigation
- Event creation with templates
- Recurring event patterns
- Export to Discord-formatted messages
- Template library management

**User Management:**
- `user_management.php` - Create/edit/delete users
- `user_profile.php` - Self-service profile (Discord ID, display name)
- Multi-role assignment with checkboxes
- Alliance assignment for R4/R5 roles
- MFA setup per user

### Important File Locations

**Core Files:**
- `admin/config.php` - Environment config, dependency loading, error handling
- `admin/jwt.php` (v2.3.0+) - JWT functions, auth helpers, role checking
- `admin/json_helpers.php` (v1.2.0+) - JSON file read/write, locking, multi-role helpers
- `admin/mailer.php` - Email sending (magic links, notifications)
- `admin/csrf.php` - CSRF token generation and validation

**User Data:**
- `admin/users.json` - User accounts with roles and alliances (NOT in git)
- `admin/.env` - Environment variables (SMTP, JWT secret, etc.) (NOT in git)
- `admin/audit-log.json` - Security audit trail (NOT in git)

**API Endpoints:**
- `admin/*_api.php` - REST APIs for data management
- All APIs use `require_jwt_session_api()` for JSON error responses
- All POST/PUT/DELETE require CSRF token in `X-CSRF-Token` header

---

## Discord Bot

### Architecture

**Entry Point:** `bot/index.js`
- Loads commands from `bot/commands/`
- Loads events from `bot/events/`
- Starts cron jobs from `bot/jobs/`
- Validates environment variables
- Includes comprehensive startup logging for cPanel debugging

**Commands:**
- `bot/commands/vote.js` - `/vote` slash command with subcommands:
  - `/vote request <title> <description> <category>` - Create vote request (any council member)
  - `/vote approve <request_id>` - Approve request and start vote (president only)
  - `/vote reject <request_id> <reason>` - Reject vote request (president only)
  - `/vote status [vote_id]` - Check active votes
  - `/vote requests` - List pending requests
  - `/vote verify <vote_id>` - Verify cryptographic integrity

**Events:**
- `bot/events/ready.js` - Bot startup, command registration
- `bot/events/interactionCreate.js` - Slash command router
- `bot/events/messageCreate.js` - DM voting handler (parses `vote: yes/no/abstain`)

**Cron Jobs:**
- `bot/jobs/voteMonitor.js` - Runs every minute:
  - Checks for expired votes (24h timeout)
  - Checks for early finalization (all 7 votes submitted)
  - Posts results to Discord channel
  - Sends results to website via webhook
- `bot/jobs/requestMonitor.js` - Runs every minute:
  - Auto-approves vote requests after 12 hours if president doesn't act

**Utilities:**
- `bot/utils/dataAccess.js` - File system access to `../data/` directory
- `bot/utils/councilUtils.js` - Reads `rotation-schedule.json` to get current council
- `bot/utils/voteIntegrity.js` - SHA-256 hash chain implementation
- `bot/utils/voteManager.js` - Vote creation, finalization, result formatting
- `bot/utils/webhookClient.js` - Website webhook integration with HMAC signatures

### Vote Workflow

**1. Request Creation:**
```
User → /vote request → Bot creates entry in discord-vote-requests.json
President notified via DM
```

**2. Request Approval (President):**
```
President → /vote approve <id> → Vote created in discord-votes.json
All 7 council members receive DM with vote details
```

**3. Voting:**
```
Council member receives DM: "Reply with: vote: yes/no/abstain"
Member replies → Vote recorded with hash chain seal
Vote cannot be changed once submitted
```

**4. Finalization (Automatic):**
```
Condition 1: All 7 votes submitted → Early close
Condition 2: 24 hours elapsed → Time expired
→ Results posted to Discord channel
→ Results sent to website via webhook
→ Vote marked as finalized
```

### Cryptographic Integrity

**Hash Chain (Blockchain-inspired):**
```javascript
// Each vote event hashed with previous hash
const eventData = {
    vote_id: "vote_123",
    voter: "user_456",
    choice: "yes",
    timestamp: "2025-11-10T12:34:56Z",
    previous_hash: "abc123..."  // Links to previous event
};
const hash = crypto.createHash('sha256').update(JSON.stringify(eventData)).digest('hex');
```

**Verification:**
- Each vote event includes hash of previous event
- Tampering with any event breaks the chain
- `/vote verify <vote_id>` recomputes entire chain to detect tampering
- Public verification endpoint available

### Council Identification

**Reads from multiple sources:**
1. `data/rotation-schedule.json` - Current week's rotating members (2 alliances)
2. `data/alliances.json` - Top 5 alliances (permanent members) + R5/R4 Discord IDs
3. `data/council.json` - President alliance designation

**Council Composition:**
- 5 permanent seats (top 5 alliances by power)
- 2 rotating seats (from ranks 6-15, changes weekly)
- President identified from `council.json` (not auto-determined by rank)

**Vote Delegation (R4s):**
- R5s can delegate voting to R4s by setting `canVote: true` in `alliances.json`
- Only ONE vote per alliance (R5 has priority, R4 votes when R5 absent)
- Multiple R4s can have delegation, but only first to vote counts

### Environment Setup

**Required variables (`.env`):**
```bash
DISCORD_BOT_TOKEN=your_bot_token
DISCORD_CLIENT_ID=your_application_id
DISCORD_GUILD_ID=your_server_id
VOTE_CHANNEL_ID=channel_for_results
WEBSITE_URL=https://www.lastwar1586.online
WEBHOOK_SECRET=random_secret_string
DATA_DIR=../data
```

**Deployment:** See `bot/CPANEL_SETUP.md` for complete cPanel deployment guide with troubleshooting.

---

## Security Architecture

### Authentication Flow

```
1. User visits admin/login.php
2. Enters email → Server generates magic link JWT
3. Email sent with token link
4. User clicks → Token validated
5. Session JWT issued (30-day expiry)
6. Session stored in HTTP-only secure cookie
7. All pages check JWT via require_jwt_session()
8. All APIs check JWT via require_jwt_session_api()
9. MFA prompt if enabled for user
10. Access granted based on roles
```

### Authorization Pattern

**Role Hierarchy:**
```
admin > president > r5 > r4 > ape > none > disabled
```

**Permission Checks:**
```php
// Single role check
if (!has_role($token, 'admin')) {
    return error('Unauthorized');
}

// Multiple role check (OR logic)
if (!has_role($token, ['admin', 'r5'])) {
    return error('Must be admin or R5');
}

// Alliance-specific check
if (!has_alliance_access($token, $alliance_tag)) {
    return error('No access to this alliance');
}
```

### Data Protection Layers

**Layer 1: File System**
- JSON files in `data/` - world-readable for website
- `admin/users.json` - protected by .htaccess (deny all)
- `admin/.env` - protected by .htaccess, NOT in git
- `admin/audit-log.json` - protected, admin-only access

**Layer 2: Authentication**
- JWT validation on every request
- Token expiry enforcement
- Revocation check (jti tracking)
- MFA verification when enabled

**Layer 3: Authorization**
- Role-based access control
- Alliance-specific permissions
- Action-level granularity (read vs write)

**Layer 4: Audit**
- All actions logged with timestamp, user, IP, action, outcome
- Immutable append-only log
- Real-time monitoring dashboard

**Layer 5: Encryption**
- HTTPS enforced (redirects HTTP → HTTPS)
- JWT signed with secret key (HMAC-SHA256)
- MFA secrets encrypted at rest
- Passwords never stored (magic links only)

---

## Data Management & Integration

### File-Based Storage Pattern

**Why JSON files instead of database:**
- Simple deployment (no DB server needed)
- Easy backups (copy files)
- Version control friendly (git-trackable)
- Fast reads for small datasets (<100 alliances)
- Portable across hosting providers

**File Locking for Concurrency:**
```php
// Read with lock
$data = read_json_file('alliances.json');  // Shared lock (LOCK_SH)

// Write with exclusive lock
write_json_file('alliances.json', $data);  // Exclusive lock (LOCK_EX)
```

**Atomic Write Pattern:**
```php
// Always: read → modify → write in one operation
$alliances = read_json_file('alliances.json');
$alliances[] = $new_alliance;
write_json_file('alliances.json', $alliances);  // Atomic replace
```

### Data Schemas

**Alliance Object (`alliances.json`):**
```json
{
  "tag": "UvvU",
  "name": "veni vidi vici",
  "power": 7804360932,
  "r5": {
    "name": "쿠치나 ᓚᘏᗢ",
    "discordId": "123456789012345678"
  },
  "r4s": [
    {
      "name": "Officer Name",
      "discordId": "987654321098765432",
      "canVote": true,
      "role": "Deputy"
    }
  ],
  "signed": true,
  "tags": ["aggressive", "competitive"]
}
```

**User Object (`admin/users.json`):**
```json
{
  "email": "user@example.com",
  "roles": ["r5", "ape"],
  "alliances": ["UvvU"],
  "mfa_enabled": true,
  "mfa_secret": "encrypted_totp_secret",
  "backup_codes": ["code1", "code2"],
  "created_at": "2025-10-01T12:00:00Z",
  "last_login": "2025-11-10T08:30:00Z"
}
```

**Vote Record (`data/discord-votes.json`):**
```json
{
  "id": "vote_123",
  "title": "Proposal Title",
  "description": "Detailed description",
  "category": "rule_change",
  "created_at": "2025-11-10T12:00:00Z",
  "created_by": "user_discord_id",
  "expires_at": "2025-11-11T12:00:00Z",
  "status": "active",
  "votes": {
    "user_id_1": {
      "choice": "yes",
      "timestamp": "2025-11-10T12:05:00Z",
      "hash": "sha256_hash"
    }
  },
  "hash_chain": [
    {
      "event": "vote_created",
      "hash": "abc123",
      "previous_hash": null
    },
    {
      "event": "vote_cast",
      "hash": "def456",
      "previous_hash": "abc123"
    }
  ]
}
```

### Cross-Component Data Sync

**Scenario: Alliance power update**
1. Admin edits power in `admin/alliances_power.php`
2. API call to `alliances_api.php` with new power value
3. Server updates `data/alliances.json` with exclusive lock
4. Audit log entry created
5. Website fetches updated `alliances.json` on next page load
6. Discord bot reads new power values on next vote creation

**Scenario: Council rotation change**
1. Admin runs `python scripts/update-rotation-schedule.py`
2. Script updates `data/rotation-schedule.json`
3. Website shows updated council members immediately
4. Discord bot reads new council on next vote (for council member list)

**Scenario: Vote completion**
1. Discord bot finalizes vote (all 7 votes or 24h timeout)
2. Bot writes result to `data/discord-votes.json`
3. Bot sends webhook to admin panel endpoint
4. Admin panel records vote in `data/votes.json` (website vote history)
5. Discord bot posts results to Discord channel

---

## Public Website

### Technology Stack
- Pure HTML5, CSS3, JavaScript ES6
- No frameworks, no build process
- No dependencies (except data files)
- Fully static (can use file:// with CORS limitations)

### File Structure

```
/
├── index.html              # Main page structure
├── login.php               # Public login redirect
├── logout.php              # Session termination
├── css/
│   └── styles.css          # All styles (v1.3.2)
├── js/
│   └── app.js              # All frontend logic (v2.0.0)
├── data/
│   ├── alliances.json      # Alliance data (read-only)
│   ├── rules.json          # Server rules
│   ├── amendments.json     # Rule history
│   ├── rotation-schedule.json  # Council rotation
│   ├── council.js          # Utility functions
│   └── power-history.csv   # Power trends
└── images/
    └── logos/              # Alliance logos (optional)
```

### Data Flow
1. On page load, `app.js` fetches JSON data asynchronously:
   - `data/alliances.json` → `alliances` array
   - `data/rules.json` → `serverRules` array
   - `data/amendments.json` → `amendments` array
   - `data/rotation-schedule.json` → `rotationSchedule` object
2. After data loads, all sections render
3. Amendment system applies changes to rules dynamically based on `showChangesEnabled` flag
4. Council section reads pre-generated schedule and filters to show: previous week, current week, next 4 weeks
5. Countdown timer updates every second showing time until next rotation
6. `council.js` provides utility functions for timezone formatting and countdown (loaded synchronously)
7. No server-side processing - everything is client-side rendering

### Council Rotation System
- **Permanent members**: Top 5 alliances (ranks 1-5)
- **Rotating members**: 2 alliances from ranks 6-15, change weekly
- **Rotation timing**: Every Sunday at 10:00 PM EDT (Monday 02:00 UTC)
- **Week calculation**: Based on fixed epoch (May 18, 2025, 10 PM EDT as Week 1)
- **Schedule**: Pre-generated in `rotation-schedule.json` using fair round-robin algorithm
- **Fairness**: All alliances rotate equally before any alliance repeats (10 alliances → 5 weeks per cycle)
- **Display**: Shows previous week (greyed), current week (highlighted), next 4 weeks
- **Countdown**: Real-time countdown timer updates every second
- **Layout**: 5-2 grid (5 permanent in row 1, 2 rotating in row 2)

### Amendment System
The site supports versioned rule changes:
- Amendments are stored separately from base rules
- `applyAmendments()` modifies rules at runtime
- Two display modes:
  - **Show Changes ON**: Highlights additions (+) in green and removals (−) with strikethrough
  - **Show Changes OFF**: Clean view with amendments fully integrated
- Process: Deep copy `serverRules` → apply amendments → render

### Rendering Functions
All rendering is done in `app.js`:
- `renderPodium()` - Top 3 alliances with trophy emojis (🥇🥈🥉)
- `renderAllianceGrid()` - Ranks 4-15 in responsive grid layout
- `renderSignatories()` - R5 signature status for all alliances
- `renderRules()` - Server rules with amendment markers (+ green, − strikethrough)
- `renderCouncil()` - Council members with 5-2 grid layout
- `renderAmendments()` - Collapsible amendment history with version badges

### Responsive Design

CSS includes mobile breakpoints for screens under 768px:
- Podium switches from flexbox to vertical stack
- Council grid changes from 5-column to 2-column
- Alliance cards become full-width
- Font sizes reduce for better mobile readability

### Timezone Display

The rotation schedule includes timezone tooltips that appear on hover, showing times in:
- GMT (primary display)
- EDT/EST, PDT/PST, BRT, KST, AEST/AEDT, CET/CEST (tooltip)

Functions: `formatGMT()` and `formatAllTimezones()` in `council.js`.

---

## Development Setup

### Prerequisites
- **PHP 8.0+** with Composer (for admin panel)
- **Node.js 18+** with npm (for Discord bot)
- **Python 3.7+** (for deployment scripts)
- **Web Server** (for local development - any of: Python http.server, PHP built-in, VS Code Live Server)

### Frontend Development (Public Website)

1. Start a local web server in the project directory:
   - **Python:** `python -m http.server 8000` (then visit http://localhost:8000)
   - **Node.js:** `npx http-server -p 8000`
   - **VS Code:** Use "Live Server" extension (right-click index.html → "Open with Live Server")
   - **PHP:** `php -S localhost:8000`

2. Open http://localhost:8000 in your browser

3. No installation or build steps required

**Important:** The site loads data from JSON files, which requires running from a web server (cannot use `file://` protocol due to CORS).

### Admin Panel Development

1. Navigate to admin directory:
   ```bash
   cd admin
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Create environment file:
   ```bash
   cp .env.example .env
   # Edit .env with your SMTP settings, JWT secret, etc.
   ```

4. Start PHP development server:
   ```bash
   php -S localhost:8080
   ```

5. Open http://localhost:8080 in your browser

6. Login using magic link (check terminal for email output in dev mode)

**See:** `docs/admin/setup-local-env.md` for complete local development setup guide.

### Discord Bot Development

1. Navigate to bot directory:
   ```bash
   cd bot
   ```

2. Install Node.js dependencies:
   ```bash
   npm install
   ```

3. Create environment file:
   ```bash
   cp .env.example .env
   # Edit .env with Discord bot token, client ID, guild ID, etc.
   ```

4. Deploy slash commands (one-time or when commands change):
   ```bash
   node deploy-commands.js
   ```

5. Start the bot:
   ```bash
   node index.js
   ```

**See:** `bot/README.md` for complete bot setup and `bot/CPANEL_SETUP.md` for production deployment.

---

## Development Workflows

### Making Changes to Alliance Data

1. **Edit alliance data via admin panel:**
   - Login to admin panel
   - Navigate to Alliance Management tab
   - Edit power, R5 names, R4s, etc.
   - Changes auto-save to `data/alliances.json`

2. **Regenerate rotation schedule if ranks changed:**
   ```bash
   python scripts/update-rotation-schedule.py
   ```

3. **Test locally:**
   - Check website displays correct data
   - Verify admin panel shows updates
   - Test Discord bot reads new council members

4. **Deploy to production:**
   ```bash
   git add data/
   git commit -m "Update alliance data"
   git push origin mainline  # Triggers CI/CD
   ```

### Adding New Rules or Amendments

1. **Via admin panel** (future feature) OR **directly edit JSON:**
   ```bash
   # Edit data/rules.json for new rules
   # Edit data/amendments.json for rule changes
   ```

2. **Test amendment display:**
   - Open website locally
   - Toggle "Show Changes" to verify highlights

3. **Commit and deploy:**
   ```bash
   git add data/rules.json data/amendments.json
   git commit -m "Add rule amendment v1.3"
   git push origin mainline
   ```

### Creating New Admin Features

1. **Create feature files:**
   - PHP page: `admin/feature_name.php`
   - API endpoint: `admin/feature_name_api.php` (if needed)

2. **Add authentication:**
   ```php
   require_once 'config.php';
   $token = require_jwt_session();  // For pages
   // OR
   $token = require_jwt_session_api();  // For APIs
   ```

3. **Add role checks:**
   ```php
   if (!has_role($token, 'admin')) {
       die('Unauthorized');
   }
   ```

4. **Add CSRF protection for forms/APIs:**
   ```php
   // Generate token
   $csrf_token = generate_csrf_token();

   // Validate on submit
   if (!verify_csrf_token($_POST['csrf_token'])) {
       die('Invalid CSRF token');
   }
   ```

5. **Add audit logging:**
   ```php
   audit_log($token->email, 'feature_action', 'resource_name', 'success', [
       'detail1' => 'value1',
       'detail2' => 'value2'
   ]);
   ```

6. **Use shared header/footer:**
   ```php
   require_once 'includes/header.php';
   // Your page content
   require_once 'includes/footer.php';
   ```

7. **JavaScript best practices:**
   - Use `credentials: 'include'` on all fetch() calls
   - Include `X-CSRF-Token` header on POST/PUT/DELETE
   - Handle loading states with CSS classes
   - Show success/error messages via modal system (not alert/confirm)

### Testing Changes

**Git Pre-commit Hooks:**
- Automatically validates JSON syntax
- Checks for console.log statements (warning only)
- Runs linting (if configured)

**Manual Testing:**
- Admin panel: `cd admin/tests && php run_tests.php`
- Full test suite: `python scripts/run-tests.py`
- Pre-push validation: Automatically runs tests before git push

**See:** `docs/GIT_HOOKS.md` for complete Git hooks setup guide.

### Deployment Process

**Automated (Recommended):**
```bash
git add .
git commit -m "Description of changes"
git push origin mainline  # Triggers GitHub Actions CI/CD
```

The CI/CD pipeline:
1. ✅ Validates JSON/CSV files
2. ✅ Runs unit tests (admin panel)
3. ✅ Deploys to production via FTP
4. ✅ Posts deployment status to GitHub

**Manual Deployment:**
```bash
# Public website only
python scripts/deploy-public-only.py

# Full site with admin panel
python scripts/deploy-ftp.py
```

**See:** `docs/DEPLOYMENT.md` for complete deployment guide.

---

## Important Implementation Details

### Updating Alliance Data

**Recommended:** Use admin panel Alliance Management features.

**Manual edit:** `data/alliances.json`
- Ranks calculated dynamically from `power` field (no `rank` field in JSON)
- Array order doesn't matter (sorted by power DESC)
- Changes take effect on page reload

**JSON Structure:**
```json
[
  {
    "tag": "UvvU",
    "name": "veni vidi vici",
    "power": 7804360932,
    "r5": {
      "name": "쿠치나 ᓚᘏᗢ",
      "discordId": "123456789012345678"
    },
    "r4s": [
      {
        "name": "Officer Name",
        "discordId": "987654321098765432",
        "canVote": true,
        "role": "Deputy"
      }
    ],
    "signed": true
  }
]
```

### Adding Rule Amendments

1. Add entry to `data/amendments.json` with version, date, title, and changes array
2. Changes use `"type": "add"` or `"type": "remove"` with `"text"` content
3. Version number auto-updates in UI from latest amendment
4. Amendment IDs are generated from `version + title` to ensure uniqueness

**JSON Structure:**
```json
[
  {
    "version": "1.2",
    "date": "2025-10-05",
    "title": "Rule Title",
    "changes": [
      {
        "type": "add",
        "text": "New rule text to add"
      },
      {
        "type": "remove",
        "text": "Old rule text to remove"
      }
    ]
  }
]
```

### Council Rotation Schedule Management

**Updating Schedule (Recommended Method):**
```bash
python scripts/update-rotation-schedule.py
```

This Python script:
- Reads current top 15 alliances from `alliances.json`
- **Creates `rotation-schedule.json` if it doesn't exist** (automatic initialization)
- Preserves all past weeks (historical record)
- Generates next 52 weeks from the upcoming rotation
- Ensures fair distribution by looking back 10 weeks
- Handles alliance rank changes gracefully (new alliances spread evenly, no catch-up bunching)
- Provides detailed fairness report

**Only requires:** `data/alliances.json` (schedule file created automatically if missing)

**When to run:**
- After alliance rankings change in `alliances.json`
- Periodically to extend schedule into the future
- When manual overrides are needed for specific weeks

**Initial Generation (One-time use):**
```bash
node scripts/generate-rotation-schedule.js
```
Only needed for completely regenerating schedule from Week 1.

**Manually Editing Schedule:**
- Edit `data/rotation-schedule.json` directly to override specific weeks
- Each week entry format:
  ```json
  {
    "weekNumber": 21,
    "startDate": "2025-10-13T02:00:00.000Z",
    "rotatingMembers": ["STR8", "EPIC"]  // Alliance tags (NOT ranks)
  }
  ```
- **Important:** Uses alliance **tags** not ranks (stable when rankings change)
- Changes take effect on page reload
- Manual edits are preserved when running update script (only future weeks are regenerated)

**Week Calculation:**
```javascript
getCurrentWeekNumber() // Returns current week based on Week 1 epoch
```
Weeks reset Sunday 10 PM EDT. Week 1 epoch: May 18, 2025, 10 PM EDT

### Adding Logos

Currently uses text placeholders (70x70 divs showing alliance tags). To add real logos:
1. Create `images/logos/` directory
2. Add logo files named `[TAG].png` (e.g., `UvvU.png`)
3. Update `createMemberCard()` in `app.js` line 268-270 to replace placeholder with `<img>` tag

### Collapsible Sections

Three collapsible sections use similar patterns:
- Rules section: `toggleRules()`
- Amendments section: `toggleAmendments()`
- Individual amendments: `toggleAmendmentVersion(versionId)`

Each toggles `.active` class which controls height/visibility via CSS.

---

## Code Versioning

All code files include changelog comments at the top documenting changes. When modifying code, update the changelog with version number, date, and description of changes.

**Current versions:**
- Website: v3.0.0
- Admin Panel: v3.9.0+
- Discord Bot: v1.0.0
- HTML: v1.3.2 (2025-10-06)
- JS (app.js): v2.0.0 (2025-10-07) - Now uses alliance tags in rotation schedule
- JS (council.js): v2.0.0 (2025-10-07) - Simplified to utility functions only
- CSS: v1.3.2 (2025-10-06)
- Python (update-rotation-schedule.py): v2.2.0 - Uses alliance tags instead of ranks
- PHP (jwt.php): v2.3.0 - API auth functions, multi-role support
- PHP (json_helpers.php): v1.2.0 - Multi-role helpers

**Data files** (JSON) do not have version headers - they are pure data. Data version is tracked via the `amendments.json` version field and admin panel version system.

**Semantic Versioning:**
- **Major** (X.0.0): Breaking changes or major redesigns
- **Minor** (1.X.0): New features or significant updates
- **Patch** (1.0.X): Bug fixes or minor improvements

---

## Common Pitfalls & Solutions

### Issue: API Returns 401 "No session token"

**Cause:** fetch() not including credentials (JWT cookie not sent)

**Solution:** Add `credentials: 'include'` to all fetch() calls
```javascript
fetch('/api/endpoint', {
    credentials: 'include',  // THIS IS REQUIRED
    // ... other options
});
```

### Issue: API Returns HTML Instead of JSON

**Cause:** Using `require_jwt_session()` instead of `require_jwt_session_api()` in API files

**Solution:** Use `require_jwt_session_api()` in all `*_api.php` files
```php
// WRONG (returns HTML redirect):
$token = require_jwt_session();

// CORRECT (returns JSON error):
$token = require_jwt_session_api();
```

### Issue: Discord Bot Won't Start in cPanel

**Causes:**
1. Partials syntax error (use `Partials.Channel` not `'CHANNEL'`)
2. Missing environment variables
3. Module dependencies not installed
4. Message Content Intent not enabled

**Solutions:** See `bot/CPANEL_SETUP.md` complete troubleshooting guide

### Issue: Dark Mode Text Unreadable

**Cause:** Missing dark theme CSS for specific component

**Solution:** Add dark theme styles to `admin/includes/header.php` following existing patterns:
```css
body.dark-theme .your-component {
    background: #16213e;
    color: #e0e0e0;
    border-color: #0f3460;
}
```

### Issue: File Lock Timeout

**Cause:** Concurrent writes to same JSON file

**Solution:** JSON helpers use exclusive locks (LOCK_EX) with 5-second timeout. If you see this:
1. Check for long-running operations holding locks
2. Ensure all code paths release locks (especially error paths)
3. Consider breaking up large operations

### Issue: Git Pre-commit Hook Blocks Commit

**Cause:** Invalid JSON syntax or security issues

**Solution:**
1. Check error message for specific file and line number
2. Validate JSON at jsonlint.com
3. Fix syntax error
4. If intentional (e.g., console.log in bot), use `git commit --no-verify`

---

## Additional Resources

**Core Documentation:**
- `README.md` - Project overview and quick start
- `docs/README.md` - Complete documentation index
- `admin/README.md` - Admin panel features and setup
- `bot/README.md` - Discord bot features and setup

**Setup Guides:**
- `docs/admin/setup-local-env.md` - Local development environment
- `docs/admin/ENV-CONFIG.md` - Environment variable configuration
- `bot/CPANEL_SETUP.md` - Discord bot cPanel deployment
- `docs/DEPLOYMENT.md` - Production deployment guide

**Feature Documentation:**
- `docs/admin/ADMIN_FUNCTIONALITY.md` - Complete admin feature list
- `docs/admin/MULTI_ROLE_IMPLEMENTATION.md` - Multi-role system details
- `docs/admin/ALLIANCE_MANAGEMENT_GUIDE.md` - Alliance CRUD operations
- `docs/admin/SECRET_KEY_ROTATION_SETUP.md` - JWT key rotation guide
- `docs/admin/CSRF_PROTECTION.md` - CSRF implementation details

**Security & Monitoring:**
- `docs/admin/SECURITY_CHANGELOG.md` - Security feature history
- `docs/admin/USER-PERSONAS.md` - Role-based access control
- `docs/admin/MIGRATION_SYSTEM.md` - Data migration process

**Development:**
- `docs/GIT_HOOKS.md` - Git hooks setup and usage
- `docs/GITHUB_RELEASES.md` - Release management process
- `docs/VERSIONING.md` - Version numbering system
- `scripts/README.md` - Deployment and utility scripts

**Discord Integration:**
- `docs/discord-announcements/BOT-SETUP.md` - Bot configuration
- `docs/discord-announcements/TEMPLATES.md` - Message templates
- `docs/discord-announcements/SCHEDULED-MESSAGES.md` - Scheduled messaging
- `docs/discord-announcements/RECURRING-MESSAGES.md` - Recurring patterns

**Data Schemas:**
- `docs/schemas/ALLIANCE_SCHEMA.md` - Alliance data structure
- `docs/schemas/alliance-with-r4s.md` - R4 management schema
- `docs/schemas/user-profile.md` - User profile schema
- `docs/schemas/R5-SIGNATURE-SCHEMA.md` - Signature tracking

---

**Version:** 2.0.0 (Updated for complete system architecture)
**Last Updated:** November 10, 2025
**Maintained by:** Server 1586 Development Team
