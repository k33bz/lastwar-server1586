# Deployment History

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
