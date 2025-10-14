#!/usr/bin/env python3
"""
Comprehensive Repository Cleanup Script

This script:
1. Identifies files containing PII
2. Removes temporary/unnecessary files
3. Updates .gitignore
4. Prepares for git history rewrite
"""
import sys
import io
import os
import shutil
from pathlib import Path

# Fix Unicode output on Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

PROJECT_ROOT = Path(__file__).parent.parent

# Files to delete (temporary, backup, or containing PII)
FILES_TO_DELETE = [
    # Temporary data files
    'data/alliances.json.backup',
    'data/alliances_temp.json',
    'data/alliances_review_20251014.csv',
    'data/alliances-ocr.json',
    'data/alliances-ocr-custom.json',
    'data/alliances-ocr-manual.json',

    # Backup files
    'admin/alliance_edit.php.backup',

    # Temporary scripts (one-time use, contain PII in comments/examples)
    'scripts/add_r4_notice.py',
    'scripts/update_admin_api_version.py',
    'scripts/update_admin_api_version_task7.py',
    'scripts/update_alliance_edit.py',
    'scripts/update_email_masking.py',
    'scripts/update_mailer_version.py',
    'scripts/reset_signatures.py',
    'scripts/export_alliances_for_review.py',
    'scripts/fix_ranks_6_7_2025_10_12.py',
    'scripts/import_corrected_alliances.py',
    'scripts/restore_unicode_r5_names.py',

    # Test files (may contain real credentials in examples)
    'admin/test-smtp.php',
    'admin/test-smtp.py',

    # Temporary OCR files
    'ocr/alliance_cards/temp',

    # Claude settings (local only)
    '.claude/settings.local.json',
    'admin/.claude/settings.local.json',
]

# Files that must be in .gitignore (sensitive data)
GITIGNORE_ENTRIES = [
    '',
    '# Sensitive user data',
    'admin/users.json',
    'admin/token_blacklist.json',
    'admin/.env',
    '',
    '# Local settings',
    '.claude/settings.local.json',
    'admin/.claude/settings.local.json',
    '',
    '# Temporary data files',
    'data/*.backup',
    'data/*_temp.json',
    'data/*_review_*.csv',
    'data/alliances-ocr*.json',
    '',
    '# Test files',
    'admin/test-smtp.php',
    'admin/test-smtp.py',
    '',
    '# Backup files',
    '*.backup',
    '*.bak',
    '*~',
]

# Files that should be removed from git history
HISTORY_FILES_TO_REMOVE = [
    'admin/users.json',
    'admin/.env',
    'admin/token_blacklist.json',
    '.claude/settings.local.json',
    'admin/.claude/settings.local.json',
]

def delete_files():
    """Delete unnecessary and temporary files."""
    print("\n" + "=" * 70)
    print("STEP 1: Deleting Unnecessary Files")
    print("=" * 70)

    deleted_count = 0
    not_found_count = 0

    for file_path in FILES_TO_DELETE:
        full_path = PROJECT_ROOT / file_path

        if full_path.exists():
            try:
                if full_path.is_file():
                    full_path.unlink()
                    print(f"  ✓ Deleted: {file_path}")
                    deleted_count += 1
                elif full_path.is_dir():
                    shutil.rmtree(full_path, ignore_errors=True)
                    print(f"  ✓ Deleted directory: {file_path}")
                    deleted_count += 1
            except Exception as e:
                print(f"  ✗ Error deleting {file_path}: {e}")
        else:
            print(f"  ⊘ Not found: {file_path}")
            not_found_count += 1

    print(f"\nDeleted: {deleted_count} files/directories")
    print(f"Not found: {not_found_count}")

def update_gitignore():
    """Update .gitignore with sensitive file patterns."""
    print("\n" + "=" * 70)
    print("STEP 2: Updating .gitignore")
    print("=" * 70)

    gitignore_path = PROJECT_ROOT / '.gitignore'

    # Read existing .gitignore
    existing_entries = set()
    if gitignore_path.exists():
        with open(gitignore_path, 'r', encoding='utf-8') as f:
            existing_entries = set(line.strip() for line in f)

    # Add new entries
    new_entries = []
    for entry in GITIGNORE_ENTRIES:
        if entry and entry not in existing_entries:
            new_entries.append(entry)

    if new_entries:
        with open(gitignore_path, 'a', encoding='utf-8') as f:
            f.write('\n'.join([''] + new_entries) + '\n')
        print(f"  ✓ Added {len(new_entries)} new patterns to .gitignore")
    else:
        print("  ✓ .gitignore already up to date")

