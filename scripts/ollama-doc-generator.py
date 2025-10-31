#!/usr/bin/env python3
"""
Ollama Documentation Generator

Automatically generates documentation and changelog entries using local Ollama LLM
Triggered by git hooks to analyze commits and update docs

Documentation:
- Setup Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/OLLAMA_AUTOMATION.md

GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues

Version: 1.0.0
Date: 2025-10-29

Usage:
  python ollama-doc-generator.py [mode] [options]

Modes:
  post-commit    - Generate changelog from last commit (default)
  changelog      - Generate changelog entry
  commit-msg     - Enhance commit message
  code-docs      - Generate code documentation
  --dry-run      - Preview without writing
  --help         - Show this help

Examples:
  python ollama-doc-generator.py post-commit
  python ollama-doc-generator.py changelog --dry-run
  SKIP_OLLAMA=1 git commit  # Skip automation
"""

import os
import sys
import json
import subprocess
import re
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple
import urllib.request
import urllib.error

# Fix Windows console encoding for emojis
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass  # Fallback if reconfigure not available

# Configuration
SCRIPT_DIR = Path(__file__).parent
CONFIG_FILE = SCRIPT_DIR / 'ollama-config.json'
OLLAMA_URL = 'http://localhost:11434'
LMSTUDIO_URL = 'http://localhost:1234/v1'
DEFAULT_BACKEND = 'lmstudio'
DEFAULT_OLLAMA_MODEL = 'qwen2.5-coder:14b'
DEFAULT_LMSTUDIO_MODEL = 'qwen/qwen3-coder-30b'
CHANGELOG_PATH = SCRIPT_DIR.parent / 'docs' / 'CHANGELOG.md'
VERSION_PATH = SCRIPT_DIR.parent / 'version.json'
TIMEOUT = 60  # seconds


def load_config() -> Dict:
    """Load configuration from file or use defaults"""
    defaults = {
        'enabled': True,
        'backend': DEFAULT_BACKEND,
        'ollama_url': OLLAMA_URL,
        'ollama_model': DEFAULT_OLLAMA_MODEL,
        'lmstudio_url': LMSTUDIO_URL,
        'lmstudio_model': DEFAULT_LMSTUDIO_MODEL,
        'temperature': 0.3,
        'max_tokens': 500,
        'auto_commit': False,
        'update_changelog': True,
        'update_code_docs': False
    }

    if CONFIG_FILE.exists():
        with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
            config = json.load(f)
            merged = {**defaults, **config}

            # Backward compatibility: if "model" exists but no backend specified
            if 'model' in config and 'backend' not in config:
                merged['ollama_model'] = config['model']
                merged['backend'] = 'ollama'

            return merged

    return defaults


def check_ollama_running(url: str) -> bool:
    """Check if Ollama is running"""
    try:
        req = urllib.request.Request(f"{url}/api/tags")
        with urllib.request.urlopen(req, timeout=2) as response:
            return response.status == 200
    except (urllib.error.URLError, Exception):
        return False


def check_lmstudio_running(url: str) -> bool:
    """Check if LM Studio is running"""
    try:
        req = urllib.request.Request(f"{url}/models")
        with urllib.request.urlopen(req, timeout=2) as response:
            return response.status == 200
    except (urllib.error.URLError, Exception):
        return False


def get_lmstudio_loaded_model(url: str) -> str:
    """Get the currently loaded model in LM Studio"""
    try:
        req = urllib.request.Request(f"{url}/models")
        with urllib.request.urlopen(req, timeout=2) as response:
            data = json.loads(response.read().decode('utf-8'))
            # LM Studio returns {"data": [{"id": "model-name", ...}]}
            if 'data' in data and len(data['data']) > 0:
                return data['data'][0].get('id', 'unknown')
    except (urllib.error.URLError, Exception):
        pass
    return None


def query_ollama(url: str, model: str, prompt: str, temperature: float, max_tokens: int) -> str:
    """Send prompt to Ollama and get response"""
    data = {
        'model': model,
        'prompt': prompt,
        'stream': False,
        'options': {
            'temperature': temperature,
            'num_predict': max_tokens
        }
    }

    try:
        req = urllib.request.Request(
            f"{url}/api/generate",
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=TIMEOUT) as response:
            if response.status != 200:
                raise Exception(f"Ollama request failed with HTTP {response.status}")

            result = json.loads(response.read().decode('utf-8'))
            if 'response' not in result:
                raise Exception("Invalid Ollama response")

            return result['response']

    except urllib.error.URLError as e:
        raise Exception(f"Failed to connect to Ollama: {e}")
    except Exception as e:
        raise Exception(f"Ollama request failed: {e}")


