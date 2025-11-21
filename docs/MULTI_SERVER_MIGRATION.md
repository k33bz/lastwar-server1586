# Multi-Server Migration Plan

## Overview
Enable the admin panel and APIs to support multiple Last War servers (1586, 9999, etc.) with minimal code changes.

## Architecture

### Public Sites
- Each server deploys their own copy of the React app
- Separate domains: `server1586.com`, `server9999.com`
- Each has a config file specifying their `SERVER_ID`
- APIs receive `server` parameter in all requests

### Admin Site
- Single admin panel at `admin.lastwar.app` (or wherever)
- Shows data from ALL servers
- Server prefixes in UI: `[1586] [ORCE]`, `[9999] [BFG]`
- Users have per-server permissions

## Migration Steps

### Phase 1: Data Schema Migration

#### Files to Update:
1. `data/alliances.json` - Add `server` field
2. `data/users.json` - Change permissions to per-server
3. `data/discord-votes.json` - Add `server` field
4. `data/discord-vote-requests.json` - Add `server` field
5. `data/rotation-schedule.json` - Add `server` field
6. `data/signature-history.json` - Add `server` field
7. `data/notifications.json` - Add `server` field
8. `admin/audit_log.json` - Add `server` field to events

#### Script: `scripts/migrate_to_multiserver.php`
```php
<?php
/**
 * Migrate existing data to multi-server format
 * Adds 'server' field to all records, defaulting to '1586'
 */

// Read existing alliances
$alliances = json_decode(file_get_contents('data/alliances.json'), true);

// Add server field to each alliance
foreach ($alliances as &$alliance) {
    if (!isset($alliance['server'])) {
        $alliance['server'] = '1586';
    }
}

// Save updated data
file_put_contents('data/alliances.json', json_encode($alliances, JSON_PRETTY_PRINT));

// Repeat for other files...
```

### Phase 2: API Updates

#### All API Files to Update:
- `api/alliances.php` - Filter by server
- `api/power-history.php` - Filter by server
- `api/rotation-schedule.php` - Filter by server
- `api/signature-history.php` - Filter by server
- `admin/notifications_api.php` - Filter by server
- `admin/discord_votes_api.php` - Filter by server
- `admin/alliances_api.php` - Filter by server

#### Example API Change:
```php
// Before:
$alliances = read_json_file(ALLIANCES_FILE);

// After:
$server = $_GET['server'] ?? '1586'; // Default to 1586 for backwards compat
$alliances = read_json_file(ALLIANCES_FILE);
$alliances = array_filter($alliances, function($a) use ($server) {
    return $a['server'] === $server;
});
```

### Phase 3: Admin Panel Updates

#### UI Changes:
1. **Alliance Lists** - Show server prefix
   ```php
   // Display: [1586] [ORCE] Oracle
   echo "[{$alliance['server']}] [{$alliance['tag']}] {$alliance['name']}";
   ```

2. **Add Server Selector** - Dropdown to filter by server
   ```html
   <select id="serverFilter">
     <option value="all">All Servers</option>
     <option value="1586">Server 1586</option>
     <option value="9999">Server 9999</option>
   </select>
   ```

3. **User Permissions** - Update user management UI
   ```html
   <h4>Server Permissions</h4>
   <div class="server-permissions">
     <div class="server-group">
       <h5>Server 1586</h5>
       <input type="text" name="servers[1586][alliances]" value="ORCE,TEST">
       <label><input type="checkbox" name="servers[1586][ape]"> APE Access</label>
     </div>
     <div class="server-group">
       <h5>Server 9999</h5>
       <input type="text" name="servers[9999][alliances]" value="BFG">
       <label><input type="checkbox" name="servers[9999][ape]"> APE Access</label>
     </div>
   </div>
   ```

#### Files to Update:
- `admin/alliances_power.php` - Add server selector, show server prefixes
- `admin/alliance_edit.php` - Add server field to form
- `admin/user_management.php` - Update permissions UI
- `admin/discord_vote_proposals.php` - Add server field
- `admin/president_vote_approvals.php` - Show server in list
- `admin/council_rotation.php` - Add server selector
- `admin/includes/header.php` - Add global server filter

