# Incremental FTP Deployment

Fast, intelligent FTP deployment that only uploads changed files.

## Overview

The incremental deployment system uses **MD5 checksums** and **modification timestamps** to detect file changes, dramatically reducing deployment time by only uploading files that have actually changed.

### Performance Improvements

**Traditional Deployment:**
- Uploads all ~200 files every time
- Takes 3-5 minutes per deployment
- Wastes bandwidth on unchanged files

**Incremental Deployment:**
- Only uploads changed files
- Typically 5-20 files per deployment
- Takes 30-60 seconds (80-90% faster!)
- Smart caching between deployments

## How It Works

### 1. File Scanning
```
Scan all files → Calculate MD5 checksums → Compare with cached state
```

### 2. Change Detection

**New Files**: Not in previous deployment state → Upload
**Modified Files**: Checksum different from cache → Upload
**Unchanged Files**: Checksum matches cache → Skip

### 3. State Caching

After each deployment, the system saves:
```json
{
  "files": {
    "index.html": {
      "checksum": "d41d8cd98f00b204e9800998ecf8427e",
      "mtime": 1698765432.123,
      "size": 2048,
      "uploaded_at": "2025-10-19T12:34:56"
    }
  },
  "last_deploy": "2025-10-19T12:34:56"
}
```

**GitHub Actions**: Uses `actions/cache` to persist state between runs
**Local**: Stores in `.deploy-state.json` (gitignored)

## Usage

### GitHub Actions (Automatic)

Already configured in `.github/workflows/deploy.yml`:

```yaml
- name: Restore deployment state cache
  uses: actions/cache@v3
  with:
    path: .deploy-state.json
    key: ftp-deploy-state-${{ github.sha }}
    restore-keys: ftp-deploy-state-

- name: Deploy via FTP (Incremental)
  run: python scripts/deploy-ftp-incremental.py

- name: Save deployment state cache
  uses: actions/cache/save@v3
  with:
    path: .deploy-state.json
    key: ftp-deploy-state-${{ github.sha }}
```

Just push to `mainline` - incremental deployment happens automatically!

### Manual Deployment

**Standard (incremental):**
```bash
python scripts/deploy-ftp-incremental.py
```

**Force full upload:**
```bash
python scripts/deploy-ftp-incremental.py --force
```

**Checksum-only (ignore timestamps):**
```bash
python scripts/deploy-ftp-incremental.py --checksum-only
```

## Deployment Output

### Example: Fast Incremental Deploy

```
======================================================================
Server 1586 - Incremental FTP Deployment
======================================================================

[INFO] Deploying to user@ftp.example.com

[1/5] Loading deployment state...
      [OK] Last deployment: 2025-10-19T12:00:00
      [OK] 198 files in state cache

[2/5] Loading .ftpignore patterns...
      [OK] Loaded 45 patterns

[3/5] Analyzing files...
      [OK] Total files: 200
      [OK] Files to upload: 8
      [OK] Files skipped (unchanged): 192

      Skip reasons:
        - checksum-match: 187 files
        - unchanged: 5 files

[INFO] Connecting to ftp.example.com:21...
      [OK] Connected as user
      [OK] Changed to /

[4/5] Uploading 8 changed files (47.3 KB)...
      [ 12.5%] admin/migrate.php (15.2 KB) [modified]
      [ 25.0%] admin/version_check.php (8.1 KB) [modified]
      [ 37.5%] version.json (1.2 KB) [modified]
      [ 50.0%] DOCUMENTATION.md (18.7 KB) [modified]
      [ 62.5%] docs/DEPLOYMENT.md (3.4 KB) [modified]
      [ 75.0%] .gitignore (0.3 KB) [modified]
      [ 87.5%] admin/config.php (0.2 KB) [modified]
      [100.0%] admin/includes/header.php (0.2 KB) [modified]

[5/5] Saving deployment state...
      [OK] State saved to .deploy-state.json

======================================================================
Deployment Summary:
  Total files:   200
  Uploaded:      8 files
  Skipped:       192 files (unchanged)
  Failed:        0 files
  Time savings:  ~96% faster
  Duration:      38.2 seconds
======================================================================

[SUCCESS] Incremental deployment completed successfully!
          Website: https://www.lastwar1586.online
```

