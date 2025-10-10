from PIL import Image
import os

# Open the image
img = Image.open('images/2025-10-06_14-30-19.png')
width, height = img.size

print(f"Image size: {width}x{height}")

# Create output directory
output_dir = 'images/logos'
os.makedirs(output_dir, exist_ok=True)

# Based on the screenshot, the image appears to be about 200px wide
# and contains two sections (ranks 1-23 and 24-45)

# Alliances with their ranking position
alliances_part1 = [
    (1, 'K44'), (2, 'L0PEC'), (3, 'cheY1'), (4, 'LSTRS'), (5, 'LEA4'),
    (6, 'L0PKL'), (7, 'F1A1P'), (8, 'SALoA'), (9, 'YFP01'), (10, 'L0NES'),
    (11, '4RONG'), (12, 'FPI2S3'), (13, 'FMG2'), (14, '3g3g3'), (15, 'MCZM3'),
    (16, 'L0PRM'), (17, '4LM01'), (18, 'CAB'), (19, 'NW00S'), (20, 'tw1l0'),
    (21, '1984'), (22, '2号店'), (23, '0FW72')
]

alliances_part2 = [
    (24, 'APXL'), (25, 'NR0DL'), (26, '0FL4T'), (27, 'FNlC5'), (28, 'AwLL'),
    (29, 'LATM9'), (30, 'LEd43'), (31, 'Lex00'), (32, 'FW4SS'), (33, 'Orld'),
    (34, 'Cue46'), (35, 'NATIVES'), (36, 'Iow0'), (37, 'Irk'), (38, 'IFEWS'),
    (39, 'yn01'), (40, '4ndz4'), (41, 'LHFC2'), (42, 'BSTS'), (43, '4NW5'),
    (44, 'zbn'), (45, 'LFW4T')
]

# Image layout parameters (these may need adjustment)
# Based on typical mobile game UI
section1_start_y = 80  # Where first section starts
section2_start_y = height // 2 + 40  # Where second section starts
row_height = 55  # Height between each alliance row
logo_x = 35  # X position of logo
logo_size = 40  # Logo dimensions (square)

def extract_logo(rank, abbrev, section_start_y, row_in_section):
    """Extract a single logo"""
    y_top = section_start_y + (row_in_section * row_height) + 7
    y_bottom = y_top + logo_size
    x_left = logo_x
    x_right = x_left + logo_size
    
    # Make sure we're within bounds
    if y_bottom > height or x_right > width:
        print(f"Warning: {abbrev} position out of bounds")
        return False
    
    try:
        logo = img.crop((x_left, y_top, x_right, y_bottom))
        output_path = os.path.join(output_dir, f'{abbrev}.png')
        logo.save(output_path)
        print(f"✓ Extracted: {abbrev} (Rank {rank})")
        return True
    except Exception as e:
        print(f"✗ Error extracting {abbrev}: {e}")
        return False

# Extract logos from first section
print("\n=== Extracting Part 1 (Ranks 1-23) ===")
for i, (rank, abbrev) in enumerate(alliances_part1):
    extract_logo(rank, abbrev, section1_start_y, i)

# Extract logos from second section
print("\n=== Extracting Part 2 (Ranks 24-45) ===")
for i, (rank, abbrev) in enumerate(alliances_part2):
    extract_logo(rank, abbrev, section2_start_y, i)

print(f"\n✓ Complete! All logos saved to {output_dir}/")
print(f"Total logos extracted: {len(alliances_part1) + len(alliances_part2)}")
