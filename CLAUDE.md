# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Server 1586 is a static website for managing alliance rankings, rules, and council voting for a Last War game server. The site displays NAP15 (Non-Aggression Pact) member alliances, server rules with amendment tracking, and a rotating council voting system.

## Development Setup

This is a vanilla HTML/CSS/JavaScript project with no build process or dependencies.

**IMPORTANT:** The site now loads data from JSON files, which requires running from a web server (cannot use `file://` protocol).

### To develop:

1. Start a local web server in the project directory:
   - **Python:** `python -m http.server 8000` (then visit http://localhost:8000)
   - **Node.js:** `npx http-server -p 8000`
   - **VS Code:** Use "Live Server" extension (right-click index.html → "Open with Live Server")
   - **PHP:** `php -S localhost:8000`

2. Open http://localhost:8000 in your browser

3. No installation or build steps required

## File Structure

- `index.html` - Main page structure with sections for rankings, council, rules, and signatories
- `js/app.js` - All rendering logic, DOM manipulation, user interactions, and data loading
- `data/` - Data and logic files:
  - `alliances.json` - Top 15 alliance rankings with R5 names and signature status (pure data)
  - `rules.json` - Server rules as structured objects (title, content, items) (pure data)
  - `amendments.json` - Rule change history with version tracking (pure data)
  - `rotation-schedule.json` - Pre-generated council rotation schedule from Week 1 (pure data)
  - `council.js` - Timezone and countdown utility functions (JavaScript functions)
- `css/styles.css` - All styling including podium, council grid (5-2 layout), and responsive design
- `scripts/` - Utility scripts:
  - `generate-rotation-schedule.js` - Initial schedule generator (Node.js, run once)
  - `update-rotation-schedule.py` - Smart schedule updater (Python 3, use ongoing)

## Key Architecture Patterns

### Data Flow
1. On page load, `app.js` fetches JSON data asynchronously:
   - `data/alliances.json` → `alliances` array
   - `data/rules.json` → `serverRules` array
   - `data/amendments.json` → `amendments` array
   - `data/rotation-schedule.json` → `rotationSchedule` object
2. After data loads, all sections render
3. Amendment system applies changes to rules dynamically based on `showChangesEnabled` flag
4. Council section reads pre-generated schedule and filters to show: previous week, current week, next 4 weeks
5. Countdown timer updates every second showing time until next rotation
6. `council.js` provides utility functions for timezone formatting and countdown (loaded synchronously)
7. No server-side processing - everything is client-side rendering

### Council Rotation System
- **Permanent members**: Top 5 alliances (ranks 1-5)
- **Rotating members**: 2 alliances from ranks 6-15, change weekly
- **Rotation timing**: Every Sunday at 10:00 PM EDT
- **Week calculation**: Based on fixed epoch (May 18, 2025, 10 PM EDT as Week 1)
- **Schedule**: Pre-generated in `rotation-schedule.json` using fair round-robin algorithm
- **Fairness**: All alliances rotate equally before any alliance repeats (10 alliances → 5 weeks per cycle)
- **Display**: Shows previous week (greyed), current week (highlighted), next 4 weeks
- **Countdown**: Real-time countdown timer updates every second
- **Layout**: 5-2 grid (5 permanent in row 1, 2 rotating in row 2)

### Amendment System
The site supports versioned rule changes:
- Amendments are stored separately from base rules
- `applyAmendments()` modifies rules at runtime
- Two display modes:
  - **Show Changes ON**: Highlights additions (+) in green and removals (−) with strikethrough
  - **Show Changes OFF**: Clean view with amendments fully integrated
- Process: Deep copy `serverRules` → apply amendments → render

### Rendering Functions
All rendering is done in `app.js`:
- `renderPodium()` - Top 3 alliances with trophy emojis
- `renderAllianceGrid()` - Ranks 4-15 in grid layout
- `renderSignatories()` - R5 signature status for all alliances
- `renderRules()` - Server rules with amendment markers
- `renderCouncil()` - Council members with 5-2 grid layout
- `renderAmendments()` - Collapsible amendment history

## Important Implementation Details

### Updating Alliance Data
Edit `data/alliances.json` to update rankings or R5 information. Changes take effect on page reload. The array order determines rank display.

**JSON Structure:**
```json
[
  {
    "rank": 1,
    "tag": "UvvU",
    "name": "veni vidi vici",
    "r5": "R5 Name",
    "signed": true
  }
]
```

### Adding Rule Amendments
1. Add entry to `data/amendments.json` with version, date, title, and changes array
2. Changes use `"type": "add"` or `"type": "remove"` with `"text"` content
3. Version number auto-updates in UI from latest amendment
4. Amendment IDs are generated from `version + title` to ensure uniqueness

**JSON Structure:**
```json
[
  {
    "version": "1.2",
    "date": "2025-10-05",
    "title": "Rule Title",
    "changes": [
      {
        "type": "add",
        "text": "New rule text to add"
      },
      {
        "type": "remove",
        "text": "Old rule text to remove"
      }
    ]
  }
]
```

### Council Rotation Schedule Management

**Updating Schedule (Recommended Method):**
```bash
python scripts/update-rotation-schedule.py
```

This Python script:
- Reads current top 15 alliances from `alliances.json`
- **Creates `rotation-schedule.json` if it doesn't exist** (automatic initialization)
- Preserves all past weeks (historical record)
- Generates next 52 weeks from the upcoming rotation
- Ensures fair distribution by looking back 10 weeks
- Handles alliance rank changes gracefully (new alliances spread evenly, no catch-up bunching)
- Provides detailed fairness report

**Only requires:** `data/alliances.json` (schedule file created automatically if missing)

**When to run:**
- After alliance rankings change in `alliances.json`
- Periodically to extend schedule into the future
- When manual overrides are needed for specific weeks

**Initial Generation (One-time use):**
```bash
node scripts/generate-rotation-schedule.js
```
Only needed for completely regenerating schedule from Week 1.

**Manually Editing Schedule:**
- Edit `data/rotation-schedule.json` directly to override specific weeks
- Each week entry format:
  ```json
  {
    "weekNumber": 21,
    "startDate": "2025-10-13T02:00:00.000Z",
    "rotatingMembers": ["STR8", "EPIC"]  // Alliance tags (NOT ranks)
  }
  ```
- **Important:** Uses alliance **tags** not ranks (stable when rankings change)
- Changes take effect on page reload
- Manual edits are preserved when running update script (only future weeks are regenerated)

**Week Calculation:**
```javascript
getCurrentWeekNumber() // Returns current week based on Week 1 epoch
```
Weeks reset Sunday 10 PM EDT. Week 1 epoch: May 18, 2025, 10 PM EDT

### Adding Logos
Currently uses text placeholders (70x70 divs showing alliance tags). To add real logos:
1. Create `images/logos/` directory
2. Add logo files named `[TAG].png` (e.g., `UvvU.png`)
3. Update `createMemberCard()` in `app.js` line 268-270 to replace placeholder with `<img>` tag

### Collapsible Sections
Three collapsible sections use similar patterns:
- Rules section: `toggleRules()`
- Amendments section: `toggleAmendments()`
- Individual amendments: `toggleAmendmentVersion(versionId)`

Each toggles `.active` class which controls height/visibility via CSS.

## Code Versioning

All code files include changelog comments at the top documenting changes. When modifying code, update the changelog with version number, date, and description of changes.

Current versions:
- HTML: v1.3.2 (2025-10-06)
- JS (app.js): v1.6.0 (2025-10-07) - Now uses alliance tags in rotation schedule
- JS (council.js): v2.0.0 (2025-10-07) - Simplified to utility functions only
- CSS: v1.3.2 (2025-10-06)
- Python (update-rotation-schedule.py): v2.0.0 - Uses alliance tags instead of ranks

**Data files** (JSON) do not have version headers - they are pure data. Data version is tracked via the `amendments.json` version field.

## Timezone Display

The rotation schedule includes timezone tooltips that appear on hover, showing times in:
- GMT (primary display)
- EDT/EST, PDT/PST, BRT, KST, AEST/AEDT, CET/CEST (tooltip)

Functions: `formatGMT()` and `formatAllTimezones()` in `council.js`.

## Responsive Design

CSS includes mobile breakpoints for screens under 768px:
- Podium switches from flexbox to vertical stack
- Council grid changes from 5-column to 2-column
- Alliance cards become full-width
- Font sizes reduce for better mobile readability

---

## AWS Lambda Backend Migration Plan

The current architecture is a static site with client-side rendering. To enable dynamic updates via a backend API, the following migration is planned:

### Current State (v1.x)
- **Frontend**: Static HTML/CSS/JS served from any web server
- **Data**: Hardcoded in `data/*.js` files as JavaScript constants
- **Updates**: Manual file edits and redeployment required
- **Hosting**: Any static host (S3, GitHub Pages, Netlify, etc.)

### Target Architecture (v2.0)

#### Frontend (S3 + CloudFront)
```
frontend/
├── index.html
├── css/
│   └── styles.css
├── js/
│   ├── app.js           # Updated to fetch from API
│   └── config.js        # API endpoint configuration
└── images/
    └── logos/
```

**Changes required:**
- Convert `data/*.js` files to API calls
- Update `app.js` to fetch data on load: `fetch('/api/alliances')`, `fetch('/api/rules')`, etc.
- Add loading states and error handling
- Add `config.js` with API Gateway URL

#### Backend (AWS Lambda + API Gateway)
```
backend/
├── functions/
│   ├── getAlliances/
│   │   ├── index.js           # GET /alliances
│   │   └── package.json
│   ├── updateAlliances/
│   │   ├── index.js           # PUT /alliances
│   │   └── package.json
│   ├── getRules/
│   │   ├── index.js           # GET /rules
│   │   └── package.json
│   ├── getAmendments/
│   │   ├── index.js           # GET /amendments
│   │   └── package.json
│   ├── addAmendment/
│   │   ├── index.js           # POST /amendments
│   │   └── package.json
│   └── updateCouncilSchedule/
│       ├── index.js           # POST /council/override
│       └── package.json
├── layers/
│   └── common/                # Shared utilities
│       ├── nodejs/
│       │   └── node_modules/
│       └── package.json
└── infrastructure/
    ├── template.yaml          # AWS SAM template
    └── serverless.yml         # OR Serverless Framework config
```

#### Data Storage (DynamoDB)

**Tables:**

1. **alliances** (Primary Key: `rank`)
   ```json
   {
     "rank": 1,
     "tag": "UvvU",
     "name": "veni vidi vici",
     "r5": "R5 Name",
     "signed": true,
     "lastUpdated": "2025-10-06T12:00:00Z"
   }
   ```

2. **rules** (Primary Key: `ruleId`)
   ```json
   {
     "ruleId": "nap15-overview",
     "title": "NAP15 Overview",
     "content": ["...", "..."],
     "type": "paragraph",
     "order": 1
   }
   ```

3. **amendments** (Primary Key: `version`, Sort Key: `title`)
   ```json
   {
     "version": "1.2",
     "title": "Insults & Malicious Language",
     "date": "2025-10-05",
     "changes": [...]
   }
   ```

4. **council_overrides** (Primary Key: `weekNumber`)
   ```json
   {
     "weekNumber": 20,
     "overrideMembers": [6, 8],  // Alliance ranks
     "setBy": "admin-user-id",
     "setAt": "2025-10-06T12:00:00Z",
     "reason": "Emergency rotation change"
   }
   ```

**Alternative:** Use S3 JSON files instead of DynamoDB for simpler deployment (suitable for low-traffic, infrequent updates)

#### API Endpoints

**Public (no auth required):**
- `GET /api/alliances` - Returns all alliance data
- `GET /api/rules` - Returns server rules
- `GET /api/amendments` - Returns amendment history
- `GET /api/council/current` - Returns current week's voting members
- `GET /api/council/schedule` - Returns rotation schedule

**Admin (requires authentication):**
- `PUT /api/alliances` - Update alliance rankings/data
- `POST /api/alliances` - Add new alliance
- `DELETE /api/alliances/{rank}` - Remove alliance
- `POST /api/amendments` - Add new amendment
- `POST /api/council/override` - Override weekly rotation

#### Authentication & Authorization
- **API Gateway + Cognito** for user authentication
- **IAM roles** for Lambda execution
- **API Keys** for simple admin access (alternative to Cognito)
- Store admin credentials in **AWS Secrets Manager**

#### Deployment Options

**Option A: AWS SAM (Serverless Application Model)**
```yaml
# template.yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31

Resources:
  AlliancesFunction:
    Type: AWS::Serverless::Function
    Properties:
      Handler: index.handler
      Runtime: nodejs20.x
      CodeUri: functions/getAlliances/
      Events:
        Api:
          Type: Api
          Properties:
            Path: /alliances
            Method: GET

  AlliancesTable:
    Type: AWS::DynamoDB::Table
    Properties:
      TableName: server1586-alliances
      AttributeDefinitions:
        - AttributeName: rank
          AttributeType: N
      KeySchema:
        - AttributeName: rank
          KeyType: HASH
      BillingMode: PAY_PER_REQUEST
```

**Deploy:** `sam build && sam deploy --guided`

**Option B: Serverless Framework**
```yaml
# serverless.yml
service: server1586-backend

provider:
  name: aws
  runtime: nodejs20.x
  region: us-east-1

functions:
  getAlliances:
    handler: src/handlers/alliances.getAll
    events:
      - httpApi:
          path: /alliances
          method: GET

resources:
  Resources:
    AlliancesTable:
      Type: AWS::DynamoDB::Table
      Properties:
        TableName: ${self:service}-alliances
        AttributeDefinitions:
          - AttributeName: rank
            AttributeType: N
        KeySchema:
          - AttributeName: rank
            KeyType: HASH
        BillingMode: PAY_PER_REQUEST
```

**Deploy:** `serverless deploy`

**Option C: AWS CDK (TypeScript/Python)**
- Most powerful, programmatic infrastructure definition
- Best for complex setups with multiple environments

#### Migration Steps

1. **Phase 1: Data Migration**
   - Convert `data/*.js` to JSON format
   - Create DynamoDB tables or S3 bucket
   - Import existing data

2. **Phase 2: Backend Development**
   - Create Lambda functions for each endpoint
   - Implement CRUD operations
   - Add council override logic
   - Set up API Gateway

3. **Phase 3: Frontend Updates**
   - Add `config.js` to configure API endpoints
   - Update fetch URLs in `app.js` from `data/*.json` to API Gateway URLs
   - Add authentication for admin operations (if needed)
   - Update `council.js` to check for overrides from API
   - Test with local API endpoint

4. **Phase 4: Authentication**
   - Set up Cognito user pool (or API keys)
   - Protect admin endpoints
   - Add login UI for admin panel (optional)

5. **Phase 5: Deployment**
   - Deploy Lambda functions
   - Deploy frontend to S3 + CloudFront
   - Configure custom domain (optional)
   - Set up CI/CD pipeline (GitHub Actions, AWS CodePipeline)

6. **Phase 6: Admin UI (Future Enhancement)**
   - Create admin dashboard for managing data
   - Add forms for updating alliances, rules, amendments
   - Council override interface

#### Cost Estimates (AWS)
- **Lambda**: Free tier covers 1M requests/month, then $0.20 per 1M requests
- **API Gateway**: $1 per million requests (HTTP API)
- **DynamoDB**: Free tier covers 25GB storage + 25 read/write capacity units
- **S3 + CloudFront**: ~$1-5/month for static hosting
- **Total estimated**: $0-10/month for low-medium traffic

#### Backward Compatibility
The current v1.4.0 already uses JSON data files, making backend migration easier:
- ✅ Data is already in JSON format (ready for API)
- ✅ Frontend already uses async/await for data loading
- ✅ Error handling already in place

For migration:
- Keep `data/*.json` files as fallback
- Frontend can check if API is available, falls back to static JSON
- Gradual migration without breaking existing functionality

**Example config.js for dual-mode:**
```javascript
const config = {
  useAPI: true,  // Set to false to use local JSON files
  apiBaseURL: 'https://api.server1586.com/v1',
  endpoints: {
    alliances: '/alliances',
    rules: '/rules',
    amendments: '/amendments'
  }
};
```

#### Repository Structure After Migration
```
Server1586/
├── frontend/              # Static site (current code, updated for API)
├── backend/               # Lambda functions + infrastructure
├── data/                  # Legacy (keep for reference/backup)
├── scripts/               # Migration and deployment scripts
├── .github/
│   └── workflows/
│       ├── deploy-frontend.yml
│       └── deploy-backend.yml
├── CLAUDE.md
└── README.md
```

This migration enables dynamic updates without redeployment, supports multiple administrators, and scales automatically with AWS Lambda.
