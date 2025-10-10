# .htaccess Configuration Guide

The `.htaccess` file configures Apache web server behavior for the Server 1586 website.

## What It Does

### 1. **HTTPS Redirect**
Automatically redirects all HTTP traffic to HTTPS for security.

```
http://example.com → https://example.com
```

### 2. **WWW Prefix**
Adds www. to the domain for consistency (except localhost for local development).

```
https://example.com → https://www.example.com
```

### 3. **Security Headers**
- **X-Frame-Options**: Prevents clickjacking attacks
- **X-Content-Type-Options**: Prevents MIME type sniffing
- **X-XSS-Protection**: Enables browser XSS protection
- **Referrer-Policy**: Controls referrer information

### 4. **Cache Control**
- **Images**: Cached for 1 month
- **CSS/JS**: Cached for 1 hour (works with version query parameters)
- **HTML**: No cache (always fetch fresh)
- **JSON**: Cached for 5 minutes

### 5. **Compression**
Enables Gzip compression for text-based files to reduce bandwidth.

### 6. **Security**
- Disables directory browsing
- Sets index.html as default document

## Testing the Redirects

### Test HTTP → HTTPS
Visit: `http://example.com`
Should redirect to: `https://www.example.com`

### Test non-www → www
Visit: `https://example.com`
Should redirect to: `https://www.example.com`

### Test Direct Access
Visit: `https://www.example.com`
Should load normally (no redirect)

## Browser Developer Tools Check

1. Open DevTools (F12)
2. Go to Network tab
3. Visit `http://example.com`
4. Look for:
   - Status code: **301** (Permanent Redirect)
   - Final URL: `https://www.example.com`

## Troubleshooting

### Redirects Not Working

**Check if .htaccess is uploaded:**
```bash
python scripts/deploy-ftp.py
```
Look for `.htaccess` in the uploaded files list.

**Verify server supports .htaccess:**
Contact hosting provider to ensure:
- Apache web server is being used
- `mod_rewrite` module is enabled
- `.htaccess` files are allowed (AllowOverride directive)

### Redirect Loops

If you experience infinite redirects:
1. Check if SSL certificate is properly installed
2. Verify hosting provider SSL configuration
3. Check if there are conflicting redirect rules

### Cache Not Working

If cache headers aren't applying:
1. Verify `mod_expires` is enabled on server
2. Check `mod_headers` is enabled on server
3. Contact hosting provider for module status

## Local Development

The `.htaccess` file includes this line to prevent www redirect on localhost:
```apache
RewriteCond %{HTTP_HOST} !^localhost [NC]
```

This allows local testing without redirects:
- `http://localhost:8000` works normally
- No redirect to www.localhost

## Modifying .htaccess

**Important**: Always test changes locally first if possible.

**After changes:**
1. Edit `.htaccess` file
2. Deploy: `python scripts/deploy-ftp.py`
3. Test all URLs to verify redirects work
4. Clear browser cache if needed (Ctrl+Shift+R)

## Security Considerations

### Why HTTPS?
- Encrypts data between browser and server
- Protects against man-in-the-middle attacks
- Required for modern web features
- Improves SEO rankings
- Builds user trust

### Why WWW?
- Consistency: All users see same URL
- Cookie handling: Better cross-subdomain control
- DNS flexibility: Easier to configure CDNs
- SEO: Avoids duplicate content issues

## Additional Resources

- [Apache mod_rewrite Documentation](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)
- [HTTP Security Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers)
- [.htaccess Best Practices](https://httpd.apache.org/docs/2.4/howto/htaccess.html)

---

**File Location**: `/.htaccess` (root directory)
**Deployment**: Automatically included when running `python scripts/deploy-ftp.py`