def query_lmstudio(url: str, model: str, prompt: str, temperature: float, max_tokens: int) -> str:
    """Send prompt to LM Studio (OpenAI-compatible API) and get response"""
    data = {
        'model': model,
        'messages': [
            {
                'role': 'user',
                'content': prompt
            }
        ],
        'temperature': temperature,
        'max_tokens': max_tokens,
        'stream': False
    }

    try:
        req = urllib.request.Request(
            f"{url}/chat/completions",
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=TIMEOUT) as response:
            if response.status != 200:
                raise Exception(f"LM Studio request failed with HTTP {response.status}")

            result = json.loads(response.read().decode('utf-8'))
            if 'choices' not in result or len(result['choices']) == 0:
                raise Exception("Invalid LM Studio response")

            return result['choices'][0]['message']['content']

    except urllib.error.HTTPError as e:
        error_body = e.read().decode('utf-8') if hasattr(e, 'read') else str(e)
        if 'model' in error_body.lower() or e.code == 404:
            raise Exception(
                f"LM Studio model error: Model '{model}' may not be loaded.\n"
                f"   Please open LM Studio and load the '{model}' model, then try again."
            )
        raise Exception(f"LM Studio HTTP error ({e.code}): {error_body}")
    except urllib.error.URLError as e:
        raise Exception(f"Failed to connect to LM Studio: {e}")
    except Exception as e:
        raise Exception(f"LM Studio request failed: {e}")


def query_llm(config: Dict, prompt: str) -> str:
    """Query the configured LLM backend (with fallback)"""
    backend = config.get('backend', 'lmstudio').lower()

    # Try primary backend
    if backend == 'lmstudio':
        if check_lmstudio_running(config['lmstudio_url']):
            try:
                return query_lmstudio(
                    config['lmstudio_url'],
                    config['lmstudio_model'],
                    prompt,
                    config['temperature'],
                    config['max_tokens']
                )
            except Exception as e:
                print(f"   ⚠️  LM Studio failed: {e}")
                print(f"   🔄 Falling back to Ollama...")

        # Fallback to Ollama
        if check_ollama_running(config['ollama_url']):
            return query_ollama(
                config['ollama_url'],
                config['ollama_model'],
                prompt,
                config['temperature'],
                config['max_tokens']
            )
        else:
            raise Exception("Neither LM Studio nor Ollama is running")

    elif backend == 'ollama':
        if check_ollama_running(config['ollama_url']):
            try:
                return query_ollama(
                    config['ollama_url'],
                    config['ollama_model'],
                    prompt,
                    config['temperature'],
                    config['max_tokens']
                )
            except Exception as e:
                print(f"   ⚠️  Ollama failed: {e}")
                print(f"   🔄 Falling back to LM Studio...")

        # Fallback to LM Studio
        if check_lmstudio_running(config['lmstudio_url']):
            return query_lmstudio(
                config['lmstudio_url'],
                config['lmstudio_model'],
                prompt,
                config['temperature'],
                config['max_tokens']
            )
        else:
            raise Exception("Neither Ollama nor LM Studio is running")

    else:
        raise Exception(f"Unknown backend: {backend}. Use 'ollama' or 'lmstudio'")


def get_last_commit() -> Dict:
    """Get last commit information"""
    try:
        # Get commit info
        commit_info = subprocess.check_output(
            ['git', 'log', '-1', '--pretty=format:%H|%s|%b'],
            encoding='utf-8'
        ).strip()

        parts = commit_info.split('|', 2)
        hash_val = parts[0] if len(parts) > 0 else ''
        subject = parts[1] if len(parts) > 1 else ''
        body = parts[2] if len(parts) > 2 else ''

        # Get changed files
        files = subprocess.check_output(
            ['git', 'diff-tree', '--no-commit-id', '--name-status', '-r', 'HEAD'],
            encoding='utf-8'
        ).strip().split('\n')

        # Get diff
        diff = subprocess.check_output(
            ['git', 'show', 'HEAD', '--format=', '--unified=3'],
            encoding='utf-8'
        ).strip()

        return {
            'hash': hash_val,
            'subject': subject,
            'body': body,
            'files': files,
            'diff': diff
        }

    except subprocess.CalledProcessError as e:
        raise Exception(f"Failed to get git commit info: {e}")


