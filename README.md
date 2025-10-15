# Server 1586 - Last War Alliance Website

[![Deploy to Production](https://github.com/username/your-repo/actions/workflows/deploy.yml/badge.svg)](https://github.com/username/your-repo/actions/workflows/deploy.yml)

Official website for Server 1586 alliance management, council voting, and server rules.

**Live Website**: [https://www.example.com](https://www.example.com)
**GitHub Repository**: [https://github.com/username/your-repo](https://github.com/username/your-repo)

**Version**: 2.0.0
**Last Updated**: October 14, 2025

> **Note**: The website automatically redirects HTTP to HTTPS and adds www. prefix for security and consistency.

---

## 📋 Table of Contents

- [Features](#features)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)
- [Deployment](#deployment)
- [Council Rotation System](#council-rotation-system)
- [Data Management](#data-management)
- [Development](#development)
- [Contributing](#contributing)

---

## ✨ Features

- **Alliance Rankings**: Display top 15 alliances with podium design for top 3
- **Council Voting System**: Rotating council members (ranks 6-15) with automatic weekly rotation
- **Server Rules**: Collapsible rules section with amendment history tracking
- **Timezone Support**: Displays rotation times in multiple timezones with automatic DST detection
- **Responsive Design**: Mobile-friendly interface with optimized layouts
- **Fair Rotation Algorithm**: Ensures all alliances get equal representation over time
- **Power Trends Chart**: Time-based alliance power visualization with accurate date spacing
- **Rotation Schedule Metadata**: Displays last-generated timestamp and change detection
- **Alliance Data Schema**: Comprehensive documentation for expandable alliance profiles

---

## 📁 Project Structure

```
Server1586/
├── index.html              # Main HTML page (v1.5.0)
├── css/
│   └── styles.css          # Main stylesheet (v1.3.2)
├── js/
│   └── app.js              # Main application logic (v1.9.3)
├── data/
│   ├── alliances.json      # Alliance data without rank fields (ranks calculated dynamically)
│   ├── rules.json          # Server rules
│   ├── amendments.json     # Rule amendment history
│   ├── rotation-schedule.json  # Pre-generated rotation schedule with metadata
│   ├── council.js          # Council utility functions (v2.0.0)
│   ├── power-history.csv   # Alliance power trends over time
│   ├── server-info.json    # Server Discord and metadata
│   ├── signature-history.json  # R5 leadership and signature tracking
│   └── ALLIANCE_SCHEMA.md  # Alliance data structure documentation
├── scripts/
│   ├── deploy-ftp.py       # FTP deployment script (local)
│   ├── deploy-ftp-ci.py    # FTP deployment script (CI/CD)
│   ├── validate-csv.py     # CSV format validator
│   ├── update-rotation-schedule.py  # Schedule generator (v2.2.0)
│   ├── merge-signature-history.py  # R5 history merger
│   ├── run-tests.py        # Unit test runner
│   ├── DEPLOY-README.md    # Deployment guide
│   └── README.md           # Scripts documentation
├── .github/
│   └── workflows/
│       └── deploy.yml      # GitHub Actions CI/CD workflow
├── images/                 # Screenshots and assets
├── .ftpignore             # FTP deployment exclusions
├── .gitignore             # Git exclusions
├── CICD-SETUP.md          # CI/CD setup guide
├── CLAUDE.md              # Developer documentation
└── README.md              # This file
```

---

## 🚀 Setup & Installation

### Prerequisites

- **Python 3.7+** (for deployment and schedule generation)
- **Web Server** (for local development - use `python -m http.server`)

### Local Development

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd Server1586
   ```

2. **Start local web server**:
   ```bash
   python -m http.server 8000
   ```

3. **Open in browser**:
   ```
   http://localhost:8000
   ```

### Install Python Dependencies

For deployment and schedule management:

```bash
pip install pywin32
```

---

## 🌐 Deployment

### Automated CI/CD Deployment (Recommended)

The website uses **GitHub Actions** for automated deployment. Every push to `mainline` triggers:
1. ✅ Unit tests validation
2. ✅ JSON/CSV format validation
3. 🚀 Automatic FTP deployment to production

**No manual deployment needed!** Just push to GitHub:

```bash
git add .
git commit -m "Your changes"
git push origin mainline
```

See [CICD-SETUP.md](CICD-SETUP.md) for complete CI/CD setup instructions.

### Manual Deployment (Local)

For emergency deployments or local testing:

```bash
python scripts/deploy-ftp.py
```

The deployment script automatically:
- ✅ Retrieves credentials from Windows Credential Manager (local) or environment variables (CI)
- ✅ Uploads only production files (respects `.ftpignore`)
- ✅ Creates remote directories as needed
- ✅ Shows deployment summary

See [scripts/DEPLOY-README.md](scripts/DEPLOY-README.md) for detailed manual deployment instructions.

---

## 🗳️ Council Rotation System

### How It Works

- **Permanent Members**: Top 5 alliances (ranks 1-5) have permanent council seats
- **Rotating Members**: 2 alliances from ranks 6-15 rotate weekly
- **Rotation Time**: Every Monday at 02:00 UTC (Sunday 10:00 PM EDT)
- **Fair Distribution**: Algorithm ensures all alliances rotate equally over 52 weeks

### Rotation Schedule

The rotation schedule is pre-generated and stored in `data/rotation-schedule.json`. It uses alliance tags (e.g., "STR8", "EPIC") instead of ranks, so it remains stable when rankings change.

#### Update Rotation Schedule

When alliance rankings change, regenerate the schedule:

```bash
python scripts/update-rotation-schedule.py
```

The script:
- Reads current top 15 alliances from `data/alliances.json`
- Preserves historical rotation data
- Generates next 52 weeks with fair distribution
- Looks back 10 weeks to ensure fairness
- Prevents back-to-back rotations (configurable minimum gap, default: 2 weeks)
- Handles new alliances gracefully (no catch-up bunching)

**Configuration**: Edit `MIN_WEEKS_BETWEEN_ROTATIONS` in the script to adjust the minimum gap between rotations for the same alliance (default: 2 = no consecutive weeks).

See [scripts/README.md](scripts/README.md) for detailed schedule management documentation.

---

## 📊 Data Management

### Alliance Data (`data/alliances.json`)

**v2.0.0 Breaking Change**: Rank fields have been removed. Ranks are now calculated dynamically based on power.

Update alliance information (ranks calculated automatically by power):

```json
[
  {
    "tag": "UvvU",
    "name": "veni vidi vici",
    "power": 7804360932,
    "r5": "R5 Name",
    "signed": true
  }
]
```

**Key Changes**:
- ❌ No more `"rank"` field in JSON
- ✅ Ranks calculated dynamically from `"power"` field
- ✅ Eliminates rank/power mismatches
- ✅ Single source of truth (power determines rank)

### Server Rules (`data/rules.json`)

Modify server rules:

```json
[
  {
    "category": "Category Name",
    "description": "Rule description",
    "items": ["Item 1", "Item 2"]
  }
]
```

### Rule Amendments (`data/amendments.json`)

Track rule changes:

```json
[
  {
    "date": "2025-10-05",
    "version": "1.2",
    "title": "Amendment Title",
    "changes": ["Change 1", "Change 2"]
  }
]
```

### After Data Updates

1. **Regenerate rotation schedule** (if alliances changed):
   ```bash
   python scripts/update-rotation-schedule.py
   ```

2. **Deploy to production**:
   ```bash
   python scripts/deploy-ftp.py
   ```

---

## 💻 Development

### Code Structure

- **HTML**: Single-page application in `index.html`
- **CSS**: Responsive design with mobile breakpoints in `css/styles.css`
- **JavaScript**: Vanilla JS, no frameworks/dependencies in `js/app.js`
- **Data**: JSON files for all dynamic content

### Version Control

The project uses semantic versioning:
- **Major** (X.0.0): Breaking changes or major redesigns
- **Minor** (1.X.0): New features or significant updates
- **Patch** (1.0.X): Bug fixes or minor improvements

Current versions:
- Website: **2.0.0**
- JavaScript: **2.0.0**
- CSS: **1.3.2**
- Council: **2.0.0**
- Schedule Script: **2.2.0**

### Key Technologies

- Pure HTML5, CSS3, JavaScript (ES5 for compatibility)
- No build process required
- No external dependencies
- Works with file:// protocol (with CORS limitations)

---

## 🤝 Contributing

### Making Changes

1. **Edit files locally** using any text editor
2. **Test locally** using `python -m http.server`
3. **Update version numbers** in affected files
4. **Update CHANGELOG** in file headers
5. **Commit to git** (excluded files: see `.gitignore`)
6. **Deploy to production** using `python scripts/deploy-ftp.py`

### Important Notes

- ⚠️ **Never commit credentials** - they're stored in Windows Credential Manager
- ⚠️ **Test before deployment** - verify locally first
- ⚠️ **Update version metadata** in `index.html` for tracking
- ✅ **Use `.ftpignore`** to exclude non-production files from deployment

---

## 📖 Additional Documentation

- **[CICD-SETUP.md](CICD-SETUP.md)** - GitHub Actions CI/CD setup guide
- **[CLAUDE.md](CLAUDE.md)** - Comprehensive developer documentation
- **[scripts/README.md](scripts/README.md)** - Schedule generation documentation
- **[scripts/DEPLOY-README.md](scripts/DEPLOY-README.md)** - Manual deployment guide

---

## 📄 License

This project is private and intended for Server 1586 alliance management only.

---

## 🔗 Links

- **Live Website**: [https://www.example.com](https://www.example.com)
- **GitHub Repository**: [https://github.com/username/your-repo](https://github.com/username/your-repo)
- **Server**: Last War - Server 1586

---

## 📞 Contact

For questions or issues, contact the server administrators.

---

**Last Updated**: October 14, 2025
**Maintained by**: Server 1586 Council
