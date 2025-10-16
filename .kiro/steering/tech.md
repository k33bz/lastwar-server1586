# Technology Stack

## Frontend
- **HTML5/CSS3/JavaScript**: Pure vanilla implementation, no frameworks
- **Chart.js**: Power trends visualization with date-fns adapter
- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox
- **No Build Process**: Direct file serving, works with file:// protocol

## Backend (Admin Panel)
- **PHP 7.4+**: Server-side logic and API endpoints
- **Composer**: Dependency management
- **JWT Authentication**: Firebase PHP-JWT library for token handling
- **Email**: PHPMailer for magic link delivery
- **Environment**: vlucas/phpdotenv for configuration

## Data Storage
- **JSON Files**: File-based data storage for alliances, rules, users
- **CSV Files**: Power history and rotation data
- **File Locking**: Concurrent access protection with flock()

## Deployment & CI/CD
- **GitHub Actions**: Automated testing and FTP deployment
- **FTP Deployment**: Direct file upload to hosting provider
- **Python Scripts**: Automation and data processing tools

## Common Commands

### Local Development
```bash
# Start local web server (frontend)
python -m http.server 8000

# PHP development server (admin panel)
php -S localhost:8080 -t admin/

# Install PHP dependencies
cd admin && composer install
```

### Deployment
```bash
# Manual FTP deployment
python scripts/deploy-ftp.py

# Update rotation schedule
python scripts/update-rotation-schedule.py

# Run validation tests
python scripts/run-tests.py
```

### Data Management
```bash
# Validate JSON files
python -m json.tool data/alliances.json

# Process screenshots (OCR)
python ocr/process-screenshots-v3.py

# Generate training data
python ocr/generate-training-data.py
```

## Security Requirements
- HTTPS required in production
- JWT tokens with 8-hour expiry
- Magic links with 10-minute expiry
- File permissions: 600 for sensitive data
- Environment variables for secrets (never commit .env)