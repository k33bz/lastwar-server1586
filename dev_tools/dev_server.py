#!/usr/bin/env python3
"""
Server 1586 Development Server GUI
Enterprise development environment manager for Server 1586 project

FEATURES:
- Multi-server management (Admin PHP, Client React, Preview Vite, API Node.js)
- Real-time log monitoring with color-coded output
- One-click browser launching and server management
- Production build tools with dist management
- Process monitoring and graceful shutdown
- Cross-platform compatibility (Windows/Linux/macOS)
- Configuration management and keyboard shortcuts
- Performance monitoring and status tracking

SERVERS MANAGED:
- Admin Panel: PHP built-in server (localhost:8000)
- Client Dev: React/Vite development server (localhost:5173)  
- Preview: Vite production preview server (localhost:4173)
- API Server: Node.js API server (localhost:3000)

REQUIREMENTS:
- Python 3.7+ with tkinter
- PHP 7.4+ (for admin server)
- Node.js 16+ with npm (for client/API servers)
- Project structure: admin/, client/, api/ directories

USAGE:
    cd dev_tools
    python dev_server.py

KEYBOARD SHORTCUTS:
    Ctrl+Q: Quit application
    Ctrl+L: Clear logs
    F5: Refresh server status
    Ctrl+S: Save configuration
    Ctrl+R: Restart all servers
"""

import tkinter as tk
from tkinter import ttk, scrolledtext, messagebox, filedialog
import subprocess
import threading
import os
import sys
import webbrowser
import json
import signal
import shutil
import time
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple

# Try to import optional dependencies
try:
    import psutil
    HAS_PSUTIL = True
except ImportError:
    HAS_PSUTIL = False
    
try:
    import requests
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False

class DevServerGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("Server 1586 - Development Server")
        self.root.geometry("1200x1000")
        
        # Ensure we're working from project root
        self.project_root = Path(__file__).parent.parent
        os.chdir(self.project_root)
        
        # Configuration
        self.config = self.load_config()
        
        # Server processes
        self.admin_process = None
        self.client_process = None
        self.preview_process = None
        self.api_process = None
        
        # Server status
        self.admin_running = False
        self.client_running = False
        self.preview_running = False
        self.api_running = False
        
        # Health monitoring
        self.health_status = {
            "admin": "unknown",
            "client": "unknown", 
            "preview": "unknown",
            "api": "unknown"
        }
        
        # Performance monitoring
        self.start_time = datetime.now()
        self.request_count = 0
        
        # Setup keyboard shortcuts
        self.setup_shortcuts()
        self.setup_ui()
        
        # Auto-detect project structure
        self.detect_project_structure()
        
        # Start monitoring thread
        self.start_monitoring()

    def load_config(self):
        """Load configuration from file or create default"""
        config_file = Path("dev_tools/dev_server_config.json")
        default_config = {
            "admin_port": 8000,
            "client_port": 5173,
            "preview_port": 4173,
            "api_port": 3000,
            "auto_open_browser": True,
            "log_level": "INFO",
            "theme": "dark",
            "auto_start_servers": [],
            "php_executable": "php",
            "node_executable": "node",
            "npm_executable": "npm",
            "max_log_lines": 1000,
            "enable_health_checks": True,
            "health_check_interval": 30,
            "auto_restart_on_crash": False,
            "log_to_file": False,
            "log_file_path": "dev_tools/dev_server.log"
        }
        
        if config_file.exists():
            try:
                with open(config_file, 'r') as f:
                    user_config = json.load(f)
                    default_config.update(user_config)
            except Exception as e:
                self.show_error(f"Failed to load config: {e}")
        
        return default_config

    def save_config(self):
        """Save current configuration"""
        try:
            config_file = Path("dev_tools/dev_server_config.json")
            with open(config_file, 'w') as f:
                json.dump(self.config, f, indent=2)
            self.log("✓ Configuration saved", "SUCCESS")
        except Exception as e:
            self.show_error(f"Failed to save config: {e}")

    def setup_shortcuts(self):
        """Setup keyboard shortcuts"""
        self.root.bind('<Control-q>', lambda e: self.on_closing())
        self.root.bind('<Control-l>', lambda e: self.clear_logs())
        self.root.bind('<F5>', lambda e: self.refresh_status())
        self.root.bind('<Control-s>', lambda e: self.save_config())
        self.root.bind('<Control-r>', lambda e: self.restart_all_servers())

    def detect_project_structure(self):
        """Auto-detect Server 1586 project structure and validate"""
        issues = []
        
        # Core directories
        required_dirs = {
            "admin": "PHP admin panel",
            "client": "React client application"
        }
        
        optional_dirs = {
            "api": "Node.js API server",
            "data": "JSON/CSV data files",
            "scripts": "Python automation scripts",
            "ocr": "OCR training and processing",
            "images": "Static assets and logos",
            "dev_tools": "Development utilities"
        }
        
        for dir_name, description in required_dirs.items():
            if not Path(dir_name).exists():
                issues.append(f"❌ {dir_name}/ directory not found ({description})")
            else:
                self.log(f"✓ {dir_name}/ directory detected ({description})", "SUCCESS")
        
        for dir_name, description in optional_dirs.items():
            if Path(dir_name).exists():
                self.log(f"✓ {dir_name}/ directory detected ({description})", "SUCCESS")
            else:
                self.log(f"ℹ️ {dir_name}/ directory not found ({description}) - optional", "INFO")
        
        # Check specific files
        required_files = {
            "client/package.json": "Client dependencies",
            "admin/config.php": "Admin configuration"
        }
        
        for file_path, description in required_files.items():
            if Path(file_path).exists():
                self.log(f"✓ {file_path} found ({description})", "SUCCESS")
            else:
                issues.append(f"⚠️ {file_path} not found ({description})")
        
        # Check executables with version info
        executables = {
            self.config["php_executable"]: "PHP",
            self.config["npm_executable"]: "npm",
            self.config["node_executable"]: "Node.js"
        }
        
        for executable, name in executables.items():
            if self.check_executable_with_version(executable):
                version = self.get_executable_version(executable)
                self.log(f"✓ {name} executable found ({version})", "SUCCESS")
            else:
                issues.append(f"❌ {name} not found in PATH")
        
        # Project health summary
        if not issues:
            self.log("🎉 Project structure validation passed!", "SUCCESS")
        else:
            self.log("⚠️ Project structure issues detected:", "WARN")
            for issue in issues:
                self.log(f"   {issue}", "WARN")
        
        return len(issues) == 0

    def check_executable_with_version(self, executable):
        """Check if executable exists and get version"""
        try:
            result = subprocess.run([executable, "--version"], 
                                  capture_output=True, check=True, text=True)
            return True
        except (subprocess.CalledProcessError, FileNotFoundError):
            return False
    
    def get_executable_version(self, executable):
        """Get version string for executable"""
        try:
            result = subprocess.run([executable, "--version"], 
                                  capture_output=True, check=True, text=True)
            # Extract first line and clean it up
            version_line = result.stdout.split('\n')[0].strip()
            return version_line[:50] + "..." if len(version_line) > 50 else version_line
        except:
            return "unknown version"

    def check_executable(self, executable):
        """Check if executable exists in PATH"""
        try:
            subprocess.run([executable, "--version"], 
                         capture_output=True, check=True)
            return True
        except (subprocess.CalledProcessError, FileNotFoundError):
            return False

    def start_monitoring(self):
        """Start system monitoring thread"""
        def monitor():
            while True:
                try:
                    # Update performance metrics every 5 seconds
                    threading.Event().wait(5)
                    if hasattr(self, 'performance_frame'):
                        self.update_performance_metrics()
                except Exception:
                    break
        
        threading.Thread(target=monitor, daemon=True).start()

    def show_error(self, message):
        """Show error dialog"""
        messagebox.showerror("Error", message)
        self.log(f"ERROR: {message}", "ERROR")

    def setup_ui(self):
        """Setup the GUI interface"""

        # Title
        title_frame = ttk.Frame(self.root, padding="10")
        title_frame.pack(fill=tk.X)

        title_label = ttk.Label(
            title_frame,
            text="🚀 Server 1586 Development Server",
            font=("Arial", 16, "bold")
        )
        title_label.pack()

        subtitle_label = ttk.Label(
            title_frame,
            text="Multi-server development environment manager",
            font=("Arial", 10)
        )
        subtitle_label.pack()

        # Separator
        ttk.Separator(self.root, orient=tk.HORIZONTAL).pack(fill=tk.X, pady=10)

        # Admin Server Section
        admin_frame = ttk.LabelFrame(self.root, text="Admin Panel Server", padding="10")
        admin_frame.pack(fill=tk.X, padx=10, pady=5)

        admin_info_frame = ttk.Frame(admin_frame)
        admin_info_frame.pack(fill=tk.X)

        ttk.Label(admin_info_frame, text="URL:", font=("Arial", 10, "bold")).pack(side=tk.LEFT, padx=5)
        self.admin_url_label = ttk.Label(
            admin_info_frame,
            text=f"http://localhost:{self.config['admin_port']}/admin/login.php",
            foreground="blue",
            cursor="hand2"
        )
        self.admin_url_label.pack(side=tk.LEFT, padx=5)
        self.admin_url_label.bind("<Button-1>", lambda e: webbrowser.open(f"http://localhost:{self.config['admin_port']}/admin/login.php"))

        self.admin_status_label = ttk.Label(admin_info_frame, text="● Stopped", foreground="red")
        self.admin_status_label.pack(side=tk.RIGHT, padx=5)

        admin_btn_frame = ttk.Frame(admin_frame)
        admin_btn_frame.pack(fill=tk.X, pady=5)

        self.admin_start_btn = ttk.Button(
            admin_btn_frame,
            text="▶ Start Admin Server",
            command=self.start_admin_server
        )
        self.admin_start_btn.pack(side=tk.LEFT, padx=5)

        self.admin_stop_btn = ttk.Button(
            admin_btn_frame,
            text="⏹ Stop Admin Server",
            command=self.stop_admin_server,
            state=tk.DISABLED
        )
        self.admin_stop_btn.pack(side=tk.LEFT, padx=5)

        ttk.Button(
            admin_btn_frame,
            text="🌐 Open in Browser",
            command=lambda: webbrowser.open(f"http://localhost:{self.config['admin_port']}/admin/login.php")
        ).pack(side=tk.LEFT, padx=5)

        # Client/Public Site Section
        client_frame = ttk.LabelFrame(self.root, text="Public Site Server (React)", padding="10")
        client_frame.pack(fill=tk.X, padx=10, pady=5)

        client_info_frame = ttk.Frame(client_frame)
        client_info_frame.pack(fill=tk.X)

        ttk.Label(client_info_frame, text="URL:", font=("Arial", 10, "bold")).pack(side=tk.LEFT, padx=5)
        self.client_url_label = ttk.Label(
            client_info_frame,
            text=f"http://localhost:{self.config['client_port']}",
            foreground="blue",
            cursor="hand2"
        )
        self.client_url_label.pack(side=tk.LEFT, padx=5)
        self.client_url_label.bind("<Button-1>", lambda e: webbrowser.open(f"http://localhost:{self.config['client_port']}"))

        self.client_status_label = ttk.Label(client_info_frame, text="● Stopped", foreground="red")
        self.client_status_label.pack(side=tk.RIGHT, padx=5)

        client_btn_frame = ttk.Frame(client_frame)
        client_btn_frame.pack(fill=tk.X, pady=5)

        self.client_start_btn = ttk.Button(
            client_btn_frame,
            text="▶ Start Client Dev Server",
            command=self.start_client_server
        )
        self.client_start_btn.pack(side=tk.LEFT, padx=5)

        self.client_stop_btn = ttk.Button(
            client_btn_frame,
            text="⏹ Stop Client Server",
            command=self.stop_client_server,
            state=tk.DISABLED
        )
        self.client_stop_btn.pack(side=tk.LEFT, padx=5)

        ttk.Button(
            client_btn_frame,
            text="🌐 Open in Browser",
            command=lambda: webbrowser.open(f"http://localhost:{self.config['client_port']}")
        ).pack(side=tk.LEFT, padx=5)

        # Vite Build Tools Section
        vite_frame = ttk.LabelFrame(self.root, text="Vite Build Tools", padding="10")
        vite_frame.pack(fill=tk.X, padx=10, pady=5)

        vite_info_frame = ttk.Frame(vite_frame)
        vite_info_frame.pack(fill=tk.X, pady=5)

        ttk.Label(
            vite_info_frame,
            text="Build and preview production-ready client",
            font=("Arial", 9, "italic")
        ).pack(side=tk.LEFT, padx=5)

        vite_btn_frame = ttk.Frame(vite_frame)
        vite_btn_frame.pack(fill=tk.X, pady=5)

        ttk.Button(
            vite_btn_frame,
            text="🔨 Build Production",
            command=self.build_production
        ).pack(side=tk.LEFT, padx=5)

        self.preview_start_btn = ttk.Button(
            vite_btn_frame,
            text="▶ Preview Build",
            command=self.start_preview_server
        )
        self.preview_start_btn.pack(side=tk.LEFT, padx=5)

        self.preview_stop_btn = ttk.Button(
            vite_btn_frame,
            text="⏹ Stop Preview",
            command=self.stop_preview_server,
            state=tk.DISABLED
        )
        self.preview_stop_btn.pack(side=tk.LEFT, padx=5)

        ttk.Button(
            vite_btn_frame,
            text="🗑️ Clean Dist",
            command=self.clean_dist
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            vite_btn_frame,
            text="🌐 Open Preview",
            command=lambda: webbrowser.open(f"http://localhost:{self.config['preview_port']}")
        ).pack(side=tk.LEFT, padx=5)

        # Preview server status
        preview_status_frame = ttk.Frame(vite_frame)
        preview_status_frame.pack(fill=tk.X, pady=2)

        ttk.Label(preview_status_frame, text="Preview Server:", font=("Arial", 9)).pack(side=tk.LEFT, padx=5)
        self.preview_url_label = ttk.Label(
            preview_status_frame,
            text=f"http://localhost:{self.config['preview_port']}",
            foreground="blue",
            cursor="hand2"
        )
        self.preview_url_label.pack(side=tk.LEFT, padx=5)
        self.preview_url_label.bind("<Button-1>", lambda e: webbrowser.open(f"http://localhost:{self.config['preview_port']}"))

        self.preview_status_label = ttk.Label(preview_status_frame, text="● Stopped", foreground="red")
        self.preview_status_label.pack(side=tk.RIGHT, padx=5)

        # API Server Section (if api directory exists)
        if Path("api").exists():
            api_frame = ttk.LabelFrame(self.root, text="API Server (Node.js)", padding="10")
            api_frame.pack(fill=tk.X, padx=10, pady=5)

            api_info_frame = ttk.Frame(api_frame)
            api_info_frame.pack(fill=tk.X)

            ttk.Label(api_info_frame, text="URL:", font=("Arial", 10, "bold")).pack(side=tk.LEFT, padx=5)
            self.api_url_label = ttk.Label(
                api_info_frame,
                text=f"http://localhost:{self.config['api_port']}",
                foreground="blue",
                cursor="hand2"
            )
            self.api_url_label.pack(side=tk.LEFT, padx=5)
            self.api_url_label.bind("<Button-1>", lambda e: webbrowser.open(f"http://localhost:{self.config['api_port']}"))

            self.api_status_label = ttk.Label(api_info_frame, text="● Stopped", foreground="red")
            self.api_status_label.pack(side=tk.RIGHT, padx=5)

            api_btn_frame = ttk.Frame(api_frame)
            api_btn_frame.pack(fill=tk.X, pady=5)

            self.api_start_btn = ttk.Button(
                api_btn_frame,
                text="▶ Start API Server",
                command=self.start_api_server
            )
            self.api_start_btn.pack(side=tk.LEFT, padx=5)

            self.api_stop_btn = ttk.Button(
                api_btn_frame,
                text="⏹ Stop API Server",
                command=self.stop_api_server,
                state=tk.DISABLED
            )
            self.api_stop_btn.pack(side=tk.LEFT, padx=5)

            ttk.Button(
                api_btn_frame,
                text="🌐 Open in Browser",
                command=lambda: webbrowser.open(f"http://localhost:{self.config['api_port']}")
            ).pack(side=tk.LEFT, padx=5)

        # Performance Monitoring Section
        self.performance_frame = ttk.LabelFrame(self.root, text="Performance Monitor", padding="10")
        self.performance_frame.pack(fill=tk.X, padx=10, pady=5)

        perf_grid = ttk.Frame(self.performance_frame)
        perf_grid.pack(fill=tk.X)

        # Uptime
        ttk.Label(perf_grid, text="Uptime:", font=("Arial", 9, "bold")).grid(row=0, column=0, sticky=tk.W, padx=5)
        self.uptime_label = ttk.Label(perf_grid, text="00:00:00")
        self.uptime_label.grid(row=0, column=1, sticky=tk.W, padx=5)

        # Active servers
        ttk.Label(perf_grid, text="Active Servers:", font=("Arial", 9, "bold")).grid(row=0, column=2, sticky=tk.W, padx=5)
        self.active_servers_label = ttk.Label(perf_grid, text="0/4")
        self.active_servers_label.grid(row=0, column=3, sticky=tk.W, padx=5)

        # Log count
        ttk.Label(perf_grid, text="Log Lines:", font=("Arial", 9, "bold")).grid(row=0, column=4, sticky=tk.W, padx=5)
        self.log_count_label = ttk.Label(perf_grid, text="0")
        self.log_count_label.grid(row=0, column=5, sticky=tk.W, padx=5)

        # Quick Actions
        actions_frame = ttk.LabelFrame(self.root, text="Quick Actions", padding="10")
        actions_frame.pack(fill=tk.X, padx=10, pady=5)

        ttk.Button(
            actions_frame,
            text="🚀 Start All Servers",
            command=self.start_all
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="⏹ Stop All Servers",
            command=self.stop_all
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="🗑️ Clear Logs",
            command=self.clear_logs
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="🔄 Restart All",
            command=self.restart_all_servers
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="⚙️ Settings",
            command=self.show_settings
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="📊 Status",
            command=self.show_status_window
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="💾 Export Logs",
            command=self.export_logs
        ).pack(side=tk.LEFT, padx=5)

        ttk.Button(
            actions_frame,
            text="🔍 Health Check",
            command=self.run_health_checks
        ).pack(side=tk.LEFT, padx=5)

        # Logs Section
        logs_frame = ttk.LabelFrame(self.root, text="Server Logs", padding="5")
        logs_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        self.log_text = scrolledtext.ScrolledText(
            logs_frame,
            height=35,
            font=("Courier", 10),
            bg="#1e1e1e",
            fg="#d4d4d4",
            insertbackground="white"
        )
        self.log_text.pack(fill=tk.BOTH, expand=True)

        # Status bar
        self.status_bar = ttk.Label(self.root, text="Ready", relief=tk.SUNKEN, anchor=tk.W)
        self.status_bar.pack(fill=tk.X, side=tk.BOTTOM)

        self.log("✓ Development Server GUI ready")
        self.log(f"📁 Project directory: {self.project_root}")

    def log(self, message, level="INFO"):
        """Add message to log window with color coding"""
        timestamp = datetime.now().strftime("%H:%M:%S")
        formatted_msg = f"[{timestamp}] [{level}] {message}\n"

        # Color coding based on level
        color_map = {
            "ERROR": "#ff6b6b",
            "WARN": "#ffd93d", 
            "SUCCESS": "#6bcf7f",
            "SERVER": "#74c0fc",
            "INFO": "#d4d4d4"
        }
        
        # Configure text tags for colors
        for tag, color in color_map.items():
            self.log_text.tag_configure(tag, foreground=color)

        # Insert with appropriate tag
        start_pos = self.log_text.index(tk.END)
        self.log_text.insert(tk.END, formatted_msg)
        end_pos = self.log_text.index(tk.END)
        
        if level in color_map:
            self.log_text.tag_add(level, start_pos, end_pos)
        
        self.log_text.see(tk.END)
        self.log_text.update()
        
        # Limit log lines to prevent memory issues
        lines = int(self.log_text.index('end-1c').split('.')[0])
        if lines > self.config["max_log_lines"]:
            self.log_text.delete(1.0, f"{lines - self.config['max_log_lines']}.0")
        
        # Update log count
        if hasattr(self, 'log_count_label'):
            self.log_count_label.config(text=str(lines))

    def clear_logs(self):
        """Clear the log window"""
        self.log_text.delete(1.0, tk.END)
        self.log("Logs cleared")

    def start_admin_server(self):
        """Start PHP admin server"""
        if self.admin_running:
            self.log("Admin server already running", "WARN")
            return

        try:
            self.log(f"Starting admin server on port {self.config['admin_port']}...", "INFO")

            # Start PHP built-in server from project root
            self.admin_process = subprocess.Popen(
                [self.config["php_executable"], "-S", f"localhost:{self.config['admin_port']}"],
                cwd=self.project_root,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1
            )

            # Monitor output in separate thread
            threading.Thread(
                target=self.monitor_admin_output,
                daemon=True
            ).start()

            self.admin_running = True
            self.admin_status_label.config(text="● Running", foreground="green")
            self.admin_start_btn.config(state=tk.DISABLED)
            self.admin_stop_btn.config(state=tk.NORMAL)
            self.status_bar.config(text=f"Admin server started on http://localhost:{self.config['admin_port']}")

            self.log("✓ Admin server started successfully", "SUCCESS")
            self.log(f"→ Access at: http://localhost:{self.config['admin_port']}/admin/login.php", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to start admin server: {e}", "ERROR")

    def stop_admin_server(self):
        """Stop PHP admin server"""
        if not self.admin_running:
            return

        try:
            self.log("Stopping admin server...", "INFO")
            self.admin_process.terminate()
            self.admin_process.wait(timeout=5)

            self.admin_running = False
            self.admin_status_label.config(text="● Stopped", foreground="red")
            self.admin_start_btn.config(state=tk.NORMAL)
            self.admin_stop_btn.config(state=tk.DISABLED)
            self.status_bar.config(text="Admin server stopped")

            self.log("✓ Admin server stopped", "SUCCESS")

        except Exception as e:
            self.log(f"✗ Error stopping admin server: {e}", "ERROR")

    def monitor_admin_output(self):
        """Monitor admin server output"""
        for line in iter(self.admin_process.stdout.readline, ''):
            if line:
                self.log(f"[ADMIN] {line.strip()}", "SERVER")

    def start_client_server(self):
        """Start React client dev server"""
        if self.client_running:
            self.log("Client server already running", "WARN")
            return

        try:
            self.log("Starting client dev server...", "INFO")

            # Check if client directory exists
            client_dir = self.project_root / "client"
            if not client_dir.exists():
                self.log("✗ Client directory not found", "ERROR")
                return

            # Start npm dev server
            self.client_process = subprocess.Popen(
                [self.config["npm_executable"], "run", "dev"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1,
                shell=True
            )

            # Monitor output
            threading.Thread(
                target=self.monitor_client_output,
                daemon=True
            ).start()

            self.client_running = True
            self.client_status_label.config(text="● Running", foreground="green")
            self.client_start_btn.config(state=tk.DISABLED)
            self.client_stop_btn.config(state=tk.NORMAL)
            self.status_bar.config(text=f"Client server started on http://localhost:{self.config['client_port']}")

            self.log("✓ Client server started successfully", "SUCCESS")
            self.log(f"→ Access at: http://localhost:{self.config['client_port']}", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to start client server: {e}", "ERROR")

    def stop_client_server(self):
        """Stop React client server"""
        if not self.client_running:
            return

        try:
            self.log("Stopping client server...", "INFO")
            self.client_process.terminate()
            self.client_process.wait(timeout=5)

            self.client_running = False
            self.client_status_label.config(text="● Stopped", foreground="red")
            self.client_start_btn.config(state=tk.NORMAL)
            self.client_stop_btn.config(state=tk.DISABLED)
            self.status_bar.config(text="Client server stopped")

            self.log("✓ Client server stopped", "SUCCESS")

        except Exception as e:
            self.log(f"✗ Error stopping client server: {e}", "ERROR")

    def monitor_client_output(self):
        """Monitor client server output"""
        for line in iter(self.client_process.stdout.readline, ''):
            if line:
                self.log(f"[CLIENT] {line.strip()}", "SERVER")

    def start_all(self):
        """Start all available servers"""
        self.start_admin_server()
        self.start_client_server()
        if Path("api").exists():
            self.start_api_server()

    def build_production(self):
        """Build production version of client"""
        try:
            self.log("Building production version...", "INFO")

            client_dir = self.project_root / "client"
            if not client_dir.exists():
                self.log("✗ Client directory not found", "ERROR")
                return

            # Run npm build
            process = subprocess.Popen(
                [self.config["npm_executable"], "run", "build"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1,
                shell=True
            )

            # Monitor build output
            self.log("→ Running: npm run build", "INFO")
            for line in iter(process.stdout.readline, ''):
                if line:
                    self.log(f"[BUILD] {line.strip()}", "SERVER")

            process.wait()

            if process.returncode == 0:
                self.log("✓ Production build completed successfully", "SUCCESS")
                self.status_bar.config(text="Production build completed")
            else:
                self.log(f"✗ Build failed with code {process.returncode}", "ERROR")

        except Exception as e:
            self.log(f"✗ Failed to build: {e}", "ERROR")

    def start_preview_server(self):
        """Start Vite preview server"""
        if self.preview_running:
            self.log("Preview server already running", "WARN")
            return

        try:
            self.log("Starting preview server...", "INFO")

            client_dir = self.project_root / "client"
            if not client_dir.exists():
                self.log("✗ Client directory not found", "ERROR")
                return

            # Start preview server
            self.preview_process = subprocess.Popen(
                [self.config["npm_executable"], "run", "preview"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1,
                shell=True
            )

            # Monitor output
            threading.Thread(
                target=self.monitor_preview_output,
                daemon=True
            ).start()

            self.preview_running = True
            self.preview_status_label.config(text="● Running", foreground="green")
            self.preview_start_btn.config(state=tk.DISABLED)
            self.preview_stop_btn.config(state=tk.NORMAL)
            self.status_bar.config(text=f"Preview server started on http://localhost:{self.config['preview_port']}")

            self.log("✓ Preview server started successfully", "SUCCESS")
            self.log(f"→ Access at: http://localhost:{self.config['preview_port']}", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to start preview server: {e}", "ERROR")

    def stop_preview_server(self):
        """Stop Vite preview server"""
        if not self.preview_running:
            return

        try:
            self.log("Stopping preview server...", "INFO")
            self.preview_process.terminate()
            self.preview_process.wait(timeout=5)

            self.preview_running = False
            self.preview_status_label.config(text="● Stopped", foreground="red")
            self.preview_start_btn.config(state=tk.NORMAL)
            self.preview_stop_btn.config(state=tk.DISABLED)
            self.status_bar.config(text="Preview server stopped")

            self.log("✓ Preview server stopped", "SUCCESS")

        except Exception as e:
            self.log(f"✗ Error stopping preview server: {e}", "ERROR")

    def monitor_preview_output(self):
        """Monitor preview server output"""
        for line in iter(self.preview_process.stdout.readline, ''):
            if line:
                self.log(f"[PREVIEW] {line.strip()}", "SERVER")

    def clean_dist(self):
        """Clean dist directory"""
        try:
            self.log("Cleaning dist directory...", "INFO")

            client_dir = self.project_root / "client"
            dist_path = client_dir / "dist"

            if dist_path.exists():
                shutil.rmtree(dist_path)
                self.log("✓ Dist directory cleaned", "SUCCESS")
                self.status_bar.config(text="Dist directory cleaned")
            else:
                self.log("Dist directory does not exist", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to clean dist: {e}", "ERROR")

    def start_api_server(self):
        """Start Node.js API server"""
        if self.api_running:
            self.log("API server already running", "WARN")
            return

        try:
            self.log("Starting API server...", "INFO")
            
            # Check if api directory exists and has package.json
            api_dir = self.project_root / "api"
            if not api_dir.exists():
                self.log("✗ API directory not found", "ERROR")
                return
                
            if not (api_dir / "package.json").exists():
                self.log("✗ API package.json not found", "ERROR")
                return

            # Start Node.js server
            self.api_process = subprocess.Popen(
                [self.config["npm_executable"], "start"],
                cwd=api_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1,
                shell=True
            )

            # Monitor output
            threading.Thread(
                target=self.monitor_api_output,
                daemon=True
            ).start()

            self.api_running = True
            self.api_status_label.config(text="● Running", foreground="green")
            self.api_start_btn.config(state=tk.DISABLED)
            self.api_stop_btn.config(state=tk.NORMAL)
            self.status_bar.config(text=f"API server started on http://localhost:{self.config['api_port']}")

            self.log("✓ API server started successfully", "SUCCESS")
            self.log(f"→ Access at: http://localhost:{self.config['api_port']}", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to start API server: {e}", "ERROR")

    def stop_api_server(self):
        """Stop Node.js API server"""
        if not self.api_running:
            return

        try:
            self.log("Stopping API server...", "INFO")
            self.api_process.terminate()
            self.api_process.wait(timeout=5)

            self.api_running = False
            self.api_status_label.config(text="● Stopped", foreground="red")
            self.api_start_btn.config(state=tk.NORMAL)
            self.api_stop_btn.config(state=tk.DISABLED)
            self.status_bar.config(text="API server stopped")

            self.log("✓ API server stopped", "SUCCESS")

        except Exception as e:
            self.log(f"✗ Error stopping API server: {e}", "ERROR")

    def monitor_api_output(self):
        """Monitor API server output"""
        for line in iter(self.api_process.stdout.readline, ''):
            if line:
                self.log(f"[API] {line.strip()}", "SERVER")

    def export_logs(self):
        """Export logs to file"""
        try:
            filename = filedialog.asksaveasfilename(
                defaultextension=".log",
                filetypes=[("Log files", "*.log"), ("Text files", "*.txt"), ("All files", "*.*")],
                title="Export Logs"
            )
            
            if filename:
                log_content = self.log_text.get(1.0, tk.END)
                with open(filename, 'w', encoding='utf-8') as f:
                    f.write(f"Server 1586 Development Server Logs\n")
                    f.write(f"Exported: {datetime.now().isoformat()}\n")
                    f.write("=" * 50 + "\n\n")
                    f.write(log_content)
                
                self.log(f"✓ Logs exported to: {filename}", "SUCCESS")
                
        except Exception as e:
            self.log(f"✗ Failed to export logs: {e}", "ERROR")

    def run_health_checks(self):
        """Run health checks on all servers"""
        self.log("Running health checks...", "INFO")
        
        if not HAS_REQUESTS:
            self.log("⚠️ requests library not available, skipping HTTP health checks", "WARN")
            return
        
        servers_to_check = []
        if self.admin_running:
            servers_to_check.append(("Admin", f"http://localhost:{self.config['admin_port']}"))
        if self.client_running:
            servers_to_check.append(("Client", f"http://localhost:{self.config['client_port']}"))
        if self.preview_running:
            servers_to_check.append(("Preview", f"http://localhost:{self.config['preview_port']}"))
        if hasattr(self, 'api_running') and self.api_running:
            servers_to_check.append(("API", f"http://localhost:{self.config['api_port']}"))
        
        if not servers_to_check:
            self.log("No servers running to check", "INFO")
            return
        
        for server_name, url in servers_to_check:
            try:
                response = requests.get(url, timeout=5)
                if response.status_code < 400:
                    self.log(f"✓ {server_name} server health check passed ({response.status_code})", "SUCCESS")
                    self.health_status[server_name.lower()] = "healthy"
                else:
                    self.log(f"⚠️ {server_name} server returned {response.status_code}", "WARN")
                    self.health_status[server_name.lower()] = "warning"
            except requests.exceptions.RequestException as e:
                self.log(f"✗ {server_name} server health check failed: {e}", "ERROR")
                self.health_status[server_name.lower()] = "error"

    def update_performance_metrics(self):
        """Update performance monitoring display"""
        try:
            # Calculate uptime
            uptime = datetime.now() - self.start_time
            uptime_str = str(uptime).split('.')[0]  # Remove microseconds
            self.uptime_label.config(text=uptime_str)
            
            # Count active servers
            active_count = sum([
                self.admin_running,
                self.client_running,
                self.preview_running,
                getattr(self, 'api_running', False)
            ])
            total_servers = 4 if Path("api").exists() else 3
            self.active_servers_label.config(text=f"{active_count}/{total_servers}")
            
        except Exception as e:
            self.log(f"Performance monitoring error: {e}", "ERROR")

    def refresh_status(self):
        """Refresh server status"""
        self.log("Refreshing server status...", "INFO")
        
        # Check if processes are still running
        if self.admin_process and self.admin_process.poll() is not None:
            self.admin_running = False
            self.admin_status_label.config(text="● Stopped", foreground="red")
            self.admin_start_btn.config(state=tk.NORMAL)
            self.admin_stop_btn.config(state=tk.DISABLED)
            
        if self.client_process and self.client_process.poll() is not None:
            self.client_running = False
            self.client_status_label.config(text="● Stopped", foreground="red")
            self.client_start_btn.config(state=tk.NORMAL)
            self.client_stop_btn.config(state=tk.DISABLED)
            
        if self.preview_process and self.preview_process.poll() is not None:
            self.preview_running = False
            self.preview_status_label.config(text="● Stopped", foreground="red")
            self.preview_start_btn.config(state=tk.NORMAL)
            self.preview_stop_btn.config(state=tk.DISABLED)
            
        if hasattr(self, 'api_process') and self.api_process and self.api_process.poll() is not None:
            self.api_running = False
            if hasattr(self, 'api_status_label'):
                self.api_status_label.config(text="● Stopped", foreground="red")
                self.api_start_btn.config(state=tk.NORMAL)
                self.api_stop_btn.config(state=tk.DISABLED)
            
        self.log("✓ Status refreshed", "SUCCESS")

    def restart_all_servers(self):
        """Restart all running servers"""
        self.log("Restarting all servers...", "INFO")
        
        # Remember which servers were running
        was_admin_running = self.admin_running
        was_client_running = self.client_running
        was_preview_running = self.preview_running
        was_api_running = getattr(self, 'api_running', False)
        
        # Stop all servers
        self.stop_all()
        
        # Wait a moment for cleanup
        self.root.after(2000, lambda: self._restart_servers_delayed(
            was_admin_running, was_client_running, was_preview_running, was_api_running))

    def _restart_servers_delayed(self, admin, client, preview, api):
        """Delayed restart of servers"""
        if admin:
            self.start_admin_server()
        if client:
            self.start_client_server()
        if preview:
            self.start_preview_server()
        if api and Path("api").exists():
            self.start_api_server()

    def show_settings(self):
        """Show settings dialog"""
        settings_window = tk.Toplevel(self.root)
        settings_window.title("Development Server Settings")
        settings_window.geometry("500x500")
        settings_window.transient(self.root)
        settings_window.grab_set()

        # Port settings
        port_frame = ttk.LabelFrame(settings_window, text="Port Configuration", padding="10")
        port_frame.pack(fill=tk.X, padx=10, pady=5)

        ttk.Label(port_frame, text="Admin Port:").grid(row=0, column=0, sticky=tk.W, padx=5, pady=2)
        admin_port_var = tk.StringVar(value=str(self.config["admin_port"]))
        ttk.Entry(port_frame, textvariable=admin_port_var, width=10).grid(row=0, column=1, padx=5, pady=2)

        ttk.Label(port_frame, text="Client Port:").grid(row=1, column=0, sticky=tk.W, padx=5, pady=2)
        client_port_var = tk.StringVar(value=str(self.config["client_port"]))
        ttk.Entry(port_frame, textvariable=client_port_var, width=10).grid(row=1, column=1, padx=5, pady=2)

        ttk.Label(port_frame, text="Preview Port:").grid(row=2, column=0, sticky=tk.W, padx=5, pady=2)
        preview_port_var = tk.StringVar(value=str(self.config["preview_port"]))
        ttk.Entry(port_frame, textvariable=preview_port_var, width=10).grid(row=2, column=1, padx=5, pady=2)

        ttk.Label(port_frame, text="API Port:").grid(row=3, column=0, sticky=tk.W, padx=5, pady=2)
        api_port_var = tk.StringVar(value=str(self.config["api_port"]))
        ttk.Entry(port_frame, textvariable=api_port_var, width=10).grid(row=3, column=1, padx=5, pady=2)

        # Options
        options_frame = ttk.LabelFrame(settings_window, text="Options", padding="10")
        options_frame.pack(fill=tk.X, padx=10, pady=5)

        auto_browser_var = tk.BooleanVar(value=self.config["auto_open_browser"])
        ttk.Checkbutton(
            options_frame,
            text="Auto-open browser when starting servers",
            variable=auto_browser_var
        ).pack(anchor=tk.W, pady=2)

        ttk.Label(options_frame, text="Max Log Lines:").pack(anchor=tk.W, pady=2)
        max_logs_var = tk.StringVar(value=str(self.config["max_log_lines"]))
        ttk.Entry(options_frame, textvariable=max_logs_var, width=10).pack(anchor=tk.W, padx=20)

        # Buttons
        btn_frame = ttk.Frame(settings_window)
        btn_frame.pack(fill=tk.X, padx=10, pady=10)

        def save_settings():
            try:
                self.config["admin_port"] = int(admin_port_var.get())
                self.config["client_port"] = int(client_port_var.get())
                self.config["preview_port"] = int(preview_port_var.get())
                self.config["api_port"] = int(api_port_var.get())
                self.config["auto_open_browser"] = auto_browser_var.get()
                self.config["max_log_lines"] = int(max_logs_var.get())
                self.save_config()
                settings_window.destroy()
            except ValueError:
                messagebox.showerror("Error", "Invalid port number or max log lines")

        ttk.Button(btn_frame, text="Save", command=save_settings).pack(side=tk.RIGHT, padx=5)
        ttk.Button(btn_frame, text="Cancel", command=settings_window.destroy).pack(side=tk.RIGHT, padx=5)

    def show_status_window(self):
        """Show detailed status window"""
        status_window = tk.Toplevel(self.root)
        status_window.title("Server Status Details")
        status_window.geometry("600x500")
        status_window.transient(self.root)

        # Server status details
        status_frame = ttk.LabelFrame(status_window, text="Server Status", padding="10")
        status_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        status_text = scrolledtext.ScrolledText(status_frame, height=20, font=("Courier", 10))
        status_text.pack(fill=tk.BOTH, expand=True)

        # Gather status information
        status_info = []
        status_info.append("=== SERVER 1586 DEVELOPMENT STATUS ===\n")
        status_info.append(f"Uptime: {datetime.now() - self.start_time}\n")
        status_info.append(f"Project Directory: {self.project_root}\n")
        status_info.append(f"Working Directory: {os.getcwd()}\n\n")

        status_info.append("=== SERVER STATUS ===\n")
        status_info.append(f"Admin Server: {'🟢 Running' if self.admin_running else '🔴 Stopped'} (Port {self.config['admin_port']})\n")
        status_info.append(f"Client Server: {'🟢 Running' if self.client_running else '🔴 Stopped'} (Port {self.config['client_port']})\n")
        status_info.append(f"Preview Server: {'🟢 Running' if self.preview_running else '🔴 Stopped'} (Port {self.config['preview_port']})\n")
        if Path("api").exists():
            status_info.append(f"API Server: {'🟢 Running' if getattr(self, 'api_running', False) else '🔴 Stopped'} (Port {self.config['api_port']})\n")

        status_info.append("\n=== CONFIGURATION ===\n")
        for key, value in self.config.items():
            status_info.append(f"{key}: {value}\n")

        status_info.append("\n=== PROJECT STRUCTURE ===\n")
        for item in ["admin/", "client/", "client/package.json", "api/", "data/", "scripts/", "dev_tools/"]:
            exists = "✓" if Path(item).exists() else "✗"
            status_info.append(f"{exists} {item}\n")

        status_text.insert(tk.END, "".join(status_info))
        status_text.config(state=tk.DISABLED)

    def stop_all(self):
        """Stop all servers"""
        self.stop_admin_server()
        self.stop_client_server()
        self.stop_preview_server()
        if hasattr(self, 'api_running') and self.api_running:
            self.stop_api_server()

    def on_closing(self):
        """Handle window close"""
        if messagebox.askokcancel("Quit", "Stop all servers and quit?"):
            self.log("Shutting down servers...", "INFO")
            self.stop_all()
            self.save_config()
            self.root.destroy()

def main():
    root = tk.Tk()
    app = DevServerGUI(root)
    root.protocol("WM_DELETE_WINDOW", app.on_closing)
    root.mainloop()

if __name__ == "__main__":
    main()