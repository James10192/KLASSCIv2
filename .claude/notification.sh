#!/bin/bash
# Script de notification Claude Code

# Son système différent selon l'OS
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    osascript -e 'beep 2'
    say "Claude a terminé la tâche"
elif [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "linux" ]]; then
    # Linux (WSL inclus)
    echo -e '\a\a\a'
    echo "🔔 Claude a terminé la tâche!"
    # Essayer paplay si disponible
    paplay /usr/share/sounds/alsa/Front_Left.wav 2>/dev/null || true
    # Essayer espeak si disponible  
    espeak "Claude finished task" 2>/dev/null || true
elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    # Windows
    echo -e '\a\a\a'
    echo "🔔 Claude a terminé la tâche!"
    # PowerShell beep si disponible
    powershell -c "[console]::beep(800,300)" 2>/dev/null || true
fi

# Notification desktop si disponible
if command -v notify-send &> /dev/null; then
    notify-send "Claude Code" "Tâche terminée ✅" --icon=dialog-information
fi

# Log de fin avec timestamp
echo "$(date '+%H:%M:%S') - ✅ Tâche Claude terminée"