#!/usr/bin/env python3
"""
Ollama vs LM Studio Comparison Tool

Runs the same prompt through both Ollama and LM Studio to compare output quality,
speed, and style differences.

Usage:
  python ollama-compare.py [commit-hash]

Examples:
  python ollama-compare.py              # Compare on last commit
  python ollama-compare.py HEAD~1       # Compare on previous commit
  python ollama-compare.py 51779ce      # Compare on specific commit

Version: 1.0.0
Date: 2025-10-29
"""

import os
import sys
import json
import subprocess
import time
from pathlib import Path
from datetime import datetime
from typing import Dict, Optional
import urllib.request
import urllib.error

# Fix Windows console encoding for emojis
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Configuration
OLLAMA_URL = 'http://localhost:11434'
LMSTUDIO_URL = 'http://localhost:1234/v1'
OLLAMA_MODEL = 'qwen2.5-coder:14b'
LMSTUDIO_MODEL = 'qwen/qwen3-coder-30b'
TEMPERATURE = 0.3
MAX_TOKENS = 500
TIMEOUT = 60


def get_commit_info(commit_ref: str = 'HEAD') -> Dict:
    """Get commit information"""
    try:
        # Get commit info
        commit_info = subprocess.check_output(
            ['git', 'log', '-1', '--pretty=format:%H|%s|%b', commit_ref],
            encoding='utf-8'
        ).strip()

        parts = commit_info.split('|', 2)
        hash_val = parts[0] if len(parts) > 0 else ''
        subject = parts[1] if len(parts) > 1 else ''
        body = parts[2] if len(parts) > 2 else ''

        # Get changed files
        files = subprocess.check_output(
            ['git', 'diff-tree', '--no-commit-id', '--name-status', '-r', commit_ref],
            encoding='utf-8'
        ).strip().split('\n')

        # Get diff (limited to avoid token overflow)
        diff = subprocess.check_output(
            ['git', 'show', commit_ref, '--format=', '--unified=3'],
            encoding='utf-8'
        ).strip()

        return {
            'hash': hash_val,
            'subject': subject,
            'body': body,
            'files': files,
            'diff': diff[:3000] + ("\n... (diff truncated)" if len(diff) > 3000 else "")
        }

    except subprocess.CalledProcessError as e:
        raise Exception(f"Failed to get git commit info: {e}")


def build_prompt(commit: Dict) -> str:
    """Build the changelog generation prompt"""
    files_str = '\n'.join(commit['files'][:10])
    if len(commit['files']) > 10:
        files_str += f"\n... and {len(commit['files']) - 10} more files"

    return f"""You are a technical documentation expert analyzing a git commit. Generate a concise changelog entry.

**Commit Message:**
{commit['subject']}

**Files Changed:**
{files_str}

**Code Changes:**
{commit['diff']}

**Task:** Generate a changelog entry in this markdown format:

### Added
- New features (if any)

### Changed
- Modifications (if any)

### Fixed
- Bug fixes (if any)

Keep it concise, professional, and user-focused. Only include sections that apply.
Respond with ONLY the markdown changelog entry, no extra text."""


def query_ollama(prompt: str) -> tuple[str, float]:
    """Query Ollama and return response + duration"""
    data = {
        'model': OLLAMA_MODEL,
        'prompt': prompt,
        'stream': False,
        'options': {
            'temperature': TEMPERATURE,
            'num_predict': MAX_TOKENS
        }
    }

    start_time = time.time()

    try:
        req = urllib.request.Request(
            f"{OLLAMA_URL}/api/generate",
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=TIMEOUT) as response:
            if response.status != 200:
                raise Exception(f"Ollama request failed with HTTP {response.status}")

            result = json.loads(response.read().decode('utf-8'))
            duration = time.time() - start_time

            if 'response' not in result:
                raise Exception("Invalid Ollama response")

            return result['response'], duration

    except urllib.error.URLError as e:
        raise Exception(f"Failed to connect to Ollama: {e}")
    except Exception as e:
        raise Exception(f"Ollama request failed: {e}")


def query_lmstudio(prompt: str) -> tuple[str, float]:
    """Query LM Studio (OpenAI-compatible API) and return response + duration"""
    data = {
        'model': LMSTUDIO_MODEL,
        'messages': [
            {
                'role': 'user',
                'content': prompt
            }
        ],
        'temperature': TEMPERATURE,
        'max_tokens': MAX_TOKENS,
        'stream': False
    }

    start_time = time.time()

    try:
        req = urllib.request.Request(
            f"{LMSTUDIO_URL}/chat/completions",
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=TIMEOUT) as response:
            if response.status != 200:
                raise Exception(f"LM Studio request failed with HTTP {response.status}")

            result = json.loads(response.read().decode('utf-8'))
            duration = time.time() - start_time

            if 'choices' not in result or len(result['choices']) == 0:
                raise Exception("Invalid LM Studio response")

            return result['choices'][0]['message']['content'], duration

    except urllib.error.URLError as e:
        raise Exception(f"Failed to connect to LM Studio: {e}")
    except Exception as e:
        raise Exception(f"LM Studio request failed: {e}")


