# Git Hooks Setup

This project uses Git hooks for automated code quality and security checks.

## Installation

Git hooks are located in `.git/hooks/` (not tracked in repository). They must be set up manually on each development machine.

### Automated Setup (Recommended)

Run the setup script:

```bash
# Copy hooks from scripts/git-hooks/ to .git/hooks/
bash scripts/setup-hooks.sh
```

### Manual Setup

1. Copy hook files:
```bash
cp scripts/git-hooks/* .git/hooks/
chmod +x .git/hooks/*
```

2. Verify Python is available:
```bash
# Windows
py --version

# Linux/Mac
python3 --version
```

## Available Hooks

### 1. pre-commit
Runs before commit is finalized. Checks:
- **Protected files**: Warns about sensitive files (.env, users.json, etc.)
- **Sensitive data**: Scans for passwords, API keys, tokens in diff
- **JSON validation**: Validates all .json files
- **PHP syntax**: Checks PHP files for syntax errors
- **Debug statements**: Detects console.log, var_dump, etc.
- **LM Studio scan**: AI-powered security review (optional)

**Skip all checks:**
```bash
git commit --no-verify -m "message"
```

**Skip LM Studio only:**
```bash
SKIP_LMSTUDIO=1 git commit -m "message"
```

### 2. commit-msg
Validates commit message format. Enforces:
- **Conventional Commits**: `<type>(<scope>): <description>`
- **Valid types**: feat, fix, docs, style, refactor, test, chore, security, perf
- **Length limits**: 20-72 characters for first line
- **Issue references**: Checks for issue numbers in fix commits
- **LM Studio review**: AI-powered message quality check (optional)

**Example valid messages:**
```
feat(admin): Add multi-role system for users
fix(csrf): Resolve token validation mismatch (Fixes #42)
security(jwt): Implement automatic key rotation
docs(api): Update user management API documentation
```

### 3. prepare-commit-msg
Automatically adds:
- Branch name to commit message (if feature/fix branch)
- Co-author tag for AI-assisted commits

### 4. post-merge
Runs after `git pull` or `git merge`. Checks:
- Dependency changes (package.json, composer.json)
- Database migrations
- Environment variables (.env.example)

### 5. pre-push
Runs before `git push`. Checks:
- Tests pass (if configured)
- No debug code in commits
- Branch protection rules

## Hook Architecture

### Windows Compatibility

Hooks use external Python scripts for Windows Git Bash compatibility:

```
.git/hooks/
├── pre-commit                  # Bash wrapper (7 lines)
├── pre-commit-checks.py       # Python checks
├── commit-msg                  # Bash wrapper
├── commit-msg-lmstudio.py     # Python LM Studio integration
├── prepare-commit-msg
├── post-merge
└── pre-push
```

**Why Python?**
- Complex bash doesn't work reliably on Windows Git Bash
- Python is cross-platform and handles Unicode properly
- Easier to maintain and debug

**Python command priority:**
1. `py` (Windows Python launcher)
2. `python3` (Linux/Mac standard)
3. `python` (fallback)

### LM Studio Integration

Optional AI-powered code review and commit message validation.

**Requirements:**
- LM Studio running on `localhost:1234`
- Model loaded: `qwen/qwen3-coder-30b` (recommended)

**Features:**
- Security vulnerability detection
- Code quality suggestions
- Commit message clarity check
- Context-aware reviews

**Disable:**
```bash
# Temporarily
SKIP_LMSTUDIO=1 git commit -m "message"

# Permanently (add to ~/.bashrc or ~/.zshrc)
export SKIP_LMSTUDIO=1
```

## Troubleshooting

### Hook Not Running

1. **Check executable permissions:**
```bash
ls -la .git/hooks/
chmod +x .git/hooks/pre-commit
```

2. **Verify Python:**
```bash
py --version          # Windows
python3 --version     # Linux/Mac
```

3. **Test hook manually:**
```bash
.git/hooks/pre-commit
```

### Python Not Found

**Windows:**
- Install Python from python.org
- Or use Microsoft Store: `python3` command

**Linux/Mac:**
```bash
sudo apt install python3    # Ubuntu/Debian
brew install python3        # macOS
```

### Unicode Errors (Windows)

Hooks automatically configure UTF-8 encoding. If you see encoding errors:

```bash
# Set Git console to UTF-8
git config --global core.quotepath false
git config --global i18n.commitEncoding utf-8
```

### Hook Fails on Specific File

If a hook incorrectly flags a file:

```bash
# Skip pre-commit checks (emergency only)
git commit --no-verify -m "message"

# Report false positive as GitHub issue
```

## Configuration

### Protected Files

Edit `.git/hooks/pre-commit-checks.py`:

```python
protected_files = [
    "admin/.env",
    "admin/users.json",
    # Add more files...
]
```

### Sensitive Data Patterns

Edit `.git/hooks/pre-commit-checks.py`:

```python
patterns = [
    r'password.*=.*["\'][^"\']{8,}',
    # Add more patterns...
]
```

### Commit Message Types

Edit `.git/hooks/commit-msg` line 43:

```bash
PATTERN='^(feat|fix|docs|style|refactor|test|chore|security|perf|build|ci|revert)(\(.+\))?: .{10,}'
```

## Best Practices

1. **Don't disable hooks** unless absolutely necessary
2. **Fix issues** instead of skipping checks
3. **Use descriptive commit messages** that pass validation
4. **Keep hooks updated** when pulling changes
5. **Report false positives** as GitHub issues

## Development

### Adding New Checks

1. Edit `.git/hooks/pre-commit-checks.py`
2. Add function: `def check_new_feature():`
3. Add to `checks` list in `main()`
4. Test manually: `.git/hooks/pre-commit`
5. Document in this file

### Testing Hooks

```bash
# Create test change
echo "test" >> README.md
git add README.md

# Test pre-commit
.git/hooks/pre-commit

# Test commit-msg
echo "test: commit message" > .git/COMMIT_EDITMSG
.git/hooks/commit-msg .git/COMMIT_EDITMSG

# Cleanup
git reset README.md
git checkout README.md
```

## Related Documentation

- **Conventional Commits**: https://www.conventionalcommits.org/
- **GitHub Issues**: https://github.com/k33bz/lastwar-server1586/issues
- **LM Studio Setup**: docs/LMSTUDIO-SETUP.md (if exists)
- **Contributing Guide**: docs/CONTRIBUTING.md (if exists)

## Version History

**v2.0.0** (2025-11-02)
- Converted to Python-based hooks for Windows compatibility
- Fixed Unicode encoding issues
- Added UTF-8 console support
- Simplified bash wrappers (7 lines each)

**v1.0.0** (2025-10-31)
- Initial bash-based hooks
- LM Studio integration
- Conventional Commits validation
