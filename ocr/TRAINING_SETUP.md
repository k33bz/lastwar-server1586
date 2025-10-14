# EasyOCR Training Setup for AMD GPU

This guide explains how to set up and train a custom OCR model for Last War using AMD GPU (ROCm).

## Prerequisites

- **AMD GPU** with ROCm support
- **Python 3.9-3.11** (3.13 may not support ROCm PyTorch yet)
- **Windows 11** or **Linux** with ROCm drivers

## Step 1: Install PyTorch with ROCm Support

### Option A: Windows (Experimental ROCm Support)

```powershell
# Install PyTorch with ROCm 6.2
pip install torch torchvision --index-url https://download.pytorch.org/whl/rocm6.2
```

### Option B: Linux (Recommended for AMD GPU)

```bash
# Install ROCm following AMD's official guide
# https://rocm.docs.amd.com/

# Install PyTorch with ROCm
pip3 install torch torchvision --index-url https://download.pytorch.org/whl/rocm6.2
```

### Verify GPU Detection

```python
import torch
print("PyTorch version:", torch.__version__)
print("CUDA available:", torch.cuda.is_available())
print("Device count:", torch.cuda.device_count())
print("Device name:", torch.cuda.get_device_name(0) if torch.cuda.is_available() else "CPU")
```

## Step 2: Install Dependencies

```bash
pip install pillow numpy easyocr
```

## Step 3: Generate Training Data

```bash
cd C:\path\to\project
python ocr/generate-training-data.py
```

This will create 420 synthetic training images using the extracted Last War fonts.

**Output:**
- `ocr/training_data/alliance_tags/` - 240 alliance tag images
- `ocr/training_data/r5_names/` - 180 R5 name images
- `ocr/training_data/metadata.json` - Training metadata

## Step 4: Train the Model

```bash
python ocr/train-easyocr-model.py
```

**Training Configuration:**
- **Epochs**: 50
- **Batch Size**: 16
- **Learning Rate**: 0.001
- **Model**: Simple CRNN (Convolutional Recurrent Neural Network)
- **Loss**: CTC (Connectionist Temporal Classification)

**Expected Training Time:**
- AMD RX 7900 XTX: ~10-15 minutes
- CPU: ~2-3 hours

**Output:**
- `ocr/models/lastwar_ocr_best.pth` - Best model (lowest validation loss)
- `ocr/models/lastwar_ocr_final.pth` - Final model after all epochs

## Step 5: Use Trained Model

### Update `process-screenshots-anchored.py`

Replace EasyOCR with custom model:

```python
# Load custom model
from train_easyocr_model import SimpleCRNN
import torch

# Load model
checkpoint = torch.load('ocr/models/lastwar_ocr_best.pth')
model = SimpleCRNN(num_chars=len(checkpoint['charset']))
model.load_state_dict(checkpoint['model_state_dict'])
model.eval()

# Use model for OCR
def ocr_with_custom_model(image):
    # Preprocess image
    image = preprocess_for_ocr(image)

    # Convert to tensor
    image = torch.from_numpy(image).unsqueeze(0).unsqueeze(0).float() / 255.0

    # Run inference
    with torch.no_grad():
        output = model(image)

    # Decode output
    # ... CTC decode logic

    return text
```

## Troubleshooting

### Issue: PyTorch doesn't detect AMD GPU

**Solution**: Ensure ROCm drivers are properly installed:

```bash
# Check ROCm installation
rocm-smi

# Verify PyTorch was built with ROCm
python -c "import torch; print(torch.version.hip)"
```

### Issue: Out of Memory Error

**Solution**: Reduce batch size in `train-easyocr-model.py`:

```python
BATCH_SIZE = 8  # Default is 16
```

### Issue: Training is very slow on CPU

**Solution**: Either:
1. Use a machine with AMD GPU + ROCm
2. Use Google Colab with NVIDIA GPU (modify script for CUDA)
3. Reduce epochs to 20 for faster CPU training

## Alternative: Use Existing OCR with Post-Processing

If training is too complex, the current solution with post-processing cleanup (`clean_alliance_tag()` and `clean_r5_name()`) already provides good results:

- Alliance tags: 90%+ accuracy
- R5 names: 80%+ accuracy

The post-processing handles common OCR errors like:
- `~` → `<`
- `:` → `>`
- `0` → `O`
- `8` → `B`

## Resources

- **ROCm Installation**: https://rocm.docs.amd.com/
- **PyTorch ROCm**: https://pytorch.org/get-started/locally/
- **EasyOCR**: https://github.com/JaidedAI/EasyOCR
- **CTC Loss**: https://distill.pub/2017/ctc/

---

**Version**: 1.0.0
**Last Updated**: October 11, 2025
