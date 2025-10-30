#!/usr/bin/env python3
"""
Repository Review Tool - LM Studio Edition

Uses LM Studio (qwen3-coder-30b) to perform comprehensive repository analysis:
- Architecture review
- Code quality assessment
- Security audit
- Documentation gaps
- Improvement suggestions

Features:
- Intelligent polling with exponential backoff for queued requests
- Auto-save results (no interactive prompts)
- Handles multiple parallel requests gracefully
- Windows-safe (no emoji encoding issues)
- **NEW**: LM Studio log monitoring for real-time status detection
- **NEW**: Performance stats extraction from logs
- **NEW**: Completion detection even after client timeout

How Log Monitoring Works:
1. Monitors ~/.lmstudio/server-logs/ for request lifecycle events
2. Detects when model is processing vs completed
3. Retrieves results even if HTTP client times out
4. Shows real-time performance stats (tokens/sec, eval time)

Usage:
  python repo-review.py [mode]

Modes:
  overview       - High-level architecture and structure review (default)
  security       - Security audit and vulnerability assessment
  quality        - Code quality and best practices review
  docs           - Documentation completeness review
  improvements   - Suggestions for improvements and refactoring
  custom         - Custom prompt (interactive)

Examples:
  python repo-review.py overview
  python repo-review.py security
  python repo-review.py custom

Configuration:
  MAX_POLL_TIME: 600s (10 minutes) - Maximum time to wait for response
  POLL_INTERVAL_START: 5s - Initial retry interval
  POLL_INTERVAL_MAX: 30s - Maximum retry interval
  LMSTUDIO_LOG_DIR: ~/.lmstudio/server-logs

Version: 3.0.0
Date: 2025-10-30
Changelog:
  v3.0.0 - Added LM Studio log monitoring for completion detection and performance stats
  v2.0.0 - Intelligent polling with exponential backoff, auto-save results
  v1.0.0 - Initial release with timeout-based requests
"""

import os
import sys
import json
import subprocess
from pathlib import Path
from typing import Dict, List
import urllib.request
import urllib.error

# Fix Windows console encoding
if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Configuration
LMSTUDIO_URL = 'http://localhost:1234/v1'
LMSTUDIO_MODEL = 'qwen/qwen3-coder-30b'
TEMPERATURE = 0.3
MAX_TOKENS = 4000  # Larger for comprehensive reviews
INITIAL_TIMEOUT = 30  # Initial request timeout (seconds)
MAX_POLL_TIME = 600  # Maximum total polling time (10 minutes)
POLL_INTERVAL_START = 5  # Start polling every 5 seconds
POLL_INTERVAL_MAX = 30  # Max polling interval

# LM Studio log monitoring
LMSTUDIO_LOG_DIR = Path.home() / '.lmstudio' / 'server-logs'


def check_lmstudio_running(url: str) -> bool:
    """Check if LM Studio is running"""
    try:
        req = urllib.request.Request(f"{url}/models")
        with urllib.request.urlopen(req, timeout=2) as response:
            return response.status == 200
    except:
        return False


def get_lmstudio_log_file() -> Path:
    """Get path to today's LM Studio server log"""
    from datetime import datetime
    today = datetime.now()
    year_month = today.strftime('%Y-%m')
    log_file = today.strftime('%Y-%m-%d.1.log')

    log_path = LMSTUDIO_LOG_DIR / year_month / log_file
    return log_path if log_path.exists() else None


