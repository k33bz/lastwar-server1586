#!/usr/bin/env python3
"""
Alliance Screenshot OCR - Anchor-Based Processing
Uses the red exclamation mark box as a consistent reference point for OCR

This script:
1. Detects the red warning box (! icon) as an anchor point
2. Defines bounding boxes relative to this anchor
3. Crops regions for alliance tag and R5 name
4. Performs OCR on cropped regions for better accuracy

Version: 1.0.0
Date: 2025-10-11
"""

import sys
from pathlib import Path
import json

try:
    import cv2
    import numpy as np
    import easyocr
except ImportError:
    print("Missing required packages. Install with:")
    print("  pip install opencv-python numpy easyocr")
    sys.exit(1)

# Paths
PROJECT_ROOT = Path(__file__).parent.parent
OCR_DIR = Path(__file__).parent
ALLIANCE_CARDS_DIR = OCR_DIR / "alliance_cards" / "temp"
OUTPUT_DIR = OCR_DIR / "debug_ocr"
OUTPUT_JSON = PROJECT_ROOT / "data" / "alliances-ocr.json"

# Create output directory
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Initialize EasyOCR reader (English only for better speed)
reader = easyocr.Reader(['en'], gpu=False)

def find_red_warning_box(image):
    """
    Find the red exclamation mark box as anchor point
    Returns (x, y, w, h) of the red box, or None if not found
    """
    # Convert to HSV for better color detection
    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)

    # Red color range in HSV (red wraps around at 0/180)
    # Lower red range (0-10)
    lower_red1 = np.array([0, 100, 100])
    upper_red1 = np.array([10, 255, 255])
    mask1 = cv2.inRange(hsv, lower_red1, upper_red1)

    # Upper red range (170-180)
    lower_red2 = np.array([170, 100, 100])
    upper_red2 = np.array([180, 255, 255])
    mask2 = cv2.inRange(hsv, lower_red2, upper_red2)

    # Combine masks
    red_mask = cv2.bitwise_or(mask1, mask2)

    # Find contours
    contours, _ = cv2.findContours(red_mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    if not contours:
        return None

    # Find the largest red contour (likely the warning box)
    largest_contour = max(contours, key=cv2.contourArea)
    x, y, w, h = cv2.boundingRect(largest_contour)

    # Verify it's roughly square (warning box should be)
    aspect_ratio = w / h if h > 0 else 0
    if 0.7 < aspect_ratio < 1.3 and w > 20 and h > 20:
        return (x, y, w, h)

    return None

def get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """
    Calculate bounding box for alliance tag relative to red warning box

    Actual layout:
    - Red box is in top-right corner (anchor point)
    - Alliance tag is on LEFT side of pink header, same vertical level
    - Tag format: "<TAG>" in angle brackets, black text
    - Tag is typically 2-6 characters between < >
    """
    # Alliance tag is in the first data row, LEFT side, black text on pink background
    # Tag format: "<TAG>" appears before the full alliance name
    # Red box at x~505, tag starts at x~215, typically ends by x~280 (65 pixels wide max)
    tag_y = anchor_y + 5  # Same row as red box (pink data row)
    tag_height = 18  # Just tall enough for the tag text
    tag_width = 70  # Wide enough for just "<NKOT>" (4-8 chars with brackets, ~50-70px)
    tag_x = anchor_x - 290  # Position where tag text appears (505 - 290 = 215)

    return {
        'x': max(0, tag_x),
        'y': max(0, tag_y),
        'width': tag_width,
        'height': tag_height
    }

def get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """
    Calculate bounding box for R5 name relative to red warning box

    Actual layout:
    - Red box is in top-right corner (anchor point)
    - R5 name is in "Leader" row, on the RIGHT side
    - Leader row is about 60 pixels below the red box
    - Name is right-aligned with the power number above it
    """
    # R5 name is below the red box in the "Leader" field
    r5_y = anchor_y + 60  # Leader row is ~60px below red box
    r5_height = 25  # Just tall enough for name text
    r5_width = 180  # Wide enough for typical R5 names
    r5_x = anchor_x - 120  # Aligned with the right-side text fields

    return {
        'x': max(0, r5_x),
        'y': max(0, r5_y),
        'width': r5_width,
        'height': r5_height
    }

def crop_region(image, bbox):
    """Crop image to specified bounding box"""
    x, y, w, h = bbox['x'], bbox['y'], bbox['width'], bbox['height']

    # Ensure we don't exceed image bounds
    img_h, img_w = image.shape[:2]
    x = max(0, min(x, img_w - 1))
    y = max(0, min(y, img_h - 1))
    w = min(w, img_w - x)
    h = min(h, img_h - y)

    return image[y:y+h, x:x+w]

def preprocess_for_ocr(image):
    """
    Preprocess cropped region for better OCR accuracy
    """
    # Convert to grayscale
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    # Increase contrast
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
    enhanced = clahe.apply(gray)

    # Threshold to get clean black text on white background
    _, thresh = cv2.threshold(enhanced, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    # Invert if background is darker than text
    if np.mean(thresh) < 127:
        thresh = cv2.bitwise_not(thresh)

    return thresh

def clean_alliance_tag(text):
    """
    Clean up common OCR errors in alliance tags
    Alliance tags are formatted as: <TAG> where TAG is 2-6 uppercase letters/numbers
    """
    if not text:
        return ""

    # Common OCR substitutions for angle brackets
    text = text.replace('~', '<')  # Tilde often misread as opening bracket
    text = text.replace(':', '>')  # Colon often misread as closing bracket
    text = text.replace(';', '>')  # Semicolon often misread as closing bracket
    text = text.replace('?', '>')  # Question mark often misread as closing bracket
    text = text.replace('=', '>')  # Equals often misread as closing bracket

    # Common character confusions
    text = text.replace('0', 'O')  # Zero to letter O in tags
    text = text.replace('8', 'B')  # 8 to B
    text = text.replace('1', 'I')  # 1 to I
    text = text.replace('5', 'S')  # 5 to S

    # Try to extract just the tag part (between < >)
    import re
    tag_match = re.search(r'<([A-Z0-9]{2,6})>', text)
    if tag_match:
        return f"<{tag_match.group(1)}>"

    # If no clean match, return first word-like sequence
    # Remove spaces and non-alphanumeric except < >
    cleaned = ''.join(c for c in text if c.isalnum() or c in '<>')

    # Ensure it has angle brackets
    if not cleaned.startswith('<'):
        cleaned = '<' + cleaned
    if not cleaned.endswith('>'):
        # Find where the tag likely ends (before space or after 6 chars)
        if len(cleaned) > 8:  # <TAG> = 5 chars minimum, 8 max
            cleaned = cleaned[:8]
        cleaned = cleaned + '>'

    return cleaned.upper()

def clean_r5_name(text):
    """
    Clean up common OCR errors in R5 names
    Names can contain letters, numbers, and some special characters
    """
    if not text:
        return ""

    # Remove extra whitespace
    text = ' '.join(text.split())

    # Common OCR character fixes
    text = text.replace('|', 'I')  # Pipe to I
    text = text.replace('0', 'O')  # Zero to O in names (context-dependent)

    # Remove obviously wrong characters
    text = text.replace(';', '')

    # Capitalize first letter if it's lowercase
    if text and text[0].islower():
        text = text[0].upper() + text[1:]

    return text.strip()

def ocr_text(image, name="region"):
    """
    Perform OCR on preprocessed image
    Returns cleaned text
    """
    # Preprocess
    processed = preprocess_for_ocr(image)

    # Perform OCR
    results = reader.readtext(processed, detail=0, paragraph=True)

    if not results:
        return ""

    # Join all detected text
    text = " ".join(results).strip()

    # Clean up based on region type
    if name == "tag":
        text = clean_alliance_tag(text)
    elif name == "r5":
        text = clean_r5_name(text)

    return text

def process_alliance_card(image_path):
    """
    Process a single alliance card screenshot
    Returns dict with alliance tag and R5 name
    """
    print(f"\nProcessing: {image_path.name}")

    # Load image
    image = cv2.imread(str(image_path))
    if image is None:
        print(f"  [ERROR] Could not load image")
        return None

    # Find red warning box anchor
    anchor = find_red_warning_box(image)
    if anchor is None:
        print(f"  [ERROR] Could not find red warning box anchor")
        return None

    anchor_x, anchor_y, anchor_w, anchor_h = anchor
    print(f"  [OK] Found anchor at ({anchor_x}, {anchor_y}) size {anchor_w}x{anchor_h}")

    # Draw anchor on debug image
    debug_image = image.copy()
    cv2.rectangle(debug_image, (anchor_x, anchor_y),
                  (anchor_x + anchor_w, anchor_y + anchor_h), (0, 0, 255), 2)

    # Get bounding boxes relative to anchor
    tag_bbox = get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h)
    r5_bbox = get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h)

    # Draw bounding boxes on debug image
    cv2.rectangle(debug_image,
                  (tag_bbox['x'], tag_bbox['y']),
                  (tag_bbox['x'] + tag_bbox['width'], tag_bbox['y'] + tag_bbox['height']),
                  (0, 255, 0), 2)
    cv2.putText(debug_image, "Alliance Tag",
                (tag_bbox['x'], tag_bbox['y'] - 10),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)

    cv2.rectangle(debug_image,
                  (r5_bbox['x'], r5_bbox['y']),
                  (r5_bbox['x'] + r5_bbox['width'], r5_bbox['y'] + r5_bbox['height']),
                  (255, 0, 0), 2)
    cv2.putText(debug_image, "R5 Name",
                (r5_bbox['x'], r5_bbox['y'] - 10),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 0, 0), 2)

    # Save debug image
    debug_path = OUTPUT_DIR / f"{image_path.stem}_debug.png"
    cv2.imwrite(str(debug_path), debug_image)
    print(f"  [DEBUG] Saved annotated image to {debug_path.name}")

    # Crop regions
    tag_crop = crop_region(image, tag_bbox)
    r5_crop = crop_region(image, r5_bbox)

    # Save cropped regions for inspection
    cv2.imwrite(str(OUTPUT_DIR / f"{image_path.stem}_tag.png"), tag_crop)
    cv2.imwrite(str(OUTPUT_DIR / f"{image_path.stem}_r5.png"), r5_crop)

    # Perform OCR
    alliance_tag = ocr_text(tag_crop, "tag")
    r5_name = ocr_text(r5_crop, "r5")

    print(f"  [OCR] Alliance Tag: '{alliance_tag}'")
    print(f"  [OCR] R5 Name: '{r5_name}'")

    return {
        'image': image_path.name,
        'tag': alliance_tag,
        'r5': r5_name,
        'anchor': anchor,
        'tag_bbox': tag_bbox,
        'r5_bbox': r5_bbox
    }

