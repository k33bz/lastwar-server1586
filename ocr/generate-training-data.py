#!/usr/bin/env python3
"""
Generate Synthetic Training Data for EasyOCR
Uses extracted Last War fonts to create realistic training images

Version: 1.0.0
Date: 2025-10-11
"""

import os
import random
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont
import numpy as np
import json

# Paths
FONT_DIR = Path(r"C:\Users\k33bz\OneDrive\git\lastwar-font-extractor\extracted_fonts")
OUTPUT_DIR = Path(__file__).parent / "training_data"
OUTPUT_DIR.mkdir(exist_ok=True)

# Training configuration
ALLIANCE_TAGS = [
    "NKOT", "STR8", "EPIC", "BOSS", "FURY", "RAGE", "DARK", "IRON",
    "WOLF", "BEAR", "FIRE", "WATR", "WIND", "STAR", "MOON", "SUN",
    "KING", "LORD", "DUKE", "EARL", "KGHT", "WARR", "HERO", "PKRS",
    # Add more realistic alliance tags
    "ARMY", "NAVY", "SEAL", "SWAT", "SPEC", "ALFA", "BETA", "GAMA",
    "DELT", "ECHO", "APEX", "CORE", "VOLT", "BOLT", "NOVA", "ZETA"
]

R5_NAMES = [
    "Commander", "General", "Captain", "Warrior", "Knight",
    "Hunter", "Shadow", "Phoenix", "Dragon", "Tiger",
    "Eagle", "Hawk", "Wolf", "Bear", "Lion",
    "Storm", "Thunder", "Lightning", "Frost", "Blaze",
    # Add Korean-style names
    "김철수", "이영희", "박민수", "최지우", "정다은",
    # Add mixed names
    "Alex123", "Mike_War", "Sarah2024", "John_X", "Tom99"
]

# Font settings
FONTS = {
    "liberation": FONT_DIR / "LiberationSans.ttf",
    "dos": FONT_DIR / "Perfect DOS VGA 437.ttf"
}

def create_pink_background(width=200, height=40):
    """Create pink background similar to alliance card"""
    # Pink color from game UI
    pink_color = (218, 160, 180)  # RGB approximation
    img = Image.new('RGB', (width, height), pink_color)
    return img

def add_noise(image, noise_level=0.02):
    """Add subtle noise to make training more robust"""
    img_array = np.array(image)
    noise = np.random.normal(0, noise_level * 255, img_array.shape)
    noisy = np.clip(img_array + noise, 0, 255).astype(np.uint8)
    return Image.fromarray(noisy)

def generate_alliance_tag_image(tag, font_path, output_path, size=20):
    """Generate alliance tag image with <TAG> format"""
    # Create image with pink background
    img = create_pink_background(width=120, height=30)
    draw = ImageDraw.Draw(img)

    # Load font
    try:
        font = ImageFont.truetype(str(font_path), size)
    except:
        print(f"Warning: Could not load font {font_path}, using default")
        font = ImageFont.load_default()

    # Draw text with angle brackets
    text = f"<{tag}>"

    # Black text on pink background (like game UI)
    text_color = (0, 0, 0)

    # Center text
    bbox = draw.textbbox((0, 0), text, font=font)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]
    position = ((120 - text_width) // 2, (30 - text_height) // 2)

    draw.text(position, text, fill=text_color, font=font)

    # Add slight noise for realism
    img = add_noise(img, noise_level=0.01)

    # Save
    img.save(output_path)
    return text

def generate_r5_name_image(name, font_path, output_path, size=18):
    """Generate R5 name image"""
    # Create image with light blue/gray background (like game UI)
    bg_color = (180, 190, 200)
    img = Image.new('RGB', (200, 30), bg_color)
    draw = ImageDraw.Draw(img)

    # Load font
    try:
        font = ImageFont.truetype(str(font_path), size)
    except:
        print(f"Warning: Could not load font {font_path}, using default")
        font = ImageFont.load_default()

    # Dark text
    text_color = (40, 40, 60)

    # Center text vertically, left-align horizontally
    bbox = draw.textbbox((0, 0), name, font=font)
    text_height = bbox[3] - bbox[1]
    position = (10, (30 - text_height) // 2)

    draw.text(position, name, fill=text_color, font=font)

    # Add slight noise
    img = add_noise(img, noise_level=0.01)

    # Save
    img.save(output_path)
    return name

def generate_dataset():
    """Generate complete training dataset"""
    print("=" * 70)
    print("Last War OCR Training Data Generator")
    print("=" * 70)
    print()

    # Create output directories
    tags_dir = OUTPUT_DIR / "alliance_tags"
    names_dir = OUTPUT_DIR / "r5_names"
    tags_dir.mkdir(exist_ok=True)
    names_dir.mkdir(exist_ok=True)

    # Metadata for training
    metadata = {
        "alliance_tags": [],
        "r5_names": []
    }

    # Generate alliance tag images
    print(f"[1/2] Generating alliance tag images...")
    tag_count = 0
    for tag in ALLIANCE_TAGS:
        for font_name, font_path in FONTS.items():
            if not font_path.exists():
                print(f"  [WARN] Font not found: {font_path}")
                continue

            # Generate multiple variations with different sizes
            for size in [18, 20, 22]:
                output_file = tags_dir / f"{tag}_{font_name}_{size}.png"
                text = generate_alliance_tag_image(tag, font_path, output_file, size=size)

                metadata["alliance_tags"].append({
                    "image": str(output_file.relative_to(OUTPUT_DIR)),
                    "text": text,
                    "font": font_name,
                    "size": size
                })
                tag_count += 1

    print(f"  [OK] Generated {tag_count} alliance tag images")

    # Generate R5 name images
    print(f"[2/2] Generating R5 name images...")
    name_count = 0
    for name in R5_NAMES:
        for font_name, font_path in FONTS.items():
            if not font_path.exists():
                continue

            # Generate multiple variations
            for size in [16, 18, 20]:
                output_file = names_dir / f"{name.replace(' ', '_')}_{font_name}_{size}.png"
                text = generate_r5_name_image(name, font_path, output_file, size=size)

                metadata["r5_names"].append({
                    "image": str(output_file.relative_to(OUTPUT_DIR)),
                    "text": text,
                    "font": font_name,
                    "size": size
                })
                name_count += 1

    print(f"  [OK] Generated {name_count} R5 name images")

    # Save metadata
    metadata_file = OUTPUT_DIR / "metadata.json"
    with open(metadata_file, 'w', encoding='utf-8') as f:
        json.dump(metadata, f, indent=2, ensure_ascii=False)

    print()
    print("=" * 70)
    print("Generation Complete:")
    print(f"  Alliance Tags: {tag_count} images")
    print(f"  R5 Names: {name_count} images")
    print(f"  Total: {tag_count + name_count} images")
    print(f"  Output: {OUTPUT_DIR}")
    print(f"  Metadata: {metadata_file}")
    print("=" * 70)

if __name__ == '__main__':
    generate_dataset()
