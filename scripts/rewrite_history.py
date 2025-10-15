#!/usr/bin/env python3
"""
Git History Rewrite Script

This script removes sensitive files and replaces PII strings from ALL git history.
Uses git-filter-repo for safe and efficient history rewriting.

WARNING: This rewrites git history! All commit hashes will change!
"""
import subprocess
import sys
import os
import io
from pathlib import Path

# Fix Unicode output on Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

PROJECT_ROOT = Path(__file__).parent.parent

# Files to completely remove from history
FILES_TO_REMOVE = [
    b'admin/users.json',
    b'admin/.env',
    b'admin/token_blacklist.json',
    b'.claude/settings.local.json',
    b'admin/.claude/settings.local.json',
]

# String replacements (old -> new)
STRING_REPLACEMENTS = {
    # Email addresses
    b'matthewkastro@gmail.com': b'admin@example.com',
    b'orianamayy@gmail.com': b'r5-user@example.com',
    b'ejtollridge@gmail.com': b'r5-user2@example.com',
    b'admin@k33.bz': b'admin@example.com',
    b'noreply@lastwar1586.online': b'noreply@example.com',
    b'mailer@lastwar1586.online': b'mailer@example.com',
    b'ftpuploader@lastwar1586.online': b'ftpuser@example.com',

    # Domains
    b'lastwar1586.online': b'example.com',
    b'www.lastwar1586.online': b'www.example.com',
    b'k33bz.com': b'example.com',
    b'k33.bz': b'example.com',
    b'ftp.k33bz.com': b'ftp.example.com',

    # GitHub URLs
    b'github.com/k33bz/lastwar-server1586': b'github.com/username/your-repo',
    b'https://github.com/k33bz/lastwar-server1586': b'https://github.com/username/your-repo',
}

def run_command(cmd, cwd=None):
    """Run a shell command and return output."""
    try:
        result = subprocess.run(
            cmd,
            cwd=cwd or PROJECT_ROOT,
            capture_output=True,
            text=True,
            check=True
        )
        return result.stdout
    except subprocess.CalledProcessError as e:
        print(f"Error running command: {' '.join(cmd)}")
        print(f"Error: {e.stderr}")
        return None

def check_git_filter_repo():
    """Check if git-filter-repo is installed."""
    result = subprocess.run(['git', 'filter-repo', '--version'], capture_output=True)
    return result.returncode == 0

def main():
    print("=" * 70)
    print("GIT HISTORY REWRITE - DANGEROUS OPERATION")
    print("=" * 70)
    print()
    print("This will:")
    print("  - Remove sensitive files from ALL git history")
    print("  - Replace PII strings in ALL commits")
    print("  - Rewrite ALL commit hashes")
    print("  - Make the repository incompatible with existing clones")
    print()

    # Check if git-filter-repo is available
    if not check_git_filter_repo():
        print("ERROR: git-filter-repo not found!")
        print()
        print("Install with: pip install git-filter-repo")
        sys.exit(1)

    # Change to project root
    os.chdir(PROJECT_ROOT)

    print("Step 1: Removing sensitive files from history...")
    print()

    # Build path-based filter arguments
    path_args = []
    for file_path in FILES_TO_REMOVE:
        path_args.extend(['--path', file_path.decode()])

    # Remove sensitive files
    cmd = ['git', 'filter-repo', '--invert-paths', '--force'] + path_args

    print(f"Running: {' '.join(cmd)}")
    result = run_command(cmd)

    if result is None:
        print("ERROR: Failed to remove files from history")
        sys.exit(1)

    print("✓ Sensitive files removed from history")
    print()

    print("Step 2: Replacing PII strings in history...")
    print()

    # Create expressions file for string replacements
    expressions_file = PROJECT_ROOT / 'scripts' / 'filter_expressions.txt'

    with open(expressions_file, 'w', encoding='utf-8') as f:
        for old, new in STRING_REPLACEMENTS.items():
            # Format: literal:old_string==>new_string
            old_str = old.decode('utf-8', errors='ignore')
            new_str = new.decode('utf-8', errors='ignore')
            f.write(f"literal:{old_str}==>{new_str}\n")

    # Apply string replacements
    cmd = ['git', 'filter-repo', '--replace-text', str(expressions_file), '--force']

    print(f"Running: {' '.join(cmd)}")
    result = run_command(cmd)

    if result is None:
        print("ERROR: Failed to replace strings")
        sys.exit(1)

    # Clean up expressions file
    expressions_file.unlink()

    print("✓ PII strings replaced in history")
    print()

    print("Step 3: Cleaning up repository...")
    print()

    # Clean up refs and gc
    run_command(['git', 'reflog', 'expire', '--expire=now', '--all'])
    run_command(['git', 'gc', '--prune=now', '--aggressive'])

    print("✓ Repository cleaned")
    print()

    print("=" * 70)
    print("HISTORY REWRITE COMPLETE!")
    print("=" * 70)
    print()
    print("Summary:")
    print(f"  - Removed {len(FILES_TO_REMOVE)} sensitive files from history")
    print(f"  - Replaced {len(STRING_REPLACEMENTS)} PII patterns")
    print("  - All commit hashes have changed")
    print()
    print("Next steps:")
    print("  1. Verify changes: git log --oneline")
    print("  2. Check for PII: git grep -i 'lastwar1586'")
    print("  3. Force push: git push origin --force --all")
    print("  4. Force push tags: git push origin --force --tags")
    print()
    print("WARNING: All collaborators must delete and re-clone the repository!")
    print()

if __name__ == "__main__":
    main()
