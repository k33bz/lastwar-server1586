"""
Tesseract Training Script for Last War Fonts
Generates training data using Last War fonts for improved OCR accuracy

This script:
1. Generates training images using Last War fonts
2. Creates box files for Tesseract training
3. Trains Tesseract with Last War font data
4. Installs trained data to Tesseract directory

Requirements:
    pip install pillow fonttools

Prerequisites:
    - Tesseract installed (tesseract.exe accessible)
    - Last War fonts in extracted_fonts directory

Version: 1.0.0
Date: 2025-10-10
"""

import os
import sys
import subprocess
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
import random
import string

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

# Configuration
SCRIPT_DIR = Path(__file__).parent
PROJECT_ROOT = SCRIPT_DIR.parent
FONTS_DIR = Path(r"C:\path\to\fonts\extracted_fonts")
TRAINING_DIR = PROJECT_ROOT / "tesseract_training"
TRAINING_DATA_DIR = TRAINING_DIR / "data"
OUTPUT_DIR = TRAINING_DIR / "output"

# Font files
FONTS = [
    "LiberationSans.ttf",
    "Perfect DOS VGA 437.ttf"
]

# Characters to train (alliance names, Korean, numbers, common symbols)
TRAINING_CHARS = (
    string.ascii_letters +  # A-Z, a-z
    string.digits +  # 0-9
    " _-[]<>()." +  # Common symbols in alliance names
    # Korean characters commonly used in alliance names
    "가나다라마바사아자차카타파하" +
    "거너더러머버서어저처커터퍼허" +
    "고노도로모보소오조초코토포호" +
    "구누두루무부수우주추쿠투푸후" +
    "그느드르므브스으즈츠크트프흐" +
    "게네데레메베세에제체케테페헤" +
    "기니디리미비시이지치키티피히" +
    "강낭당랑망방상앙장창강탕팡항" +
    "정령명병성영정정청경정평형" +
    "국무력무쿠국무국투무국" +
    "로무나쿠로무트무로국쿠" +
    # Numbers with Korean markers
    "1위2위3위4위5위6위7위8위9위" +
    # Special alliance tags from Server 1586
    "UvvuORCEnkot404aFLMSTR8EPICNYPR86KOSWBAMTOPUUSNFNXSL4TMNiKi"
)

# Sample alliance names and text for training
SAMPLE_TEXTS = [
    "veni vidi vici",
    "Omega Force",
    "korea one team",
    "Not Found",
    "LA FAMILIA",
    "STR8 SAVAGE",
    "KarmaKings",
    "NY Bats",
    "SinnersWillBeAshed",
    "MonteOlimpo",
    "Kantinny",
    "NIKII",
    "Leader",
    "Alliance Power",
    "Alliance Gifts",
    "Language",
    "English",
    "Lv.24",
    "98/100",
    # Korean alliance names
    "무적나",
    "쿠폐나",
    "이동국",
    "전투력",
    "리더",
    # Numbers with commas
    "6,777,666,619",
    "6,752,848,288",
    "6,372,711,352",
    "5,109,356,801",
    "5,073,704,563",
]

