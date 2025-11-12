# Claude Code Workflow Guidelines

## Project Status

**HeroUI v3 Migration: ✅ COMPLETE**
- Public site migrated to React with HeroUI v3
- Old site backed up to `old-site-backup/`
- Production build deployed and tested
- See `DEPLOYMENT.md` for details

**Discord Vote Management System: ✅ COMPLETE**
- Unified API for bot and admin site (`admin/discord_votes_api.php`)
- Council member proposal interface (`admin/discord_vote_proposals.php`)
- President approval dashboard (`admin/president_vote_approvals.php`)
- Bot authorization security fix (only admin/president can create votes)
- Navigation links with role-based access control
- Critical bug fix: `top5Permanent` field reference (commit 0be1633)

---

## Bug Tracking & Documentation

### ✅ DO: Use GitHub Issues
- **Bug reports**: Create GitHub issues for bugs discovered
- **Feature requests**: Create GitHub issues with proper labels
- **Bug fixes**: Reference the issue number in commits
- **Documentation of fixes**: Add comments to the GitHub issue

### ❌ DON'T: Create .md Files for Bugs
- Don't create `DISCORD_FIXES.md`, `DISCORD_ISSUES_FIXED.md`, etc.
- Don't create separate markdown files for bug tracking
- .md files are for permanent documentation only (README, guides, etc.)

## Workflow

1. **Find bug** → Create GitHub issue
2. **Fix bug** → Reference issue in commit
3. **Document fix** → Comment on GitHub issue with details
4. **Close issue** → When fix is tested and working

## GitHub Issue Commands

```bash
# Create issue
gh issue create --title "Bug: Cannot save Discord channels" --body "Description..." --label bug

# Comment on issue
gh issue comment 123 --body "Fixed by implementing..."

# Close issue
gh issue close 123 --comment "Fixed and tested"

# View issue
gh issue view 123
```

## Git Commit Guidelines

### ✅ DO: Use LM Studio Integration
- **NEVER use `SKIP_LMSTUDIO=1`** - Always let LM Studio review commits
- Let the commit-msg hook run fully for quality checks
- LM Studio provides valuable commit message improvements

### Commit Commands
```bash
# Standard commit (with LM Studio review)
git add .
git commit -m "fix(admin): Description of fix"

# DON'T use:
# SKIP_LMSTUDIO=1 git commit  ❌
```

## Discord Vote Management System

### Overview
Unified system for managing Discord council votes through both web admin and Discord bot.

### Workflow
1. **Council Member (R5/R4/APE)**: Submits vote proposal via web or Discord
2. **President/Admin**: Reviews and approves/rejects via web or Discord (or auto-approved after 12h)
3. **System**: Auto-creates vote when approved
4. **Discord Bot**: Polls every minute for new web-created votes and publishes to Discord
5. **Voters**: Receive DM notification, submit votes via Discord
6. **Finalization**: Vote closes after 24h or all votes submitted
7. **Results**: Posted to Discord channel + DM notifications sent to all voters

### Key Files
- `admin/discord_votes_api.php` - REST API with 9 endpoints
- `admin/discord_vote_proposals.php` - Council member proposal interface
- `admin/discord_vote_approvals.php` - President approval dashboard
- `bot/commands/vote.js` - Discord bot vote command handler
- `bot/utils/voteManager.js` - Vote creation, management, and notifications
- `bot/utils/councilUtils.js` - Council member utilities
- `bot/jobs/voteMonitor.js` - Polls for unpublished votes & finalizes expired votes
- `bot/jobs/requestMonitor.js` - Auto-approves requests after 12 hours
- `data/discord-votes.json` - Shared vote data (bot & web)
- `data/discord-vote-requests.json` - Shared request data (bot & web)

### Access Control
- **R5/R4/APE**: Can submit vote proposals
- **President/Admin**: Can create votes directly, approve/reject requests
- **Authorization**: Bot checks `admin/users.json` for Discord user permissions

### API Endpoints
```
POST /admin/discord_votes_api.php?action=create_request   (R5/R4/APE/President/Admin)
POST /admin/discord_votes_api.php?action=create_vote      (President/Admin)
POST /admin/discord_votes_api.php?action=approve_request  (President/Admin)
POST /admin/discord_votes_api.php?action=reject_request   (President/Admin)
GET  /admin/discord_votes_api.php?action=get_requests
GET  /admin/discord_votes_api.php?action=get_votes
```

### Bot Monitoring & Automation
**Vote Monitor** (runs every minute):
- Polls for web-created votes with null `vote_message_id`
- Automatically publishes to Discord channel
- Sends DM notifications to all eligible voters
- Finalizes votes when 24-hour deadline expires

**Request Monitor** (runs every 15 minutes):
- Auto-approves vote requests after 12 hours
- Creates vote automatically upon approval
- Publishes vote to Discord
- Notifies requester of auto-approval

**Vote Notifications**:
- Initial DM when vote is created (with voting instructions)
- Result DM when vote finalizes (shows outcome + individual vote)
- Channel posts for vote announcement and results

### Critical Bugs Fixed
**Commit 0be1633**: rotation-schedule.json field reference
- **Issue**: API and bot referenced non-existent `top15Snapshot` instead of `top5Permanent`
- **Impact**: Vote creation would fail completely
- **Fix**: Updated `admin/discord_votes_api.php` and `bot/utils/councilUtils.js`

## Frontend Development

### React Application (NEW)
```bash
cd client
npm run dev        # Development server at http://localhost:5173
npm run build      # Production build to client/dist/
npm run preview    # Preview production at http://localhost:4173
```

### Deploying Changes
```bash
cd client
npm run build
cp dist/index.html ../index.html
cp -r dist/assets ../assets
```

### Key Files
- `client/src/` - React components and source code
- `client/public/data/` - JSON data files
- `client/public/images/` - Alliance logos
- `DEPLOYMENT.md` - Deployment guide
- `HEROUI_MIGRATION.md` - Migration documentation

## Notes
- Use TodoWrite tool for task tracking during active work
- Use GitHub issues for permanent bug/feature tracking
- Keep this file updated with workflow preferences
- LM Studio is running on localhost:1234 - always use it!
- HeroUI v3 components use `onPress` instead of `onClick`
- Use compound components pattern (e.g., `Card.Header`, `Card.Content`)
