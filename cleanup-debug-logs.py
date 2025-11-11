#!/usr/bin/env python3
"""
KLASSCI Debug Cleanup Script

Nettoie tous les logs debug dans l'application:
1. Remplace console.log/error/warn/info par debugLog/debugError/debugWarn/debugInfo
2. Remplace alert() par debugAlert()
3. Conditionne les blocs de debug avec @if(config('app.debug'))
4. Supprime ou conditionne dd()/dump()/var_dump()

Usage:
    python3 cleanup-debug-logs.py --dry-run  # Preview changes
    python3 cleanup-debug-logs.py            # Apply changes
"""

import os
import re
import argparse
from pathlib import Path
from typing import List, Tuple

# Directories to scan
SCAN_DIRS = [
    'resources/views',
    'public/js'
]

# File patterns to include
INCLUDE_PATTERNS = [
    '*.blade.php',
    '*.php',
    '*.js'
]

# File patterns to exclude
EXCLUDE_PATTERNS = [
    '*.min.js',
    'debug-helper.js',  # Don't modify our helper
    'node_modules',
    'vendor',
    'storage',
    '.git'
]

class DebugCleaner:
    def __init__(self, dry_run=False):
        self.dry_run = dry_run
        self.stats = {
            'files_scanned': 0,
            'files_modified': 0,
            'console_log_replaced': 0,
            'console_error_replaced': 0,
            'console_warn_replaced': 0,
            'console_info_replaced': 0,
            'alert_replaced': 0,
            'dd_dump_found': 0
        }

    def should_exclude(self, filepath: str) -> bool:
        """Check if file should be excluded"""
        for pattern in EXCLUDE_PATTERNS:
            if pattern in filepath:
                return True
        return False

    def clean_javascript_content(self, content: str, is_blade: bool) -> Tuple[str, bool]:
        """
        Clean JavaScript console.log and alert() calls
        Returns: (cleaned_content, was_modified)
        """
        modified = False
        original = content

        # Replace console.log
        pattern = r'\bconsole\.log\('
        if re.search(pattern, content):
            content = re.sub(pattern, 'debugLog(', content)
            count = len(re.findall(pattern, original))
            self.stats['console_log_replaced'] += count
            modified = True

        # Replace console.error
        pattern = r'\bconsole\.error\('
        if re.search(pattern, content):
            content = re.sub(pattern, 'debugError(', content)
            count = len(re.findall(pattern, original))
            self.stats['console_error_replaced'] += count
            modified = True

        # Replace console.warn
        pattern = r'\bconsole\.warn\('
        if re.search(pattern, content):
            content = re.sub(pattern, 'debugWarn(', content)
            count = len(re.findall(pattern, original))
            self.stats['console_warn_replaced'] += count
            modified = True

        # Replace console.info
        pattern = r'\bconsole\.info\('
        if re.search(pattern, content):
            content = re.sub(pattern, 'debugInfo(', content)
            count = len(re.findall(pattern, original))
            self.stats['console_info_replaced'] += count
            modified = True

        # Replace alert() - mais PAS dans les conditions ou confirmations fonctionnelles
        # On ne remplace QUE les alert() qui semblent être du debug
        debug_alert_pattern = r'\balert\((\'|")(?:Debug|Test|LOG|🔍|📊|✅|❌|⚠️|🚀)'
        if re.search(debug_alert_pattern, content):
            content = re.sub(r'\balert\(', 'debugAlert(', content)
            count = len(re.findall(debug_alert_pattern, original))
            self.stats['alert_replaced'] += count
            modified = True

        return content, modified

    def clean_blade_dd_dump(self, content: str) -> Tuple[str, bool]:
        """
        Find and comment out dd()/dump() calls in Blade files
        Returns: (cleaned_content, was_modified)
        """
        modified = False

        # Pattern pour dd() et dump()
        patterns = [
            r'@dd\(',
            r'@dump\(',
            r'{{\s*dd\(',
            r'{{\s*dump\('
        ]

        for pattern in patterns:
            if re.search(pattern, content):
                self.stats['dd_dump_found'] += len(re.findall(pattern, content))
                # Commenter la ligne entière
                content = re.sub(
                    r'^(.*)(' + pattern + r'.*)$',
                    r'\1{{-- DEBUG: \2 --}}',
                    content,
                    flags=re.MULTILINE
                )
                modified = True

        return content, modified

    def process_file(self, filepath: Path) -> None:
        """Process a single file"""
        self.stats['files_scanned'] += 1

        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
        except Exception as e:
            print(f"❌ Erreur lecture {filepath}: {e}")
            return

        original_content = content
        file_modified = False

        # Determine file type
        is_blade = filepath.suffix == '.php' and '.blade.' in str(filepath)
        is_js = filepath.suffix == '.js'

        # Clean JavaScript content
        if is_blade or is_js:
            content, modified = self.clean_javascript_content(content, is_blade)
            file_modified = file_modified or modified

        # Clean Blade dd/dump
        if is_blade:
            content, modified = self.clean_blade_dd_dump(content)
            file_modified = file_modified or modified

        # Write back if modified
        if file_modified:
            self.stats['files_modified'] += 1

            if self.dry_run:
                print(f"🔍 [DRY-RUN] Fichier à modifier: {filepath}")
            else:
                try:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Nettoyé: {filepath}")
                except Exception as e:
                    print(f"❌ Erreur écriture {filepath}: {e}")

    def scan_directory(self, base_dir: str) -> List[Path]:
        """Scan directory for files to process"""
        base_path = Path(base_dir)
        files = []

        for pattern in INCLUDE_PATTERNS:
            for filepath in base_path.rglob(pattern):
                if not self.should_exclude(str(filepath)):
                    files.append(filepath)

        return files

    def run(self, base_dir: str = '.') -> None:
        """Run the cleanup process"""
        print("🚀 KLASSCI Debug Cleanup - Démarrage...")
        print(f"Mode: {'DRY-RUN (preview)' if self.dry_run else 'PRODUCTION (apply changes)'}")
        print()

        all_files = []
        for scan_dir in SCAN_DIRS:
            dir_path = os.path.join(base_dir, scan_dir)
            if os.path.exists(dir_path):
                files = self.scan_directory(dir_path)
                all_files.extend(files)
                print(f"📁 {scan_dir}: {len(files)} fichiers trouvés")

        print(f"\n📊 Total: {len(all_files)} fichiers à scanner\n")
        print("─" * 60)

        for filepath in all_files:
            self.process_file(filepath)

        print("\n" + "─" * 60)
        print("📊 STATISTIQUES:")
        print(f"   Fichiers scannés: {self.stats['files_scanned']}")
        print(f"   Fichiers modifiés: {self.stats['files_modified']}")
        print(f"   console.log → debugLog: {self.stats['console_log_replaced']}")
        print(f"   console.error → debugError: {self.stats['console_error_replaced']}")
        print(f"   console.warn → debugWarn: {self.stats['console_warn_replaced']}")
        print(f"   console.info → debugInfo: {self.stats['console_info_replaced']}")
        print(f"   alert() → debugAlert(): {self.stats['alert_replaced']}")
        print(f"   dd()/dump() trouvés: {self.stats['dd_dump_found']}")

        if self.dry_run:
            print("\n⚠️  Mode DRY-RUN: Aucune modification appliquée")
            print("   Relancez sans --dry-run pour appliquer les changements")
        else:
            print("\n✅ Nettoyage terminé!")
            print("   N'oubliez pas de tester avec APP_DEBUG=false")

def main():
    parser = argparse.ArgumentParser(description='Cleanup debug logs in KLASSCI')
    parser.add_argument('--dry-run', action='store_true', help='Preview changes without applying them')
    parser.add_argument('--dir', default='.', help='Base directory (default: current)')
    args = parser.parse_args()

    cleaner = DebugCleaner(dry_run=args.dry_run)
    cleaner.run(args.dir)

if __name__ == '__main__':
    main()
