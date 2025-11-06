#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Git Pre-Commit Checks
All checks consolidated in Python for Windows compatibility
"""
import sys
import subprocess
import json
import re
import os
import io
from pathlib import Path

# Fix Windows console encoding for Unicode
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

# Colors for output
class Colors:
    RED = '\033[0;31m'
    GREEN = '\033[0;32m'
    YELLOW = '\033[1;33m'
    BLUE = '\033[0;34m'
    NC = '\033[0m'

def print_colored(color, message):
    print(f"{color}{message}{Colors.NC}")

def print_section(message):
    print()
    print_colored(Colors.BLUE, message)

def get_staged_files():
    """Get list of staged files"""
    result = subprocess.run(['git', 'diff', '--cached', '--name-only'],
                          capture_output=True, text=True)
    return result.stdout.strip().split('\n') if result.stdout.strip() else []

def get_staged_diff():
    """Get full diff of staged changes"""
    result = subprocess.run(['git', 'diff', '--cached'],
                          capture_output=True, text=True)
    return result.stdout

def check_protected_files():
    """Check for protected files"""
    print_section("Checking for protected files...")

    protected_files = [
        "admin/.env",
        "admin/users.json",
        "admin/jwt_secret.txt",
        "admin/audit_log.json",
        "admin/.installed_version"
    ]

    staged_files = get_staged_files()

    for protected in protected_files:
        if protected in staged_files:
            print_colored(Colors.YELLOW, f"WARNING: You are committing protected file: {protected}")
            response = input("Are you sure? [yes/no]: ")
            if response.lower() != 'yes':
                print_colored(Colors.RED, "Commit aborted")
                return False

    print_colored(Colors.GREEN, "OK - No protected files in commit")
    return True

def check_sensitive_data():
    """Scan for sensitive data patterns"""
    print_section("Scanning for sensitive data...")

    patterns = [
        # Updated patterns to stop at newlines and require closing quotes
        # This prevents false positives from matching across multiple lines
        r'password.*=.*["\'][^"\'\n]{8,}["\']',
        r'secret.*=.*["\'][^"\'\n]{8,}["\']',
        r'api_key.*=.*["\'][^"\'\n]{8,}["\']',
        r'private_key.*=.*["\'][^"\'\n]{8,}["\']',
        r'token.*=.*["\'][^"\'\n]{32,}["\']',
        # Additional pattern to catch PHP template variables (should NOT match)
        # This excludes patterns like: const TOKEN = '<?= getToken() ?>';
        # By checking for PHP tags in the value
    ]

    # Whitelist patterns that should NOT be flagged as secrets
    whitelist_patterns = [
        r'<\?=.*\?>',  # PHP template tags like <?= getCsrfToken() ?>
        r'process\.env\.',  # Environment variable access
        r'config\.',  # Config object access
    ]

    diff = get_staged_diff()

    # Handle case where diff is None
    if diff is None:
        print_colored(Colors.GREEN, "OK - No diff to scan")
        return True

    found_count = 0

    for pattern in patterns:
        matches = re.finditer(pattern, diff, re.IGNORECASE)
        for match in matches:
            matched_text = match.group()

            # Check if match is whitelisted
            is_whitelisted = False
            for whitelist in whitelist_patterns:
                if re.search(whitelist, matched_text):
                    is_whitelisted = True
                    break

            if not is_whitelisted:
                print_colored(Colors.YELLOW, f"WARNING: Potential secret detected: {pattern}")
                print_colored(Colors.YELLOW, f"  Matched text: {matched_text[:80]}...")
                found_count += 1

    if found_count > 0:
        print_colored(Colors.RED, f"Found {found_count} potential secrets in diff")
        return False

    print_colored(Colors.GREEN, "OK - No sensitive data detected")
    return True

def check_json_files():
    """Validate JSON files"""
    print_section("Validating JSON files...")

    staged_files = get_staged_files()
    json_files = [f for f in staged_files if f.endswith('.json')]

    errors = 0
    for json_file in json_files:
        if os.path.exists(json_file):
            try:
                with open(json_file, 'r', encoding='utf-8') as f:
                    json.load(f)
            except json.JSONDecodeError as e:
                print_colored(Colors.RED, f"Invalid JSON: {json_file} - {e}")
                errors += 1

    if errors > 0:
        print_colored(Colors.RED, f"{errors} invalid JSON files")
        return False

    print_colored(Colors.GREEN, "OK - All JSON files valid")
    return True

def check_php_syntax():
    """Check PHP syntax"""
    # Check if PHP is available
    try:
        subprocess.run(['php', '--version'], capture_output=True, check=True)
    except (subprocess.CalledProcessError, FileNotFoundError):
        # PHP not available, skip check
        return True

    print_section("Checking PHP syntax...")

    staged_files = get_staged_files()
    php_files = [f for f in staged_files if f.endswith('.php')]

    errors = 0
    for php_file in php_files:
        if os.path.exists(php_file):
            result = subprocess.run(['php', '-l', php_file],
                                  capture_output=True, text=True)
            if result.returncode != 0:
                print_colored(Colors.RED, f"Syntax error: {php_file}")
                print(result.stdout)
                errors += 1

    if errors > 0:
        print_colored(Colors.RED, f"{errors} PHP files with syntax errors")
        return False

    print_colored(Colors.GREEN, "OK - All PHP files have valid syntax")
    return True

def check_debug_statements():
    """Check for debug statements"""
    print_section("Checking for debug statements...")

    debug_pattern = r'^\+.*(var_dump|print_r|console\.log|console\.debug|debugger;)'
    diff = get_staged_diff()

    # Handle case where diff is None
    if diff is None:
        print_colored(Colors.GREEN, "OK - No diff to scan")
        return True

    matches = re.findall(debug_pattern, diff, re.IGNORECASE | re.MULTILINE)

    if matches:
        print_colored(Colors.YELLOW, "WARNING: Debug statements detected")
        # Show first 5 matches
        for line in diff.split('\n')[:100]:
            if re.search(debug_pattern, line, re.IGNORECASE):
                print(line[:100])

        response = input("Continue anyway? [yes/no]: ")
        if response.lower() != 'yes':
            print_colored(Colors.RED, "Commit aborted")
            return False
    else:
        print_colored(Colors.GREEN, "OK - No debug statements found")

    return True

def check_lmstudio_scan():
    """Run LM Studio security scan if available"""
    # Check if SKIP_LMSTUDIO environment variable is set
    if os.environ.get('SKIP_LMSTUDIO'):
        return True

    staged_files = get_staged_files()
    code_files = [f for f in staged_files if f.endswith(('.php', '.js'))]

    if not code_files:
        return True

    # Check if LM Studio is running
    try:
        import urllib.request
        import urllib.error

        req = urllib.request.Request('http://localhost:1234/v1/models')
        with urllib.request.urlopen(req, timeout=2) as response:
            if response.status != 200:
                return True
    except (urllib.error.URLError, Exception):
        # LM Studio not running, skip scan
        return True

    print_section("Running LM Studio security scan...")

    diff = get_staged_diff()

    # Handle case where diff is None
    if diff is None:
        print_colored(Colors.GREEN, "OK - No diff to scan")
        return True

    # Create temp diff file
    import tempfile
    with tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.diff') as f:
        diff_file = f.name
        f.write(diff)

    try:
        hook_dir = os.path.dirname(os.path.abspath(__file__))
        scan_script = os.path.join(hook_dir, 'lmstudio-scan.py')

        if os.path.exists(scan_script):
            # Use sys.executable to ensure we use the same Python interpreter
            result = subprocess.run([sys.executable, scan_script, diff_file],
                                  capture_output=True, text=True)
            print(result.stdout)
            if result.returncode != 0:
                return False
    finally:
        os.unlink(diff_file)

    return True

def main():
    """Run all pre-commit checks"""
    print()
    print("Running pre-commit checks...")

    checks = [
        check_protected_files,
        check_sensitive_data,
        check_json_files,
        check_php_syntax,
        check_debug_statements,
        check_lmstudio_scan
    ]

    for check in checks:
        if not check():
            return 1

    print()
    print_colored(Colors.GREEN, "OK - All pre-commit checks passed")
    print()
    return 0

if __name__ == '__main__':
    sys.exit(main())
