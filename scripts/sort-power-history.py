#!/usr/bin/env python3
"""
Power History CSV Sorter
Sorts alliance columns by power values with multi-level criteria:
1. Highest power in latest date
2. If tied, use next latest date
3. If still tied, sort alphabetically
"""

import csv
import os
from datetime import datetime
from collections import defaultdict

def parse_date(date_str):
    """Parse various date formats in the CSV"""
    date_str = date_str.strip()
    
    # Try different date formats
    formats = [
        '%Y-%m-%d %H:%M:%S',  # 2025-10-09 04:00:00
        '%m/%d/%Y %H:%M',     # 10/10/2025 8:00
        '%m/%d/%Y %H:%M:%S',  # 10/12/2025 4:00:00
        '%Y-%m-%d',           # 2025-10-09
        '%m/%d/%Y',           # 10/10/2025
    ]
    
    for fmt in formats:
        try:
            return datetime.strptime(date_str, fmt)
        except ValueError:
            continue
    
    # If no format matches, try to extract just the date part
    try:
        # Handle cases like "1/1/2025 1:00"
        if '/' in date_str and ':' in date_str:
            date_part = date_str.split(' ')[0]
            return datetime.strptime(date_part, '%m/%d/%Y')
    except ValueError:
        pass
    
    print(f"Warning: Could not parse date '{date_str}', using current time")
    return datetime.now()

def get_power_value(value_str):
    """Convert power value string to integer, handling empty/invalid values"""
    if not value_str or value_str.strip() == '' or value_str.strip() == '0':
        return 0
    
    try:
        return int(float(value_str.strip()))
    except (ValueError, TypeError):
        return 0

def sort_power_history_csv(file_path):
    """Sort the power history CSV by the specified criteria"""
    print(f"🔄 Loading power history from {file_path}")
    
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
    
    # Parse header (first row)
    header = rows[0]
    datetime_col = header[0]  # First column is datetime
    alliance_cols = header[1:]  # Rest are alliance tags
    
    print(f"📊 Found {len(alliance_cols)} alliance columns")
    
    # Parse data rows and extract dates
    data_rows = []
    for i, row in enumerate(rows[1:], 1):
        if len(row) < len(header):
            print(f"⚠️  Warning: Row {i+1} has fewer columns than header, padding with zeros")
            row.extend(['0'] * (len(header) - len(row)))
        
        date_str = row[0]
        parsed_date = parse_date(date_str)
        power_values = [get_power_value(val) for val in row[1:]]
        
        data_rows.append({
            'original_date': date_str,
            'parsed_date': parsed_date,
            'power_values': power_values
        })
    
    # Sort data rows by date (latest first)
    data_rows.sort(key=lambda x: x['parsed_date'], reverse=True)
    sorted_dates = [row['parsed_date'] for row in data_rows]
    
    print(f"📅 Date range: {sorted_dates[-1].strftime('%Y-%m-%d')} to {sorted_dates[0].strftime('%Y-%m-%d')}")
    
    # Create sorting key for each alliance
    def get_alliance_sort_key(alliance_index):
        """Generate sort key for an alliance based on power values across dates"""
        alliance_tag = alliance_cols[alliance_index]
        
        # Get power values for this alliance across all dates (latest first)
        power_sequence = []
        for row in data_rows:
            if alliance_index < len(row['power_values']):
                power_sequence.append(row['power_values'][alliance_index])
            else:
                power_sequence.append(0)
        
        # Create sort key: (-power_latest, -power_second_latest, ..., alliance_name)
        # Negative values for descending order, then alliance name for alphabetical
        sort_key = [-power for power in power_sequence] + [alliance_tag.lower()]
        
        return sort_key
    
    # Create list of (alliance_index, sort_key) pairs
    alliance_indices = list(range(len(alliance_cols)))
    alliance_sort_data = [(i, get_alliance_sort_key(i)) for i in alliance_indices]
    
    # Sort by the sort keys
    alliance_sort_data.sort(key=lambda x: x[1])
    
    # Get the new column order
    sorted_alliance_indices = [item[0] for item in alliance_sort_data]
    
    # Create new header
    new_header = [datetime_col] + [alliance_cols[i] for i in sorted_alliance_indices]
    
    # Create new data rows
    new_rows = [new_header]
    for row_data in data_rows:
        new_row = [row_data['original_date']]
        for alliance_idx in sorted_alliance_indices:
            if alliance_idx < len(row_data['power_values']):
                new_row.append(str(row_data['power_values'][alliance_idx]))
            else:
                new_row.append('0')
        new_rows.append(new_row)
    
    # Write sorted CSV
    with open(file_path, 'w', encoding='utf-8', newline='') as f:
        writer = csv.writer(f)
        writer.writerows(new_rows)
    
    print(f"✅ Sorted power history saved!")
    print(f"📊 Column order (top 10 by latest power):")
    
    # Show top 10 alliances by latest power
    latest_powers = []
    for i, alliance_idx in enumerate(sorted_alliance_indices[:10]):
        alliance_tag = alliance_cols[alliance_idx]
        latest_power = data_rows[0]['power_values'][alliance_idx] if alliance_idx < len(data_rows[0]['power_values']) else 0
        latest_powers.append((i+1, alliance_tag, latest_power))
        print(f"  {i+1:2d}. {alliance_tag:6s} - {latest_power:,}")
    
    print(f"📈 Total alliances sorted: {len(alliance_cols)}")
    
    return new_rows

if __name__ == "__main__":
    # Run from repository root
    csv_file = "data/power-history.csv"
    
    if not os.path.exists(csv_file):
        print(f"❌ Error: {csv_file} not found. Run from repository root.")
        exit(1)
    
    sort_power_history_csv(csv_file)