def monitor_lmstudio_logs(start_time: float, request_id: str = None) -> dict:
    """
    Monitor LM Studio logs to detect request completion.

    Returns:
        dict with keys: completed (bool), in_progress (bool), stats (dict)
    """
    log_file = get_lmstudio_log_file()
    if not log_file or not log_file.exists():
        return {'completed': False, 'in_progress': False, 'stats': None, 'error': 'Log file not found'}

    try:
        # Read last 500 lines (roughly last few minutes of activity)
        with open(log_file, 'r', encoding='utf-8', errors='ignore') as f:
            # Seek to end and read backwards
            f.seek(0, 2)  # End of file
            file_size = f.tell()

            # Read last ~50KB (roughly 500-1000 lines)
            read_size = min(50000, file_size)
            f.seek(max(0, file_size - read_size))
            recent_logs = f.read()

        lines = recent_logs.split('\n')

        # Parse log events
        received_requests = []
        begin_processing = []
        finished_processing = []
        generated_predictions = []
        client_disconnects = []
        perf_stats = []

        from datetime import datetime
        for line in lines:
            # Extract timestamp if present
            if line.startswith('['):
                try:
                    ts_end = line.index(']', 1)
                    timestamp_str = line[1:ts_end]
                    ts = datetime.strptime(timestamp_str, '%Y-%m-%d %H:%M:%S')

                    # Only look at events after our request started
                    if ts.timestamp() < start_time:
                        continue

                    if 'Received request: POST to /v1/chat/completions' in line:
                        received_requests.append(ts)
                    elif 'BeginProcessingPrompt' in line:
                        begin_processing.append(ts)
                    elif 'FinishedProcessingPrompt. Progress: 100' in line:
                        finished_processing.append(ts)
                    elif 'Generated prediction:' in line:
                        generated_predictions.append(ts)
                    elif 'Client disconnected. Stopping generation...' in line:
                        client_disconnects.append(ts)
                    elif 'eval time =' in line:
                        # Parse performance stats
                        perf_stats.append(line)

                except (ValueError, IndexError):
                    continue

        # Determine status
        in_progress = len(begin_processing) > len(generated_predictions)
        completed = len(generated_predictions) > 0

        # Parse latest performance stats if available
        stats = None
        if perf_stats:
            last_stat = perf_stats[-1]
            try:
                # Extract eval time: "eval time = 59399.42 ms / 924 runs"
                import re
                match = re.search(r'eval time\s*=\s*(\d+\.\d+)\s*ms\s*/\s*(\d+)\s*runs', last_stat)
                if match:
                    eval_time_ms = float(match.group(1))
                    eval_runs = int(match.group(2))
                    stats = {
                        'eval_time_ms': eval_time_ms,
                        'eval_runs': eval_runs,
                        'eval_time_sec': eval_time_ms / 1000,
                        'tokens_per_sec': eval_runs / (eval_time_ms / 1000) if eval_time_ms > 0 else 0
                    }
            except:
                pass

        return {
            'completed': completed,
            'in_progress': in_progress,
            'stats': stats,
            'events': {
                'received': len(received_requests),
                'begin_processing': len(begin_processing),
                'finished_processing': len(finished_processing),
                'generated': len(generated_predictions),
                'disconnected': len(client_disconnects)
            }
        }

    except Exception as e:
        return {'completed': False, 'in_progress': False, 'stats': None, 'error': str(e)}


def check_lmstudio_queue() -> dict:
    """Check LM Studio queue status (if available)"""
    try:
        req = urllib.request.Request(f"{LMSTUDIO_URL}/models")
        with urllib.request.urlopen(req, timeout=2) as response:
            data = json.loads(response.read().decode('utf-8'))
            # LM Studio doesn't expose queue info, but we can check if it's responsive
            return {'responsive': True, 'data': data}
    except:
        return {'responsive': False}


