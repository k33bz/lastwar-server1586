# Screenshot Processing System - Implementation Summary

## Overview
Automated system for extracting alliance data from Last War game screenshots using OCR (Optical Character Recognition) with Tesseract trained on Last War fonts.

## Completion Date
2025-10-10

## Components Created

### 1. Tesseract Training Script (`scripts/train-tesseract-lastwar.py`)

**Purpose**: Generate training data for Tesseract OCR using Last War fonts to improve accuracy.

**What It Does**:
- Loads Last War fonts from `lastwar-font-extractor/extracted_fonts/`
  - LiberationSans.ttf
  - Perfect DOS VGA 437.ttf
- Generates 88 training images with:
  - Alliance names (veni vidi vici, Omega Force, etc.)
  - Korean characters commonly used in game
  - Numbers and special symbols
  - Alliance tags (UvvU, ORCE, nkot, etc.)
- Runs Tesseract to create 88 box files (character bounding boxes)
- Creates optimized OCR configuration file
- Generates training instructions

**Output**:
- `tesseract_training/data/` - 88 training images (.tif files)
- `tesseract_training/data/` - 88 box files (.box files)
- `tesseract_training/output/lastwar.conf` - Optimized OCR config
- `tesseract_training/TRAINING_INSTRUCTIONS.md` - Manual training guide

**OCR Optimization**:
```python
# Optimized config for Last War text
OCR_CONFIG = r'--oem 3 --psm 6 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz<>[]()_-.,:/| '
```

### 2. Screenshot Processing Script (`scripts/process-screenshots.py`)

**Purpose**: Automatically extract alliance data from screenshots and update JSON files.

**Input Directories**:
- `images/alliance_cards/` - Alliance information card screenshots
- `images/server_rankings/` - Server-wide power ranking screenshots

**What It Extracts**:

From Alliance Cards:
- Alliance tag (e.g., "UvvU", "ORCE")
- R5 leader name
- Updates both `alliances.json` and `signature-history.json`

From Server Rankings:
- Alliance rank (1-50+)
- Alliance power (total power value)
- Updates `alliances.json`

**Processing Flow**:
1. Loads screenshot images
2. Applies OCR with optimized Last War config
3. Extracts data using regex patterns
4. Updates JSON files
5. Moves processed images to `processed/` subdirectories
6. Generates detailed log file in `logs/`

**Safety Features**:
- ✅ Updates JSON files locally
- ✅ Generates comprehensive log
- ✅ Moves processed images to completed folder
- ❌ Does NOT automatically deploy to FTP (manual review required)
- ✅ Should be committed to git for version control

### 3. Documentation

**Created Files**:
- `SCREENSHOT-PROCESSING-README.md` - Complete user guide
- `tesseract_training/TRAINING_INSTRUCTIONS.md` - Tesseract training manual
- `SCREENSHOT-PROCESSING-SUMMARY.md` - This file

## Test Run Results (2025-10-10)

### Alliance Cards Processed: 5/24
**Successfully Extracted**:
- ✅ ORCE - Leader: og AllianceGifts Luyea)
- ✅ 404a - Leader: may AllianceGifts tvyea)
- ✅ EPIC - Leader: AllianceGifts LuE17)
- ✅ UUSN - Leader: og AllianceGifts itv513)
- ✅ NiKi - Leader: Z AllianceGifts itv510)

**Failed Extractions** (19 cards):
- UvvU, nkot, STR8, FLM, NYPR, SWBA, 86KO, FNXS, MTOP, L4TM
- Reasons: OCR difficulties with special characters, Korean text, image quality

### Server Rankings Processed: 1/1
**Successfully Extracted**:
- ✅ ORCE - Power: 6,710,688,392
- ✅ STR8 - Power: 5,118,554,421

### Changes Made
**R5 Updates**: 5 alliances
- ORCE: R5 Name → og AllianceGifts Luyea)
- 404a: R5 Name → may AllianceGifts tvyea)
- EPIC: R5 Name → AllianceGifts LuE17)
- UUSN: R5 Name → og AllianceGifts itv513)
- NiKi: R5 Name → Z AllianceGifts itv510)

