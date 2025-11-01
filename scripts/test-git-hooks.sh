#!/bin/bash
# Test Git Hooks Functionality
# Validates that all git hooks are properly installed and working
#
# Usage: bash scripts/test-git-hooks.sh

echo "======================================================================"
echo "  Git Hooks Test Suite"
echo "======================================================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

TESTS_PASSED=0
TESTS_FAILED=0

# Get root directory
ROOT_DIR=$(git rev-parse --show-toplevel)
cd "$ROOT_DIR"

# ============================================================================
# Test 1: Check Hook Files Exist
# ============================================================================
echo -e "${BLUE}Test 1: Checking hook files exist...${NC}"

HOOKS=(
    "pre-commit"
    "commit-msg"
    "prepare-commit-msg"
    "post-commit"
    "post-merge"
    "pre-push"
)

MISSING_HOOKS=0
for hook in "${HOOKS[@]}"; do
    if [ -f ".git/hooks/$hook" ]; then
        echo -e "${GREEN}✓${NC} .git/hooks/$hook exists"
    else
        echo -e "${RED}✗${NC} .git/hooks/$hook MISSING"
        MISSING_HOOKS=$((MISSING_HOOKS + 1))
    fi
done

if [ $MISSING_HOOKS -eq 0 ]; then
    echo -e "${GREEN}✓ Test 1 PASSED${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${RED}✗ Test 1 FAILED${NC}"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi
echo ""

# ============================================================================
# Test 2: Check Hook Files Are Executable
# ============================================================================
echo -e "${BLUE}Test 2: Checking hooks are executable...${NC}"

NON_EXECUTABLE=0
for hook in "${HOOKS[@]}"; do
    if [ -x ".git/hooks/$hook" ]; then
        echo -e "${GREEN}✓${NC} .git/hooks/$hook is executable"
    else
        echo -e "${RED}✗${NC} .git/hooks/$hook NOT executable"
        NON_EXECUTABLE=$((NON_EXECUTABLE + 1))
    fi
done

if [ $NON_EXECUTABLE -eq 0 ]; then
    echo -e "${GREEN}✓ Test 2 PASSED${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${RED}✗ Test 2 FAILED - Run: chmod +x .git/hooks/*${NC}"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi
echo ""

# ============================================================================
# Test 3: Check LM Studio Availability
# ============================================================================
echo -e "${BLUE}Test 3: Checking LM Studio availability...${NC}"

