# Server 1586 Public Site - Architecture Documentation

**Version**: 3.7.0
**Last Updated**: November 11, 2025
**Built with**: React 18 + TypeScript + HeroUI v3 + Vite

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Technology Stack](#technology-stack)
- [Component Structure](#component-structure)
- [Data Flow](#data-flow)
- [Styling & Theming](#styling--theming)
- [Build & Deployment](#build--deployment)
- [Development Workflow](#development-workflow)

---

## Overview

The Server 1586 public website is a modern, single-page application (SPA) built with React 18, TypeScript, and HeroUI v3. It serves as the central hub for alliance rankings, server rules, council voting information, and power trends visualization.

### Key Features

- **Static Data-Driven**: All content loaded from JSON/CSV files
- **Type-Safe**: Full TypeScript coverage with strict mode
- **Component-Based**: Modular React components with HeroUI v3
- **Responsive**: Mobile-first design with Tailwind CSS v4
- **Themeable**: Light/dark mode with custom accent colors
- **Fast**: Optimized build with Vite, lazy loading, code splitting

---

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Browser (Client)                        │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           React Application (SPA)                     │  │
│  │  ┌─────────────┐  ┌──────────────┐  ┌─────────────┐  │  │
│  │  │  HomePage   │  │  Components  │  │   Hooks     │  │  │
│  │  └─────────────┘  └──────────────┘  └─────────────┘  │  │
│  │                                                        │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │          Data Fetching (useApi hook)           │  │  │
│  │  └─────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
│                           │                                  │
│                           ▼                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │           Static JSON/CSV Files                       │  │
│  │  • alliances.json  • rules.json  • council.json      │  │
│  │  • power-history.csv  • server-info.json             │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Application Flow

1. **Initial Load**: Browser requests `index.html`
2. **React Bootstrap**: Vite bundles loaded, React initializes
3. **Data Fetching**: `useApi` hook fetches JSON/CSV files
4. **Component Render**: Data passed to components via props
5. **User Interaction**: State updates trigger re-renders
6. **Theme Toggle**: CSS variables updated for dark/light mode

---

## Technology Stack

### Core Framework
- **React 18.3.1**: Component-based UI library
- **TypeScript 5.6.2**: Static typing and improved DX
- **Vite 7.2.2**: Next-generation build tool

### UI Framework
- **HeroUI v3.0.0-beta.1**: React component library (Alpha)
- **Tailwind CSS v4**: Utility-first CSS framework
- **React Aria**: Accessibility primitives (used by HeroUI)

### Data Visualization
- **Chart.js 4.5.1**: Canvas-based charting library
- **react-chartjs-2 5.3.1**: React wrapper for Chart.js
- **chartjs-adapter-date-fns 3.0.0**: Time-scale adapter

### UI Controls
- **rc-slider 11.1.9**: Accessible dual-range slider component

### Development Tools
- **ESLint**: Code linting
- **TypeScript ESLint**: TypeScript-specific linting
- **Vite Plugins**: React, ESLint integration

---

## Component Structure

### Component Hierarchy

```
HomePage.tsx
├── FloatingThemeToggle.tsx     (Fixed position, top-right)
├── BackToTop.tsx                (Fixed position, bottom-right)
├── DiscordBanner.tsx            (Server info banner)
├── Tabs                         (HeroUI Tabs component)
│   ├── Rankings Tab
│   │   ├── AlliancePodium.tsx   (Top 3 display)
│   │   └── AllianceGrid.tsx     (NAP15 grid)
│   ├── Rules & NAP15 Tab
│   │   ├── ServerRules.tsx      (Rules with diff toggle)
│   │   ├── CouncilMembers.tsx   (Voting + rotation)
│   │   └── Signatories.tsx      (R5 signature tracking)
│   └── Power Trends Tab
│       └── PowerTrends.tsx      (Chart.js visualization)
└── Footer                       (Links, version, attribution)
```

### Key Components

#### `HomePage.tsx`
**Purpose**: Main application container and layout
**Features**:
- Fetches all data using `useApi` hook
- Manages tab navigation
- Provides loading and error states
- Renders footer with dynamic version

#### `AlliancePodium.tsx`
**Purpose**: Display top 3 alliances in podium style
**Features**:
- Gold/Silver/Bronze styling
- Automatic rank calculation by power
- Responsive grid layout

#### `AllianceGrid.tsx`
**Purpose**: Display all NAP15 alliances in cards
**Features**:
- Expandable cards with additional info
- Power ranking badges
- R5 leader display
- Signature status

#### `PowerTrends.tsx`
**Purpose**: Visualize alliance power over time
**Features**:
- Chart.js line chart
- Dual-range slider using rc-slider (select rank range to display)
- iOS-style slider with smooth animations and hover effects
- Season 1 filter (Sep 29 - Nov 23, 2025)
- Time-based x-axis with proper date spacing
- Dynamic color assignment
- Dark mode support

#### `ServerRules.tsx`
**Purpose**: Display server rules with amendment tracking
**Features**:
- Accordion (collapsible sections)
- Toggle switch for diff view
- Amendment visualization (add/remove/modify)
- Color-coded changes (green/red/yellow)
- Version chips

#### `CouncilMembers.tsx`
**Purpose**: Show council voting members and rotation
**Features**:
- Permanent members (top 5)
- Current rotating members (ranks 6-7)
- Previous week display
- Collapsible next 4 weeks (Disclosure component)
- Week highlighting

#### `FloatingThemeToggle.tsx`
**Purpose**: Dark/light mode switcher
**Features**:
- Fixed position (top-right)
- Sun/moon icons
- Persists to localStorage
- Updates CSS variables and data-theme attribute

#### `BackToTop.tsx`
**Purpose**: Scroll-to-top button
**Features**:
- Appears after scrolling 300px
- Fixed position (bottom-right)
- Smooth scroll animation

---

## Data Flow

### Data Fetching Pattern

```typescript
// Custom hook for data fetching
const { data, loading, error } = useApi<AllianceType>('alliances.json');

// Hook implementation
export function useApi<T>(endpoint: string) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    fetch(`/data/${endpoint}`)
      .then(res => res.json())
      .then(setData)
      .catch(setError)
      .finally(() => setLoading(false));
  }, [endpoint]);

  return { data, loading, error };
}
```

### Data Files

#### `data/alliances.json`
```typescript
interface Alliance {
  tag: string;
  name: string;
  r5: {
    name: string | null;
    gameId: string | null;
    discordId: string | null;
  };
  power: number;
  signed: boolean;
  // ... additional fields
}
```

#### `data/council.json`
```typescript
interface CouncilMember {
  tag: string;
  name: string;
  seat: 'permanent' | 'rotating';
  startDate?: string;
}
```

#### `data/rotation-schedule.json`
```typescript
interface RotationSchedule {
  currentWeekNumber: number;
  schedule: Array<{
    weekNumber: number;
    startDate: string;
    rotatingMembers: string[];
  }>;
}
```

#### `data/power-history.csv`
```csv
datetime,ORCE,STR8,UvvU,EPIC,NKOT,...
2025-11-11 01:27:00,8783088512,6775724571,...
```

#### `data/rules.json`
```typescript
interface RuleSection {
  title: string;
  content: string[];
  amendments: Array<{
    version: string;
    date: string;
    changes: Array<{
      type: 'add' | 'remove' | 'modify';
      text: string;
    }>;
  }>;
}
```

---

## Styling & Theming

### Theme Architecture

The site uses a custom HeroUI v3 theme defined in `client/src/styles/custom-theme.css`.

#### Theme Tokens

**Light Mode**:
```css
[data-theme="server1586"] {
  --accent: oklch(0.78 0.10 200);      /* Cyan */
  --accent-foreground: oklch(0.15 0.02 240);
  --background: oklch(0.99 0.01 200);
  --foreground: oklch(0.20 0.02 240);
}
```

**Dark Mode**:
```css
[data-theme="server1586-dark"] {
  --accent: oklch(0.72 0.22 35);       /* Orange */
  --accent-foreground: var(--white);
  --background: oklch(0.18 0.03 240);  /* Dark navy */
  --foreground: oklch(0.95 0.01 200);
}
```

### Color System

**Light Mode Colors**:
- Primary/Accent: Cyan from "LAST" logo
- Background: Very light with cyan hint
- Text: Dark navy

**Dark Mode Colors**:
- Primary/Accent: Orange from "WAR" logo and flame
- Background: Dark navy
- Text: Light cyan-tinted

### Responsive Breakpoints

Tailwind CSS default breakpoints:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

---

## Build & Deployment

### Build Process

```bash
# Development build (with HMR)
npm run dev

# Production build
npm run build

# Preview production build locally
npm run preview
```

### Build Output

```
client/dist/
├── index.html              # Entry point with asset links
├── assets/
│   ├── index-[hash].js     # Main JS bundle (~620 KB)
│   └── index-[hash].css    # Compiled CSS (~91 KB)
└── vite.svg                # Favicon
```

### Optimization Features

- **Code Splitting**: Automatic chunk splitting by Vite
- **Tree Shaking**: Unused code removed
- **Minification**: JS and CSS minified
- **Hashing**: Asset filenames include content hash for caching
- **Compression**: Gzip-ready (191 KB JS, 13 KB CSS gzipped)

### Deployment Steps

1. **Build**: `npm run build` in `client/` directory
2. **Copy**: Move `dist/index.html` to root, `dist/assets/*` to `assets/`
3. **Upload**: Deploy `index.html`, `assets/`, `data/`, `images/` to server

---

## Development Workflow

### Local Development

```bash
# Install dependencies
cd client
npm install

# Start dev server
npm run dev

# Open browser to http://localhost:5173
```

### Hot Module Replacement (HMR)

Vite provides instant HMR:
- **React Fast Refresh**: Component updates without page reload
- **CSS Hot Update**: Style changes applied instantly
- **State Preservation**: Component state maintained during updates

### Type Checking

```bash
# Run TypeScript compiler in check mode
npm run type-check

# Watch mode
npm run type-check --watch
```

### Linting

```bash
# Run ESLint
npm run lint

# Fix auto-fixable issues
npm run lint -- --fix
```

### Adding New Features

1. **Create component** in `client/src/components/`
2. **Define types** in `client/src/types.ts` or component file
3. **Import HeroUI components**: `import { Card } from '@heroui/react'`
4. **Use compound patterns**: Follow HeroUI v3 API
5. **Style with Tailwind**: Use utility classes
6. **Test locally**: Verify in dev server
7. **Build**: Run `npm run build` to check for errors

### Data Updates

1. **Edit JSON files** in `data/` directory
2. **Reload browser** (dev server auto-reloads)
3. **Verify changes** in UI
4. **Rebuild** if needed: `npm run build`

---

## Performance Considerations

### Bundle Size
- **Total JS**: ~620 KB (minified), ~191 KB (gzipped)
- **Total CSS**: ~91 KB (minified), ~13 KB (gzipped)
- **Initial Load**: < 1 second on fast connection

### Optimization Opportunities
- **Dynamic Imports**: Code-split Chart.js (only load on Power Trends tab)
- **Image Optimization**: Compress logo and alliance images
- **Font Subsetting**: Load only required character sets
- **Service Worker**: Cache static assets for offline access

### Loading Strategy
- **Critical CSS**: Inline critical styles in `<head>`
- **Async JS**: Load non-critical JS asynchronously
- **Preload**: Preload critical resources (fonts, data files)

---

## Browser Support

### Supported Browsers
- **Chrome**: Latest 2 versions
- **Firefox**: Latest 2 versions
- **Safari**: Latest 2 versions
- **Edge**: Latest 2 versions

### Required Features
- ES6 Modules
- CSS Custom Properties
- Fetch API
- LocalStorage
- CSS Grid & Flexbox

---

## Accessibility

### ARIA Compliance
- **HeroUI Components**: Built on React Aria with full ARIA support
- **Semantic HTML**: Proper heading hierarchy, landmarks
- **Keyboard Navigation**: Full keyboard support
- **Focus Management**: Visible focus indicators

### Color Contrast
- **WCAG AA**: All text meets contrast requirements
- **Dark Mode**: Enhanced contrast for readability

---

## Known Limitations

1. **HeroUI v3 Alpha**: API may change before stable release
2. **No SSR**: Client-side rendering only (SPA)
3. **Static Data**: No real-time updates (refresh required)
4. **Large Bundle**: HeroUI and Chart.js increase bundle size

---

## Future Enhancements

### Planned Features
- **Service Worker**: Offline support and caching
- **Progressive Web App**: Install to home screen
- **Lazy Loading**: Dynamic imports for Chart.js
- **Image Optimization**: WebP format with fallbacks
- **Analytics**: User behavior tracking (privacy-respecting)

### Potential Improvements
- **React Query**: Better data fetching and caching
- **Suspense**: React 18 concurrent features
- **Error Boundaries**: Better error handling and recovery
- **E2E Testing**: Playwright or Cypress tests

---

## Troubleshooting

### Common Issues

**Issue**: Components not rendering
**Solution**: Check TypeScript errors with `npm run type-check`

**Issue**: Styles not applying
**Solution**: Verify Tailwind classes, check `custom-theme.css`

**Issue**: Data not loading
**Solution**: Check `/data/` files exist, verify JSON syntax

**Issue**: Build fails
**Solution**: Clear `node_modules` and reinstall: `rm -rf node_modules && npm install`

**Issue**: Dark mode not working
**Solution**: Check localStorage value: `localStorage.getItem('theme')`

---

## Resources

### Documentation
- **React**: https://react.dev/
- **TypeScript**: https://www.typescriptlang.org/
- **Vite**: https://vitejs.dev/
- **HeroUI v3**: https://v3.heroui.com/
- **Tailwind CSS v4**: https://tailwindcss.com/
- **Chart.js**: https://www.chartjs.org/

### Tools
- **VS Code**: Recommended IDE with ESLint and TypeScript extensions
- **React DevTools**: Browser extension for debugging
- **Vite DevTools**: Inspect bundle and dependencies

---

**Maintained by**: Server 1586 Council
**Last Reviewed**: November 11, 2025
**Architecture Version**: 3.7.0

🤖 Generated with [Claude Code](https://claude.com/claude-code) · Enhanced by [Kiro](https://kiro.dev/)
