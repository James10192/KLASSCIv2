# ESBTP-yAKRO Documentation

## Corrections récentes

### Feature: Propagation automatique des enseignants pour toute la classe

**Date:** 4 octobre 2025
**Branche:** presentation

#### Problème résolu

Lors de la configuration des noms d'enseignants pour les bulletins, il fallait remplir les noms matière par matière **pour chaque étudiant** de la classe. Avec des classes de 30+ étudiants, cela devenait très fastidieux et répétitif.

#### Solution implémentée

Ajout d'une **checkbox "Appliquer à toute la classe"** sur la page d'édition des professeurs ([edit-professeurs.blade.php](resources/views/esbtp/bulletins/edit-professeurs.blade.php)) qui permet de **copier automatiquement** les noms des enseignants configurés vers tous les autres bulletins de la même classe (même période, même année universitaire).

#### Fonctionnement

1. **Interface** : Checkbox avec switch moderne placée juste avant les boutons d'action
2. **Backend** : Logique dans [saveProfesseurs()](app/Http/Controllers/ESBTPBulletinController.php:5272-5290)
   - Si checkbox cochée : récupère tous les bulletins de la classe (même `classe_id`, `periode`, `annee_universitaire_id`)
   - Copie le JSON `professeurs` vers chaque bulletin
   - Met à jour `updated_by` avec l'utilisateur actuel
3. **Feedback** : Message indiquant combien de bulletins ont été mis à jour
   - Ex: "Les noms des professeurs ont été enregistrés avec succès. Ces enseignants ont également été appliqués à 29 autre(s) bulletin(s) de la classe."

#### Fichiers modifiés

- [resources/views/esbtp/bulletins/edit-professeurs.blade.php:283-303](resources/views/esbtp/bulletins/edit-professeurs.blade.php:283) - Ajout checkbox propagation
- [app/Http/Controllers/ESBTPBulletinController.php:5236](app/Http/Controllers/ESBTPBulletinController.php:5236) - Validation `appliquer_a_classe`
- [app/Http/Controllers/ESBTPBulletinController.php:5270-5290](app/Http/Controllers/ESBTPBulletinController.php:5270) - Logique de propagation
- [app/Http/Controllers/ESBTPBulletinController.php:5304-5308](app/Http/Controllers/ESBTPBulletinController.php:5304) - Message de feedback dynamique

#### Avantages

✅ **Gain de temps massif** : Configuration en une seule fois pour toute la classe
✅ **Cohérence garantie** : Mêmes enseignants sur tous les bulletins de la classe
✅ **Optionnel** : L'utilisateur choisit s'il veut propager ou non
✅ **Transparent** : Feedback clair sur le nombre de bulletins mis à jour
✅ **Audit trail** : Chaque mise à jour enregistre l'utilisateur (`updated_by`)

---

### Fix: Message d'erreur explicite lors de la génération de bulletin

**Date:** 4 octobre 2025
**Branche:** presentation

#### Problème résolu

Quand l'utilisateur enregistrait les absences et générait le bulletin, si une erreur survenait (ex: "Aucune matière trouvée"), le message n'était pas explicite et ne confirmait pas que les absences avaient bien été sauvegardées.

#### Solution

Modification des messages d'erreur dans [genererPDFParParamsUnified()](app/Http/Controllers/ESBTPBulletinController.php:4740-4758) pour :
1. **Confirmer** que les absences sont bien enregistrées
2. **Expliquer** pourquoi le bulletin ne peut pas être généré
3. **Rediriger** vers la page des résultats de l'étudiant (au lieu d'un simple `back()`)
4. **Indiquer** quelle action entreprendre ("Modifier les moyennes")

**Nouveau message** :
> "Les absences ont été enregistrées avec succès. Cependant, le bulletin ne peut pas être généré car aucune matière n'a été trouvée pour cette classe. Veuillez d'abord "Modifier les moyennes" pour configurer les notes."

#### Fichiers modifiés

