#!/usr/bin/env python3
"""
Alliance Screenshot Processor - EasyOCR Version
Improved accuracy with anchor-based field detection and Korean language support

Features:
- EasyOCR for better Korean/mixed text recognition
- Anchor-based positioning (finds "Leader" label, calculates field locations)
- Handles variable screenshot positioning
- Supports Korean characters and special Unicode (like ᓚᘏᗢ)

Requirements:
    pip install easyocr Pillow

Version: 2.0.0
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
    from PIL import Image
except ImportError as e:
    print(f"[ERROR] Missing required package: {e}")
    print("        Install with: pip install easyocr Pillow")
    sys.exit(1)

# Configuration
PROJECT_DIR = Path(__file__).parent.parent
ALLIANCE_CARDS_DIR = PROJECT_DIR / "images" / "alliance_cards"
SERVER_RANKINGS_DIR = PROJECT_DIR / "images" / "server_rankings"
PROCESSED_DIR_NAME = "processed"
LOGS_DIR = PROJECT_DIR / "logs"

# Alliance order for matching
ALLIANCE_ORDER = [
    "UvvU", "ORCE", "nkot", "404a", "FLM", "STR8", "EPIC", "NYPR",
    "86KO", "SWBA", "MTOP", "UUSN", "FNXS", "L4TM", "NiKi"
]

# Initialize EasyOCR reader (supports Korean and English)
print("[INFO] Initializing EasyOCR (this may take a moment on first run)...")
reader = easyocr.Reader(['ko', 'en'], gpu=False)
print("[INFO] EasyOCR ready!")


class AllianceCardProcessor:
    def __init__(self):
        self.stats = {
            'r5_updates': 0,
            'rank_updates': 0,
            'power_updates': 0
        }
        self.log_entries = []

    def log(self, message):
        """Log message with timestamp"""
        timestamp = datetime.now().strftime('%H:%M:%S')
        formatted = f"[{timestamp}] {message}"
        print(formatted)
        self.log_entries.append(formatted)

    def find_anchor_points(self, ocr_results):
        """
        Find consistent anchor points in the image (like 'Leader', 'Alliance Power')
        Returns dict of {label: (x, y)} coordinates
        """
        anchors = {}

        for (bbox, text, confidence) in ocr_results:
            text_clean = text.strip().lower()

            # Key anchor points in alliance cards
            if 'leader' in text_clean and confidence > 0.5:
                x, y = bbox[0]  # Top-left corner
                anchors['leader_label'] = (x, y)

            elif 'alliance power' in text_clean and confidence > 0.5:
                x, y = bbox[0]
                anchors['power_label'] = (x, y)

            elif text_clean.startswith('<') and text_clean.endswith('>'):
                # Alliance tag like <ORCE>
                x, y = bbox[0]
                anchors['tag'] = (x, y)

        return anchors

    def extract_leader_name(self, ocr_results, anchors):
        """
        Extract leader name using anchor-based positioning
        Leader name is typically to the right of 'Leader' label
        """
        if 'leader_label' not in anchors:
            return None

        leader_x, leader_y = anchors['leader_label']

        # Leader name should be:
        # - To the right of the label (x > leader_x + 50)
        # - On roughly the same line (y within ±10 pixels)
        # - High confidence

        candidates = []
        for (bbox, text, confidence) in ocr_results:
            x, y = bbox[0]

            # Check if it's positioned like a leader name
            is_right_of_label = x > leader_x + 50
            is_same_line = abs(y - leader_y) < 15
            is_confident = confidence > 0.3

            # Skip known UI elements
            text_lower = text.strip().lower()
            is_ui_element = any(ui in text_lower for ui in [
                'alliance', 'gift', 'ppl', 'language', 'lv.', 'slogan'
            ])

            if is_right_of_label and is_same_line and is_confident and not is_ui_element:
                candidates.append({
                    'text': text.strip(),
                    'confidence': confidence,
                    'x': x
                })

        # Return the closest candidate to the label
        if candidates:
            closest = min(candidates, key=lambda c: c['x'])
            return closest['text']

        return None

    def extract_alliance_tag(self, ocr_results):
        """
        Extract alliance tag from OCR results
        Tags are in format <TAG> or [TAG]
        """
        for (bbox, text, confidence) in ocr_results:
            text_clean = text.strip()

            # Look for patterns like <ORCE>, <UvvU>, etc.
            patterns = [
                r'<([^>]+)>',
                r'\[([^\]]+)\]',
                r'<\s*([A-Za-z0-9]+)\s*>'
            ]

            for pattern in patterns:
                match = re.search(pattern, text_clean)
                if match:
                    tag = match.group(1).strip()
                    # Check against known alliance tags
                    for known_tag in ALLIANCE_ORDER:
                        if tag.upper() == known_tag.upper():
                            return known_tag

        return None

    def extract_alliance_power(self, ocr_results, anchors):
        """
        Extract alliance power using anchor-based positioning
        Power is to the right of 'Alliance Power' label
        """
        if 'power_label' not in anchors:
            return None

        power_x, power_y = anchors['power_label']

        # Power should be to the right of the label
        for (bbox, text, confidence) in ocr_results:
            x, y = bbox[0]

            is_right_of_label = x > power_x + 100
            is_same_line = abs(y - power_y) < 15

            if is_right_of_label and is_same_line:
                # Extract numbers and commas
                text_clean = re.sub(r'[^0-9,]', '', text)
                if text_clean:
                    # Remove commas and convert to int
                    try:
                        power = int(text_clean.replace(',', ''))
                        return power
                    except ValueError:
                        continue

        return None

    def process_alliance_card(self, image_path):
        """Process a single alliance card screenshot"""
        self.log(f"Processing alliance card: {image_path.name}")

        # Read image with EasyOCR
        results = reader.readtext(str(image_path), detail=1)

        # Find anchor points
        anchors = self.find_anchor_points(results)

        # Extract alliance tag
        tag = self.extract_alliance_tag(results)
        if not tag:
            self.log(f"  ⚠️  Could not extract alliance tag from {image_path.name}")
            return False

        # Extract leader name using anchor-based method
        leader_name = self.extract_leader_name(results, anchors)
        if not leader_name:
            self.log(f"  ⚠️  Could not extract leader name for {tag}")
            return False

        self.log(f"  ✓ Extracted: {tag} - Leader: {leader_name}")

        # Update JSON files
        self.update_alliance_r5(tag, leader_name)

        # Move to processed folder
        processed_dir = image_path.parent / PROCESSED_DIR_NAME
        processed_dir.mkdir(exist_ok=True)
        shutil.move(str(image_path), str(processed_dir / image_path.name))
        self.log(f"  → Moved to processed folder")

        return True

    def update_alliance_r5(self, tag, leader_name):
        """Update R5 leader in alliances.json and signature-history.json"""
        # Update alliances.json
        alliances_file = PROJECT_DIR / "data" / "alliances.json"

        with open(alliances_file, 'r', encoding='utf-8') as f:
            alliances = json.load(f)

        for alliance in alliances:
            if alliance['tag'] == tag:
                # Update R5 name (handle both string and object formats)
                if isinstance(alliance.get('r5'), dict):
                    alliance['r5']['name'] = leader_name
                else:
                    alliance['r5'] = leader_name

                self.log(f"  📝 Updated alliances.json: {tag} R5 = {leader_name}")
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
                # Update current R5 in history
                for r5_entry in alliance['r5History']:
                    if r5_entry.get('current', False):
                        r5_entry['r5Name'] = leader_name
                        self.log(f"  📝 Updated signature-history.json: {tag} R5 = {leader_name}")
                        break
                break

        with open(sig_file, 'w', encoding='utf-8') as f:
            json.dump(sig_data, f, indent=2, ensure_ascii=False)

    def run(self):
        """Main processing loop"""
        self.log("=" * 70)
        self.log("Server 1586 - Alliance Screenshot Processor (EasyOCR)")
        self.log("=" * 70)
        self.log("")

        # Process alliance cards
        alliance_cards = sorted(ALLIANCE_CARDS_DIR.glob("2025-*.png"))

        if not alliance_cards:
            self.log(f"[INFO] No alliance card screenshots found in {ALLIANCE_CARDS_DIR}")
            return

        self.log(f"[1/1] Processing {len(alliance_cards)} Alliance Card Screenshots...")
        self.log("")

        processed_count = 0
        for card_path in alliance_cards:
            if self.process_alliance_card(card_path):
                processed_count += 1

        self.log("")
        self.log(f"Alliance cards processed: {processed_count}/{len(alliance_cards)}")
        self.log("")

        # Summary
        self.log("=" * 70)
        self.log("Processing Complete!")
        self.log("=" * 70)
        self.log(f"R5 updates: {self.stats['r5_updates']}")
        self.log("")

        # Save log
        self.save_log()

        self.log("=" * 70)
        self.log("Next Steps:")
        self.log("=" * 70)
        self.log("✓ Data files updated (alliances.json, signature-history.json)")
        self.log("⚠️  Review changes before deploying to production")
        self.log("")
        self.log("To deploy:")
        self.log("  1. Review: git diff data/")
        self.log("  2. Commit: git add data/*.json && git commit -m 'data: Update R5 leaders'")
        self.log("  3. Deploy: python scripts/deploy-ftp.py")
        self.log("")

    def save_log(self):
        """Save processing log to file"""
        LOGS_DIR.mkdir(exist_ok=True)
        timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
        log_file = LOGS_DIR / f"screenshot_processing_easyocr_{timestamp}.log"

        with open(log_file, 'w', encoding='utf-8') as f:
            f.write('\n'.join(self.log_entries))

        self.log(f"Log saved: {log_file}")


if __name__ == "__main__":
    processor = AllianceCardProcessor()
    processor.run()
