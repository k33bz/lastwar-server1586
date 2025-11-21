# Public API Documentation

**Version:** 1.0.0
**Date:** 2025-10-29
**Architecture:** Control/Data Plane Separation

---

## Overview

The Server 1586 Public API provides read-only access to alliance data, server rules, council information, and version details. The API implements control/data plane separation for security and scalability.

### Architecture Pattern

```
┌─────────────────────────────────────────────┐
│          CONTROL PLANE (Admin)              │
│  ┌────────────┐                             │
│  │  Admin     │  Authenticated Write        │
│  │  Dashboard │  Operations (JWT Auth)      │
│  └──────┬─────┘                             │
│         │                                    │
│         ▼                                    │
│  ┌────────────┐         ┌──────────┐       │
│  │  Write API │────────▶│ JSON     │       │
│  │  (Locked)  │  LOCK_EX│ Files    │       │
│  └────────────┘         └────┬─────┘       │
└────────────────────────────────┼───────────┘
                                 │
                                 │ (Shared Storage)
                                 │
┌────────────────────────────────┼───────────┐
│          DATA PLANE (Public)   │           │
│                                 │           │
│  ┌────────────┐         ┌──────▼─────┐    │
│  │  Public    │  LOCK_SH│  JSON      │    │
│  │  Read API  │◀────────│  Files     │    │
│  └──────┬─────┘         └────────────┘    │
│         │                                   │
│         ▼                                   │
│  ┌────────────┐                            │
│  │  Website   │  Unauthenticated Read      │
│  │  /api/*    │  Operations (Public)       │
│  └────────────┘                            │
└─────────────────────────────────────────────┘
```

### Key Features

- ✅ **Read-Only**: Public API has no write access
- ✅ **CORS Enabled**: Accessible from any origin
- ✅ **HTTP Caching**: Reduces server load with cache headers
- ✅ **ETag Support**: Efficient conditional requests (304 Not Modified)
- ✅ **File Locking**: Admin writes use exclusive locks, API uses shared locks
- ✅ **JSON Format**: Standard REST responses
- ✅ **No Authentication**: Public data, no API keys required

---

## Base URL

```
Production: https://www.example.com/api/
Local Dev:  http://localhost:8000/api/
```

---

## Endpoints

### 1. GET /api/alliances.php

Returns current top 15 alliance rankings with power, R5, and signature status.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": [
    {
      "rank": 1,
      "tag": "UvvU",
      "name": "veni vidi vici",
      "power": 7804360932,
      "r5": "R5 Name",
      "signed": true
    }
  ]
}
```

**Cache:** 60 seconds
**ETag:** Supported

---

### 2. GET /api/rules.php

Returns server rules and NAP15 agreements.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": [
    {
      "category": "NAP15 Overview",
      "description": "...",
      "items": ["...", "..."]
    }
  ]
}
```

**Cache:** 300 seconds (5 minutes)
**ETag:** Supported

---

### 3. GET /api/amendments.php

Returns history of rule changes and amendments.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": [
    {
      "version": "1.2",
      "date": "2025-10-05",
      "title": "Rule Title",
      "changes": [...]
    }
  ]
}
```

**Cache:** 300 seconds (5 minutes)
**ETag:** Supported

---

### 4. GET /api/council.php

Returns current week's voting council members (permanent + rotating).

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": {
    "weekNumber": 20,
    "rotationDate": "2025-10-13T02:00:00.000Z",
    "permanentMembers": [...],
    "rotatingMembers": [...],
    "totalSeats": 7
  }
}
```

**Cache:** 60 seconds
**ETag:** Supported

---

### 5. GET /api/council/schedule.php

Returns council rotation schedule for upcoming weeks.

**Query Parameters:**
- `weeks` (optional): Number of future weeks to return (default: 5, max: 52)

