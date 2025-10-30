#!/bin/bash
# Unix/Linux/Mac Shell Script for Running Admin Panel Unit Tests
# Version: 1.0.0
# Date: 2025-10-16

echo ""
echo "================================================"
echo "  Last War 1586 Admin Panel Unit Tests"
echo "  Role-Based Access Control Test Suite"
echo "================================================"
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found"
    echo "Please install PHP and ensure it's in your PATH"
    exit 1
fi

# Display PHP version
echo "Checking PHP version..."
php -v
echo ""

# Check if test file exists
if [ ! -f "run-tests.php" ]; then
    echo "ERROR: run-tests.php not found"
    echo "Please run this script from the admin/tests directory"
    exit 1
fi

# Run tests
echo "Running tests..."
echo ""
php run-tests.php

# Capture exit code
TEST_EXIT_CODE=$?

echo ""
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ Tests completed successfully!"
else
    echo "❌ Tests failed! Please review errors above."
fi

exit $TEST_EXIT_CODE
