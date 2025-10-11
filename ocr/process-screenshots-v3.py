#!/usr/bin/env python3
"""
Alliance Screenshot Processor - Version 3.0 (Optimized with Cropping)
Advanced image preprocessing with precise region extraction

Features:
- EasyOCR with Korean language support
- Aggressive image preprocessing (crop, grayscale, contrast, threshold)
- Anchor-based positioning for variable screenshot locations
- Multi-pass OCR with different preprocessing strategies
- Debug mode to save processed images for inspection

Requirements:
    pip install easyocr Pillow opencv-python-headless

Version: 3.0.0
Date: 2025-10-10
"""

import sys
import os
from pathlib import Path
from datetime import datetime
import json
import re
import shutil

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

try:
    import easyocr
    from PIL import Image, ImageEnhance, ImageFilter, ImageOps
    import cv2
    import numpy as np
except ImportError as e:
    print(f"[ERROR] Missing required package: {e}")
    print("        Install with: pip install easyocr Pillow opencv-python-headless")
    sys.exit(1)

# Configuration
PROJECT_DIR = Path(__file__).parent.parent
ALLIANCE_CARDS_DIR = PROJECT_DIR / "images" / "alliance_cards"
PROCESSED_DIR_NAME = "processed"
LOGS_DIR = PROJECT_DIR / "logs"
DEBUG_DIR = PROJECT_DIR / "debug_ocr"  # For saving preprocessed images

DEBUG_MODE = True  # Set to True to save preprocessed images for inspection

# Alliance order for matching
ALLIANCE_ORDER = [
    "UvvU", "ORCE", "nkot", "404a", "FLM", "STR8", "EPIC", "NYPR",
    "86KO", "SWBA", "MTOP", "UUSN", "FNXS", "L4TM", "NiKi"
]

# Initialize EasyOCR readers - English-only for better accuracy, Korean+English as fallback
print("[INFO] Initializing EasyOCR (English-only for primary OCR)...")
reader_en = easyocr.Reader(['en'], gpu=False, verbose=False)
print("[INFO] Initializing EasyOCR (Korean+English for fallback)...")
reader_ko = easyocr.Reader(['ko', 'en'], gpu=False, verbose=False)
print("[INFO] EasyOCR ready!")


def preprocess_leader_region(image, leader_bbox, debug_path=None):
    """
    Advanced preprocessing for outlined game text
    Handles white text with dark outlines better
    """
    # Expand bounding box to include full leader name
    x, y = leader_bbox[0]
    crop_box = (
        x + 50,  # Start after "Leader" text
        y - 3,   # Slight expansion up
        x + 290, # Wide enough for full names, but stops before "Lv." text
        y + 35   # Tall enough for text
    )

    # Ensure coordinates are within image bounds
    crop_box = (
        max(0, crop_box[0]),
        max(0, crop_box[1]),
        min(image.width, crop_box[2]),
        min(image.height, crop_box[3])
    )

    cropped = image.crop(crop_box)
    cv_img = cv2.cvtColor(np.array(cropped), cv2.COLOR_RGB2BGR)
    gray = cv2.cvtColor(cv_img, cv2.COLOR_BGR2GRAY)

    # Strategy: Extract the bright core of the text, ignoring dark outlines

    # 1. Use bilateral filter to smooth while preserving edges
    smooth = cv2.bilateralFilter(gray, 5, 50, 50)

    # 2. Find bright text (white/light colored text core)
    # Use high threshold to get only the bright center, not dark outlines
    _, bright_text = cv2.threshold(smooth, 180, 255, cv2.THRESH_BINARY)

    # 3. Dilate slightly to fill gaps in text
    kernel_dilate = np.ones((2,2), np.uint8)
    dilated = cv2.dilate(bright_text, kernel_dilate, iterations=1)

    # 4. Invert for OCR (black text on white background)
    inverted = cv2.bitwise_not(dilated)

    # 5. Upscale 4x for better OCR
    height, width = inverted.shape
    upscaled = cv2.resize(inverted, (width * 4, height * 4), interpolation=cv2.INTER_CUBIC)

    # Save debug image
    if debug_path and DEBUG_MODE:
        cv2.imwrite(str(debug_path), upscaled)

    return Image.fromarray(upscaled)


