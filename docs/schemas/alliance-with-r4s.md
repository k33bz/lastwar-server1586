# Alliance Schema with R4 Support

## Overview
Extended alliance schema to support R4 (Rank 4) officers with voting delegation.

## New Fields

### `r4s` (Array)
Array of R4 officers for the alliance. Unlimited count.

```json
{
  "tag": "UvvU",
  "name": "veni vidi vici",
  "r5": {
    "name": "쿠치나 ᓚᘏᗢ",
    "gameId": null,
    "discordId": null
  },
  "r4s": [
    {
      "name": "PlayerName",
      "gameId": "12345",
      "discordId": "123456789012345678",
      "canVote": false,
      "role": "Recruiter",
      "addedDate": "2025-11-09T00:00:00Z"
    },
    {
      "name": "VotingOfficer",
      "gameId": "67890",
      "discordId": "987654321098765432",
      "canVote": true,
      "role": "Diplomat",
      "addedDate": "2025-11-09T00:00:00Z"
    }
  ]
}
```

## Field Definitions

### R4 Object
- **name** (string, required): In-game name of the R4
- **gameId** (string|null): In-game player ID
- **discordId** (string|null): Discord user ID for this R4
- **canVote** (boolean, default: false): Whether this R4 can vote on behalf of R5
- **role** (string|null): Optional role description (e.g., "Recruiter", "Diplomat")
- **addedDate** (string): ISO 8601 timestamp when R4 was added

## Voting Delegation Rules

1. **R5 Always Has Priority**: R5 can always vote if present
2. **R4 Delegation**: If R5 delegates (canVote=true), that R4 can vote when R5 is absent
3. **Multiple Delegates**: Multiple R4s can have canVote=true, but only ONE can submit a vote per poll
4. **No R4 Override**: R4 cannot override R5's vote

## Bot Integration

The Discord bot checks for eligible voters in this order:
1. R5's discordId
2. R4s where canVote=true (any of them can vote)

## Example Usage

### Alliance with Voting Delegation
```json
{
  "tag": "ORCE",
  "r5": {
    "name": "EchoJT",
    "discordId": "111111111111111111"
  },
  "r4s": [
    {
      "name": "BackupVoter",
      "discordId": "222222222222222222",
      "canVote": true,
      "role": "Deputy"
    },
    {
      "name": "Recruiter",
      "discordId": "333333333333333333",
      "canVote": false,
      "role": "Recruitment"
    }
  ]
}
```

In this example:
- EchoJT (R5) can vote
- BackupVoter (R4) can also vote (delegation enabled)
- Recruiter (R4) cannot vote (delegation disabled)

## Migration

Existing alliances without `r4s` field will be treated as having an empty array `[]`.

No data migration required - the field is optional and defaults to empty.
