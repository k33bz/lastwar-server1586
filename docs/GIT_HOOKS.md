# Git Hooks Documentation

Automated quality gates and AI-powered development assistance using LM Studio.

**Version:** 1.0.0
**Date:** 2025-10-31
**AI Model:** qwen/qwen3-coder-30b (LM Studio)

## Overview

This repository uses git hooks to enforce code quality, security standards, and provide AI-powered development assistance. All hooks integrate with LM Studio for intelligent code review and suggestions.

## Active Hooks

### 1. **pre-commit** - Quality & Security Gates
**Runs:** Before commit is finalized
**Purpose:** Catch issues before they enter version control

**Checks:**
- ✅ Protected files warning (`.env`, `users.json`, etc.)
- ✅ Sensitive data detection (passwords, API keys, tokens)
- ✅ JSON syntax validation
- ✅ PHP syntax validation
- ✅ TODO/FIXME detection (CLAUDE.md compliance)
- ✅ Debug statement detection (`var_dump`, `console.log`, etc.)
- 🤖 **LM Studio security scan** (optional)

**LM Studio Integration:**
- Analyzes staged changes for security issues
- Detects: XSS vulnerabilities, SQL injection risks, auth issues
- Provides: Concise 2-3 sentence security assessment
- Blocks commit if critical issues found

**Usage:**
```bash
# Normal commit (includes LM Studio scan)
git commit -m "feat(admin): Add user management"

# Skip LM Studio scan (faster)
SKIP_LMSTUDIO=1 git commit -m "docs: Update README"

# Emergency: Skip all pre-commit checks
git commit --no-verify -m "hotfix: Critical production fix"
```

**Example Output:**
```
→ Running pre-commit checks...

→ Checking for protected files...
✓ No protected files in commit

→ Scanning for sensitive data...
✓ No sensitive data detected

→ Validating JSON files...
✓ All JSON files valid

→ Checking PHP syntax...
✓ All PHP files have valid syntax

→ Checking for TODO comments...

→ Checking for debug statements...
✓ No debug statements found

→ Running LM Studio security scan...
   (Set SKIP_LMSTUDIO=1 to skip)
✓ LM Studio: ✅ No critical issues detected

✅ All pre-commit checks passed
```

---

### 2. **commit-msg** - Message Quality Validation
**Runs:** After commit message is entered
**Purpose:** Enforce Conventional Commits format and message quality

**Checks:**
- ✅ Conventional Commits format validation
- ✅ Message length checks (20-72 chars recommended)
- ✅ Issue reference suggestions for bug fixes
- 🤖 **LM Studio message quality review** (optional)

**LM Studio Integration:**
- Analyzes commit message clarity and accuracy
- Compares message to actual changes (using diff summary)
- Suggests improvements if message is unclear
- Blocks commit if message is poorly written

**Format:**
```
<type>(<scope>): <description>

Types: feat, fix, docs, security, refactor, test, chore, perf, build, ci
Scope: admin, auth, api, data, docs, deployment
```

**Examples:**
```bash
✅ Good:
feat(admin): Add multi-role system for users
fix(csrf): Resolve token validation mismatch
security(jwt): Implement automatic key rotation
docs(api): Update user management API documentation

❌ Bad:
updated stuff
fixed bug
WIP
```

**Usage:**
```bash
# Normal commit (includes LM Studio review)
git commit -m "feat(admin): Add user management"

# Skip LM Studio review
SKIP_LMSTUDIO=1 git commit -m "chore: Update dependencies"
```

**Example Output:**
```
→ Validating commit message...
✓ Conventional Commits format
✓ Issue reference included
✓ Good message length (45 chars)

→ Running LM Studio commit message review...
   (Set SKIP_LMSTUDIO=1 to skip)
✓ LM Studio: ✅ Good commit message

✅ Commit message validated
```

---

### 3. **prepare-commit-msg** - AI-Powered Message Generation
**Runs:** Before commit message editor opens
**Purpose:** Auto-generate commit message suggestions

**Features:**
- 🤖 **LM Studio analyzes staged changes** and suggests commit message
- ✅ Adds branch context (issue numbers from branch names)
- ✅ Provides commit message template
- ✅ Pre-fills message in editor

**LM Studio Integration:**
- Analyzes diff and file changes
- Generates Conventional Commits formatted message
- Considers: file types, change patterns, scope
- Pre-fills suggestion in commit message editor

**Usage:**
```bash
# Normal commit (LM Studio generates suggestion)
git commit
# Editor opens with suggested message already filled in

# Skip LM Studio suggestion
SKIP_LMSTUDIO=1 git commit

# Use -m flag (bypasses this hook)
git commit -m "feat(admin): Add feature"
```

**Example:**
```
# LM Studio suggests based on your changes:
feat(admin): Add multi-role user permission system

# Conventional Commits Format:
# <type>(<scope>): <description>
#
# Types: feat, fix, docs, security, refactor, test, chore
# ...
```

