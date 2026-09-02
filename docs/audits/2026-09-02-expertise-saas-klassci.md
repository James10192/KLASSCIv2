---
titre: Expertise SaaS KLASSCI — septembre 2026
artifact: https://claude.ai/code/artifact/5d5f075b-7759-4e31-bd6d-79b3c07f0a21
---

_Revue d'expertise produit & ingénierie_

# KLASSCI, de deux écoles à un empire : ce qui tient, ce qui casse, ce qu'il faut bâtir

Lecture complète du dépôt KLASSCIv2 sur cinq chaînes métier et quatorze pages clés, avec contre-vérification de chaque constat grave. L'objectif : dire sans détour ce qui empêche aujourd'hui une école, ivoirienne ou non, de démarrer et de tourner seule sur KLASSCI, et fixer les attentes d'un éditeur qui vise le marché africain.

  **Branche** presentation (HEAD 49b96f3)
  **Date** 2 septembre 2026
  **Périmètre** inscription · caisse · pédagogie · rôles · UX · international

  
## Verdict en une page

  KLASSCI est fonctionnellement très riche et, sur plusieurs points, au-dessus de la moyenne des SaaS de cette taille : autorisation dense, transactions, audit trail, réconciliation de caisse, machine à états des feuilles de notes, jury LMD avec PV signés. Mais ce capital est fragile : les tests ne tournent pas en intégration continue, la production n'est observée par personne, le framework est en fin de vie, et le produit reste, dans son schéma comme dans ses écrans, l'application d'une école de Yamoussoukro. Une nouvelle école ne peut pas démarrer seule : il n'y a ni parcours de configuration guidé, ni import de ses étudiants, ni périmètres par rôle, ni devise ou pays paramétrable.

  Le chantier prioritaire n'est donc pas d'ajouter des fonctions. C'est de transformer une application faite pour ESBTP en une plateforme qu'un technicien à distance configure sans coder, et qu'un caissier ou un agent d'inscription utilise 200 fois par jour sans friction.

  
    - **1 092** fichiers PHP · 211 contrôleurs · 708 vues

    - **1 516** tests écrits, 0 exécuté en CI

    - **317** permissions, dont 77 jamais vérifiées

    - **662** occurrences de « FCFA » en dur

    - **0** périmètre de données par rôle

  

## Ce qui est solide, et qu'il faut protéger

Il serait faux de présenter KLASSCI comme un produit à refaire. Les fondations suivantes sont réelles, vérifiées dans le code, et constituent un avantage sur la plupart des concurrents locaux.

  - **Sécurité applicative mûre.** 384 gardes de permission sur 1 060 routes, 9 routes publiques seulement et toutes légitimes, throttling sur login et reset, sessions chiffrées et `secure`, un seul modèle sans protection de mass-assignment sur 164, et un test de non-régression sécurité daté de mai 2026.

  - **Registre de permissions centralisé** avec 317 permissions, 33 groupes, 13 toggles de module, aliases de rétrocompatibilité et commande d'audit. Peu d'éditeurs de cette taille l'ont.

  - **Réconciliation de caisse implémentée à 80 %** : sessions, comptages figés, écarts, motifs obligatoires, log immuable avant/après, séparation ouvreur/approbateur, verrouillage post-clôture. L'audit OwenIt est bien branché sur les paiements, contrairement à ce que dit la règle interne.

  - **Jury LMD de bout en bout** : calcul automatique, override motivé, quorum, PV numéroté avec checksum, signatures, verrouillage, rattrapage avec sessions et publication.

  - **Feuilles de notes à états** (attendue → saisie → contrôlée → validée) avec verrouillage par observateur global, fenêtres de saisie, workflow d'approbation des documents scolarité avec garde permission → solde → approbation.

  - **Frais et échéanciers riches** : catégories, configurations par scope BTS/LMD, options et forfaits, règles d'échéancier avec snapshots, répartition serveur unique, reventilation, avoirs, montant zéro traité comme une valeur.

  - **Une équipe qui apprend de ses incidents** : rules internes détaillées, commentaires de CI qui expliquent le pourquoi, corrections ciblées de cascades dangereuses.

## Les 12 risques bloquants avant tout déploiement autonome

Classés par gravité, chacun vérifié directement dans le code. Un risque « critique » peut faire perdre de l'argent, des données ou la confiance d'un client sans que personne ne s'en aperçoive.

