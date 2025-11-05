# Claude Code Workflow Guidelines

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

## Notes
- Use TodoWrite tool for task tracking during active work
- Use GitHub issues for permanent bug/feature tracking
- Keep this file updated with workflow preferences
- LM Studio is running on localhost:1234 - always use it!