def generate_changelog_from_commit(config: Dict, commit: Dict, dry_run: bool) -> None:
    """Generate changelog entry from commit"""
    files_str = '\n'.join(commit['files'][:10])  # Limit to first 10 files
    if len(commit['files']) > 10:
        files_str += f"\n... and {len(commit['files']) - 10} more files"

    # Limit diff size to avoid token overflow
    diff_str = commit['diff'][:3000]
    if len(commit['diff']) > 3000:
        diff_str += "\n... (diff truncated)"

    prompt = f"""You are a technical documentation expert analyzing a git commit. Generate a concise changelog entry.

**Commit Message:**
{commit['subject']}

**Files Changed:**
{files_str}

**Code Changes:**
{diff_str}

**Task:** Generate a changelog entry in this markdown format:

### Added
- New features (if any)

### Changed
- Modifications (if any)

### Fixed
- Bug fixes (if any)

Keep it concise, professional, and user-focused. Only include sections that apply.
Respond with ONLY the markdown changelog entry, no extra text."""

    backend = config.get('backend', 'lmstudio')
    model = config[f'{backend}_model']
    print(f"   Querying {backend.upper()} ({model})...")
    start_time = datetime.now()

    try:
        entry = query_llm(config, prompt)

        duration = (datetime.now() - start_time).total_seconds()
        print(f"   ✓ Generated in {duration:.2f}s\n")

        print("   Preview:")
        print("   " + "-" * 60)
        for line in entry.strip().split('\n'):
            print(f"   {line}")
        print("   " + "-" * 60 + "\n")

        if dry_run:
            print("   (Dry run - not writing to file)")
        else:
            update_changelog(entry, commit)

    except Exception as e:
        print(f"   ❌ Failed: {e}")


def update_changelog(entry: str, commit: Dict) -> None:
    """Update CHANGELOG.md with new entry"""
    if not CHANGELOG_PATH.exists():
        print(f"   ⚠️  CHANGELOG.md not found at {CHANGELOG_PATH}")
        return

    # Read current changelog
    with open(CHANGELOG_PATH, 'r', encoding='utf-8') as f:
        changelog = f.read()

    # Get current version from version.json
    with open(VERSION_PATH, 'r', encoding='utf-8') as f:
        version_data = json.load(f)
        current_version = version_data.get('version', '3.3.0')

    # Parse version and increment patch version
    version_parts = current_version.split('.')
    version_parts[2] = str(int(version_parts[2]) + 1)
    new_version = '.'.join(version_parts)

    # Generate changelog entry header
    date = datetime.now().strftime('%Y-%m-%d')
    commit_hash = commit['hash'][:7]

    new_entry = f"\n## [{new_version}] - {date}\n\n"
    new_entry += f"**Commit:** {commit_hash} - {commit['subject']}\n\n"
    new_entry += entry.strip() + "\n\n"
    new_entry += "---\n"

    # Find insertion point (after the header section, before first version entry)
    lines = changelog.split('\n')
    insert_index = 0

    for i, line in enumerate(lines):
        # Look for first version header (## [X.Y.Z])
        if re.match(r'^## \[[\d\.]+\]', line):
            insert_index = i
            break

    if insert_index == 0:
        print("   ⚠️  Could not find insertion point in CHANGELOG.md")
        return

    # Insert new entry
    lines.insert(insert_index, new_entry)
    updated_changelog = '\n'.join(lines)

    # Write back to file
    with open(CHANGELOG_PATH, 'w', encoding='utf-8') as f:
        f.write(updated_changelog)

    print(f"   ✅ Updated CHANGELOG.md with version {new_version}")
    print(f"   📝 Location: {CHANGELOG_PATH}")


def handle_post_commit(config: Dict, dry_run: bool) -> None:
    """Handle post-commit hook"""
    print("📝 Analyzing last commit...")

    commit = get_last_commit()

    if not commit['hash']:
        print("⚠️  No commits found")
        return

    print(f"   Commit: {commit['hash'][:7]}")
    print(f"   Message: {commit['subject']}")
    print(f"   Files: {len(commit['files'])}\n")

    # Generate changelog entry if enabled
    if config['update_changelog']:
        print("📋 Generating changelog entry...")
        generate_changelog_from_commit(config, commit, dry_run)

    # Generate code documentation if enabled
    if config['update_code_docs']:
        print("📖 Generating code documentation...")
        print("   ⚠️  Code docs from commit not yet implemented")


