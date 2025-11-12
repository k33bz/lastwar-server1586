# Council Rotation Tool - How It Works

## Overview

The Admin Panel Council Rotation Tool is a secure, fair, and automated system for managing the NAP15 Council's rotating membership. It ensures balanced representation for all top 15 alliances while preventing manipulation and abuse.

## Access Control

**Who Can Use It:**
- Admin role
- President role

**Authentication:**
- JWT session required
- CSRF token protection on all modification actions
- All actions are logged in audit trail with user identity

## How It Works

### Council Structure

1. **Permanent Members (Top 5)**: Always on the council
2. **Rotating Members (Ranks 6-15)**: Rotate in pairs each week
3. **Rotation Schedule**: Monday at 2:00 AM UTC (Sunday 10PM EDT)

### The Algorithm

The system uses a sophisticated fairness algorithm with multiple constraints:

#### 1. **Weighted Fair Selection**
Each alliance in the rotating pool (ranks 6-15) is assigned a weight based on:
- **Recent rotation count**: Alliances that rotated less in the last 10 weeks get higher priority
- **Cycle bonus (+2)**: Alliances that haven't rotated yet in the current cycle get bonus weight
- **Recent history penalty**: Strong penalties for alliances that rotated in the last 1-2 weeks (prevents back-to-back rotations)

#### 2. **No Back-to-Back Rotations**
- Minimum 2-week gap between rotations for the same alliance
- Recent rotation history tracked with sliding window
- Stronger penalties for more recent rotations (exponential decay)

#### 3. **Fair Distribution**
- Algorithm counts rotations over last 10 weeks (lookback window)
- Alliances with fewer rotations get higher selection weight
- Balances rotation counts over 52-week cycle (approximately 1 year)

#### 4. **Rank-Based Tiebreaker**
- When weights are equal, higher-ranked alliances get priority
- Ensures stability and recognizes alliance strength

### Generation Process

When the tool is run:

1. **Load Current Data**: Reads alliance rankings and power levels
2. **Determine Current Week**: Calculates week number since May 19, 2025 epoch
3. **Preserve Past**: All past and current weeks remain unchanged
4. **Generate Future**: Creates next 52 weeks using fairness algorithm
5. **Save Schedule**: Updates rotation-schedule.json atomically
6. **Notify R5s**: Sends email notifications to all R5 users

## Safeguards Against Abuse

### 1. **Weekly Regeneration Limit**
- **Limit**: Maximum once per 7 days
- **Enforcement**: Tracked by `lastRegenerationTimestamp` in schedule metadata
- **Error Message**: Shows days remaining until next regeneration allowed
- **Purpose**: Prevents repeated manipulation to get favorable outcomes

### 2. **Power Update Requirement**
- **Validation**: Alliance powers must be updated within last 7 days
- **Checked Against**: power-history.csv modification time
- **Error Message**: Prompts to update powers via Power Editor first
- **Purpose**: Ensures rotation is based on current rankings, not stale data

### 3. **Historical Preservation**
- **Immutable Past**: All weeks before next rotation are locked
- **No Retroactive Changes**: Past rotations cannot be altered
- **Audit Trail**: Complete history preserved in schedule file
- **Purpose**: Prevents rewriting history or invalidating past council decisions

### 4. **Access Control**
- **Role Restriction**: Only Admin and President roles
- **CSRF Protection**: Prevents cross-site request forgery attacks
- **Session Validation**: JWT tokens required and verified
- **Audit Logging**: Every regeneration logged with username and timestamp

### 5. **Fairness Constraints**
- **Algorithmic Fairness**: Weighted selection favors underrepresented alliances
- **Anti-Gaming**: Recent history penalties prevent pattern exploitation
- **Deterministic**: Given same input data, produces consistent results
- **Transparent**: Fairness statistics shown after regeneration

### 6. **Data Integrity**
- **Atomic Writes**: Schedule file updated atomically (all or nothing)
- **Metadata Tracking**: Records who regenerated, when, and from what rankings
- **Snapshot System**: Stores top 3 and top 15 snapshots for change detection
- **Validation**: Input data validated before generation begins

## Fairness Statistics

After regeneration, the tool displays:

- **Rotation Counts**: How many times each alliance will rotate in next 52 weeks
- **Distribution**: Visual verification that rotation counts are balanced
- **Expected Result**: Each alliance rotates approximately 10-11 times per year (52 weeks ÷ 5 pairs ≈ 10.4 rotations each)

## Email Notifications

When regenerated, the system:
1. Finds all users with R5 role
2. Excludes disabled users
3. Sends detailed email to each R5 user containing:
   - Next rotation week and date
   - Number of weeks regenerated
   - Fairness distribution statistics
   - Who performed the regeneration

## Example Workflow

1. **Alliance Powers Change**: Server rankings shift after major battles
2. **Update Powers**: Admin updates power-history.csv via Power Editor
3. **Regenerate Schedule**: President or Admin clicks "Regenerate Future Weeks"
4. **Validation**: System checks 7-day limits and power freshness
5. **Preview**: Confirmation modal shows what will happen
6. **Generation**: Algorithm creates next 52 weeks fairly
7. **Notification**: All R5 users receive email about new schedule
8. **Verification**: Fairness statistics confirm balanced distribution

## Key Design Principles

1. **Fairness First**: Algorithm prioritizes underrepresented alliances
2. **Transparency**: All actions logged and auditable
3. **Stability**: Past decisions never changed
4. **Prevention**: Multiple safeguards against manipulation
5. **Balance**: Long-term rotation equality over short-term optimization

## Technical Limits Summary

| Safeguard | Limit | Purpose |
|-----------|-------|---------|
| Regeneration Frequency | Once per 7 days | Prevent manipulation attempts |
| Power Update Freshness | Within 7 days | Ensure current rankings used |
| Minimum Gap Between Rotations | 2 weeks | Prevent back-to-back rotations |
| Lookback Window | 10 weeks | Balance recent vs long-term fairness |
| Future Weeks Generated | 52 weeks | ~1 year planning horizon |
| Access Control | Admin + President only | Restrict to trusted roles |
| CSRF Protection | Required token | Prevent unauthorized requests |

## Monitoring & Accountability

Every regeneration creates:
- Audit log entry with username, timestamp, and stats
- Email notifications to all R5 users
- Metadata in schedule file tracking who/when/why
- Fairness statistics for transparency

This multi-layered approach ensures the council rotation remains fair, transparent, and resistant to manipulation while maintaining flexibility for legitimate ranking changes.
