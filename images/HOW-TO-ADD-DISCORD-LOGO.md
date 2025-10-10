# How to Add Discord Server Logo

The website is configured to display the Discord server icon at the top of the page. To add it:

## Method 1: From Discord Desktop App (Easiest)

1. Open Discord desktop app or web app
2. Navigate to the "Last War Server 1586" server
3. Right-click on the server icon (in the left sidebar)
4. Select **"Copy Image"** or **"Save Image As..."**
5. If using Copy: Paste into an image editor and save
6. Save the image as: `server-logo.png`
7. Place it in this directory: `C:\Users\k33bz\OneDrive\git\Server1586\images\`

## Method 2: From Browser Developer Tools

1. Open Discord in browser: https://discord.com
2. Navigate to Server 1586
3. Open Developer Tools (F12)
4. Go to Network tab
5. Refresh the page
6. Filter by "Images" or search for "icon"
7. Find the server icon URL (usually starts with `cdn.discordapp.com`)
8. Right-click → Open in new tab
9. Right-click image → Save as `server-logo.png`
10. Place in this directory

## Method 3: Screenshot and Crop

1. Take a screenshot of the Discord server icon
2. Crop to just the icon (square)
3. Resize to 256x256 pixels (recommended)
4. Save as `server-logo.png`
5. Place in this directory

## Recommended Specifications

- **Format**: PNG (with transparency if available)
- **Size**: 256x256 pixels (will be displayed at 120x120)
- **Filename**: `server-logo.png` (exact match, case-sensitive on some servers)
- **Location**: `images/` directory (project root)

## Fallback Behavior

If the logo file is not found:
- The image will be hidden automatically
- The Discord banner will still display with server name, description, and join button
- No error messages will be shown to users

## Testing

After adding the logo:
1. Refresh the website
2. Check if the logo appears in the Discord banner section
3. If not visible, check browser console for errors (F12)
4. Verify file path and filename match exactly

## Alternative: Update the Path

If you prefer to store the logo elsewhere or with a different filename:

1. Edit `data/server-info.json`
2. Update the `discord.logoUrl` field:
   ```json
   "discord": {
       "logoUrl": "images/your-custom-name.png"
   }
   ```
3. Save and refresh the website
