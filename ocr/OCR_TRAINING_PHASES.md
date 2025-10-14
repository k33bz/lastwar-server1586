# OCR Training Phase Plan

## Overview
Progressive approach to training OCR for alliance R5 name recognition, starting with 23 manually verified examples and scaling up as more data is collected.

---

## Phase 1: Foundation with 23 Examples ✓ IN PROGRESS

**Current Dataset:** 23 manually verified alliance R5 names
**Timeline:** Immediate
**Goal:** Establish baseline and build validation infrastructure

### Tasks:

#### 1.1 Create Character Substitution Mapping
- Analyze common OCR mistakes in special characters
- Build lookup table for character replacements
- Document Korean character OCR patterns
- Map emoji/special Unicode to common misreads
- **Deliverable:** `ocr/character_mapping.json` and `ocr/tools/substitute_characters.py`

#### 1.2 Build OCR Validation Test Suite
- Create test script that processes all 23 training images
- Run multiple OCR engines (Tesseract, EasyOCR, PaddleOCR)
- Compare results against ground truth
- Generate accuracy reports
- **Deliverable:** `ocr/tools/validate_ocr.py` and accuracy report

#### 1.3 Measure Baseline OCR Accuracy
- Test each OCR engine on training set
- Calculate character-level accuracy
- Calculate word-level accuracy
- Identify systematic errors by character type
- **Deliverable:** `ocr/reports/baseline_accuracy.json`

#### 1.4 Documentation
- Document all phases and requirements
- Create data collection guidelines
- Add usage examples
- **Deliverable:** This document and updated README files

### Success Criteria:
- [x] Character mapping table created with 20+ mappings
- [x] Validation script runs successfully on all 23 images
- [x] Baseline accuracy measured for at least 2 OCR engines
- [x] Error patterns documented

---

## Phase 2: Expansion to 50 Examples

**Target Dataset:** 50 manually verified examples (27 more needed)
**Timeline:** User-driven data collection
**Goal:** Enable fine-tuning and confidence scoring

### Prerequisites:
- Complete Phase 1
- Collect 27 additional diverse examples focusing on:
  - 10-15 Korean character names
  - 5-8 emoji/special Unicode names
  - 5-10 mixed character set names
  - Varying screenshot qualities and lighting

### Tasks:

#### 2.1 Fine-tune Tesseract
- Create custom training data format for Tesseract
- Generate box files for special characters
- Train custom Tesseract model for Korean + special chars
- **Deliverable:** Custom `.traineddata` file

#### 2.2 Build Confidence Scoring System
- Implement multi-engine voting system
- Calculate confidence scores based on agreement
- Add dictionary-based validation
- Flag low-confidence results for manual review
- **Deliverable:** `ocr/tools/confidence_scorer.py`

#### 2.3 Enhanced Character Mapping
- Update mapping with patterns from 50 examples
- Add context-aware substitutions
- Implement fuzzy matching for partial matches
- **Deliverable:** Updated `character_mapping.json`

### Success Criteria:
- [ ] 50 total training examples collected and verified
- [ ] Custom Tesseract model trained
- [ ] Confidence scoring achieves 85%+ accuracy on flagging errors
- [ ] Character mapping covers 50+ substitution patterns

---

## Phase 3: Production Ready (100+ Examples)

**Target Dataset:** 100+ manually verified examples
**Timeline:** After Phase 2 completion
**Goal:** Deployment-ready OCR system

### Prerequisites:
- Complete Phase 2
- Collect 50 additional examples
- Validate Phase 2 improvements

### Tasks:

#### 3.1 Train Custom Character Recognition Model
- Prepare dataset for deep learning
- Train character-level CNN or RNN model
- Implement ensemble model combining multiple approaches
- **Deliverable:** Custom ML model weights

#### 3.2 Implement Ensemble OCR
- Combine Tesseract + EasyOCR + custom model
- Weighted voting based on character type
- Fallback strategies for disagreements
- **Deliverable:** `ocr/tools/ensemble_ocr.py`

#### 3.3 Auto-correction System
- Implement pattern-based auto-correction
- Use alliance tag to predict character sets
- Context-aware corrections using historical data
- **Deliverable:** `ocr/tools/auto_correct.py`

