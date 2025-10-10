# Screenshot Processing Script

Automated extraction of alliance data from game screenshots.

## Overview

This script processes two types of screenshots:
1. **Alliance Cards** - Individual alliance information cards showing R5 leader names
2. **Server Rankings** - Full server ranking list with power and rank data

The script uses OCR (Optical Character Recognition) to extract data, updates JSON files, moves processed images to a 'processed' folder, and generates detailed logs.

## Prerequisites

### 1. Install Tesseract OCR

**Windows:**
1. Download from: https://github.com/tesseract-ocr/tesseract
2. Install to default location: `C:\Program Files\Tesseract-OCR\`
3. Or update `TESSERACT_CMD` path in script if installed elsewhere

**Mac:**
```bash
brew install tesseract
```

**Linux:**
```bash
sudo apt-get install tesseract-ocr
```

### 2. Install Python Packages

```bash
pip install pytesseract pillow opencv-python
```

## Directory Structure

```
Server1586/
├── images/
│   ├── alliance_cards/
│   │   ├── 2025-10-10_12-27-10.png
│   │   ├── 2025-10-10_12-27-22.png
│   │   └── processed/              # Processed images moved here
│   └── server_rankings/
│       ├── 2025-10-10_12-26-44.png
│       └── processed/              # Processed images moved here
├── data/
│   ├── alliances.json              # Updated by script
│   └── signature-history.json      # Updated by script
├── logs/
│   └── screenshot_processing_*.log # Generated logs
└── scripts/
    └── process-screenshots.py      # This script
```

## Usage

### Basic Usage

```bash
python scripts/process-screenshots.py
```

### What It Does

1. **Scans Images**
   - Looks for PNG files in `images/alliance_cards/`
   - Looks for PNG files in `images/server_rankings/`

2. **Processes Alliance Cards**
   - Extracts alliance tag (e.g., "UvvU", "ORCE")
   - Extracts R5 leader name
   - Updates `data/alliances.json` with R5 name
   - Updates `data/signature-history.json` with current R5

3. **Processes Server Rankings**
   - Extracts rank for each alliance
   - Extracts power for each alliance
   - Updates `data/alliances.json` with rank and power

4. **Moves Processed Files**
   - Moves processed alliance cards to `images/alliance_cards/processed/`
   - Moves processed rankings to `images/server_rankings/processed/`

5. **Generates Log**
   - Creates detailed log in `logs/screenshot_processing_YYYY-MM-DD_HH-MM-SS.log`
   - Includes timestamp, actions taken, changes made, errors encountered

## Screenshot Requirements

### Alliance Cards
- Should clearly show:
  - Alliance tag in format `<TAG>` or `[TAG]`
  - Leader name in "Leader" field
  - Good image quality (not blurry)

### Server Rankings
- Should clearly show:
  - Rank number
  - Alliance tag
  - Power value
  - All text readable (not cut off)

## Data Updates

### Files Modified
- `data/alliances.json` - R5 names, ranks, power values
- `data/signature-history.json` - Current R5 names in history

### Files NOT Modified
- Script does **NOT** automatically deploy to FTP
- Changes are local only until you manually deploy

## Output

### Console Output
```
======================================================================
Server 1586 - Screenshot Processing
======================================================================

[1/2] Processing Alliance Card Screenshots...

[12:34:56] Processing alliance card: 2025-10-10_12-27-10.png
[12:34:57]   ✓ Extracted: UvvU - Leader: 무적나_t_0로
[12:34:57]   📝 Updated alliances.json: UvvU R5 = 무적나_t_0로
[12:34:57]   → Moved to processed folder

...

[2/2] Processing Server Ranking Screenshots...

[12:35:10] Processing server ranking: 2025-10-10_12-26-44.png
[12:35:12]   ✓ Found UvvU: Rank=1, Power=6777666619
[12:35:12]   📝 Updated rank: UvvU = 1
[12:35:12]   📝 Updated power: UvvU = 6,777,666,619

...

======================================================================
Processing Complete!
======================================================================
Alliance cards: 15 processed
Server rankings: 1 processed
R5 updates: 15
Rank updates: 3
Power updates: 15

[12:35:20] Log saved: logs/screenshot_processing_2025-10-10_12-35-00.log

======================================================================
Next Steps:
======================================================================
✓ Data files updated (alliances.json, signature-history.json)
✓ Changes committed to git (for version control)
⚠️  Changes NOT deployed to production (manual FTP deployment required)

