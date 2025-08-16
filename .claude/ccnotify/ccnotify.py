#!/usr/bin/env python3
"""
CCNotify for WSL Ubuntu - Desktop notifications for Claude Code
Adapted from https://github.com/dazuiba/CCNotify for Linux/WSL environments
"""

import os
import sys
import sqlite3
import json
import time
import subprocess
import logging
from datetime import datetime
from pathlib import Path

# Configuration
CCNOTIFY_DIR = Path.home() / '.claude' / 'ccnotify'
DB_PATH = CCNOTIFY_DIR / 'ccnotify.db'
LOG_PATH = CCNOTIFY_DIR / 'ccnotify.log'

# Ensure directory exists
CCNOTIFY_DIR.mkdir(parents=True, exist_ok=True)

# Setup logging
logging.basicConfig(
    filename=LOG_PATH,
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

class CCNotify:
    def __init__(self):
        self.init_db()
    
    def init_db(self):
        """Initialize SQLite database for session tracking"""
        conn = sqlite3.connect(DB_PATH)
        conn.execute('''
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_path TEXT,
                start_time REAL,
                end_time REAL,
                status TEXT DEFAULT 'active'
            )
        ''')
        conn.commit()
        conn.close()
    
    def get_current_project(self):
        """Get current working directory as project identifier"""
        return os.getcwd()
    
    def send_notification(self, title, message, urgency='normal'):
        """Send desktop notification using various methods for WSL"""
        try:
            # Méthode 1: Windows Explorer popup (simple et fiable pour WSL)
            if self.has_powershell():
                # Méthode simple avec popup Windows
                escaped_title = title.replace('"', '""')
                escaped_message = message.replace('"', '""')
                ps_script = f'Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.MessageBox]::Show("{escaped_message}", "{escaped_title}", "OK", "Information")'
                
                cmd = ['powershell.exe', '-Command', ps_script]
                result = subprocess.run(cmd, capture_output=True, text=True, timeout=10)
                
                if result.returncode == 0:
                    logging.info(f"Windows popup notification sent: {title} - {message}")
                    return True
                else:
                    logging.warning(f"Windows popup failed: {result.stderr}")
            
            # Méthode 2: Notification système WSL via cmd
            if self.has_cmd():
                # Utiliser msg command de Windows
                cmd = ['cmd.exe', '/c', f'msg * "{title}: {message}"']
                result = subprocess.run(cmd, capture_output=True, text=True, timeout=10)
                
                if result.returncode == 0:
                    logging.info(f"Windows msg notification sent: {title} - {message}")
                    return True
            
            # Méthode 3: Ballon tooltip (alternative)
            if self.has_powershell():
                ps_balloon = f'''
                Add-Type -AssemblyName System.Windows.Forms
                $balloon = New-Object System.Windows.Forms.NotifyIcon
                $balloon.Icon = [System.Drawing.SystemIcons]::Information
                $balloon.BalloonTipTitle = "{title.replace('"', '""')}"
                $balloon.BalloonTipText = "{message.replace('"', '""')}"
                $balloon.Visible = $true
                $balloon.ShowBalloonTip(5000)
                Start-Sleep -Seconds 2
                $balloon.Dispose()
                '''
                cmd = ['powershell.exe', '-Command', ps_balloon]
                result = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
                
                if result.returncode == 0:
                    logging.info(f"Windows balloon notification sent: {title} - {message}")
                    return True
            
            # Fallback: Terminal avec son et couleurs
            colors = {
                'critical': '\033[91m',  # Rouge
                'normal': '\033[94m',    # Bleu
                'low': '\033[92m',       # Vert
                'reset': '\033[0m'
            }
            
            color = colors.get(urgency, colors['normal'])
            print(f"\a{color}🔔 {title}:{colors['reset']} {message}")
            logging.info(f"Terminal notification sent: {title} - {message}")
            return True
                
        except Exception as e:
            logging.error(f"All notification methods failed: {e}")
            # Fallback ultime
            print(f"\a🔔 {title}: {message}")
            return False
    
    def has_notify_send(self):
        """Check if notify-send is available"""
        try:
            subprocess.run(['which', 'notify-send'], check=True, capture_output=True)
            return True
        except:
            return False
    
    def has_powershell(self):
        """Check if PowerShell is available (WSL scenario)"""
        try:
            subprocess.run(['powershell.exe', '-Command', 'echo test'], check=True, capture_output=True, timeout=5)
            return True
        except:
            return False
    
    def has_cmd(self):
        """Check if cmd.exe is available (WSL scenario)"""
        try:
            subprocess.run(['cmd.exe', '/c', 'echo test'], check=True, capture_output=True, timeout=5)
            return True
        except:
            return False
    
    def handle_user_prompt_submit(self):
        """Handle when user submits a prompt to Claude"""
        project = self.get_current_project()
        current_time = time.time()
        
        conn = sqlite3.connect(DB_PATH)
        # End any active sessions for this project
        conn.execute(
            'UPDATE sessions SET status = "completed", end_time = ? WHERE project_path = ? AND status = "active"',
            (current_time, project)
        )
        # Start new session
        conn.execute(
            'INSERT INTO sessions (project_path, start_time, status) VALUES (?, ?, "active")',
            (project, current_time)
        )
        conn.commit()
        conn.close()
        
        logging.info(f"Started new session for project: {project}")
    
    def handle_stop(self):
        """Handle when Claude completes a task"""
        project = self.get_current_project()
        current_time = time.time()
        
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.execute(
            'SELECT start_time FROM sessions WHERE project_path = ? AND status = "active" ORDER BY id DESC LIMIT 1',
            (project,)
        )
        result = cursor.fetchone()
        
        if result:
            start_time = result[0]
            duration = current_time - start_time
            
            # Update session
            conn.execute(
                'UPDATE sessions SET status = "completed", end_time = ? WHERE project_path = ? AND status = "active"',
                (current_time, project)
            )
            conn.commit()
            
            # Format duration
            if duration < 60:
                duration_str = f"{duration:.1f}s"
            elif duration < 3600:
                minutes = int(duration // 60)
                seconds = int(duration % 60)
                duration_str = f"{minutes}m {seconds}s"
            else:
                hours = int(duration // 3600)
                minutes = int((duration % 3600) // 60)
                duration_str = f"{hours}h {minutes}m"
            
            # Send completion notification
            title = "Claude Code - Task Completed"
            message = f"Task finished in {duration_str}\nProject: {os.path.basename(project)}"
            self.send_notification(title, message, 'low')
            
            logging.info(f"Task completed for {project} in {duration_str}")
        
        conn.close()
    
    def handle_notification(self):
        """Handle when Claude needs user input"""
        project = self.get_current_project()
        title = "Claude Code - Input Needed"
        message = f"Claude is waiting for your input\nProject: {os.path.basename(project)}"
        self.send_notification(title, message, 'critical')
        logging.info(f"Input needed notification for project: {project}")

def main():
    if len(sys.argv) < 2:
        print("ok")
        return
    
    ccnotify = CCNotify()
    action = sys.argv[1]
    
    if action == "UserPromptSubmit":
        ccnotify.handle_user_prompt_submit()
    elif action == "Stop":
        ccnotify.handle_stop()
    elif action == "Notification":
        ccnotify.handle_notification()
    else:
        logging.warning(f"Unknown action: {action}")

if __name__ == "__main__":
    main()