#### 3.4 Production Pipeline
- Create end-to-end automation script
- Add error handling and logging
- Implement manual review queue for low confidence
- **Deliverable:** `ocr/tools/process_ranking_screenshot.py`

### Success Criteria:
- [ ] 100+ training examples in dataset
- [ ] Character-level accuracy > 95%
- [ ] Word-level accuracy > 90%
- [ ] Automated pipeline processes screenshots end-to-end
- [ ] Manual review needed for < 10% of results

---

## Data Collection Guidelines

### Priority Targets for Phase 2:

**High Priority (Need More):**
1. **Korean character names** - Currently: 3, Target: 15
2. **Emoji names** - Currently: 1, Target: 8
3. **Superscript/subscript** - Currently: 1, Target: 5

**Medium Priority (Diversity):**
4. **Special symbols** (ღ, ♥, ★, etc.) - Target: 10
5. **Mixed scripts** (Korean+ASCII) - Target: 10
6. **Long names** (15+ characters) - Target: 5

**Screenshot Quality Variety:**
- Different times of day (lighting)
- Different zoom levels
- Different device resolutions
- Partial obscuration (but readable)

### Collection Process:

1. **Take screenshot** of alliance card with clear R5 name
2. **Save with timestamp:** `YYYY-MM-DD_HH-MM-SS.png`
3. **Manually verify** R5 name character-by-character
4. **Document in spreadsheet:**
   - Alliance tag
   - R5 name (exact Unicode)
   - Screenshot filename
   - Character types present
   - Difficulty rating

5. **Add to training dataset:**
   ```bash
   # Add to CSV
   echo "TAG,R5_Name,filename,date,notes" >> alliance_r5_mapping.csv

   # Update JSON
   python scripts/add_training_data.py --tag TAG --r5 "R5 Name" --file filename.png
   ```

---

## Progress Tracking

### Phase 1: Foundation ✅ COMPLETE
- [x] Initial 23 examples collected
- [x] Character substitution mapping created
- [x] Validation test suite built
- [x] Baseline accuracy measured
- [x] Phase documentation complete

**Baseline Results:**
- Tesseract: 33.3% word accuracy, 62.4% character accuracy
- EasyOCR: 50.0% word accuracy, 72.4% character accuracy
- Hard cases (emoji, superscripts): 0% accuracy
- Korean-only: EasyOCR achieves 100% accuracy

### Phase 2: Expansion (Need 27 more examples)
- [ ] 27 additional examples collected
- [ ] Korean names: 0/12 needed
- [ ] Emoji names: 0/7 needed
- [ ] Mixed scripts: 0/8 needed
- [ ] Tesseract fine-tuning complete
- [ ] Confidence scoring implemented

### Phase 3: Production (Need 50 more examples)
- [ ] 100+ total examples achieved
- [ ] Custom ML model trained
- [ ] Ensemble OCR implemented
- [ ] Auto-correction system built
- [ ] Production pipeline deployed

---

## Estimated Timelines

**Phase 1:** 1-2 days (development time)
**Phase 2:** 1-2 weeks (data collection + 2-3 days development)
**Phase 3:** 2-4 weeks (data collection + 1 week development)

**Total to Production:** 4-6 weeks with consistent data collection

---

## Success Metrics

| Phase | Character Accuracy | Word Accuracy | Manual Review Rate |
|-------|-------------------|---------------|-------------------|
| 1 (Baseline) | 60-70% | 40-50% | 100% |
| 2 (Improved) | 80-85% | 70-75% | 30-40% |
| 3 (Production) | 95%+ | 90%+ | <10% |

---

## Next Steps

**Immediate (You):**
1. Continue collecting alliance card screenshots
2. Focus on Korean names and emoji names first
3. Aim for 27 more examples to reach Phase 2

**Immediate (Development):**
1. Create character substitution mapping tool
2. Build OCR validation test suite
3. Measure baseline accuracy with current 23 examples
4. Generate accuracy report

**After Phase 1 Complete:**
- Review baseline results
- Adjust Phase 2 priorities based on error patterns
- Create data collection targets for most problematic character types
