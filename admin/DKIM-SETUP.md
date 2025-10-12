# DKIM and Email Authentication Setup Guide

This guide will help you configure DKIM, improve DMARC, and ensure your emails don't go to spam.

## Prerequisites
- Access to cPanel for example.com
- Admin access to DNS settings (usually in cPanel or domain registrar)

---

## Part 1: Enable DKIM in cPanel

### Step 1: Access Email Deliverability
1. Log into **cPanel** for example.com
2. Search for **"Email Deliverability"** in the search bar
3. Click on **Email Deliverability** tool

### Step 2: Check Domain Status
You should see `example.com` listed with status indicators for:
- SPF (should show ✓ Valid)
- DKIM (likely shows ⚠ or ✗)
- Reverse DNS

### Step 3: Enable DKIM
1. Click **"Manage"** next to `example.com`
2. Look for **DKIM** section
3. Click **"Install the suggested record"** or **"Enable DKIM"**
4. cPanel will automatically:
   - Generate a 2048-bit DKIM key pair
   - Create the DNS TXT record
   - Install it for you

### Step 4: Verify DKIM Installation
After a few minutes (DNS propagation), run this command to verify:
```bash
nslookup -type=txt default._domainkey.example.com
```

You should see a long TXT record starting with `v=DKIM1;`

---

## Part 2: Improve DMARC Record

### Current DMARC Record
```
v=DMARC1; p=none;
```
This is monitoring only and provides minimal protection.

### Recommended DMARC Record

**For Testing (Start Here):**
```
v=DMARC1; p=none; rua=mailto:dmarc-reports@example.com; ruf=mailto:dmarc-reports@example.com; pct=100; adkim=r; aspf=r;
```

**For Production (After Testing):**
```
v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@example.com; ruf=mailto:dmarc-reports@example.com; pct=100; adkim=r; aspf=r;
```

**For Maximum Protection (Later):**
```
v=DMARC1; p=reject; rua=mailto:dmarc-reports@example.com; ruf=mailto:dmarc-reports@example.com; pct=100; adkim=s; aspf=s;
```

### What Each Part Means:
- `v=DMARC1` - Version 1
- `p=quarantine` - Put failing emails in spam (use `none` for testing, `reject` for strict)
- `rua` - Where to send aggregate daily reports
- `ruf` - Where to send forensic (detailed) failure reports
- `pct=100` - Apply policy to 100% of emails
- `adkim=r` - Relaxed DKIM alignment (r=relaxed, s=strict)
- `aspf=r` - Relaxed SPF alignment

### How to Update DMARC:

**Option A: In cPanel (if available)**
1. Go to **Zone Editor** in cPanel
2. Find the `_dmarc` TXT record
3. Click **Edit**
4. Replace the value with the recommended record
5. Save

**Option B: At Domain Registrar**
1. Log into Namecheap (or your DNS provider)
2. Go to **Advanced DNS** for example.com
3. Find the TXT record for `_dmarc`
4. Edit and replace with recommended record
5. Save (may take up to 48 hours to propagate)

---

## Part 3: Create DMARC Reports Mailbox

Create a mailbox to receive DMARC reports:

1. In cPanel, go to **Email Accounts**
2. Create new email: `dmarc-reports@example.com`
3. Set a password
4. This mailbox will receive XML reports of email authentication results

---

## Part 4: Verify All Records

After setup, verify all three records:

### Check SPF:
```bash
nslookup -type=txt example.com
```
Should show: `v=spf1 +a +mx +ip4:68.65.120.147 include:spf.web-hosting.com ~all`

### Check DKIM:
```bash
nslookup -type=txt default._domainkey.example.com
```
Should show: `v=DKIM1; k=rsa; p=MIIBIjANBg...` (long key)

### Check DMARC:
```bash
nslookup -type=txt _dmarc.example.com
```
Should show your updated DMARC policy

---

## Part 5: Test Email Deliverability

### Option 1: Mail-Tester (Recommended)
1. Go to https://www.mail-tester.com/
2. They'll show you a unique email address like `test-xyz@mail-tester.com`
3. Send a test email from `no-reploy@example.com` to that address
4. Go back to mail-tester and click **"Then check your score"**
5. **Aim for 9/10 or 10/10**

### Option 2: Using Python Test Script
```bash
python test-smtp.py test-xyz@mail-tester.com
```
(Replace with the address from mail-tester.com)

