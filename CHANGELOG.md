# Changelog KLASSCI

Toutes les évolutions notables de la plateforme KLASSCI, regroupées par mois.
Le format suit librement [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

> Ce fichier est curé manuellement à partir de l'historique des livraisons.
> Les correctifs mineurs, refactorings internes et changements d'infrastructure
> ne sont pas listés systématiquement. Pour le détail technique complet, consulter
> l'historique Git du dépôt.

---

## Avril 2026

### Ajouts

- **Tabs dynamiques par rôle custom sur `/esbtp/personnel/unified` (Lot 19)** — chaque rôle personnalisé créé dans la section « Rôles personnalisés » obtient automatiquement son propre onglet listant les utilisateurs assignés (avatar bleu + nom + email + téléphone + date de création + bouton « Gérer les assignations »), aligné sur le pattern visuel des onglets fixes (Coordinateurs / Enseignants / Secrétaires / Comptables / Caissiers).
- **Refonte complète du système de rôles & permissions** — nouveau registry centralisé `config/permissions.php` lu via `App\Services\PermissionRegistry` (154 permissions canoniques en dot.notation avec labels FR pour utilisateurs lambda, 125 aliases legacy maintenus pour rétrocompat, matrice "qui peut gérer qui"). Service `App\Services\UserManagementService` + Policy `UserManagementPolicy` pour la gestion granulaire (ex : un secrétaire peut gérer enseignant/étudiant/caissier mais pas comptable). Commande `php artisan permissions:audit` qui détecte cassures, orphelines, aliases utilisés et permissions deprecated. Healing automatique dans `bin/deploy/fix_permissions.php` : sur les tenants existants, ajout des permissions canoniques en complément des aliases.
- **Création de rôles personnalisés** (`/esbtp/personnel/unified`) — les superAdmin et utilisateurs avec `users.manage` peuvent créer des rôles métiers sur mesure (ex : « Agent Inscriptions ») avec nom interne, label FR, icône Font Awesome (whitelist 41 icônes), description, et sélection des permissions par groupe. Migration `roles.label_fr / icon / description / is_custom / created_by_user_id`. Sécurité : un acteur ne peut accorder que les permissions qu'il possède (sauf superAdmin via `Gate::before`).
- **Édition des rôles standards** depuis la même page (whitelist : secrétaire, comptable, caissier, coordinateur, enseignant, étudiant). Le label, l'icône, la description et les permissions sont customisables ; le nom interne reste immuable. superAdmin et serviceTechnique restent gérés exclusivement par le Service Technique via `/esbtp/roles-permissions`.
- **Dashboard avec widgets configurables** — template universel `dashboard/widget-based.blade.php` (namespace `dw-*`), catalogue de 13 widgets gated par permission (étudiants, inscriptions en attente, encaissements du mois, paiements en attente, solde restant à recouvrer, bulletins générés, taux de présence, annonces, utilisateurs actifs, paiements par mode, etc.). Chaque utilisateur configure ses widgets via la modale "Configurer mon dashboard" (colonne JSON `users.dashboard_widgets`). Cache HTML 60 s par widget × utilisateur sur le hot path.
- **Widget breakdown des paiements par mode (Côte d'Ivoire)** — taille `lg` affichant nombre + total mensuel par mode, catalogue `config/payment_modes.php` avec 11 modes canoniques + 27 aliases couvrant Espèces / Chèque / Virement / Carte / Orange Money / MTN Money / Moov Money / Wave / Djamo. Normalisation des variantes en DB (`Espèces` ↔ `especes`, `transfert` → `virement`, etc.).
- **Export détaillé des paiements** (`/esbtp/paiements/export-detaille`) — formulaire de filtres (étudiant en autocomplete, classes multi-select, filière + niveau, période, modes de paiement) avec choix Excel / PDF. Pré-vol AJAX de comptage : si le PDF dépasse 500 lignes, un toast invite l'utilisateur à affiner les filtres ou choisir Excel. Header PDF aligné sur le pattern `liste-complete-pdf` (table 2 colonnes logo + infos école, marges 0.5 cm, méta cells, footer signature). Permission `paiements.export` (assignée à comptable + secrétaire). Respect du périmètre de l'utilisateur : un caissier (`paiements.view_own`) n'exporte que ses propres encaissements.
- **Visibilité du caissier sur les paiements** — nouvelle permission `paiements.view_own` qui restreint l'index aux paiements créés par l'utilisateur. Les rôles avec `paiements.view` voient tous les paiements et la colonne "Encaissé par" sur l'index. Le reçu PDF affiche un bandeau prominent "Encaissé par : [Nom]". Le caissier reçoit désormais `paiements.view_own` (et plus `paiements.view`) par défaut.

### Améliorations

- **Refonte de la page de configuration des rôles** (`/esbtp/roles-permissions`) — lecture dynamique du catalogue depuis le registry (suppression de 185 lignes de mapping hardcodé), ajout du rôle caissier dans l'UI, badges "Legacy" et "Obsolète" sur les permissions dépréciées, toggle d'affichage des aliases, bouton "Restaurer les permissions par défaut" par rôle, panneau d'audit santé.
- **Suppression du rôle parent et de tout le code associé** — les parents utilisent en pratique le compte étudiant de leur enfant. Suppression de 10 contrôleurs Parent*, 21 vues, 1 500+ lignes de code mort, et des permissions `view children bulletins` / `can_view_parent_features`. Les emails et notifications aux parents sont conservés (entité métier `ESBTPParent` distincte du rôle utilisateur).
- **Cohérence des permissions dans tout le code** — 82 références d'aliases legacy migrées vers les noms canoniques (par exemple `view_students` → `students.view`, `valider inscriptions` → `inscriptions.validate`) dans 96 fichiers (controllers, routes, vues, policies, middleware), sans rupture pour les tenants existants grâce à la couche d'aliases.
- **Boutons d'actions cachés au lieu d'erreurs 403** — sur 23 vues clés (inscriptions, paiements, bulletins, comptabilité, notes, présences, résultats, étudiants), les boutons "Valider", "Modifier", "Supprimer", "Ajouter paiement", "Générer bulletin", "Configurer", "Envoyer relances" sont conditionnels via `@can()` canonique. Les pages restent accessibles dès que l'utilisateur a la permission de base.
- **Composant Blade unifié pour les widgets dashboard** — `<x-dw-widget>` et `<x-dw-widget-list>` extraits, les 12 widgets passent de 20 lignes HTML répétées à 5-15 lignes de logique. Index composite `(date, status)` sur `esbtp_attendances` pour le widget taux de présence. Scopes `ESBTPAnneeUniversitaire::scopeCurrent()` (cache 10 min) et `ESBTPInscription::scopePendingValidation()` extraits.
- **Fallback "KLASSCI" sur tous les PDFs et notifications** — quand `school_name` n'est pas configuré dans les settings du tenant, le fallback était hardcodé sur "ESBTP-yAKRO". Désormais, c'est "KLASSCI" partout (générique). Concerne les reçus de paiement, les exports de classes, les emails parents, les notifications de bienvenue.
- **Champs établissement nullables dans les paramètres** — sur `/esbtp/settings`, seul `school_name` reste obligatoire. Tous les autres champs (téléphone, fax, RC, NCC, capital, banque, RIB, etc.) peuvent être enregistrés vides sans erreur "valeur invalide". Double défense dans le contrôleur pour les tenants existants dont les règles de validation en DB sont legacy.

### Sécurité

- **Suppression de l'auto-attribution dangereuse de rôle par email** — précédemment, un utilisateur dont l'email contenait "admin" recevait automatiquement le rôle superAdmin lors de l'exécution de `fix_permissions.php`. Désormais, les utilisateurs sans rôle sont uniquement signalés ; l'attribution se fait explicitement via l'interface admin.
- **Matrice de gestion utilisateurs granulaire** — remplace l'ancien `manage-users` monolithique. Configurable dans `config/permissions.php` clé `role_management`. Un acteur ne peut gérer (créer/modifier/supprimer/assigner) que les utilisateurs dont les rôles sont dans son périmètre. Par exemple, un comptable ne peut plus créer un superAdmin.
- **Whitelist d'icônes Font Awesome** sur la création/édition de rôles — empêche l'injection de classes arbitraires.
- **`Gate::before` pour superAdmin** — toute vérification `@can()` retourne `true` automatiquement pour le superAdmin, évitant les blocages silencieux quand une permission est cassée ailleurs.

### Saisie globale et autres

- **Saisie globale des heures sans matière (présences)** — la nouvelle interface permet de saisir les heures de cours sans assigner de matière spécifique, utile pour les cours mutualisés ou les conférences. Pattern `UNIQUE+NULL` cross-compatible MariaDB/MySQL via une colonne générée.
- **Portail groupe pour les fondateurs** — agrégation cross-tenant des indicateurs financiers, pédagogiques et opérationnels avec scoring de santé par établissement, alertes automatiques (plan mismatch, tenant inactif, expiration SSL, baisse d'effectif), et bannière d'abonnement transverse.
- **Onboarding membres du groupe** — création du rôle DGA, flow d'invitation par email avec mot de passe auto-généré et URL signée 24h, force-change du mot de passe à la première connexion, fallback `username` (génération `prenom.nom` avec déduplication automatique en cas de collision).
- **Notifications cross-tenant** — système d'invoices, notifications enseignants, notifications fondateurs, table dédiée avec préférences par utilisateur.
- **Blocage des classes pleines** sur le formulaire d'inscription : affichage en temps réel des places disponibles avec seuils colorés (vert au-dessus de 30 %, orange entre 10 et 30 %, orange foncé en dessous de 10 %, rouge complet), désactivation automatique du bouton de soumission, prise en compte des inscriptions validées de l'année courante uniquement.
- **Contexte conversationnel du chatbot Claude** — le bot mémorise désormais le résultat du dernier outil exécuté (étudiants ou classes) pour répondre à des questions de suivi telles que « et dans la classe d'à côté ? » sans devoir re-spécifier la classe.
- **Filtres enrichis pour le chatbot** — recherche d'étudiants par étape de workflow et statut, recherche de classes par système (BTS / LMD) et disponibilité de places.
- **Schéma tarifaire Signature 2026** — trois paliers (Essentiel, PRO, ELITE) alignés sur l'enseignement supérieur, formule Partenaire à 15 000 XOF par an, période d'essai de 3 mois, grandfathering 24/18/12 mois pour les abonnements existants.

### Améliorations

- **Refonte complète du module comptabilité** — six pages premium repensées : tableau de bord comptable, paiements, suivi par catégorie, configuration des frais, dashboard, relances. Pattern `planning-header` à deux rangées avec KPIs intégrés au héros.
- **Refonte de l'onglet Présences** sur la fiche étudiant — un seul héros sombre unifié rassemblant la synthèse, sous-cards blanches pour le détail granulaire, gate des années précédentes pour éviter les régressions visuelles.
- **Refonte de la page emploi du temps** — vue jour/semaine/mois avec puces de séances colorées, kebab d'actions par séance, légende, paramètres PDF dynamiques par établissement, duplication de semaine en un clic.
- **Refonte premium des inscriptions** — KPI live-update, actions groupées (annulation, export), confirmations modales `iiConfirm`, toasts uniformes, partage de styles via `common.js` et namespace CSS `ii-*`.
- **Refonte de la page classes** — structure revue, badges sémantiques, table modernisée, AJAX pour les filtres.
- **Refonte des modales d'administration** — uniformisation sur la palette monochrome bleu KLASSCI, suppression des couleurs décoratives non sémantiques.
- **Sécurisation des actions monétaires** — limitation `throttle:60,1` et `10,1` sur les routes de paiements et remplacement de tous les `window.confirm()` natifs par des dialogues custom `iiConfirm`.

### Corrections

- **Fix N+1 dans la recherche de classes du chatbot** — passage de 300 requêtes par appel à 2, via `withCount` filtré et un seul lookup d'année universitaire courante.
- **Fix double définition `selectClasse()`** dans le sélecteur de classe : la deuxième définition par hoisting JavaScript écrasait silencieusement la première sans appeler `toggleSubmitButton()`, le bouton n'était jamais désactivé via la modale « classe pleine ». Les deux fonctions sont fusionnées.
- **Bouton « Notes »** sur la page évaluations redirige désormais vers la saisie rapide ; correction de la suppression en lot.
- **Suppression d'un `composer install` au déploiement** qui figeait certains tenants sur des environnements partagés.

### Sécurité

- **Audit IDOR phase 1** — création de trois Policies, ajout de `authorize()` sur les routes sensibles, masquage de `viewFinancials` pour les rôles non autorisés.
- **Cleanup des traces et stack traces** exposées en production sur dix routes identifiées.
- **Migration de 230+ appels `hasRole()`** vers le système de permissions Spatie (`@can`, `@cannot`), suppression des hardcodes de rôles dans les contrôleurs.

---

## Mars 2026

### Ajouts

- **Système LMD complet** parallèle au BTS — gestion des UE, ECUE et crédits par semestre, pivot many-to-many ECUE↔UE avec coefficients contextuels, parcours étudiants avec validation progressive des crédits, bulletins LMD avec moyennes pondérées, formules de calcul configurables (AQ, NAQ, APC).
- **Module tronc commun** — flow d'inscription tronc commun puis spécialisation, bulletin de classe d'origine, matières communes, planning strict, trois paramètres de configuration (bulletin, matières, planning).
- **Note de conduite** — calcul automatique sur 16/20 (déduction d'un point par tranche de 4h d'absence), mentions Blâme et Avertissement, huit niveaux d'appréciation, paramétrage par établissement, prise en compte des absences par matière.
- **Rôle caissier** — espace dédié avec dashboard caisse, flow de pré-inscription multi-étapes avec saisie des frais par catégorie, paiement partiel, génération automatique des identifiants via `UserService`, banner de complétion administrative avec garde-fous.
- **API LMS multi-tenant** — endpoints publics de découverte des établissements (sans token permanent requis), authentification dédiée avec rate-limiting `lms-discovery`, documentation interne accessible via `GET /api/lms/documentation`.
- **Module documents étudiants** — upload, liste, téléchargement, badge d'extension, gestion des permissions de lecture par rôle.
- **Différenciation BTS/LMD sur la fiche étudiant** — onglets semestriels, reliquats par catégorie de frais, CECT à vie, accessor `photo_url` unifié.
- **Configuration interactive des frais** — commande artisan pour gérer les souscriptions de frais en mode interactif, génération sans doublons, archivage des inactifs.
- **Module ECUE** — modale à deux onglets avec validation de crédits, tabs Select2 premium, page UE 100 % AJAX (zéro rechargement), liaison UE↔Parcours multi-semestres.

### Améliorations

- **Refonte du dashboard coordinateur** — design premium avec KPIs et navigation AJAX entre onglets sans rechargement.
- **Refonte de la page emploi du temps** — design premium, KPIs, scrollable charts avec ECharts, format horaire HH:MM partout.
- **Refonte des pages résultats** (index, classes, classe) — vues annuelle / S1 / S2, intégration de l'assiduité, fix du filtrage des notes incohérentes.
- **Refonte de la page Notes** — calendrier de disponibilités avec édition inline, recherche temps réel dans les modales de sélection, modal autonome remplaçant l'embed AJAX legacy.
- **Refonte de la page Pré-inscription** — cards d'analyse premium, étapes centrées avec lignes continues entre cercles, recherche d'étudiant pour le flux de réinscription.
- **Refonte de la page Planning Annuel** — fusion de l'onglet Events, calendrier équilibré (900 px, cellules 48 px), KPIs corrigés sur tous les onglets, modal d'enregistrement sans rechargement.
- **Refonte des sections Coordinateur et Charge par classe** — design premium, modal d'émargement aligné avec la page attendance-codes.
- **Refonte de la situation financière** — preview en héros sombre style fiche étudiant, PDF style document formel, boutons d'action sur l'onglet Finances, fallback SVG pour avatar manquant.
- **Refonte de la page Personnel** — design premium, masquage des rôles selon les permissions, ajout de l'onglet caissier dans l'unifié.
- **Optimisations de performance** — extraction des constantes de configuration des frais, suppression de l'email hardcodé, fix N+1 dans LMD avec eager-loading approprié.

### Corrections

- **Fix bulletin LMD moyennes à zéro** — incohérence de format de période dans la requête, ajout de `getPeriodeVariants()`.
- **Fix workflow inscription** — requirement d'un paiement validé avant validation de l'inscription, alerte sur la page étudiant en cas d'inscription en attente, redirection automatique après validation.
- **Fix dashboard pour superAdmin** — fond blanc, texte d'avertissement plus doux, AJAX refresh corrigé pour les chargements de classes/enseignants.
- **Fix rendering du modal parent** — élimination du flash au survol, suppression des transitions, modal customisé remplaçant le modal Bootstrap pour la recherche de parent.
- **Fix Select2 dans les modales** — z-index 1075, dropdown forcé en bas, dropdownParent body pour échapper à `overflow-y:auto`.
- **Fix bouton « Modifier disponibilité »** dans la grille enseignant.

---

## Février 2026

### Ajouts

- **Système de bulletins enrichi** — pondération des semestres pour la moyenne annuelle, persistance des résultats à la génération, sélecteur de style de bulletin, toggle style Abidjan.
- **Bulletin par étudiant** — modal de coefficients auto-suffisant directement sur la fiche étudiant, sans aller-retour vers une page séparée.
- **Refonte des Filières & Niveaux** — pills sélectionnables, gestion par filière du niveau dans les liaisons.
- **Module Émargement enseignant** — badges « À venir », édition en lot, modal de rapport, suivi des heures réalisées, polling des disponibilités.
- **Documents administratifs PDF** — refonte complète des prévisualisations, paramètres de couleurs configurables, génération via Browserless pour fiabilité, watermarks renforcés, header thématique appliqué aux certificats.
- **Génération PDF de feuilles de notes vierges** pour saisie manuelle.
- **Création rapide d'enseignant** depuis la modale de séance de cours.
- **Recherche temps réel dans les modales de sélection** d'étudiants, classes, matières.

### Améliorations

- **Refonte du calendrier de disponibilités** — édition inline directement dans `add-seance`.
- **Refonte de la modale notes fullscreen** + fix de l'erreur 500 sur `quick-create` enseignant.
- **Refonte des stats de présence enseignant** + refonte de la page rapport enseignant.
- **Refonte de la section « Suivi des heures par matière »** sur la page classe.
- **Filtres de classes** dans le planning + format horaire HH:MM standardisé.
- **Modales de gestion étudiants** sur la page classe (ajout, retrait, transfert).

### Corrections

- **Fix bulk-update-status** des présences enseignant — utilisation de `url()` au lieu de `route()` pour éviter le crash quand la route n'est pas en cache sur le serveur.
- **Fix régénération matricule** — application des changements lors de l'édition étudiant.
- **Fix matricule auto-generation** — récupération des informations depuis l'inscription la plus récente.

---

## Janvier 2026

### Ajouts

- **Refonte premium de la landing page** — design éditorial inspiré de zed.dev, IBM Plex Serif/Sans/Mono, palette bleu KLASSCI sur fond beige, dot grid + texture grain, animations premium (gradient text shimmer, blobs morphing, clip-path text reveal, button pulse, pillar stagger), 9 captures réelles dans le marquee hero, modales de fonctionnalités, dark mode complet, mobile responsive.
- **Manager de permissions par rôle** — interface dédiée, regroupement des permissions par module, ouverture automatique des groupes, regroupement des rôles dans l'UI.
- **Refonte complète du module Notes** — workflow par modal, endpoints API dédiés.
- **Module Présences enseignants** — détail par enseignant, statistiques individuelles, bouton PDF dans le bandeau bulk, accordéon pour l'édition en lot, sélecteurs de temps dans l'embed, confirmations en cas de conflit lors de la génération rapide.
- **Édition en lot d'emploi du temps** pour les séances avec gestion des conflits enseignants.
- **Auto-génération de matricule** pour les nouveaux étudiants.
- **Coefficients par filière et niveau** — application stricte au lieu d'un fallback global.
- **Tip d'aide pour l'emploi du temps** — modal guide pas à pas.
- **Workflow plan-and-confirm** — process de validation avant code pour les changements significatifs.

### Améliorations

- **Déblocage des coefficients moyennes** — UX améliorée pour les cas tronc commun.
- **Résolution ESBTP-ABIDJAN** — matricules MASTER/L3 ajustés, gestion des classes pleines.
- **Gestion bulk des disponibilités enseignants** — interface dédiée, application en masse.

---

*Dernière mise à jour : 25 avril 2026*