**Example:** `/api/council/schedule.php?weeks=10`

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": {
    "currentWeek": 20,
    "weeksShown": 10,
    "schedule": [
      {
        "weekNumber": 20,
        "startDate": "2025-10-13T02:00:00.000Z",
        "rotatingMembers": ["STR8", "EPIC"]
      }
    ],
    "epoch": {
      "weekOne": "2025-05-18",
      "time": "22:00 EDT (02:00 UTC)",
      "rotationDay": "Sunday"
    }
  }
}
```

**Cache:** 300 seconds (5 minutes)
**ETag:** Supported

---

### 6. GET /api/version.php

Returns current version, release date, and component versions.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": {
    "version": "3.2.0",
    "releaseDate": "2025-10-28",
    "components": {
      "frontend": {
        "version": "3.1.0",
        "html": "1.4.0",
        "js": "2.0.1",
        "css": "1.5.0"
      },
      "admin": {
        "version": "3.1.0"
      }
    }
  }
}
```

**Cache:** 300 seconds (5 minutes)
**ETag:** Supported

---

### 7. GET /api/server-info.php

Returns server metadata, Discord info, and NAP15 details.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": {
    "serverId": 1586,
    "name": "Last War Server 1586",
    "discord": {
      "inviteUrl": "https://discord.gg/...",
      "serverName": "..."
    },
    "nap15": {
      "active": true,
      "memberCount": 15
    }
  }
}
```

**Cache:** 3600 seconds (1 hour)
**ETag:** Supported

---

### 8. GET /api/power-history.php

Returns historical power data for alliances in CSV format.

**Response:**
```csv
Date,UvvU,ORCE,MTOP,FNXS,MZKU,STR8,...
2025-10-01,7804360932,6543210987,...
2025-10-08,7920451023,6598432109,...
```

**Format:** CSV (not JSON)
**Cache:** 300 seconds (5 minutes)
**ETag:** Supported

**Note:** Contains only public data (alliance tags and power numbers). No PII.

---

### 9. GET /api/signature-history.php

Returns R5 signature change history for server rules.

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-11-12T12:00:00Z",
  "data": {
    "currentRulesVersion": "1.3",
    "lastUpdated": "2025-11-01T10:00:00Z",
    "alliances": [
      {
        "tag": "UvvU",
        "currentR5": "Leader Name",
        "signed": true,
        "signatureHistory": [
          {
            "date": "2025-10-01",
            "r5Name": "Previous Leader",
            "action": "signed",
            "rulesVersion": "1.2"
          }
        ]
      }
    ]
  }
}
```

**Cache:** 60 seconds
**ETag:** Supported

---

### 10. GET /api/profile_api.php?action=search

Search for user profile by alliance and game name.

**Query Parameters:**
- `action=search` (required)
- `alliance` - Alliance tag (required)
- `name` - In-game name (required)

**Example:** `/api/profile_api.php?action=search&alliance=UvvU&name=PlayerName`

**Response:**
```json
{
  "found": true,
  "profile": {
    "profile_id": "prof_20251110_a1b2c3d4",
    "alliance_tag": "UvvU",
    "game_name": "PlayerName",
    "discord_id": "123456789012345678",
    "discord_tag": "username#1234",
    "role": "member",
    "verified": false,
    "created_at": "2025-11-10T12:00:00Z",
    "updated_at": "2025-11-10T12:00:00Z"
  }
}
```

**Cache:** No caching (real-time lookups)
**Authentication:** Not required (public self-service)

---

### 11. POST /api/profile_api.php

Create or update user profile.

**Request Body (Create):**
```json
{
  "action": "create",
  "alliance_tag": "UvvU",
  "game_name": "PlayerName"
}
```

**Request Body (Update):**
```json
{
  "action": "update",
  "profile_id": "prof_20251110_a1b2c3d4",
  "discord_id": "123456789012345678",
  "discord_tag": "username#1234"
}
```

**Response:**
```json
{
  "success": true,
  "profile": { ... }
}
```

**Authentication:** Not required (public self-service)

---

### 12. POST /api/alliance_r5_profile_api.php

Update R5 Discord ID in alliance data (self-service for R5s).

**Request Body:**
```json
{
  "alliance_tag": "UvvU",
  "discord_id": "123456789012345678"
}
```

