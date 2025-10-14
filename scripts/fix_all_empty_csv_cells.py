#!/usr/bin/env python3
"""
Fix ALL empty cells in power-history.csv

The CSV has historical rows with empty cells for alliances that weren't
in the top 50 at that time. Fill all empty cells with 0.
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

header = rows[0]
total_fixed = 0

# Fix all rows
for i in range(1, len(rows)):
    row = rows[i]
    date = row[0]
    fixed_in_row = 0

    # Fill empty cells with 0
    for j in range(1, len(row)):
        if not row[j] or row[j].strip() == '':
            alliance_tag = header[j]
            row[j] = '0'
            fixed_in_row += 1
            total_fixed += 1

    if fixed_in_row > 0:
        print(f"Fixed {fixed_in_row} empty cells in {date} row")

print(f"\nTotal: Fixed {total_fixed} empty cells")

# Write back to CSV
with open(csv_path, 'w', encoding='utf-8', newline='') as f:
    writer = csv.writer(f)
    writer.writerows(rows)

print(f"Updated {csv_path}")
