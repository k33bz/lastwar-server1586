# Changelog - Server 1586

All notable changes to the Server 1586 project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

























## [3.3.3] - 2025-10-31

**Commit:** bf7bf3d - fix: Add null coalescing to prevent count() errors in security_monitor.php

### Fixed
- Added null coalescing operators to prevent count() errors in security_monitor.php when handling empty arrays for security log events, blacklists, and rate limits data structures.

---

## [3.3.3] - 2025-10-31

**Commit:** ee005f1 - docs: Auto-update CHANGELOG.md for v3.3.3

### Fixed
- Resolved issue #52 regarding deployment overwriting production .env with rotated JWT keys
- Implemented exclusion of admin/.env from deployment (.ftpignore)
- Excluded production state files (secret_keys.json, blacklisted_tokens.json, users.json) from deployment
- Removed .env generation from GitHub Actions workflow
- Created production .env management guide (docs/PRODUCTION-ENV-SETUP.md)
- Eliminated mass user logouts caused by key rotation overwrites during deployment

---

## [3.3.3] - 2025-10-31

**Commit:** 85eeecd - chore: Bump version to 3.3.2

### Changed
- Updated version.json to bump version to 3.3.2 and adjust release dates
- Modified deployment_protection component to include new version 1.0.0 with updated date and description

### Fixed
- Resolved issue #52 regarding deployment overwriting production .env with rotated JWT keys
- Implemented exclusion of admin/.env from deployment (.ftpignore)
- Excluded production state files (secret_keys.json, blacklisted_tokens.json, users.json) from deployment
- Removed .env generation from GitHub Actions workflow
- Created production .env management guide (docs/PRODUCTION-ENV-SETUP.md)
- Eliminated mass user logouts caused by key rotation overwrites during deployment

---

## [3.3.2] - 2025-10-31

**Commit:** e41cf99 - docs: Auto-update CHANGELOG.md for v3.3.2

### Fixed
- Prevented deployment process from overwriting production .env file with rotated keys
- Updated .ftpignore to exclude production admin files (.env, key_rotation.json, blacklisted_tokens.json, users.json) from deployment
- Revised deploy workflow to remove automatic creation of production .env file and emphasize manual setup requirement for production server
- Updated documentation to reflect new production environment setup process and key rotation system management

---

## [3.3.2] - 2025-10-31

**Commit:** d466361 - fix: CRITICAL - Prevent deployment from overwriting production .env with rotated keys

### Fixed
- Prevented deployment process from overwriting production .env file with rotated keys
- Updated .ftpignore to exclude production admin files (.env, key_rotation.json, blacklisted_tokens.json, users.json) from deployment
- Revised deploy workflow to remove automatic creation of production .env file and emphasize manual setup requirement for production server
- Updated documentation to reflect new production environment setup process and key rotation system management

---

## [3.3.2] - 2025-10-30

**Commit:** 41274b8 - docs: Update documentation to version 3.3.1

### Changed
- Updated documentation to version 3.3.1
- Modified version and last updated date in README.md
- Adjusted admin panel description in README.md
- Updated admin panel description in admin/README.md
- Revised secret key rotation period from 30 days to 90 days
- Added CSRF protection for API endpoints
- Enhanced testing infrastructure with 40 automated tests
- Updated last updated date in version.json

### Fixed
- Corrected version information in index.html
- Updated documentation in DOCUMENTATION.md

---

## [3.3.1] - 2025-10-30

**Commit:** db13ca4 - test: Add comprehensive unit tests for shared utility functions

### Added
- New unit test file `UtilityFunctionsTest.php` with 11 tests for shared utility functions
- New test result file `utility-test-results.json` to track utility function test results

### Changed
- Updated `admin/tests/README.md` to include documentation for new utility function tests
- Modified `.gitignore` to include new test result file path

### Fixed
- Updated test runner instructions in `admin/tests/README.md` to reference new utility function tests
- Added proper test execution commands for utility functions in README documentation

---

## [3.3.1] - 2025-10-30