### Option 3: Send to Gmail
Send a test email to your Gmail account:
1. Send test: `python test-smtp.py your-gmail@gmail.com`
2. Check if it arrives in **Inbox** (good) or **Spam** (bad)
3. In Gmail, click **"Show original"** to see authentication results
4. Look for:
   - `SPF: PASS`
   - `DKIM: PASS`
   - `DMARC: PASS`

---

## Part 6: Gradual DMARC Policy Rollout

Don't go straight to `p=reject`! Follow this timeline:

### Week 1-2: Monitoring
```
v=DMARC1; p=none; rua=mailto:dmarc-reports@example.com;
```
- Monitor reports
- Ensure all legitimate emails pass SPF and DKIM

### Week 3-4: Quarantine
```
v=DMARC1; p=quarantine; rua=mailto:dmarc-reports@example.com;
```
- Failed emails go to spam
- Monitor for false positives

### Week 5+: Reject (Optional)
```
v=DMARC1; p=reject; rua=mailto:dmarc-reports@example.com;
```
- Failed emails are rejected outright
- Maximum protection against spoofing

---

## Troubleshooting

### DKIM Not Working
- Wait 30-60 minutes for DNS propagation
- Check cPanel DNS Zone Editor to ensure record was added
- Verify the selector is `default` (most common)
- Try: `nslookup -type=txt default._domainkey.example.com`

### DMARC Reports Not Arriving
- Ensure `dmarc-reports@example.com` mailbox exists
- Reports are sent daily (usually once every 24 hours)
- Check spam folder
- Reports are in XML format (you may need a parser)

### Emails Still Going to Spam
- Check mail-tester.com score (should be 8+/10)
- Verify SPF, DKIM, DMARC all pass
- Check email content (avoid spammy words)
- Ensure sending from proper domain
- Check IP reputation at: https://mxtoolbox.com/blacklists.aspx

### Common Issues:
1. **Wrong DKIM Selector** - Should be `default`, sometimes `mail` or `dkim`
2. **DNS Not Propagated** - Wait 1-24 hours
3. **DKIM Key Too Long** - cPanel handles this automatically
4. **SPF Too Restrictive** - Use `~all` not `-all`

---

## Expected Results After Full Setup

### Email Headers Should Show:
```
Authentication-Results:
    spf=pass smtp.mailfrom=no-reploy@example.com
    dkim=pass header.d=example.com
    dmarc=pass (p=QUARANTINE sp=QUARANTINE dis=NONE)
```

### Mail-Tester Score:
- **Before:** 5-7/10
- **After DKIM:** 8-9/10
- **After DMARC tuning:** 9-10/10

---

## Additional Best Practices

### 1. Set Up Reverse DNS (PTR Record)
Ask your hosting provider to set up PTR record for your server IP.

### 2. Monitor Blacklists
Check if your domain/IP is blacklisted:
- https://mxtoolbox.com/blacklists.aspx
- https://multirbl.valli.org/

### 3. Warm Up Your Email
When first sending emails:
- Start with small volumes (10-20/day)
- Gradually increase over 2-3 weeks
- Avoid mass emails initially

### 4. Use Professional Email Content
- Include physical address
- Add unsubscribe link (for marketing emails)
- Use proper HTML formatting
- Avoid ALL CAPS, excessive punctuation
- Don't use URL shorteners

---

## Quick Reference Commands

```bash
# Check SPF
nslookup -type=txt example.com

# Check DKIM (try different selectors if default doesn't work)
nslookup -type=txt default._domainkey.example.com
nslookup -type=txt mail._domainkey.example.com

# Check DMARC
nslookup -type=txt _dmarc.example.com

# Check MX records
nslookup -type=mx example.com

# Send test email
python admin/test-smtp.py test@mail-tester.com
```

---

## Support Resources

- **DKIM Guide:** https://www.dkim.org/
- **DMARC Guide:** https://dmarc.org/
- **Mail Tester:** https://www.mail-tester.com/
- **MX Toolbox:** https://mxtoolbox.com/
- **Google Postmaster:** https://postmaster.google.com/

---

## Current Status (Before Setup)

✅ **SPF:** Configured
❌ **DKIM:** Not enabled
⚠️ **DMARC:** Basic (needs improvement)

## Target Status (After Setup)

✅ **SPF:** Configured and passing
✅ **DKIM:** Enabled and passing
✅ **DMARC:** Enforced with reporting

---

**Last Updated:** 2025-10-12
**Domain:** example.com
**Mailbox:** no-reploy@example.com
