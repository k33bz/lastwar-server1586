#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
CSV Validation Script
Validates power-history.csv format and data integrity
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
        if header[0] not in ['date', 'datetime']:
            print(f"❌ Error: First column must be 'date' or 'datetime', found '{header[0]}'")
            return False

        num_columns = len(header)
        alliance_count = num_columns - 1  # Subtract date column
        date_format = 'datetime' if header[0] == 'datetime' else 'date'

        print(f"   ✓ Header valid: {alliance_count} alliances ({date_format} format)")

        # Validate data rows
        data_rows = 0
        for i, line in enumerate(lines[1:], start=2):
            if not line.strip():
                continue

            values = line.strip().split(',')
            if len(values) != num_columns:
                print(f"❌ Error: Line {i} has {len(values)} columns, expected {num_columns}")
                return False

            # Validate date/datetime format (YYYY-MM-DD or YYYY-MM-DD HH:mm:ss)
            datetime_str = values[0].strip()
            if date_format == 'datetime':
                # Validate YYYY-MM-DD HH:mm:ss format (19 characters)
                if len(datetime_str) != 19 or datetime_str[4] != '-' or datetime_str[7] != '-' or datetime_str[10] != ' ' or datetime_str[13] != ':' or datetime_str[16] != ':':
                    print(f"❌ Error: Line {i} has invalid datetime format '{datetime_str}' (expected YYYY-MM-DD HH:mm:ss)")
                    return False
            else:
                # Validate YYYY-MM-DD format (10 characters)
                if len(datetime_str) != 10 or datetime_str[4] != '-' or datetime_str[7] != '-':
                    print(f"❌ Error: Line {i} has invalid date format '{datetime_str}' (expected YYYY-MM-DD)")
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
