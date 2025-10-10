"""
Production Website Unit Tests
Validates that the deployed website is functioning correctly

Tests:
1. Website accessibility
2. HTTP response codes
3. JSON data loading
4. R5 signature history system
5. Alliance modal functionality
6. Server Discord banner
7. Content validation

Requirements:
    pip install requests beautifulsoup4

Version: 1.0.0
Date: 2025-10-10
"""

import sys
import requests
from datetime import datetime
import json
import re

# Set UTF-8 encoding for Windows console
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')
    sys.stderr = codecs.getwriter('utf-8')(sys.stderr.buffer, 'strict')

# Configuration
PRODUCTION_URL = "https://www.example.com"
TIMEOUT = 10  # seconds

class ProductionTester:
    def __init__(self):
        self.tests_run = 0
        self.tests_passed = 0
        self.tests_failed = 0
        self.failures = []

    def log_pass(self, test_name):
        """Log a passing test"""
        self.tests_passed += 1
        print(f"  ✓ {test_name}")

    def log_fail(self, test_name, reason):
        """Log a failing test"""
        self.tests_failed += 1
        self.failures.append(f"{test_name}: {reason}")
        print(f"  ✗ {test_name}")
        print(f"    Reason: {reason}")

    def test_website_accessible(self):
        """Test 1: Website is accessible"""
        print("\n[Test 1] Website Accessibility")
        self.tests_run += 1

        try:
            response = requests.get(PRODUCTION_URL, timeout=TIMEOUT)
            if response.status_code == 200:
                self.log_pass(f"Website accessible (HTTP {response.status_code})")
                return True
            else:
                self.log_fail(f"Website returned HTTP {response.status_code}", f"Expected 200, got {response.status_code}")
                return False
        except requests.exceptions.RequestException as e:
            self.log_fail("Website accessibility", str(e))
            return False

    def test_html_structure(self):
        """Test 2: HTML contains required structure"""
        print("\n[Test 2] HTML Structure")
        self.tests_run += 1

        try:
            response = requests.get(PRODUCTION_URL, timeout=TIMEOUT)
            html = response.text

            # Check for key sections
            required_sections = [
                ('header', '<header'),
                ('podium section', 'podium-section'),
                ('alliance grid', 'alliance-grid'),
                ('council section', 'council-section'),
                ('rules section', 'rules-section'),
                ('signatories section', 'signatories-section'),
                ('alliance modal', 'allianceModal')
            ]

            all_found = True
            for name, selector in required_sections:
                if selector in html:
                    print(f"    ✓ Found {name}")
                else:
                    print(f"    ✗ Missing {name}")
                    all_found = False

            if all_found:
                self.log_pass("All required HTML sections present")
                return True
            else:
                self.log_fail("HTML structure incomplete", "Missing required sections")
                return False

        except Exception as e:
            self.log_fail("HTML structure test", str(e))
            return False

    def test_json_data_loading(self):
        """Test 3: JSON data files are accessible"""
        print("\n[Test 3] JSON Data Loading")

        json_files = [
            'data/alliances.json',
            'data/rules.json',
            'data/amendments.json',
            'data/rotation-schedule.json',
            'data/server-info.json',
            'data/signature-history.json'
        ]

        all_loaded = True
        for json_file in json_files:
            self.tests_run += 1
            url = f"{PRODUCTION_URL}/{json_file}"

            try:
                response = requests.get(url, timeout=TIMEOUT)
                if response.status_code == 200:
                    data = response.json()
                    self.log_pass(f"{json_file} loaded successfully ({len(str(data))} bytes)")
                else:
                    self.log_fail(f"{json_file} loading", f"HTTP {response.status_code}")
                    all_loaded = False
            except Exception as e:
                self.log_fail(f"{json_file} loading", str(e))
                all_loaded = False

        return all_loaded

    def test_signature_history_data(self):
        """Test 4: R5 signature history data structure"""
        print("\n[Test 4] R5 Signature History Data")
        self.tests_run += 1

        try:
            url = f"{PRODUCTION_URL}/data/signature-history.json"
            response = requests.get(url, timeout=TIMEOUT)
            data = response.json()

            # Check structure
            if 'currentRulesVersion' not in data:
                self.log_fail("Signature history structure", "Missing currentRulesVersion")
                return False

            if 'alliances' not in data:
                self.log_fail("Signature history structure", "Missing alliances array")
                return False

            # Check first alliance structure
            if len(data['alliances']) > 0:
                alliance = data['alliances'][0]

                required_fields = ['tag', 'name', 'rank', 'r5History']
                for field in required_fields:
                    if field not in alliance:
                        self.log_fail("Signature history alliance structure", f"Missing {field}")
                        return False

                # Check r5History structure
                if len(alliance['r5History']) > 0:
                    r5 = alliance['r5History'][0]
                    r5_fields = ['r5Name', 'startDate', 'current', 'signatures']
                    for field in r5_fields:
                        if field not in r5:
                            self.log_fail("R5 history structure", f"Missing {field}")
                            return False

            print(f"    ✓ Found {len(data['alliances'])} alliances with R5 history")
            print(f"    ✓ Current rules version: {data['currentRulesVersion']}")
            self.log_pass("R5 signature history data structure valid")
            return True

        except Exception as e:
            self.log_fail("R5 signature history data", str(e))
            return False

    def test_alliance_data(self):
        """Test 5: Alliance data integrity"""
        print("\n[Test 5] Alliance Data Integrity")
        self.tests_run += 1

        try:
            url = f"{PRODUCTION_URL}/data/alliances.json"
            response = requests.get(url, timeout=TIMEOUT)
            alliances = response.json()

            if not isinstance(alliances, list):
                self.log_fail("Alliance data type", "Expected array")
                return False

            if len(alliances) != 15:
                self.log_fail("Alliance count", f"Expected 15, got {len(alliances)}")
                return False

            # Check ranks are sequential
            ranks = [a['rank'] for a in alliances]
            expected_ranks = list(range(1, 16))
            if ranks != expected_ranks:
                self.log_fail("Alliance ranks", f"Ranks not sequential: {ranks}")
                return False

            # Check all have required fields
            for alliance in alliances:
                if 'tag' not in alliance or 'name' not in alliance or 'r5' not in alliance:
                    self.log_fail("Alliance fields", f"Missing required fields in {alliance.get('tag', 'unknown')}")
                    return False

            print(f"    ✓ All 15 alliances present with sequential ranks")
            print(f"    ✓ All alliances have required fields")
            self.log_pass("Alliance data integrity valid")
            return True

        except Exception as e:
            self.log_fail("Alliance data integrity", str(e))
            return False

    def test_javascript_loading(self):
        """Test 6: JavaScript files are accessible"""
        print("\n[Test 6] JavaScript Loading")

        js_files = [
            'js/app.js',
            'data/council.js'
        ]

        all_loaded = True
        for js_file in js_files:
            self.tests_run += 1
            url = f"{PRODUCTION_URL}/{js_file}"

            try:
                response = requests.get(url, timeout=TIMEOUT)
                if response.status_code == 200:
                    content = response.text
                    self.log_pass(f"{js_file} loaded ({len(content)} bytes)")
                else:
                    self.log_fail(f"{js_file} loading", f"HTTP {response.status_code}")
                    all_loaded = False
            except Exception as e:
                self.log_fail(f"{js_file} loading", str(e))
                all_loaded = False

        return all_loaded

    def test_css_loading(self):
        """Test 7: CSS files are accessible"""
        print("\n[Test 7] CSS Loading")
        self.tests_run += 1

        try:
            url = f"{PRODUCTION_URL}/css/styles.css"
            response = requests.get(url, timeout=TIMEOUT)

            if response.status_code == 200:
                css_content = response.text

                # Check for R5 history styles
                if '.r5-history-entry' in css_content:
                    print("    ✓ R5 history styles present")
                else:
                    print("    ⚠️  R5 history styles not found")

                # Check for modal styles
                if '.modal-overlay' in css_content:
                    print("    ✓ Modal styles present")
                else:
                    print("    ⚠️  Modal styles not found")

                self.log_pass(f"CSS loaded ({len(css_content)} bytes)")
                return True
            else:
                self.log_fail("CSS loading", f"HTTP {response.status_code}")
                return False

        except Exception as e:
            self.log_fail("CSS loading", str(e))
            return False

    def test_server_info_banner(self):
        """Test 8: Server Discord banner present"""
        print("\n[Test 8] Server Discord Banner")
        self.tests_run += 1

        try:
            response = requests.get(PRODUCTION_URL, timeout=TIMEOUT)
            html = response.text

            if 'server-discord-section' in html:
                print("    ✓ Server Discord section found in HTML")

                # Check for Discord data
                url = f"{PRODUCTION_URL}/data/server-info.json"
                response = requests.get(url, timeout=TIMEOUT)
                data = response.json()

                if 'discord' in data:
                    print(f"    ✓ Server info data present")
                    self.log_pass("Server Discord banner configured")
                    return True
                else:
                    self.log_fail("Server Discord data", "Missing discord field")
                    return False
            else:
                self.log_fail("Server Discord banner", "Section not found in HTML")
                return False

        except Exception as e:
            self.log_fail("Server Discord banner", str(e))
            return False

    def test_http_headers(self):
        """Test 9: HTTP headers and caching"""
        print("\n[Test 9] HTTP Headers")
        self.tests_run += 1

        try:
            response = requests.get(PRODUCTION_URL, timeout=TIMEOUT)
            headers = response.headers

            print(f"    Content-Type: {headers.get('content-type', 'N/A')}")
            print(f"    Cache-Control: {headers.get('cache-control', 'N/A')}")
            print(f"    Server: {headers.get('server', 'N/A')}")

            # Check content type
            if 'text/html' in headers.get('content-type', ''):
                print("    ✓ Correct content-type for HTML")
            else:
                print("    ⚠️  Unexpected content-type")

            self.log_pass("HTTP headers present")
            return True

        except Exception as e:
            self.log_fail("HTTP headers", str(e))
            return False

    def test_version_consistency(self):
        """Test 10: Check version numbers in HTML"""
        print("\n[Test 10] Version Consistency")
        self.tests_run += 1

        try:
            response = requests.get(PRODUCTION_URL, timeout=TIMEOUT)
            html = response.text

            # Look for version in HTML comments or meta tags
            version_pattern = r'v\d+\.\d+\.\d+'
            versions = re.findall(version_pattern, html)

            if versions:
                print(f"    ✓ Found versions: {set(versions)}")
                self.log_pass("Version information present")
                return True
            else:
                print("    ⚠️  No version information found")
                self.log_pass("Version test completed")
                return True

        except Exception as e:
            self.log_fail("Version consistency", str(e))
            return False

    def run_all_tests(self):
        """Run all test suites"""
        print("=" * 70)
        print("Server 1586 - Production Website Unit Tests")
        print("=" * 70)
        print(f"Target: {PRODUCTION_URL}")
        print(f"Started: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print("=" * 70)

        # Run all tests
        self.test_website_accessible()
        self.test_html_structure()
        self.test_json_data_loading()
        self.test_signature_history_data()
        self.test_alliance_data()
        self.test_javascript_loading()
        self.test_css_loading()
        self.test_server_info_banner()
        self.test_http_headers()
        self.test_version_consistency()

        # Print summary
        print("\n" + "=" * 70)
        print("Test Summary")
        print("=" * 70)
        print(f"Total Tests:   {self.tests_run}")
        print(f"Passed:        {self.tests_passed} ✓")
        print(f"Failed:        {self.tests_failed} ✗")
        print(f"Success Rate:  {(self.tests_passed/self.tests_run*100) if self.tests_run > 0 else 0:.1f}%")

        if self.tests_failed > 0:
            print("\nFailures:")
            for failure in self.failures:
                print(f"  ✗ {failure}")
            print("\n⚠️  Some tests failed - please review above")
            return False
        else:
            print("\n✓ All tests passed!")
            print(f"\nProduction website is fully operational at {PRODUCTION_URL}")
            return True

if __name__ == "__main__":
    tester = ProductionTester()
    success = tester.run_all_tests()
    sys.exit(0 if success else 1)
