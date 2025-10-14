#!/usr/bin/env python3
"""
Fix rank 6 - should be LE4L not LGbt
"""
import json
import csv
from pathlib import Path

def main():
    repo_root = Path(__file__).parent.parent
    alliances_file = repo_root / "data" / "alliances.json"
    csv_file = repo_root / "data" / "power-history.csv"

    # Read alliances.json
    with open(alliances_file, 'r', encoding='utf-8') as f:
        alliances = json.load(f)

    # Find LE4L and LGbt
    le4l = None
    lgbt = None
    le4l_idx = -1
    lgbt_idx = -1

    for i, alliance in enumerate(alliances):
        if alliance['tag'] == 'LE4L':
            le4l = alliance
            le4l_idx = i
        elif alliance['tag'] == 'LGbt':
            lgbt = alliance
            lgbt_idx = i

    if le4l and lgbt:
        # Swap ranks - LE4L should be 6, LGbt should be somewhere else
        print(f"Current state:")
        print(f"  LE4L: rank {le4l['rank']}, power {le4l['power']:,}")
        print(f"  LGbt: rank {lgbt['rank']}, power {lgbt['power']:,}")

        # LE4L gets rank 6 with the power that was assigned to LGbt
        le4l['rank'] = 6
        le4l['power'] = 4988688656  # The correct power for rank 6

        # LGbt needs to be moved down - let's put it after rank 6
        lgbt['rank'] = 7

        print(f"\nCorrected:")
        print(f"  LE4L: rank {le4l['rank']}, power {le4l['power']:,}")
        print(f"  LGbt: rank {lgbt['rank']}, power {lgbt['power']:,}")

    # Re-sort by rank
    alliances.sort(key=lambda x: x['rank'])

    # Write updated alliances.json
    with open(alliances_file, 'w', encoding='utf-8') as f:
        json.dump(alliances, f, indent=4, ensure_ascii=False)

    print("\n[OK] Updated alliances.json")

    # Update CSV - LE4L gets the power value
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        headers = list(reader.fieldnames)
        rows = list(reader)

    for row in rows:
        if row['date'] == '2025-10-12':
            # LE4L gets the power that was at rank 6
            if 'LE4L' in headers:
                row['LE4L'] = '4988688656'
            print(f"[OK] Updated CSV for 2025-10-12")
            break

    # Write updated CSV
    with open(csv_file, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=headers)
        writer.writeheader()
        writer.writerows(rows)

    print("\n[OK] All corrections complete!")

if __name__ == '__main__':
    main()