def query_lmstudio_with_polling(prompt: str, max_tokens: int = MAX_TOKENS) -> str:
    """
    Send prompt to LM Studio with intelligent polling.

    Uses exponential backoff to handle queued requests gracefully.
    """
    import time
    import socket

    data = {
        'model': LMSTUDIO_MODEL,
        'messages': [
            {
                'role': 'system',
                'content': 'You are an expert code reviewer and software architect with deep knowledge of PHP, JavaScript, Python, security best practices, and web application architecture.'
            },
            {
                'role': 'user',
                'content': prompt
            }
        ],
        'temperature': TEMPERATURE,
        'max_tokens': max_tokens,
        'stream': False
    }

    start_time = time.time()
    request_start_time = start_time
    poll_interval = POLL_INTERVAL_START
    attempt = 0
    client_disconnected = False
    last_log_check = 0

    print(f"   >> Sending request to LM Studio...")

    while True:
        elapsed = time.time() - start_time

        if elapsed > MAX_POLL_TIME:
            raise Exception(f"Request timed out after {MAX_POLL_TIME}s (LM Studio may be overloaded)")

        attempt += 1

        try:
            req = urllib.request.Request(
                f"{LMSTUDIO_URL}/chat/completions",
                data=json.dumps(data).encode('utf-8'),
                headers={'Content-Type': 'application/json'}
            )

            # Try with current timeout
            timeout = min(INITIAL_TIMEOUT + (attempt * 10), 120)

            if client_disconnected:
                # After client disconnect, check logs instead of retrying HTTP
                print(f"   [LOG MONITOR] Checking LM Studio logs for completion...")
            else:
                print(f"   [Attempt {attempt}] Waiting up to {timeout}s... (elapsed: {elapsed:.0f}s)")

            if not client_disconnected:
                with urllib.request.urlopen(req, timeout=timeout) as response:
                    if response.status != 200:
                        raise Exception(f"LM Studio request failed with HTTP {response.status}")

                    result = json.loads(response.read().decode('utf-8'))
                    if 'choices' not in result or len(result['choices']) == 0:
                        raise Exception("Invalid LM Studio response")

                    print(f"   [SUCCESS] Response received after {elapsed:.1f}s")
                    return result['choices'][0]['message']['content']
            else:
                # In log monitoring mode - check logs periodically
                if time.time() - last_log_check >= poll_interval:
                    log_status = monitor_lmstudio_logs(request_start_time)

                    if log_status.get('completed'):
                        # Generation complete! Try to retrieve result
                        print(f"   [LOG DETECT] Generation complete! Attempting to retrieve...")
                        try:
                            # Make quick request to get the cached result
                            with urllib.request.urlopen(req, timeout=10) as response:
                                result = json.loads(response.read().decode('utf-8'))
                                if 'choices' in result and len(result['choices']) > 0:
                                    stats = log_status.get('stats')
                                    if stats:
                                        print(f"   [STATS] Total: {stats['eval_time_sec']:.1f}s @ {stats['tokens_per_sec']:.1f} tok/s")
                                    print(f"   [SUCCESS] Retrieved result after {elapsed:.1f}s total")
                                    return result['choices'][0]['message']['content']
                        except:
                            pass  # Failed to retrieve, keep monitoring

                    elif log_status.get('in_progress'):
                        events = log_status.get('events', {})
                        print(f"   [LOG MONITOR] Still processing... ({elapsed:.0f}s elapsed)")

                    last_log_check = time.time()

                # Wait before next check
                time.sleep(min(poll_interval, 10))
                continue

        except socket.timeout:
            # Request timed out, check if LM Studio is still processing via logs
            log_status = monitor_lmstudio_logs(request_start_time)

            if log_status.get('completed'):
                # Model completed! But we timed out. Try one more request to get result.
                print(f"   [LOG DETECT] LM Studio completed processing! Retrieving result...")
                stats = log_status.get('stats')
                if stats:
                    print(f"   [STATS] Processed in {stats['eval_time_sec']:.1f}s ({stats['tokens_per_sec']:.1f} tok/s)")
                # Fall through to retry to get the actual response

            elif log_status.get('in_progress'):
                events = log_status.get('events', {})
                print(f"   [LOG DETECT] LM Studio still processing (events: {events.get('begin_processing', 0)} began, {events.get('generated', 0)} completed)")
                client_disconnected = True  # Switch to log monitoring mode

            print(f"   [TIMEOUT] After {timeout}s - monitoring logs, waiting {poll_interval:.0f}s...")
            time.sleep(poll_interval)
            poll_interval = min(poll_interval * 1.5, POLL_INTERVAL_MAX)
            last_log_check = time.time()
            continue

        except urllib.error.URLError as e:
            if 'timed out' in str(e).lower():
                # Check logs before retrying
                log_status = monitor_lmstudio_logs(request_start_time)
                if log_status.get('completed'):
                    print(f"   [LOG DETECT] Processing complete! Fetching result...")

                print(f"   [TIMEOUT] Connection timeout - LM Studio busy, waiting {poll_interval:.0f}s...")
                time.sleep(poll_interval)
                poll_interval = min(poll_interval * 1.5, POLL_INTERVAL_MAX)
                continue
            else:
                raise Exception(f"Failed to connect to LM Studio: {e}")

        except Exception as e:
            if 'timed out' in str(e).lower():
                print(f"   [TIMEOUT] Request timeout - waiting {poll_interval:.0f}s before retry...")
                time.sleep(poll_interval)
                poll_interval = min(poll_interval * 1.5, POLL_INTERVAL_MAX)
                continue
            else:
                raise Exception(f"LM Studio request failed: {e}")