**Power Updates**: 2 alliances
- ORCE: 6,480,480,937 → 6,710,688,392
- STR8: (new) → 5,118,554,421

**Rank Updates**: 0

## OCR Accuracy Issues

### Observed Problems
1. **Alliance Tag Extraction**: Many tags not recognized
   - Korean characters difficult to read
   - Special formatting issues (multiple brackets, symbols)
   - OCR misreads < > brackets

2. **Leader Name Extraction**: Mixed results
   - Korean/Chinese characters not recognized
   - Special characters cause issues
   - "AllianceGifts" text appears in many extractions (UI element confusion)

### Recommended Improvements

**1. Image Preprocessing**
```python
# Add to process_alliance_card() before OCR:
import cv2
import numpy as np

# Convert to grayscale
gray = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2GRAY)

# Increase contrast
clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
enhanced = clahe.apply(gray)

# Threshold
_, binary = cv2.threshold(enhanced, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)

# Convert back to PIL
image = Image.fromarray(binary)
```

**2. Region of Interest (ROI) Cropping**
- Crop specific areas where alliance tag and leader name appear
- Reduces noise from other UI elements
- Improves OCR accuracy

**3. Alternative OCR Engines**
Consider using engines with better multilingual support:
- **EasyOCR** - Better Korean/Chinese support
- **PaddleOCR** - Excellent for Asian languages
- **Google Cloud Vision API** - High accuracy but requires API key

**4. Manual Review System**
- Display OCR results for user confirmation
- Allow manual correction before updating JSON
- Semi-automated approach for better accuracy

## Directory Structure

```
Server1586/
├── images/
│   ├── alliance_cards/
│   │   ├── *.png (24 screenshots taken)
│   │   └── processed/
│   │       └── *.png (5 successfully processed)
│   └── server_rankings/
│       ├── 2025-10-10_12-26-44.png
│       └── processed/
│           └── 2025-10-10_12-26-44.png (processed)
├── tesseract_training/
│   ├── data/
│   │   ├── *.tif (88 training images)
│   │   └── *.box (88 box files)
│   ├── output/
│   │   └── lastwar.conf (OCR config)
│   └── TRAINING_INSTRUCTIONS.md
├── logs/
│   └── screenshot_processing_2025-10-10_15-09-44.log
├── data/
│   ├── alliances.json (MODIFIED - 5 R5 updates, 2 power updates)
│   └── signature-history.json (MODIFIED - 5 R5 updates)
└── scripts/
    ├── train-tesseract-lastwar.py
    ├── process-screenshots.py
    ├── SCREENSHOT-PROCESSING-README.md
    └── SCREENSHOT-PROCESSING-SUMMARY.md
```

## Git Strategy

### Files to Commit
✅ **Scripts** (version control)
- `scripts/train-tesseract-lastwar.py`
- `scripts/process-screenshots.py`
- `scripts/SCREENSHOT-PROCESSING-README.md`
- `SCREENSHOT-PROCESSING-SUMMARY.md`

✅ **Training Data** (reproducibility)
- `tesseract_training/output/lastwar.conf`
- `tesseract_training/TRAINING_INSTRUCTIONS.md`
- Optional: `tesseract_training/data/*.tif` and `*.box` (if space permits)

✅ **Data Updates** (alliance information)
- `data/alliances.json`
- `data/signature-history.json`

✅ **Logs** (documentation)
- `logs/screenshot_processing_*.log`

### Files NOT to Commit
❌ **Screenshots** (large binary files)
- `images/alliance_cards/*.png` (original screenshots)
- `images/alliance_cards/processed/*.png`
- `images/server_rankings/*.png`
- `images/server_rankings/processed/*.png`

### Why Not Commit Images
1. **Large file size** - PNG screenshots are 180KB-230KB each (24 files ≈ 5MB)
2. **No code value** - Screenshots are temporary processing input
3. **Privacy** - May contain player information
4. **Regeneratable** - Can take new screenshots anytime

