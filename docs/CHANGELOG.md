# Changelog - Server 1586

All notable changes to the Server 1586 project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [3.2.0] - 2025-10-28

### Added - Navigation & Power Trends Features

#### Navigation System (v1.0.0)
- **Hamburger Navigation Menu** - Fixed-position top-left menu with slide-in navigation
  - Quick links to all major sections (Home, Alliances, Council, Rules, Power Trends)
  - Dark overlay when open with smooth transitions
  - Version display at bottom of nav panel
  - Mobile-responsive with adjusted sizing
- **Site Footer** - Professional three-column footer with:
  - Server information and description
  - Quick links (Discord, GitHub, Issues, Documentation)
  - Resource links (internal navigation)
  - Dynamic version number and last updated date
  - Copyright with Claude Code attribution
- **Floating Action Buttons** - Two subtle buttons bottom-right:
  - Back to Top: Appears after 300px scroll, smooth scroll animation
  - Admin Login: Semi-transparent lock icon linking to dashboard
- **Enhanced Section Navigation** - Added IDs to all major sections for anchor linking

#### Power Trends Enhancements (v1.9.5)
- **Interactive Power Chart** with alliance count slider (3, 5, 10, 15, 25, 50 alliances)
- **Hover Highlighting System** - Bold legend, thicker lines, gold tooltip indicators
- **ISO 8601 DateTime Format** - YYYY-MM-DD HH:mm:ss for better sorting
- **Alliance Column Sorting** - Columns ordered by latest power descending
- **Accurate Tooltips** - Hovered alliance shown first with visual indicator

### Changed
- **Rules Version Display** - Updated from v1.0 to v1.2 to reflect current amendments
- **Documentation Link** - Footer now links to README.md instead of CLAUDE.md
- **CSS Version** - Bumped to v1.5.0 (~340 lines added for navigation/footer)
- **HTML Version** - Bumped to v1.4.0 for navigation structure changes
- **JS Version** - Bumped to v2.0.1 (fixed podium ID reference)

### Fixed
- **CSV DateTime Format** - Converted all dates from EDT to GMT, standardized to ISO 8601
- **CSV Validation** - Updated script to accept both 'date' and 'datetime' headers
- **Power-History Data** - Sorted rows chronologically, columns by power descending
- **Alliance Power Values** - Updated 46 alliances with latest 2025-10-26 data
- **Podium ID Conflict** - Renamed podium content div to avoid duplicate IDs

