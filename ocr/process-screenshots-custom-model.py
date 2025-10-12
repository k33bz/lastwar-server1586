#!/usr/bin/env python3
"""
Alliance Screenshot OCR - Custom Trained Model with Manual Bounding Boxes
Uses trained Last War OCR model and precise bounding boxes from manual calibration

Version: 2.0.0
Date: 2025-10-12
"""

import sys
from pathlib import Path
import json
import torch
import torch.nn as nn

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
OUTPUT_DIR = OCR_DIR / "debug_ocr_custom"
OUTPUT_JSON = PROJECT_ROOT / "data" / "alliances-ocr-custom.json"
MODEL_PATH = OCR_DIR / "models" / "lastwar_ocr_best.pth"

# Create output directory
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Character set (must match training)
CHARSET = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]_-@#$%&*()!?., "

class SimpleCRNN(nn.Module):
    """
    Simple CRNN model (must match training architecture)
    """
    def __init__(self, num_chars, hidden_size=256):
        super(SimpleCRNN, self).__init__()

        # CNN layers
        self.cnn = nn.Sequential(
            nn.Conv2d(1, 64, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d(2, 2),

            nn.Conv2d(64, 128, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d(2, 2),

            nn.Conv2d(128, 256, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d((2, 1), (2, 1)),

            nn.Conv2d(256, 512, kernel_size=3, padding=1),
            nn.ReLU(),
        )

        # RNN layers
        self.rnn = nn.LSTM(512 * 4, hidden_size, num_layers=2, bidirectional=True, batch_first=True)

        # Output layer
        self.fc = nn.Linear(hidden_size * 2, num_chars + 1)

    def forward(self, x):
        conv_features = self.cnn(x)
        b, c, h, w = conv_features.size()
        conv_features = conv_features.permute(0, 3, 1, 2)
        conv_features = conv_features.reshape(b, w, c * h)
        rnn_out, _ = self.rnn(conv_features)
        output = self.fc(rnn_out)
        return output

# Load trained model
print("Loading custom trained model...")
if not MODEL_PATH.exists():
    print(f"[ERROR] Model not found: {MODEL_PATH}")
    print("        Run: python ocr/train-easyocr-model.py")
    sys.exit(1)

checkpoint = torch.load(MODEL_PATH, map_location='cpu')
char_to_idx = checkpoint['char_to_idx']
idx_to_char = {idx: char for char, idx in char_to_idx.items()}

model = SimpleCRNN(num_chars=len(CHARSET))
model.load_state_dict(checkpoint['model_state_dict'])
model.eval()
print(f"[OK] Model loaded: {MODEL_PATH}")

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

    # Verify it's roughly square
    aspect_ratio = w / h if h > 0 else 0
    if 0.7 < aspect_ratio < 1.3 and w > 20 and h > 20:
        return (x, y, w, h)

    return None

def get_alliance_tag_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """
    Calculate bounding box for alliance tag based on manual calibration
    From MZKU_short_name Bounding Box.png - the blue box around <MZKU>

    Measurements from manual bounding box:
    - Red box (exclamation): approximately at x=510, y=120
    - Alliance tag box: starts at x=215, y=115, width=90, height=20
    """
    tag_x = anchor_x - 295  # 510 - 215 = 295 pixels left
    tag_y = anchor_y - 5    # 120 - 115 = 5 pixels up
    tag_width = 90
    tag_height = 20

    return {
        'x': max(0, tag_x),
        'y': max(0, tag_y),
        'width': tag_width,
        'height': tag_height
    }

def get_r5_name_bbox(anchor_x, anchor_y, anchor_w, anchor_h):
    """
    Calculate bounding box for R5 name based on manual calibration
    From MZKU_Grand Puba Daddio Leader Bounding Box.png - the red box around leader name

    Measurements from manual bounding box:
    - Red box (exclamation): approximately at x=510, y=120
    - R5 name box: starts at x=368, y=175, width=152, height=18
    """
    r5_x = anchor_x - 142   # 510 - 368 = 142 pixels left
    r5_y = anchor_y + 55    # 175 - 120 = 55 pixels down
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

def preprocess_for_model(image):
    """Preprocess image for custom model"""
    # Convert to grayscale
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    # Apply CLAHE for better contrast
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8,8))
    enhanced = clahe.apply(gray)

    # Otsu's thresholding for binarization
    _, binary = cv2.threshold(enhanced, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

    # Invert if background is darker than text (training data had black text on white)
    if np.mean(binary) < 127:
        binary = cv2.bitwise_not(binary)

    # Resize to fixed height
    target_height = 32
    aspect_ratio = binary.shape[1] / binary.shape[0]
    target_width = int(target_height * aspect_ratio)
    target_width = max(target_width, 16)

    resized = cv2.resize(binary, (target_width, target_height), interpolation=cv2.INTER_LANCZOS4)

    # Normalize
    normalized = resized.astype(np.float32) / 255.0

    return normalized

def decode_prediction(output, debug=False):
    """Decode CTC output to text using greedy decoding"""
    # output shape: [1, width, num_chars+1]
    output = output.squeeze(0)  # Remove batch dimension -> [width, num_chars+1]

    # Apply softmax to get probabilities
    probs = torch.nn.functional.softmax(output, dim=1)

    # Get most likely character at each timestep
    _, pred_indices = torch.max(probs, dim=1)
    pred_indices = pred_indices.cpu().numpy()

    if debug:
        print(f"    [DEBUG] Output shape: {output.shape}")
        print(f"    [DEBUG] Predicted indices: {pred_indices[:20]}...")  # First 20

    # CTC greedy decode - remove blanks and consecutive duplicates
    decoded = []
    prev_idx = -1
    for idx in pred_indices:
        # Skip blank token (0) and consecutive duplicates
        if idx != 0 and idx != prev_idx:
            if idx in idx_to_char:
                decoded.append(idx_to_char[idx])
            elif debug:
                print(f"    [DEBUG] Unknown index: {idx}")
        prev_idx = idx

    result = ''.join(decoded)

    if debug:
        print(f"    [DEBUG] Decoded text: '{result}'")

    return result

def ocr_with_custom_model(image, debug=False):
    """Perform OCR using custom trained model"""
    # Preprocess
    processed = preprocess_for_model(image)

    if debug:
        print(f"    [DEBUG] Preprocessed shape: {processed.shape}")

    # Convert to tensor
    tensor = torch.from_numpy(processed).unsqueeze(0).unsqueeze(0)  # Add batch and channel dims

    if debug:
        print(f"    [DEBUG] Tensor shape: {tensor.shape}")

    # Run inference
    with torch.no_grad():
        output = model(tensor)

    if debug:
        print(f"    [DEBUG] Model output shape: {output.shape}")

    # Decode
    text = decode_prediction(output, debug=debug)

    return text

def process_alliance_card(image_path):
    """Process a single alliance card screenshot"""
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

    # Get bounding boxes using manual calibration
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

    # Save cropped regions
    cv2.imwrite(str(OUTPUT_DIR / f"{image_path.stem}_tag.png"), tag_crop)
    cv2.imwrite(str(OUTPUT_DIR / f"{image_path.stem}_r5.png"), r5_crop)

    # Perform OCR with custom model (enable debug for first image)
    debug_mode = image_path.stem == "2025-10-10_12-29-26"

    if debug_mode:
        print("  [DEBUG] Alliance Tag OCR:")
    alliance_tag = ocr_with_custom_model(tag_crop, debug=debug_mode)

    if debug_mode:
        print("  [DEBUG] R5 Name OCR:")
    r5_name = ocr_with_custom_model(r5_crop, debug=debug_mode)

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
    print("Alliance Screenshot OCR - Custom Trained Model")
    print("=" * 70)
    print()

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
        'processed_date': '2025-10-12',
        'model': 'Custom Trained CRNN',
        'model_path': str(MODEL_PATH),
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
            print(f"  {r['tag']:12} - {r['r5']}")

if __name__ == '__main__':
    main()