def main():
    """Process all alliance card screenshots"""
    print("=" * 70)
    print("Alliance Screenshot OCR - Anchor-Based Processing")
    print("=" * 70)

    # Find all screenshot images
    image_files = sorted(ALLIANCE_CARDS_DIR.glob("*.png"))

    if not image_files:
        print(f"\n[ERROR] No images found in {ALLIANCE_CARDS_DIR}")
        print("Place alliance card screenshots in ocr/alliance_cards/temp/")
        return

    print(f"\nFound {len(image_files)} images to process")

    # Process each image
    results = []
    successful = 0
    failed = 0

    for image_path in image_files:
        result = process_alliance_card(image_path)
        if result:
            results.append(result)
            successful += 1
        else:
            failed += 1

    # Save results to JSON
    output_data = {
        'processed_date': '2025-10-11',
        'total_images': len(image_files),
        'successful': successful,
        'failed': failed,
        'alliances': results
    }

    with open(OUTPUT_JSON, 'w', encoding='utf-8') as f:
        json.dump(output_data, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 70)
    print(f"Processing Complete:")
    print(f"  Successful: {successful}")
    print(f"  Failed: {failed}")
    print(f"  Output: {OUTPUT_JSON}")
    print(f"  Debug images: {OUTPUT_DIR}")
    print("=" * 70)

    # Display results summary
    if results:
        print("\nExtracted Data:")
        print("-" * 70)
        for r in results:
            print(f"  {r['tag']:8} - {r['r5']}")

if __name__ == '__main__':
    main()
