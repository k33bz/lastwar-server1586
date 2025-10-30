# Server 1586 - Complete Documentation Index

**Version:** 3.0.0
**Last Updated:** October 16, 2025
**Repository:** [lastwar-server1586](https://github.com/k33bz/lastwar-server1586)

This document serves as the master index for all documentation across the Server 1586 project.

---

## Table of Contents

> **📍 You are here:** DOCUMENTATION.md → Complete documentation index

1. [Quick Start](#quick-start)
2. [Project Overview](#project-overview)
3. [Frontend Documentation](#frontend-documentation)
4. [Admin Panel Documentation](#admin-panel-documentation)
5. [Deployment & CI/CD](#deployment--cicd) ⭐ **NEW:** Consolidated
6. [Data & Schemas](#data--schemas)
7. [Scripts & Automation](#scripts--automation)
8. [Security & Authentication](#security--authentication)
9. [Development Guides](#development-guides)
10. [Changelog & History](#changelog--history) ⭐ **NEW:** Consolidated

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
1. **Deployment Guide:** See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
2. **CI/CD Setup:** See [docs/DEPLOYMENT.md#github-actions-setup](docs/DEPLOYMENT.md#github-actions-setup)
3. **Manual Deployment:** See [docs/DEPLOYMENT.md#manual-deployment](docs/DEPLOYMENT.md#manual-deployment)

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
- **Alliance Modal:** [docs/CHANGELOG.md#alliance-modal-implementation](docs/CHANGELOG.md#alliance-modal-implementation)
- **Alliance Info Updates:** [docs/CHANGELOG.md#feature-implementation-summaries](docs/CHANGELOG.md#feature-implementation-summaries)
- **R5 Signatures:** [docs/CHANGELOG.md#r5-signature-history-implementation](docs/CHANGELOG.md#r5-signature-history-implementation)

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

### Security & Maintenance
- **Secret Key Rotation:** [admin/SECRET_KEY_ROTATION_SETUP.md](admin/SECRET_KEY_ROTATION_SETUP.md)
- **Security Changelog:** [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)
- **Version Summary:** [admin/VERSION_SUMMARY.md](admin/VERSION_SUMMARY.md)
- **Version Migration System:** [admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md) ⭐ **NEW**

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

⭐ **NEW:** All deployment documentation consolidated into `docs/DEPLOYMENT.md`

### 📘 Complete Deployment Guide
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Complete deployment documentation
  - Automated CI/CD deployment
  - Manual deployment instructions
  - GitHub Actions setup
  - Environment configuration
  - Deployment history
  - Troubleshooting guide

### 🔄 Version Migration System (NEW)
- **[admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md)** - Automatic schema migration
  - Automatic detection of version mismatches
  - Safe, incremental migrations with backups
  - JSON and .env schema updates
  - CLI and web interface
  - Pre-built migrations for v3.0.0 through v3.3.0
  - **See Issue #26** for documentation updates

**Quick Start:**
```bash
# After deployment, check for migration warning banner
# Run migration via CLI (recommended):
php admin/migrate.php

# Or click "Run Migration Now" in web interface
```

### Quick Links
- **Automated Deployment:** [docs/DEPLOYMENT.md#automated-cicd-deployment](docs/DEPLOYMENT.md#automated-cicd-deployment)
- **GitHub Actions Setup:** [docs/DEPLOYMENT.md#github-actions-setup](docs/DEPLOYMENT.md#github-actions-setup)
- **Manual Deployment:** [docs/DEPLOYMENT.md#manual-deployment](docs/DEPLOYMENT.md#manual-deployment)
- **Version Migration:** [admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md)
- **Troubleshooting:** [docs/DEPLOYMENT.md#troubleshooting](docs/DEPLOYMENT.md#troubleshooting)

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
- **Alliance Data Schema:** [data/ALLIANCE_SCHEMA.md](data/ALLIANCE_SCHEMA.md)
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
- **Screenshot Summary:** [docs/CHANGELOG.md#screenshot-processing-system](docs/CHANGELOG.md#screenshot-processing-system)

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
- **Test Token System** (NEW): Generate long-lived JWT tokens for API testing
  - Simplified token generation without email requirement
  - Environment-specific (localhost vs production)
  - Token management UI in Security Monitor
  - Revocation capability with audit logging

### Test Token Generation
Located at `admin/generate_test_token.php`:
- **Purpose**: Create long-lived JWT tokens for API testing and automation
- **Features**:
  - Auto-generated identifiers (`test-{role}-{timestamp}`)
  - Configurable expiry (1-365 days)
  - Role selection (admin/r5/r4)
  - Alliance access control
  - Toast notification for copy feedback
  - Localhost vs production key awareness
- **Management**: View and revoke tokens in Security Monitor
- **Testing**: Use `admin/test_token_auth.php` to verify token validity

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

⭐ **NEW:** All version history and implementation summaries consolidated into `docs/CHANGELOG.md`

### 📘 Complete Changelog
- **[docs/CHANGELOG.md](docs/CHANGELOG.md)** - Complete version history
  - All releases (v1.0.0 → v3.0.0)
  - Feature implementation summaries
  - Breaking changes and migrations
  - Deprecations and roadmap

### Quick Links
- **Latest Release (v3.0.0):** [docs/CHANGELOG.md#300---2025-10-16](docs/CHANGELOG.md#300---2025-10-16)
- **Migration Guides:** [docs/CHANGELOG.md#migration-guides](docs/CHANGELOG.md#migration-guides)
- **Roadmap:** [docs/CHANGELOG.md#roadmap](docs/CHANGELOG.md#roadmap)
- **Known Issues:** [docs/CHANGELOG.md#known-issues](docs/CHANGELOG.md#known-issues)

### Component-Specific Version History
- **Security Changelog:** [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)
- **Version Summary:** [admin/VERSION_SUMMARY.md](admin/VERSION_SUMMARY.md)

### Current Version: 3.0.0 (2025-10-16)
**Major Features:**
- JWT authentication with magic links
- Multi-factor authentication (TOTP, backup codes, hardware keys)
- Advanced security monitoring and threat detection
- Real-time audit logging with viewer
- Automatic backup & restore system
- JWT key rotation with emergency capabilities
- Email masking for PII protection
- Power Editor (APE) role
- Device management and registration
- Rate limiting and IP blocking
- Security management dashboard
- Shared components (header, footer, styles, scripts)

**See full details:** [docs/CHANGELOG.md#300---2025-10-16](docs/CHANGELOG.md#300---2025-10-16)

---

## Additional Resources

### Images
- **Discord Logo:** [images/HOW-TO-ADD-DISCORD-LOGO.md](images/HOW-TO-ADD-DISCORD-LOGO.md)

### Environment Variables
Key variables (see [admin/ENV-CONFIG.md](admin/ENV-CONFIG.md)):
```
APP_NAME=Your Server Name
APP_ENV=production
APP_URL=https://www.example.com
SECRET_KEY=[generated_key]
SMTP_HOST=smtp.example.com
SMTP_USERNAME=[email]
SMTP_PASSWORD=[password]
SMTP_FROM_EMAIL=[email]
SMTP_FROM_NAME=Your Server Admin
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

**Last Updated:** October 19, 2025
**Maintained By:** k33bz
**Repository:** https://github.com/k33bz/lastwar-server1586

**Latest Additions:**
- Version Migration System (Issue #26)
- Production Deployment File Audit
- Automatic schema upgrade system