**1.** 
### Personne ne verra une panne, et la sauvegarde meurt avec le serveur **[critique]**
Aucun Sentry ni équivalent, aucun endpoint de santé applicatif, logs sur disque local uniquement. Le job de sauvegarde écrit sur le disque du même serveur que la base. Pour une école à Cotonou ou Accra sans vous sur place, c'est le risque numéro un.

_Preuve : `app/Jobs/SauvegardeDataJob.php:49` (disque local) · aucune dépendance de monitoring dans `composer.json`._

**2.** 
### Laravel 9 en fin de vie, sur un PHP que le framework ne supporte pas **[critique]**
Le fichier CLAUDE.md annonce Laravel 12 ; le verrou de dépendances installe `laravel/framework v9.52.21`, sans correctifs de sécurité depuis février 2024, avec une exigence `php ^8.3` hors matrice supportée. Toute la pile (Sanctum 3, Spatie Permission 5, PHPUnit 9) est alignée sur cette version.

_Preuve : `composer.lock` · `composer.json:16`._

**3.** 
### Supprimer une inscription efface son historique de paiements **[critique]**
Les clés étrangères de `esbtp_paiements` vers l'inscription et l'étudiant sont en `onDelete('cascade')`. Le soft-delete du modèle ne protège pas d'une cascade SQL. Non conforme à la conservation OHADA de 10 ans.

_Preuve : `database/migrations/2025_03_01_100003_create_esbtp_paiements_table.php:21-22`._

**4.** 
### 1 516 tests qui ne protègent rien **[critique]**
Le workflow CI fait `php -l`, une vérification de noms de classes et un comptage de directives Blade. Aucune étape n'exécute PHPUnit. La configuration de test vise une base MySQL réelle, ce qui explique l'absence. Chaque régression atteint donc six écoles en production.

_Preuve : `.github/workflows/laravel-ci.yml` · `phpunit.xml:23-24`._

**5.** 
### Numéros de reçu non uniques et générés sans verrou **[critique]**
À l'encaissement, le numéro est « dernier + 1 » lu hors transaction, sur une colonne à index non unique : deux caissiers simultanés produisent le même reçu. À l'inscription, le numéro de reçu est un `mt_rand(1, 9999)`. C'est un trou d'audit et une porte à la fraude que l'on ne détecte pas.

_Preuve : `app/Models/ESBTPPaiement.php:730-752` · `app/Services/ESBTPInscriptionService.php:132`._

**6.** 
### Le verrou de période comptable n'est jamais posé **[critique]**
La garde anti-modification rétroactive lit le réglage `comptabilite.period_locked_until`, mais aucune interface ne l'écrit : la clôture périodique décrite dans vos règles internes n'existe pas (aucune table, route ni permission). De plus la création d'un paiement n'a aucune garde de période : on peut antidater un encaissement dans un mois déjà rapproché. Le module dépenses est mort : trois migrations, aucun modèle ni route, donc aucune sortie de caisse et un solde structurellement faux.

_Preuve : `app/Http/Controllers/Concerns/VerrouilleLesPeriodesComptables.php:36` (lecture seule) · absence de `app/Domain/Comptabilite/PeriodClosure`._

**7.** 
### Une école ne peut pas démarrer seule **[critique]**
Aucun assistant de configuration ni garde de prérequis dans l'interface : la seule checklist de démarrage vit dans le chatbot. On peut inscrire un étudiant sans frais configurés, et l'aperçu des frais affiché à l'inscription ne passe pas par le résolveur de scope : les montants affichés diffèrent des montants facturés en LMD et sur les overrides annuels. Aucun import Excel des étudiants d'une école qui migre : elle doit tout ressaisir.

_Preuve : `app/Services/Chatbot/ChatbotSetupGuideService.php:12-42` · `app/Http/Controllers/ESBTPInscriptionApiController.php:314-322`._

**8.** 
### Les enseignants ne peuvent pas saisir de notes en LMD, et la pondération CC/Examen n'est pas appliquée **[critique]**
Les routes de saisie LMD sont réservées à la direction et à la scolarité. Le PV de jury atteste une pondération 40/60 que le calcul de moyenne n'utilise pas ; la note éliminatoire et la compensation intra-UE sont lues mais jamais appliquées. Une école LMD sort donc des moyennes non conformes UEMOA, certifiées par un PV.