# Alias for backward compatibility
def query_lmstudio(prompt: str, max_tokens: int = MAX_TOKENS) -> str:
    """Send prompt to LM Studio (uses polling under the hood)"""
    return query_lmstudio_with_polling(prompt, max_tokens)


def get_repo_structure() -> str:
    """Get repository structure using git ls-files"""
    try:
        # Get all tracked files
        files = subprocess.check_output(
            ['git', 'ls-files'],
            encoding='utf-8'
        ).strip().split('\n')

        # Organize by directory
        structure = {}
        for file in files:
            if not file:
                continue
            parts = file.split('/')
            if len(parts) == 1:
                structure[file] = None
            else:
                dir_name = parts[0]
                if dir_name not in structure:
                    structure[dir_name] = []
                if isinstance(structure[dir_name], list):
                    structure[dir_name].append('/'.join(parts[1:]))

        # Format as tree
        lines = []
        for key in sorted(structure.keys()):
            if structure[key] is None:
                lines.append(f"📄 {key}")
            else:
                lines.append(f"📁 {key}/ ({len(structure[key])} files)")
                # Show first few files in each directory
                for file in sorted(structure[key])[:5]:
                    lines.append(f"   └─ {file}")
                if len(structure[key]) > 5:
                    lines.append(f"   └─ ... and {len(structure[key]) - 5} more")

        return '\n'.join(lines)

    except subprocess.CalledProcessError as e:
        raise Exception(f"Failed to get repo structure: {e}")


def get_file_stats() -> Dict:
    """Get statistics about files in repo"""
    try:
        files = subprocess.check_output(
            ['git', 'ls-files'],
            encoding='utf-8'
        ).strip().split('\n')

        stats = {
            'total_files': len(files),
            'by_extension': {},
            'by_directory': {}
        }

        for file in files:
            if not file:
                continue

            # Count by extension
            ext = Path(file).suffix or 'no-extension'
            stats['by_extension'][ext] = stats['by_extension'].get(ext, 0) + 1

            # Count by directory
            dir_name = str(Path(file).parent) if '/' in file or '\\' in file else 'root'
            stats['by_directory'][dir_name] = stats['by_directory'].get(dir_name, 0) + 1

        return stats

    except subprocess.CalledProcessError as e:
        raise Exception(f"Failed to get file stats: {e}")


def read_key_files() -> Dict[str, str]:
    """Read key repository files for context"""
    files_to_read = [
        'README.md',
        'CLAUDE.md',
        'version.json',
        'package.json',
        'composer.json',
        '.gitignore'
    ]

    content = {}
    for file in files_to_read:
        file_path = Path(file)
        if file_path.exists():
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    # Limit to first 2000 chars to avoid token overflow
                    content[file] = f.read(2000)
            except:
                pass

    return content


