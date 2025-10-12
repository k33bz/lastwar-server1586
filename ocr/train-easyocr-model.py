#!/usr/bin/env python3
"""
Fine-tune EasyOCR for Last War Text Recognition
Supports AMD GPU via ROCm

This script fine-tunes a pre-trained EasyOCR recognition model
on synthetic Last War game text data.

Requirements:
  pip install torch torchvision --index-url https://download.pytorch.org/whl/rocm6.2  # For AMD GPU
  pip install easyocr pillow numpy

Version: 1.0.0
Date: 2025-10-11
"""

import os
import sys
import json
import torch
import torch.nn as nn
import torch.optim as optim
from torch.utils.data import Dataset, DataLoader
from pathlib import Path
from PIL import Image
import numpy as np

# Paths
PROJECT_ROOT = Path(__file__).parent.parent
TRAINING_DATA_DIR = Path(__file__).parent / "training_data"
METADATA_FILE = TRAINING_DATA_DIR / "metadata.json"
MODEL_OUTPUT_DIR = Path(__file__).parent / "models"
MODEL_OUTPUT_DIR.mkdir(exist_ok=True)

# Training configuration
BATCH_SIZE = 16
EPOCHS = 50
LEARNING_RATE = 0.001
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# Character set for Last War text
# Alliance tags: uppercase letters, numbers, angle brackets
# R5 names: letters, numbers, spaces, special chars
CHARSET = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]_-@#$%&*()!?., "

class LastWarDataset(Dataset):
    """Dataset for Last War OCR training"""

    def __init__(self, data_items, base_dir, transform=None):
        """
        Args:
            data_items: List of metadata items (from metadata.json)
            base_dir: Base directory for images
            transform: Optional image transforms
        """
        self.data_items = data_items
        self.base_dir = base_dir
        self.transform = transform

        # Create character to index mapping
        self.char_to_idx = {char: idx + 1 for idx, char in enumerate(CHARSET)}
        self.char_to_idx['<blank>'] = 0  # CTC blank token
        self.idx_to_char = {idx: char for char, idx in self.char_to_idx.items()}

    def __len__(self):
        return len(self.data_items)

    def __getitem__(self, idx):
        item = self.data_items[idx]

        # Load image
        img_path = self.base_dir / item['image']
        image = Image.open(img_path).convert('L')  # Convert to grayscale

        # Resize to fixed height, variable width
        target_height = 32
        aspect_ratio = image.width / image.height
        target_width = int(target_height * aspect_ratio)

        # Ensure minimum width
        target_width = max(target_width, 16)

        image = image.resize((target_width, target_height), Image.LANCZOS)

        # Convert to tensor and normalize
        image = np.array(image).astype(np.float32) / 255.0
        image = torch.from_numpy(image).unsqueeze(0)  # Add channel dimension

        # Encode label
        text = item['text']
        label = self.encode_text(text)

        return image, label, text

    def encode_text(self, text):
        """Encode text to indices"""
        encoded = []
        for char in text:
            if char in self.char_to_idx:
                encoded.append(self.char_to_idx[char])
            else:
                # Unknown character - skip (e.g., Korean characters not in charset)
                pass  # Silently skip unknown characters
        return torch.LongTensor(encoded)

    def decode_text(self, indices):
        """Decode indices to text"""
        decoded = []
        for idx in indices:
            if idx == 0:  # blank token
                continue
            if idx in self.idx_to_char:
                decoded.append(self.idx_to_char[idx])
        return ''.join(decoded)

def collate_fn(batch):
    """Custom collate function to handle variable length sequences"""
    images, labels, texts = zip(*batch)

    # Pad images to same width
    max_width = max(img.shape[2] for img in images)
    padded_images = []
    for img in images:
        pad_width = max_width - img.shape[2]
        if pad_width > 0:
            padded = torch.nn.functional.pad(img, (0, pad_width))
        else:
            padded = img
        padded_images.append(padded)

    images = torch.stack(padded_images)

    # Get label lengths
    label_lengths = torch.LongTensor([len(label) for label in labels])

    # Pad labels
    max_label_len = max(len(label) for label in labels)
    padded_labels = torch.zeros(len(labels), max_label_len).long()
    for i, label in enumerate(labels):
        padded_labels[i, :len(label)] = label

    return images, padded_labels, label_lengths, texts

