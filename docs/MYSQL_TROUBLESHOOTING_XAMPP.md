# Guide de dépannage MySQL/MariaDB XAMPP

## Problème : MySQL démarre puis s'arrête immédiatement

**Date de résolution :** 3 octobre 2025
**Environnement :** Windows, XAMPP, MariaDB 10.4.32

---

## Symptômes

- MySQL démarre dans XAMPP Control Panel puis s'arrête quelques secondes après
- Le fichier `mysql_error.log` se termine par "Server socket created on IP" sans erreur visible
- Aucun message d'erreur clair dans l'interface XAMPP

---

## Diagnostic : Étapes à suivre

### 1. Vérifier si le port 3306 est utilisé

```powershell
netstat -ano | findstr :3306
```

**Résultat attendu :** Aucune ligne (le port est libre)

Si le port est occupé :
- Identifiez le processus : `tasklist | findstr <PID>`
- Tuez le processus : `taskkill /F /PID <PID>`
- OU changez le port MySQL dans `C:\xampp\mysql\bin\my.ini` (ligne `port=3306` → `port=3307`)

### 2. Démarrer MySQL en mode console pour voir les erreurs

```powershell
cd C:\xampp\mysql\bin
.\mysqld.exe --console --standalone
```

**Important :** Cette commande affiche les vraies erreurs qui ne sont pas visibles dans `mysql_error.log`

---

## Erreurs courantes et solutions

### Erreur 1 : Conflit de taille ibdata1

**Message d'erreur :**
```
[ERROR] InnoDB: The Auto-extending innodb_system data file '.\ibdata1' is of a different size 640 pages than specified in the .cnf file: initial 768 pages
[ERROR] InnoDB: Plugin initialization aborted with error Generic error
[ERROR] Plugin 'InnoDB' registration as a STORAGE ENGINE failed.
```

**Cause :** Le fichier `ibdata1` a une taille différente de celle configurée dans `my.ini`

**Solution (SANS perte de données) :**

1. Ouvrez `C:\xampp\mysql\bin\my.ini`
2. Cherchez la ligne (environ ligne 157) :
   ```ini
   innodb_data_file_path=ibdata1:10M:autoextend
   ```
3. Remplacez par :
   ```ini
   innodb_data_file_path=ibdata1:5M:autoextend
   ```
4. Sauvegardez
5. Redémarrez MySQL via XAMPP

**Explication :** La taille initiale (5M au lieu de 10M) permet à MySQL d'accepter un fichier ibdata1 plus petit et de l'étendre automatiquement si nécessaire.

---

### Erreur 2 : Fichiers InnoDB corrompus

**Symptômes :** MySQL ne démarre pas après un arrêt brutal

**Solution :**

1. Arrêtez MySQL complètement
2. Sauvegardez vos bases de données :
   ```powershell
   cd C:\xampp\mysql\data
   # Copiez tous les dossiers de vos bases ailleurs
   ```
3. Renommez les fichiers système InnoDB :
   ```powershell
   Rename-Item ibdata1 ibdata1.bak
   Rename-Item ib_logfile0 ib_logfile0.bak
   Rename-Item ib_logfile1 ib_logfile1.bak
   ```
4. Relancez MySQL (il recréera les fichiers)

---

### Erreur 3 : Tables système MySQL manquantes

**Solution :**

Restaurez depuis le backup XAMPP :
```powershell
cd C:\xampp\mysql\data
Copy-Item C:\xampp\mysql\backup\mysql -Destination . -Recurse -Force
Copy-Item C:\xampp\mysql\backup\performance_schema -Destination . -Recurse -Force
```

---

### Erreur 4 : Problème de binding IPv4/IPv6

**Solution :**

1. Ouvrez `C:\xampp\mysql\bin\my.ini`
2. Cherchez les lignes :
   ```ini
   # bind-address="127.0.0.1"
   # bind-address = ::1
   ```
3. Décommentez la ligne IPv4 :
   ```ini
   bind-address=127.0.0.1
   # bind-address = ::1
   ```
4. Sauvegardez et redémarrez

---

## Checklist de dépannage complète

- [ ] Vérifier que le port 3306 est libre
- [ ] Lancer MySQL en mode console pour voir les erreurs réelles
- [ ] Vérifier la configuration `innodb_data_file_path` dans `my.ini`
- [ ] Vérifier que les dossiers `mysql` et `performance_schema` existent dans `data/`
- [ ] Vérifier le `bind-address` dans `my.ini`
- [ ] Vérifier les permissions antivirus/pare-feu Windows
- [ ] En dernier recours : renommer ibdata1 et les ib_logfile*

---

## Fichiers importants

- **Configuration :** `C:\xampp\mysql\bin\my.ini`
- **Données :** `C:\xampp\mysql\data\`
- **Logs :** `C:\xampp\mysql\data\mysql_error.log`
- **Backup :** `C:\xampp\mysql\backup\`

---

---

## Erreur 5 : Laravel dans WSL2 ne peut pas se connecter à XAMPP MySQL sur Windows

**Date de résolution :** 3 octobre 2025
**Environnement :** WSL2 (Ubuntu/Linux) + XAMPP sur Windows

### Symptômes

```
SQLSTATE[HY000] [2002] No such file or directory
```

Dans les logs Laravel, vous voyez cette erreur même si :
- MySQL est démarré dans XAMPP
- La base de données existe
- Les credentials sont corrects dans `.env`

### Cause

Quand Laravel dans WSL2 voit `DB_HOST=localhost`, il cherche un **socket Unix** (`/tmp/mysql.sock`) qui n'existe pas, car MySQL tourne sur Windows.

### Solution 1 : Utiliser l'IP au lieu de localhost

**Modifier `.env` :**

```env
# AVANT (ne fonctionne pas)
DB_HOST=localhost

