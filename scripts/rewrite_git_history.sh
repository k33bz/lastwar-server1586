#!/bin/bash
# Git History Rewrite Script
#
# This script removes sensitive files from git history using BFG Repo-Cleaner.
#
# WARNING: This rewrites git history! Make a backup first!
#
# Prerequisites:
# 1. Install BFG Repo-Cleaner: https://rtyley.github.io/bfg-repo-cleaner/
#    - Download bfg.jar
#    - Or use: brew install bfg (Mac) / choco install bfg-repo-cleaner (Windows)
# 2. Make a backup of your repository
# 3. Ensure all changes are committed

set -e

echo "========================================================================"
echo "Git History Cleanup - DANGEROUS OPERATION"
echo "========================================================================"
echo ""
echo "This will:"
echo "  - Remove sensitive files from ALL git history"
echo "  - Rewrite commit hashes (breaks forks/clones)"
echo "  - Require force push to remote"
echo ""
read -p "Have you made a backup? (yes/no): " backup
if [ "$backup" != "yes" ]; then
    echo "Please make a backup first!"
    exit 1
fi

echo ""
echo "Step 1: Remove sensitive files from history..."
echo ""

# Method 1: Using BFG Repo-Cleaner (recommended, faster)
if command -v bfg &> /dev/null; then
    echo "Using BFG Repo-Cleaner..."
    bfg \
  --delete-files 'users.json'
  --delete-files '.env'
  --delete-files 'token_blacklist.json'
  --delete-files 'settings.local.json'
  --delete-files 'settings.local.json' \
      .

    echo "Cleaning up repository..."
    git reflog expire --expire=now --all
    git gc --prune=now --aggressive

elif command -v git-filter-repo &> /dev/null; then
    echo "Using git-filter-repo..."
    git filter-repo --path-glob 'admin/users.json' --invert-paths --force
    git filter-repo --path-glob 'admin/.env' --invert-paths --force
    git filter-repo --path-glob 'admin/token_blacklist.json' --invert-paths --force

else
    echo "ERROR: Neither BFG nor git-filter-repo found."
    echo ""
    echo "Install one of:"
    echo "  - BFG: https://rtyley.github.io/bfg-repo-cleaner/"
    echo "  - git-filter-repo: pip install git-filter-repo"
    exit 1
fi

echo ""
echo "Step 2: Replace sensitive strings in history..."
echo ""

# Replace sensitive strings (if BFG is available)
if command -v bfg &> /dev/null; then
    cat > passwords.txt <<EOF
admin@example.com
r5-user@example.com
r5-user2@example.com
admin@example.com
example.com
example.com
EOF

    bfg --replace-text passwords.txt .
    rm passwords.txt

    git reflog expire --expire=now --all
    git gc --prune=now --aggressive
fi

echo ""
echo "========================================================================"
echo "Cleanup Complete!"
echo "========================================================================"
echo ""
echo "Next steps:"
echo "  1. Review changes: git log --oneline"
echo "  2. Force push to remote: git push origin --force --all"
echo "  3. Force push tags: git push origin --force --tags"
echo "  4. Notify collaborators to re-clone the repository"
echo ""
echo "IMPORTANT: All collaborators must delete their local clones and re-clone!"
echo ""
