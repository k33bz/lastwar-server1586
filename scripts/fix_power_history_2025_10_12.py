#!/usr/bin/env python3
"""
Fix power-history.csv for 2025-10-12 by filling empty cells

The CSV validator is failing because some alliance columns have empty values
for 2025-10-12. These alliances are no longer in those rank positions, but
the CSV structure requires a value for every column.

We'll use 0 as a placeholder for alliances that are no longer ranked in top 50.
"""
import csv
from pathlib import Path

# Path to CSV file
csv_path = Path(__file__).parent.parent / "data" / "power-history.csv"

# Read CSV
rows = []
with open(csv_path, 'r', encoding='utf-8') as f:
    reader = csv.reader(f)
    rows = list(reader)

# Find the 2025-10-12 row (should be last row)
for i, row in enumerate(rows):
    if row[0] == '2025-10-12':
        print(f"Found 2025-10-12 data at row {i+1}")

        # Fill empty cells with 0
        fixed_count = 0
        for j in range(1, len(row)):
            if not row[j] or row[j].strip() == '':
                alliance_tag = rows[0][j]  # Header row
                print(f"  Filling empty cell for {alliance_tag} (column {j})")
                row[j] = '0'
                fixed_count += 1

        print(f"\nFixed {fixed_count} empty cells")
        break

# Write back to CSV
with open(csv_path, 'w', encoding='utf-8', newline='') as f:
    writer = csv.writer(f)
    writer.writerows(rows)

print(f"\n✅ Updated {csv_path}")
