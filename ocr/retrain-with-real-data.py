#!/usr/bin/env python3
"""
Retrain CRNN Model with Real Screenshot Data
Uses manually labeled real game screenshots for training

Version: 1.0.0
Date: 2025-10-12
"""

import sys
import json
from pathlib import Path

try:
    import torch
    import torch.nn as nn
    import torch.optim as optim
    from torch.utils.data import Dataset, DataLoader
    import cv2
    import numpy as np
except ImportError:
    print("Missing required packages. Install with:")
    print("  pip install torch opencv-python numpy")
    sys.exit(1)

# Paths
OCR_DIR = Path(__file__).parent
TRAINING_DATA_DIR = OCR_DIR / "training_data_real"
LABELS_JSON = TRAINING_DATA_DIR / "labels.json"
MODEL_OUTPUT_DIR = OCR_DIR / "models"
MODEL_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Character set (same as before)
CHARACTERS = " ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789<>[](){}!@#$%^&*-_=+|\\:;'\",./?"

# Training parameters
BATCH_SIZE = 8
LEARNING_RATE = 0.001
NUM_EPOCHS = 100
IMG_HEIGHT = 32
PATIENCE = 15  # Early stopping patience

class SimpleCRNN(nn.Module):
    """Simplified CRNN architecture"""
    def __init__(self, num_classes):
        super(SimpleCRNN, self).__init__()

        # CNN layers
        self.cnn = nn.Sequential(
            nn.Conv2d(1, 64, kernel_size=3, padding=1),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),  # 32 -> 16

            nn.Conv2d(64, 128, kernel_size=3, padding=1),
            nn.ReLU(inplace=True),
            nn.MaxPool2d(2, 2),  # 16 -> 8

            nn.Conv2d(128, 256, kernel_size=3, padding=1),
            nn.BatchNorm2d(256),
            nn.ReLU(inplace=True),
            nn.MaxPool2d((2, 1)),  # 8 -> 4

            nn.Conv2d(256, 512, kernel_size=3, padding=1),
            nn.BatchNorm2d(512),
            nn.ReLU(inplace=True),
        )

        # RNN layers
        self.rnn = nn.LSTM(512 * 4, 256, num_layers=2, bidirectional=True, batch_first=True)

        # Output layer
        self.fc = nn.Linear(512, num_classes)

    def forward(self, x):
        # CNN
        conv = self.cnn(x)

        # Reshape for RNN
        b, c, h, w = conv.size()
        conv = conv.permute(0, 3, 1, 2)  # [batch, width, channels, height]
        conv = conv.reshape(b, w, c * h)  # [batch, width, channels*height]

        # RNN
        rnn_out, _ = self.rnn(conv)

        # Output
        output = self.fc(rnn_out)
        output = output.permute(1, 0, 2)  # [width, batch, num_classes] for CTC

        return output

class RealScreenshotDataset(Dataset):
    """Dataset for real screenshot crops with labels"""
    def __init__(self, labels, data_dir, char_to_idx):
        self.labels = labels
        self.data_dir = data_dir
        self.char_to_idx = char_to_idx

    def __len__(self):
        return len(self.labels)

    def __getitem__(self, idx):
        sample = self.labels[idx]

        # Load image
        img_path = self.data_dir / sample['filename']
        image = cv2.imread(str(img_path), cv2.IMREAD_GRAYSCALE)

        if image is None:
            raise ValueError(f"Could not load image: {img_path}")

        # Preprocess
        image = self.preprocess_image(image)

        # Encode text
        text = sample['label']
        encoded = self.encode_text(text)

        return image, encoded, len(encoded)

    def preprocess_image(self, image):
        """Preprocess image for model input"""
        # Scale up if too small
        if image.shape[0] < 32:
            scale = 32.0 / image.shape[0]
            new_width = int(image.shape[1] * scale)
            image = cv2.resize(image, (new_width, 32), interpolation=cv2.INTER_CUBIC)

        # Apply CLAHE for contrast
        clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
        image = clahe.apply(image)

        # Resize to fixed height, variable width
        target_height = IMG_HEIGHT
        aspect_ratio = image.shape[1] / image.shape[0]
        target_width = max(int(target_height * aspect_ratio), 16)

        image = cv2.resize(image, (target_width, target_height), interpolation=cv2.INTER_LANCZOS4)

        # Normalize
        image = image.astype(np.float32) / 255.0

        # Add channel dimension
        image = np.expand_dims(image, axis=0)

        return torch.FloatTensor(image)

    def encode_text(self, text):
        """Encode text to indices"""
        encoded = []
        for char in text:
            if char in self.char_to_idx:
                encoded.append(self.char_to_idx[char])
            else:
                pass  # Skip unknown characters
        return torch.LongTensor(encoded)

def collate_fn(batch):
    """Custom collate function for variable length sequences"""
    images, targets, target_lengths = zip(*batch)

    # Pad images to same width
    max_width = max(img.size(2) for img in images)
    padded_images = []
    for img in images:
        pad_width = max_width - img.size(2)
        if pad_width > 0:
            padded = torch.nn.functional.pad(img, (0, pad_width))
            padded_images.append(padded)
        else:
            padded_images.append(img)

    images = torch.stack(padded_images)
    targets = torch.cat(targets)
    target_lengths = torch.LongTensor(target_lengths)

    return images, targets, target_lengths