def show_help() -> None:
    """Show help message"""
    print("""Ollama Documentation Generator v1.0.0

Automatically generates documentation and changelog entries using local Ollama LLM.

USAGE:
    python ollama-doc-generator.py [mode] [options]

MODES:
    post-commit    Generate changelog from last commit (default)
    changelog      Generate changelog entry manually
    commit-msg     Enhance commit message
    code-docs      Generate code documentation
    --help         Show this help

OPTIONS:
    --dry-run      Preview changes without writing files

ENVIRONMENT:
    SKIP_OLLAMA=1  Disable automation for this commit

EXAMPLES:
    # Run after commit (git hook)
    python ollama-doc-generator.py post-commit

    # Preview without writing
    python ollama-doc-generator.py post-commit --dry-run

    # Skip automation
    SKIP_OLLAMA=1 git commit -m "message"

SETUP:
    1. Install Ollama: https://ollama.ai
    2. Pull model: ollama pull qwen2.5-coder:14b
    3. Install git hook: see docs/OLLAMA_AUTOMATION.md

CONFIGURATION:
    Edit scripts/ollama-config.json to customize behavior

MORE INFO:
    docs/OLLAMA_AUTOMATION.md
""")


def main():
    """Main execution"""
    # Check if automation is disabled
    if os.getenv('SKIP_OLLAMA') == '1':
        print("ℹ️  Ollama automation skipped (SKIP_OLLAMA=1)")
        sys.exit(0)

    # Parse arguments
    mode = sys.argv[1] if len(sys.argv) > 1 else 'post-commit'
    dry_run = '--dry-run' in sys.argv

    if mode in ['--help', '-h']:
        show_help()
        sys.exit(0)

    # Load configuration
    config = load_config()

    if not config['enabled']:
        print("ℹ️  Ollama automation disabled in config")
        sys.exit(0)

    # Main execution
    try:
        backend = config.get('backend', 'lmstudio')
        model = config[f'{backend}_model']

        print("🤖 LLM Documentation Generator")
        print(f"   Backend: {backend.upper()}")
        print(f"   Model: {model}")
        print(f"   Mode: {mode}")
        if dry_run:
            print("   Dry run: Yes")
        print()

        # Check if at least one backend is running
        lmstudio_running = check_lmstudio_running(config['lmstudio_url'])
        ollama_running = check_ollama_running(config['ollama_url'])

        if not lmstudio_running and not ollama_running:
            print("❌ Neither LM Studio nor Ollama is running.")
            print("   Start LM Studio: Open LM Studio and start the local server")
            print("   OR start Ollama: ollama serve")
            sys.exit(0)  # Don't block git operations

        if backend == 'lmstudio' and not lmstudio_running:
            print(f"⚠️  LM Studio is not running, will use Ollama as fallback")
        elif backend == 'ollama' and not ollama_running:
            print(f"⚠️  Ollama is not running, will use LM Studio as fallback")

        # Verify loaded model matches configuration
        if backend == 'lmstudio' and lmstudio_running:
            loaded_model = get_lmstudio_loaded_model(config['lmstudio_url'])
            expected_model = config['lmstudio_model']
            if loaded_model and loaded_model != expected_model:
                print(f"⚠️  WARNING: Wrong model loaded in LM Studio!")
                print(f"   Expected: {expected_model}")
                print(f"   Loaded:   {loaded_model}")
                print(f"   Attempting to use {expected_model} anyway...")
                print(f"   (Note: LM Studio doesn't support automatic model switching)")
                print(f"   If this fails, please manually load {expected_model} in LM Studio")
                print()

        # Execute based on mode
        if mode == 'post-commit':
            handle_post_commit(config, dry_run)
        elif mode == 'changelog':
            print("📋 Manual changelog generation not yet implemented")
            print("   Use: python ollama-doc-generator.py post-commit")
        elif mode == 'commit-msg':
            print("💬 Commit message enhancement not yet implemented")
        elif mode == 'code-docs':
            print("📖 Code documentation generation not yet implemented")
        else:
            print(f"❌ Unknown mode: {mode}")
            show_help()
            sys.exit(1)

        print("\n✅ Done!")

    except Exception as e:
        print(f"❌ Error: {e}")
        print("   Continuing without automation...")
        sys.exit(0)  # Don't block git operations


if __name__ == '__main__':
    main()
