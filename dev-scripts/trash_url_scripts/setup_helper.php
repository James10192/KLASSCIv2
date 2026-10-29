<?php
/**
 * Script d'aide pour l'installation de l'application Smart School
 * 
 * Ce script affiche l'URL d'installation et fournit des instructions pour configurer l'application.
 * 
 * @author Claude 3.7 Sonnet
 * @version 1.0
 */

// Détection du protocole (http ou https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Détection du nom d'hôte (localhost, 127.0.0.1, etc.)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Construction de l'URL de base
$baseUrl = "$protocol://$host";

// URL d'installation
$setupUrl = "$baseUrl/smart_school_new/setup";

echo "=================================================================\n";
echo "      ASSISTANT D'INSTALLATION DE SMART SCHOOL                   \n";
echo "=================================================================\n\n";

echo "Pour configurer votre application Smart School, veuillez accéder à l'URL suivante :\n\n";
echo "🔗 $setupUrl\n\n";

echo "Instructions :\n";
echo "1. Ouvrez cette URL dans votre navigateur\n";
echo "2. Configurez les paramètres de la base de données\n";
echo "3. Créez un compte administrateur\n";
echo "4. Finalisez l'installation\n\n";

echo "Prérequis :\n";
echo "- PHP 8.0 ou supérieur\n";
echo "- MySQL 5.7 ou supérieur\n";
echo "- Extensions PHP : PDO, Mbstring, Tokenizer, XML, Ctype, JSON\n";
echo "- Serveur web (Apache/Nginx) configuré avec le module de réécriture d'URL\n\n";

echo "=================================================================\n";
echo "Pour toute assistance, consultez la documentation ou contactez le support.\n";
echo "=================================================================\n"; 