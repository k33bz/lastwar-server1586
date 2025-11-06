# Server 1586 Documentation

**Version:** 3.4.0
**Last Updated:** 2025-11-02

Welcome to the Server 1586 documentation hub. This directory contains all project documentation organized by topic.

---

## 📍 Quick Links

- **[← Back to Project Root](../README.md)** - Main project overview
- **[Admin Panel Documentation](admin/)** - Admin system guides
- **[GitHub Issues](https://github.com/k33bz/lastwar-server1586/issues)** - Task tracking and bug reports

---

## 📚 Core Documentation

### Getting Started

| Document | Description |
|----------|-------------|
| **[README.md](../README.md)** | Project overview, features, quick start |
| **[DEPLOYMENT.md](DEPLOYMENT.md)** | Complete deployment guide (CI/CD, FTP, incremental) |
| **[PRODUCTION-ENV-SETUP.md](PRODUCTION-ENV-SETUP.md)** | Production environment configuration |
| **[CLAUDE.md](CLAUDE.md)** | Claude Code AI assistant instructions |

### Development & Tools

| Document | Description |
|----------|-------------|
| **[GIT_HOOKS.md](GIT_HOOKS.md)** | Git hooks with LM Studio integration |
| **[MCP_SETUP.md](MCP_SETUP.md)** | Model Context Protocol server setup |
| **[LM-STUDIO-TESTING.md](LM-STUDIO-TESTING.md)** | LM Studio integration and testing |
| **[OLLAMA_AUTOMATION.md](OLLAMA_AUTOMATION.md)** | Ollama automation setup |

### Project Management

| Document | Description |
|----------|-------------|
| **[VERSIONING.md](VERSIONING.md)** | Version numbering and release process |
| **[CHANGELOG.md](CHANGELOG.md)** | Version history and release notes |
| **[GITHUB_RELEASES.md](GITHUB_RELEASES.md)** | GitHub Releases workflow |
| **[CONTRIBUTORS.md](CONTRIBUTORS.md)** | Project contributors and acknowledgments |

### APIs & Integration

| Document | Description |
|----------|-------------|
| **[PUBLIC_API.md](PUBLIC_API.md)** | Public REST API documentation |
| **[HOW-TO-ADD-DISCORD-LOGO.md](HOW-TO-ADD-DISCORD-LOGO.md)** | Adding Discord logo to site |

---

## 🔧 Admin Panel Documentation

Complete admin system documentation in **[docs/admin/](admin/)**.

### Core Admin Guides

| Document | Description |
|----------|-------------|
| **[admin/README.md](../admin/README.md)** | Admin panel overview |
| **[admin/ADMIN_FUNCTIONALITY.md](admin/ADMIN_FUNCTIONALITY.md)** | Feature overview and capabilities |
| **[admin/guide.md](admin/guide.md)** | Comprehensive admin guide |
| **[admin/USER-PERSONAS.md](admin/USER-PERSONAS.md)** | User roles and permissions |

### Setup & Configuration

| Document | Description |
|----------|-------------|
| **[admin/COMPOSER-INSTALL.md](admin/COMPOSER-INSTALL.md)** | Composer dependency setup |
| **[admin/ENV-CONFIG.md](admin/ENV-CONFIG.md)** | Environment variables reference |
| **[admin/setup-local-env.md](admin/setup-local-env.md)** | Local development setup |
| **[admin/DKIM-SETUP.md](admin/DKIM-SETUP.md)** | DKIM email authentication |

### Security & Auth

| Document | Description |
|----------|-------------|
| **[admin/CSRF_PROTECTION.md](admin/CSRF_PROTECTION.md)** | CSRF protection implementation |
| **[admin/SECRET_KEY_ROTATION_SETUP.md](admin/SECRET_KEY_ROTATION_SETUP.md)** | JWT key rotation |
| **[admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)** | Security updates history |

### Features & Systems

| Document | Description |
|----------|-------------|
| **[admin/MULTI_ROLE_IMPLEMENTATION.md](admin/MULTI_ROLE_IMPLEMENTATION.md)** | Multi-role system documentation |
| **[admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md)** | Version migration system |
| **[admin/ALLIANCE_MANAGEMENT_GUIDE.md](admin/ALLIANCE_MANAGEMENT_GUIDE.md)** | Alliance data management |
| **[admin/ALERT-TO-MODAL-REPLACEMENTS.md](admin/ALERT-TO-MODAL-REPLACEMENTS.md)** | UI modal system |
| **[admin/VERSION_SUMMARY.md](admin/VERSION_SUMMARY.md)** | Admin panel version history |

### Components & UI/UX

| Document | Description |
|----------|-------------|
| **[admin/includes/README.md](admin/includes/README.md)** | Shared components overview |
| **[admin/includes/SHARED-COMPONENTS.md](admin/includes/SHARED-COMPONENTS.md)** | Component documentation |
| **[LOADING_STATES.md](LOADING_STATES.md)** | Global loading overlay & toast notifications (v3.1.0) |

---

## 📊 Data Schemas

Data structure documentation in **[docs/schemas/](schemas/)**.

| Schema | Description |
|--------|-------------|
| **[schemas/ALLIANCE_SCHEMA.md](schemas/ALLIANCE_SCHEMA.md)** | Alliance data structure |
| **[schemas/R5-SIGNATURE-SCHEMA.md](schemas/R5-SIGNATURE-SCHEMA.md)** | R5 signature history format |

---

## 🔧 Scripts & Automation

Script documentation in **[docs/scripts/](scripts/)** and **[../scripts/README.md](../scripts/README.md)**.

| Document | Description |
|----------|-------------|
| **[scripts/README.md](../scripts/README.md)** | Scripts overview |

---

## 📂 Repository Structure

```
Server1586-clean/
├── README.md                   # Project overview
├── index.html                  # Public site
├── css/                        # Stylesheets
├── js/                         # Frontend JavaScript
├── data/                       # Data files (JSON, CSV)
├── admin/                      # Admin panel (PHP)
│   └── README.md               # Admin overview
├── scripts/                    # Deployment & utility scripts
│   └── README.md               # Scripts overview
├── docs/                       # ⭐ All documentation
│   ├── README.md               # ⭐ This file (documentation index)
│   ├── DEPLOYMENT.md           # Deployment guide
│   ├── CHANGELOG.md            # Version history
│   ├── admin/                  # Admin documentation
│   │   ├── *.md                # Admin guides
│   │   └── includes/           # Component docs
│   ├── schemas/                # Data schemas
│   └── scripts/                # Script docs
├── .github/                    # GitHub Actions workflows
└── documentation-archive/      # Historical documentation
```

---

## 🎯 Common Tasks

### For Developers

**Deploy to Production:**
```bash
git push origin mainline
```
See: [DEPLOYMENT.md](DEPLOYMENT.md)

**Run Tests:**
```bash
python scripts/run-tests.py
```

**Run Migrations:**
```bash
php admin/migrate.php
```
See: [admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md)

**Setup Git Hooks:**
```bash
chmod +x .git/hooks/*
```
See: [GIT_HOOKS.md](GIT_HOOKS.md)

### For Administrators

**Access Admin Panel:**
```
https://www.example.com/admin/dashboard.php
```
See: [admin/README.md](../admin/README.md)

**Generate Magic Link:**
```bash
php admin/generate_magic_link.php email@example.com
```

**Rotate JWT Keys:**
```bash
php admin/rotate_keys.php
```
See: [admin/SECRET_KEY_ROTATION_SETUP.md](admin/SECRET_KEY_ROTATION_SETUP.md)

### For Content Managers

**Update Alliance Rankings:**
- Edit `data/alliances.json`
- Commit and push changes

**Update Server Rules:**
- Edit `data/rules.json`
- Add amendment to `data/amendments.json`

**Update Power History:**
- Edit `data/power-history.csv`
- Commit and push changes

---

## 🚀 Deployment Overview

| Method | Use Case | Documentation |
|--------|----------|---------------|
| **GitHub Actions** | Automated production | [DEPLOYMENT.md](DEPLOYMENT.md#1-automated-cicd-github-actions) |
| **Incremental FTP** | Fast updates | [DEPLOYMENT.md](DEPLOYMENT.md#3-incremental-deployment) |
| **Manual FTP** | Emergency/testing | [DEPLOYMENT.md](DEPLOYMENT.md#2-manual-ftp-deployment) |
| **Public site only** | Static frontend | [DEPLOYMENT.md](DEPLOYMENT.md#4-public-site-only-deployment) |

---

## 🔐 Security Best Practices

1. **Never commit secrets** - Use `.env` files (gitignored)
2. **Rotate JWT keys** - Every 90 days or on major changes
3. **Use HTTPS** - Always in production
4. **Enable 2FA** - On GitHub account
5. **Review audit logs** - Monitor for suspicious activity
6. **Validate inputs** - All user data must be validated
7. **Update dependencies** - Run `composer update` regularly

See: [admin/SECURITY_CHANGELOG.md](admin/SECURITY_CHANGELOG.md)

---

## 🐛 Troubleshooting

**Deployment failed?**
→ [DEPLOYMENT.md#troubleshooting](DEPLOYMENT.md#troubleshooting)

**Admin panel not working?**
→ [admin/README.md](../admin/README.md)

**Migration issues?**
→ [admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md)

**Git hooks failing?**
→ [GIT_HOOKS.md#troubleshooting](GIT_HOOKS.md#troubleshooting)

---

## 📋 Documentation Standards

### When to Create Documentation

- **New features** - Document before or during implementation
- **Complex systems** - Architecture and design decisions
- **APIs** - Endpoints, parameters, responses
- **Deployment** - Any new deployment method or tool
- **Security** - Security-related changes or vulnerabilities

### Documentation Location

- **Root README.md** - Project overview only
- **Admin README.md** - Admin panel overview only
- **docs/** - All other documentation
- **docs/admin/** - Admin-specific guides
- **docs/schemas/** - Data structure definitions
- **docs/scripts/** - Script-specific documentation

### File Naming

- Use `SCREAMING_SNAKE_CASE.md` for major docs
- Use `kebab-case.md` for minor/supporting docs
- Use descriptive names (e.g., `MULTI_ROLE_IMPLEMENTATION.md` not `roles.md`)

### Required Sections

All documentation should include:
1. **Title** - Clear, descriptive title
2. **Version** - Document version and last updated date
3. **Overview** - Brief summary (2-3 sentences)
4. **Table of Contents** - For docs over 100 lines
5. **Examples** - Code/command examples where applicable
6. **Related Documentation** - Links to related docs

---

## 📝 Contributing to Documentation

1. **Follow standards** - Use templates and naming conventions
2. **Update CHANGELOG.md** - Document version changes
3. **Link related docs** - Add cross-references
4. **Test examples** - Ensure all code/commands work
5. **Get review** - Have another person review before commit

---

## 🔗 External Resources

- **GitHub Repository:** https://github.com/k33bz/lastwar-server1586
- **Production Site:** https://www.example.com
- **Admin Panel:** https://www.example.com/admin/dashboard.php
- **GitHub Issues:** https://github.com/k33bz/lastwar-server1586/issues

---

## 📞 Support

For issues, questions, or feature requests:

1. **Check existing docs** - Search this documentation first
2. **Search GitHub Issues** - See if already reported
3. **Create new issue** - Use issue templates
4. **Contact maintainers** - For urgent production issues

---

**Maintained By:** k33bz
**Last Updated:** 2025-11-02
**Documentation Version:** 3.4.0
# Git Hooks Documentation