- [app/Http/Controllers/ESBTPBulletinController.php:4740-4746](app/Http/Controllers/ESBTPBulletinController.php:4740) - Message cas "Aucune matière"
- [app/Http/Controllers/ESBTPBulletinController.php:4753-4758](app/Http/Controllers/ESBTPBulletinController.php:4753) - Message cas "Erreur récupération"

---

### Feature: Édition manuelle des absences pour les bulletins

**Date:** 4 octobre 2025
**Branche:** presentation

#### Fonctionnalités ajoutées

Implémentation d'un système d'édition manuelle des absences pour les bulletins, similaire au système de modification des moyennes.

**Flux de génération de bulletin mis à jour:**
1. Configuration des matières
2. Vérification des moyennes
3. Édition des professeurs
4. **[NOUVEAU]** Édition des absences (optionnel)
5. Génération du PDF

#### 1. Système automatique conservé

- Le système de calcul automatique des absences via le module d'émargement reste actif
- Les absences sont calculées automatiquement depuis `calculerAbsencesDetailes()`
- L'édition manuelle est **optionnelle** et vient en complément

#### 2. Interface d'édition des absences

**Page:** [resources/views/esbtp/bulletins/edit-absences.blade.php](resources/views/esbtp/bulletins/edit-absences.blade.php)

**Caractéristiques:**
- Design moderne similaire à `moyennes-preview.blade.php`
- KPI cards affichant: Étudiant, Classe, Période, Total absences
- Vue comparative: Absences calculées automatiquement vs Absences à enregistrer
- Badge indiquant la source des données (Auto/Manuel)
- Calcul en temps réel du total et de la note d'assiduité via JavaScript
- Affichage du barème de calcul de la note d'assiduité

**Champs modifiables:**
- Absences justifiées (heures, step 0.5)
- Absences non justifiées (heures, step 0.5)
- Total absences (calculé automatiquement)
- Note d'assiduité (affichée, recalculée automatiquement)

**Actions disponibles:**
- Enregistrer (reste sur la page)
- Enregistrer et retour (retourne aux résultats étudiant)
- Enregistrer et générer PDF (enregistre puis génère le bulletin)

#### 3. Backend

**Controller:** [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php)

**Nouvelles méthodes:**
- `editAbsences()` (ligne 5763) - Affiche la page d'édition
  - Récupère ou crée le bulletin
  - Calcule les absences automatiques via `calculerAbsencesDetailes()`
  - Initialise avec valeurs auto si pas de données manuelles
  - Détermine la source (auto/manuelle)
  - Calcule la note d'assiduité

- `saveAbsences()` (ligne 5870) - Sauvegarde les modifications
  - Valide les données (absences_justifiees, absences_non_justifiees)
  - Calcule `total_absences` = justifiées + non justifiées
  - Calcule `note_assiduite` via `calculerNoteAssiduite()`
  - Gère 3 actions: edit, save_and_back, generate
  - Logging complet des opérations

#### 4. Routes

