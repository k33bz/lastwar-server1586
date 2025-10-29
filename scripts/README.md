# Scripts Documentation

Utility scripts for managing Server 1586 rotation schedule, deployment, and documentation automation.

## 📍 Navigation
- **← Back to Main**: [../README.md](../README.md)
- **📚 Full Documentation**: [../DOCUMENTATION.md](../DOCUMENTATION.md)
- **🚀 Deployment Guide**: [DEPLOY-README.md](DEPLOY-README.md)
- **🔧 Admin Panel**: [../admin/README.md](../admin/README.md)

---

## 🤖 Ollama Documentation Automation (NEW)

**Automatic changelog and documentation generation** using local Ollama LLM, triggered by git commits.

**Full Documentation**: [../docs/OLLAMA_AUTOMATION.md](../docs/OLLAMA_AUTOMATION.md)

### Quick Start

1. **Install recommended model**:
   ```bash
   ollama pull qwen2.5-coder:14b
   ```

2. **Configure settings** (optional, edit `ollama-config.json`):
   ```json
   {
     "enabled": true,
     "model": "qwen2.5-coder:14b",
     "update_changelog": true
   }
   ```

3. **Install git hook** (Linux/Mac):
   ```bash
   cp scripts/post-commit.example .git/hooks/post-commit
   chmod +x .git/hooks/post-commit
   ```

   **Or for Windows**, create `.git/hooks/post-commit` (no extension):
   ```batch
   @echo off
   php scripts\ollama-doc-generator.php post-commit
   exit /b 0
   ```

4. **Test it**:
   ```bash
   # Dry run (preview without writing)
   php scripts/ollama-doc-generator.php post-commit --dry-run

   # Skip for specific commit
   SKIP_OLLAMA=1 git commit -m "message"
   ```

### Features

- ✅ **Automatic Changelog Generation** - Analyzes commits and updates `docs/CHANGELOG.md`
- ✅ **Smart Versioning** - Auto-increments patch version based on commits
- ✅ **Never Blocks Commits** - Gracefully fails without interrupting workflow
- ✅ **Local & Private** - Runs on your machine, no external API calls
- ✅ **Fast** - 2-5 seconds per commit on AMD RX 7900 XTX
- ✅ **Configurable** - Dry run mode, skip option, custom prompts

### Usage Modes

```bash
# Post-commit (default) - analyze last commit
php scripts/ollama-doc-generator.php post-commit

# Preview mode - don't write files
php scripts/ollama-doc-generator.php post-commit --dry-run

# Manual changelog generation
php scripts/ollama-doc-generator.php changelog

# Help and documentation
php scripts/ollama-doc-generator.php --help
```

### Requirements

- PHP 7.4+ with cURL extension
- Ollama installed and running (`ollama serve`)
- Recommended model: `qwen2.5-coder:14b` (~8GB VRAM)

### Configuration File

Edit `scripts/ollama-config.json` to customize:

```json
{
  "enabled": true,              // Master on/off switch
  "model": "qwen2.5-coder:14b", // Ollama model to use
  "ollama_url": "http://localhost:11434",
  "temperature": 0.3,           // Lower = more consistent
  "max_tokens": 500,            // Max response length
  "auto_commit": false,         // Don't auto-commit changelog updates
  "update_changelog": true,     // Enable changelog generation
  "update_code_docs": false     // Code docs (not yet implemented)
}
```

### How It Works

1. Git commit triggers `.git/hooks/post-commit`
2. Hook runs `ollama-doc-generator.php`
3. Script extracts commit message, diff, and changed files
4. Sends context to local Ollama LLM with structured prompt
5. LLM generates changelog entry in markdown format
6. Script updates `docs/CHANGELOG.md` with new entry
7. Increments patch version in `version.json`

### Safety Features

- **SKIP_OLLAMA=1** environment variable to bypass automation
- **Dry run mode** to preview without writing files
- **Timeout protection** (30 seconds max per request)
- **Automatic fallback** if Ollama is not running
- **Never blocks git operations** on failure

### Troubleshooting

**Ollama not responding:**
```bash
# Check if Ollama is running
curl http://localhost:11434/api/tags

# Start Ollama if needed
ollama serve

# Verify model is available
ollama list
```

**Poor quality output:**
```bash
# Use larger model for better quality
ollama pull qwen2.5-coder:32b

# Update config
# Edit ollama-config.json: "model": "qwen2.5-coder:32b"
```

**Hook not triggering:**
```bash
# Verify hook is executable (Linux/Mac)
chmod +x .git/hooks/post-commit

# Test manually
bash .git/hooks/post-commit
```

---

## update-rotation-schedule.py (Recommended)

