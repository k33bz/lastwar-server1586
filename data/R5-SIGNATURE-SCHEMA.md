# R5 Signature History Schema

## Overview
Tracks the complete history of R5 leaders and their signatures of NAP15 rules versions. R5s may change frequently (weekly/monthly rotation), and each R5 must sign the current version of the rules.

## Core Concepts

1. **R5 History**: Running list of all R5s who have led an alliance
2. **Version Signatures**: Each R5 must sign each version of rules
3. **Current Status**: Derived from most recent signature for current version
4. **Historical Record**: Complete audit trail of who signed what and when

## Data Structure

### signature-history.json

```json
{
  "currentRulesVersion": "1.2",
  "alliances": [
    {
      "tag": "ORCE",
      "name": "Omega Force",
      "rank": 1,
      "r5History": [
        {
          "r5Name": "Original R5",
          "gameId": "12345678",
          "discordId": "username#1234",
          "startDate": "2025-05-19T00:00:00Z",
          "endDate": "2025-08-15T00:00:00Z",
          "signatures": [
            {
              "version": "1.0",
              "signedAt": "2025-05-20T14:30:00Z",
              "signedBy": "Original R5",
              "ipAddress": "xxx.xxx.xxx.xxx",
              "notes": "Initial NAP15 signature"
            },
            {
              "version": "1.1",
              "signedAt": "2025-07-10T09:15:00Z",
              "signedBy": "Original R5",
              "ipAddress": "xxx.xxx.xxx.xxx",
              "notes": "Signed amendment updates"
            }
          ]
        },
        {
          "r5Name": "New R5",
          "gameId": "87654321",
          "discordId": "newleader#5678",
          "startDate": "2025-08-16T00:00:00Z",
          "endDate": null,
          "current": true,
          "signatures": [
            {
              "version": "1.2",
              "signedAt": "2025-08-18T11:45:00Z",
              "signedBy": "New R5",
              "ipAddress": "xxx.xxx.xxx.xxx",
              "notes": "Signed updated rules"
            }
          ]
        }
      ]
    }
  ]
}
```

## Field Definitions

### Alliance Level
- `tag` (string): Alliance identifier
- `name` (string): Alliance full name
- `rank` (number): Current ranking
- `r5History` (array): List of all R5 leaders (chronological)

### R5 History Entry
- `r5Name` (string, required): R5 display name
- `gameId` (string, optional): In-game ID
- `discordId` (string, optional): Discord username
- `startDate` (string, required): When R5 took leadership (ISO 8601)
- `endDate` (string, nullable): When R5 left leadership (null if current)
- `current` (boolean, optional): Is this the current R5?
- `signatures` (array): All rule signatures by this R5

### Signature Entry
- `version` (string, required): Rules version signed (e.g., "1.2")
- `signedAt` (string, required): Timestamp of signature (ISO 8601)
- `signedBy` (string, required): Name of person who signed (verification)
- `ipAddress` (string, optional): IP for audit trail
- `notes` (string, optional): Additional context

## Signature Status Logic

### Current Status Calculation
```javascript
function getAllianceSignatureStatus(alliance) {
  const currentVersion = "1.2"; // From signature-history.json root
  const currentR5 = alliance.r5History.find(r5 => r5.current === true);

  if (!currentR5) {
    return { signed: false, status: "no_r5", message: "No R5 assigned" };
  }

  const hasSignedCurrent = currentR5.signatures.some(sig => sig.version === currentVersion);

  if (hasSignedCurrent) {
    const signature = currentR5.signatures.find(sig => sig.version === currentVersion);
    return {
      signed: true,
      status: "signed",
      signedAt: signature.signedAt,
      r5Name: currentR5.r5Name
    };
  }

  // Check if within grace period (7 days from start)
  const startDate = new Date(currentR5.startDate);
  const daysSinceStart = (Date.now() - startDate) / (1000 * 60 * 60 * 24);

  if (daysSinceStart <= 7) {
    return {
      signed: false,
      status: "grace_period",
      daysRemaining: Math.ceil(7 - daysSinceStart),
      r5Name: currentR5.r5Name
    };
  }

  return {
    signed: false,
    status: "overdue",
    daysOverdue: Math.floor(daysSinceStart - 7),
    r5Name: currentR5.r5Name
  };
}
```

## Display Requirements

### Signatories Section

**For Each Alliance, Show**:
- Alliance rank, tag, and name
- Current R5 name
- Signature status:
  - ✓ **Signed** (green) - Current R5 has signed current version
  - ⏳ **Pending** (yellow) - Within 7-day grace period (show days remaining)
  - ⚠️ **Overdue** (red) - Past grace period (show days overdue)
  - ❌ **No R5** (gray) - No R5 assigned

**On Click/Expand**:
- Full R5 history timeline
- All signatures by each R5
- Which versions each R5 signed

### R5 History View (Modal/Expandable)

```
ORCE - Omega Force
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

R5 History:

┌─ Current R5 ────────────────────────────────┐
│ Name: New R5                                 │
│ Started: Aug 16, 2025                        │
│ Game ID: 87654321                            │
│                                              │
│ Signatures:                                  │
│   ✓ Version 1.2 - Signed Aug 18, 2025       │
└──────────────────────────────────────────────┘

┌─ Previous R5 ───────────────────────────────┐
│ Name: Original R5                            │
│ Tenure: May 19 - Aug 15, 2025 (88 days)     │
│ Game ID: 12345678                            │
│                                              │
│ Signatures:                                  │
│   ✓ Version 1.0 - Signed May 20, 2025       │
│   ✓ Version 1.1 - Signed Jul 10, 2025       │
└──────────────────────────────────────────────┘
```

## Migration Strategy