_Preuve : `routes/web.php:2860` · `app/Services/LMDBulletinService.php:364-410` vs `JuryPvSnapshotBuilder.php:56`._

**9.** 
### Aucun périmètre de données par rôle ou par personne **[majeur]**
Un caissier voit toute l'école, le journal de caisse n'est pas filtrable par caissier, il n'y a ni clôture de journée individuelle ni fonds d'ouverture. Aucune délégation temporaire, aucune fenêtre horaire, aucun plafond par rôle. 36 sites `hasRole()` en dur font de tout rôle personnalisé un citoyen de seconde zone. 77 permissions affichées dans l'interface ne sont vérifiées nulle part : l'école croit configurer, rien ne change.

_Preuve : `app/Http/Controllers/ESBTPJournalCaisseController.php:169-199` · `app/Policies/ESBTPNotePolicy.php:65` · `config/permissions.php`._

**10.** 
### Devise, pays, téléphone et langue sont codés en dur **[majeur]**
662 occurrences de FCFA, indicatif +225 forcé dans les SMS et WhatsApp, fuseau Africa/Abidjan dans les jobs, identité ESBTP dans `config/school.php`, 1,1 % des vues traduites. Hors zone franc ou en pays anglophone, les notifications parents échouent en silence et chaque écran doit être réécrit.

_Preuve : `app/Domain/Notifications/PhoneNormalizer.php:15` · `app/Services/SmsService.php:180` · `config/school.php:10-18`._

**11.** 
### CSRF désactivé sur des routes authentifiées par session **[majeur]**
Le préfixe `esbtp/api/*` est exclu de la vérification CSRF alors qu'il est servi par le groupe web avec cookies de session. Pas de double authentification pour les comptables.

_Preuve : `app/Http/Middleware/VerifyCsrfToken.php:16-17`._

**12.** 
### Une route de frais en erreur 500 garantie, et des places calculées de trois façons **[majeur]**
La relation `variants()` est commentée dans le modèle mais toujours appelée par la route `frais/category-variants`. Le contrôle des places disponibles est fait hors transaction, sans verrou, avec trois sources qui divergent.

_Preuve : `app/Models/ESBTPFraisCategory.php:135` vs `ESBTPFraisController.php:1105` · `ESBTPInscriptionController.php:504`._

## Inscription et démarrage d'année

C'est le module par lequel vous voulez commencer, et c'est le bon choix : tout le reste en dépend. Aujourd'hui, l'ordre de configuration obligatoire n'est écrit nulle part dans l'interface. Le voici, déduit du code :

  - Année universitaire avec `is_current`

  - Niveaux d'étude avec le bon `type` (il pilote BTS ou LMD) et une configuration de matricule par niveau, sans quoi la génération échoue

  - Filières (BTS) ou Domaine → Mention → Parcours (LMD)

  - Classes avec places, année et parcours

  - Catégories de frais, puis configurations par scope, puis options et forfaits, puis échéanciers

  - Rôles, permissions et réglages

  
### Ce qui manque pour que ça marche seul

    - Un assistant « Démarrer mon année » qui montre l'état de chaque étape, bloque l'inscription tant que les prérequis manquent, et propose « Copier depuis l'année dernière ».

    - Un import Excel des étudiants et inscriptions avec prévisualisation et rapport d'erreurs.

    - Le parcours LMD dans le formulaire d'inscription et une colonne `parcours_id` sur l'inscription.

    - Un seul calcul des places, dans une transaction avec verrou.

    - Un numéro de reçu séquentiel unique, réservé sous verrou.

  
  
### Dettes à nettoyer dans la même passe

    - 90 lignes de code mort BTS après un `return` dans la génération des frais.

    - Réinscription sans facture, sans contrôle de places, sans paywall, contrairement à l'inscription.

    - Reliquats non idempotents et jamais transformés en souscription.

    - Deux règles de solde contradictoires : solde ≤ 0 d'un côté, tolérance de 50 000 en dur de l'autre.

    - Transactions imbriquées entre contrôleur et service.

  

## Caisse et comptabilité

