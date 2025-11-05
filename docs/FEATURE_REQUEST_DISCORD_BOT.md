# Feature Request: Discord Announcement Bot System

**Status:** Proposed
**Priority:** Enhancement
**Target Version:** v4.0.0
**Created:** 2025-11-04

---

## Overview

Implement a comprehensive Discord bot system that enables R5 (alliance leaders) and R4 (alliance officers) to send announcements to Discord servers and channels. The system should support instant messages, scheduled announcements, recurring messages, and cross-alliance/cross-server broadcasts.

---

## Problem Statement

Currently, alliance leaders and officers have no automated way to:
- Send coordinated announcements to their Discord communities
- Schedule messages for future events (KE, SVS, etc.)
- Set up recurring reminders (daily reset times, event countdowns)
- Broadcast cross-alliance messages for NAP15 coordination
- Send cross-server announcements for diplomatic communications

Manual Discord posting is time-consuming and prone to missed announcements, especially across multiple servers or alliances.

---

## Proposed Solution

Build a Discord bot integration with a web-based admin interface that allows authorized users to:

1. **Send instant announcements** to one or more Discord channels
2. **Schedule announcements** for specific date/time (with timezone support)
3. **Create recurring messages** (daily, weekly, custom intervals)
4. **Target multiple destinations** (cross-alliance and cross-server messaging)
5. **Manage announcement templates** for common message types
6. **View announcement history** and delivery status

---

## User Stories

### R5 (Alliance Leader)
- As an R5, I want to send instant announcements to my alliance Discord channel
- As an R5, I want to schedule event reminders (e.g., "KE starts in 1 hour")
- As an R5, I want to set up daily recurring messages (e.g., daily reset reminders)
- As an R5, I want to send cross-alliance messages to all NAP15 Discord channels
- As an R5, I want to use message templates for common announcements

### R4 (Alliance Officer)
- As an R4, I want to send announcements to my alliance Discord channel
- As an R4, I want to schedule messages for events I'm coordinating
- As an R4, I want to view and manage my scheduled announcements

### Admin
- As an admin, I want to configure Discord bot tokens and webhook URLs
- As an admin, I want to manage which Discord servers/channels are available
- As an admin, I want to audit all Discord announcements sent
- As an admin, I want to enable/disable Discord features per user/alliance

---

## Functional Requirements

### 1. Discord Bot Configuration

#### Bot Setup
- [ ] Discord bot token configuration via `.env` file
- [ ] Support for multiple Discord servers/guilds
- [ ] OAuth2 authentication for bot installation
- [ ] Channel permission verification
- [ ] Rate limit compliance with Discord API

#### Channel Management
```json
{
  "channels": [
    {
      "id": "channel_id_123",
      "name": "UvvU-announcements",
      "server_id": "server_id_456",
      "server_name": "Last War 1586",
      "alliance": "UvvU",
      "type": "alliance",
      "enabled": true
    },
    {
      "id": "channel_id_789",
      "name": "nap15-general",
      "server_id": "server_id_456",
      "server_name": "Last War 1586",
      "alliance": "*",
      "type": "cross-alliance",
      "enabled": true
    }
  ]
}
```

### 2. Announcement Types

#### A. Instant Announcements
- Send message immediately to selected channels
- Confirmation dialog before sending
- Delivery status notification
- Support for Discord markdown formatting
- Support for embeds (rich formatted messages)
- Optional @role or @everyone mentions

#### B. Scheduled Announcements
- Date/time picker with timezone support
- Preview before scheduling
- Edit/delete scheduled messages before sending
- List view of all scheduled announcements
- Automatic timezone conversion for recipients

#### C. Recurring Announcements
- Frequency options:
  - Daily (at specific time)
  - Weekly (specific day and time)
  - Custom interval (every X hours/days)
- Start date and optional end date
- Skip/pause options for individual occurrences
- Edit all future occurrences or just next one

### 3. Message Targeting

#### Single Target
- Select one Discord channel from dropdown
- Filtered by user's alliances (R4/R5 can only target their alliances)
- Admin can target any channel

