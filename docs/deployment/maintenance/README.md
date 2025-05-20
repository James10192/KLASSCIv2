# Guide de Maintenance - ESBTP Système de Suivi des Présences

## Vue d'ensemble

Ce guide détaille les procédures de maintenance pour le système de suivi des présences ESBTP. Il couvre les tâches régulières, la résolution des problèmes courants et les procédures de mise à jour.

## Maintenance Quotidienne

### 1. Vérification des Logs

```bash
# Vérifier les logs d'erreur Laravel
tail -f storage/logs/laravel.log

# Vérifier les logs Nginx
tail -f /var/log/nginx/error.log

# Vérifier les logs des workers
tail -f /var/log/esbtp-worker.log
```

### 2. Surveillance des Ressources

```bash
# Espace disque
df -h

# Utilisation mémoire
free -m

# Charge CPU
top

# État MySQL
mysqlshow --count
```

### 3. Vérification des Services

```bash
# Vérifier l'état des services
systemctl status nginx
systemctl status mysql
systemctl status redis
systemctl status supervisor

# Vérifier les workers Laravel
php artisan queue:status
```

## Maintenance Hebdomadaire

### 1. Nettoyage

```bash
# Nettoyer le cache
php artisan cache:clear
php artisan view:clear

# Nettoyer les anciennes sessions
php artisan session:gc

# Nettoyer les vieux fichiers temporaires
find storage/framework/cache -type f -mtime +7 -delete
```

### 2. Sauvegardes

```bash
# Sauvegarde de la base de données
mysqldump -u user -p database > backup-$(date +%Y%m%d).sql

# Sauvegarde des fichiers
tar -czf backup-files-$(date +%Y%m%d).tar.gz storage/app/

# Rotation des sauvegardes
find /backup -type f -mtime +30 -delete
```

### 3. Vérification de Sécurité

-   Vérifier les tentatives de connexion suspectes
-   Examiner les logs d'audit
-   Vérifier les permissions des fichiers

## Maintenance Mensuelle

### 1. Mises à Jour

```bash
# Mettre à jour les dépendances
composer update --no-dev
npm update

# Mettre à jour les assets
npm run production

# Vider le cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Reconstruire le cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Optimisation Base de Données

```sql
-- Analyser les tables
ANALYZE TABLE users, students, attendance_codes, attendances;

-- Optimiser les tables
OPTIMIZE TABLE users, students, attendance_codes, attendances;

-- Vérifier les index
SHOW INDEX FROM users;
SHOW INDEX FROM students;
SHOW INDEX FROM attendance_codes;
SHOW INDEX FROM attendances;
```

### 3. Archivage

-   Archiver les vieux codes de présence
-   Archiver les logs anciens
-   Nettoyer les données temporaires

## Procédures d'Urgence

### 1. Panne de Serveur

1. Vérifier les services essentiels
2. Consulter les logs d'erreur
3. Redémarrer les services si nécessaire
4. Notifier les utilisateurs

```bash
# Redémarrage des services
systemctl restart nginx
systemctl restart php7.4-fpm
systemctl restart mysql
systemctl restart redis
systemctl restart supervisor
```

### 2. Problèmes de Base de Données

1. Vérifier l'espace disque
2. Examiner les logs MySQL
3. Réparer les tables si nécessaire
4. Restaurer une sauvegarde si requis

```sql
-- Vérifier les tables
CHECK TABLE users, students, attendance_codes, attendances;

-- Réparer si nécessaire
REPAIR TABLE users, students, attendance_codes, attendances;
```

### 3. Problèmes d'Application

1. Activer le mode maintenance
2. Vérifier les logs d'erreur
3. Corriger le problème
4. Désactiver le mode maintenance

```bash
# Activer le mode maintenance
php artisan down

# Désactiver le mode maintenance
php artisan up
```

## Monitoring

### 1. Métriques à Surveiller

-   Temps de réponse de l'application
-   Utilisation des ressources serveur
-   Taux d'erreur
-   Nombre de connexions simultanées

### 2. Alertes

Configurer des alertes pour :

-   Espace disque < 20%
-   Charge CPU > 80%
-   Mémoire disponible < 500MB
-   Erreurs 500 > 10/minute

### 3. Rapports

Générer des rapports mensuels sur :

-   Performance du système
-   Utilisation des ressources
-   Incidents et résolutions
-   Statistiques d'utilisation

## Sécurité

### 1. Mises à Jour de Sécurité

```bash
# Mettre à jour le système
apt update && apt upgrade

# Vérifier les dépendances PHP
composer audit

# Vérifier les dépendances npm
npm audit
```

### 2. Audit de Sécurité

-   Vérifier les permissions des fichiers
-   Examiner les logs de sécurité
-   Tester les sauvegardes
-   Vérifier les certificats SSL

### 3. Gestion des Accès

-   Réviser les comptes utilisateurs
-   Mettre à jour les mots de passe
-   Vérifier les journaux d'accès
-   Nettoyer les sessions expirées

## Documentation

### 1. Mise à Jour

-   Documenter les changements de configuration
-   Mettre à jour les procédures
-   Noter les problèmes récurrents
-   Maintenir le journal des modifications

### 2. Formation

-   Former l'équipe de support
-   Documenter les nouvelles procédures
-   Mettre à jour les guides utilisateurs
-   Organiser des sessions de formation

## Support

Pour toute assistance :

-   Documentation technique : [Lien](../../technical/README.md)
-   Email : support@esbtp.com
-   Urgence : +XX XX XX XX XX
