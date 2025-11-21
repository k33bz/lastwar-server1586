# Admin API Documentation

**Version:** 1.0.0
**Date:** 2025-11-20
**Base URL:** `/admin/`

---

## Overview

The Server 1586 Admin API provides authenticated access to alliance management, user management, Discord integration, security monitoring, and system administration. All admin API endpoints require JWT authentication and implement CSRF protection for state-changing operations.

### Key Features

- 🔐 **JWT Authentication**: All endpoints require valid session token
- 🛡️ **CSRF Protection**: POST/PUT/DELETE require CSRF token
- 👥 **Role-Based Access**: Fine-grained permissions (admin, president, r5, r4, ape)
- 📝 **Audit Logging**: All actions logged with timestamp, user, IP, action
- 🔒 **Secure Sessions**: HTTP-only secure cookies
- ⚡ **JSON Responses**: Standard REST API format

---

## Authentication

### JWT Session Authentication

All admin API endpoints require a valid JWT session token passed as an HTTP-only secure cookie.

**Cookie Name:** `session_token`
**Token Type:** Session JWT (30-day expiry)
**Algorithm:** HMAC-SHA256

**Token Claims:**
```json
{
  "sub": "usr_XXXXXXXXXX",      // User UID
  "email": "user@example.com",  // User email
  "roles": ["admin", "r5"],     // Array of roles
  "alliances": ["UvvU"],        // Alliance assignments
  "mfa_verified": true,         // MFA status
  "iat": 1700000000,            // Issued at
  "exp": 1702592000             // Expiry
}
```

### Authentication Flow

1. User logs in via magic link (passwordless)
2. Server validates magic link token
3. Server issues session JWT in HTTP-only secure cookie
4. Client includes cookie on all requests via `credentials: 'include'`
5. Server validates JWT on each API call

**JavaScript Example:**
```javascript
fetch('/admin/discord_votes_api.php?action=get_votes', {
    method: 'GET',
    credentials: 'include',  // Required to send JWT cookie
    headers: {
        'Accept': 'application/json'
    }
});
```

---

## CSRF Protection

All state-changing operations (POST, PUT, DELETE, PATCH) require a CSRF token.

**Token Generation:**
```php
$csrf_token = generate_csrf_token();  // Valid for current session
```

**Token Submission:**
```javascript
fetch('/admin/alliance_edit_api.php', {
    method: 'POST',
    credentials: 'include',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken  // Include CSRF token
    },
    body: JSON.stringify(data)
});
```

**Error Response (Missing CSRF):**
```json
{
    "success": false,
    "error": "Invalid CSRF token"
}
```

---

## Role-Based Access Control

### Available Roles

| Role | Description | Access Level |
|------|-------------|--------------|
| `admin` | Full system access | All endpoints, all data |
| `president` | Council president | Vote approvals, council management |
| `r5` | Alliance leader | Own alliance data, rule signing |
| `r4` | Alliance officer | Own alliance data (no signing) |
| `ape` | Alliance Power Editor | All alliance power values |
| `none` | Read-only | View-only access |
| `disabled` | Account suspended | No access |

### Multi-Role Support

Users can have multiple roles: `["r5", "ape"]`

**Role Checking:**
```php
// Single role
if (!has_role($token, 'admin')) {
    return error('Unauthorized');
}

// Multiple roles (OR logic)
if (!has_role($token, ['admin', 'r5'])) {
    return error('Must be admin or R5');
}

// Alliance-specific access
if (!has_alliance_access($token, 'UvvU')) {
    return error('No access to this alliance');
}
```

---

## API Endpoints

### Alliance Management

#### 1. POST /admin/alliance_edit_api.php

Update alliance data (power, R5, R4s, tags, signature status).

**Required Roles:** `admin`, `r5` (own alliance only), `r4` (own alliance only)
**CSRF Required:** Yes

**Request:**
```json
{
    "tag": "UvvU",
    "name": "veni vidi vici",
    "power": 7804360932,
    "r5": {
        "name": "Leader Name",
        "discordId": "123456789012345678"
    },
    "r4s": [
        {
            "name": "Officer Name",
            "discordId": "987654321098765432",
            "canVote": true,
            "role": "Deputy"
        }
    ],
    "signed": true,
    "tags": ["aggressive", "competitive"]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Alliance updated successfully"
}
```

**Permissions:**
- **Admin**: Can edit any alliance
- **R5/R4**: Can only edit own alliance (based on `token.alliances`)
- **R5 only**: Can update `signed` field (rule signature)

---

#### 2. POST /admin/alliances_power_api.php

Bulk update power values for all alliances.

