# Server 1586 - Last War Alliance Website

[![GitHub Release](https://img.shields.io/github/v/release/k33bz/Server1586-clean)](https://github.com/k33bz/Server1586-clean/releases)
[![License](https://img.shields.io/badge/license-Private-red)](LICENSE)

Official website for Server 1586 alliance management, council voting, and server rules.

**Live Website**: [https://www.lastwar1586.online](https://www.lastwar1586.online)
**GitHub Repository**: [https://github.com/k33bz/Server1586-clean](https://github.com/k33bz/Server1586-clean)

**Version**: 3.7.0
**Last Updated**: November 11, 2025

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [Development](#development)
- [Deployment](#deployment)
- [Data Management](#data-management)
- [Scripts](#scripts)
- [Documentation](#documentation)

---

## ✨ Features

### Public Website (React + HeroUI v3)
- **Modern UI Framework**: Built with React 18, TypeScript, and HeroUI v3 (Alpha)
- **Internationalization**: Full i18n support with 5 languages (English, Spanish, Portuguese, German, Korean)
- **Alliance Rankings**: Interactive display with top 3 podium and complete NAP15 grid
- **Power Trends Chart**: Time-series visualization using Chart.js with alliance power tracking
- **Council Voting System**: Rotating council members (ranks 6-15) with 5-week cycle
- **Server Rules**: Expandable sections with diff view toggle for amendments
- **Responsive Design**: Mobile-first design with Tailwind CSS v4
- **Dark Mode**: Theme toggle with cyan (light) and orange (dark) accent colors
- **Floating Navigation**: Back-to-top button and theme toggle
- **Dynamic Data**: JSON-based data fetching with TypeScript interfaces

### Admin Panel (PHP - Separate)
- **JWT Authentication**: Passwordless magic link login system
- **Role-Based Access Control**: Admin, President, Council Member roles
- **Multi-Language Support**: Full i18n with 5 languages (EN, ES, PT, DE, KO)
- **Discord Integration**: Vote management, announcements, channel configuration
- **Alliance Management**: Power updates, officer management, tags, rotation schedule
- **Council Voting**: Discord-integrated voting system with web management
- **Security Monitoring**: Audit logging, rate limiting, session management
- **User Management**: Profile editing, language preferences, role assignment

---

## 🛠 Tech Stack

### Frontend
- **React 18**: Modern React with hooks and TypeScript
- **TypeScript 5.6**: Type-safe development
- **Vite 7.2**: Lightning-fast build tool
- **HeroUI v3**: Component library (Alpha v3.0.0-beta.1)
- **Tailwind CSS v4**: Utility-first CSS framework
- **Chart.js 4.4**: Power trends visualization
- **React Router**: Client-side routing
- **i18next**: Internationalization with 5 language support

### Build & Development
- **ESLint**: Code quality and consistency
- **Vitest**: Unit testing framework
- **npm**: Package management

### Data Layer
- **JSON**: Static data files for alliances, rules, council
- **CSV**: Historical power trend data
- **TypeScript Interfaces**: Strongly typed data structures

---

## 📁 Project Structure

```
Server1586-clean/
├── client/                    # React frontend application
│   ├── src/
│   │   ├── components/        # React components
│   │   │   ├── AllianceGrid.tsx
│   │   │   ├── AlliancePodium.tsx
│   │   │   ├── BackToTop.tsx
│   │   │   ├── CouncilMembers.tsx
│   │   │   ├── DiscordBanner.tsx
│   │   │   ├── FloatingThemeToggle.tsx
│   │   │   ├── Header.tsx
│   │   │   ├── PowerTrends.tsx
│   │   │   ├── ServerRules.tsx
│   │   │   └── Signatories.tsx
│   │   ├── hooks/             # Custom React hooks
│   │   │   └── useApi.ts      # Data fetching hook
│   │   ├── styles/            # Styling
│   │   │   └── custom-theme.css
│   │   ├── HomePage.tsx       # Main page component
│   │   ├── main.tsx           # React entry point
│   │   └── types.ts           # TypeScript type definitions
│   ├── dist/                  # Build output
│   ├── public/                # Static assets
│   ├── package.json           # npm dependencies
│   ├── tsconfig.json          # TypeScript configuration
│   └── vite.config.ts         # Vite configuration
│
├── data/                      # JSON/CSV data files
│   ├── alliances.json         # Alliance data (power-based ranking)
│   ├── council.json           # Current council members
│   ├── rotation-schedule.json # Pre-generated rotation schedule
│   ├── rules.json             # Server rules with amendments
│   ├── server-info.json       # Server metadata and Discord
│   ├── signatories.json       # R5 signature tracking
│   ├── power-history.csv      # Alliance power trends
│   └── version.json           # Site version info
│
├── scripts/                   # Python automation scripts
│   ├── update_rotation_schedule.py
│   └── update_niki.py
│
├── docs/                      # Documentation
│   ├── HEROUI_MIGRATION.md
│   └── MIGRATION_SUMMARY.md
│
├── temp/                      # Temporary files (not in git)
│
├── admin/                     # PHP Admin Panel (separate system)
│   ├── i18n/                  # Admin panel translations
│   │   ├── en/                # English (source)
│   │   ├── es/                # Spanish
│   │   ├── pt/                # Portuguese
│   │   ├── de/                # German
│   │   └── ko/                # Korean
│   ├── includes/              # Shared PHP modules
│   │   ├── i18n.php           # Translation functions
│   │   └── help_content/      # Help drawer content
│   └── [50+ admin pages]      # Various admin functionality
│
├── assets/                    # Built static assets
├── images/                    # Static images and logos
├── index.html                 # Production entry point
├── dev_server.py              # Development server GUI tool
├── translate_admin_smart.py   # Admin panel translation script
├── translate_rules.py         # Client rules translation script
├── translate_locale.py        # Client locale translation script
├── .gitignore                 # Git exclusions
├── CLAUDE.md                  # Development workflow guide
├── DEPLOYMENT.md              # Deployment instructions
└── README.md                  # This file
```

---

## 🚀 Quick Start

### Prerequisites

- **Node.js 18+** and **npm**
- **Python 3.7+** (for data management scripts)

### Development Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/k33bz/Server1586-clean.git
   cd Server1586-clean
   ```

2. **Install dependencies**:
   ```bash
   cd client
   npm install
   ```

3. **Start development server**:
   ```bash
   npm run dev
   ```

4. **Open in browser**:
   ```
   http://localhost:5173
   ```

### Quick Commands

```bash
# Development server with hot reload
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type checking
npm run type-check

# Linting
npm run lint
```

### Development GUI Tool

For easier local testing, use the development server GUI:

```bash
python dev_server.py
```

**Features:**
- Start/stop PHP admin server (port 8000)
- Start/stop React dev server (port 5173)
- Build production bundles
- Preview production builds (port 4173)
- Live log monitoring for all servers
- One-click browser launch
- Clean dist directory

### Translation Scripts

The project includes automated translation tools for maintaining multi-language support:

```bash
# Translate admin panel UI (EN → ES, PT, DE, KO)
python translate_admin_smart.py

# Translate client rules (EN → ES, PT, DE, KO)
python translate_rules.py

# Translate client locales (EN → ES, PT, DE, KO)
python translate_locale.py
```

**Note:** Requires LM Studio running on `localhost:1234` with Hunyuan-MT-7B model.

---

## 💻 Development

### File Structure

**Components** (`client/src/components/`)
- `AlliancePodium.tsx` - Top 3 alliances display
- `AllianceGrid.tsx` - NAP15 alliance cards grid
- `CouncilMembers.tsx` - Council voting members with rotation
- `PowerTrends.tsx` - Chart.js power visualization
- `ServerRules.tsx` - Rules with diff toggle
- `FloatingThemeToggle.tsx` - Dark/light mode switcher
- `BackToTop.tsx` - Scroll-to-top button

**Hooks** (`client/src/hooks/`)
- `useApi.ts` - Generic data fetching hook with loading/error states

**Styling** (`client/src/styles/`)
- `custom-theme.css` - HeroUI theme customization (light/dark modes)

### Theme Customization

The site uses a custom HeroUI v3 theme:

**Light Mode**: Cyan accent (`oklch(0.78 0.10 200)`)
**Dark Mode**: Orange accent (`oklch(0.72 0.22 35)`)

Edit `client/src/styles/custom-theme.css` to modify colors, spacing, shadows, etc.

### Adding New Components

1. Create component in `client/src/components/`
2. Import HeroUI components: `import { Card, Button } from '@heroui/react'`
3. Use TypeScript interfaces from `client/src/types.ts`
4. Follow HeroUI v3 compound component patterns

### Internationalization (i18n)

The site supports 5 languages with full translation coverage:

**Supported Languages**:
- English (en-US) - Default
- Spanish (es)
- Portuguese (pt)
- German (de)
- Korean (ko)

**Translation Structure**:
```
client/
├── locales/
│   ├── en-US/
│   │   ├── common.json    # Navigation, UI elements, errors
│   │   └── public.json    # Page content, alliances, rules
│   ├── es/
│   ├── pt/
│   ├── de/
│   └── ko/
└── public/data/
    ├── rules-en-US.json   # Server rules by language
    ├── rules-es.json
    ├── rules-pt.json
    ├── rules-de.json
    └── rules-ko.json
```

**Using Translations in Components**:
```typescript
import { useTranslation } from 'react-i18next';

function MyComponent() {
  const { t } = useTranslation(['common', 'public']);

  return (
    <div>
      <h1>{t('public:header.title')}</h1>
      <p>{t('common:navigation.home')}</p>
      <span>{t('public:podium.power', { power: '1.5B' })}</span>
    </div>
  );
}
```

**Translation Tools**:
- `translate_locale.py` - Translate locale files using LM Studio
- `translate_rules.py` - Translate server rules using LM Studio

**Adding New Translation Keys**:
1. Add key to `en-US/common.json` or `en-US/public.json`
2. Run translation script: `python translate_locale.py <lang>`
3. Review and commit translations

**Important Notes**:
- Keep interpolation variables in English: `{{year}}`, `{{date}}`, `{{count}}`
- Use `t()` function for all user-facing text
- Discord banner and alliance modal are fully translated

---

## 🌐 Deployment

### Production Build

```bash
cd client
npm run build
```

This creates optimized files in `client/dist/`:
- `index.html` - Entry point
- `assets/` - JS, CSS bundles

### Deploy to Root

```bash
# From project root
cp client/dist/index.html index.html
cp -r client/dist/assets/* assets/
```

### Deployment Options

1. **GitHub Pages**: Push `index.html` and `assets/` to gh-pages branch
2. **Netlify/Vercel**: Connect to GitHub repo, auto-deploy on push
3. **cPanel/FTP**: Upload `index.html`, `assets/`, `data/`, `images/`

---

## 📊 Data Management

### Alliance Data (`data/alliances.json`)

Update alliance information (ranks calculated automatically by power):

```json
[
  {
    "tag": "ORCE",
    "name": "Omega Force",
    "r5": {
      "name": "Ali Ω",
      "gameId": null,
      "discordId": null
    },
    "power": 8783088512,
    "signed": false
  }
]
```

**Key Points**:
- Ranks are calculated dynamically from `power` field
- No `rank` field needed in JSON
- Single source of truth (power determines rank)

### Council Rotation (`data/rotation-schedule.json`)

Update rotation schedule when top 15 alliances change:

```bash
python scripts/update_rotation_schedule.py
```

The script:
- Reads current top 15 from `alliances.json`
- Generates 100-week rotation schedule
- Uses 5-week cycle for ranks 6-15 (2 alliances per week)
- Preserves historical rotation data

### Server Rules (`data/rules.json`)

Rules now include embedded amendments:

```json
[
  {
    "title": "NAP15 Overview",
    "content": ["Rule text..."],
    "amendments": [
      {
        "version": "1.1",
        "date": "2025-10-05",
        "changes": [
          {
            "type": "add",
            "text": "New rule text..."
          }
        ]
      }
    ]
  }
]
```

**Amendment Types**:
- `add` - New rule (shown in normal view)
- `remove` - Deleted rule (shown in diff view only)
- `modify` - Changed rule (shown in diff view only)

### Power History (`data/power-history.csv`)

CSV format for power trends chart:

```csv
datetime,ORCE,STR8,UvvU,EPIC,NKOT,...
2025-11-11 01:27:00,8783088512,6775724571,6481396612,...
```

Add new rows to track power over time. Chart.js automatically visualizes the data.

---

## 📜 Scripts

### Update Rotation Schedule

```bash
python scripts/update_rotation_schedule.py
```

Generates `data/rotation-schedule.json` with:
- Current week calculation based on epoch (May 19, 2025)
- 100-week schedule
- Fair 5-week rotation cycle for ranks 6-15

### Update Alliance Data

```bash
python scripts/update_niki.py
```

Example script for updating specific alliance information.

### After Data Updates

1. **Rebuild frontend** (if needed):
   ```bash
   cd client && npm run build
   ```

2. **Deploy** to production:
   ```bash
   cp client/dist/index.html index.html
   cp -r client/dist/assets/* assets/
   ```

---

## 📚 Documentation

### Core Documentation
- **[CLAUDE.md](CLAUDE.md)** - Development workflow guidelines
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment instructions
- **[docs/HEROUI_MIGRATION.md](docs/HEROUI_MIGRATION.md)** - HeroUI v3 migration guide
- **[docs/MIGRATION_SUMMARY.md](docs/MIGRATION_SUMMARY.md)** - Migration summary

### Version Information

Current version information is stored in `data/version.json` and displayed in the footer.

**Update Version**:
```bash
# Edit data/version.json
{
  "version": "3.7.0",
  "releaseDate": "2025-11-06",
  "lastUpdated": "2025-11-11"
}
```

---

## 🤝 Contributing

### Making Changes

1. **Create feature branch**:
   ```bash
   git checkout -b feature/your-feature
   ```

2. **Make changes** in `client/src/`

3. **Test locally**:
   ```bash
   npm run dev
   ```

4. **Build for production**:
   ```bash
   npm run build
   ```

5. **Commit and push**:
   ```bash
   git add .
   git commit -m "feat: your feature description"
   git push origin feature/your-feature
   ```

### Git Workflow

Follow the guidelines in [CLAUDE.md](CLAUDE.md):
- Use LM Studio for commit message review
- Never use `SKIP_LMSTUDIO=1`
- Create GitHub issues for bugs
- Reference issue numbers in commits

---

## 📞 Support & Contact

- **GitHub Issues**: [Report bugs or request features](https://github.com/k33bz/Server1586-clean/issues)
- **GitHub Repository**: [https://github.com/k33bz/Server1586-clean](https://github.com/k33bz/Server1586-clean)
- **Server**: Last War - Server 1586

---

## 📄 License

This project is private and intended for Server 1586 alliance management only.

---

## 🎨 Built With

- **React** - UI framework
- **TypeScript** - Type safety
- **Vite** - Build tool
- **HeroUI v3** - Component library
- **Tailwind CSS v4** - Styling
- **Chart.js** - Data visualization
- **Claude Code** - AI-assisted development
- **Kiro** - Development enhancement

---

**Version**: 3.7.0 | **Last Updated**: November 11, 2025 | **Maintained by**: Server 1586 Council

🤖 Generated with [Claude Code](https://claude.com/claude-code) · Enhanced by [Kiro](https://kiro.dev/)
