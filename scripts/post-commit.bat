@echo off
REM Git Post-Commit Hook for Ollama Documentation Automation (Windows)
REM
REM Installation:
REM   1. Copy this file to .git\hooks\post-commit (no .bat extension)
REM   2. Or create a .git\hooks\post-commit file with this content
REM
REM To skip automation for a specific commit:
REM   set SKIP_OLLAMA=1 && git commit -m "message"

REM Run the Ollama documentation generator
php scripts\ollama-doc-generator.php post-commit

REM Exit with success (don't block commit even if script fails)
exit /b 0
