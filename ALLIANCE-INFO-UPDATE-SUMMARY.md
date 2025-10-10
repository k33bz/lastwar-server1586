# Alliance Information System - Implementation Summary

## Overview
This document summarizes the new alliance information system that allows detailed tracking of alliance data including Discord servers, cross-server alliances, recruiting status, and more.

## What Was Implemented

### 1. Data Schema (v2.0)
**File**: `data/ALLIANCE-DATA-SCHEMA.md`

Comprehensive schema supporting:
- **Basic Info**: rank, tag, name, power
- **R5 Details**: name, game ID, Discord ID
- **Discord Integration**: server name, invite URL, logo, member count
- **Cross-Server**: partner servers, tags, network name
- **Alliance Info**: description, languages, timezone, recruiting, requirements
- **Contact**: recruitment contact, Discord channel
- **Achievements**: rank history, peak power/rank, specialties
- **Metadata**: last updated, verified status, featured flag

**Backward Compatible**: Works with existing v1.0 data (where r5 is just a string)

### 2. Server Information
**File**: `data/server-info.json`

Centralized server data:
- Server name, ID, region, open date
- Discord information (name, URL, logo, features)
- NAP15 details (active status, member count, version)
- Council configuration (seats, rotation schedule)
- Contact information

### 3. Example Alliance Data
**File**: `data/alliances.json` (ORCE fully populated)

ORCE alliance now includes:
- Full R5 object structure
- Discord server details
- Cross-server alliance info
- Recruiting requirements
- Alliance achievements
- All optional fields demonstrated

### 4. UI Components

#### Server Discord Banner
**Location**: Top of page, below header