def check_service(name: str, check_func) -> bool:
    """Check if a service is running"""
    try:
        check_func()
        return True
    except:
        return False


def print_section(title: str):
    """Print a section header"""
    print(f"\n{'='*80}")
    print(f"  {title}")
    print(f"{'='*80}\n")


def print_output(content: str, prefix: str = "  "):
    """Print output with proper indentation"""
    for line in content.strip().split('\n'):
        print(f"{prefix}{line}")


def main():
    commit_ref = sys.argv[1] if len(sys.argv) > 1 else 'HEAD'

    print_section("🔬 Ollama vs LM Studio Comparison Tool")

    # Get commit info
    print("📝 Loading commit information...")
    try:
        commit = get_commit_info(commit_ref)
        print(f"   Commit: {commit['hash'][:7]}")
        print(f"   Message: {commit['subject']}")
        print(f"   Files: {len(commit['files'])}")
    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)

    # Build prompt
    prompt = build_prompt(commit)

    print_section("📋 Prompt Template")
    print(f"   Temperature: {TEMPERATURE}")
    print(f"   Max Tokens: {MAX_TOKENS}")
    print(f"   Prompt Length: {len(prompt)} characters\n")
    print("   Preview (first 300 chars):")
    print_output(prompt[:300] + "...", "   ")

    # Check services
    print_section("🔍 Checking Services")

    ollama_running = False
    lmstudio_running = False

    try:
        req = urllib.request.Request(f"{OLLAMA_URL}/api/tags")
        with urllib.request.urlopen(req, timeout=2) as response:
            ollama_running = response.status == 200
    except:
        pass

    try:
        req = urllib.request.Request(f"{LMSTUDIO_URL}/models")
        with urllib.request.urlopen(req, timeout=2) as response:
            lmstudio_running = response.status == 200
    except:
        pass

    print(f"   Ollama ({OLLAMA_MODEL}): {'✅ Running' if ollama_running else '❌ Not running'}")
    print(f"   LM Studio ({LMSTUDIO_MODEL}): {'✅ Running' if lmstudio_running else '❌ Not running'}")

    if not ollama_running and not lmstudio_running:
        print("\n❌ Neither service is running. Start at least one:")
        print("   - Ollama: ollama serve")
        print("   - LM Studio: Open LM Studio and start the local server")
        sys.exit(1)

    # Query Ollama
    ollama_response = None
    ollama_duration = 0

    if ollama_running:
        print_section(f"🤖 Querying Ollama ({OLLAMA_MODEL})")
        try:
            ollama_response, ollama_duration = query_ollama(prompt)
            print(f"   ✓ Generated in {ollama_duration:.2f}s")
        except Exception as e:
            print(f"   ❌ Failed: {e}")

    # Query LM Studio
    lmstudio_response = None
    lmstudio_duration = 0

    if lmstudio_running:
        print_section(f"🎨 Querying LM Studio ({LMSTUDIO_MODEL})")
        try:
            lmstudio_response, lmstudio_duration = query_lmstudio(prompt)
            print(f"   ✓ Generated in {lmstudio_duration:.2f}s")
        except Exception as e:
            print(f"   ❌ Failed: {e}")

    # Display results
    print_section("📊 Comparison Results")

    if ollama_response:
        print(f"🤖 Ollama ({OLLAMA_MODEL}) - {ollama_duration:.2f}s")
        print("-" * 80)
        print_output(ollama_response)
        print()

    if lmstudio_response:
        print(f"🎨 LM Studio ({LMSTUDIO_MODEL}) - {lmstudio_duration:.2f}s")
        print("-" * 80)
        print_output(lmstudio_response)
        print()

    # Stats comparison
    if ollama_response and lmstudio_response:
        print_section("📈 Statistics")
        print(f"   Ollama:")
        print(f"      - Model: {OLLAMA_MODEL}")
        print(f"      - Time: {ollama_duration:.2f}s")
        print(f"      - Length: {len(ollama_response)} chars")
        print(f"      - Lines: {len(ollama_response.strip().split(chr(10)))}")
        print()
        print(f"   LM Studio:")
        print(f"      - Model: {LMSTUDIO_MODEL}")
        print(f"      - Time: {lmstudio_duration:.2f}s")
        print(f"      - Length: {len(lmstudio_response)} chars")
        print(f"      - Lines: {len(lmstudio_response.strip().split(chr(10)))}")
        print()

        speed_diff = ((lmstudio_duration - ollama_duration) / ollama_duration * 100)
        if abs(speed_diff) < 5:
            print(f"   Speed: Similar performance (~{abs(speed_diff):.1f}% difference)")
        elif ollama_duration < lmstudio_duration:
            print(f"   Speed: Ollama is {abs(speed_diff):.1f}% faster")
        else:
            print(f"   Speed: LM Studio is {abs(speed_diff):.1f}% faster")

    print()
    print("✅ Comparison complete!\n")


if __name__ == '__main__':
    main()
