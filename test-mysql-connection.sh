#!/bin/bash

echo "=== Test de connexion MySQL WSL2 → Windows XAMPP ==="
echo ""

# Obtenir l'IP Windows
WINDOWS_IP=$(cat /etc/resolv.conf | grep nameserver | awk '{print $2}')
echo "✓ IP Windows détectée: $WINDOWS_IP"
echo ""

# Test 1: Ping Windows
echo "Test 1: Ping vers Windows..."
if ping -c 1 -W 1 $WINDOWS_IP > /dev/null 2>&1; then
    echo "✅ Windows est accessible"
else
    echo "❌ Windows n'est pas accessible via ping"
fi
echo ""

# Test 2: Port 3306 ouvert ?
echo "Test 2: Vérification du port MySQL 3306..."
if timeout 2 bash -c "</dev/tcp/$WINDOWS_IP/3306" 2>/dev/null; then
    echo "✅ Port 3306 est ouvert et accessible"
else
    echo "❌ Port 3306 est fermé ou bloqué"
    echo ""
    echo "🔧 Solutions à appliquer dans Windows:"
    echo "   1. Vérifier que MySQL est démarré dans XAMPP"
    echo "   2. Dans PowerShell: netstat -ano | findstr :3306"
    echo "      (Vous devez voir 0.0.0.0:3306, pas 127.0.0.1:3306)"
    echo "   3. Modifier C:\\xampp\\mysql\\bin\\my.ini:"
    echo "      bind-address=0.0.0.0"
    echo "   4. Redémarrer MySQL dans XAMPP"
    echo "   5. Ajouter règle pare-feu (PowerShell Admin):"
    echo "      New-NetFirewallRule -DisplayName \"MySQL WSL2\" -Direction Inbound -LocalPort 3306 -Protocol TCP -Action Allow"
fi
echo ""

# Test 3: Connexion MySQL
echo "Test 3: Connexion MySQL..."
if command -v mysql > /dev/null 2>&1; then
    if mysql -h $WINDOWS_IP -u root -e "SELECT 1;" > /dev/null 2>&1; then
        echo "✅ Connexion MySQL réussie!"
        echo ""
        echo "Bases de données disponibles:"
        mysql -h $WINDOWS_IP -u root -e "SHOW DATABASES;"
    else
        echo "❌ Connexion MySQL échouée"
        echo "   Erreur: $(mysql -h $WINDOWS_IP -u root -e "SELECT 1;" 2>&1 | tail -1)"
    fi
else
    echo "⚠️  Client MySQL non installé dans WSL2"
    echo "   Installez-le: sudo apt install mysql-client"
fi
echo ""

# Test 4: Fichier .env
echo "Test 4: Configuration .env..."
if [ -f .env ]; then
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)
    echo "✓ DB_HOST=$DB_HOST"
    echo "✓ DB_DATABASE=$DB_DATABASE"

    # Vérifier /etc/hosts si utilise un nom
    if [ "$DB_HOST" = "windows.mysql" ]; then
        if grep -q "windows.mysql" /etc/hosts 2>/dev/null; then
            echo "✅ Alias 'windows.mysql' trouvé dans /etc/hosts"
        else
            echo "⚠️  Alias 'windows.mysql' manquant dans /etc/hosts"
            echo "   Ajoutez: echo \"$WINDOWS_IP    windows.mysql\" | sudo tee -a /etc/hosts"
        fi
    fi
else
    echo "❌ Fichier .env introuvable"
fi
echo ""

echo "=== Résumé ==="
echo "Pour que Laravel se connecte à MySQL:"
echo "1. MySQL doit écouter sur 0.0.0.0:3306 (pas 127.0.0.1)"
echo "2. Le pare-feu Windows doit autoriser le port 3306"
echo "3. DB_HOST dans .env doit pointer vers $WINDOWS_IP ou un alias"
echo ""
