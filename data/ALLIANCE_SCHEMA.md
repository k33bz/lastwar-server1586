# Alliance Data Schema

This document outlines the complete data structure for alliances in `alliances.json`.

## Complete Alliance Object Structure

```json
{
  "rank": 1,
  "tag": "ALLY",
  "name": "Alliance Full Name",
  "r5": {
    "name": "R5 Display Name",
    "gameId": "123456789",
    "discordId": "987654321"
  },
  "signed": true,
  "power": 1234567890,

  "discord": {
    "serverName": "Alliance Discord Server",
    "inviteUrl": "https://discord.gg/invite",
    "logoUrl": "images/discord-logos/ALLY.png",
    "memberCount": 150
  },

  "crossServer": {
    "hasPartner": true,
    "servers": [1586, 1234, 5678],
    "partnerTags": ["ALLY", "ALY2", "ALY3"],
    "network": "Alliance Network Name"
  },

  "info": {
    "description": "Alliance description and about text",
    "founded": "2025-01-01",
    "languages": ["English", "Spanish", "Korean"],
    "timezone": "Global / EST / PST / etc",
    "recruiting": true,
    "requirements": {
      "minPower": 50000000,
      "minLevel": 25,
      "activity": "High / Medium / Low",
      "notes": "Additional recruitment requirements"
    }
  },

  "contact": {
    "recruitmentContact": "R4 Recruiter Name",
    "discordRecruitment": "#recruitment-channel"
  },

  "achievements": {
    "rankHistory": [
      {"rank": 1, "date": "2025-01-15"},
      {"rank": 2, "date": "2025-01-01"}
    ],
    "peakPower": 7000000000,
    "peakRank": 1,
    "specialties": ["Territory Control", "Event Participation", "PvP"]
  },

  "metadata": {
    "lastUpdated": "2025-10-10T12:00:00Z",
    "verified": true,
    "featured": false
  },

  "r5History": [
    {
      "r5Name": "Current R5",
      "gameId": "123456",
      "discordId": "discord123",
      "startDate": "2025-01-01T00:00:00Z",
      "endDate": null,
      "current": true,
      "signatures": [
        {
          "version": "1.0",
          "signedAt": "2025-01-01T00:00:00Z",
          "signedBy": "R5 Name",
          "notes": "Signature notes"
        }
      ]
    }
  ]
}
```

## Field Descriptions

### Required Fields (Always Present)
- **rank** (number): Alliance server ranking (1-50)
- **tag** (string): 4-letter alliance tag (displayed throughout site)
- **name** (string): Full alliance name
- **r5** (object|string): Current R5 leader (can be string or object with name/gameId/discordId)
- **signed** (boolean): Whether R5 has signed NAP15 agreement
- **power** (number): Total alliance power
- **r5History** (array): Leadership history with signatures

### Optional Sections (Conditionally Displayed)

#### discord
Only displayed if `discord.serverName` OR `discord.inviteUrl` exists.
- **serverName**: Discord server name
- **inviteUrl**: Discord invite link (if null, "Join Discord" button not shown)
- **logoUrl**: Path to discord server logo image
- **memberCount**: Number of members (if null, member count not displayed)

#### crossServer
Only displayed if `crossServer.hasPartner === true`.
- **hasPartner** (boolean): Controls if entire section displays
- **servers** (array): List of partner server numbers
- **partnerTags** (array): Alliance tags on partner servers
- **network**: Name of cross-server network

#### info
Always present, but individual fields conditionally displayed.
- **description**: About text (if null, "About" section not shown)
- **founded**: Alliance founding date
- **languages**: Array of languages spoken
- **timezone**: Primary timezone
- **recruiting** (boolean): Whether alliance is recruiting
- **requirements**: Recruitment requirements (only shown if recruiting === true)

#### contact
Only displayed if any field is not null.
- **recruitmentContact**: Contact person for recruitment
- **discordRecruitment**: Discord channel for recruitment

#### achievements
Only displayed if `peakPower` OR `peakRank` exists.
- **rankHistory**: Historical rank changes
- **peakPower**: Highest power reached
- **peakRank**: Best rank achieved
- **specialties**: Array of alliance strengths/focus areas

#### metadata
Internal use only (not displayed on website).
- **lastUpdated**: ISO 8601 timestamp
- **verified**: Whether data has been verified by R5
- **featured**: Whether to highlight on homepage

## Conditional Display Logic

The website automatically hides sections when:
1. **Discord Section**: Hidden if no `serverName` AND no `inviteUrl`
2. **Cross-Server Section**: Hidden if `hasPartner === false`
3. **Description Section**: Hidden if `description` is null/empty
4. **Recruiting Requirements**: Hidden if `recruiting === false`
5. **Contact Section**: Hidden if all contact fields are null
6. **Achievements Section**: Hidden if both `peakPower` and `peakRank` are null
7. **Languages/Timezone/Specialties**: Hidden if arrays are empty or values are null

This is implemented in `js/app.js` in the `openAllianceModal()` function (lines 753-1029).

## Minimal Alliance Object

For alliances without detailed information, use this minimal structure:

```json
{
  "rank": 10,
  "tag": "ALLY",
  "name": "Alliance Name",
  "r5": "R5 Name",
  "signed": false,
  "power": 1000000000,
  "r5History": [
    {
      "r5Name": "R5 Name",
      "gameId": null,
      "discordId": null,
      "startDate": "2025-05-19T00:00:00Z",
      "endDate": null,
      "current": true,
      "signatures": []
    }
  ]
}
```

## Data Population Priority

When expanding `alliances.json` to include all 50 server alliances:

1. **Start with minimal structure** for all alliances (ranks 16-50)
2. **Add power and R5 data** from game screenshots
3. **Expand top 15** with full details (discord, achievements, etc.)
4. **Request data** from alliance leaders via Discord
5. **Verify and update** metadata.verified field when R5 confirms

## Notes

- The website filters to show only top 15 in rankings display
- All 50 alliances should be in the JSON for future expansions
- Use `normalizeAlliance()` function in JS to set defaults for missing fields
- Discord logos should be 512x512 PNG files
- Alliance logos (for council cards) should be 70x70 PNG files
