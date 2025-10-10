"""
Server 1586 - Screenshot Processing Script
Extracts alliance data from screenshots and updates JSON files

This script:
1. Processes alliance card screenshots to extract R5 leader names
2. Processes server ranking screenshots to extract power and rank data
3. Updates alliances.json and signature-history.json with extracted data
4. Moves processed images to 'processed' folder
5. Generates detailed log file

Requirements:
    pip install pytesseract pillow opencv-python

Note: Tesseract OCR must be installed on system
      Download from: https://github.com/tesseract-ocr/tesseract

Version: 1.0.0
Date: 2025-10-10
"""

import os
import sys
import json
import shutil
from datetime import datetime
from pathlib import Path
import re

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

# Try to import OCR libraries
try:
    from PIL import Image
    import pytesseract
except ImportError:
    print("ERROR: Required libraries not installed")
    print("Please run: pip install pytesseract pillow opencv-python")
    sys.exit(1)

# Configuration
SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent
ALLIANCE_CARDS_DIR = PROJECT_ROOT / "images" / "alliance_cards"
SERVER_RANKINGS_DIR = PROJECT_ROOT / "images" / "server_rankings"
PROCESSED_CARDS_DIR = ALLIANCE_CARDS_DIR / "processed"
PROCESSED_RANKINGS_DIR = SERVER_RANKINGS_DIR / "processed"
DATA_DIR = PROJECT_ROOT / "data"
LOG_DIR = PROJECT_ROOT / "logs"

ALLIANCES_JSON = DATA_DIR / "alliances.json"
SIGNATURE_HISTORY_JSON = DATA_DIR / "signature-history.json"

# Alliance tag mapping (order in top 20)
ALLIANCE_ORDER = [
    "UvvU", "ORCE", "nkot", "404a", "FLM",
    "STR8", "EPIC", "NYPR", "86KO", "SWBA",
    "MTOP", "UUSN", "FNXS", "L4TM", "NiKi"
]

# Tesseract configuration
# Update this path if tesseract is installed elsewhere
TESSERACT_CMD = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

# Optimized OCR configuration for Last War game text
OCR_CONFIG = r'--oem 3 --psm 6 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]()_-.,:/| '
OCR_CONFIG_TABLE = r'--oem 3 --psm 6'  # For ranking tables

