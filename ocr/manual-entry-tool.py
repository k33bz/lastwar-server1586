#!/usr/bin/env python3
"""
Alliance Screenshot Manual Entry Tool
Displays screenshots and prompts for manual data entry

Version: 1.0.0
Date: 2025-10-12
"""

import sys
from pathlib import Path
import json

try:
    import cv2
    import numpy as np
except ImportError:
    print("Missing required packages. Install with:")
    print("  pip install opencv-python numpy")
    sys.exit(1)

# Paths
PROJECT_ROOT = Path(__file__).parent.parent
OCR_DIR = Path(__file__).parent
ALLIANCE_CARDS_DIR = OCR_DIR / "alliance_cards" / "temp"
OUTPUT_JSON = PROJECT_ROOT / "data" / "alliances-manual.json"

def find_red_warning_box(image):
    """Find the red exclamation mark box as anchor point"""
    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

    # Red color ranges
    lower_red1 = np.array([0, 100, 100])
    upper_red1 = np.array([10, 255, 255])
    mask1 = cv2.inRange(hsv, lower_red1, upper_red1)

    lower_red2 = np.array([170, 100, 100])
    upper_red2 = np.array([180, 255, 255])
    mask2 = cv2.inRange(hsv, lower_red2, upper_red2)

    red_mask = cv2.bitwise_or(mask1, mask2)
    contours, _ = cv2.findContours(red_mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    if not contours:
        return None

    largest_contour = max(contours, key=cv2.contourArea)
    x, y, w, h = cv2.boundingRect(largest_contour)

    aspect_ratio = w / h if h > 0 else 0
    if 0.7 < aspect_ratio < 1.3 and w > 20 and h > 20:
        return (x, y, w, h)

    return None

def get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """Calculate bounding box for alliance tag"""
    tag_x = anchor_x - 295
    tag_y = anchor_y - 5
    tag_width = 90
    tag_height = 20

    return {
        'x': max(0, tag_x),
        'y': max(0, tag_y),
        'width': tag_width,
        'height': tag_height
    }

def get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """Calculate bounding box for R5 name"""
    r5_x = anchor_x - 142
    r5_y = anchor_y + 55
    r5_width = 152
    r5_height = 18

    return {
        'x': max(0, r5_x),
        'y': max(0, r5_y),
        'width': r5_width,
        'height': r5_height
    }

def process_image_interactive(image_path):
    """Display image and prompt for manual entry"""
    print(f"\n{'='*70}")
    print(f"Image: {image_path.name}")
    print('='*70)

    image = cv2.imread(str(image_path))
    if image is None:
        print("  [ERROR] Could not load image")
        return None

    # Find anchor and draw bounding boxes
    anchor = find_red_warning_box(image)
    if anchor is None:
        print("  [ERROR] Could not find red warning box anchor")
        print("  [INFO] You can still enter data manually")

        # Show full image
        display = cv2.resize(image, (800, 600))
        cv2.imshow('Alliance Screenshot', display)
        cv2.waitKey(1)

        tag = input("  Enter Alliance Tag (with brackets, e.g., <MZKU>): ").strip()
        r5 = input("  Enter R5 Name: ").strip()

        cv2.destroyAllWindows()

        if not tag and not r5:
            return None

        return {
            'image': image_path.name,
            'tag': tag,
            'r5': r5,
            'anchor': None,
            'tag_bbox': None,
            'r5_bbox': None
        }

    anchor_x, anchor_y, anchor_w, anchor_h = anchor

    # Get bounding boxes
    tag_bbox = get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h)
    r5_bbox = get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h)

    # Draw visualization
    display = image.copy()

    # Draw anchor (red)
    cv2.rectangle(display, (anchor_x, anchor_y),
                  (anchor_x + anchor_w, anchor_y + anchor_h), (0, 0, 255), 2)

    # Draw tag bbox (green)
    cv2.rectangle(display,
                  (tag_bbox['x'], tag_bbox['y']),
                  (tag_bbox['x'] + tag_bbox['width'], tag_bbox['y'] + tag_bbox['height']),
                  (0, 255, 0), 2)
    cv2.putText(display, "Alliance Tag",
                (tag_bbox['x'], tag_bbox['y'] - 10),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

    # Draw R5 bbox (blue)
    cv2.rectangle(display,
                  (r5_bbox['x'], r5_bbox['y']),
                  (r5_bbox['x'] + r5_bbox['width'], r5_bbox['y'] + r5_bbox['height']),
                  (255, 0, 0), 2)
    cv2.putText(display, "R5 Name",
                (r5_bbox['x'], r5_bbox['y'] - 10),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 0, 0), 2)

    # Display image in window
    display_resized = cv2.resize(display, (800, 600))
    cv2.imshow('Alliance Screenshot - Manual Entry', display_resized)
    cv2.waitKey(1)  # Display for 1ms to show the window

    # Get user input
    print(f"  [ANCHOR] Found at ({anchor_x}, {anchor_y})")
    tag = input("  Enter Alliance Tag (with brackets, e.g., <MZKU>): ").strip()
    r5 = input("  Enter R5 Name: ").strip()

    cv2.destroyAllWindows()

    if not tag and not r5:
        skip = input("  Skip this image? (y/n): ").strip().lower()
        if skip == 'y':
            return None

    return {
        'image': image_path.name,
        'tag': tag,
        'r5': r5,
        'anchor': list(anchor),
        'tag_bbox': tag_bbox,
        'r5_bbox': r5_bbox
    }

