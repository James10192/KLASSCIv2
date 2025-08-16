#!/bin/bash
# Notification simple qui MARCHE (basée sur le test réussi initial)

TITLE="$1"
MESSAGE="$2"

# Notification PowerShell simple qui marchait au début
powershell.exe -Command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.MessageBox]::Show('$MESSAGE', '$TITLE', 'OK', 'Information')"

echo "✅ Notification envoyée: $TITLE"