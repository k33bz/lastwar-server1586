# Scheduled Messages (Phase 2)

## Overview

The scheduled messages feature allows R4+ users and presidents to schedule Discord announcements for future delivery. Messages are automatically sent at the scheduled time by a cron processor.

## Features

- **Schedule messages** for any future date and time
- **Manage scheduled messages** with create/delete operations
- **Visual status tracking** (pending, sent, failed)
- **Timezone-aware interface** - uses your local timezone
- **Rich embed support** - optional embed formatting with custom colors
- **Access control** - respects channel permissions

## Access

**Required Role:** R4, R5, Admin, or President

**Location:** Admin Panel → Discord → Scheduled Messages

## Creating a Scheduled Message

1. Navigate to **Discord** → **Scheduled Messages**
2. Click the **Create Scheduled Message** tab
3. Select the target channel
4. Choose the date and time for delivery
5. Enter your message content (max 2000 characters)
6. Optionally enable rich embed with custom title and color
7. Click **Schedule Message**

## Managing Scheduled Messages

Switch to the **Manage Messages** tab to:
- View all your scheduled messages
- See message status (pending/sent/failed)
- Delete pending messages (before they're sent)
- View sent message history

## Message Status

- **Pending** (⏳) - Waiting to be sent at scheduled time
- **Sent** (✓) - Successfully delivered to Discord
- **Failed** (✗) - Delivery failed (check error message)

## Cron Setup (Admin Only)

The processor must run every minute to check for messages ready to send.

### Linux/Unix Cron

Add to crontab:
```bash
* * * * * php /path/to/admin/discord_scheduled_processor.php
```

### Setup Steps

1. SSH into your server
2. Edit crontab: `crontab -e`
3. Add the line above with your actual path
4. Save and exit
5. Verify cron is running: `crontab -l`

### Manual Testing

```bash
php admin/discord_scheduled_processor.php
```

Check output:
- "Discord scheduled processor completed: X sent, Y failed"
- Check `admin/audit_log.json` for `discord_scheduled_sent` events

## Technical Details

### Data Storage

Scheduled messages are stored in `admin/discord_scheduled.json`:
```json
{
  "scheduled_messages": [
    {
      "id": "sched_...",
      "channel_id": "1234567890",
      "message": "Announcement text",
      "use_embed": true,
      "embed_title": "Title",
      "embed_color": "#5865F2",
      "scheduled_time": "2025-11-10 15:30:00",
      "created_by": "user@example.com",
      "created_at": "2025-11-06 10:00:00",
      "status": "pending",
      "sent_at": null,
      "error": null
    }
  ]
}
```

### Processing Logic

1. Processor runs every minute via cron
2. Loads all pending messages
3. Checks if `scheduled_time` <= `current_time`
4. Sends eligible messages via Discord API
5. Updates status to `sent` or `failed`
6. Logs all operations to audit log
7. Respects rate limiting (1 second between sends)

### API Endpoints

**discord_scheduled_api.php**

- `GET ?action=list` - Get user's scheduled messages
- `POST ?action=create` - Create new scheduled message
- `POST ?action=delete&message_id=X` - Delete pending message

### Security

- All operations require JWT authentication
- Users can only access their permitted channels
- Only creator or admin can delete messages
- Cannot delete already-sent messages
- All operations are audit logged

## Limitations

- Maximum 2000 characters per message (Discord limit)
- Scheduled time must be in the future
- Cannot edit scheduled messages (delete and recreate instead)
- Cannot delete sent messages
- Processor accuracy depends on cron frequency

## Troubleshooting

### Messages Not Sending

1. Check cron is running: `crontab -l`
2. Check processor logs: `tail -f /var/log/syslog | grep discord`
3. Run manually: `php admin/discord_scheduled_processor.php`
4. Check audit log: Look for `discord_scheduled_sent` or `discord_scheduled_failed` events

### Permission Errors

- Verify bot has "Send Messages" permission in channel
- Check bot is in the Discord server
- Visit Discord Configuration page for bot invite link

### Timezone Issues

- Interface uses browser's local timezone
- Database stores in server timezone (usually UTC or server local)
- Times are converted appropriately during processing

## Best Practices

1. **Test first** - Schedule a test message 2-3 minutes ahead
2. **Monitor status** - Check Manage tab after scheduled time
3. **Plan ahead** - Schedule important announcements with buffer time
4. **Use embeds** - Rich embeds look more professional
5. **Clear communication** - Include context in scheduled messages

## Future Enhancements (Phase 3)

- Recurring messages (daily/weekly schedules)
- Message templates
- Multi-channel broadcasting
- Edit scheduled messages
- Scheduling from mobile-friendly UI

## Version

- Feature: Discord Scheduled Messages
- Phase: 2
- Version: 2.0.0
- Release Date: 2025-11-06
- Minimum Version: 3.5.1

## See Also

- [Discord Bot Setup](BOT-SETUP.md)
- [Discord Announcements](README.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