**Response:**
```json
{
  "success": true,
  "message": "R5 Discord ID updated successfully",
  "alliance": {
    "tag": "UvvU",
    "r5": {
      "name": "R5 Name",
      "discordId": "123456789012345678"
    }
  }
}
```

**Authentication:** Not required (public self-service)
**Note:** Validates Discord ID format (17-19 digits)

---

### 13. POST /api/alliance_r4_profile_api.php

Update R4 Discord ID in alliance data (self-service for R4s).

**Request Body:**
```json
{
  "alliance_tag": "UvvU",
  "r4_name": "Officer Name",
  "discord_id": "123456789012345678"
}
```

**Response:**
```json
{
  "success": true,
  "message": "R4 Discord ID updated successfully",
  "r4": {
    "name": "Officer Name",
    "discordId": "123456789012345678",
    "canVote": false,
    "role": "Deputy"
  }
}
```

**Authentication:** Not required (public self-service)
**Note:** Validates Discord ID format (17-19 digits)

---

## Response Format

### Success Response

```json
{
  "success": true,
  "timestamp": "2025-10-29T12:00:00Z",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "error": "Error message",
  "timestamp": "2025-10-29T12:00:00Z"
}
```

**HTTP Status Codes:**
- `200 OK` - Success
- `304 Not Modified` - Client has cached version (ETag match)
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Server error

---

## Usage Examples

### JavaScript (Fetch API)

```javascript
// Simple fetch
fetch('/api/alliances.php')
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      console.log(result.data);
    }
  });

// With caching and error handling
async function getAlliances() {
  try {
    const response = await fetch('/api/alliances.php', {
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      return result.data;
    } else {
      throw new Error(result.error);
    }
  } catch (error) {
    console.error('API Error:', error);
    return null;
  }
}
```

### JavaScript (with ETag)

```javascript
// Cache ETag in sessionStorage
let cachedETag = sessionStorage.getItem('alliances_etag');

fetch('/api/alliances.php', {
  headers: {
    'If-None-Match': cachedETag || ''
  }
})
.then(response => {
  if (response.status === 304) {
    // Use cached data
    return JSON.parse(sessionStorage.getItem('alliances_data'));
  }

  // Store new ETag
  const newETag = response.headers.get('ETag');
  if (newETag) {
    sessionStorage.setItem('alliances_etag', newETag);
  }

  return response.json().then(result => {
    sessionStorage.setItem('alliances_data', JSON.stringify(result.data));
    return result.data;
  });
});
```

### cURL

```bash
# Basic request
curl https://www.example.com/api/alliances.php

# Pretty print JSON
curl https://www.example.com/api/alliances.php | json_pp

# With headers
curl -i https://www.example.com/api/alliances.php

# With ETag
curl -H "If-None-Match: \"abc123\"" https://www.example.com/api/alliances.php
```

### Python

```python
import requests

# Simple request
response = requests.get('https://www.example.com/api/alliances.php')
data = response.json()

if data['success']:
    alliances = data['data']
    print(f"Top alliance: {alliances[0]['name']}")

# With caching
session = requests.Session()
response = session.get('https://www.example.com/api/alliances.php')

# Subsequent requests will use cached ETag automatically
response2 = session.get('https://www.example.com/api/alliances.php')
if response2.status_code == 304:
    print("Using cached version")
```

---

## Caching Strategy

### HTTP Cache Headers

All endpoints return standard cache headers:

```
Cache-Control: public, max-age=60
Expires: Mon, 29 Oct 2025 12:01:00 GMT
ETag: "abc123def456"
```

### Cache Duration by Endpoint

