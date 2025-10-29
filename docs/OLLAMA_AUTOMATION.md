# Ollama Automation for Documentation

**Version:** 1.0.0
**Date:** 2025-10-29

---

## Overview

Automated documentation and changelog generation using Ollama (local LLM) triggered by git commits.

---

## Recommended Models for Code Documentation

### Best Choice: Qwen2.5-Coder 14B
**Why:** Specifically trained for code understanding and documentation
```bash
ollama pull qwen2.5-coder:14b
```
- **VRAM:** ~8GB (plenty of room on your 24GB card)
- **Speed:** Fast enough for git hooks (~2-5 seconds)
- **Quality:** Excellent for code analysis and technical writing
- **Strengths:** PHP, JavaScript, Python, technical documentation

### Alternative: Qwen2.5-Coder 32B
**Why:** Best quality, still fast on your hardware
```bash
ollama pull qwen2.5-coder:32b
```
- **VRAM:** ~19GB (fits comfortably on 24GB)
- **Speed:** Slightly slower (~5-10 seconds)
- **Quality:** Superior code understanding
- **Use When:** You want highest quality output

### Lightweight: Qwen2.5-Coder 7B
**Why:** Fastest option, good enough for most tasks
```bash
ollama pull qwen2.5-coder:7b
```
- **VRAM:** ~4GB
- **Speed:** Very fast (~1-3 seconds)
- **Quality:** Good for standard documentation
- **Use When:** Speed is critical

### Already Installed (Not Ideal for This):
- ❌ `qwen2.5-coder:1.5b-base` - Too small, limited quality
- ⚠️ `llama3.1:8b` - General purpose, okay but not code-specialized
- ⚠️ `deepseek-r1:32b` - Reasoning model (slow, overkill for docs)
- ⚠️ `openthinker:32b` - Reasoning model (slow, overkill)
- ⚠️ `gemma3:27b` - General purpose

---

## Recommendation

**Install and use: `qwen2.5-coder:14b`**

This is the sweet spot for:
- Code analysis speed
- Documentation quality
- VRAM efficiency
- PHP/JavaScript understanding

---

## Architecture

```
┌─────────────────────────────────────────────┐
│          Git Commit                         │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│    .git/hooks/post-commit (or prepare-     │
│    commit-msg)                              │
│                                             │
│    1. Extract commit diff                   │
│    2. Get commit message                    │
│    3. Analyze changed files                 │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│    scripts/ollama-doc-generator.py          │
│                                             │
│    - Connects to Ollama (localhost:11434)   │
│    - Sends code diff + context              │
│    - Receives AI-generated content          │
└──────────────┬──────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────┐
│    Update Documentation                     │
│                                             │
│    - docs/CHANGELOG.md (new entry)          │
│    - Inline code documentation              │
│    - version.json (if major changes)        │
└─────────────────────────────────────────────┘
```

---

## Implementation Options

### Option 1: Post-Commit Hook (Recommended)
**Pros:**
- Commit already made, safer
- Can amend if needed
- Won't block commits

**Cons:**
- Creates additional commit for documentation

**Use Case:** Most projects, safest option

### Option 2: Pre-Commit Hook
**Pros:**
- Documentation included in same commit
- Cleaner history

**Cons:**
- Can slow down commits
- Might block urgent commits

**Use Case:** When you want everything in one commit

### Option 3: Prepare-Commit-Msg Hook
**Pros:**
- Generates better commit messages
- Runs before commit message editor

**Cons:**
- Only helps with commit messages

**Use Case:** Improve commit message quality

---

## Setup Instructions

### 1. Install Recommended Model

```bash
ollama pull qwen2.5-coder:14b
```

### 2. Test Ollama API

```bash
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5-coder:14b",
  "prompt": "Explain what a git hook is in one sentence.",
  "stream": false
}'
```

### 3. Create Documentation Generator Script

Location: `scripts/ollama-doc-generator.py`

Features:
- Reads git diff
- Analyzes code changes
- Generates changelog entries
- Updates documentation
- Uses Ollama API

### 4. Install Git Hook

Location: `.git/hooks/post-commit`

Triggers documentation generator after each commit.

---

## Prompt Templates

### Changelog Generation Prompt

```
You are a technical documentation expert. Analyze this git commit and generate a changelog entry.

**Commit Message:**
{commit_message}

**Files Changed:**
{file_list}

**Code Diff:**
{git_diff}

**Task:** Generate a concise changelog entry in this format:

### Changed
- Brief description of what changed

### Added
- New features or files

### Fixed
- Bug fixes or corrections

Keep it professional, concise, and user-focused. Use markdown formatting.
```

### Code Documentation Prompt

```
You are a Python/PHP/JavaScript documentation expert. Generate docstrings/PHPDoc/JSDoc comments for this code.

**Code:**
{code_snippet}

**Context:**
- File: {file_path}
- Purpose: {file_purpose}

**Task:** Generate proper documentation comments including:
- Description
- @param tags (with types)
- @return tag
- @throws if applicable
- Usage example if complex

Format as valid PHPDoc or JSDoc.
```

### Commit Message Enhancement Prompt