def build_overview_prompt() -> str:
    """Build prompt for overview review"""
    structure = get_repo_structure()
    stats = get_file_stats()
    key_files = read_key_files()

    # Get recent commits
    try:
        recent_commits = subprocess.check_output(
            ['git', 'log', '--oneline', '-10'],
            encoding='utf-8'
        ).strip()
    except:
        recent_commits = "Unable to retrieve commit history"

    prompt = f"""I need a comprehensive architecture and code review of this repository.

**Project:** Server 1586 - Last War Alliance Management Website

**Repository Structure:**
{structure}

**File Statistics:**
- Total files: {stats['total_files']}
- By extension: {', '.join(f'{k}({v})' for k, v in sorted(stats['by_extension'].items(), key=lambda x: -x[1])[:10])}
- Main directories: {', '.join(f'{k}({v})' for k, v in sorted(stats['by_directory'].items(), key=lambda x: -x[1])[:8])}

**Key Files Content:**
"""

    for filename, content in key_files.items():
        prompt += f"\n### {filename}:\n```\n{content[:1000]}\n```\n"

    prompt += f"""
**Recent Activity (Last 10 commits):**
{recent_commits}

**Review Request:**

Please provide a comprehensive review covering:

1. **Architecture Assessment**
   - Overall structure and organization
   - Separation of concerns (frontend, backend, data)
   - API design and patterns

2. **Technology Stack**
   - Frontend: HTML/CSS/JavaScript analysis
   - Backend: PHP admin panel assessment
   - Data management: JSON files vs database considerations

3. **Code Quality**
   - Best practices adherence
   - Maintainability concerns
   - Technical debt indicators

4. **Security Posture**
   - Authentication/authorization patterns
   - Data validation and sanitization
   - Common vulnerability risks

5. **Documentation Quality**
   - README completeness
   - Code documentation
   - API documentation

6. **Scalability & Performance**
   - Current limitations
   - Growth considerations
   - Optimization opportunities

7. **Key Strengths**
   - What's done well
   - Notable positive patterns

8. **Priority Improvements**
   - Top 5 recommendations
   - Quick wins vs long-term refactoring

Please be specific with file/directory references and provide actionable recommendations.
"""

    return prompt


def build_security_prompt() -> str:
    """Build prompt for security audit"""
    structure = get_repo_structure()

    # Get key security-related files
    security_files = []
    try:
        files = subprocess.check_output(
            ['git', 'ls-files'],
            encoding='utf-8'
        ).strip().split('\n')

        patterns = ['.env', 'auth', 'login', 'jwt', 'security', 'api']
        for file in files:
            if any(p in file.lower() for p in patterns):
                security_files.append(file)
    except:
        pass

    prompt = f"""Perform a comprehensive security audit of this repository.

**Project:** Server 1586 - Last War Alliance Website (PHP backend + static frontend)

**Repository Structure:**
{structure}

**Security-Critical Files Found:**
{chr(10).join(f'- {f}' for f in security_files[:20])}

**Audit Focus Areas:**

1. **Authentication & Authorization**
   - JWT implementation review
   - Session management
   - Password/credential handling
   - MFA implementation
   - Token rotation and expiration

2. **Input Validation & Sanitization**
   - User input handling
   - SQL injection risks (if applicable)
   - XSS vulnerabilities
   - CSRF protection
   - File upload security

3. **API Security**
   - Public API endpoint security
   - CORS configuration
   - Rate limiting
   - API key/token management

4. **Data Protection**
   - Sensitive data handling
   - PII protection
   - Encryption at rest
   - Encryption in transit
   - Backup security

5. **Configuration Security**
   - Environment variables
   - Secret management
   - .env file handling
   - Default credentials

6. **Access Control**
   - Role-based permissions (Admin, R5, R4, APE)
   - File permission issues
   - Directory traversal risks

7. **Third-Party Dependencies**
   - Composer packages (PHP)
   - Outdated dependencies
   - Known vulnerabilities

8. **Deployment Security**
   - .ftpignore effectiveness
   - Production file exclusions
   - Git secrets exposure

Please provide:
- Specific vulnerabilities found (with file:line references)
- Severity ratings (Critical/High/Medium/Low)
- Concrete remediation steps
- Security best practice recommendations

Be thorough and assume this is a production application.
"""

    return prompt