| Endpoint | Cache Duration | Rationale |
|----------|----------------|-----------|
| /api/alliances.php | 60s | Power updates frequently |
| /api/council.php | 60s | Changes weekly, but check often |
| /api/signature-history.php | 60s | R5 changes tracked in real-time |
| /api/rules.php | 300s | Rules rarely change |
| /api/amendments.php | 300s | Amendments are historical |
| /api/council/schedule.php | 300s | Schedule pre-generated |
| /api/power-history.php | 300s | Historical data, updated weekly |
| /api/version.php | 300s | Versions change on deployment |
| /api/server-info.php | 3600s | Static server information |
| /api/profile_api.php | No cache | Real-time profile lookups |
| /api/alliance_r5_profile_api.php | No cache | Immediate updates |
| /api/alliance_r4_profile_api.php | No cache | Immediate updates |

### Client-Side Caching Recommendations

1. **Respect Cache-Control headers** - Use browser cache
2. **Implement ETag support** - Send If-None-Match header
3. **Use sessionStorage** - Cache for user session
4. **Avoid polling** - Use cache duration as minimum delay

---

## File Locking (Technical)

### Admin Panel (Control Plane)

```php
// Exclusive lock for writes
$handle = fopen($file, 'c+');
flock($handle, LOCK_EX);  // Blocks all other access
fwrite($handle, $json);
flock($handle, LOCK_UN);
fclose($handle);
```

### Public API (Data Plane)

```php
// Shared lock for reads
$handle = fopen($file, 'r');
flock($handle, LOCK_SH);  // Multiple readers allowed
$data = fread($handle, filesize($file));
flock($handle, LOCK_UN);
fclose($handle);
```

**Benefits:**
- Prevents read/write conflicts
- Multiple readers can access simultaneously
- Writers get exclusive access
- No race conditions

---

## CORS Configuration

All endpoints support Cross-Origin Resource Sharing (CORS):

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

This allows the API to be consumed from:
- External websites
- Mobile applications
- Browser extensions
- Third-party tools

---

## Interactive Documentation

Visit `/api/` in your browser for an interactive API testing interface.

**Features:**
- Live endpoint testing
- JSON response preview
- Cache header inspection
- Example code snippets

---

## Migration from Direct JSON Access

### Before (Direct File Access)

```javascript
fetch('/data/alliances.json')
  .then(r => r.json())
  .then(data => console.log(data));
```

### After (API Access)

```javascript
fetch('/api/alliances.php')
  .then(r => r.json())
  .then(result => console.log(result.data));
```

**Backward Compatibility:**
Direct JSON file access still works, but API endpoints are recommended for:
- Better caching control
- Consistent response format
- ETag support
- Error handling

---

## Rate Limiting

**Current Status:** No rate limiting implemented

**Future Consideration:**
If API usage grows, consider implementing rate limiting:
- Per-IP: 60 requests/minute
- Burst: Allow 100 requests in 10 seconds

---

## Security

### What's Public

✅ Alliance rankings
✅ Server rules
✅ Council rotation
✅ Version information

### What's NOT Public

❌ User data (admin/users.json)
❌ Audit logs
❌ Security events
❌ Token blacklists
❌ Backup files

### Security Headers

All endpoints include:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Type: application/json; charset=utf-8
```

---

## Monitoring

### Logs

API errors are logged to PHP error log:

```php
error_log("JSON decode error in $file_path");
```

### Health Check

Use `/api/version.php` as a health check endpoint:
- Fast response
- No database dependencies
- Returns 200 OK if system operational

---

## Future Enhancements

Planned features:

- [ ] WebSocket support for real-time updates
- [ ] GraphQL endpoint for flexible queries
- [ ] Rate limiting per IP
- [ ] API usage analytics
- [ ] Webhook notifications for data changes
- [ ] OpenAPI/Swagger documentation

---

## Support

For API issues or questions:
- **GitHub Issues**: https://github.com/username/repo-name/issues
- **Documentation**: https://github.com/username/repo-name/blob/mainline/docs/PUBLIC_API.md

---

**Last Updated:** 2025-11-20
**API Version:** 1.1.0
**Maintained By:** Server 1586 Development Team

**Changelog:**
- v1.1.0 (2025-11-20): Added 6 public endpoints (power-history, signature-history, profile APIs)
- v1.0.0 (2025-10-29): Initial public API documentation
