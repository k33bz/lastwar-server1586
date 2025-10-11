#!/usr/bin/env python3
"""
Merge signature history into alliances.json

This script:
1. Merges r5History from signature-history.json into alliances.json
2. Sorts alliances by: latest version signed, earliest signed date, then alphabetically by tag
3. Preserves all existing alliance data

Version: 1.0.0
Date: 2025-10-10
"""

import sys
import json
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Any

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

PROJECT_DIR = Path(__file__).parent.parent
ALLIANCES_FILE = PROJECT_DIR / "data" / "alliances.json"
SIGNATURE_HISTORY_FILE = PROJECT_DIR / "data" / "signature-history.json"


def get_latest_signature_version(alliance: Dict[str, Any]) -> tuple:
    """
    Get sorting key for alliance based on signature history
    Returns: (latest_version, earliest_date, tag) for sorting
    """
    r5_history = alliance.get('r5History', [])

    if not r5_history:
        # No R5 history - sort to end
        return ("0.0", "9999-12-31", alliance.get('tag', 'ZZZ'))

    # Get current R5's signatures
    current_r5 = None
    for r5_entry in r5_history:
        if r5_entry.get('current', False):
            current_r5 = r5_entry
            break

    if not current_r5 or not current_r5.get('signatures'):
        # No current R5 or no signatures - sort to end
        return ("0.0", "9999-12-31", alliance.get('tag', 'ZZZ'))

    signatures = current_r5['signatures']

    # Get latest version
    latest_version = "0.0"
    earliest_date = "9999-12-31"

    for sig in signatures:
        version = sig.get('version', '0.0')
        signed_at = sig.get('signedAt', '9999-12-31T00:00:00Z')[:10]  # Get just date part

        # Compare versions (simple string comparison works for "1.0", "1.1", "1.2", etc.)
        if version > latest_version:
            latest_version = version

        # Track earliest signature date
        if signed_at < earliest_date:
            earliest_date = signed_at

    # Return tuple for sorting: latest version DESC, earliest date ASC, tag ASC
    # Negate version for DESC sort (invert using complement)
    return (latest_version, earliest_date, alliance.get('tag', 'ZZZ'))


def merge_signature_history():
    """Main function to merge signature history into alliances.json"""

    print("=" * 70)
    print("Merge Signature History into alliances.json")
    print("=" * 70)
    print()
    print("NOTE: This merges ALL alliances (not just top 15)")
    print("      Website filters to show top 15, but JSON stores all for history")
    print()

    # Load alliances.json
    print("[1/4] Loading alliances.json...")
    with open(ALLIANCES_FILE, 'r', encoding='utf-8') as f:
        alliances = json.load(f)
    print(f"      Loaded {len(alliances)} alliances")
    print()

    # Load signature-history.json
    print("[2/4] Loading signature-history.json...")
    with open(SIGNATURE_HISTORY_FILE, 'r', encoding='utf-8') as f:
        sig_data = json.load(f)

    sig_alliances = {a['tag']: a for a in sig_data['alliances']}
    print(f"      Loaded {len(sig_alliances)} alliance signature histories")
    print()

    # Merge r5History into each alliance
    print("[3/4] Merging signature history...")
    merged_count = 0

    for alliance in alliances:
        tag = alliance['tag']

        if tag in sig_alliances:
            # Copy r5History from signature-history.json
            alliance['r5History'] = sig_alliances[tag]['r5History']
            merged_count += 1
            print(f"      ✓ {tag}: Added {len(alliance['r5History'])} R5 history entries")
        else:
            print(f"      ⚠ {tag}: No signature history found")

    print()
    print(f"      Merged {merged_count}/{len(alliances)} alliances")
    print()

    # Sort alliances
    print("[4/4] Sorting alliances...")
    print("      Sort order: Latest version signed DESC, Earliest date ASC, Tag ASC")

    # Sort: latest version DESC (reverse), earliest date ASC, tag ASC
    alliances_sorted = sorted(
        alliances,
        key=lambda a: (
            get_latest_signature_version(a)[0],  # Latest version
            get_latest_signature_version(a)[1],  # Earliest date
            get_latest_signature_version(a)[2]   # Tag (alphabetical)
        ),
        reverse=True  # Reverse for DESC on version
    )

    # Show new order
    print()
    print("      New alliance order:")
    for i, alliance in enumerate(alliances_sorted, 1):
        tag = alliance['tag']
        version, date, _ = get_latest_signature_version(alliance)
        signed = alliance.get('signed', False)
        status = "✓" if signed else "✗"
        print(f"      {i:2d}. {status} {tag:6s} - v{version} signed on {date}")

    print()

    # Save updated alliances.json
    backup_file = ALLIANCES_FILE.with_suffix('.json.bak')
    print(f"[SAVE] Creating backup: {backup_file.name}")
    with open(backup_file, 'w', encoding='utf-8') as f:
        json.dump(alliances, f, indent=4, ensure_ascii=False)

    print(f"[SAVE] Writing updated alliances.json...")
    with open(ALLIANCES_FILE, 'w', encoding='utf-8') as f:
        json.dump(alliances_sorted, f, indent=4, ensure_ascii=False)

    print()
    print("=" * 70)
    print("✓ Merge Complete!")
    print("=" * 70)
    print()
    print("Next steps:")
    print("1. Review the changes: git diff data/alliances.json")
    print("2. Test the website locally to ensure display is correct")
    print("3. Deploy: python scripts/deploy-ftp.py")
    print()


if __name__ == "__main__":
    merge_signature_history()
