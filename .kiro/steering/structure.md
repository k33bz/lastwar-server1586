# Project Structure

## Root Directory Layout
```
Server1586/
├── index.html              # Main public website entry point
├── index.php               # PHP redirect handler
├── login.php               # Public login page
├── logout.php              # Public logout handler
├── css/                    # Frontend stylesheets
├── js/                     # Frontend JavaScript
├── data/                   # JSON/CSV data files
├── admin/                  # Admin panel (PHP application)
├── scripts/                # Python automation scripts
├── ocr/                    # OCR training and processing
├── images/                 # Static assets and logos
├── .github/workflows/      # CI/CD pipeline definitions
└── .kiro/steering/         # AI assistant guidance files
```

## Key Directories

### `/data/` - Data Storage
- `alliances.json` - Alliance rankings (power-based, no rank field)
- `rules.json` - Server rules and regulations
- `amendments.json` - Rule change history
- `rotation-schedule.json` - Pre-generated council rotation
- `power-history.csv` - Alliance power trends over time
- `signature-history.json` - R5 leadership tracking
- `server-info.json` - Discord server metadata

### `/admin/` - PHP Admin Panel
- `config.php` - Environment and dependency loading
- `jwt.php` - JWT token management
- `mailer.php` - Email functionality
- `dashboard.php` - Main admin interface
- `*_api.php` - API endpoints for data management
- `users.json` - User permissions and roles
- `includes/` - Shared PHP components
- `vendor/` - Composer dependencies

### `/scripts/` - Automation
- `deploy-ftp.py` - FTP deployment script
- `update-rotation-schedule.py` - Council rotation generator
- `run-tests.py` - Validation and testing
- `process-screenshots.py` - OCR automation

### `/ocr/` - Machine Learning
- Training data and models for screenshot processing
- Custom OCR implementations for alliance data extraction

## File Naming Conventions

### PHP Files
- `snake_case.php` for utility files
- `kebab-case.php` for user-facing pages
- `*_api.php` suffix for API endpoints

### JavaScript/CSS
- `app.js` - Main application logic
- `styles.css` - Main stylesheet
- Version comments in file headers

### Data Files
- `.json` for structured data
- `.csv` for time-series data
- `.example` suffix for template files

## Architecture Patterns

### Frontend (Static)
- Single-page application pattern
- Progressive enhancement
- Mobile-first responsive design
- Vanilla JavaScript with modular functions

### Backend (Admin Panel)
- MVC-inspired structure without framework
- JWT-based stateless authentication
- File-based data storage with locking
- RESTful API endpoints

### Data Flow
1. Frontend loads static JSON data
2. Admin panel modifies data via PHP APIs
3. Changes trigger rotation schedule updates
4. CI/CD deploys changes automatically

## Security Considerations
- Admin panel isolated in `/admin/` directory
- Sensitive files excluded via `.ftpignore`
- Environment variables in `.env` (not committed)
- File permissions: 600 for data files, 644 for web files