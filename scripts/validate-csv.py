#!/usr/bin/env python3
"""
CSV Validation Script
Validates power-history.csv format and data integrity
"""

import sys
from pathlib import Path

# Get project root
PROJECT_ROOT = Path(__file__).parent.parent
CSV_PATH = PROJECT_ROOT / 'data' / 'power-history.csv'

def validate_csv():
    """Validate power-history.csv format"""
    print("Validating power-history.csv...")

    if not CSV_PATH.exists():
        print(f"❌ Error: {CSV_PATH} not found")
        return False

    try:
        with open(CSV_PATH, 'r') as f:
            lines = f.readlines()

        if len(lines) < 2:
            print(f"❌ Error: CSV must have at least 2 lines (header + data)")
            return False

        # Validate header
        header = lines[0].strip().split(',')
        if header[0] != 'date':
            print(f"❌ Error: First column must be 'date', found '{header[0]}'")
            return False

        num_columns = len(header)
        alliance_count = num_columns - 1  # Subtract date column

        print(f"   ✓ Header valid: {alliance_count} alliances")

        # Validate data rows
        data_rows = 0
        for i, line in enumerate(lines[1:], start=2):
            if not line.strip():
                continue

            values = line.strip().split(',')
            if len(values) != num_columns:
                print(f"❌ Error: Line {i} has {len(values)} columns, expected {num_columns}")
                return False

            # Validate date format (YYYY-MM-DD)
            date = values[0].strip()
            if len(date) != 10 or date[4] != '-' or date[7] != '-':
                print(f"❌ Error: Line {i} has invalid date format '{date}' (expected YYYY-MM-DD)")
                return False

            # Validate power values are numeric
            for j, value in enumerate(values[1:], start=1):
                try:
                    power = int(value.strip())
                    if power < 0:
                        print(f"❌ Error: Line {i}, column {j} has negative power value")
                        return False
                except ValueError:
                    print(f"❌ Error: Line {i}, column {j} has non-numeric value '{value}'")
                    return False

            data_rows += 1

        print(f"   ✓ {data_rows} data rows validated")
        print(f"✅ power-history.csv is valid")
        return True

    except Exception as e:
        print(f"❌ Error reading CSV: {e}")
        return False

def main():
    """Main validation function"""
    if validate_csv():
        sys.exit(0)
    else:
        sys.exit(1)

if __name__ == '__main__':
    main()
