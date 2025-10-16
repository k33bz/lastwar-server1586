# Server 1586 - Complete Documentation Index

**Version:** 2.1.0
**Last Updated:** October 16, 2025
**Repository:** [lastwar-server1586](https://github.com/k33bz/lastwar-server1586)

This document serves as the master index for all documentation across the Server 1586 project.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Project Overview](#project-overview)
3. [Frontend Documentation](#frontend-documentation)
4. [Admin Panel Documentation](#admin-panel-documentation)
5. [Deployment & CI/CD](#deployment--cicd)
6. [Data & Schemas](#data--schemas)
7. [Scripts & Automation](#scripts--automation)
8. [Security & Authentication](#security--authentication)
9. [Development Guides](#development-guides)
10. [Changelog & History](#changelog--history)

---

## Quick Start

### For Developers
1. **Frontend Setup:** See [README.md](README.md)
2. **Admin Panel Setup:** See [admin/README.md](admin/README.md)
3. **Local Development:** See [admin/setup-local-env.md](admin/setup-local-env.md)

### For Admins
1. **Admin Functionality:** See [admin/ADMIN_FUNCTIONALITY.md](admin/ADMIN_FUNCTIONALITY.md)
2. **Alliance Management:** See [admin/ALLIANCE_MANAGEMENT_GUIDE.md](admin/ALLIANCE_MANAGEMENT_GUIDE.md)
3. **User Guide:** See [admin/guide.md](admin/guide.md)

### For DevOps
1. **Deployment Guide:** See [admin/DEPLOYMENT.md](admin/DEPLOYMENT.md)
2. **CI/CD Setup:** See [CICD-SETUP.md](CICD-SETUP.md)
3. **GitHub Actions:** See [GITHUB-SETUP.md](GITHUB-SETUP.md)

---

## Project Overview

**Main README:** [README.md](README.md)
- Project description and architecture
- Static website for Last War Server 1586
- NAP15 alliance rankings and council voting system

**Project Structure:**
```
Server1586-clean/
├── index.html              # Main public site
├── js/app.js               # Frontend JavaScript
├── css/styles.css          # Frontend styles
├── data/                   # Alliance data and rules
├── admin/                  # Admin panel (PHP)
├── scripts/                # Automation scripts
├── ocr/                    # OCR training for screenshots
└── .github/workflows/      # CI/CD pipelines
```

---

## Frontend Documentation

### Main Site
- **Overview:** [README.md](README.md) - Static HTML/CSS/JS site
- **Development:** [CLAUDE.md](CLAUDE.md) - Development guidelines for Claude Code
- **Alliance Modal:** [ALLIANCE-MODAL-IMPLEMENTATION.md](ALLIANCE-MODAL-IMPLEMENTATION.md)
- **Alliance Info Updates:** [ALLIANCE-INFO-UPDATE-SUMMARY.md](ALLIANCE-INFO-UPDATE-SUMMARY.md)
- **R5 Signatures:** [R5-SIGNATURE-HISTORY-IMPLEMENTATION.md](R5-SIGNATURE-HISTORY-IMPLEMENTATION.md)

### Data Files
Located in `data/`:
- `alliances.json` - Top 15 alliance rankings
- `rules.json` - Server rules
- `amendments.json` - Rule change history
- `rotation-schedule.json` - Council rotation schedule
- `council.js` - Timezone utilities

---

## Admin Panel Documentation

### Getting Started
- **Main Admin README:** [admin/README.md](admin/README.md)
- **Functionality Overview:** [admin/ADMIN_FUNCTIONALITY.md](admin/ADMIN_FUNCTIONALITY.md)
- **Alliance Management Guide:** [admin/ALLIANCE_MANAGEMENT_GUIDE.md](admin/ALLIANCE_MANAGEMENT_GUIDE.md)
- **User Guide:** [admin/guide.md](admin/guide.md)

### Setup & Configuration
- **Local Environment Setup:** [admin/setup-local-env.md](admin/setup-local-env.md)
- **Environment Configuration:** [admin/ENV-CONFIG.md](admin/ENV-CONFIG.md)
- **Composer Installation:** [admin/COMPOSER-INSTALL.md](admin/COMPOSER-INSTALL.md)
- **DKIM Email Setup:** [admin/DKIM-SETUP.md](admin/DKIM-SETUP.md)

### Security
- **Secret Key Rotation:** [admin/SECRET_KEY_ROTATION_SETUP.md](admin/SECRET_KEY_ROTATION_SETUP.md)
- **Security Changelog:** [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)
- **Version Summary:** [admin/VERSION_SUMMARY.md](admin/VERSION_SUMMARY.md)

### Shared Components
- **Includes Documentation:** [admin/includes/README.md](admin/includes/README.md)
  - `header.php` - Shared header with navigation
  - `footer.php` - Shared footer with system status
  - `email_utils.php` - Email masking utilities
  - `styles.css` - Consolidated shared CSS (NEW)
  - `scripts.js` - Shared JavaScript utilities (NEW)
  - `api_helpers.php` - Standardized API responses (NEW)

### Key Features
- JWT-based authentication with magic links
- Role-based access control (Admin, R5, R4)
- Power Editor (APE) permissions
- Alliance data management
- Backup & restore system
- Audit logging
- Security monitoring
- Email masking for PII protection

---

## Deployment & CI/CD

### Deployment Documentation
- **Main Deployment Guide:** [admin/DEPLOYMENT.md](admin/DEPLOYMENT.md)
- **CI/CD Setup:** [CICD-SETUP.md](CICD-SETUP.md)
- **GitHub Setup:** [GITHUB-SETUP.md](GITHUB-SETUP.md)
- **Deployment Notes:** [DEPLOYMENT_NOTES.md](DEPLOYMENT_NOTES.md)
- **Deployment Status:** [DEPLOYMENT-STATUS.md](DEPLOYMENT-STATUS.md)
- **Deployment History:** [DEPLOYMENT-HISTORY.md](DEPLOYMENT-HISTORY.md)
- **Power Editor Deployment:** [DEPLOYMENT-POWEREDITOR.md](DEPLOYMENT-POWEREDITOR.md)

### GitHub Actions Workflows
Located in `.github/workflows/`:
- `deploy.yml` - Main deployment workflow
- Deploys to FTP server on push to mainline
- Installs Composer dependencies
- Runs validation checks

### Deployment Scripts
See [scripts/README.md](scripts/README.md) and [scripts/DEPLOY-README.md](scripts/DEPLOY-README.md)

---

## Data & Schemas

### Alliance Data
- **Alliance Data Schema:** [data/ALLIANCE-DATA-SCHEMA.md](data/ALLIANCE-DATA-SCHEMA.md)
- **Alliance Schema (Legacy):** [data/ALLIANCE_SCHEMA.md](data/ALLIANCE_SCHEMA.md)
- **R5 Signature Schema:** [data/R5-SIGNATURE-SCHEMA.md](data/R5-SIGNATURE-SCHEMA.md)

### Data Files Structure
```
data/
├── alliances.json          # Alliance rankings
├── rules.json              # Server rules
├── amendments.json         # Rule amendments
├── rotation-schedule.json  # Council schedule
└── council.js              # Timezone utilities
```

---

## Scripts & Automation

### Main Scripts Documentation
- **Scripts README:** [scripts/README.md](scripts/README.md)
- **Deployment:** [scripts/DEPLOY-README.md](scripts/DEPLOY-README.md)
- **Screenshot Processing:** [scripts/SCREENSHOT-PROCESSING-README.md](scripts/SCREENSHOT-PROCESSING-README.md)
- **Screenshot Summary:** [SCREENSHOT-PROCESSING-SUMMARY.md](SCREENSHOT-PROCESSING-SUMMARY.md)

### Available Scripts
```
scripts/
├── generate-rotation-schedule.js   # Initial schedule generation (Node.js)
├── update-rotation-schedule.py     # Smart schedule updater (Python)
├── deploy-ftp-ci.py                # FTP deployment script
└── screenshot_processor.py         # OCR screenshot processing
```

### OCR & Training
- **OCR README:** [ocr/README.md](ocr/README.md)
- **Training Phases:** [ocr/OCR_TRAINING_PHASES.md](ocr/OCR_TRAINING_PHASES.md)
- **Phase 1 Summary:** [ocr/PHASE1_SUMMARY.md](ocr/PHASE1_SUMMARY.md)
- **Training Setup:** [ocr/TRAINING_SETUP.md](ocr/TRAINING_SETUP.md)
- **Training Data:** [ocr/training_data/README.md](ocr/training_data/README.md)
- **Tesseract Training:** [tesseract_training/TRAINING_INSTRUCTIONS.md](tesseract_training/TRAINING_INSTRUCTIONS.md)

---

## Security & Authentication

### JWT Authentication System
- **Magic Link Authentication**: Passwordless email-based login
- **Token Expiration**: 8-hour session tokens with 5-minute countdown warnings
- **Session Management**: Active session tracking, refresh capability
- **Token Blacklisting**: Revoked tokens stored in blacklist.json

### Secret Key Rotation
- **Documentation:** [admin/SECRET_KEY_ROTATION_SETUP.md](admin/SECRET_KEY_ROTATION_SETUP.md)
- **Grace Period**: 5-minute overlap for smooth transitions
- **Emergency Rotation**: Immediate invalidation of all tokens
- **Automatic Rotation**: Scheduled 30-day cycle

### Role-Based Access Control (RBAC)
- **Admin**: Full system access
- **R5 (Alliance Leader)**: Alliance data editing + rule signing
- **R4 (Officer)**: Alliance data editing
- **Power Editor (APE)**: Special permission for alliance power editing

### Security Features
- Audit logging for all administrative actions
- Email masking for PII protection
- Rate limiting (configurable via api_helpers.php)
- CORS headers for API security
- Input validation and sanitization
- File locking for concurrent write operations

### Security Changelog
See [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md) for detailed security updates.

---

## Development Guides

### For Claude Code
- **Main Development Guide:** [CLAUDE.md](CLAUDE.md)
  - Project architecture
  - File structure
  - Rendering patterns
  - Amendment system
  - Council rotation system
  - Code versioning

### Development Setup
1. **Local Development:**
   - See [admin/setup-local-env.md](admin/setup-local-env.md)
   - Requires PHP 7.4+, Composer
   - Local web server (Python, Node.js, PHP built-in, or Live Server)

2. **Environment Configuration:**
   - Copy `.env.example` to `.env`
   - See [admin/ENV-CONFIG.md](admin/ENV-CONFIG.md) for variables

3. **Composer Dependencies:**
   - See [admin/COMPOSER-INSTALL.md](admin/COMPOSER-INSTALL.md)
   - PHPMailer, Firebase JWT, vlucas/phpdotenv

### Coding Standards
- **PHP**: PSR-12 coding style
- **JavaScript**: ES6+ with JSDoc comments
- **CSS**: BEM naming convention (recommended)
- **File Comments**: Include version, date, changelog

### API Development
- **API Helpers:** [admin/includes/api_helpers.php](admin/includes/api_helpers.php)
- Standardized JSON responses
- Error handling utilities
- Validation functions
- Rate limiting

---

## Changelog & History

### Project History
- **Cleanup Complete:** [CLEANUP-COMPLETE.md](CLEANUP-COMPLETE.md)
- **Sanitization Log:** [SANITIZATION-LOG.md](SANITIZATION-LOG.md)
- **TODO Review:** [TODO-REVIEW.md](TODO-REVIEW.md)

### Version History
- **Security Changelog:** [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)
- **Version Summary:** [admin/VERSION_SUMMARY.md](admin/VERSION_SUMMARY.md)

### Current Version: 2.1.0
**Major Features:**
- JWT authentication with magic links
- Secret key rotation system
- Audit logging
- Backup & restore
- Email masking for PII
- Power Editor (APE) role
- Security monitoring
- Shared components (header, footer, styles, scripts)

---

## Additional Resources

### Images
- **Discord Logo:** [images/HOW-TO-ADD-DISCORD-LOGO.md](images/HOW-TO-ADD-DISCORD-LOGO.md)

### Environment Variables
Key variables (see [admin/ENV-CONFIG.md](admin/ENV-CONFIG.md)):
```
APP_NAME=Last War 1586
APP_ENV=production
APP_URL=https://www.lastwar1586.online
SECRET_KEY=[generated_key]
SMTP_HOST=mail.privateemail.com
SMTP_USERNAME=[email]
SMTP_PASSWORD=[password]
SMTP_FROM_EMAIL=[email]
SMTP_FROM_NAME=Last War 1586 Admin
```

### GitHub Secrets
Required for CI/CD:
- `FTP_SERVER`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `APP_URL`
- `DEPLOY_PATH`

---

## Support & Contributing

### Getting Help
1. Check relevant documentation above
2. Review [admin/guide.md](admin/guide.md)
3. Check GitHub Issues

### Contributing
1. Fork the repository
2. Create a feature branch
3. Follow coding standards
4. Update documentation
5. Submit pull request

### Reporting Issues
Use GitHub Issues with:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Environment details

---

## License

[Specify your license here]

---

**Last Updated:** October 16, 2025
**Maintained By:** k33bz
**Repository:** https://github.com/k33bz/lastwar-server1586