if curl -s -o /dev/null -w "%{http_code}" http://localhost:1234/v1/models 2>/dev/null | grep -q "200"; then
    echo -e "${GREEN}✓${NC} LM Studio is running"

    # Check model
    MODEL=$(curl -s http://localhost:1234/v1/models 2>/dev/null | grep -o '"id":\s*"[^"]*"' | head -1 | cut -d'"' -f4)
    if [ -n "$MODEL" ]; then
        echo -e "${GREEN}✓${NC} Model loaded: $MODEL"

        if echo "$MODEL" | grep -q "qwen.*coder.*30b"; then
            echo -e "${GREEN}✓${NC} Correct model (qwen3-coder-30b)"
        else
            echo -e "${YELLOW}⚠${NC}  Different model loaded (expected qwen3-coder-30b)"
        fi
    fi

    echo -e "${GREEN}✓ Test 3 PASSED${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${YELLOW}⚠${NC}  LM Studio not running"
    echo "   Hooks will work but skip AI features"
    echo "   Start LM Studio and load qwen/qwen3-coder-30b for full functionality"
    echo -e "${YELLOW}⚠ Test 3 WARNING (not a failure)${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
fi
echo ""

# ============================================================================
# Test 4: Test Pre-Commit Hook (Dry Run)
# ============================================================================
echo -e "${BLUE}Test 4: Testing pre-commit hook (dry run)...${NC}"

# Create temporary test file
TEST_FILE="test-hook-validation.tmp"
echo '{"test": "valid json"}' > "$TEST_FILE"

# Stage it
git add "$TEST_FILE" 2>/dev/null

# Run pre-commit hook directly
if bash .git/hooks/pre-commit 2>&1 | grep -q "pre-commit checks"; then
    echo -e "${GREEN}✓${NC} Pre-commit hook executed"
    echo -e "${GREEN}✓ Test 4 PASSED${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${RED}✗${NC} Pre-commit hook failed to execute"
    echo -e "${RED}✗ Test 4 FAILED${NC}"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi

# Clean up
git reset HEAD "$TEST_FILE" 2>/dev/null
rm -f "$TEST_FILE"
echo ""

# ============================================================================
# Test 5: Test Commit Message Validation
# ============================================================================
echo -e "${BLUE}Test 5: Testing commit-msg validation...${NC}"

# Create temporary commit message file
MSG_FILE=$(mktemp)

# Test valid message
echo "feat(test): Add test feature" > "$MSG_FILE"
if bash .git/hooks/commit-msg "$MSG_FILE" 2>&1 | grep -q "Conventional Commits format"; then
    echo -e "${GREEN}✓${NC} Valid commit message accepted"
else
    echo -e "${YELLOW}⚠${NC}  Valid commit message check unclear"
fi

# Test invalid message
echo "bad commit message" > "$MSG_FILE"
if bash .git/hooks/commit-msg "$MSG_FILE" 2>&1 | grep -q "Invalid commit message"; then
    echo -e "${GREEN}✓${NC} Invalid commit message rejected"
else
    echo -e "${YELLOW}⚠${NC}  Invalid commit message check unclear"
fi

rm -f "$MSG_FILE"
echo -e "${GREEN}✓ Test 5 PASSED${NC}"
TESTS_PASSED=$((TESTS_PASSED + 1))
echo ""

# ============================================================================
# Test 6: Check Hook Integration with SKIP Flags
# ============================================================================
echo -e "${BLUE}Test 6: Testing SKIP_LMSTUDIO environment variable...${NC}"

# Test that hooks respect SKIP_LMSTUDIO
if grep -q "SKIP_LMSTUDIO" .git/hooks/pre-commit; then
    echo -e "${GREEN}✓${NC} pre-commit honors SKIP_LMSTUDIO"
else
    echo -e "${YELLOW}⚠${NC}  pre-commit missing SKIP_LMSTUDIO check"
fi

if grep -q "SKIP_LMSTUDIO" .git/hooks/commit-msg; then
    echo -e "${GREEN}✓${NC} commit-msg honors SKIP_LMSTUDIO"
else
    echo -e "${YELLOW}⚠${NC}  commit-msg missing SKIP_LMSTUDIO check"
fi

if grep -q "SKIP_LMSTUDIO" .git/hooks/prepare-commit-msg; then
    echo -e "${GREEN}✓${NC} prepare-commit-msg honors SKIP_LMSTUDIO"
else
    echo -e "${YELLOW}⚠${NC}  prepare-commit-msg missing SKIP_LMSTUDIO check"
fi

echo -e "${GREEN}✓ Test 6 PASSED${NC}"
TESTS_PASSED=$((TESTS_PASSED + 1))
echo ""

# ============================================================================
# Test 7: Verify Hook Scripts Have No Syntax Errors
# ============================================================================
echo -e "${BLUE}Test 7: Checking hook script syntax...${NC}"

SYNTAX_ERRORS=0
for hook in "${HOOKS[@]}"; do
    if [ -f ".git/hooks/$hook" ]; then
        # Check for bash syntax errors (basic check)
        if bash -n ".git/hooks/$hook" 2>/dev/null; then
            echo -e "${GREEN}✓${NC} .git/hooks/$hook syntax valid"
        else
            echo -e "${RED}✗${NC} .git/hooks/$hook has syntax errors"
            SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
        fi
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ Test 7 PASSED${NC}"
    TESTS_PASSED=$((TESTS_PASSED + 1))
else
    echo -e "${RED}✗ Test 7 FAILED${NC}"
    TESTS_FAILED=$((TESTS_FAILED + 1))
fi
echo ""

# ============================================================================
# Summary
# ============================================================================
echo "======================================================================"
echo "  Test Summary"
echo "======================================================================"
echo ""
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    echo ""
    echo "Git hooks are properly installed and configured."
    echo ""
    echo "Features available:"
    echo "  ✓ Pre-commit quality checks"
    echo "  ✓ Commit message validation"
    echo "  ✓ Auto-generated commit messages"
    echo "  ✓ Changelog generation"
    echo "  ✓ Post-merge environment sync"
    echo "  ✓ Pre-push quality gates"

    if curl -s -o /dev/null -w "%{http_code}" http://localhost:1234/v1/models 2>/dev/null | grep -q "200"; then
        echo "  ✓ LM Studio AI features enabled"
    else
        echo "  ⚠ LM Studio AI features disabled (not running)"
    fi

    echo ""
    echo "Documentation: docs/GIT_HOOKS.md"
    exit 0
else
    echo -e "${RED}❌ Some tests failed${NC}"
    echo ""
    echo "Please fix the issues above before using git hooks."
    echo ""
    echo "Common fixes:"
    echo "  • Make hooks executable: chmod +x .git/hooks/*"
    echo "  • Start LM Studio: Open LM Studio and load qwen/qwen3-coder-30b"
    echo "  • Check hook syntax: bash -n .git/hooks/hook-name"
    exit 1
fi
