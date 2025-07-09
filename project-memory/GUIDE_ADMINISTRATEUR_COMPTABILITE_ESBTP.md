# 👨‍💼 GUIDE ADMINISTRATEUR - MODULE COMPTABILITÉ ESBTP

## 🎯 PRÉSENTATION DU GUIDE

Ce guide est destiné aux **administrateurs système** responsables de la configuration, supervision et maintenance du module comptabilité ESBTP. Formation recommandée : **4 heures**.

**Public cible :** Administrateurs IT, responsables techniques  
**Prérequis :** Connaissances Laravel, MySQL, Redis  
**Durée formation :** 4h (2h théorie + 2h pratique)

---

## 📋 TABLE DES MATIÈRES

1. [Configuration initiale](#configuration-initiale)
2. [Gestion des utilisateurs et permissions](#gestion-des-utilisateurs-et-permissions)
3. [Configuration du système](#configuration-du-système)
4. [Monitoring et performance](#monitoring-et-performance)
5. [Maintenance préventive](#maintenance-préventive)
6. [Sauvegarde et restauration](#sauvegarde-et-restauration)
7. [Dépannage courant](#dépannage-courant)
8. [Sécurité et audit](#sécurité-et-audit)

---

## 🚀 CONFIGURATION INITIALE

### Installation du Module

```bash
# 1. Vérification des prérequis
php --version                # PHP 8.1+
mysql --version             # MySQL 8.0+
redis-cli ping              # Redis 6.0+

# 2. Installation des dépendances
composer install --no-dev --optimize-autoloader
npm install && npm run production

# 3. Configuration environnement
cp .env.example .env
php artisan key:generate

# 4. Configuration base de données
php artisan migrate --force
php artisan db:seed --class=ComptabiliteSeeder

# 5. Configuration cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Permissions système
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
```

### Configuration .env Avancée

```env
# === CONFIGURATION BASE DE DONNÉES ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esbtp_prod
DB_USERNAME=esbtp_admin
DB_PASSWORD=SecurePassword123!

# Optimisations MySQL
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_STRICT_MODE=true
DB_ENGINE=InnoDB

# === CONFIGURATION CACHE REDIS ===
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=RedisSecurePass
REDIS_PORT=6379
REDIS_DB_CACHE=1
REDIS_DB_SESSION=2
REDIS_DB_QUEUE=3

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# === CONFIGURATION COMPTABILITÉ ===
COMPTABILITE_CACHE_TTL_KPI=900        # 15 minutes
COMPTABILITE_CACHE_TTL_STATS=1800     # 30 minutes
COMPTABILITE_CACHE_TTL_HEAVY=3600     # 1 heure

# Seuils d'alertes
COMPTABILITE_SEUIL_CRITIQUE=1000000   # 1M CFA
COMPTABILITE_TAUX_RECOUVREMENT_MIN=70 # 70%
COMPTABILITE_PERFORMANCE_THRESHOLD=2000 # 2s

# === CONFIGURATION NOTIFICATIONS ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=comptabilite@esbtp.com
MAIL_PASSWORD=AppSpecificPassword
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=comptabilite@esbtp.com
MAIL_FROM_NAME="ESBTP Comptabilité"

# Configuration SMS (Twilio)
SMS_DRIVER=twilio
TWILIO_SID=ACxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+22500000000

# === CONFIGURATION MONITORING ===
APP_DEBUG=false
LOG_LEVEL=info
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null

# Monitoring performance
MONITOR_SLOW_QUERIES=true
MONITOR_MEMORY_USAGE=true
MONITOR_CACHE_HIT_RATE=true

# === CONFIGURATION SÉCURITÉ ===
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,esbtp.local
```

---

## 👥 GESTION DES UTILISATEURS ET PERMISSIONS

### Hiérarchie des Rôles

```
SuperAdmin
├── ComptableChef (Responsable comptabilité)
│   ├── Comptable (Comptable standard)
│   └── AideComptable (Assistant comptable)
└── Secrétaire (Saisie données)
```

### Attribution des Permissions

```bash
# Création des rôles de base
php artisan role:create "ComptableChef" "Responsable comptabilité"
php artisan role:create "Comptable" "Comptable standard"
php artisan role:create "AideComptable" "Assistant comptable"

# Attribution permissions par rôle
php artisan permission:assign ComptableChef "comptabilite.*"
php artisan permission:assign Comptable "comptabilite.dashboard.view,comptabilite.paiements.manage"
php artisan permission:assign AideComptable "comptabilite.dashboard.view,comptabilite.paiements.create"
```

### Matrice des Permissions Détaillée

| Permission                      | SuperAdmin | ComptableChef | Comptable | AideComptable | Secrétaire |
| ------------------------------- | ---------- | ------------- | --------- | ------------- | ---------- |
| `access_comptabilite_module`    | ✅         | ✅            | ✅        | ✅            | ✅         |
| `comptabilite.dashboard.view`   | ✅         | ✅            | ✅        | ✅            | ❌         |
| `comptabilite.paiements.manage` | ✅         | ✅            | ✅        | ❌            | ❌         |
| `comptabilite.paiements.create` | ✅         | ✅            | ✅        | ✅            | ✅         |
| `comptabilite.depenses.manage`  | ✅         | ✅            | ✅        | ❌            | ❌         |
| `comptabilite.bons.approve`     | ✅         | ✅            | ❌        | ❌            | ❌         |
| `comptabilite.reports.export`   | ✅         | ✅            | ✅        | ❌            | ❌         |
| `comptabilite.config.manage`    | ✅         | ✅            | ❌        | ❌            | ❌         |
| `comptabilite.analytics.view`   | ✅         | ✅            | ✅        | ❌            | ❌         |

### Procédure Création Utilisateur

```bash
# 1. Création utilisateur
php artisan user:create \
  --name="Marie Kouassi" \
  --email="marie.kouassi@esbtp.com" \
  --role="Comptable" \
  --department="Comptabilité"

# 2. Activation compte
php artisan user:activate marie.kouassi@esbtp.com

# 3. Envoi email bienvenue
php artisan user:welcome marie.kouassi@esbtp.com
```

---

## ⚙️ CONFIGURATION DU SYSTÈME

### Configuration Cache Redis

```php
// config/cache.php - Stores spécialisés
'stores' => [
    'comptabilite_kpis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'comptabilite_kpis:',
        'serializer' => 'json',
    ],
    'comptabilite_reports' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'comptabilite_reports:',
        'serializer' => 'json',
    ],
    'dashboard_queries' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'dashboard_queries:',
        'serializer' => 'json',
    ],
    'heavy_calculations' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'heavy_calculations:',
        'serializer' => 'json',
    ],
]
```

### Configuration Queue Workers

```bash
# Supervisor configuration (/etc/supervisor/conf.d/laravel-worker.conf)
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/esbtp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/esbtp/storage/logs/worker.log
stopwaitsecs=3600

# Rechargement configuration
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Configuration Cron Jobs

```bash
# Édition crontab
crontab -e

# Ajout des tâches automatisées
# Scheduler Laravel (toutes les minutes)
* * * * * cd /var/www/esbtp && php artisan schedule:run >> /dev/null 2>&1

# Nettoyage logs (quotidien à 01:00)
0 1 * * * cd /var/www/esbtp && php artisan log:clear --days=30

# Optimisation cache (quotidien à 02:00)
0 2 * * * cd /var/www/esbtp && php artisan cache:clear && php artisan config:cache

# Calcul KPIs (quotidien à 06:00)
0 6 * * * cd /var/www/esbtp && php artisan comptabilite:calculate-kpis

# Backup base de données (quotidien à 03:00)
0 3 * * * cd /var/www/esbtp && php artisan backup:run --only-db

# Nettoyage sessions expirées (hebdomadaire)
0 3 * * 0 cd /var/www/esbtp && php artisan session:gc
```

---

## 📊 MONITORING ET PERFORMANCE

### Dashboard Monitoring

Accès : `/admin/monitoring` (permissions SuperAdmin requises)

**Métriques Surveillées :**

-   Temps de réponse des pages (objectif < 2s)
-   Utilisation mémoire (alerte > 128MB)
-   Taux de cache hit (objectif > 85%)
-   Nombre de requêtes SQL (alerte > 15 par page)
-   État des workers queue
-   Espace disque disponible

### Surveillance en Temps Réel

```bash
# Monitoring logs en continu
tail -f storage/logs/comptabilite.log | grep ERROR
tail -f storage/logs/performance.log | grep SLOW_QUERY

# État des workers
php artisan queue:monitor
php artisan horizon:status  # Si Horizon installé

# Statistiques cache Redis
redis-cli info stats
redis-cli --latency-history -i 1

# Performance base de données
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW ENGINE INNODB STATUS\G" | grep -A 10 "LATEST DETECTED DEADLOCK"
```

### Alertes Automatiques

```php
// Configuration alertes (config/comptabilite.php)
'monitoring' => [
    'alerts' => [
        'slow_response' => [
            'threshold' => 2000,  // 2 secondes
            'notify' => ['admin@esbtp.com'],
        ],
        'high_memory' => [
            'threshold' => 134217728,  // 128MB
            'notify' => ['dev@esbtp.com'],
        ],
        'cache_miss_rate' => [
            'threshold' => 15,  // 15% miss rate
            'notify' => ['admin@esbtp.com'],
        ],
        'queue_backlog' => [
            'threshold' => 100,  // 100 jobs en attente
            'notify' => ['admin@esbtp.com'],
        ],
    ],
],
```

---

## 🔧 MAINTENANCE PRÉVENTIVE

### Checklist Maintenance Quotidienne

-   [ ] **Vérification logs erreurs** : `grep ERROR storage/logs/*.log`
-   [ ] **État workers queue** : `php artisan queue:monitor`
-   [ ] **Performance cache** : Taux hit Redis > 85%
-   [ ] **Espace disque** : < 80% utilisé
-   [ ] **Backup automatique** : Vérification exécution 03:00
-   [ ] **Métriques performance** : Dashboard < 2s
-   [ ] **État services** : MySQL, Redis, Apache/Nginx actifs

### Checklist Maintenance Hebdomadaire

-   [ ] **Optimisation base de données** : `php artisan db:optimize`
-   [ ] **Nettoyage cache** : Invalidation complète si nécessaire
-   [ ] **Analyse logs performance** : Identification requêtes lentes
-   [ ] **Mise à jour dépendances** : `composer update` (environnement test)
-   [ ] **Tests automatisés** : Exécution suite complète
-   [ ] **Vérification backups** : Test restauration échantillon
-   [ ] **Review métriques** : Analyse tendances semaine

### Checklist Maintenance Mensuelle

-   [ ] **Optimisation index BDD** : `ANALYZE TABLE` + `OPTIMIZE TABLE`
-   [ ] **Archivage données** : Anciennes années universitaires
-   [ ] **Audit sécurité** : Review permissions + logs accès
-   [ ] **Mise à jour système** : Patches sécurité OS
-   [ ] **Formation utilisateurs** : Sessions de mise à niveau
-   [ ] **Documentation** : Mise à jour procédures
-   [ ] **Plan de continuité** : Test procédures de reprise

### Scripts Maintenance Automatiques

```bash
#!/bin/bash
# Script: maintenance-quotidienne.sh

echo "=== MAINTENANCE QUOTIDIENNE ESBTP $(date) ==="

# Nettoyage logs
echo "Nettoyage des logs..."
php artisan log:clear --days=30

# Optimisation cache
echo "Optimisation cache..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Calcul KPIs
echo "Calcul des KPIs..."
php artisan comptabilite:calculate-kpis

# Vérification état système
echo "Vérification état système..."
systemctl status mysql
systemctl status redis
systemctl status nginx

# Vérification espace disque
echo "Espace disque:"
df -h

# Vérification workers
echo "État workers queue:"
php artisan queue:monitor

echo "=== MAINTENANCE TERMINÉE ==="
```

---

## 💾 SAUVEGARDE ET RESTAURATION

### Stratégie de Sauvegarde

**Sauvegarde Quotidienne :**

-   Base de données complète (03:00)
-   Fichiers storage/ (04:00)
-   Configuration .env (04:30)

**Sauvegarde Hebdomadaire :**

-   Code source complet (dimanche 02:00)
-   Logs d'activité (dimanche 03:00)

**Sauvegarde Mensuelle :**

-   Archivage hors site (1er du mois)
-   Test de restauration (2ème dimanche)

### Configuration Backup

```bash
# Installation Laravel Backup
composer require spatie/laravel-backup

# Publication configuration
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Configuration dans config/backup.php
'backup' => [
    'name' => 'esbtp_backup',
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
                base_path('storage/logs'),
            ],
        ],
        'databases' => [
            'mysql',
        ],
    ],
    'destination' => [
        'filename_prefix' => 'esbtp_',
        'disks' => [
            'local',
            's3',  // Sauvegarde externe
        ],
    ],
]
```

### Procédures de Restauration

```bash
# 1. Restauration base de données
mysql -u root -p esbtp_database < backup_2024-01-15.sql

# 2. Restauration fichiers
tar -xzf esbtp_files_2024-01-15.tar.gz -C /var/www/

# 3. Restauration permissions
chown -R www-data:www-data /var/www/esbtp/
chmod -R 755 /var/www/esbtp/storage/

# 4. Clear cache après restauration
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 5. Vérification intégrité
php artisan migrate:status
php artisan config:cache
```

### Test de Restauration

```bash
#!/bin/bash
# Script: test-restauration.sh

# Création environnement test
cp .env .env.backup
cp .env.test .env

# Restauration backup
mysql -u test_user -p test_esbtp_db < latest_backup.sql

# Tests automatisés
php artisan test --testsuite=Critical

# Validation données
php artisan comptabilite:verify-data

# Nettoyage
cp .env.backup .env
```

---

## 🚨 DÉPANNAGE COURANT

### Problèmes Fréquents et Solutions

#### 1. Dashboard Lent (> 5s)

**Diagnostic :**

```bash
# Vérification cache Redis
redis-cli ping
redis-cli info stats | grep keyspace_hits

# Analyse requêtes lentes
php artisan telescope:clear
# Accéder à /telescope après reproduced le problème
```

**Solutions :**

```bash
# Optimisation cache
php artisan cache:clear
php artisan config:cache

# Rebuild index BDD
mysql -e "ANALYZE TABLE esbtp_paiements, esbtp_depenses;"

# Restart workers si nécessaire
sudo supervisorctl restart laravel-worker:*
```

#### 2. Erreurs Workflow Approbation

**Logs à vérifier :**

```bash
grep "WorkflowService" storage/logs/comptabilite.log
grep "approve\|reject" storage/logs/comptabilite.log
```

**Solutions :**

```bash
# Reset workflow bloqué
php artisan comptabilite:reset-workflow --id=123

# Vérification permissions
php artisan permission:check user@email.com comptabilite.bons.approve
```

#### 3. Analytics Prédictifs Indisponibles

**Diagnostic :**

```bash
# Vérification service
php artisan service:status AnalyticsPredictifService

# Test cache analytics
redis-cli keys "*analytics*"
```

**Solutions :**

```bash
# Régénération cache analytics
php artisan comptabilite:regenerate-analytics

# Reset données corrompues
php artisan cache:forget cash_flow_projection_*
```

#### 4. Notifications Non Envoyées

**Vérification configuration :**

```bash
# Test email
php artisan mail:test admin@esbtp.com

# État queue notifications
php artisan queue:failed
```

**Solutions :**

```bash
# Retry jobs échoués
php artisan queue:retry all

# Clear queue si bloquée
php artisan queue:clear redis
```

#### 5. Exports PDF Échouent

**Diagnostic :**

```bash
# Vérification permissions
ls -la storage/app/pdf/
df -h  # Vérification espace disque
```

**Solutions :**

```bash
# Fix permissions
chmod -R 755 storage/app/
chown -R www-data:www-data storage/

# Test génération PDF
php artisan pdf:test
```

---

## 🔒 SÉCURITÉ ET AUDIT

### Configuration Sécurisée

```php
// config/session.php
'lifetime' => 120,           // 2 heures max
'encrypt' => true,           // Chiffrement session
'http_only' => true,         // Cookies HTTP uniquement
'same_site' => 'lax',        // Protection CSRF

// config/cors.php
'allowed_origins' => ['https://esbtp.com'],
'allowed_headers' => ['Content-Type', 'Authorization'],
'max_age' => 0,
```

### Audit Trail et Logs

```bash
# Surveillance connexions suspectes
grep "login" storage/logs/auth.log | grep "failed"

# Analyse actions sensibles
grep "approve\|delete\|export" storage/logs/audit.log

# Monitoring tentatives intrusion
fail2ban-client status laravel-auth
```

### Checklist Sécurité Mensuelle

-   [ ] **Audit permissions** : Review rôles et droits
-   [ ] **Analyse logs** : Connexions suspectes
-   [ ] **Mise à jour sécurité** : Patches Laravel/PHP
-   [ ] **Test vulnérabilités** : Scan automatique
-   [ ] **Backup chiffré** : Vérification encryption
-   [ ] **Rotation mots de passe** : Comptes service
-   [ ] **Certificats SSL** : Expiration dans 30 jours

### Procédure Incident Sécurité

1. **Isolation** : Déconnexion utilisateur suspect
2. **Investigation** : Analyse logs détaillée
3. **Containment** : Blocage IP si nécessaire
4. **Documentation** : Rapport incident détaillé
5. **Correction** : Patch vulnérabilité
6. **Communication** : Information équipes

---

## 📞 SUPPORT ET ESCALADE

### Niveaux de Support

**Niveau 1 - Incidents Mineurs (< 4h)**

-   Dashboard lent temporairement
-   Erreurs sporadiques export PDF
-   Questions utilisateurs formation

**Niveau 2 - Incidents Modérés (< 2h)**

-   Service indisponible partiellement
-   Erreurs workflow bloquantes
-   Performance dégradée significativement

**Niveau 3 - Incidents Critiques (< 30min)**

-   Module comptabilité totalement indisponible
-   Perte de données
-   Faille sécurité identifiée

### Contacts d'Escalade

```
Niveau 1: helpdesk@esbtp.com
Niveau 2: admin@esbtp.com / +225 XX XX XX XX
Niveau 3: emergency@esbtp.com / +225 XX XX XX XX
```

### Procédure d'Escalade

1. **Documentation** : Description détaillée problème
2. **Classification** : Niveau urgence + impact
3. **Actions immédiates** : Mesures temporaires
4. **Communication** : Information stakeholders
5. **Résolution** : Plan d'action détaillé
6. **Post-mortem** : Analyse et prévention

---

## 📈 FORMATION ET CERTIFICATION

### Programme Formation (4h)

**Session 1 (2h) - Configuration et Administration**

-   Installation et configuration système
-   Gestion utilisateurs et permissions
-   Configuration cache et performance
-   Monitoring et alertes

**Session 2 (2h) - Maintenance et Dépannage**

-   Maintenance préventive
-   Procédures sauvegarde/restauration
-   Dépannage incidents courants
-   Sécurité et audit

### Certification Administrateur

**Prérequis :**

-   Formation 4h complétée
-   3 mois expérience système ESBTP
-   Connaissances Laravel/MySQL/Redis

**Évaluation :**

-   Test théorique (60 questions)
-   Exercice pratique (configuration complète)
-   Simulation incident (dépannage temps réel)

**Recertification :**

-   Tous les 12 mois
-   Formation continue 8h/an

---

## 📚 RESSOURCES SUPPLÉMENTAIRES

### Documentation Technique

-   [Architecture système détaillée](./DOCUMENTATION_TECHNIQUE_COMPTABILITE_ESBTP.md)
-   [Guide développeur API](./API_DEVELOPER_GUIDE.md)
-   [Procédures d'urgence](./EMERGENCY_PROCEDURES.md)

### Outils Recommandés

-   **Monitoring** : Grafana + Prometheus
-   **Logs** : ELK Stack (Elasticsearch, Logstash, Kibana)
-   **Backup** : Laravel Backup + AWS S3
-   **Performance** : Laravel Telescope + Debugbar

### Formation Continue

-   Laravel Certification
-   Redis Administration
-   MySQL Performance Tuning
-   Cybersécurité avancée

---

**Guide mis à jour le :** {{ date('d/m/Y H:i') }}  
**Version :** 2.0  
**Contact support :** admin@esbtp.com