def train_model():
    """Train CRNN on real screenshot data"""
    print("=" * 70)
    print("Retrain CRNN with Real Screenshot Data")
    print("=" * 70)
    print()

    # Check for labels file
    if not LABELS_JSON.exists():
        print(f"[ERROR] Labels file not found: {LABELS_JSON}")
        print("Run create-training-labels.py first to create labeled data")
        return

    # Load labels
    with open(LABELS_JSON, 'r', encoding='utf-8') as f:
        all_labels = json.load(f)

    print(f"Loaded {len(all_labels)} labeled samples")

    if len(all_labels) < 10:
        print("[WARNING] Very few training samples! Need at least 20-30 for good results")
        proceed = input("Continue anyway? (y/n): ").strip().lower()
        if proceed != 'y':
            return

    # Create character mappings
    char_to_idx = {char: idx + 1 for idx, char in enumerate(CHARACTERS)}  # 0 is blank for CTC
    idx_to_char = {idx: char for char, idx in char_to_idx.items()}
    num_classes = len(char_to_idx) + 1  # +1 for CTC blank

    print(f"Character set: {len(CHARACTERS)} characters")
    print(f"Number of classes: {num_classes} (including CTC blank)")

    # Split data (80% train, 20% val)
    np.random.seed(42)
    indices = np.random.permutation(len(all_labels))
    split_idx = int(len(all_labels) * 0.8)
    train_indices = indices[:split_idx]
    val_indices = indices[split_idx:]

    train_labels = [all_labels[i] for i in train_indices]
    val_labels = [all_labels[i] for i in val_indices]

    print(f"\nDataset split:")
    print(f"  Training: {len(train_labels)} samples")
    print(f"  Validation: {len(val_labels)} samples")

    # Create datasets
    train_dataset = RealScreenshotDataset(train_labels, TRAINING_DATA_DIR, char_to_idx)
    val_dataset = RealScreenshotDataset(val_labels, TRAINING_DATA_DIR, char_to_idx)

    train_loader = DataLoader(train_dataset, batch_size=BATCH_SIZE, shuffle=True,
                             collate_fn=collate_fn, num_workers=0)
    val_loader = DataLoader(val_dataset, batch_size=BATCH_SIZE, shuffle=False,
                           collate_fn=collate_fn, num_workers=0)

    # Initialize model
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    print(f"\nUsing device: {device}")

    model = SimpleCRNN(num_classes).to(device)

    # Loss and optimizer
    ctc_loss = nn.CTCLoss(blank=0, zero_infinity=True)
    optimizer = optim.Adam(model.parameters(), lr=LEARNING_RATE)
    scheduler = optim.lr_scheduler.ReduceLROnPlateau(optimizer, mode='min', factor=0.5, patience=5)

    # Training loop
    print(f"\nStarting training for {NUM_EPOCHS} epochs...")
    print("=" * 70)

    best_val_loss = float('inf')
    patience_counter = 0

    for epoch in range(NUM_EPOCHS):
        # Training
        model.train()
        train_loss = 0

        for batch_idx, (images, targets, target_lengths) in enumerate(train_loader):
            images = images.to(device)
            targets = targets.to(device)

            optimizer.zero_grad()

            # Forward pass
            outputs = model(images)
            output_lengths = torch.full((images.size(0),), outputs.size(0), dtype=torch.long)

            # Calculate loss
            loss = ctc_loss(outputs, targets, output_lengths, target_lengths)

            # Backward pass
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), 5)
            optimizer.step()

            train_loss += loss.item()

        train_loss /= len(train_loader)

        # Validation
        model.eval()
        val_loss = 0

        with torch.no_grad():
            for images, targets, target_lengths in val_loader:
                images = images.to(device)
                targets = targets.to(device)

                outputs = model(images)
                output_lengths = torch.full((images.size(0),), outputs.size(0), dtype=torch.long)

                loss = ctc_loss(outputs, targets, output_lengths, target_lengths)
                val_loss += loss.item()

        val_loss /= len(val_loader)

        # Learning rate scheduling
        scheduler.step(val_loss)

        # Print progress
        print(f"Epoch {epoch+1}/{NUM_EPOCHS} - Train Loss: {train_loss:.4f}, Val Loss: {val_loss:.4f}")

        # Save best model
        if val_loss < best_val_loss:
            best_val_loss = val_loss
            patience_counter = 0
            torch.save({
                'model_state_dict': model.state_dict(),
                'char_to_idx': char_to_idx,
                'idx_to_char': idx_to_char,
                'num_classes': num_classes
            }, MODEL_OUTPUT_DIR / "lastwar_ocr_real_best.pth")
            print(f"  → New best model saved (val_loss: {val_loss:.4f})")
        else:
            patience_counter += 1

        # Early stopping
        if patience_counter >= PATIENCE:
            print(f"\nEarly stopping triggered after {epoch+1} epochs")
            break

    # Save final model
    torch.save({
        'model_state_dict': model.state_dict(),
        'char_to_idx': char_to_idx,
        'idx_to_char': idx_to_char,
        'num_classes': num_classes
    }, MODEL_OUTPUT_DIR / "lastwar_ocr_real_final.pth")

    print("\n" + "=" * 70)
    print("Training Complete!")
    print(f"  Best validation loss: {best_val_loss:.4f}")
    print(f"  Best model: {MODEL_OUTPUT_DIR / 'lastwar_ocr_real_best.pth'}")
    print(f"  Final model: {MODEL_OUTPUT_DIR / 'lastwar_ocr_real_final.pth'}")
    print("=" * 70)
    print("\nNext step: Test the model with process-screenshots-custom-model.py")
    print("           (update it to use the new 'lastwar_ocr_real_best.pth' model)")

if __name__ == '__main__':
    train_model()
