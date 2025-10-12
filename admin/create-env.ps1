param($path)
Set-Content -Path "$path\.env" -Value @"
SECRET_KEY=your-secret-key
SMTP_HOST=smtp.example.com
SMTP_USER=mailer@example.com
SMTP_PASS=your-smtp-password
SMTP_FROM=noreply@example.com
"@