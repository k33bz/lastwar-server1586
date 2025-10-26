#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Unit Test Runner
Runs all unit tests for Server 1586 website

Documentation:
- Scripts README: https://github.com/k33bz/lastwar-server1586/blob/mainline/scripts/README.md
- Deployment Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/DEPLOYMENT.md

GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
"""

import sys
import io
from pathlib import Path

# Fix Windows console encoding issues
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

# Get project root
PROJECT_ROOT = Path(__file__).parent.parent

def run_tests():
    """Run all unit tests"""
    print("=" * 60)
    print("Server 1586 - Unit Tests")
    print("=" * 60)
    print()

    # For now, just verify critical files exist
    critical_files = [
        'index.html',
        'js/app.js',
        'css/styles.css',
        'data/alliances.json',
        'data/rules.json',
        'data/amendments.json',
        'data/rotation-schedule.json',
        'data/council.js',
        'data/power-history.csv',
        'data/server-info.json',
        'data/signature-history.json'
    ]

    passed = 0
    failed = 0

    print("Testing critical files exist...")
    for file_path in critical_files:
        full_path = PROJECT_ROOT / file_path
        if full_path.exists():
            print(f"  ✓ {file_path}")
            passed += 1
        else:
            print(f"  ✗ {file_path} - NOT FOUND")
            failed += 1

    print()
    print("=" * 60)
    print(f"Test Results: {passed} passed, {failed} failed")
    print("=" * 60)

    if failed > 0:
        print()
        print("❌ Tests FAILED")
        return False
    else:
        print()
        print("✅ All tests PASSED")
        return True

def main():
    """Main test function"""
    if run_tests():
        sys.exit(0)
    else:
        sys.exit(1)

if __name__ == '__main__':
    main()
