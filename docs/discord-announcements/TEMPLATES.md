# Discord Message Templates

## Overview

The Discord message templates system allows you to create reusable message templates with dynamic variables that automatically populate with real data when sent. Templates can be shared globally or kept alliance-specific.

## Features

- **Reusable Templates** - Create once, use many times
- **Dynamic Variables** - Auto-populate with user, alliance, and server data
- **Global & Alliance Templates** - Share with everyone or keep private
- **Submission Workflow** - Submit alliance templates for global approval
- **Click-to-Insert** - Easy variable insertion with buttons
- **Full Integration** - Works with instant, scheduled, and recurring messages

## Access

**Required Role:** R4, R5, Admin, or President

**Location:** Admin Panel → Discord → Message Templates

## Creating Templates

### Basic Template Creation

1. Navigate to **Discord** → **Message Templates**
2. Click **Create Template** tab
3. Enter a descriptive name (e.g., "Daily Event Reminder")
4. Compose your message using variables
5. Click variables from the picker to insert them
6. Optionally check "Submit for Global Approval"
7. Click **Create Template**

### Using Variables

Variables are placeholders that get replaced with real data when messages are sent. Click variable buttons to insert them, or type them manually in `{curly_braces}` format.

## Available Variables

### Server Information
- `{server_name}` - Server name (e.g., "Server 1586")
- `{server_reset_time}` - Server reset time (e.g., "00:00 UTC")

### User Information
- `{sender_name}` - In-game name of message sender
- `{sender_alliance}` - Alliance tag of sender
- `{sender_tag}` - Alliance tag in brackets format

### Alliance Information
- `{alliance_name}` - Full alliance name
- `{alliance_tag}` - Alliance tag/abbreviation
- `{r5_name}` - R5 leader's name

### Date & Time (Auto-populated)
- `{date}` - Current date (YYYY-MM-DD)
- `{time}` - Current time (HH:MM)
- `{datetime}` - Current date and time

### Custom Fields (User-provided)
- `{event_time}` - Stays as placeholder for user to fill in
- `{event_name}` - Stays as placeholder for user to fill in
- `{location}` - Stays as placeholder for user to fill in
- `{notes}` - Stays as placeholder for user to fill in

**Note:** Custom variables remain as placeholders in templates. Users replace them with actual values when composing messages.

## Template Scopes

### Alliance Templates
- Visible only to members of your alliance
- Automatically scoped to your alliance
- Perfect for alliance-specific messages