def check_for_pii():
    """Check remaining files for PII."""
    print("\n" + "=" * 70)
    print("STEP 3: Scanning for Remaining PII")
    print("=" * 70)

    pii_patterns = [
        'admin@example.com',
        'r5-user@example.com',
        'r5-user2@example.com',
        'admin@example.com',
        'example.com',
        'example.com',
    ]

    exclude_files = [
        'scripts/cleanup_repository.py',
        'scripts/sanitize_pii.py',
        'SANITIZATION-LOG.md',
        '.git',
    ]

    files_with_pii = {}

    for root, dirs, files in os.walk(PROJECT_ROOT):
        # Skip .git directory
        if '.git' in root:
            continue

        for file in files:
            file_path = Path(root) / file
            rel_path = file_path.relative_to(PROJECT_ROOT)

            # Skip excluded files
            if any(excl in str(rel_path) for excl in exclude_files):
                continue

            # Only check text files
            if file_path.suffix not in ['.py', '.php', '.js', '.md', '.json', '.txt', '.csv', '.yml', '.yaml', '.sh', '.ps1']:
                continue

            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()

                found_patterns = []
                for pattern in pii_patterns:
                    if pattern.lower() in content.lower():
                        found_patterns.append(pattern)

                if found_patterns:
                    files_with_pii[str(rel_path)] = found_patterns

            except Exception:
                pass

    if files_with_pii:
        print("\n⚠️  WARNING: PII found in the following files:\n")
        for file, patterns in sorted(files_with_pii.items()):
            print(f"  {file}")
            for pattern in patterns:
                print(f"    - {pattern}")
        print(f"\nTotal files with PII: {len(files_with_pii)}")
    else:
        print("  ✓ No PII found in tracked files")

def generate_history_cleanup_script():
    """Generate script to clean git history."""
    print("\n" + "=" * 70)
    print("STEP 4: Git History Cleanup")
    print("=" * 70)

    script_path = PROJECT_ROOT / 'scripts' / 'rewrite_git_history.sh'

    # Generate BFG Repo-Cleaner commands
    bfg_commands = []
    for file in HISTORY_FILES_TO_REMOVE:
        bfg_commands.append(f"  --delete-files '{Path(file).name}'")

    script_content = f"""#!/bin/bash
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
    bfg \\
{chr(10).join(bfg_commands)} \\
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
"""

    with open(script_path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(script_content)

    # Make executable on Unix
    if os.name != 'nt':
        os.chmod(script_path, 0o755)

    print(f"  ✓ Created history cleanup script: {script_path.name}")
    print("\n  To clean git history:")
    print("    1. Make a backup of your repository")
    print("    2. Run: bash scripts/rewrite_git_history.sh")
    print("    3. Force push: git push origin --force --all")

def main():
    """Main cleanup function."""
    print("=" * 70)
    print("COMPREHENSIVE REPOSITORY CLEANUP")
    print("=" * 70)
    print("\nThis script will:")
    print("  1. Delete temporary and unnecessary files")
    print("  2. Update .gitignore for sensitive files")
    print("  3. Scan for remaining PII")
    print("  4. Generate git history cleanup script")
    print()

    # Execute cleanup steps
    delete_files()
    update_gitignore()
    check_for_pii()
    generate_history_cleanup_script()

    print("\n" + "=" * 70)
    print("CLEANUP COMPLETE")
    print("=" * 70)
    print("\nNext steps:")
    print("  1. Review deleted files (if needed)")
    print("  2. Commit changes: git add -A && git commit -m 'Clean up repository'")
    print("  3. Clean git history: bash scripts/rewrite_git_history.sh")
    print("  4. Force push: git push origin --force --all")
    print("\n⚠️  WARNING: Force push will rewrite history!")
    print("   All collaborators must re-clone the repository.")

if __name__ == "__main__":
    main()