Vos rôles caissier et comptable sont bien pensés sur le papier. Dans le code, le caissier n'a pas de journée : pas de fonds d'ouverture, pas de comptage individuel, pas d'écart imputable à une personne. Le comptable n'a pas de clôture : le verrou de période est un décor. Les modes de paiement sont une chaîne libre à l'encaissement alors qu'un enum existe, et le journal de caisse code sa propre liste en dur. Aucune double validation au-dessus d'un seuil, seulement une notification après coup.

| Capacité attendue d'un logiciel de gestion scolaire | État KLASSCI | Écart |
|---|---|---|
| Journée de caisse par caissier (ouverture, encaissements, comptage, clôture, écart) | Absent | **[critique]** |
| Numéro de reçu séquentiel, unique, thread-safe | Lecture « dernier + 1 » sans lock, index non unique | **[critique]** |
| Clôture mensuelle avec snapshot et verrou | Setting lu, jamais écrit ; aucune UI | **[critique]** |
| Dépenses / sorties de caisse | Table migrée, module mort | **[critique]** |
| Double validation au-dessus d'un seuil configurable | Notification seulement | **[majeur]** |
| Modes de paiement contrôlés | Enum existe, non appliqué à l'encaissement ; avoirs hors enum | **[majeur]** |
| Réconciliation caisse ↔ système | Implémentée à 80 %, PV non généré à la clôture | **[mineur]** |
| Mobile money (Wave, Orange, MTN) | Grille de permissions, aucune intégration ni webhook | **[majeur]** |
| Pénalités de retard, exonérations formelles | Absent ; exonération = paiement à 0 | **[mineur]** |

## Pédagogie BTS et LMD

Le BTS est opérationnel. Le LMD est proche, mais trois manques le rendent non conforme et inutilisable par les enseignants.

  - **Saisie LMD réservée à la scolarité.** Les routes sont sous un middleware direction/scolarité. Votre modèle « l'enseignant saisit TD/TP, la scolarité saisit l'examen » n'existe pas : il n'y a aucune notion de type de note par rôle.

  - **Fenêtre de saisie contournable.** Le garde est appliqué sur deux méthodes AJAX seulement ; création, mise à jour, saisie rapide et import Excel passent outre.

  - **Bulletins sans immuabilité.** PDF à la volée, aucun hash ni version, régénération libre même après publication. Deux impressions du même bulletin peuvent différer. Les PV de jury, eux, ont déjà tout cela : il suffit de réutiliser `OfficialDocumentService`.

  - **Deux moteurs de calcul** pour les moyennes UE et pour les moyennes BTS, avec divergences possibles selon l'écran.

  - **Planning général** : quatre méthodes vides dont la détection de conflits, alors que la vue affiche « 0 conflit ». À la duplication de semaine, les séances en conflit sont supprimées en silence.

  - **Propriété des évaluations** basée sur qui a créé l'évaluation, pas sur l'affectation enseignant-matière : un enseignant retiré d'une matière garde la main.

## Rôles, périmètres et limites

Votre vision, chaque école définit ses rôles, leurs périmètres et leurs limites dans le temps, est la bonne. Le registre de permissions en est la première brique. Il manque les trois autres :

  
### Périmètre (sur quoi)
- Filières, niveaux, classes, sites ou campus attachés à une affectation de rôle.
- Appliqué par un scope global sur les requêtes, pas par des `if` dans chaque contrôleur.
- Le seul scoping existant, enseignant → ses évaluations, est codé en dur.

  
### Limites (jusqu'où)
- Plafond de montant par rôle, avec approbation au-delà.
- Séparation des devoirs déclarative : saisie ≠ validation, comptable ≠ caissier. Aujourd'hui trois règles LMD seulement, et un cumul comptable + caissier passe.
- Fenêtres horaires de caisse.

  
### Temps (jusqu'à quand)
- Affectation de rôle avec date de fin et intérim.
- Aucun `expires_at` n'existe sur les affectations.

  
### Autonomie de l'école
- La matrice globale et la restauration des défauts sont réservées au service technique.
- Les rôles personnalisés ne sont assignables que par un superAdmin.
- Des droits implicites hors registre (`Gate::after`) sont invisibles dans l'interface.

## Ergonomie, pertinence des KPI et fabrication des pages

