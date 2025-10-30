# Markdown File Consolidation Script
# Organizes markdown files by moving temporary/outdated files to documentation-archive
# 
# Usage: .\scripts\consolidate-markdown.ps1
# Run from repository root directory

param(
    [switch]$DryRun = $false,
    [switch]$AutoCommit = $false
)

Write-Host "🔄 Markdown File Consolidation Script" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

if ($DryRun) {
    Write-Host "🔍 DRY RUN MODE - No files will be moved" -ForegroundColor Yellow
}

# Ensure we're in the repository root
if (!(Test-Path "README.md") -or !(Test-Path "scripts")) {
    Write-Host "❌ Error: Please run this script from the repository root directory" -ForegroundColor Red
    exit 1
}

# Create documentation archive folder if it doesn't exist
if (!(Test-Path "documentation-archive")) {
    if (!$DryRun) {
        New-Item -ItemType Directory -Path "documentation-archive" -Force | Out-Null
    }
    Write-Host "📁 Created documentation-archive folder" -ForegroundColor Yellow
}

# Files to keep in root (essential documentation)
$keepInRoot = @(
    "README.md",
    "DOCUMENTATION.md", 
    "CONTRIBUTORS.md"
)

# Patterns for files to archive
$archivePatterns = @(
    "review-*.md",
    "SESSION_SUMMARY_*.md", 
    "*_GUIDE.md",
    "*_STATUS_REPORT.md",
    "*_FIX.md",
    "JWT_*.md",
    "KEY_ROTATION_*.md",
    "OCR_*.md",
    "PRODUCTION-*.md",
    "CLAUDE.md",
    "FILE_AUDIT_SUMMARY.md",
    "ISSUE_COMPLETION_SUMMARY.md",
    "KIRO-GITHUB-TEST.md",
    "test-*.md"
)

Write-Host "📦 Scanning for files to archive..." -ForegroundColor Cyan

$movedFiles = @()
$totalFiles = 0

foreach ($pattern in $archivePatterns) {
    $files = Get-ChildItem -Path . -Name $pattern -ErrorAction SilentlyContinue
    foreach ($file in $files) {
        if (Test-Path $file -PathType Leaf) {
            $totalFiles++
            if ($DryRun) {
                Write-Host "  📄 Would move: $file" -ForegroundColor Gray
            } else {
                try {
                    Move-Item $file "documentation-archive/" -Force
                    Write-Host "  ✅ Moved: $file" -ForegroundColor Green
                    $movedFiles += $file
                }
                catch {
                    Write-Host "  ❌ Failed to move: $file - $($_.Exception.Message)" -ForegroundColor Red
                }
            }
        }
    }
}

if ($DryRun) {
    Write-Host "`n🔍 DRY RUN SUMMARY:" -ForegroundColor Yellow
    Write-Host "  - Files that would be moved: $totalFiles" -ForegroundColor White
    Write-Host "  - Run without -DryRun to execute" -ForegroundColor White
    exit 0
}

# Create archive index if files were moved
if ($movedFiles.Count -gt 0) {
    $archiveIndex = @"
# 📚 Documentation Archive

This folder contains markdown files that have been archived from the root directory.

## 📁 Archived Files ($($movedFiles.Count) files)

$(($movedFiles | Sort-Object | ForEach-Object { "- ``$_``" }) -join "`n")

## 📝 Archive Information

- **Created**: $(Get-Date -Format "MMMM dd, yyyy")
- **Total Files**: $($movedFiles.Count) markdown files
- **Purpose**: Repository organization and maintenance

## 🔗 Current Documentation

Essential documentation remains in the root:
- **README.md** - Main project overview
- **DOCUMENTATION.md** - Complete documentation index  
- **CONTRIBUTORS.md** - Project contributors

---

*Files archived to reduce root directory clutter while preserving historical information.*
"@

    Set-Content -Path "documentation-archive/README.md" -Value $archiveIndex -Encoding UTF8
    Write-Host "📝 Created documentation-archive index" -ForegroundColor Green
}

# Show results
Write-Host "`n📊 CONSOLIDATION RESULTS:" -ForegroundColor Cyan
Write-Host "  - Files moved: $($movedFiles.Count)" -ForegroundColor Green
Write-Host "  - Root markdown files remaining: $((Get-ChildItem -Path . -Name '*.md').Count)" -ForegroundColor White

Write-Host "`n📋 Remaining root markdown files:" -ForegroundColor Yellow
Get-ChildItem -Path . -Name "*.md" | ForEach-Object {
    Write-Host "  📄 $_" -ForegroundColor White
}

if ($movedFiles.Count -gt 0) {
    Write-Host "`n📦 Archived files:" -ForegroundColor Yellow
    $movedFiles | Sort-Object | ForEach-Object {
        Write-Host "  📄 $_" -ForegroundColor Gray
    }
}

# Commit changes if requested
if ($AutoCommit -and $movedFiles.Count -gt 0) {
    Write-Host "`n📤 Auto-committing changes..." -ForegroundColor Green
    
    git add documentation-archive/
    git add .gitignore
    
    $commitMessage = @"
📚 Consolidate markdown documentation

- Moved $($movedFiles.Count) temporary/outdated markdown files to documentation-archive/
- Kept essential documentation in root: README.md, DOCUMENTATION.md, CONTRIBUTORS.md
- Created archive index for reference
- Updated .gitignore for temp/ folder

Archived files:
$(($movedFiles | Sort-Object | ForEach-Object { "- $_" }) -join "`n")
"@
    
    git commit -m $commitMessage
    Write-Host "✅ Changes committed!" -ForegroundColor Green
} elseif ($movedFiles.Count -gt 0) {
    Write-Host "`n💡 To commit these changes, run:" -ForegroundColor Yellow
    Write-Host "   git add documentation-archive/ .gitignore" -ForegroundColor White
    Write-Host "   git commit -m 'Consolidate markdown documentation'" -ForegroundColor White
}

Write-Host "`n✅ Markdown consolidation complete!" -ForegroundColor Green