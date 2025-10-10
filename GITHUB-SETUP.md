# GitHub Setup Instructions

## Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com/new)
2. Fill in repository details:
   - **Repository name**: `Server1586` (or `lastwar-server1586`)
   - **Description**: `Server 1586 - Last War Alliance Website`
   - **Visibility**: Public or Private (your choice)
   - **Initialize repository**: Leave unchecked (we already have code)
3. Click "Create repository"

## Step 2: Add Remote and Push

After creating the repository, GitHub will show commands. Use these:

```bash
# Add the remote (replace USERNAME with your GitHub username)
git remote add origin https://github.com/USERNAME/Server1586.git

# Or if you prefer SSH:
# git remote add origin git@github.com:USERNAME/Server1586.git

# Verify remote was added
git remote -v

# Push to GitHub (first time)
git push -u origin mainline

# For subsequent pushes:
# git push
```

## Step 3: Verify Upload

1. Visit your repository on GitHub
2. Verify all files are present
3. Check that README.md displays correctly
4. Verify no sensitive data was uploaded (credentials, passwords)

## Alternative: Using GitHub CLI

If you have GitHub CLI installed:

```bash
# Login to GitHub
gh auth login

# Create repository and push
gh repo create Server1586 --public --source=. --remote=origin --push

# Or for private repository:
# gh repo create Server1586 --private --source=. --remote=origin --push
```

## What's Been Committed

✅ All website files (HTML, CSS, JS)
✅ Deployment scripts (FTP/SFTP)
✅ Rotation schedule algorithm
✅ Documentation (README, deployment guides)
✅ Alliance data and rules

❌ No credentials or passwords (stored in Windows Credential Manager)
❌ No sensitive configuration

## Repository Structure

```
Server1586/
├── index.html              # Main website (v1.4.2)
├── css/styles.css          # Styles (v1.4.1)
├── js/app.js               # Application logic (v1.4.2)
├── data/                   # Alliance data, rules, schedule
├── scripts/                # Deployment and rotation scripts
├── images/logos/           # Alliance logos
├── README.md               # Project documentation
├── DEPLOYMENT-HISTORY.md   # Version history
└── scripts/
    ├── deploy-ftp.py       # FTP deployment script
    ├── update-rotation-schedule.py  # Rotation algorithm (v2.2.0)
    └── DEPLOY-README.md    # Deployment guide
```

## Current Status

- **Latest Commit**: chore: Update Claude Code tool permissions
- **Commits**: 4 total
- **Branch**: mainline
- **Files**: 80+ files committed
- **No Remote**: Needs to be added (follow steps above)

## After Pushing

Once pushed to GitHub, update the README.md with the repository link:

```markdown
**GitHub Repository**: [https://github.com/USERNAME/Server1586](https://github.com/USERNAME/Server1586)
```

Then commit and push again:

```bash
git add README.md
git commit -m "docs: Add GitHub repository link"
git push
```
