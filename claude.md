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

**Admin Panel i18n: ✅ COMPLETE**
- Multi-language support for 15 languages (EN, ES, PT, DE, KO, FR, IT, JA, ZH, RU, AR, NL, PL, TR, SV, DA)
- Translation system using `__('key')` pattern
- User language preferences stored in JWT and user profile
- Automated translation via LM Studio (Hunyuan-MT-7B)
- Translation files: `admin/i18n/{lang}/translations.json`
- Magic link emails sent in user's preferred language
- Language selector on login page and user profile
- Consolidation pattern: Common translations in `common.*` namespace
- 9+ admin pages translated (dashboard, user management, Discord votes, etc.)

**UID-Based Identity System (v4.0.0): ✅ COMPLETE**
- Migrated from email-based to UID-based user identity
- All 9 users migrated with UID format: `usr_XXXXXXXXXXXXXXXX`
- JWT tokens updated: `sub` claim = UID, `email` as separate claim
- Email change history tracking with timestamps
- R4 management linked to user UIDs (not emails)
- Backward compatible with legacy email-based tokens
- Migration script: `admin/migrate_to_uid.php`
- Key files: `jwt.php`, `json_helpers.php`, `profile_api.php`, `callback.php`

**Development Tools: ✅ COMPLETE**
- GUI development server tool (`dev_server.py`)
- Controls PHP admin server, React dev server, Vite preview
- Live log monitoring for all servers
- One-click browser launch and build management
- Translation automation tool (`translation_tools/translate_admin_reliable.py`)

---

## Translation Tool System

### Overview
Automated translation system using LM Studio with Hunyuan-MT-7B model for admin panel internationalization.

### Key Files
- `translation_tools/translate_admin_reliable.py` - Main translation script
- `translation_tools/translate_config.json` - Configuration file
- `admin/i18n/en/translations.json` - Source English translations
- `admin/i18n/{lang}/translations.json` - Target language files (15 languages)

### Configuration (`translate_config.json`)
```json
{
  "i18n": {
    "source_locale": "en",
    "default_namespace": "admin",
    "key_separator": ".",
    "fallback_locale": "en",
    "validate_placeholders": true,
    "validate_html_tags": true,
    "validate_preserve_terms": true
  },
  "model": "tencent.hunyuan-mt-7b",
  "temperature": 0.1,
  "max_tokens": 512,
  "max_retries": 3,
  "timeout": 30,
  "lm_studio_url": "http://localhost:1234",
  "preserve_terms": [
    "R5", "R4", "APE", "NAP15", "Discord", "SMTP", "JWT", "API",
    "UvvU", "ORCE", "MTOP", "FNXS", "MZKU", "admin",
    "🚀", "📋", "🛡️", "⚠️", "✅", "🗳️", "📅", "📊", "ℹ️"
  ],
  "languages": {
    "es": "Spanish", "pt": "Portuguese", "de": "German", "ko": "Korean",
    "fr": "French", "it": "Italian", "ja": "Japanese", "zh": "Chinese (Simplified)",
    "ru": "Russian", "ar": "Arabic", "nl": "Dutch", "pl": "Polish",
    "tr": "Turkish", "sv": "Swedish", "da": "Danish"
  }
}
```

### Running Translations

**Incremental Translation** (recommended):
```bash
cd translation_tools
python translate_admin_reliable.py --incremental 2>&1 | tee ../translation_output.log
```

**Full Translation** (all strings):
```bash
cd translation_tools
python translate_admin_reliable.py 2>&1 | tee ../translation_output.log
```

**Single Language**:
```bash
cd translation_tools
python translate_admin_reliable.py --language ko 2>&1 | tee ../translation_ko.log
```

### Features

**Quality Validation**:
- ✅ Placeholder preservation (e.g., `{user}`, `{count}`)
- ✅ HTML tag validation
- ✅ Preserve terms enforcement (technical terms, emojis)
- ✅ Contamination detection (Korean in non-Korean languages)
- ✅ Auto-retry on validation failures (max 3 attempts)

**Translation Consolidation Pattern**:
- Reusable translations under `common.*` namespace
- `common.buttons.*` - Standard buttons (cancel, save, delete, etc.)
- `common.labels.*` - Standard labels (email, username, status, etc.)
- `common.messages.*` - Standard messages (loading, error_occurred, success, etc.)
- Page-specific translations under `pages.{page_name}.*`

