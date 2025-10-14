#!/usr/bin/env python3
"""
Sanitize PII and real credentials from repository files

Replaces real email addresses, domains, and credentials with example/dummy values
to prepare the repository for public sharing.
"""
import json
import re
import sys
import io
from pathlib import Path

# Fix Unicode output on Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# Project root
PROJECT_ROOT = Path(__file__).parent.parent

# Define replacements
REPLACEMENTS = {
    # Email addresses
    r'admin@k33\.bz': 'admin@example.com',
    r'matthewkastro@gmail\.com': 'admin@example.com',
    r'matthewkastro\+r4@gmail\.com': 'user@example.com',
    r'orianamayy@gmail\.com': 'r5-user@example.com',
    r'ejtollridge@gmail\.com': 'r5-user2@example.com',
    r'noreply@lastwar1586\.online': 'noreply@example.com',
    r'mailer@lastwar1586\.online': 'mailer@example.com',
    r'ftpuploader@lastwar1586\.online': 'ftpuser@example.com',
    r'dmarc-reports@lastwar1586\.online': 'dmarc-reports@example.com',

    # Domains
    r'lastwar1586\.online': 'example.com',
    r'www\.lastwar1586\.online': 'www.example.com',
    r'k33bz\.com': 'example.com',
    r'k33\.bz': 'example.com',
    r'ftp\.k33bz\.com': 'ftp.example.com',
    r'ftp\.lastwar1586\.online': 'ftp.example.com',

    # FTP/SMTP hostnames
    r'smtp\.yourdomain\.com': 'smtp.example.com',

    # Windows paths (use raw strings for replacements too)
    r'C:\\\\Users\\\\k33bz\\\\OneDrive\\\\git\\\\Server1586': r'C:\\path\\to\\project',
    r'C:/Users/k33bz/OneDrive/git/Server1586': 'C:/path/to/project',
    r'C:\\Users\\k33bz\\OneDrive\\git\\Server1586': r'C:\\path\\to\\project',
    r'C:\\Users\\k33bz\\OneDrive\\git\\lastwar-font-extractor': r'C:\\path\\to\\fonts',
    r'/path/to/admin': '/path/to/project/admin',

    # GitHub URLs (keep generic pattern for examples)
    r'github\.com/k33bz/lastwar-server1586': 'github.com/username/your-repo',
    r'https://github\.com/k33bz/lastwar-server1586': 'https://github.com/username/your-repo',

    # Credential names
    r'ftp_lastwar1586\.online': 'ftp_example.com',

    # IP addresses
    r'68\.65\.120\.147': '192.0.2.1',

    # JWT tokens (example tokens only, not real production tokens)
    r'9de40ce3666cd44316bfa7b04b071b1b': 'example-jti-token-12345',
}

def sanitize_file(file_path):
    """Sanitize a single file by applying all replacements."""
    print(f"Sanitizing: {file_path.relative_to(PROJECT_ROOT)}")

    try:
        # Read file
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        original_content = content

        # Apply replacements
        for pattern, replacement in REPLACEMENTS.items():
            content = re.sub(pattern, replacement, content)

        # Write back if changed
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"  ✓ Updated")
            return True
        else:
            print(f"  ✓ No changes needed")
            return False

    except Exception as e:
        print(f"  ✗ Error: {e}")
        return False

