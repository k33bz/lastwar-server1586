#!/usr/bin/env python3
"""
Datetime Format Standardizer for Power History CSV
Converts all datetime entries to ISO format: YYYY-MM-DD HH:MM:SS
"""

import csv
import os
from datetime import datetime

def parse_datetime(date_str):
    """Parse various datetime formats and return standardized ISO format"""
    date_str = date_str.strip()
    
    # Try different datetime formats found in the CSV
    formats = [
        '%Y-%m-%d %H:%M:%S',    # 2025-10-09 04:00:00 (already ISO)
        '%m/%d/%Y %H:%M',       # 10/26/2025 4:00
        '%m/%d/%Y %H:%M:%S',    # 10/26/2025 4:00:00
        '%Y-%m-%d',             # 2025-10-09
        '%m/%d/%Y',             # 10/26/2025
    ]
    
    for fmt in formats:
        try:
            parsed_dt = datetime.strptime(date_str, fmt)
            # Return in ISO format with seconds
            return parsed_dt.strftime('%Y-%m-%d %H:%M:%S')
        except ValueError:
            continue
    
    # Handle special cases
    try:
        # Handle cases like "1/1/2025 1:00" (single digit month/day)
        if '/' in date_str and ':' in date_str:
            # Try parsing with flexible format
            parts = date_str.split(' ')
            if len(parts) == 2:
                date_part, time_part = parts
                
                # Parse date part
                date_components = date_part.split('/')
                if len(date_components) == 3:
                    month, day, year = date_components
                    
                    # Parse time part
                    time_components = time_part.split(':')
                    if len(time_components) == 2:
                        hour, minute = time_components
                        
                        # Create datetime object
                        dt = datetime(
                            year=int(year),
                            month=int(month),
                            day=int(day),
                            hour=int(hour),
                            minute=int(minute),
                            second=0
                        )
                        return dt.strftime('%Y-%m-%d %H:%M:%S')
    except (ValueError, IndexError):
        pass
    
    print(f"⚠️  Warning: Could not parse datetime '{date_str}', keeping original")
    return date_str

def standardize_datetime_format(file_path):
    """Standardize all datetime entries in the CSV to ISO format"""
    print(f"🔄 Standardizing datetime format in {file_path}")
    
    # Read the CSV file
    with open(file_path, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        rows = list(reader)
    
    if len(rows) < 2:
        print("❌ Error: CSV file must have at least a header and one data row")
        return
    
    # Create backup
    backup_path = f"{file_path}.backup.{datetime.now().strftime('%Y%m%d_%H%M%S')}"
    with open(backup_path, 'w', encoding='utf-8', newline='') as f:
        writer = csv.writer(f)
        writer.writerows(rows)
    print(f"📁 Created backup: {backup_path}")
    
    # Process rows
    header = rows[0]
    print(f"📊 Processing {len(rows)-1} data rows")
    
    standardized_count = 0
    unchanged_count = 0
    
    # Process each data row
    for i in range(1, len(rows)):
        original_datetime = rows[i][0]
        standardized_datetime = parse_datetime(original_datetime)
        
        if standardized_datetime != original_datetime:
            rows[i][0] = standardized_datetime
            standardized_count += 1
            print(f"  ✅ '{original_datetime}' → '{standardized_datetime}'")
        else:
            unchanged_count += 1
    
    # Write standardized CSV
    with open(file_path, 'w', encoding='utf-8', newline='') as f:
        writer = csv.writer(f)
        writer.writerows(rows)
    
    print(f"✅ Datetime standardization complete!")
    print(f"📊 Summary:")
    print(f"  - Standardized: {standardized_count} entries")
    print(f"  - Unchanged: {unchanged_count} entries")
    print(f"  - Total processed: {len(rows)-1} entries")
    print(f"  - Format: ISO 8601 (YYYY-MM-DD HH:MM:SS)")
    
    # Show sample of standardized datetimes
    print(f"📅 Sample standardized datetimes:")
    for i in range(1, min(6, len(rows))):
        print(f"  {i}. {rows[i][0]}")
    
    return rows

if __name__ == "__main__":
    # Run from repository root
    csv_file = "data/power-history.csv"
    
    if not os.path.exists(csv_file):
        print(f"❌ Error: {csv_file} not found. Run from repository root.")
        exit(1)
    
    standardize_datetime_format(csv_file)