#!/usr/bin/env python3
"""
Create Training Labels for Real Screenshots
Manually label cropped regions to create training data from actual game screenshots

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
TRAINING_OUTPUT_DIR = OCR_DIR / "training_data_real"
TRAINING_LABELS_JSON = TRAINING_OUTPUT_DIR / "labels.json"

# Create output directory
TRAINING_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

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

def crop_region(image, bbox):
    """Crop image to specified bounding box"""
    x, y, w, h = bbox['x'], bbox['y'], bbox['width'], bbox['height']

    img_h, img_w = image.shape[:2]
    x = max(0, min(x, img_w - 1))
    y = max(0, min(y, img_h - 1))
    w = min(w, img_w - x)
    h = min(h, img_h - y)

    return image[y:y+h, x:x+w]

def label_image_interactive(image_path, sample_id):
    """Display cropped regions and get labels"""
    print(f"\n{'='*70}")
    print(f"Image: {image_path.name} (Sample ID: {sample_id})")
    print('='*70)

    image = cv2.imread(str(image_path))
    if image is None:
        print("  [ERROR] Could not load image")
        return None, None

    # Find anchor
    anchor = find_red_warning_box(image)
    if anchor is None:
        print("  [ERROR] Could not find red warning box anchor - skipping")
        return None, None

    anchor_x, anchor_y, anchor_w, anchor_h = anchor
    print(f"  [OK] Found anchor at ({anchor_x}, {anchor_y})")

    # Get bounding boxes
    tag_bbox = get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h)
    r5_bbox = get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h)

    # Crop regions
    tag_crop = crop_region(image, tag_bbox)
    r5_crop = crop_region(image, r5_bbox)

    # Scale up for better visibility
    tag_display = cv2.resize(tag_crop, None, fx=4, fy=4, interpolation=cv2.INTER_CUBIC)
    r5_display = cv2.resize(r5_crop, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)

    # Show tag crop
    cv2.imshow('Alliance Tag - What text do you see?', tag_display)
    cv2.waitKey(1)

    print("\n  [TAG REGION] Look at the window showing the alliance tag")
    tag_label = input("  Enter Alliance Tag (without brackets, e.g., MZKU): ").strip().upper()
    cv2.destroyAllWindows()

    if not tag_label:
        print("  [SKIP] No tag entered, skipping this image")
        return None, None

    # Show R5 crop
    cv2.imshow('R5 Name - What text do you see?', r5_display)
    cv2.waitKey(1)

    print("\n  [R5 REGION] Look at the window showing the R5 name")
    r5_label = input("  Enter R5 Name (e.g., Grand Puba Daddio): ").strip()
    cv2.destroyAllWindows()

    if not r5_label:
        print("  [SKIP] No R5 name entered, skipping this image")
        return None, None

    # Save cropped images
    tag_filename = f"{sample_id:04d}_tag.png"
    r5_filename = f"{sample_id:04d}_r5.png"

    cv2.imwrite(str(TRAINING_OUTPUT_DIR / tag_filename), tag_crop)
    cv2.imwrite(str(TRAINING_OUTPUT_DIR / r5_filename), r5_crop)

    print(f"  ✓ Saved: {tag_filename} (label: {tag_label})")
    print(f"  ✓ Saved: {r5_filename} (label: {r5_label})")

    tag_sample = {
        'filename': tag_filename,
        'label': tag_label,
        'type': 'alliance_tag',
        'source_image': image_path.name
    }

    r5_sample = {
        'filename': r5_filename,
        'label': r5_label,
        'type': 'r5_name',
        'source_image': image_path.name
    }

    return tag_sample, r5_sample

def main():
    """Main labeling tool"""
    print("=" * 70)
    print("Create Training Labels for Real Screenshots")
    print("=" * 70)
    print()
    print("Instructions:")
    print("  - For each screenshot, you'll see 2 zoomed-in crops:")
    print("    1. Alliance tag (small text in brackets)")
    print("    2. R5 leader name")
    print("  - Type EXACTLY what you see in the image")
    print("  - For alliance tags, enter WITHOUT brackets (e.g., MZKU not <MZKU>)")
    print("  - Press Enter without typing to skip an image")
    print("  - This will create training data for retraining the model")
    print()

    image_files = sorted(ALLIANCE_CARDS_DIR.glob("*.png"))

    if not image_files:
        print(f"\n[ERROR] No images found in {ALLIANCE_CARDS_DIR}")
        return

    print(f"\nFound {len(image_files)} images to label")

    # Load existing labels if available
    existing_labels = []
    existing_filenames = set()
    if TRAINING_LABELS_JSON.exists():
        try:
            with open(TRAINING_LABELS_JSON, 'r', encoding='utf-8') as f:
                existing_labels = json.load(f)
                for sample in existing_labels:
                    existing_filenames.add(sample['source_image'])
            print(f"Loaded {len(existing_filenames)} existing labeled images")
        except Exception as e:
            print(f"Warning: Could not load existing labels: {e}")

    all_samples = existing_labels.copy()
    sample_id = len(existing_labels) // 2  # Each image produces 2 samples
    labeled = 0
    skipped = 0

    for image_path in image_files:
        # Skip if already labeled
        if image_path.name in existing_filenames:
            use_existing = input(f"\n{image_path.name} already labeled. Use existing? (y/n): ").strip().lower()
            if use_existing == 'y':
                continue

        tag_sample, r5_sample = label_image_interactive(image_path, sample_id)

        if tag_sample and r5_sample:
            all_samples.append(tag_sample)
            all_samples.append(r5_sample)
            labeled += 1
            sample_id += 1
        else:
            skipped += 1

        # Save progress after each image
        with open(TRAINING_LABELS_JSON, 'w', encoding='utf-8') as f:
            json.dump(all_samples, f, indent=2, ensure_ascii=False)

    print("\n" + "=" * 70)
    print(f"Labeling Complete:")
    print(f"  Labeled: {labeled} images ({labeled * 2} training samples)")
    print(f"  Skipped: {skipped}")
    print(f"  Total samples: {len(all_samples)}")
    print(f"  Output: {TRAINING_OUTPUT_DIR}")
    print(f"  Labels: {TRAINING_LABELS_JSON}")
    print("=" * 70)
    print()
    print("Next step: Run the retraining script with this real-world data")

if __name__ == '__main__':
    main()