### Phase 4: Public Site Updates

#### Add Server Config:
```typescript
// client/src/config/server.ts
export const SERVER_CONFIG = {
  id: import.meta.env.VITE_SERVER_ID || '1586',
  name: import.meta.env.VITE_SERVER_NAME || 'Server 1586',
  apiUrl: import.meta.env.VITE_API_URL || '/api'
};
```

#### Update API Calls:
```typescript
// client/src/services/api.ts
import { SERVER_CONFIG } from '../config/server';

export async function fetchAlliances() {
  const response = await fetch(
    `${SERVER_CONFIG.apiUrl}/alliances.php?server=${SERVER_CONFIG.id}`
  );
  return response.json();
}
```

#### Environment Files:
```bash
# Server 1586 deployment
# .env.1586
VITE_SERVER_ID=1586
VITE_SERVER_NAME="Server 1586"
VITE_API_URL=https://api.lastwar1586.online

# Server 9999 deployment
# .env.9999
VITE_SERVER_ID=9999
VITE_SERVER_NAME="Server 9999"
VITE_API_URL=https://api.lastwar9999.online
```

### Phase 5: JWT & Auth Updates

#### Update JWT Claims:
```php
// jwt.php - generate_jwt()
$payload = [
    'sub' => $email,
    'aud' => $role,
    'servers' => $user['servers'], // Add server permissions to token
    // ... existing claims
];
```

#### Update Permission Checks:
```php
// jwt.php - has_alliance_access()
function has_alliance_access($user, $alliance_tag, $server) {
    // Admin with wildcard
    if ($user->aud === 'admin' &&
        isset($user->servers->{$server}) &&
        in_array('*', $user->servers->{$server}->alliances)) {
        return true;
    }

    // Check specific server + alliance access
    if (isset($user->servers->{$server}) &&
        in_array($alliance_tag, $user->servers->{$server}->alliances)) {
        return true;
    }

    return false;
}
```

### Phase 6: Testing

#### Test Cases:
1. **Data Isolation**
   - Server 1586 public site only shows 1586 data
   - Server 9999 public site only shows 9999 data
   - Admin panel shows both with correct prefixes

2. **User Permissions**
   - R5 with 1586 access cannot edit 9999 alliances
   - Admin can manage both servers
   - Users see only their authorized servers

3. **API Filtering**
   - `/api/alliances.php?server=1586` returns only 1586 data
   - `/api/alliances.php?server=9999` returns only 9999 data
   - Missing `server` param defaults to 1586 (backwards compat)

4. **Discord Integration**
   - Server 1586 bot only sees 1586 votes
   - Server 9999 bot only sees 9999 votes
   - Vote proposals go to correct server's council

## Deployment Strategy

### Server 1586 (Existing)
- No changes to domain/deployment
- Uses `SERVER_ID=1586` in env
- Continues working as-is

### Server 9999 (New)
1. Copy React app to new repo or build with `.env.9999`
2. Deploy to `server9999.com` or similar
3. Point to same admin panel API
4. Import server 9999 data through admin panel

### Admin Panel
- Deploy once, serves all servers
- No code changes needed after migration
- Add new servers by importing data

## Backwards Compatibility

All APIs default to `server=1586` if parameter is missing, ensuring existing deployments continue working without changes.

## Estimated Timeline

- Phase 1 (Data Migration): 2-4 hours
- Phase 2 (API Updates): 4-6 hours
- Phase 3 (Admin UI): 6-8 hours
- Phase 4 (Public Site): 2-4 hours
- Phase 5 (Auth Updates): 2-3 hours
- Phase 6 (Testing): 4-6 hours

**Total: 20-31 hours** (2.5-4 days of focused work)

## Next Steps

1. Review this plan with server 9999 team
2. Create data backup before migration
3. Run Phase 1 migration script in staging
4. Test with both server data
5. Deploy to production
6. Onboard server 9999