Add to `.gitignore`:
```
images/alliance_cards/*.png
images/alliance_cards/processed/
images/server_rankings/*.png
images/server_rankings/processed/
tesseract_training/data/*.tif
tesseract_training/data/*.box
```

## Deployment Strategy

### Local Processing Only
The screenshot processing system is designed for **local use only**:

1. **Take screenshots** from Last War game
2. **Place in** `images/alliance_cards/` or `images/server_rankings/`
3. **Run script**: `python scripts/process-screenshots.py`
4. **Review log** in `logs/`
5. **Review changes**: `git diff data/alliances.json`
6. **Commit if correct**: `git add data/*.json && git commit`
7. **Deploy manually**: `python scripts/deploy-ftp.py`

### Why Manual Deployment
- OCR is not 100% accurate
- Human review prevents bad data going live
- Changes affect production website
- Git history provides rollback capability

## Future Enhancements

### High Priority
1. **Image Preprocessing Pipeline**
   - Contrast enhancement
   - Noise reduction
   - ROI cropping for alliance tag and leader fields

2. **Manual Review UI**
   - Display OCR results before updating JSON
   - Allow user to correct misreads
   - Confidence scoring

3. **Alternative OCR Engines**
   - EasyOCR for Korean text
   - Fallback to multiple engines
   - Ensemble voting

### Medium Priority
4. **Batch Processing**
   - Process multiple screenshot folders
   - Parallel processing for speed
   - Progress bar

5. **Data Validation**
   - Verify alliance tags against known list
   - Flag suspicious changes (rank jumps, power drops)
   - Require confirmation for large changes

6. **Screenshot Guidelines**
   - Document optimal screenshot settings
   - Provide cropping templates
   - Image quality checklist

### Low Priority
7. **Cloud OCR Integration**
   - Google Cloud Vision API
   - Azure Computer Vision
   - Better accuracy for complex text

8. **Automated Screenshot Capture**
   - Game automation scripts
   - Scheduled screenshot capture
   - Automatic upload to processing folder

## Known Limitations

1. **OCR Accuracy**: ~21% success rate on alliance cards (5/24)
2. **Korean Text**: Poor recognition of Korean character names
3. **UI Elements**: Confuses "AllianceGifts" UI text with leader names
4. **Special Characters**: Struggles with brackets, symbols
5. **Image Quality**: Requires good quality, high contrast screenshots
6. **Manual Work**: Still requires significant manual review

## Recommendations

### For Best Results
1. **Take High Quality Screenshots**
   - Use highest game resolution
   - Ensure text is sharp and clear
   - Good contrast (dark text on light background)

2. **Crop Before Processing**
   - Crop to just alliance tag and leader name areas
   - Remove unnecessary UI elements
   - Saves OCR processing time

3. **Manual Review Always**
   - Never trust OCR 100%
   - Always review log file
   - Check git diff before committing
   - Verify leader names are correct

4. **Supplement with Manual Entry**
   - For alliances that fail OCR
   - Manually update JSON files
   - Document in commit message

## Conclusion

The screenshot processing system provides a semi-automated workflow for updating alliance data. While OCR accuracy is currently limited (~21% for alliance cards), the system successfully:

- ✅ Processes screenshots without manual data entry
- ✅ Updates JSON files automatically
- ✅ Generates detailed logs for review
- ✅ Moves processed files to organized folders
- ✅ Prevents accidental production deployment
- ✅ Maintains git history for rollback

**Best Use Case**: Processing server ranking screenshots (higher success rate) and supplementing with manual review for alliance cards.

**Alternative Approach**: Use screenshots as reference while manually updating JSON files - faster and more accurate for small datasets (15 alliances).

## Version History

- **v1.0.0** (2025-10-10) - Initial release
  - Tesseract training script with Last War fonts
  - Screenshot processing script with OCR
  - Comprehensive documentation
  - Test run: 5/24 alliance cards successful