def sanitize_users_json():
    """Special handling for users.json - create example version."""
    users_file = PROJECT_ROOT / 'admin' / 'users.json'
    example_file = PROJECT_ROOT / 'admin' / 'users.json.example'

    print(f"\nSanitizing users.json...")

    # Create sanitized example version
    example_data = {
        "users": [
            {
                "email": "admin@example.com",
                "alliances": ["*"],
                "role": "admin"
            },
            {
                "email": "r5-user@example.com",
                "alliances": ["UvvU"],
                "role": "r5"
            },
            {
                "email": "r4-user@example.com",
                "alliances": ["UvvU", "ORCE"],
                "role": "r4"
            }
        ]
    }

    # Write example file
    with open(example_file, 'w', encoding='utf-8') as f:
        json.dump(example_data, f, indent=2)

    print(f"  ✓ Created {example_file.name}")

    # Note: users.json should be in .gitignore, but sanitize it anyway for safety
    if users_file.exists():
        with open(users_file, 'r', encoding='utf-8') as f:
            users_data = json.load(f)

        # Sanitize email addresses in actual users.json
        for user in users_data.get('users', []):
            if 'email' in user:
                for pattern, replacement in REPLACEMENTS.items():
                    user['email'] = re.sub(pattern, replacement, user['email'])
            # Remove active_sessions if present
            if 'active_sessions' in user:
                user['active_sessions'] = []

        with open(users_file, 'w', encoding='utf-8') as f:
            json.dump(users_data, f, indent=4)

        print(f"  ✓ Sanitized {users_file.name}")

def main():
    """Main sanitization function."""
    print("=" * 70)
    print("Sanitizing PII and Credentials from Repository")
    print("=" * 70)

    # Files to sanitize
    files_to_sanitize = [
        # Documentation
        PROJECT_ROOT / 'README.md',
        PROJECT_ROOT / 'CICD-SETUP.md',
        PROJECT_ROOT / 'DEPLOYMENT-HISTORY.md',
        PROJECT_ROOT / 'DEPLOYMENT-STATUS.md',
        PROJECT_ROOT / 'DEPLOYMENT_NOTES.md',
        PROJECT_ROOT / 'GITHUB-SETUP.md',

        # Admin files
        PROJECT_ROOT / 'admin' / 'README.md',
        PROJECT_ROOT / 'admin' / 'DKIM-SETUP.md',
        PROJECT_ROOT / 'admin' / '.env.example',
        PROJECT_ROOT / 'admin' / 'guide.md',
        PROJECT_ROOT / 'admin' / 'mailer.php',

        # GitHub workflows
        PROJECT_ROOT / '.github' / 'workflows' / 'deploy.yml',
        PROJECT_ROOT / '.github' / 'SECRETS.md',

        # Scripts
        PROJECT_ROOT / 'scripts' / 'deploy-ftp.py',
        PROJECT_ROOT / 'scripts' / 'deploy-ftp-ci.py',
        PROJECT_ROOT / 'scripts' / 'test-production.py',
        PROJECT_ROOT / 'scripts' / 'DEPLOY-README.md',
        PROJECT_ROOT / 'scripts' / 'SCREENSHOT-PROCESSING-README.md',
        PROJECT_ROOT / 'scripts' / 'train-tesseract-lastwar.py',
        PROJECT_ROOT / 'admin' / 'test-smtp.py',
        PROJECT_ROOT / 'admin' / 'test-smtp.php',
        PROJECT_ROOT / 'admin' / 'config.php',
        PROJECT_ROOT / 'ocr' / 'admin' / 'setup-admin.ps1',
        PROJECT_ROOT / 'ocr' / 'TRAINING_SETUP.md',
        PROJECT_ROOT / 'ocr' / 'generate-training-data.py',
        PROJECT_ROOT / 'images' / 'HOW-TO-ADD-DISCORD-LOGO.md',
    ]

    updated_count = 0

    # Sanitize each file
    for file_path in files_to_sanitize:
        if file_path.exists():
            if sanitize_file(file_path):
                updated_count += 1
        else:
            print(f"[WARNING] File not found: {file_path.relative_to(PROJECT_ROOT)}")

    # Special handling for users.json
    sanitize_users_json()

    print("\n" + "=" * 70)
    print(f"Sanitization Complete")
    print(f"  Updated: {updated_count} files")
    print("=" * 70)
    print("\n[NEXT STEPS]")
    print("1. Review the sanitized files to ensure no PII remains")
    print("2. Verify .gitignore excludes:")
    print("   - admin/users.json (actual data)")
    print("   - admin/.env (actual credentials)")
    print("   - admin/token_blacklist.json")
    print("3. Commit the sanitized files")
    print("4. Double-check GitHub repository for any exposed secrets")

if __name__ == "__main__":
    main()
