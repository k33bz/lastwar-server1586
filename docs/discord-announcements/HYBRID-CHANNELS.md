# Discord Channels - Hybrid Configuration (v2.0)

**Status:** ✅ Implemented
**Version:** 2.0.0
**Date:** 2025-11-04
**Improvement:** Self-service channel management for R5/R4

---

## What Changed in v2.0

### Before (v1.0)
❌ Admin had to configure ALL channels
❌ R5/R4 couldn't add their own channels
❌ Bottleneck for alliance leaders
❌ Manual JSON file editing required

### After (v2.0)
✅ R5/R4 can configure their own alliance channels
✅ Self-service via Alliance Edit UI
✅ Admin only manages global/cross-alliance channels
✅ User-friendly interface with validation
✅ Immediate updates, no admin intervention

---

## Hybrid Approach

### 🏰 Alliance Channels → `alliances.json` (R5/R4 Managed)

**Who can configure:** R5 and R4 users for their own alliances
**Where:** Via Alliance Edit UI page
**Use for:**
- Alliance-specific announcement channels
- Alliance event channels
- Alliance reminder channels
- Any channel specific to one alliance

**Example in `alliances.json`:**
```json
{
  "tag": "UvvU",
  "name": "veni vidi vici",
  "r5": {...},
  "discord": {
    "serverName": "UvvU Official",
    "inviteUrl": "https://discord.gg/...",
    "logoUrl": "images/discord-logos/UvvU.png",
    "channels": [
      {
        "id": "1234567890123456789",
        "name": "announcements",
        "type": "announcements",
        "enabled": true
      },
      {
        "id": "9876543210987654321",
        "name": "events",
        "type": "events",
        "enabled": true
      }
    ]
  }
}
```

### 🌐 Global Channels → `discord-channels.json` (Admin Only)

**Who can configure:** Admin only
**Where:** Manual file editing
**Use for:**
- Server-wide announcements (all alliances)
- NAP15 cross-alliance coordination
- Multi-alliance event planning
- Admin broadcast channels

**Example in `discord-channels.json`:**
```json
{
  "version": "2.0.0",
  "description": "Global and cross-alliance Discord channels (admin-managed only). Alliance-specific channels are configured in alliances.json by R5/R4.",
  "global_channels": [
    {
      "id": "1111222233334444555",
      "name": "server-announcements",
      "server_id": "999888777666555444",
      "server_name": "Last War Server 1586",
      "alliance": "*",
      "type": "global",
      "enabled": true,
      "description": "Server-wide announcements for all alliances"
    },
    {
      "id": "5555666677778888999",
      "name": "nap15-coordination",
      "server_id": "999888777666555444",
      "server_name": "Last War Server 1586",
      "alliance": "*",
      "type": "cross-alliance",
      "enabled": true,
      "description": "NAP15 cross-alliance coordination"
    }
  ]
}
```

---

## How R5/R4 Configure Channels

### Step-by-Step Guide

1. **Log into Admin Panel**
   - Use your magic link login

2. **Navigate to Alliance Edit**
   - Go to: `admin/alliance_edit.php?alliance=YOUR_TAG`
   - Or click "Edit" on your alliance

3. **Find Discord Channels Section**
   - Scroll to **"Discord Announcement Channels"**
   - Marked with ✨ New badge

4. **Add a Channel**
   - Click **"+ Add Channel"** button
   - Enter channel details:

   **Channel ID** (required)
   - Right-click channel in Discord
   - Select "Copy ID"
   - Paste the ID (18-20 digits)

   **Channel Name** (required)
   - Display name for the channel
   - Examples: "announcements", "events", "war-room"

   **Channel Type**
   - announcements (general announcements)
   - events (event coordination)
   - reminders (daily reminders)
   - general (other purposes)

   **Enabled**
   - Check to allow sending messages
   - Uncheck to disable temporarily

5. **Save Changes**
   - Click **"Save Changes"** at bottom
   - Channels are immediately available

6. **Test It**
   - Go to `admin/discord_announcements.php`
   - Your channel should appear in the list!
   - Send a test announcement

### Multiple Channels

You can add multiple channels per alliance:
- ✅ One for announcements
- ✅ One for events
- ✅ One for reminders
- ✅ As many as you need!

### Editing Channels

To edit existing channels:
1. Go back to Alliance Edit page
2. Modify the channel details
3. Click Save Changes

### Removing Channels

To remove a channel:
1. Click **"Remove Channel"** button
2. Confirm deletion
3. Click Save Changes

---

## Channel Access Logic

### How It Works

When a user opens Discord Announcements page, the system loads:

1. **Alliance Channels** from `alliances.json`
   - Filters by user's assigned alliances
   - R5/R4 of "UvvU" see channels from UvvU alliance only
   - Admin sees channels from ALL alliances

2. **Global Channels** from `discord-channels.json`
   - All users see global channels (alliance: "*")
   - Admin sees all global channels
   - R5/R4 see global channels they have access to

