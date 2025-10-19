@echo off
echo Setting up Kiro AI as Git contributor...

git config user.name "Kiro AI Assistant"
git config user.email "kiro-ai@lastwar-server1586.dev"

echo Kiro Git configuration complete!
echo.
echo Current Git config:
git config --get user.name
git config --get user.email

pause