def build_quality_prompt() -> str:
    """Build prompt for code quality review"""
    stats = get_file_stats()

    prompt = f"""Perform a code quality and best practices review of this repository.

**Project:** Server 1586 - Multi-language web application (PHP, JavaScript, Python)

**File Distribution:**
{json.dumps(stats['by_extension'], indent=2)}

**Quality Review Areas:**

1. **Code Organization**
   - File structure and naming
   - Module/component organization
   - Separation of concerns
   - Duplication detection

2. **Coding Standards**
   - PHP: PSR compliance, type hints, error handling
   - JavaScript: ES6+ usage, modern patterns, async/await
   - Python: PEP 8, type hints, docstrings
   - Consistency across codebase

3. **Error Handling**
   - Exception handling patterns
   - Logging practices
   - User-facing error messages
   - Graceful degradation

4. **Testing**
   - Test coverage
   - Test quality
   - CI/CD pipeline
   - Testing gaps

5. **Performance**
   - Database query patterns (if applicable)
   - Caching strategies
   - Frontend optimization
   - API response times

6. **Maintainability**
   - Code complexity
   - Function/method length
   - Comment quality
   - Technical debt indicators

7. **Documentation**
   - Inline comments
   - Function/method documentation
   - README completeness
   - API documentation

8. **Version Control**
   - Commit message quality
   - Branch strategy
   - .gitignore completeness

Please provide:
- Specific code quality issues (with file references)
- Priority ratings
- Refactoring recommendations
- Quick wins for improvement
- Long-term quality goals
"""

    return prompt


def build_docs_prompt() -> str:
    """Build prompt for documentation review"""
    try:
        md_files = subprocess.check_output(
            ['git', 'ls-files', '*.md'],
            encoding='utf-8'
        ).strip().split('\n')
    except:
        md_files = []

    prompt = f"""Review the documentation completeness and quality of this repository.

**Project:** Server 1586 - Alliance Management Website

**Documentation Files Found ({len(md_files)}):**
{chr(10).join(f'- {f}' for f in md_files)}

**Documentation Review:**

1. **README Quality**
   - Getting started instructions
   - Installation steps
   - Usage examples
   - Contributing guidelines
   - License information

2. **Technical Documentation**
   - Architecture documentation
   - API documentation
   - Database schema (if applicable)
   - Deployment guides

3. **Developer Documentation**
   - Setup instructions
   - Development workflow
   - Testing procedures
   - Debugging tips

4. **User Documentation**
   - Feature documentation
   - Configuration guides
   - Troubleshooting
   - FAQ

5. **Code Documentation**
   - Inline comments quality
   - Function/method documentation
   - Complex logic explanation
   - TODOs and FIXMEs

6. **Changelog & Versioning**
   - Changelog completeness
   - Version tracking
   - Release notes

7. **Documentation Gaps**
   - Missing documentation
   - Outdated documentation
   - Incomplete sections

8. **Documentation Improvements**
   - Better examples needed
   - Diagram/visualization needs
   - Organization improvements

Please provide:
- Documentation coverage assessment
- Priority gaps to fill
- Specific improvement recommendations
- Documentation structure suggestions
"""

    return prompt


