#!/bin/bash
# Test des hooks Claude Code pour CCNotify

echo "🧪 Test complet des hooks CCNotify..."
echo ""

# Simulation UserPromptSubmit
echo "1️⃣ Simulation UserPromptSubmit (début de tâche)..."
.claude/ccnotify/hybrid-notify.py UserPromptSubmit
echo ""

# Attendre un peu
echo "⏳ Simulation d'une tâche de 3 secondes..."
sleep 3
echo ""

# Simulation Stop
echo "2️⃣ Simulation Stop (fin de tâche)..."
.claude/ccnotify/hybrid-notify.py Stop
echo ""

# Attendre un peu
sleep 1

# Simulation Notification (input requis)
echo "3️⃣ Simulation Notification (input requis)..."
.claude/ccnotify/hybrid-notify.py Notification
echo ""

echo "✅ Test terminé ! Vous devriez avoir vu :"
echo "   - Notification bleue 'Claude Started'"
echo "   - Notification verte 'Task Completed'"
echo "   - Notification rouge 'Input Required'"
echo "   - Popup Windows pour chaque notification"