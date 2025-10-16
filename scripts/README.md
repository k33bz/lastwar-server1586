# Scripts Documentation

Utility scripts for managing Server 1586 rotation schedule and automation.

## 📍 Navigation
- **← Back to Main**: [../README.md](../README.md)
- **📚 Full Documentation**: [../DOCUMENTATION.md](../DOCUMENTATION.md)
- **🚀 Deployment Guide**: [DEPLOY-README.md](DEPLOY-README.md)
- **🔧 Admin Panel**: [../admin/README.md](../admin/README.md)

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

**Version**: 2.2.0 | **Last Updated**: October 16, 2025 | **Part of**: [Server 1586 Project](../README.md)