#!/usr/bin/env python3
"""
Development Server GUI
Simple GUI to run PHP development servers for testing
"""

import tkinter as tk
from tkinter import ttk, scrolledtext
import subprocess
import threading
import os
import webbrowser
from datetime import datetime

class DevServerGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("Server 1586 - Development Server")
        self.root.geometry("900x700")

        # Server processes
        self.admin_process = None
        self.client_process = None
        self.preview_process = None

        # Server status
        self.admin_running = False
        self.client_running = False
        self.preview_running = False

        self.setup_ui()

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
            text="Start/Stop PHP servers and monitor logs",
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
            text="http://localhost:8000/admin/login.php",
            foreground="blue",
            cursor="hand2"
        )
        self.admin_url_label.pack(side=tk.LEFT, padx=5)
        self.admin_url_label.bind("<Button-1>", lambda e: webbrowser.open("http://localhost:8000/admin/login.php"))

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
            command=lambda: webbrowser.open("http://localhost:8000/admin/login.php")
        ).pack(side=tk.LEFT, padx=5)

        # Client/Public Site Section
        client_frame = ttk.LabelFrame(self.root, text="Public Site Server (React)", padding="10")
        client_frame.pack(fill=tk.X, padx=10, pady=5)

        client_info_frame = ttk.Frame(client_frame)
        client_info_frame.pack(fill=tk.X)

        ttk.Label(client_info_frame, text="URL:", font=("Arial", 10, "bold")).pack(side=tk.LEFT, padx=5)
        self.client_url_label = ttk.Label(
            client_info_frame,
            text="http://localhost:5173",
            foreground="blue",
            cursor="hand2"
        )
        self.client_url_label.pack(side=tk.LEFT, padx=5)
        self.client_url_label.bind("<Button-1>", lambda e: webbrowser.open("http://localhost:5173"))

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
            command=lambda: webbrowser.open("http://localhost:5173")
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
            command=lambda: webbrowser.open("http://localhost:4173")
        ).pack(side=tk.LEFT, padx=5)

        # Preview server status
        preview_status_frame = ttk.Frame(vite_frame)
        preview_status_frame.pack(fill=tk.X, pady=2)

        ttk.Label(preview_status_frame, text="Preview Server:", font=("Arial", 9)).pack(side=tk.LEFT, padx=5)
        self.preview_url_label = ttk.Label(
            preview_status_frame,
            text="http://localhost:4173",
            foreground="blue",
            cursor="hand2"
        )
        self.preview_url_label.pack(side=tk.LEFT, padx=5)
        self.preview_url_label.bind("<Button-1>", lambda e: webbrowser.open("http://localhost:4173"))

        self.preview_status_label = ttk.Label(preview_status_frame, text="● Stopped", foreground="red")
        self.preview_status_label.pack(side=tk.RIGHT, padx=5)

        # Quick Actions
        actions_frame = ttk.LabelFrame(self.root, text="Quick Actions", padding="10")
        actions_frame.pack(fill=tk.X, padx=10, pady=5)

        ttk.Button(
            actions_frame,
            text="🚀 Start Both Servers",
            command=self.start_both
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

        # Logs Section
        logs_frame = ttk.LabelFrame(self.root, text="Server Logs", padding="10")
        logs_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        self.log_text = scrolledtext.ScrolledText(
            logs_frame,
            height=20,
            font=("Courier", 9),
            bg="#1e1e1e",
            fg="#d4d4d4",
            insertbackground="white"
        )
        self.log_text.pack(fill=tk.BOTH, expand=True)

        # Status bar
        self.status_bar = ttk.Label(self.root, text="Ready", relief=tk.SUNKEN, anchor=tk.W)
        self.status_bar.pack(fill=tk.X, side=tk.BOTTOM)

        self.log("✓ Development Server GUI ready")
        self.log("📁 Project directory: " + os.getcwd())

    def log(self, message, level="INFO"):
        """Add message to log window"""
        timestamp = datetime.now().strftime("%H:%M:%S")
        formatted_msg = f"[{timestamp}] [{level}] {message}\n"

        self.log_text.insert(tk.END, formatted_msg)
        self.log_text.see(tk.END)
        self.log_text.update()

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
            self.log("Starting admin server on port 8000...", "INFO")

            # Start PHP built-in server
            self.admin_process = subprocess.Popen(
                ["php", "-S", "localhost:8000"],
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
            self.status_bar.config(text="Admin server started on http://localhost:8000")

            self.log("✓ Admin server started successfully", "SUCCESS")
            self.log("→ Access at: http://localhost:8000/admin/login.php", "INFO")

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

            # Check if in client directory or need to cd
            client_dir = "client" if os.path.exists("client") else "."

            # Start npm dev server
            self.client_process = subprocess.Popen(
                ["npm", "run", "dev"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1
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
            self.status_bar.config(text="Client server started on http://localhost:5173")

            self.log("✓ Client server started successfully", "SUCCESS")
            self.log("→ Access at: http://localhost:5173", "INFO")

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

    def start_both(self):
        """Start both servers"""
        self.start_admin_server()
        self.start_client_server()

    def build_production(self):
        """Build production version of client"""
        try:
            self.log("Building production version...", "INFO")

            client_dir = "client" if os.path.exists("client") else "."

            # Run npm build
            process = subprocess.Popen(
                ["npm", "run", "build"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1
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

            client_dir = "client" if os.path.exists("client") else "."

            # Start preview server
            self.preview_process = subprocess.Popen(
                ["npm", "run", "preview"],
                cwd=client_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                universal_newlines=True,
                bufsize=1
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
            self.status_bar.config(text="Preview server started on http://localhost:4173")

            self.log("✓ Preview server started successfully", "SUCCESS")
            self.log("→ Access at: http://localhost:4173", "INFO")

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

            client_dir = "client" if os.path.exists("client") else "."
            dist_path = os.path.join(client_dir, "dist")

            if os.path.exists(dist_path):
                import shutil
                shutil.rmtree(dist_path)
                self.log("✓ Dist directory cleaned", "SUCCESS")
                self.status_bar.config(text="Dist directory cleaned")
            else:
                self.log("Dist directory does not exist", "INFO")

        except Exception as e:
            self.log(f"✗ Failed to clean dist: {e}", "ERROR")

    def stop_all(self):
        """Stop all servers"""
        self.stop_admin_server()
        self.stop_client_server()
        self.stop_preview_server()

    def on_closing(self):
        """Handle window close"""
        self.log("Shutting down servers...", "INFO")
        self.stop_all()
        self.root.destroy()

def main():
    root = tk.Tk()
    app = DevServerGUI(root)
    root.protocol("WM_DELETE_WINDOW", app.on_closing)
    root.mainloop()

if __name__ == "__main__":
    main()
