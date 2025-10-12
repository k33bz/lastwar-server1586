#!/usr/bin/env python3
"""
SMTP Test Script - Python Version

Quick test to verify email configuration is working
Usage: python test-smtp.py recipient@example.com

@version 1.1.0
@date 2025-10-12
@changelog
  1.1.0 (2025-10-12) - Convert to HTML emails with professional styling
                     - Add no-reply notice to all emails
                     - Switch from no-reploy@ to noreply@ (correct spelling, no hyphen)
  1.0.0 (2025-10-12) - Initial implementation with SSL connection
"""

import smtplib
import sys
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# SMTP Configuration
SMTP_HOST = "example.com"
SMTP_PORT = 465
SMTP_USER = "noreply@example.com"
SMTP_PASS = "O5UYYkukI[IR"
SMTP_FROM = "noreply@example.com"
SMTP_FROM_NAME = "Last War 1586"

def send_test_email(recipient):
    """Send a test email to verify SMTP configuration"""

    print(f"Testing SMTP configuration...")
    print(f"SMTP Host: {SMTP_HOST}")
    print(f"SMTP Port: {SMTP_PORT}")
    print(f"SMTP User: {SMTP_USER}")
    print(f"Sending to: {recipient}")
    print()

    # Create message
    message = MIMEMultipart('alternative')
    message['From'] = f"{SMTP_FROM_NAME} <{SMTP_FROM}>"
    message['To'] = recipient
    message['Subject'] = "Last War 1586 Admin - SMTP Test Email"

    # HTML version with no-reply notice
    html_body = """<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #27ae60;
        }
        .header h1 {
            color: #27ae60;
            margin: 0;
            font-size: 24px;
        }
        .success-badge {
            display: inline-block;
            background-color: #27ae60;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        .content {
            margin: 30px 0;
        }
        .config-box {
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .config-box h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .config-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .config-box li {
            margin: 8px 0;
            color: #555;
        }
        .config-box code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
        .no-reply-notice {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #2c3e50;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SMTP Test Successful</h1>
            <div class="success-badge">Configuration Working</div>
        </div>

        <div class="content">
            <p><strong>Congratulations!</strong> If you received this email, your SMTP configuration is working correctly.</p>
        </div>

        <div class="config-box">
            <h3>Configuration Tested</h3>
            <ul>
                <li><strong>SMTP Server:</strong> <code>example.com</code></li>
                <li><strong>Port:</strong> <code>465</code> (SSL)</li>
                <li><strong>Authentication:</strong> Successful</li>
                <li><strong>From Address:</strong> <code>noreply@example.com</code></li>
            </ul>
        </div>

        <div class="content">
            <h3>Next Steps:</h3>
            <p>You can now proceed with deploying the JWT admin system. The magic link authentication will work correctly.</p>
        </div>

        <div class="no-reply-notice">
            <strong>Note:</strong> This is an automated message. Please do not reply to this email as this mailbox is not monitored.
        </div>

        <div class="footer">
            <p>Best regards,<br><strong>The Last War 1586 Admin Team</strong></p>
            <p style="font-size: 12px; color: #999;">Test email sent to """ + recipient + """</p>
        </div>
    </div>
</body>
</html>"""

    # Plain text fallback
    text_body = """This is a test email from the Last War 1586 admin system.

If you received this, your SMTP configuration is working correctly!

Configuration tested:
- SMTP Server: example.com
- Port: 465 (SSL)
- Authentication: Successful
- From Address: noreply@example.com

You can now proceed with deploying the JWT admin system.

NOTE: This is an automated message. Please do not reply to this email as this mailbox is not monitored.

---
Last War 1586 Admin Team
"""

    message.attach(MIMEText(text_body, 'plain'))
    message.attach(MIMEText(html_body, 'html'))

    try:
        # Connect to SMTP server with SSL
        print("Connecting to SMTP server with SSL...")
        server = smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT)
        server.set_debuglevel(0)  # Set to 1 for verbose output

        # Login
        print("Authenticating...")
        server.login(SMTP_USER, SMTP_PASS)

        # Send email
        print("Sending email...")
        server.send_message(message)

        # Close connection
        server.quit()

        print()
        print("[SUCCESS] Test email sent successfully!")
        print(f"Check your inbox at: {recipient}")
        return True

    except smtplib.SMTPAuthenticationError as e:
        print()
        print("[FAILED] AUTHENTICATION FAILED")
        print("Error: Invalid username or password")
        print(f"Details: {e}")
        print()
        print("Please check:")
        print("- Email address is correct: no-reply@example.com")
        print("- Password is correct")
        print("- Mailbox exists in cPanel")
        return False

    except smtplib.SMTPException as e:
        print()
        print("[FAILED] SMTP ERROR")
        print(f"Error: {e}")
        return False

    except Exception as e:
        print()
        print("[FAILED] UNEXPECTED ERROR")
        print(f"Error: {e}")
        return False

def main():
    if len(sys.argv) < 2:
        print("Usage: python test-smtp.py <recipient-email>")
        print("Example: python test-smtp.py admin@example.com")
        sys.exit(1)

    recipient = sys.argv[1]

    # Basic email validation
    if '@' not in recipient or '.' not in recipient:
        print(f"Error: Invalid email address: {recipient}")
        sys.exit(1)

    success = send_test_email(recipient)
    sys.exit(0 if success else 1)

if __name__ == "__main__":
    main()