---

### 4. **post-commit** - Changelog Generation
**Runs:** After commit is created
**Purpose:** Auto-generate changelog entries

**Features:**
- 🤖 **LM Studio generates changelog entry** from commit
- ✅ Updates `docs/CHANGELOG.md` automatically
- ✅ Categorizes changes (Added, Changed, Fixed, Security, etc.)
- ✅ Non-blocking (doesn't prevent commit if fails)

**Usage:**
```bash
# Normal commit (auto-generates changelog)
git commit -m "feat(admin): Add user management"

# Skip changelog generation
SKIP_OLLAMA=1 git commit -m "docs: Update README"
```

**Note:** This hook uses the existing `scripts/ollama-doc-generator.py`

---

### 5. **post-merge** - Environment Sync
**Runs:** After `git pull` or merge
**Purpose:** Keep environment in sync with code changes

**Checks:**
- ✅ Auto-updates Composer dependencies if `composer.json` changed
- ✅ Detects version mismatches (migration required)
- ✅ Checks for schema changes (`users.json`, `alliances.json`)
- ✅ Reminds about `.env` updates if `.env.example` changed
- ✅ Shows critical file changes
- 🤖 **LM Studio migration risk assessment** (optional)

**LM Studio Integration:**
- Analyzes database schema changes
- Assesses migration complexity (Low/Medium/High)
- Identifies potential data loss risks
- Evaluates rollback difficulty

**Usage:**
```bash
# Normal pull (includes all checks)
git pull origin mainline

# Example output:
🔄 Post-merge cleanup...

→ version.json changed
⚠️  VERSION MISMATCH DETECTED
   Code version:    3.5.0
   Installed:       3.4.0

   🔧 Run migration:
      php admin/migrate.php

→ Running LM Studio migration risk assessment...
📊 Migration Risk Assessment:
   • Complexity: Medium
   • Risk: Data structure change (role → roles array)
   • Backup recommended before migration
   • Rollback: Moderate difficulty

✅ Post-merge checks complete

📌 REMINDER: Run migration before using the application
   php admin/migrate.php
```

---

### 6. **pre-push** - Final Quality Gate
**Runs:** Before push to remote
**Purpose:** Ensure code quality before deployment

**Checks:**
- ✅ Critical files exist
- ✅ PHP syntax validation (all files)
- ✅ PHP unit tests (requires 75% pass rate)
- ✅ JSON validation
- ✅ CSV validation

**Usage:**
```bash
# Normal push (runs all tests)
git push origin mainline

# Skip pre-push checks (not recommended)
git push --no-verify origin mainline
```

**Example Output:**
```
==========================================
Running pre-push tests...
==========================================

→ Checking critical files exist...
✓ All critical files exist

→ Checking PHP syntax...
✓ All PHP files have valid syntax

→ Running PHP unit tests...
✓ PHP tests passed (86.21%)
⚠ 4 test(s) failed (may be environment-specific)

→ Validating JSON files...
✓ All JSON files are valid

==========================================
✓ All pre-push checks passed!
==========================================
```

---

## Configuration

### Skip LM Studio Checks

All LM Studio integrations can be skipped:

```bash
# Skip for single commit
SKIP_LMSTUDIO=1 git commit -m "message"

# Skip globally (not recommended)
export SKIP_LMSTUDIO=1
```

### LM Studio Requirements

**Model:** `qwen/qwen3-coder-30b`
**Server:** LM Studio running on `http://localhost:1234`
**Features used:**
- Code security analysis
- Commit message quality review
- Message generation
- Migration risk assessment

**Check if running:**
```bash
curl http://localhost:1234/v1/models
```

---

## Hook Workflow

### Complete Commit Flow
```
1. git add files
2. git commit
   ├─ prepare-commit-msg runs
   │  └─ 🤖 LM Studio generates suggested message
   ├─ (you edit message in editor)
   ├─ commit-msg runs
   │  ├─ Validates format
   │  └─ 🤖 LM Studio reviews message quality
   ├─ pre-commit runs
   │  ├─ Validates syntax
   │  ├─ Checks for secrets
   │  └─ 🤖 LM Studio security scan
   ├─ Commit created ✓
   └─ post-commit runs
      └─ 🤖 LM Studio generates changelog
```

### Complete Push Flow
```
1. git push
   ├─ pre-push runs
   │  ├─ Critical file checks
   │  ├─ PHP syntax validation
   │  ├─ PHP unit tests
   │  └─ JSON validation
   └─ Push to remote ✓
```

### Complete Pull Flow
```
1. git pull
   ├─ Merge occurs
   └─ post-merge runs
      ├─ Composer dependency check
      ├─ Version mismatch detection
      ├─ Schema change detection
      ├─ .env.example change check
      └─ 🤖 LM Studio migration risk assessment
```

---

## Best Practices

### 1. **Use Conventional Commits**
Always follow the format:
```
<type>(<scope>): <description>

feat(admin): Add multi-role system
fix(auth): Resolve JWT token expiry
docs(api): Update endpoint documentation
```

### 2. **Keep LM Studio Running**
For best experience, keep LM Studio running during development:
```bash
# Start LM Studio
# Load model: qwen/qwen3-coder-30b
# Start server on localhost:1234
```

### 3. **Review AI Suggestions**
LM Studio provides suggestions, but always review:
- Commit messages may need tweaking
- Security warnings should be investigated
- Migration assessments are estimates

### 4. **Skip When Needed**
Use `SKIP_LMSTUDIO=1` for:
- Quick documentation fixes
- Minor typo corrections
- Emergency hotfixes
- When LM Studio is not running

### 5. **Don't Skip Safety Checks**
Never use `--no-verify` unless absolutely necessary:
- Syntax errors will cause deployment failures
- Secrets in commits create security risks
- Invalid JSON breaks the application

---

## Troubleshooting

### LM Studio Timeout
```
⚠️  LM Studio timeout (continuing anyway)
```
**Solution:** Model is busy. Wait or skip with `SKIP_LMSTUDIO=1`

### LM Studio Not Running
```
⚠️  LM Studio not running, skipping AI review
```
**Solution:** Start LM Studio and load `qwen/qwen3-coder-30b`

### Hook Permission Error
```
Permission denied: .git/hooks/pre-commit
```
**Solution:**
```bash
chmod +x .git/hooks/*
```

### Pre-commit Blocks Legitimate Commit
**Emergency bypass:**
```bash
git commit --no-verify -m "message"
```
**Note:** Only use in emergencies. Fix issues instead.

---

## Performance

**Typical timings:**

| Hook | Without LM Studio | With LM Studio |
|------|------------------|----------------|
| pre-commit | ~2 seconds | ~5-10 seconds |
| commit-msg | ~1 second | ~3-8 seconds |
| prepare-commit-msg | ~1 second | ~5-10 seconds |
| post-commit | ~1 second | ~20-30 seconds |
| post-merge | ~2 seconds | ~5-10 seconds (if schema changes) |
| pre-push | ~10-20 seconds | N/A |

**LM Studio factors:**
- Model: qwen3-coder-30b (30B parameters)
- Hardware: CPU/GPU performance
- Context size: Diff size affects processing time
- Concurrent requests: Multiple hooks running simultaneously

---

## Security Considerations

### What Hooks Check For

1. **Sensitive Data Patterns:**
   - Passwords
   - API keys
   - Private keys
   - Tokens (>32 chars)
   - AWS credentials
   - Stripe keys

2. **Security Vulnerabilities:**
   - XSS risks
   - SQL injection patterns
   - Auth bypass attempts
   - Insecure data handling

3. **Protected Files:**
   - `admin/.env`
   - `admin/users.json`
   - `admin/jwt_secret.txt`
   - `admin/audit_log.json`

### False Positives

If hooks block legitimate code:
```bash
# Option 1: Skip the check
SKIP_LMSTUDIO=1 git commit -m "message"

# Option 2: Emergency bypass (not recommended)
git commit --no-verify -m "message"

# Option 3: Refactor code to pass checks (best)
# Remove debug statements, move secrets to .env
```

---

## Maintenance

### Updating Hooks

Hooks are in `.git/hooks/` directory:
```
.git/hooks/
├── pre-commit
├── commit-msg
├── prepare-commit-msg
├── post-commit
├── post-merge
└── pre-push
```

**To update:**
1. Edit hook file
2. Ensure executable: `chmod +x .git/hooks/hook-name`
3. Test with sample commit

### Sharing Hooks

Git hooks are not version-controlled by default. To share:

**Option 1:** Copy from this documentation
**Option 2:** Use templates (Git 2.9+):
```bash
git config core.hooksPath .githooks
```

### Disabling Hooks

**Temporary (single command):**
```bash
git commit --no-verify
SKIP_LMSTUDIO=1 git commit
```

**Permanent (not recommended):**
```bash
# Remove hook
rm .git/hooks/hook-name

# Or make non-executable
chmod -x .git/hooks/hook-name
```

---

## Related Documentation

- [CLAUDE.md](../CLAUDE.md) - Project conventions
- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment process
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [scripts/repo-review.py](../scripts/repo-review.py) - LM Studio repository review tool

---

## Support

**Questions or Issues:**
- GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
- Add label: `automation` for hook-related issues

**LM Studio:**
- Website: https://lmstudio.ai/
- Model: qwen/qwen3-coder-30b
- API: OpenAI-compatible endpoint

---

**Last Updated:** 2025-10-31
**Hook Version:** 1.0.0
**Integrated AI:** LM Studio (qwen3-coder-30b)
