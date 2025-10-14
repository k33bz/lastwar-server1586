# OCR Training Data - Alliance R5 Names

This directory contains manually verified training data for OCR (Optical Character Recognition) of alliance R5 names from Last War game screenshots.

## Purpose

To improve automated extraction of alliance data from ranking screenshots by providing ground truth labels for R5 names, which often contain:
- Special Unicode characters (Korean, Japanese, emojis)
- Superscript/subscript characters
- Mixed character sets (ASCII + special symbols)
- Numbers and hexadecimal identifiers

## Files

- **alliance_r5_mapping.csv** - CSV format for easy viewing and editing
- **alliance_r5_mapping.json** - JSON format for programmatic access
- **README.md** - This file

## Data Format

### CSV Columns
- `alliance_tag`: The short alliance identifier (e.g., "ORCE", "UvvU")
- `r5_name`: The R5 leader's in-game name (manually verified)
- `image_filename`: Screenshot filename in `ocr/alliance_cards/processed/` or `temp/`
- `date_catalogued`: Date the data was manually verified
- `notes`: Additional information about character types or difficulty

### JSON Structure
```json
{
  "tag": "Alliance tag",
  "r5_name": "R5 leader name",
  "image_file": "filename.png",
  "character_types": ["ascii", "korean", "emoji"],
  "difficulty": "easy|medium|hard"
}
```

## Character Type Categories

- **ascii**: Standard ASCII characters (A-Z, a-z, 0-9)
- **korean**: Korean Hangul characters (한글)
- **emoji**: Emoji characters (😀, ᓚᘏᗢ, etc.)
- **special**: Special Unicode symbols (ღ, ʚɞ, etc.)
- **superscript**: Superscript characters (ᴸᵘᶰᵃ)
- **numeric**: Numbers
- **hex**: Hexadecimal identifiers

## Difficulty Levels

- **easy**: Pure ASCII or simple numeric characters
- **medium**: Mixed character sets (Korean + ASCII, ASCII + special symbols)
- **hard**: Complex Unicode (emojis, superscripts, multiple character sets)

## OCR Challenges

### Hard Cases (Require Special Handling)
1. **UvvU** - `쿠치나 ᓚᘏᗢ` (Korean + rare emoji)
2. **86KO** - `ʚᴸᵘᶰᵃɞ` (Superscript + special brackets)
3. **STR8** - `ULoveGucciiღ` (Heart symbol)

### Medium Cases
1. **NKOT** - `잔인한노니 Noni` (Korean + ASCII mix)
2. **1985** - `최사령 관` (Korean characters)

### Easy Cases
- Most ASCII-only names (EchoJT, MastaGinger, DjNinja17, etc.)
- Simple numeric combinations

## Usage

### For Future OCR Improvements
1. Use these verified labels to test OCR accuracy
2. Identify patterns in misread characters
3. Train custom OCR models with difficult character sets
4. Build character mapping tables for common substitutions

### For Manual Verification
When processing new screenshots:
1. Cross-reference with this dataset
2. Look for recurring R5 names
3. Pay special attention to hard cases
4. Update this dataset when R5 leaders change

## Image Location

Screenshots are stored in:
- `ocr/alliance_cards/processed/` - Fully processed cards
- `ocr/alliance_cards/temp/` - Temporary/working files

## Updates

When alliance R5 leaders change:
1. Add new entry with current date
2. Mark old entry as historical (add note: "R5 changed on YYYY-MM-DD")
3. Keep historical data for tracking leadership changes

## Statistics

- Total entries: 23
- Easy difficulty: 16 (70%)
- Medium difficulty: 3 (13%)
- Hard difficulty: 2 (9%)
- Missing image: 1 (4%)

## Contributing

To add new training data:
1. Take clear screenshot of alliance card
2. Save to `ocr/alliance_cards/processed/` with timestamp filename
3. Manually verify R5 name character-by-character
4. Add entry to both CSV and JSON files
5. Commit with message: "Add OCR training data for [ALLIANCE_TAG]"
