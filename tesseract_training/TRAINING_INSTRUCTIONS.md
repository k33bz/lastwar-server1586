# Tesseract Training Instructions for Last War

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