# APRÈS (fonctionne)
DB_HOST=127.0.0.1
```

**Explication :** `127.0.0.1` force Laravel à utiliser TCP/IP au lieu d'un socket Unix.

### Solution 2 : Utiliser l'IP Windows depuis WSL2

Si Solution 1 ne fonctionne pas, utilisez l'IP de Windows vue depuis WSL2 :

1. **Dans PowerShell Windows :**
   ```powershell
   ipconfig
   ```
   Cherchez l'IP de l'adaptateur `vEthernet (WSL)` (ex: `172.18.96.1`)

2. **Modifier `.env` :**
   ```env
   DB_HOST=172.18.96.1
   DB_PORT=3306
   ```

3. **Configurer XAMPP pour accepter les connexions externes :**

   a. Ouvrez `C:\xampp\mysql\bin\my.ini`

   b. Cherchez la ligne `bind-address` et modifiez :
   ```ini
   # Commentez ou changez
   # bind-address=127.0.0.1

   # En
   bind-address=0.0.0.0
   ```

   c. Redémarrez MySQL dans XAMPP

4. **Autoriser dans le pare-feu Windows :**
   - Ouvrez "Pare-feu Windows Defender avec fonctions avancées de sécurité"
   - Nouvelle règle entrante → Port → TCP → 3306
   - Autoriser la connexion
   - Nom : "MySQL XAMPP pour WSL2"

5. **Créer un utilisateur MySQL pour WSL2 :**
   ```sql
   -- Dans phpMyAdmin ou console MySQL
   CREATE USER 'root'@'172.18.96.%' IDENTIFIED BY '';
   GRANT ALL PRIVILEGES ON *.* TO 'root'@'172.18.96.%';
   FLUSH PRIVILEGES;
   ```

### Solution 3 : Créer un alias permanent

Pour éviter de chercher l'IP à chaque redémarrage :

1. **Dans WSL2, éditez `/etc/hosts` :**
   ```bash
   sudo nano /etc/hosts
   ```

2. **Ajoutez cette ligne :**
   ```
   172.18.96.1    windows.host
   ```
   (Remplacez par votre IP Windows)

3. **Dans `.env` Laravel :**
   ```env
   DB_HOST=windows.host
   ```

### Vérification

Testez la connexion depuis WSL2 :

```bash
# Test de ping
ping 127.0.0.1

# Test de connexion MySQL
mysql -h 127.0.0.1 -u root -p
# OU
mysql -h 172.18.96.1 -u root -p
```

Si vous obtenez le prompt MySQL `mysql>`, la connexion fonctionne !

### Problème : Connection Refused malgré toutes les configurations

Si après avoir tout configuré correctement, vous obtenez toujours **"Connection refused (115)"**, le problème vient très probablement du **pare-feu Windows qui bloque malgré les règles**.

**Test de diagnostic :**

Dans PowerShell (Admin), désactivez TEMPORAIREMENT le pare-feu :
```powershell
Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled False
```

Testez la connexion depuis WSL2. Si ça fonctionne, réactivez le pare-feu :
```powershell
Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled True
```

**Solution définitive :**

1. **Supprimez toutes les règles MySQL existantes :**
   ```powershell
   Get-NetFirewallRule | Where-Object {$_.DisplayName -like "*MySQL*"} | Remove-NetFirewallRule
   ```

2. **Créez une règle très permissive :**
   ```powershell
   New-NetFirewallRule `
       -DisplayName "MySQL XAMPP - WSL2" `
       -Direction Inbound `
       -LocalPort 3306 `
       -Protocol TCP `
       -Action Allow `
       -Profile Domain,Public,Private `
       -Enabled True `
       -RemoteAddress Any
   ```

3. **Créez un utilisateur MySQL qui accepte toutes les connexions :**
   ```powershell
   cd C:\xampp\mysql\bin
   .\mysql.exe -u root -e "CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '';"
   .\mysql.exe -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%';"
   .\mysql.exe -u root -e "FLUSH PRIVILEGES;"
   ```

4. **Vérifiez que l'utilisateur existe :**
   ```powershell
   .\mysql.exe -u root -e "SELECT user, host FROM mysql.user WHERE user='root';"
   ```

   Vous devez voir 2 lignes :
   ```
   +------+-----------+
   | user | host      |
   +------+-----------+
   | root | localhost |
   | root | %         |  ← IMPORTANT
   +------+-----------+
   ```

5. **Testez depuis WSL2 :**
   ```bash
   # Obtenir l'IP Windows
   WINDOWS_IP=$(cat /etc/resolv.conf | grep nameserver | awk '{print $2}')

   # Tester la connexion
   mysql -h $WINDOWS_IP -u root -e "SHOW DATABASES;"
   ```

### Alternative : Installer MySQL dans WSL2

Si vous préférez, installez MySQL directement dans WSL2 :

```bash
sudo apt update
sudo apt install mysql-server
sudo service mysql start
```

Puis dans `.env` :
```env
DB_HOST=localhost
DB_PORT=3306
```

---

## Prévention

1. **Toujours arrêter proprement MySQL** avant de fermer XAMPP
2. **Faire des backups réguliers** du dossier `data/`
3. **Ne pas modifier `my.ini`** sans backup préalable
4. **Éviter les arrêts brutaux** de Windows pendant que MySQL tourne
5. **Pour WSL2 : Noter l'IP Windows** ou créer un alias dans `/etc/hosts`

---

*Dernière mise à jour : 3 octobre 2025*
