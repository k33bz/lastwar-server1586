# User Profile Schema

## Overview
User profiles link game accounts to Discord IDs, allowing self-service management.

## Profile Object Structure

```json
{
  "profile_id": "prof_20251109_abc123",
  "alliance_tag": "UvvU",
  "game_name": "PlayerName",
  "game_id": "12345",
  "discord_id": "123456789012345678",
  "discord_tag": "username#1234",
  "role": "r5",
  "verified": true,
  "created_at": "2025-11-09T00:00:00Z",
  "updated_at": "2025-11-09T12:00:00Z",
  "updated_by": "self",
  "metadata": {
    "last_alliance_sync": "2025-11-09T12:00:00Z",
    "admin_notes": null
  }
}
```

## Field Definitions

- **profile_id** (string): Unique identifier `prof_{date}_{random}`
- **alliance_tag** (string): Alliance tag from alliances.json
- **game_name** (string): In-game player name
- **game_id** (string|null): In-game player ID
- **discord_id** (string|null): Discord user ID
- **discord_tag** (string|null): Discord username#discriminator for display
- **role** (enum): One of: `r5`, `r4`, `member`, `admin`
- **verified** (boolean): Whether profile is verified by admin/R5
- **created_at** (string): ISO 8601 timestamp
- **updated_at** (string): ISO 8601 timestamp
- **updated_by** (enum): One of: `self`, `r5`, `admin`, `system`
- **metadata** (object): Additional tracking data

## Update Rules

### Self-Service Updates (users can update)
- `discord_id`
- `discord_tag`
- `game_id` (if not verified)

### R5 Updates (R5s can update their alliance members)
- All fields for their own alliance members
- Can verify profiles (`verified: true`)
- Can change roles (r5/r4/member)

### Admin Updates (admins can update)
- All fields for all profiles
- Can override any user/R5 changes

## Sync Behavior

### From alliances.json → Profiles
- When alliances.json is updated, sync R5 data to profiles
- Auto-create profile if R5 has discordId set
- Mark as `verified: true` and `updated_by: system`

### From Profiles → alliances.json
- When profile is verified and updated, sync back to alliances.json
- R5 profiles update `r5.discordId` field
- R4 profiles update `r4s` array

## Example Workflows

### New User Self-Registration
1. User goes to profile page
2. Selects their alliance from dropdown
3. Enters game name
4. Enters Discord ID
5. Profile created with `verified: false`
6. R5 or admin can verify later

### R5 Managing Their Alliance
1. R5 logs in (JWT auth)
2. Views their alliance members
3. Can add/edit/verify profiles
4. Can designate R4s with voting rights

### Admin Override
1. Admin views all profiles
2. Can correct any incorrect data
3. Changes marked as `updated_by: admin`

## Security

- Users can only update their own profile
- R5s can only update their alliance members
- Admins can update all profiles
- All changes logged in `updated_by` and `updated_at`