Features:
- Discord logo (120x120, circular)
- Server name and description
- Feature tags (NAP15, Events, Diplomacy, News)
- Join Discord button with icon
- Member count (optional)
- Discord brand colors (#5865f2)
- Responsive design

**Files Modified**:
- `index.html` (lines 78-101)
- `css/styles.css` (lines 87-197, 1199-1219)
- `js/app.js` (renderServerDiscord function)

### 5. JavaScript Enhancements

**New Functions**:
- `normalizeAlliance(alliance)` - Handles v1.0 and v2.0 data formats
- `renderServerDiscord()` - Renders server Discord banner
- Updated `renderSignatories()` - Uses r5.name instead of r5 string

**Data Loading**:
- Added `serverInfo` global variable
- Fetches `data/server-info.json` on page load
- Gracefully handles missing logo files

**Files Modified**:
- `js/app.js` (lines 85, 196-267, 314-332, 900-930, 949)

### 6. Styling

**New CSS Classes**:
- `.server-discord-section` - Main container
- `.server-discord-banner` - Flexbox layout
- `.discord-logo-container` - Logo wrapper
- `.server-discord-logo` - Logo styling
- `.discord-info` - Text content area
- `.discord-server-name` - Server name heading
- `.discord-description` - Description text
- `.discord-features` - Feature tags container
- `.discord-feature-tag` - Individual feature pills
- `.discord-join` - Button container
- `.discord-join-button` - Join button with hover effects
- `.member-count` - Member count display

**Responsive Design**:
- Mobile: Stacks vertically, centers content
- Desktop: Horizontal layout with logo, info, and button

### 7. Documentation

**Files Created**:
- `data/ALLIANCE-DATA-SCHEMA.md` - Complete schema documentation
- `images/HOW-TO-ADD-DISCORD-LOGO.md` - Instructions for adding logo
- `ALLIANCE-INFO-UPDATE-SUMMARY.md` - This file

## How to Use

### Adding Alliance Information

1. **Edit** `data/alliances.json`
2. **Expand** any alliance with new fields:
   ```json
   {
       "rank": 1,
       "tag": "ORCE",
       "r5": {
           "name": "R5 Name",
           "gameId": "12345678"
       },
       "discord": {
           "inviteUrl": "https://discord.gg/example"
       },
       "info": {
           "recruiting": true,
           "requirements": {
               "minPower": 50000000
           }
       }
   }
   ```
3. **Save** and refresh the page

### Adding Server Discord Logo

See `images/HOW-TO-ADD-DISCORD-LOGO.md` for detailed instructions.

**Quick Method**:
1. Right-click Discord server icon → Copy Image
2. Paste into image editor → Save as `images/server-logo.png`
3. Refresh website

### Updating Server Information

1. **Edit** `data/server-info.json`
2. **Modify** fields as needed:
   - Discord invite URL
   - Server description
   - Feature list
   - Contact information
3. **Save** and refresh

## Future Enhancements

### Recommended Next Steps

1. **Alliance Detail Pages/Modals**
   - Click alliance card to view full details
   - Show Discord invite, recruiting info, achievements
   - Display cross-server alliance network

2. **Alliance Discord Integration**
   - Show Discord invite buttons per alliance
   - Display member counts (if available via API)
   - Show online status indicators

3. **Recruiting Dashboard**
   - Filter alliances by recruiting status
   - Show minimum requirements
   - Contact information prominently displayed

4. **Cross-Server Alliance Network**
   - Visual map of cross-server connections
   - Filter/highlight alliances in same network
   - Show server distribution

5. **Alliance Achievements**
   - Display rank history chart
   - Show peak power/rank badges
   - Highlight specialties with icons

6. **Admin Panel** (Future)
   - Edit alliance data via UI
   - Upload logos and images
   - Update recruiting status
   - Manage Discord invites

## Migration Notes

### Backward Compatibility

The system is **fully backward compatible** with v1.0 data:

```javascript
// v1.0 format (still works)
{
    "r5": "R5 Name"
}

// v2.0 format (new)
{
    "r5": {
        "name": "R5 Name",
        "gameId": "12345678"
    }
}
```

The `normalizeAlliance()` function automatically converts v1.0 to v2.0 format at runtime.

### Gradual Migration

You can update alliances gradually:
1. Keep most alliances in v1.0 format
2. Update individual alliances to v2.0 as information becomes available
3. No need to update all at once

## Technical Details

### Data Flow

1. **Page Load**:
   - Fetch `server-info.json` and `alliances.json`
   - Normalize alliance data (v1.0 → v2.0)
   - Render server Discord banner
   - Render alliance sections

2. **Server Discord Banner**:
   - Load from `server-info.json`
   - Display logo (if available)
   - Show features as tags
   - Join button links to Discord invite

3. **Alliance Data**:
   - Backward compatible handling
   - Optional fields gracefully ignored if missing
   - No errors if data incomplete

### Performance

- All data loaded in single parallel fetch
- No additional HTTP requests per alliance
- Images lazy-loaded on demand
- Total added payload: ~2KB (server-info.json)

### Browser Support

- Modern browsers (ES6 async/await)
- Fallback for missing images
- Responsive design (mobile-first)
- No breaking changes for older browsers

## Files Modified

### Created
- `data/ALLIANCE-DATA-SCHEMA.md`
- `data/server-info.json`
- `images/HOW-TO-ADD-DISCORD-LOGO.md`
- `images/discord-logos/` (directory)
- `ALLIANCE-INFO-UPDATE-SUMMARY.md`

### Modified
- `data/alliances.json` (ORCE example)
- `index.html` (Discord banner section)
- `css/styles.css` (Discord banner styles + responsive)
- `js/app.js` (data loading, normalization, rendering)

### No Changes Required
- `data/rules.json`
- `data/amendments.json`
- `data/rotation-schedule.json`
- `data/power-history.csv`
- All other existing files

## Version History

- **v2.0.0** (2025-10-10): Initial alliance information system
  - Comprehensive data schema
  - Server Discord integration
  - Backward compatible with v1.0 data

## Support

For questions or issues:
1. Check schema documentation: `data/ALLIANCE-DATA-SCHEMA.md`
2. Review example: ORCE alliance in `alliances.json`
3. Test with browser console (F12) for errors

## License

Part of the Server 1586 Last War website project.