**Required Roles:** `admin`, `ape`
**CSRF Required:** Yes

**Request:**
```json
{
    "alliances": [
        { "tag": "UvvU", "power": 7920000000 },
        { "tag": "ORCE", "power": 6780000000 }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "updated_count": 2,
    "message": "Power values updated successfully"
}
```

---

#### 3. POST /admin/alliance_delete_api.php

Delete an alliance from the system.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "tag": "OLD"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Alliance deleted successfully"
}
```

---

#### 4. GET /admin/alliance_tags_api.php

Manage alliance tags and categories.

**Required Roles:** `admin`, `r5`, `r4`
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "tags": [
        { "id": "aggressive", "label": "Aggressive", "category": "playstyle" },
        { "id": "defensive", "label": "Defensive", "category": "playstyle" }
    ],
    "categories": [
        { "id": "playstyle", "name": "Play Style", "color": "#3498db" }
    ]
}
```

---

### User Management

#### 5. GET /admin/user_management_api.php?action=list

List all admin panel users.

**Required Roles:** `admin` only
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "users": [
        {
            "email": "user@example.com",
            "roles": ["r5", "ape"],
            "alliances": ["UvvU"],
            "mfa_enabled": true,
            "created_at": "2025-10-01T12:00:00Z",
            "last_login": "2025-11-20T08:30:00Z"
        }
    ]
}
```

---

#### 6. POST /admin/user_management_api.php?action=create

Create a new user account.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "email": "newuser@example.com",
    "roles": ["r5"],
    "alliances": ["UvvU"]
}
```

**Response:**
```json
{
    "success": true,
    "message": "User created successfully"
}
```

---

#### 7. POST /admin/user_management_api.php?action=update

Update user roles and alliances.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "email": "user@example.com",
    "roles": ["r5", "ape"],
    "alliances": ["UvvU", "ORCE"]
}
```

---

#### 8. POST /admin/user_management_api.php?action=delete

Delete a user account.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "email": "user@example.com"
}
```

---

#### 9. GET /admin/profile_api.php

Get current user's profile.

**Required Roles:** All authenticated users
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "profile": {
        "uid": "usr_XXXXXXXXXX",
        "email": "user@example.com",
        "roles": ["r5"],
        "alliances": ["UvvU"],
        "display_name": "Username",
        "discord_id": "123456789012345678",
        "mfa_enabled": true,
        "language": "en",
        "created_at": "2025-10-01T12:00:00Z"
    }
}
```

---

#### 10. POST /admin/profile_api.php

Update current user's profile.

**Required Roles:** All authenticated users
**CSRF Required:** Yes

**Request:**
```json
{
    "display_name": "New Name",
    "discord_id": "123456789012345678",
    "language": "es"
}
```

**Note:** Users can only update their own profile. Roles and alliances can only be changed by admins.

---

### Discord Integration

#### 11. GET /admin/discord_votes_api.php?action=get_votes

Get all Discord council votes.

**Required Roles:** `admin`, `president`, `r5`, `r4`, `ape`
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "votes": [
        {
            "id": "vote_20251120_abc123",
            "title": "Rule Change Proposal",
            "description": "Detailed description",
            "category": "rule_change",
            "created_at": "2025-11-20T12:00:00Z",
            "created_by": {
                "discord_id": "123456789012345678",
                "username": "Creator#1234"
            },
            "expires_at": "2025-11-21T12:00:00Z",
            "status": "active",
            "votes": {
                "234567890123456789": {
                    "choice": "yes",
                    "timestamp": "2025-11-20T12:05:00Z",
                    "hash": "sha256_hash_value"
                }
            },
            "vote_message_id": "987654321098765432",
            "result": null
        }
    ]
}
```

---

#### 12. GET /admin/discord_votes_api.php?action=get_requests

Get all vote requests pending approval.

