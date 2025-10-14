#!/usr/bin/env python3
"""
Correct top 3 rankings for 2025-10-12 data
Based on user feedback:
1. ORCE - 7,044,519,755
2. UvvU - 6,467,618,745
3. nkot - 5,005,417,714
"""
import json
import csv
from pathlib import Path

def main():
    repo_root = Path(__file__).parent.parent
    alliances_file = repo_root / "data" / "alliances.json"
    csv_file = repo_root / "data" / "power-history.csv"

    # Correct power values for top 3
    corrections = {
        "ORCE": {"rank": 1, "power": 7044519755},
        "UvvU": {"rank": 2, "power": 6467618745},
        "nkot": {"rank": 3, "power": 5005417714}
    }

    # Update alliances.json
    with open(alliances_file, 'r', encoding='utf-8') as f:
        alliances = json.load(f)

    # Find and update the top 3 alliances
    for alliance in alliances:
        tag = alliance['tag']
        if tag in corrections:
            old_rank = alliance['rank']
            old_power = alliance['power']
            alliance['rank'] = corrections[tag]['rank']
            alliance['power'] = corrections[tag]['power']
            print(f"Updated {tag}:")
            print(f"  Rank: {old_rank} -> {corrections[tag]['rank']}")
            print(f"  Power: {old_power:,} -> {corrections[tag]['power']:,}")

    # Re-sort by rank
    alliances.sort(key=lambda x: x['rank'])

    # Write updated alliances.json
    with open(alliances_file, 'w', encoding='utf-8') as f:
        json.dump(alliances, f, indent=4, ensure_ascii=False)

    print("\n[OK] Updated alliances.json with corrected top 3 rankings")

    # Update CSV
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        headers = reader.fieldnames
        rows = list(reader)

    # Find the 2025-10-12 row and update
    for row in rows:
        if row['date'] == '2025-10-12':
            row['ORCE'] = str(corrections['ORCE']['power'])
            row['UvvU'] = str(corrections['UvvU']['power'])
            row['nkot'] = str(corrections['nkot']['power'])
            print("\n[OK] Updated power-history.csv for 2025-10-12")
            break

    # Write updated CSV
    with open(csv_file, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        writer.writerows(rows)

    print("\n[OK] Corrections complete!")
    print("\nCorrected Top 3:")
    print("1. ORCE - 7,044,519,755")
    print("2. UvvU - 6,467,618,745")
    print("3. nkot - 5,005,417,714")

if __name__ == '__main__':
    main()
