# setup-admin.ps1
$basePath = "C:\Users\k33bz\OneDrive\git\Server1586\admin"
$dataPath = "$basePath\..\data"

# Ensure directories exist
New-Item -ItemType Directory -Force -Path $basePath | Out-Null
New-Item -ItemType Directory -Force -Path $dataPath | Out-Null
New-Item -ItemType Directory -Force -Path "$basePath\vendor" | Out-Null

# Run modular scripts
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
& "$scriptDir\create-config.ps1" $basePath
& "$scriptDir\create-jwt.ps1" $basePath
& "$scriptDir\create-env.ps1" $basePath
& "$scriptDir\create-users.ps1" $basePath
& "$scriptDir\create-blacklist.ps1" $basePath
& "$scriptDir\create-alliances.ps1" $dataPath
& "$scriptDir\create-mailer.ps1" $basePath
& "$scriptDir\create-login.ps1" $basePath
& "$scriptDir\create-logout.ps1" $basePath
& "$scriptDir\create-cron.ps1" $basePath

Write-Host "✅ All admin files created at $basePath"