Quatorze pages clés et cinq axes transversaux ont été audités avec une rubrique commune, en simulant les trois tâches les plus fréquentes du rôle concerné. Chaque constat critique a ensuite été soumis à un contradicteur chargé de le réfuter dans le code. Résultat : un seul constat réfuté, une trentaine rétrogradés de critique à majeur, et trois critiques maintenus que j'ai revérifiés moi-même.

  
    - **5,1 / 10** moyenne générale des 19 périmètres

    - **4,3** configurabilité, l'axe le plus faible

    - **4,5** accessibilité

    - **322 → 11** KPI affichés vs KPI qui mènent à une action

    - **~90** en-têtes de page recopiés à la main

  

### Scores par périmètre

7 = bon niveau professionnel, 9 = référence du marché. Les scores sont sévères à dessein : ils mesurent l'écart avec votre ambition, pas avec la concurrence locale.

| Périmètre | Rôle simulé | Moy. | Verdict |
|---|---|---|---|
| Bulletins | Scolarité | 5,9 | Meilleur module : préflight et génération solides ; publication de masse et périodes à corriger. |
| Paiements, liste | Caissier, comptable | 5,7 | Liste AJAX exemplaire ; reçu bloqué tant que non validé, rafraîchissement automatique qui écrase la saisie. |
| Inscriptions, liste | Agent d'inscription | 5,6 | Mécanique fluide, mais KPI, liste et fiche ne racontent pas le même état. |
| Notes et évaluations | Enseignant, scolarité | 5,4 | Grille tableur excellente ; trois parcours de saisie avec des statuts divergents. |
| Étudiants, liste et fiche | Scolarité | 5,3 | Fiche de niveau marché ; recherche tronquée, documents à trois clics. |
| Inscription, création | Agent d'inscription | 5,1 | Garde-fous riches, mais perte silencieuse de données et aucun mode série un jour de rentrée. |
| Dashboard comptable | Comptable | 5,1 | Le bandeau « Aujourd'hui » est juste ; le reste ment (année, export, cache jamais invalidé). |
| Paiement, création | Caissier | 5,0 | Règles serveur sûres, ergonomie de guichet absente : neuf interactions par encaissement, pas de reçu à la fin. |
| Jury et crédits LMD | Scolarité LMD | 5,0 | Chaîne légale robuste ; le seuil configuré par l'école est ignoré par la délibération. |
| Dashboards par rôle | Tous | 4,9 | Composant récent prometteur ; les dashboards anciens sont décoratifs ou cassés. |
| Système de KPI | Direction | 4,9 | Registre propre, mais 11 widgets sur 16 sans action et sans point d'entrée. |
| Rôles personnalisés | SuperAdmin école | 4,7 | Sécurité correcte ; 317 cases à cocher sans description, dissuasif. |
| Configuration des frais | Comptable | 4,6 | Propagation par niveau utile ; mutations sans garde de permission, feedback défaillant. |
| Navigation | Tous | 4,6 | Menu organisé par tables, 74 entrées, des permissions accordées sans chemin pour y accéder. |
| Réglages | Technicien | 4,4 | Page la plus sensible : rôles en dur, 146 champs sans parcours ni recherche. |
| Fabrication des pages | Développeur | 4,3 | La charte impose la copie ; aucun composant de liste, filtre ou KPI. |
| Emploi du temps, planning | Coordinateur | 4,1 | Conflits non détectés en avance, pas de publication, tout figé dans le code. |

### Les trois constats critiques confirmés

**A.** 
### Des champs d'inscription saisis puis perdus en silence **[critique]**
Le formulaire d'inscription propose nationalité, transfert, établissement d'origine et statut d'affectation. Les règles de validation ne les déclarent pas, et le contrôleur ne transmet que les données validées : ces champs sont jetés sans message. L'agent d'inscription crée des dossiers faux dès le premier jour.

_Preuve : `app/Http/Requests/Inscription/StoreInscriptionRequest.php:18-37` · `ESBTPInscriptionController.php:561`._

**B.** 
### Le seuil de validation LMD configuré par l'école est ignoré par le jury **[critique]**
Le profil de règles LMD lit la clé moderne puis la clé historique. La délibération automatique et la programmation des rattrapages ne lisent que la clé historique. Une école qui règle son seuil dans l'interface voit ses décisions calculées avec un autre seuil que celui imprimé sur le PV.

_Preuve : `app/Services/JuryDeliberationService.php:50` · `RattrapageSchedulingService.php:81` vs `LmdAcademicRuleProfile.php:23`._