### Global Templates
- Visible to all users across all alliances
- Requires admin approval (unless you're admin)
- Great for server-wide messages

## Submission Workflow

Similar to the alliance tags system:

1. **Create** - User creates template (alliance-scoped)
2. **Submit** - Check "Submit for Global Approval"
3. **Review** - Admins see submission in Pending Approvals tab
4. **Approve/Reject** - Admin approves or rejects with reason
5. **Global** - Approved templates become globally available

## Example Templates

### Daily Event Reminder
```
🎯 {alliance_name} Daily Reminder

Event: {event_name}
Time: {event_time}
Coordinator: {r5_name}

Please confirm attendance!
- {sender_name}
```

### VS Day Templates

#### VS Day 2 - Base Expansion
```
🆚 Day 2️⃣ - Base Expansion! 🏗️
----------
✅ Pop build presents 🎁
✅ Build Speed-ups ⚡🏗️
✅ Survivor Tickets 🎫
❌ Hero Tickets 🎫
❌ Radar Tasks 📡
----------
💡 Secretary of Development 50% build and 25% research time reduction
```

#### VS Day 3 - Age of Science
```
🆚 Day 3️⃣ - Age of Science! 🔬
----------
✅ Complete Radar tasks 📡
✅ OPEN Drone chests 📦
❌ DO NOT USE PARTS!
----------
💡 Drone Parts = Day 1️⃣
💡 Secretary of Science 50% research and 25% build time reduction
```

#### VS Day 4 - Train Heroes
```
🆚 Day 4️⃣ - Train Heroes! 👤💪
----------
✅ Hero Tickets 🎫
✅ Hero EXP Points 💎
✅ Hero Shards 🧩⭐
✅ Skill Medals 🏅
❌ Survivor Tickets 🎫
❌ Radar Tasks 📡
----------
💡 Focus on higher tier shards for maximum points! Use saved EXP!
```

#### VS Day 5 - Total Mobilization
```
🆚 Day 5️⃣ - Total Mobilization ⚔️💪
----------
✅ Radar tasks 📡
✅ Speed-ups 🏗️🔬🏃‍♂️
✅🔋 Building/Tech ⚡⬆️
✅ Train Units - 🏃‍♂️📈🏆
----------
💡 Focus higher level
🌊 Waterfall training for maximum points!
```

### Server Announcement with Variables
```
📢 {server_name} Announcement

Hello everyone! This message is from {sender_name} of {alliance_name}.

Server reset occurs at {server_reset_time} daily.

For more info, contact your R5: {r5_name}

Sent on {date} at {time}
```

### Event Coordination
```
⚔️ Alliance Battle Prep - {alliance_name}

📅 Date: {date}
⏰ Time: {event_time}
📍 Location: {location}

🎯 Objectives:
- Coordinate with {r5_name}
- Follow {alliance_tag} battle plan
- Report to {sender_name}

{notes}
```

## Using Templates

### In Announcements (Instant)
1. Go to Discord → Announcements
2. Select template from **Use Template** dropdown
3. Click **Quick Variables** to add more
4. Edit message as needed
5. Send

### In Scheduled Messages
1. Go to Discord → Scheduled Messages
2. Select template from dropdown
3. Set schedule date/time
4. Variables will be replaced when message sends
5. Schedule message

### In Recurring Messages
1. Go to Discord → Recurring Messages
2. Select template from dropdown
3. Set frequency (daily/weekly/monthly)
4. Variables replaced on each send
5. Create recurring message

## Managing Templates

### View Templates
- **My Templates** tab shows all accessible templates
- Global templates marked with 🌍
- Alliance templates marked with 🏢

### Delete Templates
- Only creator or admin can delete
- Cannot delete global templates (admin only)
- Click **Delete** button in template card

### Admin: Review Submissions
1. Go to **Pending Approvals** tab
2. Review template content
3. Click **Approve** to make global
4. Click **Reject** to deny (with optional reason)

## Variable Replacement

### How It Works
1. User creates message with variables
2. System fetches current data when sending:
   - User data from users.json
   - Alliance data from alliances.json
   - Current date/time
3. Replaces variables with actual values
4. Sends processed message

### Example Transformation
**Template:**
```
Hi! I'm {sender_name} from {alliance_name}.
Contact our leader {r5_name}.
Sent {datetime}
```

**Becomes:**
```
Hi! I'm Commander123 from Fire & Fury.
Contact our leader CoolLeader.
Sent 2025-11-06 14:30
```

## Best Practices

1. **Descriptive Names** - Name templates clearly (e.g., "VS Day 2 Reminder")
2. **Use Variables** - Leverage variables for dynamic content
3. **Test First** - Test templates with instant messages before scheduling
4. **Document Custom Vars** - Include notes about what to fill in {custom} variables
5. **Keep Updated** - Review and update templates regularly
6. **Submit Good Ones** - Submit well-crafted templates for global use
7. **Alliance Coordination** - Share templates within alliance for consistency

## Limitations

- Maximum 2000 characters per template (Discord limit)
- Cannot edit templates (delete and recreate instead)
- Custom variables remain as placeholders
- Global templates require admin approval

## Troubleshooting

### Variables Not Replacing
- Ensure correct syntax: `{variable_name}` with curly braces
- Check user has IGN set in profile
- Verify alliance data exists in alliances.json
- Variables replace at send-time, not in preview

### Missing Data
- {sender_name} - Check user profile has IGN
- {alliance_name} - Verify user assigned to alliance
- {r5_name} - Check alliance has leader field
- {server_reset_time} - Set in server_config.json

### Permission Errors
- Template deletion: Only creator or admin
- Global approval: Admin only
- Template access: Must have R4+ or president role

## Use Cases

### Weekly Event Reminders
Create recurring weekly templates for regular events (VS Day, Raid Boss, etc.)

### Alliance Coordination
Templates for battle plans, resource sharing, diplomacy messages

### Server Announcements
Global templates for server-wide news, updates, maintenance

### Daily Reminders
Recurring daily templates for daily tasks, resets, events

## Integration

Templates integrate seamlessly with:
- ✅ Instant Announcements
- ✅ Scheduled Messages
- ✅ Recurring Messages

Same templates work across all message types!

## Version

- Feature: Discord Message Templates
- Version: 1.0.0
- Release Date: 2025-11-06
- Minimum Version: 3.6.0

## See Also

- [Discord Announcements](README.md)
- [Scheduled Messages](SCHEDULED-MESSAGES.md)
- [Recurring Messages](RECURRING-MESSAGES.md)
- [Discord Bot Setup](BOT-SETUP.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
