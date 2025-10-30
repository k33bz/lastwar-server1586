#!/usr/bin/env python3
"""
Generate Unit Tests Using LM Studio

Uses LM Studio (qwen3-coder-30b) to generate PHPUnit tests for PHP functions.
Can analyze code and suggest comprehensive test cases including edge cases.

Usage:
    python scripts/generate-tests.py <file_path>
    python scripts/generate-tests.py admin/includes/input_validator.php

Documentation:
- LM Studio API: http://localhost:1234/v1 (OpenAI-compatible)
- Model: qwen/qwen3-coder-30b
- Purpose: Test generation, code analysis, edge case discovery

@version 1.0.0
@date 2025-10-30
"""

import json
import sys
import urllib.request
import urllib.error
from pathlib import Path


def query_lmstudio(prompt: str, max_tokens: int = 4000) -> str:
    """
    Query LM Studio API with OpenAI-compatible format

    Args:
        prompt: The prompt to send to the model
        max_tokens: Maximum tokens in response

    Returns:
        Model response text
    """
    url = "http://localhost:1234/v1/chat/completions"

    data = {
        "model": "qwen/qwen3-coder-30b",
        "messages": [
            {
                "role": "system",
                "content": "You are an expert software testing engineer specializing in PHPUnit tests. Generate comprehensive, well-structured unit tests with edge cases."
            },
            {
                "role": "user",
                "content": prompt
            }
        ],
        "temperature": 0.2,  # Lower temperature for more deterministic test generation
        "max_tokens": max_tokens
    }

    request = urllib.request.Request(
        url,
        data=json.dumps(data).encode('utf-8'),
        headers={'Content-Type': 'application/json'}
    )

    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            result = json.loads(response.read().decode('utf-8'))
            return result['choices'][0]['message']['content']
    except urllib.error.URLError as e:
        print(f"❌ Error connecting to LM Studio: {e}")
        print("   Make sure LM Studio is running and the server is started")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Error querying LM Studio: {e}")
        sys.exit(1)


def read_php_file(file_path: str) -> str:
    """Read PHP file content"""
    path = Path(file_path)
    if not path.exists():
        print(f"❌ File not found: {file_path}")
        sys.exit(1)

    return path.read_text(encoding='utf-8')


def build_test_generation_prompt(file_path: str, code: str) -> str:
    """Build prompt for test generation"""
    return f"""Analyze this PHP file and generate comprehensive PHPUnit tests.

File: {file_path}

Code:
```php
{code}
```

Generate PHPUnit test cases that:
1. Test all functions with valid inputs
2. Test edge cases (empty strings, null, very long strings, negative numbers)
3. Test boundary values (min/max lengths, zero, maximum integers)
4. Test invalid inputs (special characters, wrong types, out-of-range values)
5. Assert both 'valid' flag and 'sanitized' return values
6. Use descriptive test method names (e.g., testValidateAllianceTagWithEmptyString)

Format the output as complete, executable PHPUnit test code with:
- Proper class structure extending TestCase
- setUp() method if needed
- Clear test method names
- Comprehensive assertions
- Comments explaining what each test validates

Return ONLY the PHP test code, no explanations before or after."""


def build_edge_case_prompt(function_name: str, code: str) -> str:
    """Build prompt for edge case discovery"""
    return f"""Analyze this PHP validation function and identify ALL edge cases that should be tested:

Function: {function_name}

Code:
```php
{code}
```

List all edge cases in this format:
1. [Category] - [Test case] - [Expected behavior]

Example:
1. Empty Input - Empty string "" - Should return valid=false, error message
2. Boundary - Exactly minimum length - Should return valid=true
3. Special Chars - Unicode emoji in tag - Should be rejected
4. Overflow - Value exceeds PHP_INT_MAX - Should handle gracefully

Be thorough and include:
- Empty/null inputs
- Boundary values (min-1, min, min+1, max-1, max, max+1)
- Special characters (unicode, emoji, control chars, SQL injection attempts, XSS attempts)
- Type mismatches (array instead of string, object, boolean)
- Extreme values (very long strings, negative numbers, zero, PHP limits)

Return ONLY the numbered list, no code."""


def save_tests(file_path: str, test_code: str):
    """Save generated tests to appropriate test file"""
    # Convert admin/includes/input_validator.php -> tests/InputValidatorTest.php
    path = Path(file_path)
    base_name = path.stem  # input_validator

    # Convert snake_case to PascalCase
    class_name = ''.join(word.capitalize() for word in base_name.split('_'))
    test_file_name = f"{class_name}Test.php"

    tests_dir = Path("tests")
    tests_dir.mkdir(exist_ok=True)

    test_file_path = tests_dir / test_file_name

    test_file_path.write_text(test_code, encoding='utf-8')
    print(f"\nTests saved to: {test_file_path}")


def main():
    if len(sys.argv) < 2:
        print("Usage: python scripts/generate-tests.py <file_path>")
        print("Example: python scripts/generate-tests.py admin/includes/input_validator.php")
        sys.exit(1)

    file_path = sys.argv[1]

    print("LM Studio Unit Test Generator")
    print(f"   File: {file_path}")
    print(f"   Model: qwen/qwen3-coder-30b")
    print()

    # Read file
    print("Reading file...")
    code = read_php_file(file_path)

    # Generate edge case analysis
    print("Analyzing edge cases...")
    # Extract first function for demo (or specify which function)
    edge_case_prompt = build_edge_case_prompt("validate_alliance_tag", code[:2000])
    edge_cases = query_lmstudio(edge_case_prompt, max_tokens=2000)

    print("\nEdge Cases Identified:")
    print("=" * 60)
    print(edge_cases)
    print("=" * 60)

    # Generate tests
    print("\nGenerating PHPUnit tests...")
    test_prompt = build_test_generation_prompt(file_path, code)
    test_code = query_lmstudio(test_prompt, max_tokens=6000)

    print("\nTests generated successfully!")
    print(f"   Lines of test code: {len(test_code.splitlines())}")

    # Save tests
    save_tests(file_path, test_code)

    print("\nNext steps:")
    print("   1. Review the generated tests")
    print("   2. Install PHPUnit: composer require --dev phpunit/phpunit")
    print("   3. Run tests: vendor/bin/phpunit tests/")
    print("   4. Add more test cases as needed")


if __name__ == "__main__":
    main()