**C.** 
### L'indicateur de focus clavier est supprimé sur tout le design system **[critique]**
Un `outline: none` global et répété rend la navigation au clavier aveugle. Pour un caissier qui encaisse 50 fois par matinée, le clavier est l'outil principal ; pour un utilisateur malvoyant, c'est bloquant.

_Preuve : `auth/login.blade.php:325` · `esbtp/paiements/create.blade.php:241,324` et règle globale._

### Cinq problèmes systémiques, avec leur cause racine

  - **Quatre familles de droits écrites séparément.** Rôles, identités, toggles de module et permissions métier coexistent sans règle de composition. Résultat : un bouton « Modifier » affiché puis refusé sur les paiements, des réglages gardés par rôle en dur alors que la route exige une permission, des mutations de frais sans garde, une section de menu morte. La promesse des rôles personnalisés est cassée exactement là où ça compte. Il manque un test simple : « bouton visible implique action autorisée ».

  - **Les référentiels métier vivent dans le code.** Devise, indicatif, modes de paiement recopiés dans cinq vues, périodes, jours et plages d'emploi du temps, seuils d'ancienneté 30/60/90, quatre listes divergentes de types d'évaluation, préfixes de reçus, modèles de bulletin nommés d'après des villes. Cause : l'écran des réglages est écrit champ par champ dans une vue de 4 536 lignes. Tout nouveau réglage exige un développeur, donc on code en dur.

  - **Des KPI calculés localement, décoratifs et contradictoires.** « Validées » ne correspond pas à la liste, « Total » inclut les rejetés, deux « reste à recouvrer » différents, étudiants en base confondus avec inscrits, un compteur plafonné à 12, des caches de 60 secondes jamais invalidés. Cause : aucun service partagé de comptage, aucun composant KPI qui porte une action. Chaque contrôleur réinvente la règle.

  - **Ruptures de flux et feedback hétérogène.** Redirection après chaque création, rechargements de page sur le jury, les frais et les volumes horaires, fenêtres `alert()` natives, un toast réimplémenté onze fois, des erreurs AJAX avalées, des formulaires rechargés vides. Cause : pas de socle JavaScript commun. La règle « pas de rechargement » est appliquée page par page.

  - **Fabrication par copie, donc chaque correction se fait 300 fois.** Environ 90 en-têtes dupliqués, 358 blocs de style inline, 307 libellés non liés à leur champ, 39 boutons icône sans nom. Cause : la charte interne prescrit de copier le bloc et interdit le CSS séparé. Sans composants de page, aucune correction transversale n'est possible.

### Ce que vivent vos rôles aujourd'hui

  
### Caissier
- Neuf interactions par encaissement, aucun mode série, pas d'autofocus ni de validation par Entrée.
- Le reçu ne s'imprime pas tant que le paiement n'est pas validé : l'étudiant repart sans preuve.
- Bouton « Modifier » visible puis refusé ; correction impossible sans superAdmin passé cinq minutes.
- Un montant à zéro est piégé : l'instruction affichée est impossible à suivre.

  
### Agent d'inscription
- Trois écrans par dossier un jour de rentrée, sections importantes cachées.
- Champs perdus en silence (constat A).
- La validation groupée promet une auto-validation qu'elle ne fait pas ; aucun pré-diagnostic « 12 prêtes / 8 sans paiement ».

  
### Scolarité
- Recherche étudiant tronquée sans avertissement : source de doublons.
- Attestation ou certificat à trois clics, sans registre.
- Rien ne dit ce qui manque avant le bulletin : complétude des notes non calculée sur l'effectif.

  
### Technicien / superAdmin
- 146 réglages sans parcours ni recherche, certains réservés à des rôles codés en dur.
- 317 permissions sans description ; impossible de partir d'un rôle existant.
- Menu de 74 entrées organisé par tables, pas par tâches.

### Les KPI qu'une direction attend et qui n'existent pas

  - Taux de recouvrement par classe et par filière, avec objectif et écart au mois précédent.

  - Taux de remplissage des classes et classes incomplètes.

  - Notes manquantes avant bulletin, par classe et par enseignant.

  - Dossiers d'inscription en attente depuis plus de N jours.

  - Absences critiques du jour, absences d'enseignants.

  - Encaissé net du jour par caissier, écart de caisse.

Règle à adopter : un KPI qui ne mène pas à une liste filtrée ou à une action n'a pas sa place sur un écran. Chaque tuile doit répondre à « et donc je fais quoi ? ».

