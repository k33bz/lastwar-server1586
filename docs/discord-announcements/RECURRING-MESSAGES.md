# Recurring Messages (Phase 3)

## Overview

The recurring messages feature allows R4+ users and presidents to set up automatic Discord announcements that repeat on a schedule (daily, weekly, or monthly). Messages are sent automatically by the same cron processor that handles scheduled messages.

## Features

- **Daily messages** - Send at same time every day
- **Weekly messages** - Send on specific day of week
- **Monthly messages** - Send on specific day of month (1-28 for all-month compatibility)
- **Enable/disable toggle** - Pause and resume messages without deleting
- **Send history tracking** - View count and last send time
- **Next send preview** - See when message will send next
- **Rich embed support** - Optional embed formatting with custom colors
- **Access control** - Respects channel permissions

## Access

**Required Role:** R4, R5, Admin, or President

**Location:** Admin Panel → Discord → Recurring Messages

## Creating a Recurring Message

1. Navigate to **Discord** → **Recurring Messages**
2. Click the **Create Recurring Message** tab
3. Select the target channel
4. Choose frequency:
   - **Daily** - Sends every day at specified time
   - **Weekly** - Sends once per week on chosen day
   - **Monthly** - Sends once per month on chosen date (1-28)
5. Set the time of day for sending (24-hour format)
6. Enter your message content (max 2000 characters)
7. Optionally enable rich embed
8. Click **Create Recurring Message**

## Managing Recurring Messages

Switch to the **Manage Messages** tab to:
- View all recurring messages
- See next send time and frequency
- Toggle messages on/off with switch
- View send history (count and last sent time)
- Delete messages permanently

## Message States

- **Active** (✓) - Message is enabled and will send automatically
- **Disabled** (✗) - Message is paused and won't send

## Frequency Options

### Daily
- Sends every day at specified time
- Example: "10:00" sends at 10:00 AM daily

### Weekly
- Choose day of week (Monday-Sunday)
- Sends once per week on that day at specified time
- Example: "Monday 14:00" sends every Monday at 2:00 PM

### Monthly
- Choose day of month (1-28)
- Sends once per month on that date at specified time
- Limited to days 1-28 to work with all months (including February)
- Example: "Day 15, 09:00" sends on the 15th of each month at 9:00 AM

## Cron Setup (Admin Only)

Recurring messages use the same processor as scheduled messages. If you've already set up the cron job for scheduled messages, no additional setup is needed!

### Verify Cron is Running

```bash
crontab -l
```

Should show:
```bash
* * * * * php /path/to/admin/discord_scheduled_processor.php
```

### Manual Testing

```bash
php admin/discord_scheduled_processor.php
```

Check output for recurring messages:
- "Recurring message {id} sent successfully"
- Check `admin/audit_log.json` for `discord_recurring_sent` events

## Technical Details

### Data Storage

Recurring messages are stored in `admin/discord_recurring.json`:
```json
{
  "recurring_messages": [
    {
      "id": "recur_...",
      "channel_id": "1234567890",
      "message": "Announcement text",
      "use_embed": true,
      "embed_title": "Title",
      "embed_color": "#5865F2",
      "frequency": "daily|weekly|monthly",
      "time_of_day": "14:00",
      "day_of_week": "monday",
      "day_of_month": 15,
      "next_send_time": "2025-11-07 14:00:00",
      "last_sent_at": "2025-11-06 14:00:00",
      "send_count": 5,
      "created_by": "user@example.com",
      "created_at": "2025-11-06 10:00:00",
      "enabled": true
    }
  ]
}
```

### Processing Logic

1. Processor runs every minute via cron
2. Loads all enabled recurring messages
3. Checks if `next_send_time` <= `current_time`
4. Sends eligible messages via Discord API
5. Calculates and updates `next_send_time`
6. Updates `last_sent_at` and increments `send_count`
7. Logs all operations to audit log
8. Respects rate limiting (1 second between sends)

### Next Send Time Calculation

- **Daily:** Tomorrow at same time
- **Weekly:** Next occurrence of specified weekday
- **Monthly:** Same day next month (or current month if not passed)

### API Endpoints

**discord_recurring_api.php**

- `GET ?action=list` - Get user's recurring messages
- `POST ?action=create` - Create new recurring message
- `POST ?action=toggle&message_id=X&enabled=true/false` - Enable/disable message
- `POST ?action=delete&message_id=X` - Delete recurring message

### Security

- All operations require JWT authentication
- Users can only access their permitted channels
- Only creator or admin can modify/delete messages
- Disabled messages don't send but remain in database
- All operations are audit logged

## Use Cases

### Daily Announcements
- Daily reminders for events or deadlines
- Daily morale messages
- Daily activity summaries

### Weekly Announcements
- Weekly event schedules
- Weekly council meeting reminders
- Weekly leaderboard updates

### Monthly Announcements
- Monthly council elections
- Monthly alliance goals
- Monthly newsletter or recap

## Limitations

- Maximum 2000 characters per message (Discord limit)
- Monthly messages limited to days 1-28 (safe for all months)
- Cannot edit recurring messages (delete and recreate instead)
- Processor accuracy depends on cron frequency
- Failed sends are logged but message remains enabled

## Troubleshooting

### Messages Not Sending

1. Check message is enabled (toggle switch on)
2. Verify `next_send_time` has passed
3. Check cron is running: `crontab -l`
4. Check processor logs: `tail -f /var/log/syslog | grep discord`
5. Run manually: `php admin/discord_scheduled_processor.php`
6. Check audit log for `discord_recurring_sent` or `discord_recurring_failed` events

### Permission Errors

- Verify bot has "Send Messages" permission in channel
- Check bot is in the Discord server
- Visit Discord Configuration page for bot invite link

### Time Issues

- Interface uses browser's local timezone
- Database stores in server timezone (usually UTC or server local)
- Times are converted appropriately during processing
- `next_send_time` always shows when message will actually send

## Best Practices

1. **Test first** - Create a test message and verify it works
2. **Use descriptive titles** - Help identify messages later
3. **Monitor send history** - Check count to verify messages are sending
4. **Disable when not needed** - Use toggle instead of deleting
5. **Plan ahead** - Set up recurring messages before you need them
6. **Clear timing** - Choose times that make sense for your community
7. **Review regularly** - Check and update recurring messages periodically

## Comparison with Scheduled Messages

| Feature | Scheduled Messages | Recurring Messages |
|---------|-------------------|-------------------|
| **Use Case** | One-time future announcements | Repeating announcements |
| **Status** | Pending → Sent/Failed | Enabled/Disabled |
| **Timing** | Specific date/time | Repeating schedule |
| **After Send** | Becomes "Sent" | Reschedules for next time |
| **Editing** | Cannot edit | Cannot edit |
| **History** | Sent time only | Count + last sent time |

## Version

- Feature: Discord Recurring Messages
- Phase: 3
- Version: 3.0.0
- Release Date: 2025-11-06
- Minimum Version: 3.5.1

## See Also

- [Discord Bot Setup](BOT-SETUP.md)
- [Scheduled Messages](SCHEDULED-MESSAGES.md)
- [Discord Announcements](README.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