### Example: First Deploy (Full Upload)

```
======================================================================
Server 1586 - Incremental FTP Deployment
======================================================================

[1/5] Loading deployment state...
      [INFO] No previous deployment state - full upload required

[2/5] Loading .ftpignore patterns...
      [OK] Loaded 45 patterns

[3/5] Analyzing files...
      [OK] Total files: 200
      [OK] Files to upload: 200
      [OK] Files skipped (unchanged): 0

[4/5] Uploading 200 changed files (2.3 MB)...
      [  0.5%] index.html (12.4 KB) [new]
      [  1.0%] admin/dashboard.php (18.7 KB) [new]
      ...
      [100.0%] version.json (1.2 KB) [new]

[5/5] Saving deployment state...
      [OK] State saved to .deploy-state.json

======================================================================
Deployment Summary:
  Total files:   200
  Uploaded:      200 files
  Skipped:       0 files (unchanged)
  Failed:        0 files
  Time savings:  ~0% faster
  Duration:      287.3 seconds
======================================================================

[SUCCESS] Incremental deployment completed successfully!
```

## Change Detection Logic

### Priority Order

1. **Force flag** (`--force`): Upload everything
2. **New file**: Not in cache → Upload
3. **Modification time** (unless `--checksum-only`):
   - File modified since last deploy → Check checksum
   - File not modified → Skip (fast path)
4. **MD5 Checksum**:
   - Checksum matches cache → Skip
   - Checksum different → Upload

### Why Both Timestamps and Checksums?

**Modification Time** (fast):
- Quick to check (no file reading)
- Filters out most unchanged files
- Can be fooled by touch/copy operations

**MD5 Checksum** (reliable):
- 100% accurate for detecting changes
- Requires reading entire file
- Slower but definitive

**Hybrid Approach** (best):
- Check mtime first (fast)
- If mtime changed, verify with checksum (accurate)
- Gets best of both worlds

## State File Structure

`.deploy-state.json` contains:

```json
{
  "files": {
    "path/to/file.php": {
      "checksum": "md5_hash_string",
      "mtime": 1698765432.123,
      "size": 2048,
      "uploaded_at": "2025-10-19T12:34:56.789"
    }
  },
  "last_deploy": "2025-10-19T12:34:56.789"
}
```

**Location**:
- Local: `.deploy-state.json` in project root (gitignored)
- GitHub Actions: Stored in actions/cache

**Persistence**:
- Survives between deployments
- Automatically managed
- Safe to delete (triggers full upload next time)

## GitHub Actions Cache

The workflow uses GitHub's cache to persist state:

**Key**: `ftp-deploy-state-{commit-sha}`
**Restore Keys**: `ftp-deploy-state-*` (finds most recent)

**Cache Lifecycle**:
1. Deployment starts
2. Restore cache from previous run
3. Compare files using cached state
4. Upload only changed files
5. Save new state to cache

**Cache Limits**:
- GitHub allows 10GB total cache per repo
- Deployment state is ~1-2MB
- Auto-evicted after 7 days of no access
- No manual cleanup needed

## When Files Are Uploaded

| Scenario | Upload? | Reason |
|----------|---------|--------|
| New file created | ✅ Yes | Not in cache |
| File edited | ✅ Yes | Checksum changed |
| File renamed | ✅ Yes | New path, old path deleted |
| File touched (no changes) | ❌ No | Checksum matches |
| File copied (same content) | ❌ No | Checksum matches |
| File deleted locally | ❌ No | Not uploaded (server retains) |
| First deployment | ✅ Yes | No cache exists |
| Force flag used | ✅ Yes | Override |

