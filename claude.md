# Claude Code Workflow Guidelines

## Project Status

**HeroUI v3 Migration: ✅ COMPLETE**
- Public site migrated to React with HeroUI v3
- Old site backed up to `old-site-backup/`
- Production build deployed and tested
- See `DEPLOYMENT.md` for details

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