def main():
    """Main entry tool"""
    print("=" * 70)
    print("Alliance Screenshot Manual Entry Tool")
    print("=" * 70)
    print()
    print("Instructions:")
    print("  - Each screenshot will be displayed with bounding boxes")
    print("  - Type the alliance tag WITH brackets (e.g., <MZKU>)")
    print("  - Type the R5 leader name exactly as shown")
    print("  - Press Enter without typing to skip both fields")
    print("  - The window will close after you finish typing")
    print()

    image_files = sorted(ALLIANCE_CARDS_DIR.glob("*.png"))

    if not image_files:
        print(f"\n[ERROR] No images found in {ALLIANCE_CARDS_DIR}")
        return

    print(f"\nFound {len(image_files)} images to process")

    # Load existing data if available
    existing_data = {}
    if OUTPUT_JSON.exists():
        try:
            with open(OUTPUT_JSON, 'r', encoding='utf-8') as f:
                existing = json.load(f)
                for alliance in existing.get('alliances', []):
                    existing_data[alliance['image']] = alliance
            print(f"Loaded {len(existing_data)} existing entries")
        except Exception as e:
            print(f"Warning: Could not load existing data: {e}")

    results = []
    processed = 0
    skipped = 0

    for image_path in image_files:
        # Check if already processed
        if image_path.name in existing_data:
            use_existing = input(f"\n{image_path.name} already processed. Use existing data? (y/n): ").strip().lower()
            if use_existing == 'y':
                results.append(existing_data[image_path.name])
                processed += 1
                continue

        result = process_image_interactive(image_path)
        if result:
            results.append(result)
            processed += 1
            print(f"  ✓ Recorded: Tag={result['tag']}, R5={result['r5']}")
        else:
            skipped += 1
            print(f"  ⊘ Skipped")

    # Save results
    output_data = {
        'processed_date': '2025-10-12',
        'model': 'Manual Entry',
        'total_images': len(image_files),
        'successful': processed,
        'failed': skipped,
        'alliances': results
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output_data, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 70)
    print(f"Manual Entry Complete:")
    print(f"  Processed: {processed}")
    print(f"  Skipped: {skipped}")
    print(f"  Output: {OUTPUT_JSON}")
    print("=" * 70)

    if results:
        print("\nEntered Data:")
        print("-" * 70)
        for r in results:
            print(f"  {r['tag']:12} - {r['r5']}")

if __name__ == '__main__':
    main()
