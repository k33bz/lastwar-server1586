# Version Update Checklist

When deploying updates to the website, update the version number in these files to ensure browsers fetch fresh content (cache-busting).

## Files to Update

### 1. **index.html** (3 locations)
```html
<!-- Line ~7: HTML Comment -->
Version: 1.4.2

<!-- Line ~54: Meta tag -->
<meta name="version" content="1.4.2">

<!-- Line ~55: Last updated -->
<meta name="last-updated" content="2025-10-08">

<!-- Line ~65: CSS link -->
<link rel="stylesheet" href="css/styles.css?v=1.4.2">

<!-- Line ~159: Council JS -->
<script src="data/council.js?v=1.4.2"></script>

<!-- Line ~162: App JS -->
<script src="js/app.js?v=1.4.2"></script>
```

### 2. **js/app.js** (1 location)
```javascript
// Line ~571: APP_VERSION variable
var APP_VERSION = '1.4.2';
```

### 3. **css/styles.css** (1 location)
```css
/* Line ~6: CHANGELOG comment */
v1.4.1 - 2025-10-08
```

## Version Numbering

Follow semantic versioning: `MAJOR.MINOR.PATCH`

- **MAJOR** (X.0.0): Breaking changes, complete redesign
- **MINOR** (1.X.0): New features, significant updates
- **PATCH** (1.0.X): Bug fixes, minor improvements

## Quick Update Script

Use Find & Replace to update all instances:

1. **Find**: `1.4.2` (current version)
2. **Replace**: `1.5.0` (new version)
3. **Files**: `index.html`, `js/app.js`, `css/styles.css`

## After Version Update

1. **Update changelog** in file headers
2. **Test locally**: `python -m http.server 8000`
3. **Deploy**: `python scripts/deploy-ftp.py`
4. **Verify**: Visit http://example.com and check browser console for version

## Cache-Busting Explanation

The `?v=1.4.2` query parameters tell browsers to treat each version as a unique file:
- `styles.css?v=1.4.2` is different from `styles.css?v=1.5.0`
- Browser downloads new version instead of using cached old version
- Users always see latest content without manually clearing cache

## Meta Tags Explanation

```html
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
```

These meta tags tell browsers:
- **Cache-Control**: Don't cache this page
- **Pragma**: Legacy directive for HTTP/1.0 browsers
- **Expires**: Content expired immediately (fetch fresh copy)

Combined with version query parameters, this ensures users always see the latest version!

## Testing Cache-Busting

1. Deploy a change with version `1.4.2`
2. Visit website and note content
3. Make a change and update to version `1.5.0`
4. Deploy again
5. Refresh website - new content appears immediately
6. Check browser DevTools Network tab - files show `?v=1.5.0`

---

**Current Version**: 1.4.2
**Last Updated**: 2025-10-08