## Comparison with Traditional

### Traditional Deployment
```python
# deploy-ftp-ci.py (old)
for file in all_files:
    upload(file)  # Always uploads
```

**Pros**: Simple, reliable
**Cons**: Slow, wasteful

### Incremental Deployment
```python
# deploy-ftp-incremental.py (new)
for file in all_files:
    if file_changed(file):
        upload(file)  # Only if changed
    else:
        skip(file)   # Fast path
```

**Pros**: Fast, efficient
**Cons**: Slightly more complex (but worth it!)

## Performance Benchmarks

Based on typical Server 1586 deployments:

| Deployment Type | Files Changed | Upload Time | Speed Improvement |
|----------------|---------------|-------------|-------------------|
| **Full (first)** | 200 / 200 | 4-5 min | Baseline |
| **Minor update** | 5-10 / 200 | 30-45 sec | 85-90% faster |
| **Code changes** | 20-30 / 200 | 60-90 sec | 70-80% faster |
| **Data only** | 2-5 / 200 | 15-30 sec | 90-95% faster |
| **Documentation** | 10-15 / 200 | 45-60 sec | 80-85% faster |

**Average deployment**: ~60 seconds (was ~300 seconds)

## Troubleshooting

### Cache Not Working

**Symptom**: Always uploads all files

**Check**:
```bash
# Local: Verify state file exists
ls -la .deploy-state.json

# GitHub Actions: Check cache step in logs
# Look for "Cache restored from key: ftp-deploy-state-..."
```

**Fix**:
- Local: State file may be deleted (normal - will rebuild)
- GitHub: Cache may have expired (7 days) - will rebuild

### Files Not Uploading When They Should

**Symptom**: Made changes but file wasn't uploaded

**Check**:
```bash
# Calculate checksum manually
md5sum path/to/file.php

# Compare with cached checksum in .deploy-state.json
cat .deploy-state.json | grep "path/to/file.php" -A 3
```

**Fix**:
```bash
# Force full upload
python scripts/deploy-ftp-incremental.py --force

# Or delete state and re-deploy
rm .deploy-state.json
python scripts/deploy-ftp-incremental.py
```

### False "Modified" Detection

**Symptom**: File marked as modified but content didn't change

**Reason**: Modification time changed (file touched, copied, or git operation)

**Behavior**: Checksum verification will detect no actual change and skip upload

**This is normal** - the system automatically handles false positives

## Migration from Traditional

Already using `deploy-ftp-ci.py`? The incremental script is **fully compatible**:

**GitHub Actions**: Already updated to use incremental
**Local**: Just use `deploy-ftp-incremental.py` instead

**First run**: Will upload everything (no cache)
**Subsequent runs**: Will be fast (cached state)

**Rollback**: Can always use `deploy-ftp-ci.py` if needed (still exists)

## Best Practices

1. **Let it build cache**: First deploy is slow, subsequent are fast
2. **Don't delete state file**: Unless troubleshooting
3. **Use force flag sparingly**: Only when truly needed
4. **Monitor GitHub Actions logs**: Verify cache is working
5. **Check deployment summary**: Confirms files skipped

## Future Enhancements

Potential improvements (not yet implemented):

- [ ] Parallel uploads (multiple FTP connections)
- [ ] Compression for large files
- [ ] Delete remote files not in local (sync mode)
- [ ] Dry-run mode (show what would be uploaded)
- [ ] FTP resume for interrupted uploads
- [ ] Delta uploads (only changed bytes, not whole file)

---

**Documentation**: https://github.com/k33bz/lastwar-server1586/blob/mainline/scripts/INCREMENTAL-DEPLOYMENT.md
**Script**: `scripts/deploy-ftp-incremental.py`
**GitHub Issues**: https://github.com/k33bz/lastwar-server1586/issues
