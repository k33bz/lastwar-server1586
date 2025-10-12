#!/usr/bin/env python3
"""
Check GPU Setup for EasyOCR Training
Detects AMD/NVIDIA GPU and provides installation instructions

Version: 1.0.0
Date: 2025-10-11
"""

import sys
import subprocess
import platform

def check_python_version():
    """Check if Python version is compatible"""
    version = sys.version_info
    print(f"Python Version: {version.major}.{version.minor}.{version.micro}")

    if version.major == 3 and 9 <= version.minor <= 11:
        print("  [OK] Python version is compatible with ROCm PyTorch")
    elif version.major == 3 and version.minor >= 12:
        print("  [WARN] Python 3.12+ may not have ROCm PyTorch wheels yet")
        print("  [SUGGEST] Consider using Python 3.11 for AMD GPU training")
    else:
        print("  [ERROR] Python version not recommended for PyTorch")

def check_torch():
    """Check PyTorch installation"""
    print("\nPyTorch Installation:")
    try:
        import torch
        print(f"  PyTorch Version: {torch.__version__}")

        # Check for CUDA/ROCm
        if torch.cuda.is_available():
            print(f"  [OK] GPU Detected: {torch.cuda.get_device_name(0)}")
            print(f"  Device Count: {torch.cuda.device_count()}")

            # Check if ROCm or CUDA
            if hasattr(torch.version, 'hip') and torch.version.hip is not None:
                print(f"  Backend: ROCm (HIP version {torch.version.hip})")
            else:
                print(f"  Backend: CUDA {torch.version.cuda}")
        else:
            print("  [WARN] No GPU detected - using CPU")
            print("  [INFO] Training will be slow (~2-3 hours)")

    except ImportError:
        print("  [ERROR] PyTorch not installed")
        print("\n  Install PyTorch:")
        if platform.system() == "Windows":
            print("    # For AMD GPU (ROCm 6.2)")
            print("    pip install torch torchvision --index-url https://download.pytorch.org/whl/rocm6.2")
            print("\n    # For NVIDIA GPU (CUDA 12.1)")
            print("    pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121")
            print("\n    # For CPU only")
            print("    pip install torch torchvision")
        else:
            print("    # Follow instructions at: https://pytorch.org/get-started/locally/")

def check_dependencies():
    """Check other required packages"""
    print("\nDependencies:")
    required_packages = {
        'PIL': 'pillow',
        'numpy': 'numpy',
        'easyocr': 'easyocr',
        'cv2': 'opencv-python'
    }

    for module, package in required_packages.items():
        try:
            __import__(module)
            print(f"  [OK] {package} installed")
        except ImportError:
            print(f"  [MISSING] {package} not installed")
            print(f"    [INSTALL] pip install {package}")

def check_amd_gpu():
    """Check for AMD GPU and ROCm"""
    print("\nAMD GPU Detection:")

    if platform.system() == "Windows":
        # Check for AMD GPU via DirectX
        try:
            result = subprocess.run(['wmic', 'path', 'win32_VideoController', 'get', 'name'],
                                     capture_output=True, text=True, timeout=5)
            if 'AMD' in result.stdout or 'Radeon' in result.stdout:
                print("  [OK] AMD GPU detected via WMIC")
                print(f"  GPU: {result.stdout.strip().split(chr(10))[1].strip()}")
            else:
                print("  [WARN] No AMD GPU detected")
        except Exception as e:
            print(f"  [WARN] Could not detect GPU: {e}")

    else:  # Linux
        # Check for ROCm
        try:
            result = subprocess.run(['rocm-smi'], capture_output=True, text=True, timeout=5)
            if result.returncode == 0:
                print("  [OK] ROCm detected (rocm-smi available)")
            else:
                print("  [ERROR] ROCm not installed")
                print("    [INSTALL] Install from: https://rocm.docs.amd.com/")
        except FileNotFoundError:
            print("  [ERROR] ROCm not found")
            print("    [INSTALL] Install ROCm for AMD GPU support")

def check_training_data():
    """Check if training data exists"""
    from pathlib import Path

    print("\nTraining Data:")
    training_dir = Path(__file__).parent / "training_data"
    metadata_file = training_dir / "metadata.json"

    if metadata_file.exists():
        import json
        with open(metadata_file, 'r', encoding='utf-8') as f:
            metadata = json.load(f)
        tag_count = len(metadata.get('alliance_tags', []))
        name_count = len(metadata.get('r5_names', []))
        print(f"  [OK] Training data found")
        print(f"    Alliance Tags: {tag_count} images")
        print(f"    R5 Names: {name_count} images")
    else:
        print(f"  [MISSING] Training data not found")
        print(f"    [GENERATE] Run: python ocr/generate-training-data.py")

def main():
    """Main check function"""
    print("=" * 70)
    print("GPU Setup Check for EasyOCR Training")
    print("=" * 70)
    print()

    check_python_version()
    check_torch()
    check_dependencies()
    check_amd_gpu()
    check_training_data()

    print()
    print("=" * 70)
    print("Setup Summary")
    print("=" * 70)
    print()
    print("Next Steps:")
    print("  1. Install missing dependencies (see above)")
    print("  2. Generate training data: python ocr/generate-training-data.py")
    print("  3. Train model: python ocr/train-easyocr-model.py")
    print()
    print("For detailed setup instructions, see: ocr/TRAINING_SETUP.md")
    print("=" * 70)

if __name__ == '__main__':
    main()
