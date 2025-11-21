#!/usr/bin/env python3
"""
Clean up contaminated Korean translations
Removes preservation instruction text that was accidentally included
"""

import json
import re
import sys
from pathlib import Path

# Fix Windows console encoding
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except AttributeError:
        pass

def clean_text(text):
    """Remove contamination from a text string"""
    if not isinstance(text, str):
        return text

    # Patterns to remove (contamination from translation instructions)
    contamination_patterns = [
        # Korean preservation instructions with tech terms
        r'\n+.*?(?:변경하지|변경|유지|않|없음|마세요|항목).*?(?:R5|R4|APE|Discord|SMTP|JWT|API).*?$',
        # Direct lists of tech terms after newline
        r'\n+\s*(?:R5|Discord).*?,.*?(?:R4|SMTP|APE|JWT).*?$',
        # Standalone tech term lists (when it's JUST the list)
        r'^\s*R5,\s*R4,\s*APE.*?(?:admin|president|council|🚀|📋|🛡️|⚠️|✅|🗳️|📅|📊|ℹ️).*?$',
        # Lines with preservation keywords
        r'\n+.*?(?:Keep unchanged|sin cambios|inalterado|Mantenha).*?:.*?$',
        # Multiple consecutive tech terms with commas/emojis
        r'\s+R5,\s*R4,\s*APE,\s*NAP15,\s*Discord.*?(?:🚀|📋|🛡️).*?$',
    ]

    original = text

    # Apply each pattern
    for pattern in contamination_patterns:
        text = re.sub(pattern, '', text, flags=re.IGNORECASE | re.MULTILINE | re.DOTALL)

    # Clean up excessive whitespace and newlines
    text = re.sub(r'\n\s*\n\s*\n+', '\n\n', text)  # Multiple blank lines -> double
    text = re.sub(r'\n{3,}', '\n\n', text)  # Triple+ newlines -> double
    text = text.strip()

    # If we removed everything, return a simple translation marker
    if not text and original:
        # Text was entirely contamination - check what the key might need
        return original.split('\n')[0].strip() if '\n' in original else original.strip()

    return text

def clean_value(value):
    """Recursively clean JSON values"""
    if isinstance(value, str):
        return clean_text(value)
    elif isinstance(value, dict):
        return {k: clean_value(v) for k, v in value.items()}
    elif isinstance(value, list):
        return [clean_value(item) for item in value]
    else:
        return value

def main():
    print("=" * 70)
    print("Korean Translation Cleanup Script")
    print("=" * 70)

    # Load Korean translations
    ko_file = Path("admin/i18n/ko/translations.json")
    print(f"\n📖 Loading: {ko_file}")

    with open(ko_file, 'r', encoding='utf-8') as f:
        ko_data = json.load(f)

    print(f"✅ Loaded {len(ko_data)} sections")

    # Clean all values
    print("\n🧹 Cleaning contamination...")
    cleaned_data = clean_value(ko_data)

    # Backup original
    backup_file = Path("admin/i18n/ko/translations.json.backup")
    print(f"\n💾 Creating backup: {backup_file}")
    with open(backup_file, 'w', encoding='utf-8') as f:
        json.dump(ko_data, f, indent=2, ensure_ascii=False)

    # Save cleaned version
    print(f"💾 Saving cleaned version: {ko_file}")
    with open(ko_file, 'w', encoding='utf-8') as f:
        json.dump(cleaned_data, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 70)
    print("✨ Cleanup complete!")
    print("=" * 70)
    print(f"\n✅ Original backed up to: {backup_file}")
    print(f"✅ Cleaned file saved to: {ko_file}")
    print("\n💡 TIP: Check the login page to verify translations look correct")

if __name__ == '__main__':
    main()