## Déployer hors Côte d'Ivoire sans être sur place

Trois conditions préalables, dans cet ordre : voir la production (monitoring, sauvegardes hors site, santé), la protéger (tests en CI, framework supporté), puis la rendre configurable (devise, pays, téléphone, langue, identité d'établissement, gabarits de documents). Aujourd'hui l'outillage de déploiement est Windows-only et le `.env.example` ne contient aucune variable Laravel : une équipe distante ne peut pas provisionner une instance sans vous.

| Dimension | Aujourd'hui | Cible |
|---|---|---|
| Devise | « FCFA » en dur, 662 fois | Réglage tenant currency + helper unique de formatage, un seul point de vérité |
| Téléphone | +225 forcé, 10 chiffres nationaux supposés | Indicatif par tenant, normalisation par pays (libphonenumber) |
| Fuseau | UTC global, Africa/Abidjan dans les jobs | Fuseau par tenant |
| Langue | Français en dur, 1,1 % des vues traduites | Extraction progressive vers lang/, en commençant par les écrans caisse et inscription |
| Identité école | config/school.php = ESBTP Yamoussoukro | Tout dans les réglages tenant, config vide de valeurs client |
| Diplômes | « BTS » en dur, 100+ occurrences | Systèmes académiques déclarés en données (BTS, LMD, HND…) |
| Nomenclature technique | 101 modèles ESBTP*, 281 tables esbtp_ | Accepter la dette : ne pas renommer, mais ne plus jamais l'exposer à l'utilisateur |

## Les standards à se fixer pour ne pas décevoir

Voici ce qu'un éditeur qui vise le marché africain doit s'imposer, tel que le font les références du secteur. Chaque ligne est mesurable.

  - **Une école démarre en une journée**, avec son propre technicien, guidée par un assistant, sans ticket ni ligne de code. Mesure : temps entre création du tenant et première inscription validée.

  - **Rien n'est en dur.** Devise, pays, identité, seuils, listes de valeurs, gabarits de documents, textes de notifications : tout vit dans un registre de réglages typé, avec défaut, validation et interface. Mesure : zéro déploiement pour une adaptation locale.

  - **Chaque franc a une trace.** Reçu unique, journée de caisse close par son caissier, période verrouillée par le comptable, aucune suppression physique d'écriture. Mesure : audit reconstituable sur 10 ans.

  - **Chaque rôle a un périmètre, une limite et une durée**, définis par l'école. Mesure : un rôle créé dans l'interface fonctionne partout où un rôle standard fonctionne.

  - **Les tâches fréquentes se font en moins de 30 secondes** : encaisser, inscrire, saisir une note, sortir une attestation. Mesure : clics et champs par tâche, mesurés sur les pages réelles.

  - **Une régression n'atteint jamais la production.** Tests exécutés à chaque PR, monitoring des erreurs, sauvegardes hors site vérifiées par restauration. Mesure : délai de détection d'un incident.

  - **Un document officiel est immuable** : bulletin, PV, attestation, reçu ont un numéro, une version, un hash et une vérification en ligne.

  - **Fonctionne sur un Android bas de gamme en 3G.** Pages légères, actions sans rechargement, formulaires courts.

## Feuille de route recommandée

Trois horizons. Le premier ne contient presque aucune nouvelle fonctionnalité : il sécurise ce qui existe pour que le reste puisse être construit vite et sans peur.

### 30 jours  · Protéger la production

  - Monitoring des erreurs (Sentry ou équivalent), endpoint de santé, sauvegardes hors site avec test de restauration. _(effort S)_

  - Exécuter PHPUnit en CI avec un service MySQL ; faire tourner la suite, corriger les tests cassés. _(effort M)_

  - Reçu : colonne unique + réservation sous verrou ; supprimer le `mt_rand`. _(effort S)_

  - Paiements : remplacer les cascades par `restrict` ; garde de période sur la création. _(effort S)_

  - Réparer la route `frais/category-variants` et l'aperçu des frais à l'inscription (passer par le résolveur de scope). _(effort S)_

  - Réactiver le CSRF sur `esbtp/api/*`. _(effort S)_

  - Nettoyer la racine du dépôt, écrire un vrai `.env.example`, rendre les scripts de déploiement exécutables sous Linux. _(effort S)_

  - Inscription : déclarer les champs perdus dans la validation ; jury LMD : lire le seuil via le profil de règles. _(effort S)_

  - Guichet caisse : reçu provisoire imprimable dès la saisie, mode série (« Nouveau paiement » avec mode et date conservés, autofocus, Entrée), un seul bouton Modifier correctement gardé. _(effort M)_

  - Inscription en série : « Enregistrer et encaisser », « Inscrire un autre », pré-diagnostic honnête sur la validation groupée. _(effort M)_

  - Toast global et `:focus-visible` dans le layout, suppression des `outline: none`. _(effort S)_

  - Dashboard comptable : chiffres justes (année, export, invalidation du cache) ; réparer les dashboards secrétaire et étudiant. _(effort S)_

### 90 jours  · Rendre une école autonome

  - Assistant « Démarrer mon année » avec état des prérequis, blocage des inscriptions tant qu'ils manquent, copie depuis l'année précédente. _(effort M)_

  - Import Excel étudiants et inscriptions avec prévisualisation et rapport d'erreurs. _(effort M)_

  - Registre de réglages typé (clé, type, défaut, validation, groupe, permission) et interface générée depuis ce registre ; y migrer devise, pays, indicatif, fuseau, identité école. _(effort L)_

  - Journée de caisse par caissier : ouverture, comptage, clôture, écart ; journal filtrable par caissier. _(effort M)_

  - Clôture mensuelle minimale : bouton qui écrit le verrou, snapshot des KPI, PV PDF. _(effort M)_

  - LMD : ouvrir la saisie aux enseignants par type de note, appliquer la pondération CC/Examen et la note éliminatoire dans le calcul, fermer les contournements de la fenêtre de saisie. _(effort M)_

  - Enum des modes de paiement appliqué partout ; double validation au-dessus d'un seuil. _(effort S)_

  - Montée Laravel 9 → 10 → 11/12 en trois PR, chacune validée par la suite de tests. _(effort L)_

  - Services KPI partagés (une seule définition de « validé », « reste à recouvrer », « inscrits ») et composant KPI qui porte obligatoirement une action. _(effort M)_

  - Rôles personnalisés utilisables : descriptions, « partir d'un rôle existant », test « bouton visible implique action autorisée », visibilité du menu dérivée des permissions réelles. _(effort M)_

  - Socle JavaScript commun (fetch, CSRF, erreurs 419/422, toast) et premiers composants de page : en-tête, grille KPI, barre de filtres, champ de formulaire accessible. _(effort L)_

### 6 mois  · Devenir une plateforme

  - Périmètres par affectation de rôle (filière, classe, site), plafonds, dates de fin, séparation des devoirs déclarative ; supprimer les 36 `hasRole()` en dur ; retirer ou câbler les 77 permissions mortes. _(effort L)_

  - Documents officiels unifiés sur `OfficialDocumentService` : bulletins, attestations, certificats, reçus avec numéro, version, hash, vérification en ligne. _(effort M)_

  - Gabarits de documents éditables par l'école (en-tête, signatures, mentions légales) sans déploiement. _(effort M)_

  - Fabrique de pages : composants hero, KPI, filtres, table, formulaire, export ; toute nouvelle page se déclare, elle ne se recopie plus. _(effort L)_

  - Module dépenses vivant ; intégrations mobile money avec webhooks et rapprochement automatique. _(effort L)_

  - Internationalisation par étapes : caisse, inscription, portail étudiant d'abord ; anglais comme seconde langue. _(effort L)_

## Méthode et limites de cette revue

Cinq lectures parallèles du code (64 agents au total pour la partie UX, 12 millions de tokens lus) (inscription, caisse, pédagogie, rôles, qualité), puis un workflow de quatorze audits de pages et cinq audits transversaux avec une rubrique commune, dont chaque constat critique ou majeur a été soumis à un contradicteur chargé de le réfuter en vérifiant le code. J'ai ensuite revérifié moi-même chaque risque bloquant listé plus haut. Cette revue lit le code, pas les données ni les usages réels : elle ne mesure pas les temps de tâche des utilisateurs en établissement, ce qui reste la prochaine étape indispensable.

Rapport établi le 2 septembre 2026 sur la branche `presentation`, commit 49b96f3. Les numéros de ligne renvoient à cet état du dépôt.
