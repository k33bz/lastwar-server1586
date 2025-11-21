# Server 1586 Development Tools

This directory contains development utilities for the Server 1586 project.

## 🚀 Development Server GUI

The main development server GUI provides a unified interface for managing all Server 1586 development servers.

### Features

- **Multi-server Management**: Admin (PHP), Client (React), Preview (Vite), API (Node.js)
- **Real-time Monitoring**: Color-coded logs with timestamps
- **One-click Actions**: Start/stop servers, open browsers, build production
- **Health Checks**: HTTP endpoint validation
- **Configuration Management**: Persistent settings with GUI editor
- **Performance Monitoring**: Uptime, active servers, log statistics

### Quick Start

```bash
# Navigate to dev_tools directory
cd dev_tools

# Install optional dependencies (recommended)
pip install -r requirements.txt

# Launch the development server GUI
python dev_server.py
```

### Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+Q` | Quit application |
| `Ctrl+L` | Clear logs |
| `F5` | Refresh server status |
| `Ctrl+S` | Save configuration |
| `Ctrl+R` | Restart all servers |

### Server Management

#### Admin Panel Server
- **Technology**: PHP built-in server
- **Default Port**: 8000
- **URL**: http://localhost:8000/admin/login.php
- **Purpose**: Server 1586 admin interface

#### Client Development Server
- **Technology**: Vite development server
- **Default Port**: 5173
- **URL**: http://localhost:5173
- **Purpose**: React client with hot reload

#### Preview Server
- **Technology**: Vite preview server
- **Default Port**: 4173
- **URL**: http://localhost:4173
- **Purpose**: Production build preview

#### API Server (Optional)
- **Technology**: Node.js server
- **Default Port**: 3000
- **URL**: http://localhost:3000
- **Purpose**: Backend API services

### Configuration

Settings are stored in `dev_server_config.json` and can be edited via the GUI settings dialog or manually:

```json
{
  "admin_port": 8000,
  "client_port": 5173,
  "preview_port": 4173,
  "api_port": 3000,
  "auto_open_browser": true,
  "max_log_lines": 1000,
  "php_executable": "php",
  "npm_executable": "npm",
  "node_executable": "node"
}
```

### Development Workflow

1. **Launch GUI**: `python dev_server.py`
2. **Validate Project**: Automatic structure detection
3. **Start Servers**: Use "🚀 Start All Servers" or individual controls
4. **Monitor Logs**: Real-time output from all servers
5. **Build & Test**: Production build tools
6. **Health Checks**: Validate server endpoints

### Troubleshooting

#### Common Issues

**"Project structure issues detected"**
- Ensure you're running from the Server 1586 project root
- Verify admin/ and client/ directories exist
- Check that required executables (PHP, npm, Node.js) are in PATH

**"Failed to start server"**
- Check if ports are already in use
- Verify executable paths in configuration
- Review logs for specific error messages

**"Health check failed"**
- Install requests library: `pip install requests`
- Ensure servers are fully started before running checks
- Check firewall/antivirus blocking localhost connections

#### Log Export

Use the "💾 Export Logs" button to save logs for debugging or sharing with team members.

### Requirements

- **Python 3.7+** with tkinter
- **PHP 7.4+** (for admin server)
- **Node.js 16+** with npm (for client/API servers)
- **Optional**: psutil, requests (for enhanced monitoring)

### Project Structure Integration

The development server is designed specifically for the Server 1586 project structure:

```
Server1586/
├── admin/           # PHP admin panel
├── client/          # React client application
├── api/             # Node.js API server (optional)
├── data/            # JSON/CSV data files
├── scripts/         # Python automation scripts
├── dev_tools/       # Development utilities (this directory)
│   ├── dev_server.py
│   ├── dev_server_config.json
│   └── README.md
└── ...
```

This ensures proper path resolution and project-aware functionality.