**Required Roles:** `admin`, `president`
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "requests": [
        {
            "request_id": "req_20251120_xyz789",
            "requested_by": {
                "discord_id": "123456789012345678",
                "username": "Member#1234",
                "alliance": "UvvU",
                "role": "R5"
            },
            "vote_details": {
                "title": "Proposal Title",
                "description": "Description",
                "category": "alliance_action"
            },
            "created_at": "2025-11-20T10:00:00Z",
            "status": "pending",
            "president_response": null
        }
    ]
}
```

---

#### 13. POST /admin/discord_votes_api.php?action=create_vote

Create a new Discord vote (bypass request approval).

**Required Roles:** `admin`, `president`
**CSRF Required:** Yes

**Request:**
```json
{
    "title": "Vote Title",
    "description": "Detailed description",
    "category": "rule_change"
}
```

**Response:**
```json
{
    "success": true,
    "vote_id": "vote_20251120_abc123",
    "message": "Vote created successfully"
}
```

**Categories:** `rule_change`, `alliance_action`, `server_event`, `other`

---

#### 14. POST /admin/discord_votes_api.php?action=create_request

Submit a vote request for president approval.

**Required Roles:** `admin`, `president`, `r5`, `r4`, `ape`
**CSRF Required:** Yes

**Request:**
```json
{
    "title": "Proposal Title",
    "description": "Detailed description",
    "category": "alliance_action"
}
```

**Response:**
```json
{
    "success": true,
    "request_id": "req_20251120_xyz789",
    "message": "Request submitted successfully. Auto-approval in 12 hours if not reviewed."
}
```

---

#### 15. POST /admin/discord_votes_api.php?action=approve_request

Approve a vote request and create the vote.

**Required Roles:** `admin`, `president`
**CSRF Required:** Yes

**Request:**
```json
{
    "request_id": "req_20251120_xyz789"
}
```

**Response:**
```json
{
    "success": true,
    "vote_id": "vote_20251120_abc123",
    "message": "Request approved and vote created"
}
```

---

#### 16. POST /admin/discord_votes_api.php?action=reject_request

Reject a vote request with optional reason.

**Required Roles:** `admin`, `president`
**CSRF Required:** Yes

**Request:**
```json
{
    "request_id": "req_20251120_xyz789",
    "reason": "Does not meet criteria for council vote"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Request rejected successfully"
}
```

---

#### 17. GET /admin/discord_channels_api.php

Get Discord channel configurations.

**Required Roles:** `admin`
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "channels": [
        {
            "id": "ch_alliance_UvvU",
            "alliance": "UvvU",
            "channel_id": "123456789012345678",
            "webhook_url": "https://discord.com/api/webhooks/...",
            "enabled": true
        },
        {
            "id": "ch_global_announce",
            "type": "global",
            "channel_id": "987654321098765432",
            "webhook_url": "https://discord.com/api/webhooks/...",
            "enabled": true
        }
    ]
}
```

---

#### 18. POST /admin/discord_channels_api.php

Create or update Discord channel configuration.

**Required Roles:** `admin`
**CSRF Required:** Yes

**Request:**
```json
{
    "id": "ch_alliance_UvvU",
    "alliance": "UvvU",
    "channel_id": "123456789012345678",
    "webhook_url": "https://discord.com/api/webhooks/...",
    "enabled": true
}
```

---

#### 19. GET /admin/discord_templates_api.php

Get Discord message templates.

**Required Roles:** `admin`, `r5`
**CSRF Required:** No (GET)

---

#### 20. POST /admin/discord_templates_api.php

Create or update message template.

**Required Roles:** `admin`
**CSRF Required:** Yes

---

#### 21. GET /admin/discord_scheduled_api.php

Get scheduled Discord messages.

**Required Roles:** `admin`
**CSRF Required:** No (GET)

---

#### 22. POST /admin/discord_scheduled_api.php

Create or update scheduled message.

**Required Roles:** `admin`
**CSRF Required:** Yes

---

### Security & Monitoring

#### 23. GET /admin/audit_log_api.php

Get security audit log entries.

**Required Roles:** `admin` only
**CSRF Required:** No (GET)

**Query Parameters:**
- `user` - Filter by user email (optional)
- `action` - Filter by action type (optional)
- `start_date` - Filter from date (optional)
- `end_date` - Filter to date (optional)
- `limit` - Number of entries (default: 100, max: 1000)

**Response:**
```json
{
    "success": true,
    "entries": [
        {
            "timestamp": "2025-11-20T12:00:00Z",
            "user": "u***@example.com",
            "action": "alliance_edit",
            "resource": "UvvU",
            "ip": "192.168.1.100",
            "user_agent": "Mozilla/5.0...",
            "outcome": "success",
            "details": {
                "field": "power",
                "old_value": "7800000000",
                "new_value": "7920000000"
            }
        }
    ],
    "total": 1523,
    "filtered": 100
}
```

**Action Types:**
- `login_success`, `login_failed`
- `alliance_create`, `alliance_edit`, `alliance_delete`
- `user_create`, `user_edit`, `user_delete`
- `vote_create`, `vote_approve`, `vote_reject`
- `discord_message`, `discord_channel_update`
- `security_event`, `mfa_enable`, `mfa_disable`

---

#### 24. GET /admin/backup_restore_api.php?action=list

List available backup files.

