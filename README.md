# Server 1586 - Last War Alliance Website

[![Deploy to Production](https://github.com/username/your-repo/actions/workflows/deploy.yml/badge.svg)](https://github.com/username/your-repo/actions/workflows/deploy.yml)

Official website for Server 1586 alliance management, council voting, and server rules.

**Live Website**: [https://www.example.com](https://www.example.com)
**GitHub Repository**: [https://github.com/username/your-repo](https://github.com/username/your-repo)

**Version**: 3.0.0
**Last Updated**: October 16, 2025

> **Note**: The website automatically redirects HTTP to HTTPS and adds www. prefix for security and consistency.

---

## 📋 Table of Contents

- [Features](#features)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [Documentation Index](#documentation-index)
- [Setup & Installation](#setup--installation)
- [Deployment](#deployment)
- [Council Rotation System](#council-rotation-system)
- [Data Management](#data-management)
- [Development](#development)
- [Contributing](#contributing)

---

## ✨ Features

### Public Website
- **Alliance Rankings**: Display top 15 alliances with podium design for top 3
- **Council Voting System**: Rotating council members (ranks 6-15) with automatic weekly rotation
- **Server Rules**: Collapsible rules section with amendment history tracking
- **Timezone Support**: Displays rotation times in multiple timezones with automatic DST detection
- **Responsive Design**: Mobile-friendly interface with optimized layouts
- **Fair Rotation Algorithm**: Ensures all alliances get equal representation over time
- **Power Trends Chart**: Time-based alliance power visualization with accurate date spacing
- **Alliance Data Schema**: Comprehensive documentation for expandable alliance profiles

### Admin Panel (v3.0.0)
- **JWT Authentication**: Passwordless magic link login system
- **Role-Based Access**: Admin, R5, R4, and Power Editor (APE) roles
- **Multi-Factor Authentication**: TOTP support with backup codes
- **Secret Key Rotation**: Automatic 30-day JWT key rotation with emergency rotation
- **Security Monitoring**: Real-time threat detection and IP blocking
- **Audit Logging**: Comprehensive security event tracking with real-time viewer
- **Backup & Restore**: Automatic backups with point-in-time recovery
- **Email Masking**: PII protection for user data
- **Alliance Management**: Full CRUD operations for alliance data

---

## 📁 Project Structure

```
Server1586/
├── index.html              # Main public website entry point
├── index.php               # PHP redirect handler
├── login.php               # Public login page
├── logout.php              # Public logout handler
├── css/
│   └── styles.css          # Main stylesheet (v1.3.2)
├── js/
│   └── app.js              # Main application logic (v2.0.0)
├── data/
│   ├── alliances.json      # Alliance data (power-based ranking)
│   ├── rules.json          # Server rules
│   ├── amendments.json     # Rule amendment history
│   ├── rotation-schedule.json  # Pre-generated rotation schedule
│   ├── council.js          # Council utility functions (v2.0.0)
│   ├── power-history.csv   # Alliance power trends over time
│   ├── server-info.json    # Server Discord and metadata
│   ├── signature-history.json  # R5 leadership tracking
│   └── ALLIANCE_SCHEMA.md  # Alliance data structure documentation
├── admin/                  # PHP Admin Panel (v3.0.0)
│   ├── config.php          # Environment and dependency loading
│   ├── jwt.php             # JWT token management
│   ├── mailer.php          # Email functionality
│   ├── dashboard.php       # Main admin interface
│   ├── security_*.php      # Security management tools
│   ├── *_api.php           # API endpoints for data management
│   ├── users.json          # User permissions and roles
│   ├── includes/           # Shared PHP components
│   ├── vendor/             # Composer dependencies
│   ├── .env                # Environment configuration (NOT in git)
│   └── composer.json       # PHP dependencies
├── scripts/
│   ├── deploy-ftp.py       # FTP deployment script
│   ├── deploy-public-only.py  # Public site deployment (static only)
│   ├── update-rotation-schedule.py  # Schedule generator (v2.2.0)
│   ├── run-tests.py        # Unit test runner
│   └── README.md           # Scripts documentation
├── .github/
│   └── workflows/
│       └── deploy.yml      # GitHub Actions CI/CD workflow
├── images/                 # Static assets and logos
├── .ftpignore             # FTP deployment exclusions
├── .gitignore             # Git exclusions
└── README.md              # This file
```

---

## 🚀 Quick Start

### For Users
- **Live Website**: [https://www.example.com](https://www.example.com)
- **Admin Login**: [https://www.example.com/admin/login.php](https://www.example.com/admin/login.php)

### For Developers
- **Frontend Setup**: See [Setup & Installation](#setup--installation) below
- **Admin Panel Setup**: See [admin/README.md](admin/README.md)
- **Deployment Guide**: See [DEPLOYMENT.md](admin/DEPLOYMENT.md)

### For Contributors
- **Development Guide**: See [CLAUDE.md](CLAUDE.md)
- **Complete Documentation**: See [DOCUMENTATION.md](DOCUMENTATION.md)

---

## 📚 Documentation Index

> **📍 You are here:** README.md → Main project overview

### 🎯 Quick Navigation

**I want to...**
- 👨‍💻 **Develop locally** → [admin/setup-local-env.md](admin/setup-local-env.md)
- 🚀 **Deploy to production** → [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- 🔐 **Setup admin panel** → [admin/README.md](admin/README.md)
- 📖 **Browse all docs** → [DOCUMENTATION.md](DOCUMENTATION.md)
- 📋 **See what changed** → [docs/CHANGELOG.md](docs/CHANGELOG.md)

### 📚 Core Documentation

| Document | Description | Audience |
|----------|-------------|----------|
| **[README.md](README.md)** | This file - Project overview | Everyone |
| **[DOCUMENTATION.md](DOCUMENTATION.md)** | Master index - All documentation | Everyone |
| **[CLAUDE.md](CLAUDE.md)** | Developer guide & architecture | Developers |
| **[docs/CHANGELOG.md](docs/CHANGELOG.md)** | Version history & changes | Everyone |
| **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** | Deployment guide (CI/CD + manual) | DevOps |

### 🔧 Component Documentation

| Component | Documentation | Description |
|-----------|---------------|-------------|
| **Admin Panel** | [admin/README.md](admin/README.md) | Admin system overview |
| **Automation Scripts** | [scripts/README.md](scripts/README.md) | Deployment & schedule scripts |
| **Data Schemas** | [data/ALLIANCE_SCHEMA.md](data/ALLIANCE_SCHEMA.md) | Data structure docs |

### 🛠️ Setup & Configuration

| Task | Documentation | Quick Link |
|------|---------------|------------|
| **Local Development** | [admin/setup-local-env.md](admin/setup-local-env.md) | PHP + Composer setup |
| **Environment Config** | [admin/ENV-CONFIG.md](admin/ENV-CONFIG.md) | `.env` variables |
| **GitHub Actions** | [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md#github-actions-setup) | CI/CD pipeline |
| **Public Site Deployment** | [docs/PUBLIC_SITE_DEPLOYMENT.md](docs/PUBLIC_SITE_DEPLOYMENT.md) | Static frontend only |
| **Full Site Deployment** | [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | FTP + SSH + Admin |
| **GitHub Releases** | [docs/GITHUB_RELEASES.md](docs/GITHUB_RELEASES.md) | Release management |

### 🔐 Security & Admin Features

| Feature | Documentation | Description |
|---------|---------------|-------------|
| **Admin Features** | [admin/ADMIN_FUNCTIONALITY.md](admin/ADMIN_FUNCTIONALITY.md) | Feature overview |
| **Alliance Management** | [admin/ALLIANCE_MANAGEMENT_GUIDE.md](admin/ALLIANCE_MANAGEMENT_GUIDE.md) | CRUD operations |
| **JWT Key Rotation** | [KEY_ROTATION_GUIDE.md](KEY_ROTATION_GUIDE.md) | Security key management |
| **User Roles** | [admin/USER-PERSONAS.md](admin/USER-PERSONAS.md) | RBAC system |

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

2. **Start local web server** (frontend):
   ```bash
   python -m http.server 8000
   ```

3. **Setup admin panel** (optional):
   ```bash
   cd admin
   composer install
   cp .env.example .env
   # Edit .env with your configuration
   php -S localhost:8080
   ```

4. **Open in browser**:
   ```
   Frontend: http://localhost:8000
   Admin Panel: http://localhost:8080
   ```

### Install Dependencies

**Python** (for deployment and schedule management):
```bash
pip install pywin32
```

**PHP** (for admin panel):
```bash
cd admin && composer install
```

---

## 🌐 Deployment

### Quick Deployment Options

**Option 1: Download Release (Easiest)**
```bash
# Download the latest public site ZIP from GitHub Releases
# Extract and upload to your web server
```
📥 **[Download Latest Release](https://github.com/k33bz/lastwar-server1586/releases/latest)** - Pre-packaged ZIP (~80 KB)

**Option 2: Deploy from Repository**
```bash
python scripts/deploy-public-only.py
```
Deploys only the static website (no admin panel). Perfect for hosting on any static file server.

📘 **[Public Site Deployment Guide](docs/PUBLIC_SITE_DEPLOYMENT.md)** - Static frontend deployment only

**Option 3: Full Site with Admin Panel**
```bash
git push origin mainline  # Automated CI/CD
# OR
python scripts/deploy-ftp.py  # Manual deployment
```
Deploys complete site including admin panel backend.

📘 **[Full Deployment Guide](docs/DEPLOYMENT.md)** - CI/CD + Admin panel deployment

---

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

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md#github-actions-setup) for complete CI/CD setup instructions.

### Manual Deployment

**Public Site Only (170 KB):**
```bash
python scripts/deploy-public-only.py
```
- Deploys: HTML, CSS, JS, and data files
- No dependencies required
- Works on any static host (Netlify, GitHub Pages, S3, etc.)

**Full Site with Admin:**
```bash
python scripts/deploy-ftp.py
```
- Deploys: Public site + admin panel + backend
- Requires: PHP server, Composer, database

The deployment script automatically:
- ✅ Retrieves credentials from Windows Credential Manager (local) or environment variables (CI)
- ✅ Uploads only production files (respects `.ftpignore`)
- ✅ Creates remote directories as needed
- ✅ Shows deployment summary

See [docs/PUBLIC_SITE_DEPLOYMENT.md](docs/PUBLIC_SITE_DEPLOYMENT.md) for static site hosting options.

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
- Website: **3.0.0**
- Admin Panel: **3.0.0**
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

- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Complete deployment guide (CI/CD + manual)
- **[docs/CHANGELOG.md](docs/CHANGELOG.md)** - Version history and implementation summaries
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

---

## 📞 Support & Contact

For questions, issues, or contributions:
- **GitHub Issues**: [Report bugs or request features](https://github.com/username/your-repo/issues)
- **Server Discord**: [Join Server 1586 Discord](https://discord.gg/e53v2Dnp)
- **Admin Contact**: Contact server administrators

---

## 📄 License

This project is private and intended for Server 1586 alliance management only.

---

**Version**: 3.0.0 | **Last Updated**: October 16, 2025 | **Maintained by**: Server 1586 Council