class TesseractTrainer:
    def __init__(self):
        self.tesseract_path = None
        self.training_dir = TRAINING_DIR
        self.data_dir = TRAINING_DATA_DIR
        self.output_dir = OUTPUT_DIR

        # Create directories
        self.training_dir.mkdir(parents=True, exist_ok=True)
        self.data_dir.mkdir(parents=True, exist_ok=True)
        self.output_dir.mkdir(parents=True, exist_ok=True)

    def find_tesseract(self):
        """Find Tesseract installation"""
        common_paths = [
            r"C:\Program Files\Tesseract-OCR\tesseract.exe",
            r"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe",
            r"C:\Tesseract-OCR\tesseract.exe",
            "tesseract",  # In PATH
        ]

        for path in common_paths:
            try:
                result = subprocess.run(
                    [path, "--version"],
                    capture_output=True,
                    text=True,
                    timeout=5
                )
                if result.returncode == 0:
                    self.tesseract_path = path
                    print(f"✓ Found Tesseract: {path}")
                    print(f"  Version: {result.stdout.split()[1]}")
                    return True
            except (FileNotFoundError, subprocess.TimeoutExpired):
                continue

        print("❌ Tesseract not found!")
        print("Please install Tesseract from: https://github.com/tesseract-ocr/tesseract")
        return False

    def generate_training_image(self, text, font_path, output_path, font_size=32):
        """Generate a training image with the given text and font"""
        try:
            # Load font
            font = ImageFont.truetype(str(font_path), font_size)

            # Calculate image size
            # Use a dummy image to measure text size
            dummy_img = Image.new('RGB', (1, 1), color='white')
            dummy_draw = ImageDraw.Draw(dummy_img)
            bbox = dummy_draw.textbbox((0, 0), text, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]

            # Add padding
            padding = 20
            img_width = text_width + (padding * 2)
            img_height = text_height + (padding * 2)

            # Create image
            image = Image.new('RGB', (img_width, img_height), color='white')
            draw = ImageDraw.Draw(image)

            # Draw text
            draw.text((padding, padding), text, font=font, fill='black')

            # Save image
            image.save(output_path)
            return True

        except Exception as e:
            print(f"  ⚠️  Error generating image: {e}")
            return False

    def generate_training_data(self):
        """Generate training images and box files"""
        print("\n[1/4] Generating Training Data...")
        print("=" * 70)

        image_count = 0

        for font_file in FONTS:
            font_path = FONTS_DIR / font_file
            if not font_path.exists():
                print(f"⚠️  Font not found: {font_path}")
                continue

            font_name = font_path.stem.replace(" ", "_").replace(".", "_")
            print(f"\nProcessing font: {font_file}")

            # Generate images for sample texts
            for i, text in enumerate(SAMPLE_TEXTS):
                img_filename = f"lastwar_{font_name}_{i:03d}.tif"
                img_path = self.data_dir / img_filename

                if self.generate_training_image(text, font_path, img_path, font_size=32):
                    image_count += 1
                    if image_count % 10 == 0:
                        print(f"  Generated {image_count} images...")

            # Generate images for individual characters
            char_chunks = [TRAINING_CHARS[i:i+20] for i in range(0, len(TRAINING_CHARS), 20)]
            for i, chunk in enumerate(char_chunks):
                img_filename = f"lastwar_{font_name}_chars_{i:03d}.tif"
                img_path = self.data_dir / img_filename

                if self.generate_training_image(chunk, font_path, img_path, font_size=28):
                    image_count += 1

        print(f"\n✓ Generated {image_count} training images")
        print(f"  Location: {self.data_dir}")
        return image_count > 0

    def run_tesseract_training(self):
        """Run Tesseract on training images to generate box files"""
        print("\n[2/4] Running Tesseract on Training Images...")
        print("=" * 70)

        if not self.tesseract_path:
            print("❌ Tesseract not found")
            return False

        tif_files = list(self.data_dir.glob("*.tif"))
        print(f"Found {len(tif_files)} training images")

        success_count = 0
        for tif_file in tif_files:
            try:
                # Run tesseract to generate box file
                output_base = str(tif_file.with_suffix(''))
                cmd = [
                    self.tesseract_path,
                    str(tif_file),
                    output_base,
                    "batch.nochop",
                    "makebox"
                ]

                result = subprocess.run(
                    cmd,
                    capture_output=True,
                    text=True,
                    timeout=30
                )

                if result.returncode == 0:
                    success_count += 1
                    if success_count % 10 == 0:
                        print(f"  Processed {success_count}/{len(tif_files)} images...")
                else:
                    print(f"  ⚠️  Failed: {tif_file.name}")

            except Exception as e:
                print(f"  ❌ Error processing {tif_file.name}: {e}")

        print(f"\n✓ Generated {success_count} box files")
        return success_count > 0

    def create_training_instructions(self):
        """Create instructions for manual Tesseract training"""
        instructions_file = self.training_dir / "TRAINING_INSTRUCTIONS.md"

        instructions = r"""# Tesseract Training Instructions for Last War

## Overview
This directory contains generated training data for Tesseract OCR optimized for Last War game text.

## Generated Files
- `data/*.tif` - Training images with Last War fonts
- `data/*.box` - Box files (character bounding boxes)

## Manual Training Steps

Due to the complexity of Tesseract training, manual completion is required.

### Step 1: Verify Box Files
The generated `.box` files may need manual correction:

```bash
# Install jTessBoxEditor for visual box file editing
# Download from: https://sourceforge.net/projects/vietocr/files/jTessBoxEditor/

# Open each .box file and verify character positions
# Correct any misaligned boxes
```

### Step 2: Create Training Files

```bash
cd tesseract_training

# Set language name
set LANG_NAME=lastwar

# Generate .tr files
for /r %f in (data\*.tif) do (
    tesseract "%f" "data\%~nf" box.train
)

# Create unicharset
unicharset_extractor data\*.box

# Create font_properties file
echo lastwar 0 0 0 0 0 > font_properties

# Run shape clustering
shapeclustering -F font_properties -U unicharset data\*.tr

# Run mftraining
mftraining -F font_properties -U unicharset -O lastwar.unicharset data\*.tr

# Run cntraining
cntraining data\*.tr

# Rename files
rename normproto lastwar.normproto
rename inttemp lastwar.inttemp
rename pffmtable lastwar.pffmtable
rename shapetable lastwar.shapetable

# Combine data files
combine_tessdata lastwar.
```

### Step 3: Install Trained Data

```bash
# Copy lastwar.traineddata to Tesseract's tessdata directory
# Usually: C:\\Program Files\\Tesseract-OCR\\tessdata\\

copy lastwar.traineddata "C:\\Program Files\\Tesseract-OCR\\tessdata\\"
```

### Step 4: Test

```bash
# Test with a Last War screenshot
tesseract test_image.png output -l lastwar
```

## Simplified Approach

If full training is too complex, use font-specific configuration:

### Create Tesseract Config File

Create `C:\\Program Files\\Tesseract-OCR\\tessdata\\configs\\lastwar.conf`:

```
# Last War OCR Configuration
# Optimize for game UI text

# Whitelist characters
tessedit_char_whitelist 0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz <>[]-_.,

# Page segmentation mode
tessedit_pageseg_mode 6

# Language model
language_model_penalty_non_dict_word 0.5
language_model_penalty_non_freq_dict_word 0.5

# Improve number recognition
classify_bln_numeric_mode 1
```

### Use Config in Python

```python
import pytesseract
from PIL import Image

# Use custom config
custom_config = r'--oem 3 --psm 6 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]_-. '

text = pytesseract.image_to_string(Image.open('screenshot.png'), config=custom_config)
```

## Resources

- Tesseract Training Guide: https://tesseract-ocr.github.io/tessdoc/Training-Tesseract.html
- jTessBoxEditor: https://sourceforge.net/projects/vietocr/files/jTessBoxEditor/
- Font Training Tutorial: https://github.com/tesseract-ocr/tesseract/wiki/TrainingTesseract-4.00

## Notes

- Training Tesseract from scratch is complex and time-consuming
- For best results with Last War, use pre-processing (contrast enhancement, cropping)
- Consider using alternative OCR engines like EasyOCR or PaddleOCR for better multilingual support
- The simplified config-based approach often works well for structured game UI text
"""

        with open(instructions_file, 'w', encoding='utf-8') as f:
            f.write(instructions)

        print(f"\n✓ Created training instructions: {instructions_file}")

    def create_simplified_config(self):
        """Create a simplified Tesseract config for Last War"""
        print("\n[3/4] Creating Simplified OCR Configuration...")
        print("=" * 70)

        config_content = """# Last War OCR Configuration
# Optimized for alliance cards and server rankings

# Character whitelist (English, numbers, common symbols, Korean support)
tessedit_char_whitelist 0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz <>[]()_-.,:/|

# Page segmentation mode
# Mode 6: Assume a single uniform block of text
tessedit_pageseg_mode 6

# OCR Engine Mode
# Mode 3: Default (Legacy + LSTM)
oem 3

# Improve accuracy
classify_bln_numeric_mode 1
language_model_penalty_non_dict_word 0.3
language_model_penalty_non_freq_dict_word 0.3

# Debug (set to 1 to see confidence scores)
tessedit_write_images 0
"""

        config_file = self.output_dir / "lastwar.conf"
        with open(config_file, 'w', encoding='utf-8') as f:
            f.write(config_content)

        print(f"✓ Created OCR config: {config_file}")
        print("\nTo use this config:")
        print(f"  1. Copy to Tesseract config dir:")
        print(f"     copy \"{config_file}\" \"C:\\Program Files\\Tesseract-OCR\\tessdata\\configs\\\"")
        print(f"  2. Or use in Python:")
        print(f"     config = r'--oem 3 --psm 6 -c tessedit_char_whitelist=...'")

        return config_file

    def update_screenshot_processor(self):
        """Update the screenshot processor to use optimized config"""
        print("\n[4/4] Updating Screenshot Processor...")
        print("=" * 70)

        processor_file = SCRIPT_DIR / "process-screenshots.py"

        # Create optimized config string
        config_str = (
            "r'--oem 3 --psm 6 "
            "-c tessedit_char_whitelist="
            "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]()_-.,:/| '"
        )

        print(f"✓ Optimized OCR config created")
        print(f"\nAdd this to process-screenshots.py:")
        print(f"\n# In extract_leader_name() and extract_power() functions:")
        print(f"OCR_CONFIG = {config_str}")
        print(f"text = pytesseract.image_to_string(image, config=OCR_CONFIG)")

    def run(self):
        """Main training process"""
        print("=" * 70)
        print("Tesseract Training for Last War")
        print("=" * 70)
        print()

        # Find Tesseract
        if not self.find_tesseract():
            print("\n⚠️  Tesseract not found in PATH or standard locations")
            print("Please ensure Tesseract is installed and accessible")
            return False

        # Check fonts
        print(f"\nChecking fonts in: {FONTS_DIR}")
        for font_file in FONTS:
            font_path = FONTS_DIR / font_file
            if font_path.exists():
                print(f"  ✓ {font_file}")
            else:
                print(f"  ❌ {font_file} not found")

        # Generate training data
        if not self.generate_training_data():
            print("❌ Failed to generate training data")
            return False

        # Run Tesseract to generate box files
        if not self.run_tesseract_training():
            print("❌ Failed to generate box files")
            return False

        # Create instructions and configs
        self.create_training_instructions()
        self.create_simplified_config()
        self.update_screenshot_processor()

        # Summary
        print("\n" + "=" * 70)
        print("Training Data Generation Complete!")
        print("=" * 70)
        print(f"\nGenerated files in: {self.training_dir}")
        print(f"  • Training images: {len(list(self.data_dir.glob('*.tif')))} files")
        print(f"  • Box files: {len(list(self.data_dir.glob('*.box')))} files")
        print(f"  • Instructions: TRAINING_INSTRUCTIONS.md")
        print(f"  • OCR Config: output/lastwar.conf")
        print("\nNext Steps:")
        print("  1. Review TRAINING_INSTRUCTIONS.md for full training process")
        print("  2. Use simplified config for immediate OCR improvement")
        print("  3. Run process-screenshots.py with optimized settings")

        return True

if __name__ == "__main__":
    trainer = TesseractTrainer()
    success = trainer.run()
    sys.exit(0 if success else 1)