**Required Roles:** `admin` only
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "backups": [
        {
            "filename": "backup_20251120_120000.zip",
            "created_at": "2025-11-20T12:00:00Z",
            "size": 524288,
            "files_count": 15
        }
    ]
}
```

---

#### 25. POST /admin/backup_restore_api.php?action=create

Create a new backup.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Response:**
```json
{
    "success": true,
    "filename": "backup_20251120_120000.zip",
    "message": "Backup created successfully"
}
```

---

#### 26. POST /admin/backup_restore_api.php?action=restore

Restore from a backup file.

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "filename": "backup_20251120_120000.zip"
}
```

**Warning:** This operation overwrites current data. Creates automatic backup before restoration.

---

#### 27. POST /admin/revoke_token_api.php

Revoke a user's session token (force logout).

**Required Roles:** `admin` only
**CSRF Required:** Yes

**Request:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Token revoked successfully. User logged out."
}
```

---

### Council & Governance

#### 28. GET /admin/council_rotation_api.php

Get council rotation schedule.

**Required Roles:** `admin`, `president`, `r5`, `r4`, `ape`
**CSRF Required:** No (GET)

**Query Parameters:**
- `weeks` - Number of future weeks (default: 5, max: 52)

**Response:**
```json
{
    "success": true,
    "current_week": 20,
    "schedule": [
        {
            "weekNumber": 20,
            "startDate": "2025-10-13T02:00:00.000Z",
            "permanentMembers": ["UvvU", "ORCE", "MTOP", "FNXS", "MZKU"],
            "rotatingMembers": ["STR8", "EPIC"]
        }
    ]
}
```

---

### Metrics & Analytics

#### 29. GET /admin/metrics_api.php

Get system metrics and statistics.

**Required Roles:** `admin` only
**CSRF Required:** No (GET)

**Response:**
```json
{
    "success": true,
    "metrics": {
        "users": {
            "total": 25,
            "active_today": 12,
            "mfa_enabled": 18
        },
        "alliances": {
            "total": 15,
            "signed": 15,
            "total_power": 95432109876
        },
        "votes": {
            "active": 2,
            "pending_requests": 3,
            "total_completed": 45
        },
        "audit_log": {
            "entries_today": 234,
            "failed_logins_today": 0
        }
    }
}
```

---

## Response Format

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful" // Optional
}
```

### Error Response

```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE" // Optional
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful request |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Missing or invalid JWT token |
| 403 | Forbidden | Valid token, insufficient permissions |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | Wrong HTTP method |
| 409 | Conflict | Resource already exists |
| 500 | Internal Server Error | Server error |

---

## Error Codes

| Code | Description |
|------|-------------|
| `AUTH_REQUIRED` | No JWT token provided |
| `AUTH_INVALID` | Invalid or expired JWT token |
| `AUTH_INSUFFICIENT` | Token valid, role insufficient |
| `CSRF_INVALID` | Missing or invalid CSRF token |
| `VALIDATION_ERROR` | Input validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource doesn't exist |
| `RESOURCE_EXISTS` | Resource already exists |
| `OPERATION_FAILED` | Generic operation failure |

---

## Security Best Practices

### For API Consumers

1. **Always use HTTPS** - Never send tokens over HTTP
2. **Include credentials** - Set `credentials: 'include'` on all fetch() calls
3. **Include CSRF tokens** - Add `X-CSRF-Token` header on POST/PUT/DELETE
4. **Handle 401/403 errors** - Redirect to login on authentication failures
5. **Don't log tokens** - Never console.log() JWT tokens or CSRF tokens
6. **Respect rate limits** - Avoid excessive API calls

### For API Implementers

1. **Validate JWT on every request** - Use `require_jwt_session_api()`
2. **Check permissions** - Use `has_role()` and `has_alliance_access()`
3. **Verify CSRF tokens** - Use `verify_csrf_token()` on state changes
4. **Audit log all actions** - Use `audit_log()` function
5. **Sanitize inputs** - Validate and sanitize all user inputs
6. **Return JSON errors** - Never return HTML from API endpoints

---

## Rate Limiting

**Current Status:** No rate limiting implemented

**Planned:** Per-user rate limiting (100 requests/minute per user)

---

## Monitoring

All API calls are logged to:
- **Audit Log:** `admin/audit-log.json` (user actions)
- **PHP Error Log:** Server errors and exceptions

**Health Check:** Use `/admin/metrics_api.php` for monitoring system health

---

## Support

For API issues or questions:
- **GitHub Issues**: https://github.com/k33bz/lastwar-server1586/issues
- **Documentation**: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/ADMIN_API.md

---

**Last Updated:** 2025-11-20
**API Version:** 1.0.0
**Maintained By:** Server 1586 Development Team
