#!/usr/bin/env python3
"""
Alliance Screenshot OCR - Manual Bounding Boxes with EasyOCR
Uses precise bounding boxes from manual calibration with EasyOCR recognition

Version: 2.1.0
Date: 2025-10-12
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
OUTPUT_DIR = OCR_DIR / "debug_ocr_manual"
OUTPUT_JSON = PROJECT_ROOT / "data" / "alliances-ocr-manual.json"

# Create output directory
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Initialize EasyOCR reader
reader = easyocr.Reader(['en'], gpu=False)

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
    """Calculate bounding box for alliance tag based on manual calibration"""
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
    """Calculate bounding box for R5 name based on manual calibration"""
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

def crop_region(image, bbox):
    """Crop image to specified bounding box"""
    x, y, w, h = bbox['x'], bbox['y'], bbox['width'], bbox['height']

    img_h, img_w = image.shape[:2]
    x = max(0, min(x, img_w - 1))
    y = max(0, min(y, img_h - 1))
    w = min(w, img_w - x)
    h = min(h, img_h - y)

    return image[y:y+h, x:x+w]

def preprocess_for_ocr(image):
    """Preprocess cropped region for better OCR accuracy"""
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    # Scale up small images for better OCR
    if gray.shape[0] < 32 or gray.shape[1] < 100:
        scale_factor = max(2.0, 32.0 / gray.shape[0])
        new_width = int(gray.shape[1] * scale_factor)
        new_height = int(gray.shape[0] * scale_factor)
        gray = cv2.resize(gray, (new_width, new_height), interpolation=cv2.INTER_CUBIC)

    # Apply strong CLAHE for better contrast
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8,8))
    enhanced = clahe.apply(gray)

    # Use adaptive thresholding for varied backgrounds
    thresh = cv2.adaptiveThreshold(
        enhanced, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY, 11, 2
    )

    # Ensure text is black on white background
    # Check if more pixels are dark (text) than light (background)
    if np.mean(thresh) < 127:
        thresh = cv2.bitwise_not(thresh)

    return thresh

def clean_alliance_tag(text):
    """Clean up OCR errors in alliance tags"""
    if not text:
        return ""

    # Common OCR character substitutions for brackets and letters
    text = text.replace('~', '<').replace('-', '<').replace('_', '<')
    text = text.replace(':', '>').replace(';', '>').replace('?', '>').replace('=', '>')
    text = text.replace('|', 'I').replace('1', 'I').replace('!', 'I')
    text = text.replace('0', 'O')

    # Try to extract just the tag part with brackets
    import re
    tag_match = re.search(r'[<~\-_]([A-Z0-9]{2,6})[>:;?=]', text.upper())
    if tag_match:
        return f"<{tag_match.group(1)}>"

    # Clean and ensure brackets
    cleaned = ''.join(c for c in text.upper() if c.isalnum() or c in '<>')
    if not cleaned.startswith('<'):
        cleaned = '<' + cleaned
    if not cleaned.endswith('>'):
        if len(cleaned) > 8:
            cleaned = cleaned[:8]
        cleaned = cleaned + '>'

    return cleaned

def clean_r5_name(text):
    """Clean up OCR errors in R5 names"""
    if not text:
        return ""

    text = ' '.join(text.split())
    text = text.replace('|', 'I').replace(';', '')

    if text and text[0].islower():
        text = text[0].upper() + text[1:]

    return text.strip()

def ocr_text(image, name="region"):
    """Perform OCR with EasyOCR"""
    processed = preprocess_for_ocr(image)
    results = reader.readtext(processed, detail=0, paragraph=True)

    if not results:
        return ""

    text = " ".join(results).strip()

    if name == "tag":
        text = clean_alliance_tag(text)
    elif name == "r5":
        text = clean_r5_name(text)

    return text

def process_alliance_card(image_path):
    """Process a single alliance card screenshot"""
    print(f"\nProcessing: {image_path.name}")

    image = cv2.imread(str(image_path))
    if image is None:
        print(f"  [ERROR] Could not load image")
        return None

    anchor = find_red_warning_box(image)
    if anchor is None:
        print(f"  [ERROR] Could not find red warning box anchor")
        return None

    anchor_x, anchor_y, anchor_w, anchor_h = anchor
    print(f"  [OK] Found anchor at ({anchor_x}, {anchor_y}) size {anchor_w}x{anchor_h}")

    # Draw debug image
    debug_image = image.copy()
    cv2.rectangle(debug_image, (anchor_x, anchor_y),
                  (anchor_x + anchor_w, anchor_y + anchor_h), (0, 0, 255), 2)

    # Get bounding boxes
    tag_bbox = get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h)
    r5_bbox = get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h)

    # Draw bounding boxes
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

    debug_path = OUTPUT_DIR / f"{image_path.stem}_debug.png"
    cv2.imwrite(str(debug_path), debug_image)
    print(f"  [DEBUG] Saved annotated image to {debug_path.name}")

    # Crop regions
    tag_crop = crop_region(image, tag_bbox)
    r5_crop = crop_region(image, r5_bbox)

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
    print("Alliance Screenshot OCR - Manual Bounding Boxes + EasyOCR")
    print("=" * 70)
    print()

    image_files = sorted(ALLIANCE_CARDS_DIR.glob("*.png"))

    if not image_files:
        print(f"\n[ERROR] No images found in {ALLIANCE_CARDS_DIR}")
        return

    print(f"\nFound {len(image_files)} images to process")

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

    output_data = {
        'processed_date': '2025-10-12',
        'model': 'EasyOCR with Manual Bounding Boxes',
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

    if results:
        print("\nExtracted Data:")
        print("-" * 70)
        for r in results:
            print(f"  {r['tag']:12} - {r['r5']}")

if __name__ == '__main__':
    main()