**Smart schedule updater** that generates fair rotation schedules based on current alliance rankings.

**Uses alliance tags** (STR8, EPIC, etc.) instead of ranks for stability when rankings change.

### Usage:
```bash
python scripts/update-rotation-schedule.py
```

### Features:
- ✅ Reads current top 15 alliances from `data/alliances.json`
- ✅ **Creates schedule file if it doesn't exist** (automatic initialization)
- ✅ Preserves all historical weeks (before next rotation)
- ✅ Generates next 52 weeks using weighted fair selection
- ✅ Looks back 10 weeks to ensure fair distribution
- ✅ **Prevents back-to-back rotations** (configurable minimum gap, default: 2 weeks)
- ✅ Handles new alliances gracefully (spreads them evenly, no bunching)
- ✅ Provides detailed output showing upcoming schedule and fairness stats

### Algorithm:
1. Loads current alliance rankings
2. Determines current week and next rotation date
3. Counts recent rotations (last 10 weeks) for each alliance
4. Generates 52 future weeks using weighted selection:
   - Prioritizes alliances with fewer recent rotations
   - Applies graduated penalty to prevent back-to-back rotations (stronger for more recent weeks)
   - Ensures minimum gap between rotations (configurable via `MIN_WEEKS_BETWEEN_ROTATIONS`)
   - Ensures all alliances rotate equally over time
5. Preserves past schedule (historical record)
6. Writes updated schedule to `data/rotation-schedule.json`

### Configuration:
Edit the script to adjust rotation behavior:
- `MIN_WEEKS_BETWEEN_ROTATIONS = 2` (default): Minimum weeks before same alliance can rotate again
  - `1` = Allow back-to-back (consecutive weeks)
  - `2` = Must skip at least 1 week (no consecutive)
  - `3` = Must skip at least 2 weeks (2-week gap)
  - etc.

### When to Run:
- After updating `data/alliances.json` with new rankings
- Periodically to extend schedule further into future
- When you need to refresh rotation fairness

### Requirements:
- Python 3.7+
- No external dependencies (uses standard library only)
- **Only requires `data/alliances.json`** - will create schedule file if missing

### Example Output:
```
============================================================
Council Rotation Schedule Update
============================================================

[1/6] Loading alliance data...
      Loaded 15 alliances

[2/6] Loading existing schedule...
      Existing schedule has 72 weeks

[3/6] Calculating current week...
      Current week: 21
      Next rotation: 2025-10-12 22:00
      Generating from week: 21

[4/6] Analyzing rotation pool...
      Rotating pool (ranks 6-15): [6, 7, 8, 9, 10, 11, 12, 13, 14, 15]

[5/6] Analyzing recent rotation history (last 10 weeks)...
      Recent rotation counts:
        Rank  6 (STR8): 2 times
        Rank  7 (EPIC): 2 times
        ...

[6/6] Generating next 52 weeks...
      [OK] Generated 52 new weeks
      [OK] Preserved 20 past weeks
      [OK] Total schedule: 72 weeks

============================================================
Upcoming Rotation Schedule (Next 10 weeks):
============================================================
  Week 21: 2025-10-12 - Ranks  6,  7 (STR8, EPIC) (NEXT)
  Week 22: 2025-10-19 - Ranks  8,  9 (NYPR, 86KO)
  ...

============================================================
Fairness Check (Next 52 weeks):
============================================================
  Rank  6 (STR8): 11 rotations
  Rank  7 (EPIC): 11 rotations
  ...

[SUCCESS] Schedule updated successfully!
```

---

## generate-rotation-schedule.js (Initial Setup Only)

**One-time schedule generator** for creating the initial 52-week schedule.

### Usage:
```bash
node scripts/generate-rotation-schedule.js
```

### When to Use:
- **Initial setup only** (already done)
- Complete regeneration from Week 1 (rare)

### Note:
For ongoing updates, use `update-rotation-schedule.py` instead. This script generates a simple round-robin schedule and doesn't account for past rotation history or alliance changes.

---

## Files Modified:
Both scripts update: `data/rotation-schedule.json`

## Files Read:
- `data/alliances.json` (Python script only)
- `data/rotation-schedule.json` (both scripts)

---

## 📞 Support & Contact

For questions about scripts and automation:
- **Main Documentation**: [../README.md](../README.md)
- **GitHub Issues**: [Report bugs or request features](https://github.com/username/your-repo/issues)
- **Deployment Guide**: [DEPLOY-README.md](DEPLOY-README.md)

---

**Version**: 2.3.0 | **Last Updated**: October 29, 2025 | **Part of**: [Server 1586 Project](../README.md)