**Performance**:
- Incremental mode: Only translates new/missing keys
- Parallel processing: Translates multiple strings concurrently
- Progress tracking: Real-time completion percentage
- Error logging: Detailed logs for debugging

### Translation Workflow

1. **Update English source** (`admin/i18n/en/translations.json`):
   - Add new translation keys with English text
   - Use placeholder syntax: `{variable_name}`
   - Group by namespace: `pages.page_name.section.key`

2. **Run incremental translation**:
   ```bash
   cd translation_tools
   python translate_admin_reliable.py --incremental
   ```

3. **Monitor progress**:
   - Watch console output for completion percentage
   - Check for validation warnings/errors
   - Review quality issues flagged by validator

4. **Verify translations**:
   ```bash
   # Check all languages have the new keys
   for lang in es pt de ko fr it ja zh ru ar nl pl tr sv da; do
     echo "$lang: $(jq '.pages.page_name.new_key' admin/i18n/$lang/translations.json)"
   done
   ```

5. **Commit changes**:
   ```bash
   git add admin/i18n/
   git commit -m "feat(i18n): Add translations for new_feature"
   ```

### PHP Integration

**Using translations in PHP**:
```php
<?php echo __('common.buttons.save'); ?>
<?php echo __('pages.dashboard.welcome_message'); ?>
<?php echo __('common.messages.error_occurred'); ?>
```

**JavaScript integration**:
```javascript
const i18n = {
    save: <?php echo json_encode(__('common.buttons.save')); ?>,
    cancel: <?php echo json_encode(__('common.buttons.cancel')); ?>,
    loading: <?php echo json_encode(__('common.messages.loading')); ?>
};

// Use in JavaScript
alert(i18n.save);
document.getElementById('msg').textContent = i18n.loading;
```

### Troubleshooting

**KeyError: 'languages'**:
- Fix: Ensure `translate_config.json` has `languages` key (not `locales`)

**Placeholder not preserved**:
- Symptom: `{user}` becomes `{usuario}` in Spanish
- Fix: Model limitation - manually correct in target language file

**LM Studio connection error**:
- Check: LM Studio is running on `http://localhost:1234`
- Check: Hunyuan-MT-7B model is loaded
- Test: `curl http://localhost:1234/v1/models`

**Contamination warnings**:
- Review: Check flagged translations for incorrect language mixing
- Fix: Manually correct contaminated strings

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

### ✅ DO: Use LM Studio Integration with Retry Loop
- **NEVER use `SKIP_LMSTUDIO=1`** - Always let LM Studio review commits
- **IMPLEMENT RETRY LOOP** - If LM Studio rejects, revise and retry until approved
- Let the commit-msg hook run fully for quality checks
- LM Studio provides valuable commit message improvements

### Commit Workflow with Retry Loop
When committing changes, follow this pattern:

1. **Attempt initial commit** with your best message
2. **Check if commit succeeded**:
   - If LM Studio approves (✅) → Commit succeeds, continue
   - If LM Studio rejects (❌) → Commit fails, go to step 3
3. **Read LM Studio feedback** from the error output
4. **Revise commit message** based on suggestions
5. **Retry with `git commit --amend`** or new commit
6. **Repeat steps 2-5** until LM Studio approves

### Commit Commands
```bash
# Standard commit (with LM Studio review and retry)
git add .
git commit -m "fix(admin): Description of fix"

# If rejected, read feedback and retry with revised message:
git commit --amend -m "fix(admin): Improved description based on feedback"

# Repeat until LM Studio approves (✅ Good commit message)

# DON'T use:
# SKIP_LMSTUDIO=1 git commit  ❌
```

### Example Retry Flow
```bash
# Attempt 1
$ git commit -m "fix stuff"
❌ LM Studio: Message too vague, describe what was fixed

# Attempt 2
$ git commit --amend -m "fix(admin): Fix user profile bug"
❌ LM Studio: Describe what the bug was and how it was fixed

# Attempt 3
$ git commit --amend -m "fix(admin): Fix user profile email update not saving to database"
✅ LM Studio: Good commit message

# Success! Commit created
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
