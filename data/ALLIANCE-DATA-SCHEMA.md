# Alliance Data Schema Documentation

This document defines the structure for alliance data in `alliances.json`.

## Schema Version: 2.0

### Full Alliance Object

```json
{
  "rank": 1,                        // Current ranking position (1-15)
  "tag": "ORCE",                    // 4-letter alliance tag (unique identifier)
  "name": "Omega Force",            // Full alliance name

  "r5": {
    "name": "R5 Name",              // R5 display name
    "gameId": "12345678",           // In-game ID (optional)
    "discordId": "username#1234"    // Discord username (optional)
  },

  "signed": true,                   // Has R5 signed the NAP15?

  "power": 6480480937,              // Current alliance power

  "discord": {
    "serverName": "Omega Force Official",           // Discord server name
    "inviteUrl": "https://discord.gg/example",      // Permanent invite link
    "logoUrl": "images/discord-logos/ORCE.png",     // Path to Discord server icon
    "memberCount": 150                               // Approximate member count (optional)
  },

  "crossServer": {
    "hasPartner": true,             // Has cross-server alliance?
    "servers": [1234, 5678],        // List of partner server numbers
    "partnerTags": ["ORCE2", "ORCE3"],  // Alliance tags on other servers
    "network": "Omega Network"      // Network name (optional)
  },

  "info": {
    "description": "Elite alliance focused on teamwork and strategy",  // Brief description
    "founded": "2025-01-15",        // Foundation date (YYYY-MM-DD)
    "languages": ["English", "Spanish"],  // Primary languages
    "timezone": "Global",           // Primary timezone or "Global"
    "recruiting": true,             // Currently accepting new members?
    "requirements": {
      "minPower": 50000000,         // Minimum power requirement
      "minLevel": 25,               // Minimum HQ level
      "activity": "High",           // Activity level: "Low", "Medium", "High"
      "notes": "Must participate in events"  // Additional requirements
    }
  },

  "contact": {
    "recruitmentContact": "R4Name",  // Who to contact for recruitment
    "discordRecruitment": "recruit-channel"  // Discord channel for recruitment
  },

  "achievements": {
    "rankHistory": [                // Historical rank positions
      {"date": "2025-10-01", "rank": 2},
      {"date": "2025-10-08", "rank": 1}
    ],
    "peakPower": 6500000000,        // Highest power achieved
    "peakRank": 1,                  // Highest rank achieved
    "specialties": ["Defense", "Territory Control"]  // Alliance specialties
  },

  "metadata": {
    "lastUpdated": "2025-10-10T12:00:00Z",  // When data was last updated
    "verified": true,               // Has alliance verified this information?
    "featured": false               // Should this alliance be featured?
  }
}
```

## Field Requirements

### Required Fields (Minimum)
- `rank` (number)
- `tag` (string, 4 chars)
- `name` (string)
- `r5.name` (string)
- `signed` (boolean)

### Recommended Fields
- `power` (number)
- `discord.serverName` (string)
- `discord.inviteUrl` (string)
- `info.description` (string)
- `info.recruiting` (boolean)

### Optional Fields
All other fields can be omitted or set to `null` if not applicable.

## Backward Compatibility

The schema is backward compatible with v1.0 data:
```json
{
  "rank": 1,
  "tag": "ORCE",
  "name": "Omega Force",
  "r5": "R5 Name",      // v1.0 format (string)
  "signed": true
}
```

Code will auto-detect and handle both formats:
- If `r5` is a string, treat as `r5.name`
- If `r5` is an object, use full structure

## Special Values

- `"N/A"` - Information not applicable
- `null` - Information not yet collected
- `[]` - Empty array (no items)
- `false` - Explicitly disabled/not available

## Example: Minimal Alliance

```json
{
  "rank": 15,
  "tag": "NiKi",
  "name": "NIKII",
  "r5": {
    "name": "R5 Name"
  },
  "signed": true,
  "power": 1275619637
}
```

## Example: Full Alliance

See `alliances.json` for ORCE (rank 1) for a complete example.

## Data Update Process

1. Update `alliances.json` with new information
2. Update `lastUpdated` timestamp in metadata
3. Deploy updated file
4. Browser will fetch new data on next page load (cache-busted by version)

## Images and Assets

### Alliance Logos
- Path: `images/logos/[TAG].png`
- Recommended size: 256x256px
- Format: PNG with transparency

### Discord Server Icons
- Path: `images/discord-logos/[TAG].png`
- Recommended size: 128x128px
- Format: PNG (can be square or circular)

## Validation

Before deploying, validate JSON syntax:
```bash
node -e "console.log(JSON.parse(require('fs').readFileSync('data/alliances.json', 'utf8')))"
```

Or use online validator: https://jsonlint.com/
