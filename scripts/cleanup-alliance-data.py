#!/usr/bin/env python3
"""
Alliance Data Cleanup Script
Merges duplicate entries and updates timestamps in alliances.json
"""

import json
import os
from datetime import datetime
from collections import defaultdict

def load_alliances(file_path):
    """Load alliance data from JSON file"""
    with open(file_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_alliances(file_path, data):
    """Save alliance data to JSON file"""
    with open(file_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

def merge_alliance_data(alliance1, alliance2):
    """Merge two alliance objects, preferring non-null/non-empty values"""
    merged = alliance1.copy()
    
    # Merge fields, preferring non-null/non-empty values
    for key, value in alliance2.items():
        if key not in merged or merged[key] is None or merged[key] == "" or merged[key] == "???":
            if value is not None and value != "" and value != "???":
                merged[key] = value
        elif key == "power" and isinstance(value, (int, float)) and value > 0:
            # Prefer higher power value
            if merged[key] is None or merged[key] == 0:
                merged[key] = value
        elif key == "r5History" and isinstance(value, list) and len(value) > 0:
            # Merge r5History arrays
            if not isinstance(merged[key], list):
                merged[key] = value
            else:
                # Combine and deduplicate r5History
                existing_r5s = {r5.get('r5Name', '') for r5 in merged[key]}
                for r5_entry in value:
                    if r5_entry.get('r5Name', '') not in existing_r5s:
                        merged[key].append(r5_entry)
    
    return merged

def cleanup_alliance_data(file_path):
    """Clean up alliance data by merging duplicates and updating timestamps"""
    print(f"🔄 Loading alliance data from {file_path}")
    
    # Load data
    alliances = load_alliances(file_path)
    
    # Create backup
    backup_path = f"{file_path}.backup.{datetime.now().strftime('%Y%m%d_%H%M%S')}"
    save_alliances(backup_path, alliances)
    print(f"📁 Created backup: {backup_path}")
    
    # Group by tag to find duplicates
    tag_groups = defaultdict(list)
    for i, alliance in enumerate(alliances):
        tag = alliance.get('tag', '')
        tag_groups[tag].append((i, alliance))
    
    # Find duplicates
    duplicates = {tag: entries for tag, entries in tag_groups.items() if len(entries) > 1}
    
    print(f"🔍 Found {len(duplicates)} duplicate alliance tags:")
    for tag, entries in duplicates.items():
        print(f"  - {tag}: {len(entries)} entries")
    
    # Merge duplicates
    cleaned_alliances = []
    processed_tags = set()
    current_timestamp = datetime.now().isoformat() + 'Z'
    
    for alliance in alliances:
        tag = alliance.get('tag', '')
        
        if tag in processed_tags:
            continue
            
        if tag in duplicates:
            # Merge all entries for this tag
            merged_alliance = alliance.copy()
            for _, duplicate_alliance in duplicates[tag][1:]:  # Skip first entry
                merged_alliance = merge_alliance_data(merged_alliance, duplicate_alliance)
            
            # Update timestamp
            if 'metadata' not in merged_alliance:
                merged_alliance['metadata'] = {}
            merged_alliance['metadata']['lastUpdated'] = current_timestamp
            
            cleaned_alliances.append(merged_alliance)
            processed_tags.add(tag)
            print(f"  ✅ Merged {len(duplicates[tag])} entries for tag '{tag}'")
        else:
            # Single entry, just update timestamp
            if 'metadata' not in alliance:
                alliance['metadata'] = {}
            alliance['metadata']['lastUpdated'] = current_timestamp
            cleaned_alliances.append(alliance)
    
    # Remove placeholder entries (those with name "???" and null values)
    original_count = len(cleaned_alliances)
    cleaned_alliances = [
        alliance for alliance in cleaned_alliances 
        if not (alliance.get('name') == '???' and alliance.get('r5') is None)
    ]
    placeholder_removed = original_count - len(cleaned_alliances)
    
    if placeholder_removed > 0:
        print(f"🗑️  Removed {placeholder_removed} placeholder entries")
    
    # Save cleaned data
    save_alliances(file_path, cleaned_alliances)
    
    print(f"✅ Cleanup complete!")
    print(f"📊 Summary:")
    print(f"  - Original entries: {len(alliances)}")
    print(f"  - Duplicates merged: {sum(len(entries) - 1 for entries in duplicates.values())}")
    print(f"  - Placeholders removed: {placeholder_removed}")
    print(f"  - Final entries: {len(cleaned_alliances)}")
    print(f"  - All entries updated with timestamp: {current_timestamp}")

if __name__ == "__main__":
    # Run from repository root
    alliance_file = "data/alliances.json"
    
    if not os.path.exists(alliance_file):
        print(f"❌ Error: {alliance_file} not found. Run from repository root.")
        exit(1)
    
    cleanup_alliance_data(alliance_file)