**Commit:** 2226926 - fix: Fix CSRF blocking GET requests in alliances_power_api (Issue #43)

### Fixed
- Resolved CSRF issue that was blocking GET requests in `alliances_power_api.php`
- Fixed header output issues in `migrate.php` by adding output buffering for web mode

---

## [3.3.1] - 2025-10-30

**Commit:** e38b45f - fix: Fix critical PHP errors breaking admin site login

### Fixed
- Resolved critical PHP syntax errors in admin scripts that were preventing site login
- Fixed issue with `audit_logger.php` missing required rate limiter include
- Corrected variable reference in `cron_key_rotation.php` to properly display admin panel URL
- Implemented PHP syntax checking in deployment workflow to prevent future syntax errors
- Updated `.gitignore` to properly track `admin/tests/` directory while ignoring root-level `tests/` directory

---

## [3.3.1] - 2025-10-30

**Commit:** 8d178bd - fix: Add admin/tests directory to repository (was gitignored)

### Added
- Admin panel unit test suite with comprehensive role-based access control testing
- Test runner scripts for Windows (run-tests.bat) and Unix (run-tests.sh)
- README documentation for test setup and execution instructions

### Changed
- Updated test structure to include new test files and documentation

### Fixed
- Resolved gitignore issue preventing admin/tests directory from being included in repository

---

## [3.3.1] - 2025-10-30

**Commit:** 937f0f1 - feat: Add PHP unit tests to CI/CD pipeline (Closes #51)

### Added
- PHP unit tests integrated into CI/CD pipeline, including setup, execution, and results upload steps

### Changed
- Updated deployment workflow to include PHP testing and validation steps
- Enhanced test summary output to include PHP unit test status

### Fixed
- Updated documentation links in README.md to reflect repository reorganization, ensuring all references point to correct locations within the docs directory

---

## [3.3.1] - 2025-10-30

**Commit:** 0bad8af - docs: Fix all broken documentation links after repository reorganization

### Fixed
- Updated documentation links in README.md to reflect repository reorganization, ensuring all references point to correct locations within the docs directory.

---

## [3.3.1] - 2025-10-30

**Commit:** f0836b5 - chore: Move remaining markdown files to docs/ folder

### Changed
- Moved CONTRIBUTORS.md and DOCUMENTATION.md files to docs/ folder for better organization and clarity.

---

## [3.3.1] - 2025-10-30

**Commit:** 11d3818 - chore: Reorganize repository structure and cleanup temp files

### Changed
- Reorganized repository structure to improve file management and cleanup of temporary files
- Updated `.gitignore` to better categorize helper scripts and temporary files in `scripts/` and `temp/` directories
- Moved documentation files into `documentation-archive/` directory for better organization

### Fixed
- Resolved issues related to repository cleanup and file tracking by reorganizing file structure and updating `.gitignore` entries

---

## [3.3.1] - 2025-10-30

**Commit:** 08d8dba - docs: Document repo-review.py log monitoring capability (Closes #50)

### Added
- Added intelligent LM Studio log monitoring capability to `repo-review.py` script for comprehensive repository analysis
- New documentation in `docs/LM-STUDIO-TESTING.md` describing log monitoring features and usage

### Changed
- Updated `repo-review.py` script with enhanced log monitoring functionality including real-time tracking, performance stats extraction, and completion detection after HTTP timeouts
- Enhanced documentation in `docs/LM-STUDIO-TESTING.md` to include detailed usage instructions and log monitoring workflow explanation

### Fixed
- Resolved issue #50 by implementing log monitoring capabilities for repository review process

---

## [3.3.1] - 2025-10-30

**Commit:** 7a3a44a - feat: Add automatic email notifications for role and permission changes

### Added
- New feature to send automatic email notifications when role or permission changes are made to users

### Changed
- Updated `mailer.php` to include new function `send_role_change_email` for handling role change notifications
- Enhanced user management API in `user_management_api.php` to support the new email notification functionality

### Fixed
- No fixes required for this commit

---

## [3.3.1] - 2025-10-30

**Commit:** 9f985df - docs: Add LM Studio unit test generation capability

### Added
- Added documentation for using LM Studio to generate unit tests for the admin panel
- Introduced script for generating PHPUnit test cases from source code
- Included scripts for analyzing edge cases and reviewing test coverage

### Changed
- Updated test generation process to leverage LM Studio's capabilities for testability and edge case discovery

### Fixed
- None

---

## [3.3.1] - 2025-10-30

**Commit:** b9e7291 - feat: Add comprehensive input validation for alliance editor (High priority)

### Added
- Input validation for alliance tag, name, R5 name, and Discord server name in alliance editor API

### Changed
- Updated alliance update logic to include sanitized input validation before processing

### Fixed
- Improved error handling for invalid input data in alliance editor API

---

## [3.3.1] - 2025-10-30

**Commit:** f1ae981 - feat: Add comprehensive input validation for power editor (Critical fix)

### Added
- Input validation logic for alliance tag, name, and power in `admin/alliances_power_api.php`

### Fixed
- Critical input validation bug fixes for power editor functionality, ensuring proper sanitization and error handling for alliance data updates

---

## [3.3.1] - 2025-10-30

**Commit:** c089cca - feat: Implement restrictive file permissions for sensitive data (Fixes #38)

### Changed
- Implemented restrictive file permissions for sensitive data files to enhance security
- Added permission settings for audit log files and JSON helper files in admin directory

### Fixed
- Resolved issue #38 related to sensitive data access control

---

## [3.3.1] - 2025-10-30

**Commit:** 56913a2 - feat: Implement comprehensive XSS protection across admin panel (Fixes #37)

### Changed
- Implemented comprehensive XSS protection in admin panel for alliance-related functions
- Updated HTML attribute escaping to prevent cross-site scripting vulnerabilities

### Fixed
- Resolved potential XSS security issue in admin panel (Fixes #37)

---

## [3.3.1] - 2025-10-29

**Commit:** b021ffd - feat: Implement CSRF protection across all admin API endpoints (Fixes #36)

### Added
- CSRF protection implemented across all admin API endpoints to prevent cross-site request forgery attacks

### Fixed
- Resolved security vulnerability related to missing CSRF token validation in admin API endpoints (Fixes #36)

---

## [3.3.1] - 2025-10-29

**Commit:** 72138f4 - security: Implement rate limiting to prevent brute force attacks (Issue #35)

### Added
- Added rate limiting middleware to prevent brute force attacks and DDoS
- Implemented API rate limiting with 20 requests per minute for admin API endpoints
- Added login rate limiting with 5 attempts per 60 seconds for admin login

### Changed
- Updated admin/login.php and admin/send_magic_link.php to include rate limiting checks
- Enhanced security measures to protect against unauthorized access attempts

### Fixed
- Resolved potential security vulnerability related to brute force attacks (Issue #35)

---

## [3.3.1] - 2025-10-29

**Commit:** 6366ea4 - docs: Minor wording improvement in configuration section

### Changed
- Updated wording in configuration section to improve clarity and user understanding

---

## [3.3.0] - 2025-10-29

### Added - Public API & Architecture Improvements

#### Public Read-Only API (v1.0.0)
- **Control/Data Plane Separation** - Clear architectural boundary between admin writes and public reads
- **7 REST API Endpoints** - Read-only public data access
  - `GET /api/alliances.php` - Alliance rankings (cache: 60s)
  - `GET /api/rules.php` - Server rules (cache: 300s)
  - `GET /api/amendments.php` - Rule amendments (cache: 300s)
  - `GET /api/council.php` - Current council members (cache: 60s)
  - `GET /api/council/schedule.php` - Rotation schedule (cache: 300s)
  - `GET /api/version.php` - Version information (cache: 300s)
  - `GET /api/server-info.php` - Server metadata (cache: 3600s)
- **Interactive API Documentation** - `/api/` serves testing interface with live endpoint testing
- **CORS Support** - Cross-origin resource sharing enabled for external consumption
- **ETag Caching** - Efficient conditional requests with 304 Not Modified responses
- **HTTP Cache Headers** - Cache-Control and Expires headers for optimal performance
- **File Locking** - LOCK_SH for reads, LOCK_EX for writes to prevent race conditions
- **Standard JSON Responses** - Consistent response format with success/error handling
- **Security Headers** - X-Content-Type-Options, X-Frame-Options for API security

#### Documentation & Configuration
- **GitHub Badges** - Release version, deploy status, and license badges in README
- **API Documentation** - Comprehensive docs/PUBLIC_API.md with examples and usage
- **Configuration Templates** - Created server-info.json.example and .project-info.json
- **Documentation Sanitization** - All .md files use example values (example.com, discord.gg/your-invite)
- **Prod/Dev Separation** - Clear separation between documentation (examples) and functional files (real values)

### Changed
- **Version**: 3.2.0 → 3.3.0
- **File Locking**: Admin panel already had file locking (verified in json_helpers.php)
- **Deployment Exclusions**: Added docs/ directory to .ftpignore

### Fixed
- **Issue #5**: Exclude docs/ directory from FTP deployment
- **Issue #12**: Add GitHub badges to README.md
- **API Directory Browsing**: Added .htaccess to serve index.php and disable directory listing

### Security
- **No PII in Documentation** - All real URLs, Discord invites, and SMTP hosts replaced with examples
- **Git Security** - .project-info.json (with real values) excluded via .gitignore
- **Deployment Security** - Documentation files excluded from production via .ftpignore
- **Read-Only API** - Public API has no write access to data files
- **CORS Configuration** - Safe cross-origin access for public data only

### Technical Details
- **Architecture**: File-based control/data plane separation (no AWS required)
- **API Files**: 9 new PHP files + comprehensive documentation
- **Caching Strategy**: Variable cache durations (60s-3600s) based on data volatility
- **Response Format**: JSON with success flag, timestamp, and data payload
- **Production Tested**: All endpoints verified live and operational
- **Backward Compatible**: Direct JSON file access still works

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

**Last Updated:** 2025-10-29
**Current Version:** 3.3.0