class SimpleCRNN(nn.Module):
    """
    Simple CRNN (Convolutional Recurrent Neural Network) for OCR
    Similar architecture to EasyOCR but simplified for fine-tuning
    """

    def __init__(self, num_chars, hidden_size=256):
        super(SimpleCRNN, self).__init__()

        # CNN layers for feature extraction
        self.cnn = nn.Sequential(
            nn.Conv2d(1, 64, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d(2, 2),  # 32 -> 16

            nn.Conv2d(64, 128, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d(2, 2),  # 16 -> 8

            nn.Conv2d(128, 256, kernel_size=3, padding=1),
            nn.ReLU(),
            nn.MaxPool2d((2, 1), (2, 1)),  # 8x -> 4x (height only)

            nn.Conv2d(256, 512, kernel_size=3, padding=1),
            nn.ReLU(),
        )

        # RNN layers for sequence modeling
        self.rnn = nn.LSTM(512 * 4, hidden_size, num_layers=2, bidirectional=True, batch_first=True)

        # Output layer
        self.fc = nn.Linear(hidden_size * 2, num_chars + 1)  # +1 for CTC blank

    def forward(self, x):
        # CNN feature extraction
        conv_features = self.cnn(x)  # [B, C, H, W]

        # Reshape for RNN: [B, W, C*H]
        b, c, h, w = conv_features.size()
        conv_features = conv_features.permute(0, 3, 1, 2)  # [B, W, C, H]
        conv_features = conv_features.reshape(b, w, c * h)

        # RNN sequence modeling
        rnn_out, _ = self.rnn(conv_features)  # [B, W, hidden*2]

        # Output layer
        output = self.fc(rnn_out)  # [B, W, num_chars]

        return output

def train_model():
    """Main training function"""
    print("=" * 70)
    print("EasyOCR Fine-tuning for Last War")
    print("=" * 70)
    print()

    # Check device
    print(f"[INFO] Using device: {DEVICE}")
    if DEVICE == "cuda":
        print(f"[INFO] GPU: {torch.cuda.get_device_name(0)}")
        print(f"[INFO] CUDA Version: {torch.version.cuda}")
    print()

    # Load metadata
    print(f"[1/5] Loading training data...")
    if not METADATA_FILE.exists():
        print(f"[ERROR] Metadata file not found: {METADATA_FILE}")
        print(f"        Run generate-training-data.py first")
        sys.exit(1)

    with open(METADATA_FILE, 'r', encoding='utf-8') as f:
        metadata = json.load(f)

    # Combine alliance tags and R5 names
    all_items = metadata['alliance_tags'] + metadata['r5_names']
    print(f"  [OK] Loaded {len(all_items)} training samples")

    # Split into train/val (80/20)
    split_idx = int(len(all_items) * 0.8)
    train_items = all_items[:split_idx]
    val_items = all_items[split_idx:]
    print(f"  [OK] Train: {len(train_items)}, Val: {len(val_items)}")

    # Create datasets
    print(f"\n[2/5] Creating datasets...")
    train_dataset = LastWarDataset(train_items, TRAINING_DATA_DIR)
    val_dataset = LastWarDataset(val_items, TRAINING_DATA_DIR)

    train_loader = DataLoader(
        train_dataset,
        batch_size=BATCH_SIZE,
        shuffle=True,
        collate_fn=collate_fn,
        num_workers=0  # Windows compatibility
    )
    val_loader = DataLoader(
        val_dataset,
        batch_size=BATCH_SIZE,
        shuffle=False,
        collate_fn=collate_fn,
        num_workers=0
    )
    print(f"  [OK] Train batches: {len(train_loader)}, Val batches: {len(val_loader)}")

    # Create model
    print(f"\n[3/5] Initializing model...")
    num_chars = len(CHARSET)
    model = SimpleCRNN(num_chars).to(DEVICE)
    print(f"  [OK] Model created with {num_chars} character classes")

    # Loss and optimizer
    criterion = nn.CTCLoss(blank=0, zero_infinity=True)
    optimizer = optim.Adam(model.parameters(), lr=LEARNING_RATE)

    # Training loop
    print(f"\n[4/5] Training for {EPOCHS} epochs...")
    best_val_loss = float('inf')

    for epoch in range(EPOCHS):
        # Training
        model.train()
        train_loss = 0
        for batch_idx, (images, labels, label_lengths, texts) in enumerate(train_loader):
            images = images.to(DEVICE)
            labels = labels.to(DEVICE)

            # Forward pass
            outputs = model(images)  # [B, W, num_chars]

            # CTC loss expects [W, B, num_chars]
            outputs = outputs.permute(1, 0, 2)

            # Input lengths (sequence length for each batch item)
            input_lengths = torch.full((images.size(0),), outputs.size(0), dtype=torch.long)

            # Compute loss
            loss = criterion(outputs, labels, input_lengths, label_lengths)

            # Backward pass
            optimizer.zero_grad()
            loss.backward()
            optimizer.step()

            train_loss += loss.item()

        train_loss /= len(train_loader)

        # Validation
        model.eval()
        val_loss = 0
        with torch.no_grad():
            for images, labels, label_lengths, texts in val_loader:
                images = images.to(DEVICE)
                labels = labels.to(DEVICE)

                outputs = model(images)
                outputs = outputs.permute(1, 0, 2)
                input_lengths = torch.full((images.size(0),), outputs.size(0), dtype=torch.long)

                loss = criterion(outputs, labels, input_lengths, label_lengths)
                val_loss += loss.item()

        val_loss /= len(val_loader)

        print(f"  Epoch [{epoch+1}/{EPOCHS}] - Train Loss: {train_loss:.4f}, Val Loss: {val_loss:.4f}")

        # Save best model
        if val_loss < best_val_loss:
            best_val_loss = val_loss
            model_path = MODEL_OUTPUT_DIR / "lastwar_ocr_best.pth"
            torch.save({
                'epoch': epoch,
                'model_state_dict': model.state_dict(),
                'optimizer_state_dict': optimizer.state_dict(),
                'val_loss': val_loss,
                'charset': CHARSET,
                'char_to_idx': train_dataset.char_to_idx,
            }, model_path)
            print(f"  [SAVED] Best model at epoch {epoch+1} (val_loss: {val_loss:.4f})")

    # Final save
    print(f"\n[5/5] Saving final model...")
    final_model_path = MODEL_OUTPUT_DIR / "lastwar_ocr_final.pth"
    torch.save({
        'epoch': EPOCHS,
        'model_state_dict': model.state_dict(),
        'optimizer_state_dict': optimizer.state_dict(),
        'val_loss': val_loss,
        'charset': CHARSET,
        'char_to_idx': train_dataset.char_to_idx,
    }, final_model_path)

    print()
    print("=" * 70)
    print("Training Complete!")
    print(f"  Best Val Loss: {best_val_loss:.4f}")
    print(f"  Best Model: {MODEL_OUTPUT_DIR / 'lastwar_ocr_best.pth'}")
    print(f"  Final Model: {final_model_path}")
    print("=" * 70)

if __name__ == '__main__':
    train_model()
