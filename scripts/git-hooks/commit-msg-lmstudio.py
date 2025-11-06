#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
LM Studio Commit Message Review
Checks commit message quality using LM Studio API
"""
import sys
import json
import urllib.request
import urllib.error
import io

# Fix Windows console encoding for Unicode
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

def review_commit_message(commit_msg, diff_summary):
    """Review commit message with LM Studio"""

    prompt = f"""Review this git commit message for quality and clarity.

COMMIT MESSAGE:
{commit_msg}

CHANGES SUMMARY:
{diff_summary}

Evaluate:
1. Is the message clear and descriptive?
2. Does it accurately describe what changed?
3. Any suggestions for improvement?

Respond in one of these formats:

✅ Good commit message
OR
⚠️  Suggestion: [brief improvement suggestion]
OR
❌ Poor commit message: [reason]

Be concise (1-2 sentences max).
"""

    data = {
        'model': 'qwen/qwen3-coder-30b',
        'messages': [
            {'role': 'system', 'content': 'You are a commit message reviewer. Be concise and constructive.'},
            {'role': 'user', 'content': prompt}
        ],
        'temperature': 0.3,
        'max_tokens': 150,
        'stream': False
    }

    try:
        req = urllib.request.Request(
            'http://localhost:1234/v1/chat/completions',
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=25) as response:
            result = json.loads(response.read().decode('utf-8'))
            review = result['choices'][0]['message']['content'].strip()

            if '✅' in review:
                print(f"\033[0;32m✓\033[0m LM Studio: {review}")
            elif '⚠️' in review:
                print(f"\033[1;33m{review}\033[0m")
            elif '❌' in review:
                print(f"\033[0;31m{review}\033[0m")
                print("")
                confirm = input("Continue anyway? (yes/no): ")
                if confirm.lower() != 'yes':
                    print("\033[0;31m❌ Commit aborted\033[0m")
                    return 1
            else:
                print(f"\033[1;33m⚠️  {review}\033[0m")

    except urllib.error.URLError:
        print("\033[1;33m⚠️  LM Studio timeout (continuing anyway)\033[0m")
    except Exception as e:
        print(f"\033[1;33m⚠️  LM Studio review failed: {e}\033[0m")

    return 0

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: commit-msg-lmstudio.py <commit_msg> <diff_summary>")
        sys.exit(1)

    commit_msg = sys.argv[1]
    diff_summary = sys.argv[2]

    sys.exit(review_commit_message(commit_msg, diff_summary))