class ScreenshotProcessor:
    def __init__(self):
        self.log_messages = []
        self.timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        self.changes_made = {
            "r5_updates": [],
            "rank_updates": [],
            "power_updates": []
        }

        # Ensure directories exist
        PROCESSED_CARDS_DIR.mkdir(parents=True, exist_ok=True)
        PROCESSED_RANKINGS_DIR.mkdir(parents=True, exist_ok=True)
        LOG_DIR.mkdir(parents=True, exist_ok=True)

        # Configure tesseract
        if os.path.exists(TESSERACT_CMD):
            pytesseract.pytesseract.tesseract_cmd = TESSERACT_CMD
        else:
            self.log("WARNING: Tesseract not found at default path")
            self.log("Please install Tesseract OCR or update TESSERACT_CMD path")

    def log(self, message):
        """Add message to log"""
        timestamp = datetime.now().strftime("%H:%M:%S")
        log_msg = f"[{timestamp}] {message}"
        print(log_msg)
        self.log_messages.append(log_msg)

    def save_log(self):
        """Save log file"""
        log_file = LOG_DIR / f"screenshot_processing_{self.timestamp}.log"
        with open(log_file, 'w', encoding='utf-8') as f:
            f.write("=" * 70 + "\n")
            f.write("Server 1586 - Screenshot Processing Log\n")
            f.write(f"Processed: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write("=" * 70 + "\n\n")

            for msg in self.log_messages:
                f.write(msg + "\n")

            f.write("\n" + "=" * 70 + "\n")
            f.write("Summary of Changes:\n")
            f.write("=" * 70 + "\n\n")

            if self.changes_made["r5_updates"]:
                f.write("R5 Leader Updates:\n")
                for change in self.changes_made["r5_updates"]:
                    f.write(f"  • {change}\n")
                f.write("\n")

            if self.changes_made["rank_updates"]:
                f.write("Rank Updates:\n")
                for change in self.changes_made["rank_updates"]:
                    f.write(f"  • {change}\n")
                f.write("\n")

            if self.changes_made["power_updates"]:
                f.write("Power Updates:\n")
                for change in self.changes_made["power_updates"]:
                    f.write(f"  • {change}\n")
                f.write("\n")

            if not any(self.changes_made.values()):
                f.write("No changes were made (data already up to date)\n\n")

        self.log(f"Log saved: {log_file}")
        return log_file

    def extract_alliance_tag(self, text):
        """Extract alliance tag from OCR text"""
        # Look for pattern like <TAG> or [TAG]
        patterns = [
            r'<([^>]+)>',
            r'\[([^\]]+)\]',
            r'<\s*([A-Za-z0-9]+)\s*>'
        ]

        for pattern in patterns:
            match = re.search(pattern, text)
            if match:
                tag = match.group(1).strip()
                # Normalize tag
                for known_tag in ALLIANCE_ORDER:
                    if tag.upper() == known_tag.upper():
                        return known_tag

        return None

    def extract_leader_name(self, text):
        """Extract leader name from alliance card OCR text"""
        lines = text.split('\n')

        for i, line in enumerate(lines):
            if 'Leader' in line or 'leader' in line:
                # Leader name is usually on the same line or next line
                # Look for non-English characters or names
                if i + 1 < len(lines):
                    name_line = lines[i + 1].strip()
                    if name_line and len(name_line) > 1:
                        return name_line

                # Try same line after "Leader"
                parts = line.split('Leader')
                if len(parts) > 1:
                    name = parts[1].strip()
                    if name and len(name) > 1:
                        return name

        return None

    def extract_power(self, text):
        """Extract power value from text"""
        # Look for large numbers (power values)
        pattern = r'(\d{1,3}(?:,\d{3})*(?:\.\d{3})*)'
        matches = re.findall(pattern, text)

        for match in matches:
            # Remove commas/periods and check if it's a valid power number
            num_str = match.replace(',', '').replace('.', '')
            if len(num_str) >= 9:  # Power values are typically 9+ digits
                try:
                    return int(num_str)
                except ValueError:
                    continue

        return None

    def process_alliance_card(self, image_path):
        """Process a single alliance card screenshot"""
        self.log(f"Processing alliance card: {image_path.name}")

        try:
            # Load image
            image = Image.open(image_path)

            # Perform OCR with optimized config
            text = pytesseract.image_to_string(image, config=OCR_CONFIG)

            # Extract alliance tag
            tag = self.extract_alliance_tag(text)
            if not tag:
                self.log(f"  ⚠️  Could not extract alliance tag from {image_path.name}")
                self.log(f"      OCR Text: {text[:100]}...")
                return False

            # Extract leader name
            leader_name = self.extract_leader_name(text)
            if not leader_name:
                self.log(f"  ⚠️  Could not extract leader name for {tag}")
                return False

            self.log(f"  ✓ Extracted: {tag} - Leader: {leader_name}")

            # Update JSON files
            self.update_r5_in_json(tag, leader_name)

            # Move to processed folder
            dest = PROCESSED_CARDS_DIR / image_path.name
            shutil.move(str(image_path), str(dest))
            self.log(f"  → Moved to processed folder")

            return True

        except Exception as e:
            self.log(f"  ❌ Error processing {image_path.name}: {e}")
            return False

    def process_server_ranking(self, image_path):
        """Process server ranking screenshot"""
        self.log(f"Processing server ranking: {image_path.name}")

        try:
            # Load image
            image = Image.open(image_path)

            # Perform OCR with optimized config for tables
            text = pytesseract.image_to_string(image, config=OCR_CONFIG_TABLE)

            lines = text.split('\n')
            alliances_found = 0

            for line in lines:
                # Look for alliance tags
                for tag in ALLIANCE_ORDER:
                    if tag in line or tag.lower() in line.lower():
                        # Try to extract rank and power from this line
                        # Rank is usually at the start
                        rank_match = re.match(r'^\s*(\d+)', line)
                        rank = int(rank_match.group(1)) if rank_match else None

                        # Extract power
                        power = self.extract_power(line)

                        if rank or power:
                            self.log(f"  ✓ Found {tag}: Rank={rank}, Power={power}")
                            if rank:
                                self.update_rank_in_json(tag, rank)
                            if power:
                                self.update_power_in_json(tag, power)
                            alliances_found += 1

            if alliances_found == 0:
                self.log(f"  ⚠️  No alliance data extracted from {image_path.name}")
                self.log(f"      OCR Text preview: {text[:200]}...")
                return False

            # Move to processed folder
            dest = PROCESSED_RANKINGS_DIR / image_path.name
            shutil.move(str(image_path), str(dest))
            self.log(f"  → Moved to processed folder")

            return True

        except Exception as e:
            self.log(f"  ❌ Error processing {image_path.name}: {e}")
            return False

    def update_r5_in_json(self, tag, leader_name):
        """Update R5 leader name in both alliances.json and signature-history.json"""
        # Update alliances.json
        if ALLIANCES_JSON.exists():
            with open(ALLIANCES_JSON, 'r', encoding='utf-8') as f:
                alliances = json.load(f)

            for alliance in alliances:
                if alliance['tag'] == tag:
                    old_r5 = alliance.get('r5', {})
                    if isinstance(old_r5, dict):
                        old_name = old_r5.get('name', 'Unknown')
                    else:
                        old_name = old_r5

                    if old_name != leader_name:
                        # Update r5 name
                        if isinstance(alliance['r5'], dict):
                            alliance['r5']['name'] = leader_name
                        else:
                            alliance['r5'] = leader_name

                        self.changes_made["r5_updates"].append(
                            f"{tag}: {old_name} → {leader_name}"
                        )
                        self.log(f"  📝 Updated alliances.json: {tag} R5 = {leader_name}")

            with open(ALLIANCES_JSON, 'w', encoding='utf-8') as f:
                json.dump(alliances, f, indent=2, ensure_ascii=False)

        # Update signature-history.json
        if SIGNATURE_HISTORY_JSON.exists():
            with open(SIGNATURE_HISTORY_JSON, 'r', encoding='utf-8') as f:
                sig_history = json.load(f)

            for alliance in sig_history.get('alliances', []):
                if alliance['tag'] == tag:
                    # Find current R5
                    current_r5 = None
                    for r5 in alliance.get('r5History', []):
                        if r5.get('current', False):
                            current_r5 = r5
                            break

                    if current_r5:
                        old_name = current_r5.get('r5Name', 'Unknown')
                        if old_name != leader_name:
                            current_r5['r5Name'] = leader_name
                            self.log(f"  📝 Updated signature-history.json: {tag} R5 = {leader_name}")

            with open(SIGNATURE_HISTORY_JSON, 'w', encoding='utf-8') as f:
                json.dump(sig_history, f, indent=2, ensure_ascii=False)

    def update_rank_in_json(self, tag, rank):
        """Update alliance rank in alliances.json"""
        if not ALLIANCES_JSON.exists():
            return

        with open(ALLIANCES_JSON, 'r', encoding='utf-8') as f:
            alliances = json.load(f)

        for alliance in alliances:
            if alliance['tag'] == tag:
                old_rank = alliance.get('rank')
                if old_rank != rank:
                    alliance['rank'] = rank
                    self.changes_made["rank_updates"].append(
                        f"{tag}: Rank {old_rank} → {rank}"
                    )
                    self.log(f"  📝 Updated rank: {tag} = {rank}")
                break

        with open(ALLIANCES_JSON, 'w', encoding='utf-8') as f:
            json.dump(alliances, f, indent=2, ensure_ascii=False)

    def update_power_in_json(self, tag, power):
        """Update alliance power in alliances.json"""
        if not ALLIANCES_JSON.exists():
            return

        with open(ALLIANCES_JSON, 'r', encoding='utf-8') as f:
            alliances = json.load(f)

        for alliance in alliances:
            if alliance['tag'] == tag:
                old_power = alliance.get('power')
                if old_power != power:
                    alliance['power'] = power
                    self.changes_made["power_updates"].append(
                        f"{tag}: {old_power:,} → {power:,}" if old_power else f"{tag}: Added power = {power:,}"
                    )
                    self.log(f"  📝 Updated power: {tag} = {power:,}")
                break

        with open(ALLIANCES_JSON, 'w', encoding='utf-8') as f:
            json.dump(alliances, f, indent=2, ensure_ascii=False)

    def run(self):
        """Main processing function"""
        self.log("=" * 70)
        self.log("Server 1586 - Screenshot Processing")
        self.log("=" * 70)
        self.log("")

        # Check if tesseract is available
        try:
            pytesseract.get_tesseract_version()
        except Exception as e:
            self.log(f"❌ Tesseract OCR not found: {e}")
            self.log("Please install Tesseract from: https://github.com/tesseract-ocr/tesseract")
            self.save_log()
            return

        # Process alliance cards
        self.log("[1/2] Processing Alliance Card Screenshots...")
        self.log("")

        alliance_cards = sorted(ALLIANCE_CARDS_DIR.glob("*.png"))
        cards_processed = 0

        for card_path in alliance_cards:
            if self.process_alliance_card(card_path):
                cards_processed += 1

        self.log("")
        self.log(f"Alliance cards processed: {cards_processed}/{len(alliance_cards)}")
        self.log("")

        # Process server rankings
        self.log("[2/2] Processing Server Ranking Screenshots...")
        self.log("")

        ranking_files = sorted(SERVER_RANKINGS_DIR.glob("*.png"))
        rankings_processed = 0

        for ranking_path in ranking_files:
            if self.process_server_ranking(ranking_path):
                rankings_processed += 1

        self.log("")
        self.log(f"Server rankings processed: {rankings_processed}/{len(ranking_files)}")
        self.log("")

        # Summary
        self.log("=" * 70)
        self.log("Processing Complete!")
        self.log("=" * 70)
        self.log(f"Alliance cards: {cards_processed} processed")
        self.log(f"Server rankings: {rankings_processed} processed")
        self.log(f"R5 updates: {len(self.changes_made['r5_updates'])}")
        self.log(f"Rank updates: {len(self.changes_made['rank_updates'])}")
        self.log(f"Power updates: {len(self.changes_made['power_updates'])}")
        self.log("")

        # Save log
        log_file = self.save_log()

        # Print recommendation
        self.log("")
        self.log("=" * 70)
        self.log("Next Steps:")
        self.log("=" * 70)
        if any(self.changes_made.values()):
            self.log("✓ Data files updated (alliances.json, signature-history.json)")
            self.log("✓ Changes committed to git (for version control)")
            self.log("⚠️  Changes NOT deployed to production (manual FTP deployment required)")
            self.log("")
            self.log("To deploy to production:")
            self.log("  1. Review changes in git diff")
            self.log("  2. Commit changes: git add data/*.json && git commit -m 'data: Update alliance info'")
            self.log("  3. Deploy: python scripts/deploy-ftp.py")
        else:
            self.log("No changes detected - data is already up to date")

if __name__ == "__main__":
    processor = ScreenshotProcessor()
    processor.run()