```
You are a git commit expert. Improve this commit message following conventional commits.

**Current Message:**
{current_message}

**Files Changed:**
{file_list}

**Diff Summary:**
{diff_summary}

**Task:** Generate a better commit message in format:
<type>(<scope>): <subject>

<body>

<footer>

Types: feat, fix, docs, style, refactor, test, chore
Keep subject under 50 chars, body wrapped at 72 chars.
```

---

## Performance Expectations

### Qwen2.5-Coder 14B on RX 7900 XTX:

| Task | Tokens | Time |
|------|--------|------|
| Changelog entry | ~500 | 2-3 sec |
| Code documentation | ~300 | 1-2 sec |
| Commit message | ~200 | 1 sec |
| File analysis | ~1000 | 4-5 sec |

**Total per commit:** 5-10 seconds (acceptable for git hooks)

---

## Configuration

### `.git/hooks/post-commit`
```bash
#!/bin/bash
# Auto-generate documentation using Ollama

python scripts/ollama-doc-generator.py post-commit
```

### `scripts/ollama-config.json`
```json
{
  "model": "qwen2.5-coder:14b",
  "ollama_url": "http://localhost:11434",
  "temperature": 0.3,
  "max_tokens": 500,
  "enabled": true,
  "auto_commit": false,
  "update_changelog": true,
  "update_code_docs": false
}
```

---

## Safety Features

**Always Include:**

1. **Dry Run Mode**
   - Preview changes before applying
   - `python ollama-doc-generator.py --dry-run`

2. **Skip Option**
   - Environment variable to bypass
   - `SKIP_OLLAMA=1 git commit -m "message"`

3. **Timeout**
   - Max 30 seconds per request
   - Fallback to manual documentation

4. **Validation**
   - Check Ollama is running
   - Verify model is available
   - Validate generated content

5. **Error Handling**
   - Never block commits on failure
   - Log errors for debugging
   - Graceful degradation

---

## Cost Comparison

### Current (Claude API):
- **Cost:** ~$0.01-0.05 per commit (depending on context)
- **Speed:** 2-5 seconds
- **Quality:** Excellent
- **Limit:** Token budget

### Ollama (Local):
- **Cost:** $0 (electricity negligible)
- **Speed:** 2-5 seconds (similar!)
- **Quality:** Very good (Qwen2.5-Coder 14B)
- **Limit:** None

**Savings:** 100% on documentation automation costs

---

## Advanced Features

### Future Enhancements:

1. **Semantic Version Bumping**
   - Analyze breaking changes
   - Auto-increment version.json
   - Follow semver rules

2. **Release Notes Generation**
   - Aggregate commits since last tag
   - Group by type (feat/fix/docs)
   - Generate GitHub release notes

3. **API Documentation**
   - Scan PHP endpoints
   - Generate OpenAPI spec
   - Update docs/PUBLIC_API.md

4. **Code Review Assistant**
   - Analyze complexity
   - Suggest improvements
   - Check for security issues

5. **Inline Comment Generation**
   - Add JSDoc/PHPDoc to functions
   - Update existing documentation
   - Maintain consistency

---

## Troubleshooting

### Ollama Not Responding
```bash
# Check if Ollama is running
curl http://localhost:11434/api/tags

# Start Ollama
ollama serve

# Check GPU usage
ollama ps
```

### Slow Performance
- Use smaller model (qwen2.5-coder:7b)
- Reduce max_tokens in config
- Disable for large commits (>100 files)

### Poor Quality Output
- Upgrade to larger model (32B)
- Adjust temperature (try 0.1-0.5)
- Improve prompts with more context

### Hook Not Triggering
```bash
# Check hook exists and is executable
ls -la .git/hooks/post-commit
chmod +x .git/hooks/post-commit

# Test manually
bash .git/hooks/post-commit
```

---

## Example Output

### Input (Git Commit):
```
feat: Add alliances API endpoint

Created GET /api/alliances.php to return alliance rankings
```

### Output (Generated Changelog Entry):
```markdown
## [3.4.0] - 2025-10-30

### Added
- **Alliances API Endpoint** - New GET /api/alliances.php endpoint
  - Returns top 15 alliance rankings with power and R5 data
  - Includes caching (60s) and ETag support
  - CORS enabled for cross-origin access
  - Dynamic rank calculation from power values

### Technical Details
- Response format: JSON with success flag and data payload
- Cache headers: public, max-age=60
- File locking: LOCK_SH for concurrent read access
```

---

## Security Considerations

1. **Local Only**
   - Ollama runs on localhost
   - No data sent to external services
   - No API keys needed

2. **Code Privacy**
   - All processing local
   - No telemetry
   - Your code stays on your machine

3. **Git Hook Safety**
   - Never blocks commits
   - Always allows manual override
   - Errors logged, not blocking

---

## Next Steps

1. Install recommended model: `ollama pull qwen2.5-coder:14b`
2. Review implementation script (will be created)
3. Test with sample commits
4. Enable in production when satisfied

---

**Last Updated:** 2025-10-29
**Status:** Planning phase
**Hardware:** AMD Radeon RX 7900 XTX (24GB VRAM)
**Recommendation:** Qwen2.5-Coder 14B