**Fichier:** [routes/web.php](routes/web.php#L1630-L1631)

```php
Route::get('/esbtp-special/bulletins/edit-absences', [ESBTPBulletinController::class, 'editAbsences'])
    ->name('esbtp.bulletins.edit-absences');
Route::post('/esbtp-special/bulletins/save-absences', [ESBTPBulletinController::class, 'saveAbsences'])
    ->name('esbtp.bulletins.save-absences');
```

#### 5. Bouton d'accès

**Fichier:** [resources/views/components/student-results/action-buttons.blade.php](resources/views/components/student-results/action-buttons.blade.php#L60-L63)

- Visible uniquement pour les `superAdmin`
- Placé après "Éditer professeurs"
- Icône: `fas fa-user-clock`
- Style: `btn-acasi warning`

**Guide mis à jour:**
- Étape 4 ajoutée: "Éditer les absences (optionnel)"
- Indique que c'est facultatif

#### 6. Barème de calcul de la note d'assiduité

La note d'assiduité est calculée selon les absences **non justifiées** uniquement:

| Absences non justifiées | Note d'assiduité |
|------------------------|------------------|
| 0                      | +0.13 point      |
| 1                      | 0 point          |
| 2                      | -0.13 point      |
| 3-4                    | -0.39 point      |
| 5+                     | -0.50 point      |

**Implémentation:** [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php#L4060-L4096)

#### 7. Stockage des données

**Table:** `esbtp_bulletins`

**Champs concernés:**
- `absences_justifiees` (float) - Heures d'absences justifiées
- `absences_non_justifiees` (float) - Heures d'absences non justifiées
- `total_absences` (float) - Total des heures d'absences
- `note_assiduite` (float, nullable) - Note d'assiduité calculée
- `details_absences` (json, nullable) - Détails au format JSON

**Migration:** [database/migrations/2025_04_08_091936_add_absences_fields_to_esbtp_bulletins_table.php](database/migrations/2025_04_08_091936_add_absences_fields_to_esbtp_bulletins_table.php)

#### Fichiers modifiés

- [routes/web.php](routes/web.php#L1630-L1631) - Ajout des routes
- [app/Http/Controllers/ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php#L5763-L5991) - Méthodes editAbsences() et saveAbsences()
- [resources/views/components/student-results/action-buttons.blade.php](resources/views/components/student-results/action-buttons.blade.php) - Bouton et guide

#### Fichiers créés

- [resources/views/esbtp/bulletins/edit-absences.blade.php](resources/views/esbtp/bulletins/edit-absences.blade.php) - Interface d'édition

#### Tests recommandés

- [ ] Tester l'affichage des absences calculées automatiquement
- [ ] Tester la modification manuelle des absences
- [ ] Vérifier le calcul en temps réel du total et de la note
- [ ] Tester les 3 boutons d'action (enregistrer, retour, générer)
- [ ] Vérifier que les valeurs sont bien sauvegardées dans le bulletin
- [ ] Générer un PDF et vérifier que les absences apparaissent correctement
- [ ] Tester le badge Auto/Manuel selon la source des données

#### Caractéristiques techniques

- **Permissions:** Accessible uniquement aux `superAdmin`
- **Validation:** Valeurs numériques ≥ 0, step 0.5h
- **Calcul JS:** Mise à jour en temps réel sans rechargement
- **Logging:** Tous les changements sont loggés
- **Transaction-safe:** Utilisation de try-catch pour gestion d'erreurs
- **Flexibilité:** Édition optionnelle, n'impacte pas le flux de base

---

## Fix: 404 error when generating bulletin from edit-absences page

**Date:** 4 octobre 2025
**Branche:** presentation

### Problème résolu

Lorsque l'utilisateur cliquait sur "Enregistrer et générer bulletin" depuis la page d'édition des absences, il obtenait une erreur 404 avec l'URL `http://localhost:8000/esbtp/bulletins/generate?etudiant_id=1`.

### Cause racine

La méthode `saveAbsences()` dans [ESBTPBulletinController.php](app/Http/Controllers/ESBTPBulletinController.php:5937) redirigait vers la route `esbtp.bulletins.generate` qui pointe vers une méthode `generateBulletin()` qui n'est qu'un stub avec des commentaires placeholder (`// ... existing code ...`).

### Solution

Changement de la route de redirection de `esbtp.bulletins.generate` vers `esbtp.bulletins.pdf-params` qui est la vraie route de génération de PDF définie à la ligne 1596 de [routes/web.php](routes/web.php:1596).

**Avant:**
```php
return redirect()->route('esbtp.bulletins.generate', [
    'etudiant_id' => $etudiant_id
]);
```

**Après:**
```php
return redirect()->route('esbtp.bulletins.pdf-params', [
    'bulletin' => $etudiant_id,
    'classe_id' => $classe_id,
    'periode' => $periode,
    'annee_universitaire_id' => $annee_universitaire_id
]);
```

### Fichiers modifiés

- [app/Http/Controllers/ESBTPBulletinController.php:5937](app/Http/Controllers/ESBTPBulletinController.php:5937) - Correction de la route de redirection

### Notes

La route `esbtp.bulletins.pdf-params` est utilisée partout ailleurs dans l'application (notamment dans [action-buttons.blade.php:74](resources/views/components/student-results/action-buttons.blade.php:74)) pour générer les bulletins PDF.

---

### Fix: Résolution de la fonctionnalité de sélection rapide d'enseignant

**Date:** 21 septembre 2025
**Branche:** presentation

#### Problèmes résolus

1. **Erreur de validation "La valeur sélectionnée pour periode est invalide"**
   - **Localisation:** `app/Http/Controllers/ESBTPBulletinController.php:2517`
   - **Cause:** La méthode `resultatEtudiant` acceptait seulement les valeurs '1,2' mais recevait 'semestre2' lors de la redirection
   - **Solution:** Mise à jour de la validation pour accepter les formats: '1,2,semestre1,semestre2'
   - **Code ajouté:** Logique de conversion complète entre les formats entiers et string

2. **Fonctionnalité de sélection rapide d'enseignant non fonctionnelle**
   - **Localisation:** `resources/views/esbtp/bulletins/edit-professeurs.blade.php`
   - **Cause:** Erreur JavaScript "selectEnseignant is not defined" due aux attributs `onchange`
   - **Solution:** Remplacement par des `addEventListener` et placement direct du script dans le HTML
   - **Résultat:** La sélection d'un enseignant dans le dropdown remplit automatiquement l'input correspondant

3. **Interface utilisateur peu moderne**
   - **Problème:** Design des inputs/selects et boutons trop près des bords
   - **Solution:** Refonte complète avec design moderne basé sur des cartes
   - **Améliorations:**
     - Cartes modernes avec hover effects
     - Meilleur espacement et placement des boutons
     - Icônes et couleurs améliorées
     - Responsive design

#### Fichiers modifiés

- `app/Http/Controllers/ESBTPBulletinController.php`
- `app/Http/Controllers/ESBTPEvaluationController.php`
- `resources/views/components/student-results/results-overview-card.blade.php`
- `resources/views/esbtp/bulletins/edit-professeurs.blade.php`

#### Fonctionnalités ajoutées

- Support des formats de période multiples (1, 2, semestre1, semestre2)
- Logging détaillé pour le débogage des erreurs de validation
- Interface moderne avec cartes pour l'assignation des enseignants
- Sélection rapide d'enseignant fonctionnelle avec animation
- Gestion robuste des événements JavaScript

#### Tests recommandés

- [ ] Tester la sélection rapide d'enseignant sur différentes matières
- [ ] Vérifier que la validation des périodes fonctionne correctement
- [ ] Tester l'interface sur mobile (responsive design)
- [ ] Vérifier que les bulletins PDF se génèrent correctement

#### Commandes de test

```bash
# Tests de base
php artisan test

# Vérification du linting (si configuré)
npm run lint

# Build des assets (si nécessaire)
npm run build
```

---

## Structure des composants

### Teacher Assignment Interface

Le composant d'assignation des enseignants utilise maintenant une structure moderne :

```html
<div class="subject-card">
    <div class="subject-header">
        <div class="subject-icon"><!-- Icône matière --></div>
        <div class="subject-info"><!-- Nom et code matière --></div>
    </div>
    <div class="quick-select-section"><!-- Sélection rapide --></div>
    <div class="teacher-input-section"><!-- Input enseignant --></div>
</div>
```

### JavaScript Events

Les événements JavaScript sont maintenant gérés via `addEventListener` :

```javascript
select.addEventListener('change', function() {
    // Logique de transfert de valeur vers l'input
    const targetInput = parentCard.querySelector('.form-control-modern');
    if (targetInput) {
        targetInput.value = this.value;
        // Animation et reset du select
    }
});
```

---

## Fix: Implémentation des actions groupées sur les paiements

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

**UX pénible sur la gestion des paiements**
- Les paiements en attente devaient être validés/rejetés un par un
- Avec beaucoup de paiements répartis sur plusieurs pages de pagination, le processus était fastidieux
- Aucune possibilité de traiter plusieurs paiements simultanément

### Solution implémentée

Implémentation complète d'un système d'actions groupées (bulk actions) pour les paiements :

1. **Interface utilisateur**
   - Checkboxes de sélection pour chaque paiement en attente (visible uniquement pour superAdmin)
   - Checkbox "Tout sélectionner" dans l'en-tête du tableau
   - Barre d'actions flottante en bas de l'écran affichant le nombre de paiements sélectionnés
   - Boutons pour valider ou rejeter la sélection
   - Modal de confirmation pour le rejet groupé avec champ "motif de rejet"

2. **Backend**
   - Nouvelle méthode `bulkValider()` dans `ESBTPPaiementController`
   - Nouvelle méthode `bulkRejeter()` dans `ESBTPPaiementController`
   - Support des transactions DB pour garantir l'intégrité des données
   - Gestion intelligente des reliquats lors de la validation
   - Messages de feedback détaillés (succès/erreurs/déjà traités)

3. **Routes**
   - `POST /paiements/bulk-valider`
   - `POST /paiements/bulk-rejeter`

### Fichiers modifiés

- [resources/views/esbtp/paiements/index.blade.php](resources/views/esbtp/paiements/index.blade.php) - Interface avec checkboxes et JavaScript
- [app/Http/Controllers/ESBTPPaiementController.php](app/Http/Controllers/ESBTPPaiementController.php:1666) - Méthodes `bulkValider()` et `bulkRejeter()`
- [routes/web.php](routes/web.php:691) - Routes pour actions groupées

### Caractéristiques techniques

- Sélection limitée aux paiements en statut `en_attente`
- Vérification des permissions (superAdmin uniquement)
- Compteurs en temps réel du nombre de paiements sélectionnés
- Animation smooth de la barre d'actions
- Validation côté serveur des IDs de paiements
- Gestion des erreurs avec rollback de transaction
- Logging des erreurs pour le débogage
- Mise à jour automatique des reliquats lors de la validation

---

## Fix: Migration base de données XAMPP Windows vers MariaDB WSL2

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

**Impossible de connecter Laravel (WSL2) à MySQL XAMPP (Windows)**

Erreur rencontrée :
```
SQLSTATE[HY000] [2002] No such file or directory
```

### Cause racine

1. Laravel dans WSL2 avec `DB_HOST=localhost` cherchait un socket Unix (`/tmp/mysql.sock`) inexistant
2. MySQL XAMPP configuré sur Windows avec `bind-address=127.0.0.1` n'acceptait que les connexions locales Windows
3. Pare-feu Windows bloquait les connexions depuis WSL2 malgré les règles configurées

### Solution appliquée

**Migration vers MariaDB dans WSL2** pour éviter les complications de connexion cross-système :

1. Installation et configuration de MariaDB dans WSL2
2. Création de la base de données `esbtp-abidjan-db`
3. Configuration des utilisateurs MySQL
4. Mise à jour du fichier `.env` avec `DB_HOST=localhost`

### Scripts créés

- [setup-mariadb-wsl2.sh](setup-mariadb-wsl2.sh) - Script d'installation automatique MariaDB WSL2
- [test-mysql-connection.sh](test-mysql-connection.sh) - Script de diagnostic connexion MySQL

### Documentation mise à jour

- [docs/MYSQL_TROUBLESHOOTING_XAMPP.md](docs/MYSQL_TROUBLESHOOTING_XAMPP.md) - Section "Erreur 5: Laravel dans WSL2 ne peut pas se connecter à XAMPP MySQL sur Windows"

Trois solutions documentées :
1. Utiliser `DB_HOST=127.0.0.1` au lieu de `localhost`
2. Utiliser l'IP Windows depuis WSL2 avec configuration pare-feu
3. **Installer MariaDB directement dans WSL2** (solution choisie)

---

## Fix: Message "compiled views cleared successfully" sur toutes les pages

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

Message texte "compiled views cleared successfully" apparaissant sur toutes les pages de l'application, corrompant :
- L'affichage des pages HTML
- Les réponses AJAX JSON
- Le chargement des images (404)

### Cause racine

Fichier [public/index.php](public/index.php:15) contenait un code de debug :

```php
// Force clear all caches on each request during development
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    if (file_exists(__DIR__.'/../artisan')) {
        passthru('php ../artisan view:clear 2>/dev/null');
    }
}
```

Ce code exécutait `view:clear` à **chaque requête HTTP**, injectant le message de succès dans toutes les réponses.

### Solution

Suppression complète du bloc de code auto-cache-clearing (lignes 10-17) de `public/index.php`.

---

## Fix: Syntaxe Blade dans fichier JavaScript

**Date:** 3 octobre 2025
**Branche:** presentation

### Problème résolu

Le fichier [public/js/navbar-diagnostics.js](public/js/navbar-diagnostics.js) contenait du code Blade (`{{ route() }}`) qui ne compile pas dans les fichiers .js.

### Solution

Remplacement par lecture des routes depuis les attributs `data-route` du DOM, avec fallback vers chemins hardcodés :

```javascript
const notifBtn = document.getElementById('notificationsDropdown');
const msgBtn = document.getElementById('messagesDropdown');
const actionBtn = document.getElementById('quickActionsDropdown');

if (notifBtn) {
    console.log('🛣️ Route notifications:', notifBtn.dataset.route || '/navbar/notifications');
}
```

### Création de répertoires manquants

Création du répertoire pour les photos de profil :
```bash
mkdir -p storage/app/public/profile-photos
```

---

## Feature: Système de notifications et rappels automatiques pour inscriptions et paiements

**Date:** 4 octobre 2025
**Branche:** presentation

### Fonctionnalités ajoutées

Implémentation complète d'un système de notifications en temps réel et de rappels automatiques pour les inscriptions et paiements en attente.

#### 1. Notifications en temps réel

**Notifications d'inscription :**
- Envoyées à tous les `superAdmin`, `coordinateur` et `secretaire` (sauf celui qui a créé l'inscription)
- Contiennent : nom étudiant, classe, statut inscription, étape workflow, état du paiement
- Lien direct vers [inscriptions.show](app/Http/Controllers/ESBTPInscriptionController.php:485)
- Icônes FontAwesome pour meilleure lisibilité

**Notifications de paiement :**
- **Création** : Notifie les `superAdmin` quand un paiement en attente est créé
- **Validation** : Notifie l'étudiant concerné avec les détails (référence, numéro de reçu)
- **Rejet** : Notifie l'étudiant avec le motif du rejet

#### 2. Système de rappels automatiques

**Table de suivi `notification_reminders` :**
- Stocke l'état des rappels pour chaque inscription/paiement
- Champs : `remindable_type`, `remindable_id`, `reminder_count`, `last_reminder_sent_at`, `next_reminder_at`, `is_active`
- Désactivation automatique après validation/rejet

**Paramètres configurables (via interface) :**
- Délai avant premier rappel (jours)
- Fréquence entre rappels (jours)
- Nombre maximum de rappels (0 = illimité)
- Activation/désactivation par type (inscriptions/paiements)

**Valeurs par défaut :**
- Inscriptions : 1er rappel après 3j, puis tous les 2j, max 5 rappels
- Paiements : 1er rappel après 2j, puis tous les 1j, max 7 rappels

#### 3. Interface de configuration

**Nouvelle page settings avec onglets :**
- Onglet "Général" : Informations établissement (inchangé)
- Onglet "Configuration PDF" : Paramètres bulletins (inchangé)
- **Nouveau** - Onglet "Notifications et Rappels" :
  - Section rappels inscriptions
  - Section rappels paiements
  - Section test et diagnostics (bouton de test en mode simulation)

**Route de test :** `POST /esbtp/settings/test-reminders`

### Fichiers créés

#### Modèles et migrations
- [database/migrations/2025_10_04_092055_create_notification_reminders_table.php](database/migrations/2025_10_04_092055_create_notification_reminders_table.php)
- [app/Models/NotificationReminder.php](app/Models/NotificationReminder.php)

#### Commande et scheduler
- [app/Console/Commands/SendInscriptionPaiementReminders.php](app/Console/Commands/SendInscriptionPaiementReminders.php)
- [app/Console/Kernel.php](app/Console/Kernel.php:102) - Ajout de la tâche planifiée quotidienne à 8h00

#### Seeder
- [database/seeders/ReminderSettingsSeeder.php](database/seeders/ReminderSettingsSeeder.php)

### Fichiers modifiés

#### Services
- [app/Services/NotificationService.php](app/Services/NotificationService.php:1847) - 6 nouvelles méthodes :
  - `notifyInscriptionCreated()` - Notification création inscription
  - `notifyPaiementCreated()` - Notification création paiement
  - `notifyPaiementValide()` - Notification validation paiement
  - `notifyPaiementRejete()` - Notification rejet paiement
  - `sendInscriptionReminder()` - Envoi rappel inscription
  - `sendPaiementReminder()` - Envoi rappel paiement

#### Controllers
- [app/Http/Controllers/ESBTPInscriptionController.php](app/Http/Controllers/ESBTPInscriptionController.php:458) - Appel `notifyInscriptionCreated()` après création
- [app/Http/Controllers/ESBTPPaiementController.php](app/Http/Controllers/ESBTPPaiementController.php:464) - 3 intégrations :
  - Ligne 464 : Notification création paiement
  - Ligne 1618 : Notification validation + désactivation rappels
  - Ligne 1680 : Notification rejet + désactivation rappels
- [app/Http/Controllers/ESBTP/ESBTPSettingsController.php](app/Http/Controllers/ESBTP/ESBTPSettingsController.php:83) - Gestion paramètres rappels + méthode `testReminders()`

#### Vues
- [resources/views/esbtp/settings/index.blade.php](resources/views/esbtp/settings/index.blade.php) - Refonte complète avec système d'onglets :
  - Lignes 285-302 : Navigation par onglets
  - Lignes 864-1042 : Nouvel onglet "Notifications et Rappels"
  - Lignes 1164-1225 : Fonction JavaScript `testReminders()`

#### Routes
- [routes/web.php](routes/web.php:1518) - Route `esbtp.settings.test-reminders`

### Commandes disponibles

```bash
# Tester les rappels (mode simulation, n'envoie rien)
php artisan reminders:send-inscription-paiement --test

# Envoyer les rappels réellement
php artisan reminders:send-inscription-paiement

# Seed des paramètres par défaut
php artisan db:seed --class=ReminderSettingsSeeder
```

### Planification automatique

La commande `reminders:send-inscription-paiement` s'exécute automatiquement **chaque jour à 8h00** (heure d'Abidjan) via le scheduler Laravel.

Pour activer le scheduler en production :
```bash
# Ajouter au crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Caractéristiques techniques

- **Anti-auto-notification** : L'utilisateur qui crée une inscription/paiement ne reçoit pas la notification
- **Icônes FontAwesome** : Toutes les notifications utilisent des icônes (pas d'emojis)
- **Gestion intelligente des rappels** : Arrêt automatique après limite ou changement de statut
- **Mode test intégré** : Permet de tester sans envoyer de vraies notifications
- **Logging complet** : Toutes les opérations sont loguées pour audit
- **Transaction-safe** : Utilisation de DB::beginTransaction() pour intégrité des données

### Tests effectués

- ✅ Migration `notification_reminders` exécutée avec succès
- ✅ Seeder des paramètres par défaut exécuté avec succès
- ✅ Commande test avec 226 inscriptions et 110 paiements en attente détectés
- ✅ Interface settings avec onglets fonctionnelle
- ✅ Système anti-auto-notification vérifié

### Notes importantes

- Les notifications utilisent la table `custom_notifications` (pas la table Laravel native `notifications`)
- Les settings de rappels utilisent `ESBTPSystemSetting` (pas la table `settings`)
- Le scheduler doit être activé via crontab pour le fonctionnement automatique en production
- En développement, lancer manuellement : `php artisan schedule:work`

---

*Dernière mise à jour: 4 octobre 2025*