#!/bin/bash
# Git Hooks Setup Script
# Copies hooks from scripts/git-hooks/ to .git/hooks/

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
HOOKS_SOURCE="$REPO_ROOT/scripts/git-hooks"
HOOKS_DEST="$REPO_ROOT/.git/hooks"

echo "Setting up Git hooks..."

# Check if source directory exists
if [ ! -d "$HOOKS_SOURCE" ]; then
    echo "Error: Source directory not found: $HOOKS_SOURCE"
    exit 1
fi

# Check if .git directory exists
if [ ! -d "$REPO_ROOT/.git" ]; then
    echo "Error: Not a git repository"
    exit 1
fi

# Copy all hook files
cp "$HOOKS_SOURCE"/* "$HOOKS_DEST/"

# Make hooks executable (for Unix-like systems)
if [ "$(uname)" != "MINGW"* ] && [ "$(uname)" != "MSYS"* ]; then
    chmod +x "$HOOKS_DEST"/*
fi

echo "✓ Git hooks installed successfully!"
echo ""
echo "Hooks installed:"
ls -1 "$HOOKS_DEST" | grep -v ".sample" | head -10

echo ""
echo "To skip hooks for a single commit, use:"
echo "  git commit --no-verify -m \"message\""
