# Scripts Directory

This directory contains all automation scripts and utilities for the Server 1586 project.

## Tracked Scripts (Version Controlled)

### Repository Review & Analysis
- **repo-review.py** (v3.0.0) - Comprehensive repository analysis with LM Studio
  - Uses qwen3-coder-30b for code review
  - Modes: overview, security, quality, docs, improvements
  - Features: Log monitoring, performance stats, auto-save
  - Documentation: [docs/LM-STUDIO-TESTING.md](../docs/LM-STUDIO-TESTING.md)

### Unit Test Generation
- **generate-tests.py** - Generate PHPUnit tests using LM Studio
  - Analyzes PHP functions and generates test cases
  - Discovers edge cases automatically
  - Outputs to tests/ directory
  - Documentation: [docs/LM-STUDIO-TESTING.md](../docs/LM-STUDIO-TESTING.md)

### Data Management
- **update-rotation-schedule.py** - Update council rotation schedule
  - Reads data/alliances.json
  - Generates fair rotation for ranks 6-15
  - Preserves historical data
  - Updates data/rotation-schedule.json

### Documentation Tools
- **consolidate-markdown.ps1** - PowerShell script to consolidate markdown files
  - Merges multiple markdown documents
  - Used for documentation management

## Untracked Scripts (Not Version Controlled)

These one-time use scripts contain environment-specific credentials or production fixes.

### Categories
- check_*.py - Production environment validation scripts
- fix_*.py - Production hotfix scripts (contain FTP credentials)
- cleanup_*.py - Cleanup and maintenance scripts
- sync_*.py - FTP sync scripts (contain credentials)
- upload_*.py / download_*.py - File transfer utilities
- enable_*.py - Feature enablement scripts
- emergency_*.py - Emergency production fixes
- update_*.py - Update and migration scripts

**Security Note**: These scripts are gitignored to prevent credential exposure.

## Usage

### Run Repository Review
```bash
python scripts/repo-review.py overview
python scripts/repo-review.py security
```

### Generate Unit Tests
```bash
python scripts/generate-tests.py admin/includes/input_validator.php
```

### Update Council Rotation
```bash
python scripts/update-rotation-schedule.py
```

## Documentation

- [LM Studio Testing Guide](../docs/LM-STUDIO-TESTING.md)
- [Main README](../README.md)
