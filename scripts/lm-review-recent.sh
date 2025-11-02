#!/bin/bash
# Review Recent Changes with LM Studio
# Analyzes changes from last N commits instead of entire repository
#
# Usage: bash scripts/lm-review-recent.sh [commits] [mode]
# Example: bash scripts/lm-review-recent.sh 5 security

COMMITS=${1:-5}
MODE=${2:-overview}

echo "=================================================================="
echo "  LM Studio - Recent Changes Review"
echo "=================================================================="
echo ""
echo "Analyzing last $COMMITS commits..."
echo "Mode: $MODE"
echo ""

# Get recent commit messages
echo "Recent commits:"
git log -$COMMITS --oneline
echo ""

# Get changed files
CHANGED_FILES=$(git diff HEAD~$COMMITS --name-only | head -20)
echo "Files changed:"
echo "$CHANGED_FILES"
echo ""

# Get diff summary
DIFF_STAT=$(git diff HEAD~$COMMITS --stat | head -20)
echo "Diff summary:"
echo "$DIFF_STAT"
echo ""

# Get actual changes (truncated)
DIFF_CONTENT=$(git diff HEAD~$COMMITS | head -200)

# Build prompt based on mode
case $MODE in
  security)
    PROMPT="Analyze these recent code changes for security vulnerabilities.

CHANGED FILES:
$CHANGED_FILES

DIFF SUMMARY:
$DIFF_STAT

CODE CHANGES (first 200 lines):
\`\`\`
$DIFF_CONTENT
\`\`\`

Focus on:
1. Security vulnerabilities (XSS, SQL injection, auth bypass)
2. Sensitive data exposure
3. Input validation issues
4. Authentication/authorization problems

Provide:
- Severity (Critical/High/Medium/Low)
- Specific file:line references
- Remediation steps"
    ;;

  quality)
    PROMPT="Review these recent code changes for quality issues.

CHANGED FILES:
$CHANGED_FILES

DIFF SUMMARY:
$DIFF_STAT

CODE CHANGES (first 200 lines):
\`\`\`
$DIFF_CONTENT
\`\`\`

Focus on:
1. Code quality issues
2. Best practices violations
3. Potential bugs
4. Performance concerns
5. Maintainability issues

Provide specific file:line references and improvement suggestions."
    ;;

  *)
    PROMPT="Provide a brief architectural review of these recent changes.

CHANGED FILES:
$CHANGED_FILES

DIFF SUMMARY:
$DIFF_STAT

CODE CHANGES (first 200 lines):
\`\`\`
$DIFF_CONTENT
\`\`\`

Analyze:
1. What changed and why
2. Architectural impact
3. Potential issues
4. Improvement suggestions"
    ;;
esac

# Query LM Studio
echo "Querying LM Studio..."
echo ""

python - "$PROMPT" <<'PYTHON_SCRIPT'
import sys
import json
import urllib.request

prompt = sys.argv[1]

data = {
    'model': 'qwen/qwen3-coder-30b',
    'messages': [
        {
            'role': 'system',
            'content': 'You are an expert code reviewer. Be specific with file/line references and provide actionable recommendations.'
        },
        {
            'role': 'user',
            'content': prompt
        }
    ],
    'temperature': 0.2,
    'max_tokens': 2000
}

try:
    req = urllib.request.Request(
        'http://localhost:1234/v1/chat/completions',
        data=json.dumps(data).encode('utf-8'),
        headers={'Content-Type': 'application/json'}
    )

    with urllib.request.urlopen(req, timeout=120) as response:
        result = json.loads(response.read().decode('utf-8'))
        review = result['choices'][0]['message']['content']
        print(review)

except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
PYTHON_SCRIPT

echo ""
echo "=================================================================="
echo "  Review Complete"
echo "=================================================================="