#### Multi-Target (Cross-Alliance)
- Select multiple channels via checkboxes
- Grouped by server and alliance
- Quick select options:
  - "All my alliances" (user's assigned alliances)
  - "All NAP15" (all top 15 alliance channels)
  - "All Server 1586" (all channels in main server)
- Individual channel selection

#### Cross-Server
- Support for multiple Discord servers
- Select channels across different servers
- Server grouping in UI for clarity

### 4. Message Composition

#### Message Editor
- Rich text editor with markdown preview
- Character limit display (Discord 2000 char limit)
- Variable substitution:
  - `{alliance}` - Alliance tag
  - `{date}` - Current date
  - `{time}` - Current time
  - `{event}` - Event name (from template)
  - `{countdown}` - Time until event
- Discord mention syntax support:
  - `@role` mentions
  - `@everyone` / `@here`
  - User mentions `@username`

#### Message Templates
```json
{
  "templates": [
    {
      "id": "ke-reminder",
      "name": "Kingdom Event Reminder",
      "category": "events",
      "content": "🏰 **Kingdom Event Alert** 🏰\n\n{alliance} - KE starts in {countdown}!\n\nRemember:\n- Join your assigned rally\n- Follow R4 instructions\n- Use your boosts wisely\n\nGood luck! 💪",
      "variables": ["alliance", "countdown"]
    },
    {
      "id": "daily-reset",
      "name": "Daily Reset Reminder",
      "category": "reminders",
      "content": "⏰ **Daily Reset in 1 Hour** ⏰\n\nDon't forget:\n- [ ] Claim your dailies\n- [ ] Use stamina\n- [ ] Complete alliance tasks\n- [ ] Check store reset",
      "variables": []
    },
    {
      "id": "svs-prep",
      "name": "SVS Preparation",
      "category": "events",
      "content": "⚔️ **SVS Preparation** ⚔️\n\n{alliance} - Server vs Server in {countdown}\n\n**Checklist:**\n- Stock up on speedups\n- Prepare troops\n- Clear hospital\n- Join Discord voice channel\n\nLet's dominate! 🔥",
      "variables": ["alliance", "countdown"]
    }
  ]
}
```

### 5. User Interface

#### Dashboard Widget
- "Discord Announcements" card on admin dashboard
- Quick stats:
  - Messages sent today
  - Scheduled announcements count
  - Active recurring messages
- Quick action button: "Send Announcement"

#### Announcement Manager Page
```
admin/discord_announcements.php
```

**Sections:**
1. **Send New Announcement**
   - Message composition area
   - Target selection
   - Send options (instant/schedule/recurring)
   - Send button

2. **Scheduled Announcements**
   - Table view with columns:
     - Message preview
     - Scheduled time
     - Targets (channel names)
     - Created by
     - Actions (edit, delete, send now)
   - Filter by user, alliance, date range

3. **Recurring Announcements**
   - Table view with columns:
     - Template name
     - Frequency
     - Next send time
     - Targets
     - Status (active/paused)
     - Actions (edit, pause, delete)

4. **Message History**
   - Table view with columns:
     - Sent time
     - Message preview
     - Targets
     - Sent by
     - Status (success/failed)
     - Actions (view details, resend)
   - Pagination (50 per page)
   - Export to CSV option

#### Configuration Page
```
admin/discord_config.php
```

**Sections:**
1. **Bot Configuration**
   - Bot token (masked)
   - Test connection button
   - Connection status indicator

2. **Server/Channel Management**
   - Add Discord server (via invite link)
   - List connected servers
   - Add/edit channels per server
   - Assign channels to alliances
   - Enable/disable channels

3. **User Permissions**
   - Enable/disable Discord features per user
   - Set announcement limits (messages per day)
   - Cross-alliance permissions

4. **Template Management**
   - Create/edit message templates
   - Categorize templates
   - Set template visibility (private/alliance/global)

### 6. Access Control

#### Permission Rules
| Role  | Instant | Schedule | Recurring | Own Alliance | Cross-Alliance | Cross-Server | Templates |
|-------|---------|----------|-----------|--------------|----------------|--------------|-----------|
| Admin | ✅      | ✅       | ✅        | All (*)      | ✅             | ✅           | Create/Edit All |
| R5    | ✅      | ✅       | ✅        | Own          | ✅ (if authorized) | ✅ (if authorized) | Create/Edit Own |
| R4    | ✅      | ✅       | ❌        | Own          | ❌             | ❌           | Use Only |
| APE   | ❌      | ❌       | ❌        | N/A          | ❌             | ❌           | N/A |

**Notes:**
- R5 can request cross-alliance permissions from admin
- R4 cannot create recurring announcements (prevent spam)
- All users can save their own templates for their alliance
- Admin can create global templates available to all

#### Rate Limiting
- Instant messages: 10 per hour per user
- Scheduled messages: 50 pending per user
- Recurring messages: 5 active per user
- Cross-alliance messages: 5 per day per user (non-admin)
- Admin: No limits

### 7. Discord Bot Features

#### Message Sending
- Support for Discord embeds (rich formatted messages)
- Support for attachments (images from URLs)
- Support for buttons/reactions (optional)
- Automatic markdown formatting
- Link preview handling

#### Message Types

**Simple Text Message:**
```json
{
  "content": "This is a simple announcement"
}
```

**Embed Message:**
```json
{
  "embeds": [{
    "title": "Kingdom Event Alert",
    "description": "KE starts in 1 hour!",
    "color": 3447003,
    "fields": [
      {
        "name": "Alliance",
        "value": "UvvU",
        "inline": true
      },
      {
        "name": "Time",
        "value": "2025-11-04 20:00 UTC",
        "inline": true
      }
    ],
    "footer": {
      "text": "Last War 1586 Bot"
    },
    "timestamp": "2025-11-04T20:00:00Z"
  }]
}
```

#### Bot Commands (Optional)
Users can interact with bot in Discord:
- `/status` - Show bot status and next scheduled announcements
- `/schedule` - List upcoming announcements
- `/help` - Show available commands

---

## Technical Requirements

### 1. Backend Architecture

#### File Structure
```
admin/
├── discord_bot.php           # Bot client and API wrapper
├── discord_webhook.php       # Webhook sending (fallback)
├── discord_announcements.php # UI page
├── discord_config.php        # Configuration UI
├── discord_api.php           # REST API endpoints
└── discord_scheduler.php     # Cron job handler

data/
├── discord-channels.json     # Channel configuration
├── discord-announcements.json # Scheduled/recurring messages
├── discord-history.json      # Message history (last 1000)
└── discord-templates.json    # Message templates
```

#### Dependencies
**Add to `admin/composer.json`:**
```json
{
  "require": {
    "team-reflex/discord-php": "^10.0",
    "guzzlehttp/guzzle": "^7.8"
  }
}
```

**Alternative (Webhook Only - Simpler):**
```json
{
  "require": {
    "guzzlehttp/guzzle": "^7.8"
  }
}
```

#### Environment Variables
**Add to `admin/.env`:**
```ini
# Discord Bot Configuration
DISCORD_BOT_TOKEN=your_bot_token_here
DISCORD_CLIENT_ID=your_client_id_here
DISCORD_CLIENT_SECRET=your_client_secret_here

# Discord Webhooks (fallback)
DISCORD_WEBHOOK_URL_MAIN=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_URL_NAP15=https://discord.com/api/webhooks/...

# Feature Flags
DISCORD_ENABLED=true
DISCORD_RATE_LIMIT_ENABLED=true
DISCORD_MAX_INSTANT_PER_HOUR=10
DISCORD_MAX_SCHEDULED_PENDING=50
DISCORD_MAX_RECURRING_ACTIVE=5
```

### 2. Database Schema (JSON)

#### discord-announcements.json
```json
{
  "scheduled": [
    {
      "id": "sched_001",
      "type": "scheduled",
      "message": {
        "content": "KE reminder message",
        "embed": {...}
      },
      "targets": ["channel_id_1", "channel_id_2"],
      "scheduled_time": "2025-11-04T20:00:00Z",
      "timezone": "UTC",
      "created_by": "user@example.com",
      "created_at": "2025-11-04T10:00:00Z",
      "status": "pending",
      "sent_at": null,
      "error": null
    }
  ],
  "recurring": [
    {
      "id": "recur_001",
      "type": "recurring",
      "template_id": "daily-reset",
      "message": {...},
      "targets": ["channel_id_1"],
      "frequency": "daily",
      "time": "23:00",
      "timezone": "UTC",
      "start_date": "2025-11-01",
      "end_date": null,
      "next_send": "2025-11-04T23:00:00Z",
      "last_sent": "2025-11-03T23:00:00Z",
      "created_by": "user@example.com",
      "status": "active",
      "send_count": 3
    }
  ]
}
```

#### discord-history.json
```json
{
  "messages": [
    {
      "id": "msg_001",
      "type": "instant",
      "message": {...},
      "targets": ["channel_id_1"],
      "sent_by": "user@example.com",
      "sent_at": "2025-11-04T12:00:00Z",
      "status": "success",
      "discord_message_ids": {
        "channel_id_1": "discord_msg_id_123"
      },
      "error": null
    }
  ]
}
```

### 3. API Endpoints

#### POST /admin/discord_api.php?action=send
Send instant announcement
```json
{
  "message": {
    "content": "Message text",
    "embed": {...}
  },
  "targets": ["channel_id_1", "channel_id_2"]
}
```

#### POST /admin/discord_api.php?action=schedule
Schedule announcement
```json
{
  "message": {...},
  "targets": [...],
  "scheduled_time": "2025-11-04T20:00:00Z",
  "timezone": "UTC"
}
```

#### POST /admin/discord_api.php?action=recurring
Create recurring announcement
```json
{
  "template_id": "daily-reset",
  "message": {...},
  "targets": [...],
  "frequency": "daily",
  "time": "23:00",
  "timezone": "UTC",
  "start_date": "2025-11-01"
}
```

#### GET /admin/discord_api.php?action=list_scheduled
List scheduled announcements

#### GET /admin/discord_api.php?action=list_recurring
List recurring announcements

#### GET /admin/discord_api.php?action=history&limit=50&offset=0
Get message history

#### DELETE /admin/discord_api.php?action=delete&id=sched_001
Delete scheduled/recurring announcement

#### PUT /admin/discord_api.php?action=update&id=sched_001
Update scheduled/recurring announcement

### 4. Cron Job

#### Scheduler Script
**File:** `admin/discord_scheduler.php`

Run every minute via cron:
```bash
* * * * * cd /path/to/admin && php discord_scheduler.php
```

**Responsibilities:**
- Check for scheduled announcements due to send
- Check for recurring announcements due to send
- Send pending messages via Discord API
- Update message status and history
- Generate next occurrence for recurring messages
- Handle failures and retry logic
- Clean up old history (keep last 1000)

### 5. Error Handling

#### Failure Scenarios
1. **Discord API rate limit exceeded**
   - Queue message for retry
   - Display warning to user
   - Log in audit system

2. **Channel not found / no permissions**
   - Mark message as failed
   - Notify user via email
   - Log error details

3. **Invalid message format**
   - Validate before sending
   - Show error in UI immediately
   - Suggest corrections

4. **Bot offline / connection error**
   - Retry up to 3 times
   - Fall back to webhook if available
   - Alert admin via email

#### Retry Logic
- Automatic retry for transient errors (3 attempts)
- Exponential backoff (1min, 5min, 15min)
- Manual retry option in UI
- Move to "failed" status after max retries

### 6. Audit Logging

Extend `admin/audit_logger.php` to include:
```php
log_audit_event('discord_announcement_sent', [
    'user' => $user_email,
    'type' => 'instant|scheduled|recurring',
    'targets' => ['channel_id_1', 'channel_id_2'],
    'message_preview' => substr($message, 0, 100),
    'status' => 'success|failed',
    'error' => $error_message
]);
```

### 7. Security Considerations

#### Input Validation
- Sanitize all message content
- Validate Discord channel IDs
- Validate timestamps and timezones
- Check message length limits

#### Rate Limiting
- Per-user limits (configurable)
- Per-alliance limits (optional)
- Global rate limit tracking
- Discord API rate limit compliance

#### Permission Checks
- Verify user role before sending
- Check user's alliance assignment
- Validate cross-alliance permissions
- Audit all announcement attempts

#### Data Protection
- Store Discord tokens encrypted
- Mask sensitive data in logs
- Sanitize message content for XSS
- Validate webhook URLs

---

## User Experience (UX) Design

### Send Announcement Flow

```
1. User clicks "Send Announcement" button
   ↓
2. Modal opens with tabs:
   - [Instant] [Schedule] [Recurring]
   ↓
3. User composes message:
   - Select template (optional)
   - Edit message content
   - Preview with markdown rendering
   ↓
4. User selects targets:
   - Checkbox list of available channels
   - Grouped by server/alliance
   - Quick select buttons
   ↓
5. User configures timing (if scheduled/recurring):
   - Date/time picker
   - Timezone selector
   - Frequency options (if recurring)
   ↓
6. User reviews summary:
   - Message preview
   - Target list
   - Timing details
   ↓
7. User clicks "Send" / "Schedule" / "Create"
   ↓
8. Confirmation dialog:
   - "Are you sure you want to send to 5 channels?"
   ↓
9. Processing:
   - Loading indicator
   - Progress for multi-target sends
   ↓
10. Result notification:
    - Success: "Announcement sent to 5 channels"
    - Partial: "Sent to 3/5 channels (2 failed)"
    - Failure: "Failed to send. Error: [details]"
```

### UI Wireframe (Conceptual)

```
┌─────────────────────────────────────────────────────┐
│ Discord Announcements                               │
├─────────────────────────────────────────────────────┤
│                                                     │
│  [ Instant ] [ Schedule ] [ Recurring ]            │
│                                                     │
│  ┌─ Message ────────────────────────────────────┐ │
│  │ [Template: Select...        ▼] [Variables]  │ │
│  │                                              │ │
│  │ ┌──────────────────────────────────────────┐│ │
│  │ │ Type your message here...                ││ │
│  │ │                                          ││ │
│  │ │ Supports **markdown** and @mentions     ││ │
│  │ └──────────────────────────────────────────┘│ │
│  │ 156 / 2000 characters                        │ │
│  └──────────────────────────────────────────────┘ │
│                                                     │
│  ┌─ Target Channels ─────────────────────────────┐│
│  │ Quick Select: [My Alliances] [All NAP15]     ││
│  │                                               ││
│  │ Last War 1586 Server:                        ││
│  │   ☑ UvvU-announcements                       ││
│  │   ☐ ORCE-announcements                       ││
│  │   ☐ NAP15-general                            ││
│  │                                               ││
│  │ Other Server:                                 ││
│  │   ☐ Alliance-coordination                     ││
│  └───────────────────────────────────────────────┘│
│                                                     │
│  [Preview] [Cancel]        [Send Announcement]    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Implementation Phases

### Phase 1: Foundation (v4.0.0)
**Timeline:** 2-3 weeks

- [ ] Discord bot setup and authentication
- [ ] Webhook integration (fallback)
- [ ] Basic instant message sending
- [ ] Single channel targeting
- [ ] Admin configuration UI
- [ ] Audit logging integration
- [ ] Basic error handling

**Deliverables:**
- Working Discord bot connected to server
- Admin can send instant messages
- Configuration page for bot setup
- Audit logs for all messages

### Phase 2: Scheduling (v4.1.0)
**Timeline:** 2 weeks

- [ ] Scheduled announcement system
- [ ] Cron job scheduler implementation
- [ ] Schedule management UI
- [ ] Edit/delete scheduled messages
- [ ] Email notifications for failures
- [ ] Retry logic for failed sends

**Deliverables:**
- Schedule announcements for future dates/times
- View/manage scheduled announcements
- Automatic sending at scheduled times

### Phase 3: Multi-Target & Templates (v4.2.0)
**Timeline:** 2 weeks

- [ ] Multi-channel targeting
- [ ] Cross-alliance permissions
- [ ] Message template system
- [ ] Template management UI
- [ ] Variable substitution
- [ ] Quick select channel groups

**Deliverables:**
- Send to multiple channels simultaneously
- Create and use message templates
- Cross-alliance announcement capability

### Phase 4: Recurring Messages (v4.3.0)
**Timeline:** 2 weeks

- [ ] Recurring announcement system
- [ ] Frequency configuration
- [ ] Next occurrence calculation
- [ ] Pause/resume recurring messages
- [ ] Edit future occurrences
- [ ] History tracking for recurring sends

**Deliverables:**
- Create daily/weekly recurring announcements
- Manage recurring message schedule
- Automatic repeated sending

### Phase 5: Advanced Features (v4.4.0)
**Timeline:** 2 weeks

- [ ] Discord embed formatting
- [ ] Rich message editor
- [ ] Image attachment support
- [ ] Cross-server targeting
- [ ] Advanced rate limiting
- [ ] Message history export (CSV)
- [ ] Analytics dashboard

**Deliverables:**
- Rich formatted messages with embeds
- Send to multiple Discord servers
- Comprehensive message history
- Usage analytics and reporting

---

## Testing Requirements

### Unit Tests
- [ ] Message formatting and validation
- [ ] Permission checking logic
- [ ] Rate limiting calculations
- [ ] Timezone conversions
- [ ] Template variable substitution
- [ ] Schedule calculation (recurring messages)

### Integration Tests
- [ ] Discord API connection
- [ ] Message sending (mock Discord API)
- [ ] Cron job execution
- [ ] Database operations (JSON files)
- [ ] Audit logging
- [ ] Error handling and retries

### End-to-End Tests
- [ ] Send instant announcement
- [ ] Schedule announcement
- [ ] Create recurring message
- [ ] Multi-channel targeting
- [ ] Template usage
- [ ] Permission enforcement

### Manual Testing Checklist
- [ ] R5 can send to own alliance
- [ ] R4 can send to own alliance
- [ ] R4 cannot create recurring messages
- [ ] Cross-alliance requires permission
- [ ] Rate limits enforced
- [ ] Scheduled messages send at correct time
- [ ] Recurring messages repeat correctly
- [ ] Failed sends trigger notifications
- [ ] Audit logs captured correctly

---

## Success Metrics

### Adoption Metrics
- Number of active users sending announcements
- Number of announcements sent per day/week
- Number of scheduled announcements created
- Number of recurring announcements active

### Quality Metrics
- Message delivery success rate (target: >99%)
- Average time from scheduled time to actual send (target: <30 seconds)
- Error rate (target: <1%)
- User satisfaction (feedback survey)

### Usage Patterns
- Most used message templates
- Peak sending times
- Most targeted channels
- Cross-alliance message frequency

---

## Documentation Requirements

### User Documentation
- [ ] "Getting Started with Discord Announcements" guide
- [ ] How to schedule announcements
- [ ] How to create recurring messages
- [ ] How to use templates
- [ ] Cross-alliance messaging guide
- [ ] Troubleshooting common errors

**File:** `docs/discord-announcements/USER-GUIDE.md`

### Admin Documentation
- [ ] Discord bot setup guide
- [ ] Channel configuration guide
- [ ] Permission management
- [ ] Monitoring and troubleshooting
- [ ] Rate limit configuration
- [ ] Audit log review

**File:** `docs/discord-announcements/ADMIN-GUIDE.md`

### Developer Documentation
- [ ] Architecture overview
- [ ] API endpoints reference
- [ ] Database schema documentation
- [ ] Cron job setup
- [ ] Error handling patterns
- [ ] Testing guide

**File:** `docs/discord-announcements/DEVELOPER-GUIDE.md`

---

## Risks and Mitigations

### Risk 1: Discord API Rate Limits
**Impact:** High
**Probability:** Medium

**Description:** Discord has strict rate limits that could prevent message sending if exceeded.

**Mitigation:**
- Implement client-side rate limiting
- Queue messages and send with appropriate delays
- Use burst buckets for rate limit tracking
- Provide clear user feedback when limits approached
- Consider webhook fallback for non-interactive messages

### Risk 2: Spam Potential
**Impact:** High
**Probability:** Medium

**Description:** Users could abuse system to spam Discord channels.

**Mitigation:**
- Strict per-user rate limits
- Admin approval for cross-alliance permissions
- Recurring message limits (max 5 active per user)
- Audit logging of all messages
- Admin override/disable functionality
- Report abuse mechanism

### Risk 3: Message Delivery Failures
**Impact:** Medium
**Probability:** Low

**Description:** Messages may fail to send due to connectivity, permissions, or Discord issues.

**Mitigation:**
- Retry logic with exponential backoff
- Email notifications for failures
- Manual retry option in UI
- Fallback to webhook sending
- Comprehensive error logging
- Status monitoring dashboard

### Risk 4: Timezone Confusion
**Impact:** Medium
**Probability:** Medium

**Description:** Users in different timezones may schedule messages incorrectly.

**Mitigation:**
- Clear timezone display throughout UI
- User timezone detection and defaults
- Timezone conversion preview
- Confirmation dialog with timezone info
- Schedule in UTC but display in user's timezone
- Examples and guidance in UI

### Risk 5: Bot Account Compromise
**Impact:** High
**Probability:** Low

**Description:** Discord bot token could be compromised, allowing unauthorized access.

**Mitigation:**
- Store bot token encrypted in `.env`
- Never expose token in logs or UI
- Limit bot permissions to minimum required
- Regular token rotation
- Monitor unusual activity
- Audit logging of all bot actions

---

## Dependencies

### External Services
- Discord API (https://discord.com/developers/docs/intro)
- Discord Bot Gateway (WebSocket connection)

### PHP Libraries
- `team-reflex/discord-php` or `guzzlehttp/guzzle` (HTTP client)
- Existing: `firebase/php-jwt`, `phpmailer/phpmailer`

### Infrastructure
- Cron job capability on hosting server
- HTTPS for webhook callbacks (if used)
- Sufficient disk space for message history

---

## Alternatives Considered

### 1. Webhook-Only Approach
**Pros:**
- Simpler implementation
- No bot hosting required
- Lower resource usage

**Cons:**
- Limited to one channel per webhook
- Cannot read Discord state
- No interactive features
- Less flexible

**Decision:** Use bot for primary implementation, keep webhooks as fallback

### 2. Third-Party Service (Zapier, IFTTT)
**Pros:**
- No development required
- Managed infrastructure
- Multi-platform integration

**Cons:**
- Monthly subscription cost
- Less control over features
- Data privacy concerns
- Limited customization

**Decision:** Build custom solution for full control and integration

### 3. Separate Discord Bot Application
**Pros:**
- Cleaner separation of concerns
- Could be standalone product
- Different tech stack possible (Node.js, Python)

**Cons:**
- Requires separate hosting
- More complex deployment
- Authentication between systems
- Duplicated user management

**Decision:** Integrate into existing PHP application for simplicity

---

## Future Enhancements (Post v4.4.0)

### Interactive Bot Commands
- Users can interact with bot in Discord
- Query upcoming announcements
- Subscribe/unsubscribe from announcement types
- Get event reminders via DM

### Discord Role Integration
- Sync website roles (R5, R4) with Discord roles
- Automatic role assignment based on alliance
- Permission enforcement via Discord roles

### Advanced Analytics
- Click tracking on announcement links
- Reaction analytics
- Engagement metrics per channel
- A/B testing for message formats

### Mobile App Integration
- Send announcements from mobile app
- Push notifications for scheduled sends
- Mobile-optimized message editor

### AI-Powered Features
- Smart scheduling suggestions
- Message optimization recommendations
- Auto-categorization of messages
- Sentiment analysis

---

## Acceptance Criteria

### Must Have (MVP)
- [x] R5 can send instant announcements to their alliance Discord channel
- [x] R4 can send instant announcements to their alliance Discord channel
- [x] Admin can configure Discord bot via UI
- [x] Scheduled announcements send at correct time
- [x] Multi-channel targeting works correctly
- [x] Permission enforcement prevents unauthorized access
- [x] Audit logging captures all announcement activity
- [x] Rate limiting prevents spam
- [x] Error handling with retry logic
- [x] User documentation available

### Should Have
- [x] Message template system
- [x] Recurring announcements (daily, weekly)
- [x] Cross-alliance messaging with permissions
- [x] Email notifications for failures
- [x] Message history with search/filter
- [x] Discord embed formatting
- [x] Timezone support

### Could Have
- [ ] Cross-server messaging
- [ ] Interactive bot commands in Discord
- [ ] Advanced analytics dashboard
- [ ] Message export (CSV/JSON)
- [ ] Template sharing between alliances
- [ ] Message preview with variables resolved

### Won't Have (This Version)
- Discord role synchronization
- Mobile app integration
- AI-powered features
- Reaction tracking
- User DM capabilities

---

## Rollout Plan

### Beta Testing (1 week)
- Enable for admin and select R5 users
- Test instant and scheduled announcements
- Gather feedback on UI/UX
- Monitor for bugs and performance issues

### Limited Release (1 week)
- Enable for all R5 users
- Enable for select R4 users
- Test cross-alliance messaging
- Monitor rate limiting and spam

### Full Release (Ongoing)
- Enable for all R4 users
- Enable recurring announcements
- Monitor usage and stability
- Iterate based on feedback

### Announcement Strategy
- Discord announcement about new feature
- Email to all R5/R4 users
- User guide link in dashboard
- Tutorial video (optional)
- FAQ document

---

## Open Questions

1. **Should R4 users be able to create recurring announcements?**
   - Current proposal: No (to prevent spam)
   - Alternative: Yes, but with lower limits (2 active max)

2. **How many channels should cross-alliance messages target at once?**
   - Current proposal: No limit for admin, 10 max for R5
   - Alternative: Set global limit (e.g., 20 channels max)

3. **Should we support Discord slash commands for bot interaction?**
   - Current proposal: Not in MVP, consider for future
   - Alternative: Include basic commands like `/status` in Phase 5

4. **How long should we retain message history?**
   - Current proposal: Last 1000 messages (rolling)
   - Alternative: 90 days + configurable retention

5. **Should scheduled messages be editable after creation?**
   - Current proposal: Yes, until send time
   - Alternative: No edits, must delete and recreate

6. **Should we support message reactions/buttons?**
   - Current proposal: Not in MVP
   - Alternative: Add in Phase 5 for interactive announcements

---

## GitHub Issue Creation

**To create this issue on GitHub:**

1. Go to: https://github.com/k33bz/lastwar-server1586/issues/new
2. **Title:** Add Discord Announcement Bot System
3. **Labels:** `enhancement`, `feature-request`, `discord`
4. **Milestone:** v4.0.0
5. **Body:** Use summarized version below

---

## GitHub Issue Summary (Copy/Paste)

```markdown
## Description
Implement Discord bot system for R5/R4 to send announcements to Discord servers/channels with instant, scheduled, and recurring message support.

## Problem
Alliance leaders need automated way to send Discord announcements for events, reminders, and cross-alliance coordination.

## Proposed Solution
- Instant announcements to selected channels
- Scheduled announcements with timezone support
- Recurring messages (daily, weekly)
- Multi-channel targeting (cross-alliance, cross-server)
- Message templates
- Permission-based access (R5, R4, Admin)

## Key Features
- Role-based permissions (R5, R4 can send to their alliances)
- Scheduling with timezone support
- Recurring announcements for daily reminders
- Cross-alliance messaging (with permissions)
- Message template system
- Audit logging
- Rate limiting to prevent spam

## Implementation Phases
1. Foundation - Basic instant messaging
2. Scheduling - Schedule future announcements
3. Multi-Target - Cross-alliance support
4. Recurring - Repeated messages
5. Advanced - Embeds, analytics

## Technical Stack
- PHP Discord bot using `team-reflex/discord-php` or webhooks
- JSON data storage for messages and history
- Cron job for scheduled/recurring sends
- Extend existing admin panel UI

## Documentation Needed
- User guide for sending announcements
- Admin guide for bot setup and configuration
- Developer guide for API and architecture

## Related Files
- `admin/mailer.php` - Pattern to follow for Discord integration
- `admin/audit_logger.php` - Extend for Discord event logging
- `data/server-info.json` - Contains Discord server metadata

## Acceptance Criteria
- R5/R4 can send instant announcements to their channels
- Scheduled announcements send at correct time
- Recurring messages repeat correctly
- Cross-alliance messaging requires permission
- Rate limiting prevents spam
- Full audit logging

## See Also
Full feature specification: `docs/FEATURE_REQUEST_DISCORD_BOT.md`
```

---

## Related Issues

- None (new feature)

---

## References

- Discord API Documentation: https://discord.com/developers/docs/intro
- Discord Bot Best Practices: https://discord.com/developers/docs/topics/community-resources
- Discord PHP Library: https://github.com/discord-php/DiscordPHP
- Existing Email System: `admin/mailer.php`
- Existing Audit System: `admin/audit_logger.php`

---

**Status:** Ready for review and GitHub issue creation
**Next Steps:** Review, refine, create GitHub issue, assign to milestone v4.0.0
