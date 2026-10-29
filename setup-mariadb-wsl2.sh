#!/bin/bash

echo "════════════════════════════════════════════════════════════════"
echo "  Configuration MariaDB dans WSL2 pour Laravel"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Vérifier si MariaDB est installé
if ! dpkg -l | grep -q mariadb-server; then
    echo "❌ MariaDB n'est pas installé"
    echo "   Installez-le avec: sudo apt install mariadb-server"
    exit 1
fi

echo "✅ MariaDB est installé"
echo ""

# Vérifier si MariaDB est démarré
echo "🔄 Vérification du statut de MariaDB..."
if ! sudo service mariadb status | grep -q "active (running)"; then
    echo "⚠️  MariaDB n'est pas démarré. Démarrage..."
    sudo service mariadb start
    sleep 2

    if sudo service mariadb status | grep -q "active (running)"; then
        echo "✅ MariaDB démarré avec succès"
    else
        echo "❌ Impossible de démarrer MariaDB"
        exit 1
    fi
else
    echo "✅ MariaDB est déjà en cours d'exécution"
fi
echo ""

# Test de connexion
echo "🔄 Test de connexion à MariaDB..."
if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Connexion réussie"
else
    echo "❌ Impossible de se connecter à MariaDB"
    exit 1
fi
echo ""

# Créer la base de données
echo "🔄 Création de la base de données 'esbtp-abidjan-db'..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`esbtp-abidjan-db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Base de données créée ou existe déjà"
else
    echo "❌ Erreur lors de la création de la base"
    exit 1
fi
echo ""

# Créer les utilisateurs
echo "🔄 Configuration des utilisateurs MySQL..."
sudo mysql <<EOF
-- Utilisateur pour localhost (Laravel dans WSL2)
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;

-- Utilisateur pour connexions externes (phpMyAdmin depuis Windows)
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo "✅ Utilisateurs configurés"
else
    echo "❌ Erreur lors de la configuration des utilisateurs"
    exit 1
fi
echo ""

# Test de connexion sans sudo
echo "🔄 Test de connexion sans sudo..."
if mysql -u root -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Connexion sans mot de passe fonctionne"
else
    echo "⚠️  La connexion sans sudo nécessite un mot de passe"
    echo "   Mais Laravel pourra se connecter via socket Unix"
fi
echo ""

# Vérifier les bases de données
echo "📊 Bases de données disponibles:"
mysql -u root -e "SHOW DATABASES;" 2>/dev/null || sudo mysql -e "SHOW DATABASES;"
echo ""

# Afficher les tables si la base existe
echo "📋 Tables dans esbtp-abidjan-db:"
TABLE_COUNT=$(mysql -u root -e "USE \`esbtp-abidjan-db\`; SHOW TABLES;" 2>/dev/null | wc -l)
if [ $TABLE_COUNT -gt 1 ]; then
    mysql -u root -e "USE \`esbtp-abidjan-db\`; SHOW TABLES;" 2>/dev/null | head -20
    echo "   ... (Total: $((TABLE_COUNT - 1)) tables)"
else
    echo "   ⚠️  Aucune table (base vide - normal si pas encore importé)"
fi
echo ""

# Vérifier la configuration .env
echo "🔄 Vérification du fichier .env..."
if [ -f .env ]; then
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)

    if [ "$DB_HOST" = "localhost" ]; then
        echo "✅ DB_HOST=localhost (correct)"
    else
        echo "⚠️  DB_HOST=$DB_HOST (devrait être 'localhost')"
        echo "   Modifiez .env: DB_HOST=localhost"
    fi

    if [ "$DB_DATABASE" = "esbtp-abidjan-db" ]; then
        echo "✅ DB_DATABASE=esbtp-abidjan-db (correct)"
    else
        echo "⚠️  DB_DATABASE=$DB_DATABASE"
    fi
else
    echo "❌ Fichier .env introuvable"
fi
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "  Configuration terminée!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "📝 Prochaines étapes:"
echo ""
echo "1. Si vous avez des données dans XAMPP à importer:"
echo "   - Dans Windows PowerShell:"
echo "     cd C:\\xampp\\mysql\\bin"
echo "     .\\mysqldump.exe -u root esbtp-abidjan-db > C:\\Users\\yabla\\Desktop\\backup.sql"
echo ""
echo "   - Dans WSL2:"
echo "     mysql -u root esbtp-abidjan-db < /mnt/c/Users/yabla/Desktop/backup.sql"
echo ""
echo "2. Tester Laravel:"
echo "   php artisan migrate:status"
echo "   php artisan db:show"
echo ""
echo "3. Pour phpMyAdmin depuis Windows (optionnel):"
echo "   - Configurer MariaDB pour écouter sur 0.0.0.0:3306"
echo "   - Ajouter bind-address=0.0.0.0 dans /etc/mysql/mariadb.conf.d/50-server.cnf"
echo "   - sudo service mariadb restart"
echo ""
