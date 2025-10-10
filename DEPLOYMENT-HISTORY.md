# Deployment History

## v1.5.0 - 2025-10-10

### Changes
- **R5 Signature History System**: Complete tracking of R5 leaders and rule signatures
  - Implemented signature-history.json with complete audit trail
  - Grace period system (7 days for new R5s to sign)
  - Dynamic status display: signed (green), pending (yellow), overdue (red), no R5 (gray)
  - Status calculation with days remaining/overdue
  - Complete R5 history per alliance with start/end dates

- **Alliance Detail Modal System**: Interactive modal for comprehensive alliance information
  - Click-to-open on any alliance card, podium position, or signatory
  - R5 Leadership History timeline section
  - Current R5 highlighted with gold "CURRENT" badge
  - Tenure tracking with day counts
  - All signatures per R5 with versions and timestamps
  - Support for Discord info, recruiting status, requirements, cross-server alliances
  - Smooth animations and responsive mobile design

- **Server Discord Banner**: Server-wide Discord information section
  - Join button with Discord branding (#5865f2)
  - Feature tags display (NAP15 Coordination, Event Planning, etc.)
  - Fully responsive layout
  - Added server-info.json configuration

- **Alliance Data Schema v2.0**: Comprehensive alliance information structure
  - R5 object with name, gameId, discordId
  - Discord server details (name, invite URL)
  - Recruiting status and requirements (power, level, activity)
  - Languages and specialties tags
  - Cross-server alliance tracking
  - Achievements (peak rank, peak power)
  - Backward compatible with v1.0 format

### Files Updated
- `css/styles.css` → v1.4.2
  - Added R5 Leadership History styles (lines 1455-1574)
  - Added Alliance Detail Modal styles (lines 1136-1453)
  - Added Server Discord Banner styles (lines 87-197)
  - Added signature status colors (overdue, no-r5)
  - Added responsive mobile styles for modal

- `js/app.js` → Updated with signature and modal systems
  - Added `signatureHistory` global variable
  - Added `getSignatureStatus()` function (lines 112-197)
  - Added `getCurrentR5()` helper function (lines 98-110)
  - Updated `renderSignatories()` with status system (lines 402-419)
  - Added `openAllianceModal()` and `closeAllianceModal()` (lines 666-901)
  - Added R5 history display in modal (lines 959-1021)
  - Updated `loadData()` to fetch signature-history.json
  - Added `renderServerDiscord()` function (lines 220-267)

- `index.html` → Updated with new sections
  - Added Server Discord banner section (lines 78-101)
  - Added Alliance Detail Modal HTML structure (lines 208-225)

- `data/alliances.json` → Updated with v2.0 example
  - ORCE alliance updated with full v2.0 structure
  - Other 14 alliances remain in v1.0 format (backward compatible)

### Files Created
- `data/signature-history.json` → R5 history and signatures for all 15 alliances
- `data/server-info.json` → Server Discord configuration
- `data/R5-SIGNATURE-SCHEMA.md` → Complete schema specification
- `data/ALLIANCE-DATA-SCHEMA.md` → v2.0 alliance data format documentation
- `R5-SIGNATURE-HISTORY-IMPLEMENTATION.md` → Implementation guide
- `ALLIANCE-MODAL-IMPLEMENTATION.md` → Modal system documentation
- `ALLIANCE-INFO-UPDATE-SUMMARY.md` → System overview
- `images/HOW-TO-ADD-DISCORD-LOGO.md` → Logo setup instructions

### Deployment Details
- **Deployed:** 2025-10-10 16:39 UTC
- **Method:** FTP via `scripts/deploy-ftp.py`
- **Files Deployed:** 12 files (0 failures)
  - .htaccess
  - css/styles.css
  - data/alliances.json
  - data/amendments.json
  - data/council.js
  - data/rotation-schedule.js
  - data/rotation-schedule.json
  - data/rules.json
  - data/server-info.json
  - data/signature-history.json
  - index.html
  - js/app.js

### Verification
- ✅ Website accessible at https://www.example.com
- ✅ R5 signature status displaying correctly
- ✅ Grace period logic working (7-day window)
- ✅ Alliance modal opens on click
- ✅ R5 history timeline displays with signatures
- ✅ Server Discord banner visible
- ✅ Mobile responsive design working
- ✅ All 12 files deployed successfully

### Technical Highlights
- Complete audit trail for R5 changes and signatures
- Automatic grace period calculation with visual indicators
- Dynamic status based on current R5, current version, and tenure
- Backward compatible with v1.0 alliance data
- Click-to-open modal system with comprehensive alliance details
- R5 leadership timeline with tenure tracking
- Signature version tracking per R5
- Warning display for unsigned R5s (STR8, UUSN examples)

### Git Commit
- **Commit:** ddecc53
- **Message:** feat: R5 signature history system with alliance modal enhancements
- **Branch:** mainline
- **Pushed:** origin/mainline

---

## v1.4.2 - 2025-10-09

### Changes
- **Fixed mobile rendering issue**: Rules section was getting cut off on mobile devices
  - Changed CSS `.rules-content.active` from `max-height: 5000px` to `max-height: none`
  - Updated `overflow` to `visible` for proper content display

- **Implemented configurable rotation back-to-back prevention**
  - Added `MIN_WEEKS_BETWEEN_ROTATIONS = 2` configuration parameter
  - Implemented sliding window history tracking (last N-1 weeks)
  - Added graduated penalty system for recent rotations
  - Regenerated schedule with improved fairness algorithm (v2.2.0)
  - **Verified:** No back-to-back rotations in schedule (all alliances have minimum 2-week gap)

- **Updated FTP deployment script**
  - Changed credential name from `sftp_server1586_web` to `ftp_example.com`
  - Configured for FTP user: `ftpuser@example.com`
  - Successfully deployed 13 files to web root

### Files Updated
- `css/styles.css` → v1.4.1
- `scripts/update-rotation-schedule.py` → v2.2.0
- `scripts/deploy-ftp.py` → Updated credential configuration
- `index.html` → v1.4.2
- `index_remote.html` → v1.4.2
- `data/rotation-schedule.json` → Regenerated with no violations
- `data/rotation-schedule.js` → Created as workaround for JSON access
- `rotation-schedule.js` → Root-level rotation data

### Documentation Updated
- `scripts/DEPLOY-README.md` → Added FTP deployment instructions
- `scripts/README.md` → Already had configuration documentation
- `VERSION-UPDATE-CHECKLIST.md` → Updated to v1.4.2

### Deployment Details
- **Deployed:** 2025-10-09 20:01 UTC
- **Method:** FTP via `scripts/deploy-ftp.py`
- **Credentials:** Windows Credential Manager (`ftp_example.com`)
- **Files Deployed:** 13 files (0 failures)
- **Verification:**
  - Website shows v1.4.2 ✅
  - Rotation schedule has no back-to-back violations ✅
  - Mobile CSS fix active ✅

### Known Issues Resolved
- ~~Version mismatch (localhost v1.4 vs remote v1.3)~~ → Fixed by deploying v1.4.2
- ~~SWBA back-to-back in weeks 24-25~~ → Fixed by regenerating schedule with prevention algorithm
- ~~Rules cut off on mobile~~ → Fixed by removing max-height restriction
- ~~FTP directory mismatch~~ → Fixed by creating proper FTP user with web root access
- ~~JSON files returning 404~~ → Workaround: Created `.js` versions loaded via script tags

---

## v1.4.0 - 2025-10-08

### Changes
- Added version tracking for deployment verification
- Updated timezone system to use UTC for all calculations
- Fixed rotation schedule week number calculation
- Changed rotation display to show correct timezone abbreviations (EDT/EST auto-detected)

### Files Updated
- `index.html` → v1.4.0
- `js/app.js` → v1.4.0

---

## v1.3.2 - 2025-10-06

### Changes
- Added "Previous Week Rotation" section to show last week's rotating members
- Updated council grid layout from 3-2-2 to 5-2 pattern

### Files Updated
- `index.html` → v1.3.2
- `css/styles.css` → Updated grid layout

---

## v1.3.0 - 2025-10-06

### Changes
- Moved Council Voting Members section above Rules section
- Redesigned council layout with 3-2-2 grid for better aesthetics
- Added placeholder logo support for alliance branding
- Improved visual hierarchy and spacing

### Files Updated
- `index.html` → v1.3.0
- `css/styles.css` → Updated layout styles

---

## v1.2.0 - 2025-10-05

### Changes
- Added Council Voting Members section with dynamic rotation
- Added timezone display for rotation schedule
- Implemented collapsible amendments section

### Files Updated
- `index.html` → v1.2.0
- `js/app.js` → Added council rotation logic
- `data/council.js` → Council member data

---

## v1.1.0 - 2025-10-05

### Changes
- Added amendment tracking system
- Added version control for rules
- Added R5 signatories section

### Files Updated
- `index.html` → v1.1.0
- `data/amendments.json` → Amendment history
- `data/rules.json` → Versioned rules

---

## v1.0.0 - 2025-10-05

### Initial Release
- Alliance rankings display
- Top 3 podium design
- Server rules display
- NAP15 member listing

### Files Created
- `index.html`
- `css/styles.css`
- `js/app.js`
- `data/alliances.json`
- `data/rules.json`