### Technical Details
- **Frontend Version**: 3.0.0 → 3.1.0
- **Files Modified**: index.html, css/styles.css, js/app.js, version.json
- **GitHub Issue**: [#33](https://github.com/k33bz/lastwar-server1586/issues/33) - Navigation system
- **Accessibility**: ARIA labels, semantic HTML, keyboard navigation support

---

## [3.0.0] - 2025-10-16

### Added - Admin Panel Major Release

#### Security & Authentication
- **JWT Authentication System** with passwordless magic links
- **Multi-Factor Authentication (MFA)** with TOTP, backup codes, hardware keys
- **Secret Key Rotation** - Automatic 30-day rotation with emergency rotation capability
- **Session Management** - Active session tracking, 8-hour tokens, refresh capability
- **Security Monitoring** - Real-time threat detection, IP blocking, device tracking
- **Audit Logging** - Comprehensive event tracking for all administrative actions
- **Email Masking** - PII protection for user data display

#### User Management
- **Role-Based Access Control**:
  - Admin (full system access)
  - R5 (alliance leaders - edit alliance + sign rules)
  - R4 (alliance officers - edit alliance data)
  - Power Editor (APE - special permission for bulk power editing)
- **User Management Interface** - Add, edit, delete users with permission control
- **Magic Link System** - Passwordless email authentication
- **Token Revocation** - Blacklist management for compromised tokens

#### Alliance Management
- **Alliance Power Editor** - Bulk alliance power editing interface
- **Alliance Tag Manager** - Category-based tag system for alliances
- **Alliance Edit Interface** - R4/R5 can update their alliance data
- **Dynamic Rank Calculation** - Ranks calculated from power (no more rank/power mismatches)
- **Add/Delete Alliances** - Full CRUD operations for alliance management

#### Data & Backup
- **Automatic Backups** - Scheduled backups of all critical data
- **Point-in-Time Recovery** - Restore from any backup with preview
- **Backup Viewer** - Browse backup contents before restoring
- **File Locking** - Prevents concurrent write conflicts

#### UI/UX Improvements
- **Shared Header/Footer** - Consistent navigation across all admin pages
- **Modal System** - Replaced all alert()/confirm() with modern modals
- **Toast Notifications** - Non-intrusive success/error messages
- **Dropdown Navigation** - Organized menu structure (Alliances, Users, Security)
- **Dynamic Dashboard Statistics** - Live metrics with trends and status indicators
- **Responsive Design** - Mobile-friendly admin interface

### Changed

#### Breaking Changes
- **Dynamic Rank Calculation** - Removed `rank` field from `alliances.json`
  - Ranks now calculated automatically from `power` field
  - Single source of truth eliminates data inconsistencies
  - All rendering functions updated to use calculated ranks

#### Data Structure
- **Rotation Schedule** - Now uses alliance tags instead of ranks (stable when rankings change)
- **User Data** - Added `active_sessions`, `last_login`, `masked_email` fields
- **Alliance Data** - Expanded with `discord`, `founded`, `motto`, `r4List` fields

### Fixed
- **Security Backups Modal** - Fixed auto-popup issue on page load/navigation
- **Session Expiration Warning** - Converted from alert() to proper modal
- **Key Sync Issues** - Added utilities to verify and fix .env/secret_keys.json sync
- **Power History CSV** - Fixed empty cells causing chart rendering errors

### Security
- **Test Token System** - Generate long-lived JWT tokens for API testing
- **Token Blacklisting** - Revoked tokens cannot be reused
- **Rate Limiting** - API request throttling (configurable)
- **CORS Headers** - Cross-origin request protection
- **Input Validation** - Sanitization of all user inputs
- **Audit Trail** - All security events logged with timestamps

---

## [2.0.0] - 2025-10-07

### Added
- **Power Trends Chart** - Time-based alliance power visualization with accurate date spacing
- **Fair Rotation Algorithm** - Improved council rotation with fairness reporting
- **Alliance Tags in Schedule** - Rotation schedule uses tags instead of ranks (more stable)

### Changed
- **Dynamic Rank Calculation** - Ranks calculated from power field (breaking change)
- **Rotation Algorithm** - Updated to use alliance tags for stability
- **Council.js** - Simplified to utility functions only (breaking change)

### Removed
- **Hardcoded Ranks** - Eliminated rank field from alliances.json
- **Rank-based Rotation** - Replaced with tag-based system

---

## [1.6.0] - 2025-10-07

### Added
- **Alliance Modal System** - Click alliance cards to view detailed information
- **Expandable Alliance Profiles** - Support for Discord links, founded date, motto, R4 list
- **R5 Signature History** - Track leadership changes over time

### Changed
- **Alliance Data Schema** - Expanded to support additional alliance metadata
- **Council Rotation Display** - Improved visual hierarchy and mobile responsiveness

---

## [1.4.0] - 2025-10-06

### Added
- **JSON Data Migration** - All data moved from hardcoded JS to JSON files
- **Amendment System** - Track rule changes with version history
- **"Show Changes" Toggle** - View amendments as highlights or integrated
- **Collapsible Sections** - Rules and amendments can be expanded/collapsed

### Changed
- **Data Loading** - Async fetch from JSON instead of hardcoded constants
- **Error Handling** - Better error messages for failed data loads
- **Amendment Display** - Two display modes (with/without change markers)

---

## [1.3.2] - 2025-10-06

### Added
- **Screenshot Processing System (v3.0.0)** - Automated OCR for alliance screenshots
- **Tesseract Training** - Custom training data for better OCR accuracy
- **Batch Processing** - Process multiple screenshots automatically
- **Validation System** - Verify OCR results against expected formats

### Fixed
- **Power Number Recognition** - Improved accuracy for large numbers with K/M/B suffixes
- **Alliance Tag Detection** - Better handling of special characters

---

## [1.2.0] - 2025-10-05

### Added
- **Rule Amendment System** - Track rule changes with dates and descriptions
- **Version Display** - Show current rules version on website
- **Amendment History** - Collapsible section showing all past amendments

### Changed
- **Rules Structure** - Converted to JSON format for easier updates

---

## [1.0.0] - 2025-05-18

### Added - Initial Release
- **Alliance Rankings** - Display top 15 alliances with podium design
- **Council Voting System** - Rotating council members with weekly rotation
- **Server Rules** - Display NAP15 rules in organized categories
- **Rotation Schedule** - Pre-generated 52-week rotation schedule
- **Timezone Support** - Multiple timezone display with DST detection
- **Responsive Design** - Mobile-friendly interface
- **Fair Rotation** - Ensures equal representation over time

---

## Feature Implementation Summaries

### Alliance Modal Implementation (v1.6.0)

**Implemented:** 2025-10-07

**Features:**
- Click-to-expand modal for alliance details
- Support for Discord links, founded date, motto
- R4 officer list display
- Graceful fallback for alliances without extended data
- Mobile-responsive modal design

**Files Modified:**
- `js/app.js` - Added modal rendering and click handlers
- `css/styles.css` - Modal styles and animations
- `data/alliances.json` - Schema expansion (backward compatible)

**Schema Addition:**
```json
{
  "tag": "UvvU",
  "name": "veni vidi vici",
  "power": 7804360932,
  "r5": "R5 Name",
  "signed": true,
  "discord": "https://discord.gg/invite",
  "founded": "2024-03-15",
  "motto": "Alliance motto",
  "r4List": ["Officer1", "Officer2"]
}
```

---

### R5 Signature History Implementation (v1.6.0)

**Implemented:** 2025-10-07

**Features:**
- Track R5 leadership changes over time
- Display current and previous R5s
- Timeline view of leadership transitions
- Automatic signature date tracking

**Files Created:**
- `data/signature-history.json` - Leadership timeline data
- `data/R5-SIGNATURE-SCHEMA.md` - Schema documentation

**Data Structure:**
```json
{
  "UvvU": [
    {
      "r5": "Current R5",
      "signedDate": "2025-10-05",
      "current": true
    },
    {
      "r5": "Previous R5",
      "signedDate": "2024-06-15",
      "current": false
    }
  ]
}
```

---

### Screenshot Processing System (v3.0.0)

**Implemented:** 2025-10-06

**Features:**
- OCR-based screenshot processing
- Automatic alliance data extraction
- Tesseract custom training for improved accuracy
- Batch processing capability
- Validation and error reporting

**Components:**
- `ocr/process-screenshots-v3.py` - Main processor
- `ocr/training_data/` - Custom training datasets
- `tesseract_training/` - Tesseract model training

**Accuracy:**
- Alliance tags: ~95%
- Power numbers: ~90%
- Overall: ~92% with validation

---

### Repository Cleanup (2025-10-15)

**Completed Tasks:**
- ✅ PII sanitization (emails, domains, sensitive data removed)
- ✅ Test token exclusions (.gitignore, .ftpignore)
- ✅ Backup file exclusions
- ✅ Documentation consolidation (in progress)
- ✅ Standardized file naming conventions

**Sanitized:**
- All `.env` example files
- User data (email masking implemented)
- Deployment notes (generic placeholders)
- GitHub workflow files
- Documentation files

---

## Deprecations

### [2.0.0]
- **Deprecated:** `rank` field in `alliances.json` - Use power-based calculation instead
- **Deprecated:** Rank-based rotation schedule - Use tag-based schedule instead

### [1.4.0]
- **Deprecated:** Hardcoded data in `data/*.js` - Use JSON files instead

---

## Migration Guides

### Migrating to v2.0.0 (Dynamic Ranks)

**Before:**
```json
{
  "rank": 1,
  "tag": "UvvU",
  "power": 7804360932
}
```

**After:**
```json
{
  "tag": "UvvU",
  "power": 7804360932
}
```

**Steps:**
1. Remove all `"rank":` fields from `alliances.json`
2. Ensure alliances are sorted by power (descending)
3. Run `python scripts/update-rotation-schedule.py`
4. Deploy updated files

**JavaScript Update:**
```javascript
// Old (v1.x)
const rank = alliance.rank;

// New (v2.x)
const alliances = data.sort((a, b) => b.power - a.power);
const rank = alliances.indexOf(alliance) + 1;
```

---

### Migrating to v1.4.0 (JSON Data)

**Before:**
```javascript
// data/alliances.js
const alliances = [...]
```

**After:**
```json
// data/alliances.json
[
  {...}
]
```

**Steps:**
1. Convert JS arrays to JSON format
2. Update fetch calls in `app.js`
3. Test with local web server (cannot use file:// protocol)

---

## Known Issues

### Current
- None

### Fixed in v3.0.0
- ✅ Security backups modal auto-popup
- ✅ Session warning using browser alert()
- ✅ Key sync issues between .env and secret_keys.json
- ✅ Power history CSV empty cells

### Fixed in v2.0.0
- ✅ Rank/power data mismatches
- ✅ Rotation schedule breaks when rankings change

---

## Roadmap

### Planned for v3.1.0
- [ ] Admin dashboard caching (60-second TTL)
- [ ] Alliance trend tracking (auto-generate alliance-count-history.json)
- [ ] Email notification system for security events
- [ ] API rate limiting dashboard
- [ ] Real-time audit log updates (WebSocket)

### Planned for v4.0.0 (Future)
- [ ] AWS Lambda backend migration
- [ ] DynamoDB data storage
- [ ] API Gateway endpoints
- [ ] Cognito authentication
- [ ] Admin dashboard UI overhaul

---

## Contributors

- **k33bz** - Project maintainer
- **Claude Code** - AI-assisted development

---

## Links

- **Repository:** https://github.com/k33bz/lastwar-server1586
- **Production:** https://www.example.com
- **Documentation:** [DOCUMENTATION.md](DOCUMENTATION.md)

---

**Last Updated:** 2025-10-19
**Current Version:** 3.0.0
