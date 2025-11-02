# Alliance Management Guide

## Overview
The alliance management system allows R4 and R5 users to edit alliance information that appears on the main site alliance cards.

## Access Levels

### R5 (Alliance Leaders)
- **Full Access**: Can edit all alliance information
- **Rule Signing**: Can sign server rules on behalf of the alliance
- **Alliance Name**: Can change alliance name and R5 information
- **All Fields**: Can edit all available fields in the alliance profile

### R4 (Alliance Officers)
- **Limited Access**: Can edit most alliance information
- **No Rule Signing**: Cannot sign server rules (R5 only)
- **Protected Fields**: Cannot edit alliance name or R5 leader information
- **Alliance Specific**: Only see alliances they are assigned to

### Admin
- **Full System Access**: Can edit any alliance
- **All Permissions**: Has all R5 permissions plus system management

## Available Fields for Editing

### Basic Information
- **Alliance Name** (R5 only)
- **Alliance Tag** (read-only, set by system)
- **Power** (managed by power editor)

### R5 Leader Information
- **R5 Name** (R5 only)
- **UID/Game ID** (optional)
- **Discord ID** (optional)

### Discord Server
- **Server Name**
- **Invite URL**
- **Logo URL** (path to logo image)

### Recruitment & Contact
- **Currently Recruiting** (checkbox)
- **Recruitment Contact** (in-game name or email)
- **Discord Recruitment** (Discord username or channel)

### Alliance Description
- **Description** (main alliance description)
- **Timezone** (e.g., Global, EST, PST)

### Recruitment Requirements
- **Minimum Power**
- **Minimum Level**
- **Activity Level** (Casual, Moderate, High, Hardcore)
- **Additional Requirements** (optional notes)

### Server Rules
- **Rule Signing** (R5 only)
- **Version Selection** (can sign different rule versions)
- **Signature Notes** (optional notes about signing)

## How to Use

### For R5 Users
1. Go to **Alliance Editor** from the dashboard (`allianceedit.php`)
2. Click **Edit** next to your alliance
3. Edit any fields you want to update
4. **Sign Rules**: Use the signature section to sign the latest server rules
5. Click **Save Changes**

### For R4 Users
1. Go to **Alliance Editor** from the dashboard (`allianceedit.php`)
2. You'll only see alliances you have access to
3. Click **Edit** next to an alliance you can manage
4. Edit available fields (alliance name and R5 info will be read-only)
5. Click **Save Changes**

## Important Notes

- **Rule Signing**: Only R5 can sign server rules. This is required for official alliance status.
- **Version Control**: You can sign different versions of the rules as they are updated.
- **Auto-Save**: Changes are saved immediately when you submit the form.
- **Validation**: The system validates all input and prevents invalid data.
- **Audit Trail**: All changes are logged for security and tracking.

## Data Structure

The alliance data is stored in `/data/alliances.json` and includes:

```json
{
  "tag": "ORCE",
  "name": "Omega Force",
  "r5": {
    "name": "EchoJT",
    "gameId": null,
    "discordId": null
  },
  "signed": false,
  "power": 7044519755,
  "discord": {
    "serverName": "Omega Force Official",
    "inviteUrl": null,
    "logoUrl": "images/discord-logos/ORCE.png"
  },
  "info": {
    "description": "Elite alliance focused on teamwork and strategy",
    "timezone": "Global",
    "recruiting": false,
    "requirements": {
      "minPower": 50000000,
      "minLevel": 25,
      "activity": "High",
      "notes": "Must participate in all alliance events"
    }
  },
  "contact": {
    "recruitmentContact": null,
    "discordRecruitment": null
  }
}
```

## Security Features

- **JWT Authentication**: All access requires valid JWT tokens
- **Role-Based Access**: Different permissions for different roles
- **Alliance-Specific Access**: R4 users only see their assigned alliances
- **Input Validation**: All form inputs are validated and sanitized
- **Audit Logging**: Changes are tracked for security

## Troubleshooting

### "Access Denied" Error
- Check your role assignment
- Verify you have access to the specific alliance
- Contact an admin if you should have access

### Cannot Edit Certain Fields
- R4 users cannot edit alliance name or R5 information
- Only R5 can sign server rules
- Some fields may be admin-only

### Changes Not Saving
- Check for form validation errors
- Ensure all required fields are filled
- Try refreshing the page and trying again

## Support

For technical issues or access problems, contact the server administrators.