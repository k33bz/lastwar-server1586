# OCR System - Alliance R5 Name Recognition

Advanced OCR (Optical Character Recognition) system for automatically extracting alliance R5 names from Last War screenshots.

## 📍 Navigation
- **← Back to Main**: [../README.md](../README.md)
- **📚 Full Documentation**: [../DOCUMENTATION.md](../DOCUMENTATION.md)
- **🔧 Admin Panel**: [../admin/README.md](../admin/README.md)
- **📊 Training Data**: [training_data/README.md](training_data/README.md)

## 📋 Documentation

- **[OCR Training Phase Plan](OCR_TRAINING_PHASES.md)** - Complete 3-phase training roadmap
- **[Phase 1 Summary](PHASE1_SUMMARY.md)** - Phase 1 accomplishments and baseline results
- **[Training Data README](training_data/README.md)** - Ground truth data documentation

## 🎯 Current Status

**✅ Phase 1 Complete** - Foundation established with 23 training examples

### Baseline Accuracy Results
| Engine | Word Accuracy | Character Accuracy |
|--------|---------------|-------------------|
| Tesseract | 33.3% | 62.4% |
| EasyOCR | 50.0% | 72.4% |

**Key Findings:**
- ✅ ASCII-only names: 100% accuracy
- ✅ Korean text: EasyOCR 100%, Tesseract fails
- ❌ Special Unicode (ღ, ʚ, ɞ, superscripts): 0% accuracy

## 📁 Directory Structure

```
ocr/
├── README.md                       # This file
├── OCR_TRAINING_PHASES.md          # Complete 3-phase training plan
├── PHASE1_SUMMARY.md               # Phase 1 summary report
│
├── tools/                          # OCR processing tools
│   ├── character_mapping.json      # OCR error patterns (20+ mappings)
│   ├── substitute_characters.py    # Character substitution engine
│   └── validate_ocr.py             # OCR validation test suite
│
├── reports/                        # Accuracy reports
│   └── baseline_accuracy.json      # Phase 1 baseline results
│
├── training_data/                  # Ground truth training data
│   ├── README.md                   # Training data documentation
│   ├── alliance_r5_mapping.csv     # 23 training examples (CSV)
│   └── alliance_r5_mapping.json    # 23 training examples (JSON)
│
└── alliance_cards/                 # Screenshot storage
    ├── processed/                  # Fully processed cards
    └── temp/                       # Working/temporary files
```

## 🚀 Phase 1 Deliverables

### 1. Character Substitution Mapping
**File:** `tools/character_mapping.json`

Comprehensive OCR error patterns:
- 8+ common character substitutions (ღ → o/g, ʚ → 3/e, etc.)
- Korean character patterns and common misreads
- Context rules for alliance-specific corrections
- Confidence indicators for quality assessment
- Engine-specific recommendations

### 2. Character Substitution Tool
**File:** `tools/substitute_characters.py`

```bash
cd tools
python substitute_characters.py
```

Features:
- CharacterSubstitutor class for applying corrections
- Confidence scoring system
- Context-based correction suggestions
- Engine recommendations based on character types

### 3. OCR Validation Test Suite
**File:** `tools/validate_ocr.py`

```bash
cd tools
python validate_ocr.py
```

Features:
- Automated testing against ground truth
- Character-level and word-level accuracy metrics
- Per-engine performance comparison
- Difficulty breakdown (easy/medium/hard)
- Human-readable reports + JSON export

### 4. Training Data
**Directory:** `training_data/`

23 manually verified alliance R5 names with:
- Alliance tags and R5 names (exact Unicode)
- Screenshot filenames
- Character type categorization
- Difficulty ratings (easy/medium/hard)
- Available in both CSV and JSON formats

See [training_data/README.md](training_data/README.md) for details.

## 📈 Next Steps: Phase 2

**Goal:** Expand to 50 training examples

### Data Collection Needed (27 more examples)

**High Priority:**
- Korean character names: 12 more needed
- Emoji names: 7 more needed
- Superscript/subscript: 5 more needed

**Collection Format:**
```
TAG R5_Name filename.png
```

### Development Tasks (After Data Collection)
- Fine-tune Tesseract with custom training data
- Build confidence scoring system (multi-engine voting)
- Enhanced character mapping with fuzzy matching
- Auto-flagging of low-confidence results

See [OCR_TRAINING_PHASES.md](OCR_TRAINING_PHASES.md) for complete Phase 2 requirements.

## 🛠️ Usage

### Test Character Substitution
```bash
cd tools
python substitute_characters.py
```

### Run OCR Validation
```bash
cd tools
python validate_ocr.py
```

### View Baseline Report
```bash
cat reports/baseline_accuracy.json
```

## 📊 Training Progress

- **Phase 1**: ✅ Complete (23 examples, baseline measured)
- **Phase 2**: ⏳ Pending (need 27 more examples → 50 total)
- **Phase 3**: ⏳ Pending (need 50 more examples → 100 total)

## 🔗 Related Files

- [Character mapping](tools/character_mapping.json) - OCR error patterns
- [Training plan](OCR_TRAINING_PHASES.md) - Complete 3-phase roadmap
- [Phase 1 summary](PHASE1_SUMMARY.md) - Baseline results and findings
- [Training data](training_data/README.md) - Ground truth documentation

---

**Last Updated:** October 14, 2025
**Status:** Phase 1 Complete ✅
**Next Milestone:** 50 training examples for Phase 2
---

## 📞 Support & Contact

For questions about OCR and image processing:
- **Main Documentation**: [../README.md](../README.md)
- **GitHub Issues**: [Report bugs or request features](https://github.com/username/your-repo/issues)
- **Training Data**: [training_data/README.md](training_data/README.md)

---

**Version**: 3.0.0 | **Last Updated**: October 16, 2025 | **Part of**: [Server 1586 Project](../README.md)