def preprocess_tag_region(image, tag_bbox, debug_path=None):
    """
    Preprocess alliance tag region (between < >)
    """
    x, y = tag_bbox[0]
    # Tag is narrow, just alliance code
    crop_box = (
        max(0, x - 10),
        max(0, y - 5),
        min(image.width, x + 100),
        min(image.height, y + 30)
    )

    cropped = image.crop(crop_box)

    # Convert to OpenCV format
    cv_img = cv2.cvtColor(np.array(cropped), cv2.COLOR_RGB2GRAY)

    # Increase contrast
    enhanced = cv2.convertScaleAbs(cv_img, alpha=2.5, beta=0)

    # Threshold
    _, thresh = cv2.threshold(enhanced, 140, 255, cv2.THRESH_BINARY)

    # Upscale 3x for better OCR
    upscaled = cv2.resize(thresh, (thresh.shape[1] * 3, thresh.shape[0] * 3), interpolation=cv2.INTER_CUBIC)

    if debug_path and DEBUG_MODE:
        cv2.imwrite(str(debug_path), upscaled)

    return Image.fromarray(upscaled)


class AllianceCardProcessor:
    def __init__(self):
        self.stats = {
            'r5_updates': 0,
            'processed': 0,
            'failed': 0
        }
        self.log_entries = []

        if DEBUG_MODE:
            DEBUG_DIR.mkdir(exist_ok=True)

    def log(self, message):
        """Log message with timestamp"""
        timestamp = datetime.now().strftime('%H:%M:%S')
        formatted = f"[{timestamp}] {message}"
        print(formatted)
        self.log_entries.append(formatted)

    def find_leader_label(self, ocr_results):
        """Find the 'Leader' label to use as anchor point"""
        for (bbox, text, confidence) in ocr_results:
            text_clean = text.strip().lower()
            if 'leader' in text_clean and confidence > 0.3:
                return bbox
        return None

    def has_korean_chars(self, text):
        """Check if text contains Korean characters"""
        return any('\uac00' <= c <= '\ud7a3' for c in text)

    def find_tag_region(self, ocr_results):
        """Find alliance tag region (text between < >)"""
        for (bbox, text, confidence) in ocr_results:
            text_clean = text.strip()
            # Look for patterns like <ORCE>, <UvvU>, etc.
            if ('<' in text_clean or '>' in text_clean) and confidence > 0.3:
                return bbox
        return None

    def extract_alliance_tag(self, image, debug_name=None):
        """
        Extract alliance tag using initial full-image OCR + targeted cropping
        """
        # First pass: full image OCR to find tag region (English only - tags are English)
        initial_results = reader_en.readtext(np.array(image), detail=1)

        tag_bbox = self.find_tag_region(initial_results)
        if not tag_bbox:
            return None

        # Second pass: preprocess tag region and re-OCR (English only)
        debug_path = DEBUG_DIR / f"{debug_name}_tag.png" if debug_name else None
        tag_img = preprocess_tag_region(image, tag_bbox, debug_path)

        tag_text = reader_en.readtext(np.array(tag_img), detail=0, paragraph=True)
        if not tag_text:
            return None

        # Extract tag from results
        combined_text = ' '.join(tag_text)

        # Try different patterns
        patterns = [
            r'<([A-Za-z0-9]+)>',
            r'\[([A-Za-z0-9]+)\]',
            r'([A-Z][A-Za-z0-9]{2,4})\s',
            r'([A-Z0-9]{3,5})'
        ]

        for pattern in patterns:
            match = re.search(pattern, combined_text)
            if match:
                tag = match.group(1).strip()
                # Check against known tags
                for known_tag in ALLIANCE_ORDER:
                    if tag.upper() == known_tag.upper():
                        return known_tag

        return None

    def extract_leader_name(self, image, debug_name=None):
        """
        Extract leader name using anchor-based cropping and multi-pass OCR
        """
        # First pass: full image OCR to find "Leader" label (English only for label)
        initial_results = reader_en.readtext(np.array(image), detail=1)

        leader_bbox = self.find_leader_label(initial_results)
        if not leader_bbox:
            self.log("    ⚠️  Could not find 'Leader' label anchor")
            return None

        # Second pass: preprocess leader region and re-OCR
        debug_path = DEBUG_DIR / f"{debug_name}_leader.png" if debug_name else None
        leader_img = preprocess_leader_region(image, leader_bbox, debug_path)

        # Strategy: Use word-by-word detection and take leftmost valid word
        # This avoids paragraph mode combining "Echo" + "n" into "Echon"
        leader_results = reader_en.readtext(np.array(leader_img), detail=1, paragraph=False)

        candidates = []
        for (bbox, text, confidence) in leader_results:
            text_clean = text.strip()
            text_lower = text_clean.lower()

            self.log(f"    [DEBUG] OCR word: '{text_clean}' (conf: {confidence:.2f})")

            # Skip very short text (but allow 2 chars for names like "JT")
            if len(text_clean) < 2:
                self.log(f"    [DEBUG] Skipped (too short)")
                continue

            # Skip UI elements (more comprehensive list)
            is_ui = any(ui in text_lower for ui in [
                'alliance', 'gift', 'ppl', 'language', 'lv.', 'lv', 'slogan',
                'leader', 'power', 'member', 'luyea'
            ])

            if is_ui:
                self.log(f"    [DEBUG] Skipped (UI element)")
                continue

            # Skip pure numbers
            if text_clean.replace(',', '').replace('.', '').isdigit():
                self.log(f"    [DEBUG] Skipped (pure number)")
                continue

            # Skip if it's just "Lv" or level indicators
            if re.match(r'^Lv\.?\s*\d*$', text_clean, re.IGNORECASE):
                self.log(f"    [DEBUG] Skipped (level indicator)")
                continue

            if confidence > 0.05:  # Lower threshold for preprocessed images
                self.log(f"    [DEBUG] Added as candidate")
                candidates.append({
                    'text': text_clean,
                    'confidence': confidence,
                    'x': bbox[0][0]  # X position
                })

        self.log(f"    [DEBUG] Total candidates: {len(candidates)}")

        if candidates:
            # Return leftmost high-confidence candidate (leader name is usually first)
            candidates.sort(key=lambda c: c['x'])
            self.log(f"    [DEBUG] Selected: '{candidates[0]['text']}'")
            return candidates[0]['text']

        # Fallback: Try Korean+English if we got nothing
        self.log("    🔄 Trying Korean+English OCR...")
        leader_results_ko = reader_ko.readtext(np.array(leader_img), detail=1, paragraph=False)

        candidates_ko = []
        for (bbox, text, confidence) in leader_results_ko:
            text_clean = text.strip()
            text_lower = text_clean.lower()

            if len(text_clean) < 2:
                continue

            is_ui = any(ui in text_lower for ui in [
                'alliance', 'gift', 'ppl', 'language', 'lv.', 'lv', 'slogan',
                'leader', 'power', 'member', 'luyea'
            ])

            if text_clean.replace(',', '').replace('.', '').isdigit():
                continue

            if re.match(r'^Lv\.?\s*\d*$', text_clean, re.IGNORECASE):
                continue

            if not is_ui and confidence > 0.05:  # Lower threshold for preprocessed images
                candidates_ko.append({
                    'text': text_clean,
                    'confidence': confidence,
                    'x': bbox[0][0]
                })

        if candidates_ko:
            candidates_ko.sort(key=lambda c: c['x'])
            return candidates_ko[0]['text']

        return None

    def process_alliance_card(self, image_path):
        """Process a single alliance card screenshot"""
        self.log(f"Processing: {image_path.name}")

        try:
            image = Image.open(image_path)
        except Exception as e:
            self.log(f"  ✗ Failed to open image: {e}")
            self.stats['failed'] += 1
            return False

        debug_name = image_path.stem if DEBUG_MODE else None

        # Extract alliance tag
        tag = self.extract_alliance_tag(image, debug_name)
        if not tag:
            self.log(f"  ✗ Could not extract alliance tag")
            self.stats['failed'] += 1
            return False

        self.log(f"  ✓ Alliance: {tag}")

        # Extract leader name
        leader_name = self.extract_leader_name(image, debug_name)
        if not leader_name:
            self.log(f"  ✗ Could not extract leader name for {tag}")
            self.stats['failed'] += 1
            return False

        self.log(f"  ✓ Leader: {leader_name}")

        # Update JSON files
        self.update_alliance_r5(tag, leader_name)

        # Move to processed folder
        processed_dir = image_path.parent / PROCESSED_DIR_NAME
        processed_dir.mkdir(exist_ok=True)
        shutil.move(str(image_path), str(processed_dir / image_path.name))
        self.log(f"  → Moved to processed")

        self.stats['processed'] += 1
        return True

    def update_alliance_r5(self, tag, leader_name):
        """Update R5 leader in alliances.json and signature-history.json"""
        # Update alliances.json
        alliances_file = PROJECT_DIR / "data" / "alliances.json"

        with open(alliances_file, 'r', encoding='utf-8') as f:
            alliances = json.load(f)

        for alliance in alliances:
            if alliance['tag'] == tag:
                if isinstance(alliance.get('r5'), dict):
                    alliance['r5']['name'] = leader_name
                else:
                    alliance['r5'] = leader_name

                self.stats['r5_updates'] += 1
                break

        with open(alliances_file, 'w', encoding='utf-8') as f:
            json.dump(alliances, f, indent=4, ensure_ascii=False)

        # Update signature-history.json
        sig_file = PROJECT_DIR / "data" / "signature-history.json"

        with open(sig_file, 'r', encoding='utf-8') as f:
            sig_data = json.load(f)

        for alliance in sig_data['alliances']:
            if alliance['tag'] == tag:
                for r5_entry in alliance['r5History']:
                    if r5_entry.get('current', False):
                        r5_entry['r5Name'] = leader_name
                        break
                break

        with open(sig_file, 'w', encoding='utf-8') as f:
            json.dump(sig_data, f, indent=2, ensure_ascii=False)

    def run(self):
        """Main processing loop"""
        self.log("=" * 70)
        self.log("Alliance Screenshot Processor v3.0 (Optimized)")
        self.log("=" * 70)
        self.log("")

        if DEBUG_MODE:
            self.log(f"[DEBUG] Preprocessed images will be saved to: {DEBUG_DIR}")
            self.log("")

        # Process alliance cards
        alliance_cards = sorted(ALLIANCE_CARDS_DIR.glob("2025-*.png"))

        if not alliance_cards:
            self.log(f"[INFO] No screenshots found in {ALLIANCE_CARDS_DIR}")
            return

        self.log(f"Found {len(alliance_cards)} alliance card screenshots")
        self.log("")

        for card_path in alliance_cards:
            self.process_alliance_card(card_path)
            self.log("")

        # Summary
        self.log("=" * 70)
        self.log("Processing Complete!")
        self.log("=" * 70)
        self.log(f"Processed: {self.stats['processed']}/{len(alliance_cards)}")
        self.log(f"Failed:    {self.stats['failed']}/{len(alliance_cards)}")
        self.log(f"R5 Updates: {self.stats['r5_updates']}")

        if DEBUG_MODE:
            self.log(f"Debug images: {DEBUG_DIR}")

        self.log("")

        # Save log
        self.save_log()

        self.log("=" * 70)
        self.log("Next Steps:")
        self.log("=" * 70)
        self.log("1. Review debug images in debug_ocr/ to see preprocessing")
        self.log("2. Check git diff data/ to verify extracted names")
        self.log("3. Deploy: python scripts/deploy-ftp.py")
        self.log("")

    def save_log(self):
        """Save processing log"""
        LOGS_DIR.mkdir(exist_ok=True)
        timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
        log_file = LOGS_DIR / f"ocr_processing_v3_{timestamp}.log"

        with open(log_file, 'w', encoding='utf-8') as f:
            f.write('\n'.join(self.log_entries))

        self.log(f"Log saved: {log_file}")


if __name__ == "__main__":
    processor = AllianceCardProcessor()
    processor.run()