### From Current Format (v1.0)

**Old `alliances.json`**:
```json
{
  "rank": 1,
  "tag": "ORCE",
  "name": "Omega Force",
  "r5": "R5 Name",
  "signed": true
}
```

**Migrate To `signature-history.json`**:
```json
{
  "tag": "ORCE",
  "name": "Omega Force",
  "rank": 1,
  "r5History": [
    {
      "r5Name": "R5 Name",
      "gameId": null,
      "discordId": null,
      "startDate": "2025-05-19T00:00:00Z",
      "endDate": null,
      "current": true,
      "signatures": [
        {
          "version": "1.0",
          "signedAt": "2025-05-19T00:00:00Z",
          "signedBy": "R5 Name",
          "notes": "Migrated from old format"
        }
      ]
    }
  ]
}
```

### Adding New R5

When R5 changes:
1. Set `endDate` on previous R5
2. Set `current: false` on previous R5
3. Add new R5 entry with `current: true`
4. New R5 has 7 days to sign current version

### Adding New Signature

When R5 signs:
1. Find current R5 in `r5History`
2. Add signature object to R5's `signatures` array
3. Include version, timestamp, and signer name

## Use Cases

### 1. R5 Changes Weekly
```javascript
// Alliance rotates R5 every week
// Track: R5_1 (week 1), R5_2 (week 2), R5_3 (week 3)
// Each must sign current version within 7 days
// History maintains all R5s and their signatures
```

### 2. Rules Update
```javascript
// Rules update from v1.2 to v1.3
// All current R5s must sign v1.3
// Previous signatures (v1.0, v1.1, v1.2) remain in history
// Status shows who has/hasn't signed new version
```

### 3. R5 Returns
```javascript
// Original R5 returns after 3 months
// History shows previous tenure
// Can see old signatures
// Must sign current version as new entry
```

## API/Functions Needed

### Core Functions
```javascript
getCurrentR5(allianceTag)
getAllR5History(allianceTag)
getSignatureStatus(allianceTag)
hasSignedVersion(allianceTag, version)
addNewR5(allianceTag, r5Data)
addSignature(allianceTag, r5Name, signatureData)
changeR5(allianceTag, oldR5Name, newR5Data)
```

### Query Functions
```javascript
getUnsignedAlliances(version)
getAlliancesInGracePeriod()
getOverdueAlliances()
getR5Tenure(allianceTag, r5Name)
getAllVersionsSignedBy(allianceTag, r5Name)
```

## Validation Rules

1. **Only one current R5 per alliance**
   - Exactly one R5 with `current: true`
   - All others must have `endDate`

2. **Chronological dates**
   - `endDate` must be after `startDate`
   - Next R5's `startDate` should be after/equal to previous `endDate`

3. **Signature versions**
   - Can only sign existing rule versions
   - Can't sign same version twice
   - Signature date must be after R5 start date

4. **Required fields**
   - `r5Name`, `startDate`, `signatures` are required
   - Each signature needs `version`, `signedAt`, `signedBy`

## Security Considerations

1. **IP Logging** (optional but recommended)
   - Track who signed from where
   - Detect fraud/unauthorized signatures

2. **Timestamp Verification**
   - Can't backdate signatures
   - Must be within reasonable time window

3. **Name Verification**
   - `signedBy` should match `r5Name`
   - Or be designated representative

4. **Audit Trail**
   - Never delete signatures
   - Keep complete history
   - Archive old R5 entries

## Future Enhancements

1. **Digital Signatures**
   - Cryptographic verification
   - Non-repudiation

2. **Delegation**
   - R4 can sign on behalf of R5
   - Track delegated signatures

3. **Notifications**
   - Alert when R5 changes
   - Remind to sign before grace period ends
   - Notify alliance when overdue

4. **Analytics**
   - Average R5 tenure by alliance
   - Signature compliance rate
   - Time to sign statistics

## Example: Complete Alliance

```json
{
  "currentRulesVersion": "1.2",
  "alliances": [
    {
      "tag": "ORCE",
      "name": "Omega Force",
      "rank": 1,
      "r5History": [
        {
          "r5Name": "Founder Leader",
          "gameId": "11111111",
          "discordId": "founder#0001",
          "startDate": "2025-05-19T00:00:00Z",
          "endDate": "2025-06-30T00:00:00Z",
          "current": false,
          "signatures": [
            {
              "version": "1.0",
              "signedAt": "2025-05-19T12:00:00Z",
              "signedBy": "Founder Leader",
              "notes": "Initial signature"
            }
          ]
        },
        {
          "r5Name": "Second Leader",
          "gameId": "22222222",
          "discordId": "second#0002",
          "startDate": "2025-07-01T00:00:00Z",
          "endDate": "2025-09-15T00:00:00Z",
          "current": false,
          "signatures": [
            {
              "version": "1.0",
              "signedAt": "2025-07-02T08:30:00Z",
              "signedBy": "Second Leader",
              "notes": "New R5 signature"
            },
            {
              "version": "1.1",
              "signedAt": "2025-08-10T14:20:00Z",
              "signedBy": "Second Leader",
              "notes": "Signed amendments"
            }
          ]
        },
        {
          "r5Name": "Current Leader",
          "gameId": "33333333",
          "discordId": "current#0003",
          "startDate": "2025-09-16T00:00:00Z",
          "endDate": null,
          "current": true,
          "signatures": [
            {
              "version": "1.2",
              "signedAt": "2025-09-17T10:00:00Z",
              "signedBy": "Current Leader",
              "notes": "Signed latest version"
            }
          ]
        }
      ]
    }
  ]
}
```

This shows 3 different R5s, each signing the version(s) active during their tenure.
