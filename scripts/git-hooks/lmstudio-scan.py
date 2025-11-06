#!/usr/bin/env python3
"""
LM Studio Security Scanner for Git Pre-Commit Hook
Sends diff to LM Studio for security and quality review
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

def main():
    if len(sys.argv) < 2:
        print("\033[0;31m❌ Error: No diff file provided\033[0m")
        sys.exit(1)

    diff_file = sys.argv[1]

    # Read diff content
    try:
        with open(diff_file, 'r', encoding='utf-8', errors='ignore') as f:
            diff_content = f.read()
    except Exception as e:
        print(f"\033[0;31m❌ Error reading diff: {e}\033[0m")
        sys.exit(1)

    # Truncate if too large
    if len(diff_content) > 5000:
        diff_content = diff_content[:5000] + "\n... (truncated)"

    # Build prompt for LM Studio
    prompt = f"""Review this git diff for security issues and code quality problems.

DIFF:
```
{diff_content}
```

Provide a BRIEF (2-3 sentences) summary of:
1. Any security concerns (XSS, SQL injection, auth issues, sensitive data)
2. Critical code quality issues only

If no major issues: respond with "✅ No critical issues detected"
"""

    # Prepare API request
    data = {
        'model': 'qwen/qwen3-coder-30b',
        'messages': [
            {'role': 'system', 'content': 'You are a security-focused code reviewer. Be concise and actionable.'},
            {'role': 'user', 'content': prompt}
        ],
        'temperature': 0.2,
        'max_tokens': 200,
        'stream': False
    }

    try:
        req = urllib.request.Request(
            'http://localhost:1234/v1/chat/completions',
            data=json.dumps(data).encode('utf-8'),
            headers={'Content-Type': 'application/json'}
        )

        with urllib.request.urlopen(req, timeout=30) as response:
            result = json.loads(response.read().decode('utf-8'))
            review = result['choices'][0]['message']['content'].strip()

            # Check if issues were found
            if '✅' in review or 'no critical issues' in review.lower() or 'no major issues' in review.lower():
                print(f"\033[0;32m✓\033[0m LM Studio: {review}")
                sys.exit(0)
            else:
                print(f"\033[1;33m⚠️  LM Studio Review:\033[0m")
                print(f"   {review}")
                print("")

                # Ask user if they want to proceed
                confirm = input("Continue with commit? (yes/no): ")
                if confirm.lower() != 'yes':
                    print("\033[0;31m❌ Commit aborted\033[0m")
                    sys.exit(1)
                else:
                    sys.exit(0)

    except urllib.error.URLError as e:
        print(f"\033[1;33m⚠️  LM Studio timeout (continuing anyway)\033[0m")
        sys.exit(0)

    except Exception as e:
        print(f"\033[1;33m⚠️  LM Studio check failed: {e}\033[0m")
        print("   Continuing anyway...")
        sys.exit(0)

if __name__ == '__main__':
    main()
