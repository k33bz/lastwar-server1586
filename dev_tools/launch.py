#!/usr/bin/env python3
"""
Server 1586 Development Server Launcher
Convenience script to launch the development server GUI from anywhere
"""

import os
import sys
from pathlib import Path

def main():
    # Get the directory containing this script
    script_dir = Path(__file__).parent
    
    # Change to the dev_tools directory
    os.chdir(script_dir)
    
    # Import and run the main dev server
    try:
        from dev_server import main as dev_server_main
        print("🚀 Launching Server 1586 Development Server...")
        dev_server_main()
    except ImportError as e:
        print(f"❌ Failed to import dev_server: {e}")
        print("Make sure you're running from the dev_tools directory")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Failed to start development server: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()