To deploy to production:
  1. Review changes in git diff
  2. Commit changes: git add data/*.json && git commit -m 'data: Update alliance info'
  3. Deploy: python scripts/deploy-ftp.py
```

### Log File Content
```
======================================================================
Server 1586 - Screenshot Processing Log
Processed: 2025-10-10 12:35:20
======================================================================

[12:34:56] Processing alliance card: 2025-10-10_12-27-10.png
[12:34:57]   ✓ Extracted: UvvU - Leader: 무적나_t_0로
...

======================================================================
Summary of Changes:
======================================================================

R5 Leader Updates:
  • UvvU: R5 Name → 무적나_t_0로
  • ORCE: R5 Name → 쿠폐나
  • nkot: R5 Name → 이동국
  ...

Rank Updates:
  • ORCE: Rank 1 → 2
  • UvvU: Rank 2 → 1

Power Updates:
  • UvvU: 6,727,666,619 → 6,777,666,619
  • ORCE: 6,480,480,937 → 6,752,848,288
  ...
```

## Git Workflow

### Recommended Process

1. **Run Script**
   ```bash
   python scripts/process-screenshots.py
   ```

2. **Review Changes**
   ```bash
   git diff data/alliances.json
   git diff data/signature-history.json
   ```

3. **Commit Changes**
   ```bash
   git add data/alliances.json data/signature-history.json
   git commit -m "data: Update alliance R5 leaders, ranks, and power from screenshots"
   ```

4. **Push to GitHub**
   ```bash
   git push origin mainline
   ```

5. **Deploy to Production (Manual)**
   ```bash
   python scripts/deploy-ftp.py
   ```

## Why Changes Are Not Auto-Deployed

The script does **NOT** automatically deploy changes to production for safety:

1. **Review First** - Always review OCR-extracted data before deploying
2. **Manual Control** - Gives you control over when production is updated
3. **Git History** - Commit to git first for version control
4. **Error Prevention** - OCR can make mistakes, so review is important

## Troubleshooting

### "Tesseract not found"
- Install Tesseract OCR from https://github.com/tesseract-ocr/tesseract
- Update `TESSERACT_CMD` path in script if installed to non-default location

### "Could not extract alliance tag"
- Check image quality (not blurry, good contrast)
- Ensure alliance tag is visible in format `<TAG>` or `[TAG]`
- Try preprocessing image (crop, enhance contrast)

### "Could not extract leader name"
- Ensure "Leader" field is visible in screenshot
- Check that leader name is clearly readable
- Try cropping image to focus on alliance card area

### "No alliance data extracted"
- Check if server ranking screenshot shows full alliance list
- Ensure text is not cut off at edges
- Verify image is not too compressed (low quality)

### OCR Accuracy Issues
- Use high-resolution screenshots
- Ensure good lighting/contrast in screenshots
- Crop out unnecessary UI elements
- For non-English names, Tesseract may struggle - manual review recommended

## Manual Verification

After running the script, always:

1. **Check Logs** - Review `logs/screenshot_processing_*.log` for errors
2. **Review JSON Changes** - Use `git diff` to see what changed
3. **Test Locally** - Load website locally to verify data looks correct
4. **Deploy Carefully** - Only deploy after verifying changes are correct

## Alliance Tag Order

The script expects alliances in this order (Top 15):

1. UvvU
2. ORCE
3. nkot
4. 404a
5. FLM
6. STR8
7. EPIC
8. NYPR
9. 86KO
10. SWBA
11. MTOP
12. UUSN
13. FNXS
14. L4TM
15. NiKi

If ranking changes, update `ALLIANCE_ORDER` list in script.

## Example Workflow

```bash
# 1. Take screenshots in game
# 2. Save to images/alliance_cards/ and images/server_rankings/

# 3. Run processing script
python scripts/process-screenshots.py

# 4. Review log file
cat logs/screenshot_processing_2025-10-10_12-35-00.log

# 5. Check changes
git diff data/alliances.json

# 6. If changes look good, commit
git add data/*.json
git commit -m "data: Update alliance info from 2025-10-10 screenshots"
git push

# 7. Deploy to production
python scripts/deploy-ftp.py

# 8. Verify on website
# Visit https://www.example.com
```

## Version History

- **v1.0.0** (2025-10-10) - Initial release
  - Alliance card processing
  - Server ranking processing
  - R5 name extraction
  - Rank and power extraction
  - Automatic file moving
  - Log generation

## Support

For issues:
1. Check log file for detailed error messages
2. Verify Tesseract is installed and accessible
3. Check image quality and format
4. Review OCR text output in log for accuracy
5. Manually verify extracted data before deploying
