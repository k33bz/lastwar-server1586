# Multi-Server Deployment Guide

## Overview

As of v3.8.0, the system supports multiple Last War servers (1586, 9999, etc.) with a simple configuration-based approach. Each server deploys its own copy of the React public site with server-specific configuration.

## Architecture

### Public Site (React Client)
- **Each server** deploys its own copy of the React app
- Configured via environment variables (`.env.local`)
- Automatically filters API data for that server only
- Separate domains: `server1586.com`, `server9999.com`, etc.

### Admin Panel (PHP)
- **Single instance** serves all servers
- Shows data from ALL servers with prefixes: `[1586] [ORCE]`, `[9999] [BFG]`
- Users have per-server permissions
- No changes needed for multi-server support

### APIs
- All public APIs accept `?server=` parameter
- Default to `server=1586` for backwards compatibility
- React client automatically includes server parameter

## Deployment Steps

### For Server 1586 (Existing - No Changes)

Server 1586 continues to work as-is with no configuration changes:

```bash
cd client
npm run build
# Deploy dist/ to production
```

The default server ID is `1586`, so no environment variables needed.

### For Server 9999 (New Server)

1. **Clone or build from same repository:**

```bash
cd client

# Option A: Use pre-configured env file
cp .env.9999 .env.local

# Option B: Create custom configuration
cp .env.example .env.local
# Edit .env.local:
#   VITE_SERVER_ID=9999
#   VITE_SERVER_NAME=Server 9999
```

2. **Build for production:**

```bash
npm run build
```

3. **Deploy to server 9999's domain:**

```bash
# Deploy client/dist/ to server9999.com or similar
# Contents:
#   - index.html
#   - assets/
#   - data/ (optional for dev)
```

4. **Point to same admin panel API:**

All servers share the same admin panel and APIs. No backend changes needed.

## Environment Variables

### `VITE_SERVER_ID` (Required)

The Last War server identifier.

- **Server 1586**: `VITE_SERVER_ID=1586`
- **Server 9999**: `VITE_SERVER_ID=9999`
- **Default**: `1586` (if not set)

### `VITE_SERVER_NAME` (Optional)

Human-readable server name for display in the UI.

- **Server 1586**: `VITE_SERVER_NAME="Server 1586"`
- **Server 9999**: `VITE_SERVER_NAME="Server 9999"`
- **Default**: `"Server {ID}"` (e.g., "Server 9999")

### `VITE_API_BASE_URL` (Optional)

Base URL for API endpoints if different from the public site.

- **Same domain**: Leave empty (uses relative paths)
- **Different domain**: `VITE_API_BASE_URL=https://api.lastwar.app`
- **Default**: `""` (relative paths)

## How It Works

### 1. Server Configuration

The `src/config/server.ts` file reads environment variables:

```typescript
export const SERVER_CONFIG = {
  id: import.meta.env.VITE_SERVER_ID || '1586',
  name: import.meta.env.VITE_SERVER_NAME || 'Server 1586',
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL || '',
};
```

### 2. API Calls

The `useApi` hook automatically includes server parameter:

```typescript
// User code (no changes needed):
const { data } = useApi<Alliance[]>('alliances.json');

// In production, fetches:
//   /api/alliances.php?server=1586
//   or
//   /api/alliances.php?server=9999
```

### 3. API Filtering

Server-side APIs filter data by server field:

```php
// api/alliances.php
$server_id = $_GET['server'] ?? '1586';
$alliances = filter_by_server($alliances, $server_id);
```

## Testing Locally

### Test Server 1586

```bash
cd client
npm run dev
# Opens http://localhost:5173
# Uses default server=1586
```

### Test Server 9999

```bash
cd client

# Set environment for dev server
export VITE_SERVER_ID=9999
export VITE_SERVER_NAME="Server 9999"

npm run dev
# Opens http://localhost:5173
# Uses server=9999
```

Or create `.env.local`:

```bash
cp .env.9999 .env.local
npm run dev
```

## Data Migration

Before deploying a new server, ensure the data migration (v3.8.0) has run:

1. **Migration adds `server` field to all records**
   - `data/alliances.json`: `{ "server": "1586", "tag": "ORCE", ... }`
   - `data/discord-votes.json`: Each vote has `server` field
   - `data/notifications.json`: Each notification has `server` field

2. **Migration converts user permissions**
   - Old: `{ "alliances": ["ORCE", "TEST"], "ape": true }`
   - New: `{ "servers": { "1586": { "alliances": ["ORCE"], "ape": true } } }`

3. **Add server 9999 data**

Through admin panel or manually:

```json
// data/alliances.json
[
  { "server": "1586", "tag": "ORCE", "name": "Oracle", "power": 12500000 },
  { "server": "9999", "tag": "BFG", "name": "Big Friendly Giant", "power": 15000000 }
]
```

## Deployment Checklist

### New Server Deployment

- [ ] Run migration (v3.8.0) to add `server` fields to data
- [ ] Add server's alliance data via admin panel
- [ ] Configure environment variables (`.env.local`)
- [ ] Build production bundle (`npm run build`)
- [ ] Deploy `dist/` to server's domain
- [ ] Test API calls include correct `?server=` parameter
- [ ] Verify only server's data is displayed

### Existing Server (1586)

- [ ] No changes required
- [ ] Backwards compatible with existing deployment
- [ ] Default `server=1586` maintains current behavior

## Troubleshooting

### Issue: Seeing wrong server's data

**Solution**: Check environment variables in build:

```bash
# Verify build used correct env
cat dist/assets/index-*.js | grep "VITE_SERVER_ID"

# Should see: server:1586 or server:9999
```

### Issue: API returns empty results

**Cause**: API may not have data for requested server yet

**Solution**:
1. Check API response: `/api/alliances.php?server=9999`
2. Verify data has `server` field in JSON files
3. Ensure migration (v3.8.0) has run

### Issue: Both servers show same data

**Cause**: Environment variable not set or build cached

**Solution**:
```bash
# Clear build cache
rm -rf dist/

# Verify .env.local
cat .env.local

# Rebuild with correct config
npm run build
```

## API Endpoints

All public APIs support `?server=` parameter:

- `/api/alliances.php?server=1586`
- `/api/council.php?server=9999`
- `/api/rotation-schedule.php?server=1586`
- `/api/signature-history.php?server=9999`

**Note**: APIs default to `server=1586` if parameter is missing (backwards compatibility).

## Future Enhancements

Potential improvements for Phase 2:

1. **Server selector UI** - Allow users to switch servers in browser
2. **Cross-server comparisons** - Show data from multiple servers side-by-side
3. **Shared admin panel URL** - All servers link to same admin domain
4. **Server list API** - Dynamic list of available servers

## See Also

- [MULTI_SERVER_MIGRATION.md](../MULTI_SERVER_MIGRATION.md) - Complete migration plan
- [.env.example](..env.example) - Environment variable reference
- [src/config/server.ts](src/config/server.ts) - Server configuration code