def build_improvements_prompt() -> str:
    """Build prompt for improvement suggestions"""
    structure = get_repo_structure()
    stats = get_file_stats()

    prompt = f"""Analyze this repository and provide actionable improvement recommendations.

**Project:** Server 1586 - Full-stack web application

**Current State:**
{structure}

**Stats:** {stats['total_files']} files across {len(stats['by_directory'])} directories

**Improvement Analysis:**

1. **Architecture Improvements**
   - Structural refactoring opportunities
   - Design pattern applications
   - Modularity enhancements

2. **Technology Upgrades**
   - Framework/library updates
   - Modern alternatives
   - Deprecation concerns

3. **Performance Optimizations**
   - Database optimization (if applicable)
   - Caching strategies
   - Frontend optimization
   - API performance

4. **Developer Experience**
   - Build process improvements
   - Development tools
   - Debugging capabilities
   - Testing infrastructure

5. **User Experience**
   - Frontend improvements
   - Mobile responsiveness
   - Accessibility
   - Load times

6. **Maintainability**
   - Code complexity reduction
   - Duplication elimination
   - Technical debt reduction

7. **Automation Opportunities**
   - CI/CD improvements
   - Testing automation
   - Deployment automation
   - Documentation generation

8. **Future-Proofing**
   - Scalability preparations
   - Migration paths
   - Technology trends

For each recommendation, provide:
- Current issue/limitation
- Proposed solution
- Effort estimate (Low/Medium/High)
- Priority (Critical/High/Medium/Low)
- Expected benefits
- Implementation approach

Focus on practical, achievable improvements.
"""

    return prompt


def print_section(title: str):
    """Print section header"""
    print(f"\n{'='*80}")
    print(f"  {title}")
    print(f"{'='*80}\n")


def main():
    mode = sys.argv[1] if len(sys.argv) > 1 else 'overview'

    print_section("🔍 Repository Review Tool - LM Studio Edition")

    # Check LM Studio
    if not check_lmstudio_running(LMSTUDIO_URL):
        print("❌ LM Studio is not running!")
        print("   Please open LM Studio and start the local server")
        print("   Model: qwen/qwen3-coder-30b")
        sys.exit(1)

    print(f"✅ LM Studio detected ({LMSTUDIO_MODEL})")
    print(f"📂 Repository: {Path.cwd().name}")
    print(f"🎯 Review Mode: {mode}")

    # Build prompt based on mode
    print_section(f"🤖 Generating {mode.upper()} Review Prompt")

    if mode == 'overview':
        prompt = build_overview_prompt()
        print("   Building comprehensive architecture review...")
    elif mode == 'security':
        prompt = build_security_prompt()
        print("   Building security audit...")
    elif mode == 'quality':
        prompt = build_quality_prompt()
        print("   Building code quality review...")
    elif mode == 'docs':
        prompt = build_docs_prompt()
        print("   Building documentation review...")
    elif mode == 'improvements':
        prompt = build_improvements_prompt()
        print("   Building improvement recommendations...")
    elif mode == 'custom':
        print("   Enter your custom review prompt (end with Ctrl+Z on Windows, Ctrl+D on Unix):")
        prompt = sys.stdin.read()
    else:
        print(f"❌ Unknown mode: {mode}")
        print("   Valid modes: overview, security, quality, docs, improvements, custom")
        sys.exit(1)

    print(f"   Prompt size: {len(prompt)} characters")

    # Query LM Studio
    print_section(f"⚡ Querying LM Studio")
    print(f"   This may take 30-120 seconds for comprehensive analysis...")
    print()

    try:
        import time
        start = time.time()
        response = query_lmstudio(prompt)
        duration = time.time() - start

        print_section(f"📊 Review Results ({duration:.1f}s)")
        print(response)
        print()

        # Auto-save results
        print_section("💾 Saving Results")
        filename = f"review-{mode}-{time.strftime('%Y%m%d-%H%M%S')}.md"
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(f"# Repository Review - {mode.title()}\n\n")
            f.write(f"**Date:** {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"**Model:** {LMSTUDIO_MODEL}\n")
            f.write(f"**Duration:** {duration:.1f}s\n\n")
            f.write("---\n\n")
            f.write(response)
        print(f"   ✅ Saved to: {filename}")

    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)

    print_section("✅ Review Complete")


if __name__ == '__main__':
    main()