3. **Combined List**
   - Displays in single dropdown/list
   - Grouped by alliance name
   - Shows channel source (alliance vs global)

### Example User View

**R5 of UvvU sees:**
- UvvU → announcements (from alliances.json)
- UvvU → events (from alliances.json)
- Global → server-announcements (from discord-channels.json)
- Global → nap15-coordination (from discord-channels.json)

**Admin sees:**
- UvvU → announcements
- UvvU → events
- ORCE → announcements
- ORCE → war-room
- ... (all alliance channels)
- Global → server-announcements
- Global → nap15-coordination

---

## Benefits of Hybrid Approach

### For Alliance Leaders (R5/R4)
✅ **Self-Service** - Configure channels without waiting for admin
✅ **Immediate Updates** - Changes take effect instantly
✅ **User-Friendly** - Simple web form, no JSON editing
✅ **Control** - Manage your own channels
✅ **Validation** - UI validates channel IDs automatically

### For Admins
✅ **Less Work** - No need to configure every alliance channel
✅ **Scalability** - Alliances manage themselves
✅ **Focus on Global** - Only manage cross-alliance channels
✅ **Reduced Support** - Fewer "add my channel" requests

### For System
✅ **Organized** - Alliance data in one place
✅ **Maintainable** - Each alliance owns their data
✅ **Extensible** - Easy to add more channel features
✅ **Consistent** - Follows existing alliance edit patterns

---

## Migration from v1.0

If you already configured channels in old `discord-channels.json` (v1.0), here's how to migrate:

### Option 1: Move to Alliances (Recommended)

1. Note alliance-specific channels from `discord-channels.json`
2. Log in as admin or alliance R5
3. Edit each alliance
4. Add channels via UI
5. Remove from `discord-channels.json`

### Option 2: Keep as Global

If channels should be accessible by multiple alliances:
1. Move to `global_channels` array
2. Set `alliance: "*"` for all-access
3. Leave in `discord-channels.json`

### No Breaking Changes

Old format still works! System reads from both:
- `discord-channels.json` → Treated as global channels
- Gradually migrate to new format

---

## Troubleshooting

### Channel Not Appearing

**Problem:** Added channel but don't see it in announcements page

**Solutions:**
1. Check "Enabled" checkbox is checked
2. Verify channel ID is correct (18-20 digits)
3. Ensure you saved changes in Alliance Edit
4. Refresh Discord Announcements page
5. Check browser console for errors

### Invalid Channel ID Error

**Problem:** Error when saving: "Invalid Discord channel ID format"

**Solutions:**
1. Channel ID must be 15-20 digits
2. No spaces or special characters
3. Get ID from Discord (Developer Mode → Right-click → Copy ID)
4. Make sure you copied Channel ID, not Server ID or User ID

### Can't Edit Other Alliance Channels

**Problem:** R5/R4 can't edit another alliance's channels

**This is correct behavior!**
- R5/R4 can only edit their OWN alliance channels
- Admin can edit all alliance channels
- This prevents unauthorized access

### Channel Shows for Wrong Users

**Problem:** Channel appearing for users it shouldn't

**Check:**
1. Channel is in correct alliance's section in `alliances.json`
2. User has correct alliance assignment in `admin/users.json`
3. Channel `enabled` is true
4. Clear browser cache and refresh

---

## API Changes (v2.0)

### New Channel Loading Function

```php
function get_user_accessible_channels($user) {
    // 1. Load from alliances.json
    // 2. Load from discord-channels.json (global_channels)
    // 3. Filter by user permissions
    // 4. Return combined array
}
```

### Channel Object Format

```php
[
    'id' => 'channel_id_here',
    'name' => 'announcements',
    'type' => 'announcements',
    'enabled' => true,
    'alliance' => 'UvvU',           // Added by system
    'alliance_name' => 'veni vidi vici', // Added by system
    'server_name' => 'Discord Server',   // From alliance or config
    'source' => 'alliance'  // 'alliance' or 'global'
]
```

---

## Future Enhancements

Planned for future phases:

### Phase 3+
- **Channel Management UI** - Admin page to manage global channels
- **Bulk Operations** - Enable/disable multiple channels at once
- **Channel Templates** - Pre-configured channel sets
- **Permissions UI** - Fine-grained access control per channel
- **Channel Testing** - Test button to verify bot access
- **Usage Stats** - See which channels are used most

---

## Documentation Links

- **Phase 1 README:** `docs/discord-announcements/PHASE1-README.md`
- **Bot Setup Guide:** `docs/discord-announcements/BOT-SETUP.md`
- **Feature Spec:** `docs/FEATURE_REQUEST_DISCORD_BOT.md`
- **GitHub Issue:** https://github.com/k33bz/lastwar-server1586/issues/59

---

**Questions?** Create a GitHub issue with label `discord`

**Ready to configure your channels?** Go to Alliance Edit page and add your first channel! 🎉
