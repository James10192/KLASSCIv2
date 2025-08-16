#!/bin/bash
# Installation script for CCNotify WSL Ubuntu adaptation

echo "🔧 Installation de CCNotify pour WSL Ubuntu..."

# Créer les dossiers nécessaires
mkdir -p ~/.claude/ccnotify

# Copier les fichiers (si pas déjà fait)
if [ ! -f ~/.claude/ccnotify/ccnotify.py ]; then
    cp ccnotify.py ~/.claude/ccnotify/
    chmod +x ~/.claude/ccnotify/ccnotify.py
fi

# Tester les notifications disponibles
echo "🔍 Test des systèmes de notification disponibles..."

# Test notify-send (Linux natif)
if command -v notify-send >/dev/null 2>&1; then
    echo "✅ notify-send trouvé (Linux notifications)"
    notify-send "CCNotify" "Test Linux notification" --icon=dialog-information
else
    echo "⚠️  notify-send non trouvé. Installation recommandée:"
    echo "   sudo apt install libnotify-bin"
fi

# Test PowerShell (WSL vers Windows)
if command -v powershell.exe >/dev/null 2>&1; then
    echo "✅ PowerShell trouvé (Windows notifications via WSL)"
    # Test de notification Windows simple
    powershell.exe -Command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.MessageBox]::Show('Test CCNotify', 'CCNotify')" 2>/dev/null || echo "Note: Notifications Windows toast nécessitent Windows 10+"
else
    echo "⚠️  PowerShell non trouvé. Vérifiez que vous êtes dans WSL."
fi

# Test du script principal
echo "🧪 Test du script CCNotify..."
if ~/.claude/ccnotify/ccnotify.py | grep -q "ok"; then
    echo "✅ Script CCNotify fonctionne correctement"
else
    echo "❌ Erreur avec le script CCNotify"
    exit 1
fi

# Vérifier Python
if command -v python3 >/dev/null 2>&1; then
    echo "✅ Python3 trouvé: $(python3 --version)"
else
    echo "❌ Python3 requis. Installation:"
    echo "   sudo apt install python3"
    exit 1
fi

# Afficher les informations d'intégration
echo ""
echo "🎯 Intégration avec Claude Code:"
echo "Les hooks suivants doivent être dans ~/.claude/settings.json:"
echo ""
cat << 'EOF'
"hooks": [
  {
    "matcher": "UserPromptSubmit",
    "hooks": [
      {
        "type": "command",
        "command": "~/.claude/ccnotify/ccnotify.py UserPromptSubmit"
      }
    ]
  },
  {
    "matcher": "Stop",
    "hooks": [
      {
        "type": "command",
        "command": "~/.claude/ccnotify/ccnotify.py Stop"
      }
    ]
  },
  {
    "matcher": "Notification",
    "hooks": [
      {
        "type": "command",
        "command": "~/.claude/ccnotify/ccnotify.py Notification"
      }
    ]
  }
]
EOF

echo ""
echo "🚀 Installation terminée ! Redémarrez Claude Code pour activer les notifications."
echo "📋 Logs disponibles dans: ~/.claude/ccnotify/ccnotify.log"
echo "🗄️  Database sessions: ~/.claude/ccnotify/ccnotify.db"