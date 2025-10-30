# OCR System Extraction Guide

**Date:** 2025-10-28
**Action:** Moved OCR/Tesseract work to separate repository

---

## Background

The OCR (Optical Character Recognition) system was originally part of the Server 1586 website repository for processing alliance ranking screenshots. As the OCR system evolved with training data, models, and specialized tools, it became clear it should be its own independent project.

**Reasons for separation:**
- OCR is a standalone tool, not part of the website
- Different deployment and development workflows
- Large binary files (training data, models)
- Independent versioning and releases
- Reusable for other Last War servers

---

## What Was Removed

### Directories
```
ocr/                    # Main OCR processing scripts
tesseract_training/     # Tesseract training data and configs
.kiro/                  # Kiro IDE configuration
```

### Files
```
setup-kiro-git.bat      # Kiro setup script
```

---

## Extracting OCR to New Repository

If you want to continue OCR development, follow these steps to create a new repository with the full history:

### Method 1: Simple Copy (Recommended)

**1. Create new repository:**
```bash
mkdir lastwar-ocr
cd lastwar-ocr
git init
```

**2. Copy OCR files from Server1586:**
```bash
# From Server1586-clean directory
cp -r ocr/* ../lastwar-ocr/
cp -r tesseract_training ../lastwar-ocr/
```

**3. Create new README:**
```bash
cd ../lastwar-ocr
cat > README.md << 'EOF'
# Last War Alliance OCR

Optical Character Recognition system for processing Last War alliance ranking screenshots.

## Features
- Tesseract-based OCR
- EasyOCR integration
- Custom training for Last War screenshots
- Alliance tag and power extraction
- Manual correction tools

## Installation
```bash
pip install pytesseract easyocr opencv-python pillow
```

## Usage
```bash
python process-screenshots-v3.py
```

See documentation in `ocr/README.md` for details.
EOF
```

**4. Initial commit:**
```bash
git add .
git commit -m "Initial commit: Last War OCR system

Extracted from Server 1586 website repository.
OCR system is now standalone for processing alliance screenshots."
```

**5. Push to GitHub:**
```bash
gh repo create lastwar-ocr --public --source=. --remote=origin
git push -u origin main
```

---

### Method 2: Preserve Git History (Advanced)

If you want to preserve the Git history of OCR-related files:

```bash
# Clone the original repo
git clone https://github.com/k33bz/lastwar-server1586.git lastwar-ocr
cd lastwar-ocr

# Filter to only OCR-related commits
git filter-branch --prune-empty --subdirectory-filter ocr HEAD

# Or use git-filter-repo (better tool)
pip install git-filter-repo
git filter-repo --path ocr/ --path tesseract_training/

# Create new remote
gh repo create lastwar-ocr --public
git remote set-url origin https://github.com/k33bz/lastwar-ocr.git
git push -u origin main
```

---

## OCR Repository Structure

Suggested structure for the new repository:

```
lastwar-ocr/
├── README.md
├── requirements.txt
├── .gitignore
├── docs/
│   ├── TRAINING_GUIDE.md
│   ├── OCR_TRAINING_PHASES.md
│   └── SETUP.md
├── src/
│   ├── process_screenshots.py
│   ├── train_model.py
│   └── manual_entry_tool.py
├── models/
│   ├── tesseract/
│   └── easyocr/
├── training_data/
│   ├── images/
│   └── labels/
├── tests/
│   └── test_ocr.py
└── examples/
    └── sample_screenshots/
```

---

## Integration with Server 1586

The OCR system can still be used with Server 1586 as an external tool:

### Option 1: Manual Integration
1. Run OCR in separate repository
2. Generate `alliances.json` output
3. Copy to Server 1586 `data/` directory
4. Deploy Server 1586

### Option 2: CI/CD Pipeline
```yaml
# .github/workflows/update-rankings.yml
name: Update Rankings from OCR

on:
  repository_dispatch:
    types: [ocr-complete]

jobs:
  update:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Server1586
        uses: actions/checkout@v4
        with:
          repository: k33bz/lastwar-server1586

      - name: Download OCR output
        run: |
          curl -o data/alliances.json \
            https://github.com/k33bz/lastwar-ocr/releases/latest/download/alliances.json

      - name: Commit and push
        run: |
          git config user.name "OCR Bot"
          git config user.email "bot@example.com"
          git add data/alliances.json
          git commit -m "chore: Update alliance rankings from OCR"
          git push
```

### Option 3: Git Submodule
```bash
# In Server1586 repository
git submodule add https://github.com/k33bz/lastwar-ocr.git tools/ocr
git submodule update --init --recursive

# Run OCR
cd tools/ocr
python src/process_screenshots.py --output ../../data/alliances.json
```

---

## Migration Checklist

If you're setting up the new OCR repository:

- [ ] Create new repository: `lastwar-ocr`
- [ ] Copy OCR files and history
- [ ] Create comprehensive README
- [ ] Add requirements.txt
- [ ] Update .gitignore for training data
- [ ] Document installation process
- [ ] Add usage examples
- [ ] Create release with sample data
- [ ] Update Server 1586 README with OCR link
- [ ] Archive OCR issues in Server 1586 repo
- [ ] Create new issues in OCR repo

---

## Dependencies

**Python Libraries:**
```txt
pytesseract>=0.3.10
tesseract-ocr>=5.0.0
easyocr>=1.7.0
opencv-python>=4.8.0
Pillow>=10.0.0
numpy>=1.24.0
```

**System Requirements:**
- Tesseract OCR engine
- CUDA (optional, for GPU acceleration)
- 2GB+ RAM for model loading

---

## References

**Original OCR Documentation:**
- `ocr/README.md` - Main OCR documentation
- `ocr/OCR_TRAINING_PHASES.md` - Training process
- `ocr/TRAINING_SETUP.md` - Environment setup
- `tesseract_training/TRAINING_INSTRUCTIONS.md` - Tesseract training

**New Repository (once created):**
- https://github.com/k33bz/lastwar-ocr (planned)

**Related Projects:**
- [Tesseract OCR](https://github.com/tesseract-ocr/tesseract)
- [EasyOCR](https://github.com/JaidedAI/EasyOCR)

---

## Contact

For OCR-specific questions, please use the new repository's issue tracker once it's created.

For Server 1586 website questions, use:
- https://github.com/k33bz/lastwar-server1586/issues

---

**Last Updated:** 2025-10-28
**Maintained By:** k33bz
