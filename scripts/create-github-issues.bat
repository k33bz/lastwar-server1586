@echo off
REM Create GitHub Issues Script (Windows)
REM
REM Automatically creates all issues defined in .github/ISSUES_TO_CREATE.md
REM Requires: GitHub CLI (gh) installed and authenticated
REM
REM Usage: scripts\create-github-issues.bat

setlocal enabledelayedexpansion

set REPO=k33bz/lastwar-server1586

echo Creating GitHub Issues for Server 1586...
echo Repository: %REPO%
echo.

REM Check if gh CLI is installed
where gh >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: GitHub CLI (gh) is not installed
    echo Install from: https://cli.github.com/
    pause
    exit /b 1
)

REM Check if authenticated
gh auth status >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: Not authenticated with GitHub CLI
    echo Run: gh auth login
    pause
    exit /b 1
)

echo Creating issues...
echo.

REM Note: For simplicity, this batch file creates issues with minimal bodies
REM For full issue creation with detailed bodies, use the bash script or create manually

echo Creating issue #1...
gh issue create --title "Delete consolidated documentation files" --body "Delete markdown files consolidated into docs/DEPLOYMENT.md and docs/CHANGELOG.md. See .github/ISSUES_TO_CREATE.md for details." --label "documentation,cleanup,good first issue" --repo %REPO%

echo Creating issue #2...
gh issue create --title "Update .ftpignore to exclude docs/ directory" --body "Exclude docs/ from FTP deployment. See .github/ISSUES_TO_CREATE.md for details." --label "deployment,configuration" --repo %REPO%

echo Creating issue #3...
gh issue create --title "Add version.json to deployment workflow verification" --body "Verify version.json is deployed and accessible. See .github/ISSUES_TO_CREATE.md for details." --label "deployment,versioning" --repo %REPO%

echo Creating issue #4...
gh issue create --title "Implement dashboard statistics caching" --body "Add 60-second cache for dashboard stats. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,performance,admin" --repo %REPO%

echo Creating issue #5...
gh issue create --title "Auto-generate alliance-count-history.json for trend tracking" --body "Automate alliance trend tracking. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,data,automation" --repo %REPO%

echo Creating issue #6...
gh issue create --title "Add email notifications for security events" --body "Implement email alerts for critical security events. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,security,admin" --repo %REPO%

echo Creating issue #7...
gh issue create --title "API rate limiting dashboard" --body "Create UI for monitoring rate limits. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,admin,monitoring" --repo %REPO%

echo Creating issue #8...
gh issue create --title "Real-time audit log updates (WebSocket/SSE)" --body "Add real-time updates to audit log viewer. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,admin,real-time" --repo %REPO%

echo Creating issue #9...
gh issue create --title "Add version badge to README.md" --body "Add dynamic version badge using shields.io. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,documentation" --repo %REPO%

echo Creating issue #10...
gh issue create --title "Create changelog RSS feed" --body "Allow users to subscribe to changelog via RSS. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,documentation" --repo %REPO%

echo Creating issue #11...
gh issue create --title "Add desktop notifications on version change" --body "Notify users when new version deployed. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,frontend" --repo %REPO%

echo Creating issue #12...
gh issue create --title "Create version comparison tool" --body "Tool to compare changes between versions. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,admin,tools" --repo %REPO%

echo Creating issue #13...
gh issue create --title "Automatic release notes generation" --body "Generate release notes from commits. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,automation,documentation" --repo %REPO%

echo Creating issue #14...
gh issue create --title "Replace all remaining alert() and confirm() calls with modals" --body "Replace browser alerts with modal dialogs. See .github/ISSUES_TO_CREATE.md for details." --label "bug,ui/ux,admin" --repo %REPO%

echo Creating issue #15...
gh issue create --title "Add loading states to all API calls" --body "Show loading indicators for operations. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,ui/ux,admin" --repo %REPO%

echo Creating issue #16...
gh issue create --title "Improve mobile responsiveness for admin panel" --body "Enhance mobile UX for admin panel. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,ui/ux,mobile,admin" --repo %REPO%

echo Creating issue #17...
gh issue create --title "Add form validation to all admin forms" --body "Implement client-side validation. See .github/ISSUES_TO_CREATE.md for details." --label "enhancement,validation,admin" --repo %REPO%

echo Creating issue #18...
gh issue create --title "Implement CSRF protection" --body "Add CSRF tokens to all forms. HIGH PRIORITY. See .github/ISSUES_TO_CREATE.md for details." --label "security,enhancement,admin" --repo %REPO%

echo Creating issue #19...
gh issue create --title "Add Content Security Policy (CSP) headers" --body "Implement CSP headers for XSS protection. See .github/ISSUES_TO_CREATE.md for details." --label "security,enhancement" --repo %REPO%

echo Creating issue #20...
gh issue create --title "Create video tutorials for common tasks" --body "Create short video/GIF tutorials. See .github/ISSUES_TO_CREATE.md for details." --label "documentation,tutorial,good first issue" --repo %REPO%

echo.
echo =========================================
echo Successfully created 20 GitHub issues!
echo =========================================
echo.
echo View issues at: https://github.com/%REPO%/issues
echo.
echo Next steps:
echo 1. Review and prioritize issues
echo 2. Assign labels and milestones
echo 3. Create project board for tracking
echo 4. Start with high-priority items
echo.
pause
