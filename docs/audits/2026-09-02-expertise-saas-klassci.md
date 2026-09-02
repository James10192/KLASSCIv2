---
titre: Expertise SaaS KLASSCI — septembre 2026 (rapport final contre-vérifié)
artifact: https://claude.ai/code/artifact/5d5f075b-7759-4e31-bd6d-79b3c07f0a21
epic: https://github.com/James10192/KLASSCIv2/issues/738
---

_Revue d'expertise produit & ingénierie_

# KLASSCI, de deux écoles à un empire : ce qui tient, ce qui casse, ce qu'il faut bâtir

Lecture complète du dépôt KLASSCIv2 sur cinq chaînes métier, quatorze pages clés et douze axes élargis (offre publique, marché, réglementation, architecture, données, exploitation, sécurité, frontend, IA, modules, scénarios de vie d'une école), avec contre-vérification de chaque constat grave. L'objectif : dire sans détour ce qui empêche aujourd'hui une école, ivoirienne ou non, de démarrer et de tourner seule sur KLASSCI, et fixer les attentes d'un éditeur qui vise le marché africain.

  **Branche** presentation (HEAD 49b96f3)
  **Date** 2 septembre 2026
  **Périmètre** inscription · caisse · pédagogie · rôles · UX · offre · marché · architecture · données · exploitation · sécurité · frontend · IA · modules · scénarios · E2E réel · 49 issues

  
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

## Votre offre telle qu'elle se présente, et le décalage

Le site klassci.com se décrit comme « le SaaS éducatif africain tout-en-un, né en Côte d'Ivoire », avec trois univers : enseignement supérieur (LMD, semestres, crédits, comptabilité, gouvernance), collège et lycée (trimestres, bulletins DREN, caisse, présences, parents), et classe virtuelle. La documentation publique est une vraie force : un « Quickstart 60 minutes » en huit étapes, des guides par rôle, une référence API, un changelog tenu.

| Ce que promet le site | Ce que fait le produit | Décalage |
|---|---|---|
| « Un compte, une école. Hébergé, sauvegardé, sécurisé. » | Hébergement mutualisé cPanel partagé entre les six écoles, sauvegarde sur le même disque, aucun monitoring | **[critique]** |
| Collège et lycée : trimestres, bulletins DREN | Produit séparé (KLASSCI Collège, autre dépôt) : hors périmètre de cette revue. À noter tout de même que ce dépôt ne connaît que des semestres, ce qui compte pour le supérieur en trimestres | **[hors périmètre]** |
| Classe virtuelle : cours, devoirs, évaluations | Une API « LMS » documentée, mais aucun module de cours en ligne dans l'application | **[majeur]** |
| Sélecteur de langue EN visible | Non fonctionnel ; 1,1 % des vues traduites | **[majeur]** |
| Quickstart 60 minutes | Aucun assistant dans l'application ; la checklist n'existe que dans le chatbot | **[majeur]** |
| Changelog public | Dernière date visible : avril 2026, alors que le dépôt livre des fonctions en août | **[mineur]** |

**Ce qui manque au site pour convaincre un directeur exigeant ou une école hors Côte d'Ivoire :** aucun tarif, aucun plan nommé (les plans Free, Essentiel, Professionnel, Élite, Partenaire n'existent que dans votre base master), aucun témoignage ni chiffre client, aucunes mentions légales, politique de confidentialité ni conditions générales, aucun engagement de disponibilité, aucune page sécurité ou conformité, aucune démo publique. Pour un SaaS qui traite des paiements et des données de mineurs, l'absence de politique de confidentialité est aussi un risque légal.

Règle simple : ne vendez sur le site que ce qui existe dans l'un des deux produits aujourd'hui. « Classe virtuelle » doit devenir une page « bientôt » ou disparaître jusqu'à ce qu'un module la porte.

## Marché, concurrence, réglementation

### Concurrence directe en Afrique de l'Ouest

  - **KiboERP** (Abidjan, Dakar, Bamako, Ouagadougou) : inscriptions, scolarités, export SYSCOHADA, plan Starter gratuit à vie. C'est votre concurrent le plus proche en positionnement prix.

  - **School'Gest / Sup'Gest** (Sénégal) : version dédiée au supérieur.

  - **EduSahel** (Sénégal, Côte d'Ivoire, Burkina) : SaaS régional.

  - **Edves** (Nigeria, Ghana, 2 300 écoles, 625 000 élèves) : la référence anglophone. Paiement des frais par les parents depuis l'application, SMS et WhatsApp en langues locales, internat, transport, inventaire. C'est le niveau à viser pour l'expansion anglophone.

  - **SAFSMS** (Nigeria), **EdwebApps** et **Eduware** (Ghana) : acteurs établis avec application mobile parents.

Ce que ces acteurs font mieux que KLASSCI aujourd'hui : paiement des frais par mobile money directement par le parent, application mobile, notifications multilingues, modules de vie scolaire (internat, transport, cantine), et un prix public. Ce que KLASSCI fait mieux : la profondeur LMD (jury, PV, crédits, rattrapage), la réconciliation de caisse OHADA, l'export Sage, le registre de permissions, et une documentation publique rare sur ce marché.

### Réglementation qui vous concerne dès maintenant

  - **Loi ivoirienne 2013-450 sur les données personnelles** : déclaration obligatoire des traitements à l'ARTCI, droits d'accès et d'effacement, encadrement des transferts hors du pays. KLASSCI envoie des données au chatbot via des fournisseurs d'IA situés hors d'Afrique (Gemini, Groq, Anthropic sont tous configurés) : ce transfert doit être déclaré et encadré. Le code ne contient aucun mécanisme de consentement ni de registre des traitements.

  - **Facture Normalisée Électronique (FNE) et Reçu Normalisé Électronique (RNE)**, obligatoires en Côte d'Ivoire pour tous les contribuables depuis le 1er décembre 2025, sans exception de régime fiscal. Les reçus de scolarité de vos écoles clientes sont potentiellement concernés par le RNE. C'est à vérifier avec un fiscaliste, mais si c'est le cas, c'est une fonctionnalité obligatoire, et une opportunité : être le premier SIS ivoirien qui émet des reçus normalisés DGI.

  - **OHADA / SYSCOHADA** : conservation 10 ans, pièces justificatives numérotées, séparation des devoirs. Déjà partiellement traité par la réconciliation, contredit par les cascades de suppression et les reçus non uniques.

  - **UEMOA et CAMES** : 30 crédits par semestre, 75 à 85 % de crédits fondamentaux et transversaux, mentions, compensation. Le calcul LMD doit appliquer les règles qu'il imprime.

  - **Pays cibles** : Sénégal (CDP), Bénin (APDP), Ghana (Data Protection Act 2012), Nigeria (NDPA 2023). Chacun exige une déclaration ou un enregistrement et, souvent, une localisation des données. L'hébergement unique en France chez LWS ne tiendra pas partout.

### Paiements

Wave expose une API métier avec sandbox, webhooks et frais d'environ 1 %, une entité et une clé par pays. Les agrégateurs (CinetPay, PayDunya, Paystack, Flutterwave) couvrent Wave, Orange Money, MTN et Moov en une intégration. Les parents ivoiriens paient déjà les frais d'inscription du secondaire dans l'application Wave. KLASSCI n'a aujourd'hui qu'une grille de permissions « mobile money » et aucune intégration : c'est l'écart concurrentiel le plus visible pour un parent.

## Architecture du code, des dossiers et des fichiers

Ce n'est pas un code « sale ». C'est un monolithe par couches devenu trop gros pour sa structure, avec une seconde architecture par domaines qui a commencé à pousser à côté sans que la première ne recule.

| Zone | Fichiers | Lignes | Lecture |
|---|---|---|---|
| Contrôleurs HTTP | 211 | 97 620 | Le centre de gravité du métier : plus de lignes que les services et le domaine réunis |
| Services | 223 | 57 909 | Couche « fourre-tout » : Chatbot, Frais, MailPulse, Scoring, LMD, Reinscription… |
| Domain (Comptabilité, AcademicPilotage, Analytics, OfficialDocuments, BtsTroncCommun…) | 170 | 16 703 | La bonne direction, mais 15 % du code seulement, et en concurrence avec app/Services |
| Modèles | 169 | 25 198 | Modèles lourds, préfixe ESBTP sur 101 d'entre eux |
| Vues Blade | 708 | 284 950 | Trois fois le volume PHP applicatif ; 25 vues font des requêtes Eloquent directes |
| Routes | 2 | 4 010 | 153 closures dans les routes, routes de debug et de test laissées en place |

### Quinze faiblesses de fondation

  - **Deux architectures en concurrence.** app/Services (223 fichiers) et app/Domain (170) se partagent le métier sans règle : la comptabilité a un domaine, les frais ont des services, l'inscription a les deux.

  - **Contrôleurs porteurs du métier.** Dix contrôleurs dépassent 1 900 lignes. Le plus gros (résultats) fait 3 343 lignes.

  - **Doublons conceptuels nommés en deux langues.** ESBTPEtudiantController, ESBTPStudentController, StudentController ; TeacherController, ESBTPEnseignantController ; sept contrôleurs de dashboard ; quatre contrôleurs « paiement ». La règle interne le documente comme piège (« 3 heures perdues ») au lieu de le corriger.

  - **485 blocs `catch (\Exception)` dans les contrôleurs**, la plupart transformant une erreur en redirection silencieuse. C'est la cause première des « ça a marché » qui n'ont rien fait.

  - **582 `Log::info` de diagnostic** dispersés, sans convention ni niveau, sur un hébergement où seul `error` passe.

  - **Le préfixe ESBTP partout** : modèles, tables, vues, routes, CSS. Le produit s'appelle KLASSCI mais tout le code porte le nom du premier client. Ne pas renommer (trop coûteux), mais ne plus jamais l'exposer et ne plus créer de nouveaux fichiers avec ce préfixe.

  - **29 appels `env()` hors des fichiers de configuration** : ils retournent null dès que la config est mise en cache en production.

  - **Aucune règle d'architecture exécutable** (deptrac, phpat, tests d'architecture) : les 45 règles écrites dans .claude/rules sont une documentation, pas une contrainte. Elles sont appliquées par discipline humaine et par IA, donc de façon intermittente.

  - **Trois moteurs PDF** (dompdf, mpdf, browsershot avec Chromium) pour un seul besoin.

  - **Trois fournisseurs d'IA** configurés (Gemini, Groq, Anthropic) sans abstraction unique ni politique de données.

  - **Aucune analyse statique** : ni PHPStan ni Psalm, Pint installé mais jamais exécuté en CI. Le vérificateur maison de noms de classes admet « une soixantaine de noms irrésolus antérieurs ».

  - **Un dépôt qui mélange produit et atelier** : 19 fichiers Markdown et 9 captures d'écran à la racine, deux dumps de texte nommés d'après un chemin Windows, `composer.phar` versionné, 532 fichiers dans dev-scripts, 16 fichiers Playwright versionnés, 63 documents dans docs dont beaucoup décrivent des correctifs passés.

  - **Un `.env.example` qui n'est pas celui de Laravel** : il contient une configuration Taskmaster, Perplexity et Anthropic, aucune variable de base de données ni d'application.

  - **Le layout principal fait 4 418 lignes** dont 1 499 lignes de CSS et 1 128 de JavaScript inline, chargé sur chaque page.

  - **Le frontend n'a pas de build** : package.json ne contient que puppeteer-core, aucun script, aucun bundler actif ; jQuery, Bootstrap et Alpine viennent de CDN sans intégrité vérifiée.

### Architecture cible et chemin de migration

Un monolithe modulaire par domaines métier, sans réécriture : **Plateforme** (tenant, réglages, identités, permissions, documents officiels, notifications), **Admissions et Inscriptions**, **Scolarité** (étudiants, classes, documents), **Finance** (frais, caisse, réconciliation, clôture, dépenses), **Pédagogie** (maquettes, planning, emploi du temps, évaluations, notes, jury, bulletins), **Ressources humaines** (personnel, contrats, présences, paie), **Communication** (annonces, messages, chatbot, canaux). Règle unique : un module ne dépend que de Plateforme et d'interfaces publiques des autres.

  - Poser deptrac avec les six modules et une règle : tout nouveau fichier doit vivre dans un module. L'existant est toléré (baseline), pas étendu.

  - Activer PHPStan niveau 1 avec baseline, puis monter d'un niveau par trimestre.

  - Fusionner les doublons de contrôleurs un couple à la fois, en commençant par étudiants, avec tests de routes.

  - Extraire du contrôleur vers le module tout ce qui touche à l'argent d'abord (paiement, inscription), en strangler : la nouvelle méthode appelle le service, l'ancienne disparaît quand ses tests passent.

  - Choisir un seul moteur PDF et un seul fournisseur d'IA derrière une interface.

  - Nettoyer le dépôt : racine, docs obsolètes archivées, dev-scripts hors du dépôt.

  - Remplacer les 485 catch silencieux par une gestion centralisée des exceptions avec message utilisateur et trace serveur.

  - Découper routes/web.php par module, supprimer closures et routes de debug.

## Schéma de données

| Mesure | Valeur | Ce que ça signifie |
|---|---|---|
| Tables créées par les migrations | 206 | Dont 68 sans préfixe esbtp_ : deux conventions coexistent |
| Tables sans modèle déclarant explicitement leur nom | 91 | Beaucoup s'appuient sur la convention Laravel, mais des tables mortes s'y cachent (dépenses) |
| Colonnes status ou statut | 60 | 28 en enum SQL, 32 en chaîne libre : deux philosophies, et des valeurs sales (« valide » et « validé » coexistent sur les paiements) |
| Colonnes JSON | 105 | Beaucoup portent des règles métier (config de bulletin, snapshots) : non requêtables, non validées par le schéma |
| Colonnes *_id déclarées sans contrainte de clé étrangère | 107 | Contre 588 avec contrainte : un cinquième des liens n'est pas garanti par la base |
| Migrations protégées par Schema::hasColumn ou hasTable | 104 | Signe clair d'une dérive de schéma entre les six bases de tenants : les migrations ne savent plus ce qu'elles trouveront |
| Migrations qui insèrent ou modifient des données | 25 | Réglages et permissions seedés par migration, avec le piège created_by = 1 documenté dans vos règles |
| Tables porteuses de annee_universitaire_id | 44 | Dont les classes, que votre propre règle déclare universelles : le modèle et la règle se contredisent |
| Montants en decimal | 48 | Bon point ; deux colonnes en float à corriger |
| Colonnes chiffrées au repos | 0 | Aucune PII chiffrée : photos, téléphones, adresses, données de mineurs en clair |
| Tables de journal qui grossissent sans purge | 25 | Audits, événements, notifications, prédictions, logs chatbot : cinq commandes de purge seulement |

**Fondations données cibles.** Un outil de vérification de dérive de schéma exécuté sur les six tenants avant chaque déploiement (il n'en existe aucun). Des enums PHP adossés à des enums SQL pour tous les statuts, avec migration de nettoyage des valeurs sales. Des contraintes de clé étrangère partout, en `restrict` sur tout ce qui est financier ou académique. Une politique de rétention par table (purge après N mois, archivage à froid après 10 ans). Le chiffrement au repos des colonnes de PII sensibles. Et une décision à prendre à 30 écoles : rester en base par tenant (isolation forte, mais 30 migrations, 30 sauvegardes, 30 monitorings) ou passer à une base partagée avec identifiant de tenant. La base par tenant reste défendable pour des écoles qui exigent l'isolation ; elle impose un outillage d'orchestration que vous n'avez pas encore.

## Exploitation, infrastructure, cycle de vie tenant

Le modèle actuel : six écoles, six branches Git, six dossiers sur un seul compte d'hébergement mutualisé cPanel chez LWS, une application maître qui orchestre. Un workflow GitHub déploie par FTP. Le scheduler tourne par crontab, le worker de file d'attente n'est documenté que dans un guide de bonnes pratiques.

  - **Isolation entre écoles : nulle.** Même utilisateur Unix, même compte, même disque. Une école compromise expose les cinq autres. Les données de mineurs de six établissements partagent un mot de passe cPanel.

  - **Déploiement par FTP et git pull**, sans étape de test, sans rollback outillé, avec migrations exécutées en production sans vérification de dérive.

  - **Jobs lourds sur mutualisé** : PDF en masse, analytics, notifications. CloudLinux limite le CPU et les processus ; LiteSpeed coupe les requêtes longues. C'est la source probable des timeouts de 30 secondes que vos règles internes mentionnent.

  - **Facturation des écoles** : rien dans le code tenant, et le paywall interroge le maître. Comment ADC facture, relance et suspend une école n'est pas outillé côté produit.

  - **Fin de contrat** : aucune procédure d'export complet et de restitution des données à une école qui part, aucune purge à J+N. C'est une obligation légale et un argument commercial (« vos données restent les vôtres »).

  - **Environnements** : pas de staging, pas de Docker, pas de README de mise en route, outillage Windows uniquement, seeders ignorés par Git. Un développeur distant ne peut pas démarrer seul.

| Palier | Infra | Ordre de grandeur mensuel |
|---|---|---|
| Maintenant (6 écoles) | Un VPS ou deux (Hetzner, OVH, ou un fournisseur africain pour la résidence des données), Laravel Forge ou Ploi, Supervisor pour les workers, sauvegardes hors site chiffrées, Sentry, Uptime | 60 à 150 € |
| 30 écoles | Une seule codebase déployée une fois, résolution du tenant par sous-domaine, base par tenant orchestrée, staging, déploiement sans interruption, stockage objet (S3 compatible) pour fichiers et sauvegardes | 300 à 800 € |
| 200 écoles, plusieurs pays | Conteneurs, régions par pays pour la résidence des données, files distribuées, observabilité centralisée, astreinte, statut public | 2 000 à 5 000 € |

Le modèle « une branche Git par école » est le premier obstacle à l'échelle. Il n'existe aucune divergence de code entre tenants selon vos propres règles : les branches ne servent qu'au déploiement. Une seule version déployée partout, avec des fonctions activées par réglage, remplace six branches.

## Sécurité, second passage

  - **Comptes enseignants créés avec le mot de passe « password »** dans le contrôleur super-administrateur, à deux endroits. Avec 2 000 étudiants et des dizaines d'enseignants, c'est une porte ouverte prévisible.

  - **CORS ouvert à toutes les origines** sur l'API, sans restriction.

  - **Routes de debug et de test laissées dans le routeur** (debug-annees-simple, test-emploi-temps-show, test-debug-mode, planning-general/test).

  - **17 requêtes SQL brutes avec variables interpolées** à auditer une par une.

  - **58 sorties HTML non échappées** dont le contenu d'un message de relance : si un utilisateur peut y écrire, c'est une injection de script.

  - **30 usages de `Storage::url`** : à vérifier que les photos, pièces et PDF ne sont pas servis sans authentification par URL devinable.

  - **Secrets** : des motifs de clés apparaissent dans `.env.example`, dans `.windsurfrules` et dans des tests. J'ai vérifié la nature sans copier les valeurs : à faire tourner immédiatement dans un scanner de secrets sur tout l'historique Git, et à révoquer ce qui est réel.

  - **Aucune fonction d'impersonation tracée**, aucune journalisation d'accès aux dossiers étudiants : impossible de répondre à « qui a consulté ce dossier ? ».

  - **Pas de verrou de ligne à l'encaissement** : deux caissiers sur le même étudiant peuvent dépasser le dû.

  - **Bon point** : un middleware d'en-têtes de sécurité existe, 36 vérifications HMAC sur les webhooks, 131 vérifications d'abilities sur l'API CLI, un test de non-régression sécurité.

Avant un audit externe ou une certification : rotation de tous les secrets, scanner de secrets en CI, PHPStan, Sentry, 2FA pour les rôles financiers, journal d'accès aux données personnelles, registre des traitements, politique de confidentialité publiée, procédure de notification de violation.

## Frontend et performance terrain

  - **Pas de chaîne de build** : jQuery 3.7, Bootstrap 5.3, Alpine 3 et FontAwesome viennent de trois CDN différents ; 12 feuilles de style et 6 scripts externes par page, plus 2 600 lignes inline dans le layout. Sur une 3G à Bouaké, chaque page recharge tout.

  - **Des images de 14 Mo et 19 Mo dans public/images** (fond de connexion 4,9 Mo). La page de connexion est probablement la plus lourde de l'application.

  - **1 870 `!important`** dans le CSS : le design system se corrige par surenchère de spécificité. Neuf fichiers CSS s'appellent « fix » de quelque chose.

  - **96 vues réimplémentent l'appel fetch avec jeton CSRF** ; 65 vues utilisent encore jQuery à côté d'Alpine.

  - **Un service worker existe** (bon point, cache-busting par version), mais sans stratégie hors ligne pour la caisse ou la saisie de notes.

  - **Aucun test JavaScript, aucun lint.**

**Trajectoire réaliste** : Vite avec un bundle unique versionné, jQuery retiré page par page, un fichier klassci.js (fetch, CSRF, erreurs, toast), images optimisées et servies en WebP, puis composants Blade de page. Pas de migration vers Livewire ou Inertia avant que les composants existent : ce serait une réécriture.

## IA, chatbot, communication

  - **Trois fournisseurs d'IA** configurés (Gemini, Groq, Anthropic) avec des services agents distincts. Aucune abstraction commune, aucune politique écrite sur les données envoyées. Le chatbot administrateur explore la base (service « Explorer », outils) : des données d'étudiants et de paiements partent chez un fournisseur étranger sans consentement documenté. Le premier chantier IA n'est pas une fonction, c'est une politique.

  - **Chatbot parents WhatsApp** via l'API Meta Cloud, avec files entrantes, bail de traitement, boîte d'envoi durable, lots d'onboarding : c'est l'une des architectures les plus soignées du dépôt. Il manque l'opt-out explicite, la mesure du coût par message et une FAQ multilingue.

  - **SMS** : Orange SMS CI, SMS.to et Beem Africa configurés. Aucune fenêtre horaire d'envoi : un rappel de paiement peut partir à 3 heures du matin.

  - **MailPulse** : un service maison d'orchestration de notifications de workflow. Bien structuré, mais c'est une brique de plus à maintenir seul.

  - **Trois modèles d'e-mail seulement**, non éditables par l'école. Les textes de SMS et de WhatsApp sont dans le code.

  - **Analytics prédictifs** : régression logistique de risque d'impayé et prévision de trésorerie. Vos propres règles documentent dix pièges (saturation à 100 % à risque, mode dégradé invisible). Utile pour le comptable seulement si calibré et expliqué ; à présenter comme « indicatif » tant qu'il n'y a pas d'évaluation sur données réelles.

**Opportunités IA à forte valeur et faible risque** : lecture de relevés de notes papier par photo pour saisie assistée, détection d'anomalies de caisse (montants inhabituels, séquences de reçus), rédaction de courriers et attestations, résumé de dossier étudiant pour la scolarité, génération d'emploi du temps sous contraintes, FAQ parents multilingue. Toutes peuvent tourner sur des données pseudonymisées.

## Inventaire et maturité des modules

Le menu compte 19 sections et 25 vues de dashboard distinctes. Les routes révèlent une soixantaine de modules. Voici l'essentiel de leur état, au-delà du cœur déjà audité.

| Module | État | Observation |
|---|---|---|
| Inscriptions, réinscriptions, caisse, paiements, frais, échéanciers | **[prod]** | Cœur du produit, testé, mais dettes listées plus haut |
| Notes BTS, bulletins BTS, feuilles de notes à états | **[prod]** | Le module le mieux testé (15 fichiers) |
| LMD complet : domaines, mentions, parcours, UE, ECUE, jury, PV, rattrapage, crédits | **[prod fragile]** | Enseignants exclus de la saisie, pondération non appliquée |
| Réconciliation de caisse | **[prod fragile]** | 80 % ; PV non archivé, un seul test de routes |
| Emploi du temps, planning général, séances | **[prod fragile]** | Méthodes vides, conflits silencieux |
| Présences étudiants, codes journaliers, appel | **[prod fragile]** | 3 tests, aucun sur les règles d'assiduité |
| Personnel, contrats, présences enseignants, taux horaires, salaires | **[bêta]** | Modèles de paie présents, 13 routes, peu de tests ; un vrai module RH est à portée |
| TPE, bourses, bons de sortie, candidatures, portail public, annonces, messages | **[bêta]** | Fonctionnels mais isolés, sans tests dédiés |
| Pilotage académique (alertes, métriques, feuilles) | **[bêta]** | 81 fichiers de domaine, 2 tests : ambitieux, peu vérifié |
| Chatbot admin, chatbot parents, MailPulse, webpush | **[bêta]** | Architecture soignée, politique de données absente |
| Analytics prédictifs, scoring du personnel | **[prototype]** | À présenter comme indicatif |
| API CLI (30 contrôleurs), API LMS | **[outil interne]** | Puissant pour vous, dangereux si un token fuit ; à documenter et cloisonner |
| Dépenses | **[mort]** | Migrations sans modèle, contrôleur ni route |
| Formation continue, partenariats, événements académiques, spécialités, cycles | **[à qualifier]** | Routes présentes, usage réel inconnu |

**Dix décisions de portefeuille** : finir la réconciliation (PV, verrou) ; ressusciter ou supprimer les dépenses ; fusionner les trois contrôleurs étudiants ; unifier les sept dashboards sur le système de widgets ; sortir l'API CLI en produit d'administration séparé avec jetons à durée limitée ; transformer personnel et paie en module RH testé ; geler analytics et scoring en « indicatif » ; choisir un fournisseur d'IA ; supprimer les modules à usage inconnu après vérification des logs d'accès ; ne lancer aucun nouveau module avant que les fondations du premier horizon soient posées.

## Scénarios de vie d'une école que le produit ne couvre pas

Un logiciel de gestion d'établissement se juge sur les cas rares qui, chaque année, coûtent des journées à une administration. Voici ceux que le code ne traite pas, ou traite à moitié.

  
### Année et structure

    - Bascule d'année : aucune commande ni assistant de clôture N et ouverture N+1 ; les réinscriptions existent, le report des frais, planifications et rôles non.

    - Trimestres : inexistants ; le code est semestriel en dur. Une école supérieure en trimestres ou en sessions ne peut pas être servie (le collège est un produit séparé).

    - Multi-campus : une seule colonne `etablissement_id` sur six tables, pas de notion de site.

    - Jours fériés et calendrier scolaire : absents.

  
  
### Étudiant

    - Transfert entrant avec équivalences de crédits : suivi de candidature seulement.

    - Abandon, exclusion, décès : un champ « abandon » existe, aucun flux de clôture de dossier avec solde, remboursement et documents.

    - Fusion de doublons : un détecteur existe, pas d'action de fusion.

    - Changement d'état civil ou de matricule : aucun flux tracé.

    - Étudiant mineur et tuteur légal : pas de modèle de responsable légal distinct.

  
  
### Finance

    - Remboursement : avoirs seulement, pas de sortie de caisse (module dépenses mort).

    - Pénalités de retard, remises commerciales tracées, chèque impayé : absents.

    - Paiement à distance par un parent : impossible sans intégration mobile money.

    - Duplicata de reçu : possible, mais sans mention « duplicata » ni compteur.

    - Tarif par nationalité ou par statut : non prévu dans le résolveur de frais.

  
  
### Pédagogie et personnel

    - Réclamation d'un étudiant sur une note : aucun flux.

    - Fraude à l'examen et sanction disciplinaire : aucun module discipline.

    - Stages, alternance, mémoires, soutenances : absents (une soutenance n'existe que comme type de séance).

    - Départ d'un employé : désactivation manuelle, pas de transfert des dossiers ni de révocation des jetons.

    - Heures des vacataires vers la paie : présences enseignants et taux existent, la chaîne jusqu'au bulletin de paie est à vérifier.

  
  
### Conformité et plateforme

    - Statistiques annuelles MESRS/DESP et listes d'examens nationaux : aucun export au format attendu.

    - Demande d'accès ou d'effacement de données : aucun flux.

    - École qui quitte KLASSCI : aucun export complet.

    - Saisie hors ligne pendant une coupure : aucune.

    - Migration depuis Excel : aucun import.

    - Support : aucun canal intégré, aucun ticket, aucune base de connaissances dans l'application.

  

## Ce qui manque de sérieux, et les idées qui feraient la différence

Vous sentez qu'il manque quelque chose de sérieux. Voici, à mon avis, les quatre manques structurants, puis les idées.

  - **Un modèle de « structure d'établissement » configurable.** Aujourd'hui, la structure (année, semestres, niveaux BTS/LMD, filières) est celle d'ESBTP. Un produit pour toute l'Afrique a besoin d'un modèle générique : périodes (semestre, trimestre, session), niveaux et cursus déclarés en données, systèmes académiques (BTS, LMD, HND, secondaire) comme configurations, pas comme branches de code. C'est le chantier qui rend tout le reste possible.

  - **Un moteur de règles pour les calculs qui font foi.** Moyennes, mentions, crédits, compensation, frais, pénalités : ils sont aujourd'hui dispersés dans des contrôleurs et services, parfois en double. Un endroit unique, versionné, testé, dont chaque document officiel enregistre la version, est ce qui permet d'affirmer « ce bulletin a été calculé avec la règle v3 ».

  - **La plateforme comme produit.** Réglages typés, fonctions activables, périmètres de rôle, gabarits de documents, import et export : tout ce qui permet à un technicien de configurer sans coder. C'est votre demande initiale, et c'est le cœur de l'autonomie.

  - **L'exploitation comme discipline.** Monitoring, sauvegardes testées, déploiement d'une seule version, staging, tests en CI. Sans cela, chaque nouvelle école augmente le risque au lieu du revenu.

  
### Idées à fort effet commercial

    - **Paiement des frais par les parents depuis WhatsApp ou un lien** (Wave, Orange, MTN via agrégateur) avec rapprochement automatique et reçu instantané. C'est ce qu'Edves vend, et ce que les parents ivoiriens font déjà avec Wave.

    - **Reçu et facture normalisés DGI** si l'obligation s'applique : différenciateur unique en Côte d'Ivoire.

    - **Documents vérifiables par QR code** (attestations, bulletins, diplômes) : vous avez déjà la brique pour les PV.

    - **Application mobile parents et étudiants** (ou PWA aboutie) : notes, absences, solde, paiement, messages.

    - **Portail d'admission en ligne complet** avec paiement des frais de dossier.

    - **Export ministère en un clic** (statistiques MESRS, listes d'examens).

  
  
### Idées à fort effet opérationnel

    - **Mode caisse hors ligne** : encaissements en file locale, synchronisation à la reconnexion, avec numérotation réservée.

    - **Assistant de rentrée** qui copie l'année précédente et guide étape par étape.

    - **Import Excel intelligent** avec détection de doublons et rapport.

    - **Journée de caisse et clôture mensuelle** avec PV automatiques.

    - **Module RH complet** : contrats, heures des vacataires, paie, congés, avec export vers la CNPS.

    - **Benchmarks anonymisés entre écoles** (taux de recouvrement, réussite) : un avantage réseau que seul un SaaS multi-écoles peut offrir.

    - **Marketplace de gabarits** (bulletins, attestations, règlements) partagés entre écoles d'un même pays.

  

## Organisation, business et façon de construire

  - **Le dépôt raconte une équipe qui livre vite avec l'IA** : 45 règles internes, des skills, des workflows, 733 PR. C'est un atout, à condition que les garde-fous soient dans les outils (CI, analyse statique, tests) et non dans des documents que chaque session doit relire. Aujourd'hui, la qualité dépend de la discipline de lecture des règles.

  - **Une seule version pour tous** : abandonner les branches par tenant, déployer une version, activer les fonctions par réglage. Cela supprime la question « quelle école a quel code ».

  - **Publier les prix et les plans.** Un SaaS sans prix public paraît artisanal. Les plans existent dans votre base master : mettez-les sur le site avec leurs limites.

  - **Écrire les engagements** : disponibilité, sauvegardes, support, restitution des données, confidentialité. Puis les tenir avec l'infra du premier palier.

  - **Choisir un premier pays d'expansion et une seule verticale** (le supérieur privé LMD, où vous êtes réellement différenciés) plutôt que d'annoncer collège, lycée et classe virtuelle.

  - **Mesurer sur le terrain** : temps par encaissement, par inscription, par saisie de notes, dans deux écoles, avant et après chaque amélioration. C'est la seule preuve du « 98 % automatisé ».

## Test en environnement réel (presentation.klassci.com, 2 septembre 2026)

Parcours authentifié en lecture seule avec le compte superadmin fourni : connexion, collecte de tous les liens de la barre latérale, visite de 124 pages, relevé du code HTTP, du temps de réponse, du poids, des marqueurs d'erreur, des selects natifs et des champs sans libellé. Aucune action de modification n'a été déclenchée. Limite : la protection anti-DDoS de l'hébergeur rejette Chromium au niveau TLS, le parcours a donc été fait avec curl ; les erreurs JavaScript et les captures d'écran n'ont pas pu être relevées.

  - **124** pages visitées, 111 en 200

  - **4** erreurs 500 réelles en production démo

  - **1,4 s** temps médian, 253 Ko médian

  - **12,8 s** page la plus lente (analytics compta)

  - **1 146 Ko** page la plus lourde (emploi du temps)

### Les quatre pages en erreur

| Page | Erreur | Issue |
|---|---|---|
| /dashboard/superadmin | Variable $pendingInscriptionsCount non définie dans la vue | #739 |
| /dashboard/teacher | Directive Blade mal compilée ($startSection) : probablement tous les enseignants de la démo | #740 |
| /esbtp/logs | Vue esbtp.logs.index absente, route active | #741 |
| /esbtp/frais/category-variants/1 | Relation variants() supprimée mais appelée, prédit par la lecture du code et confirmé | #742 |

### Ce que le parcours a aussi montré

  - Deux liens `${action.url}` et `${result.url}` rendus tels quels dans le HTML de chaque page (#743).

  - Selects natifs visibles malgré la règle « jamais de select natif » : 28 sur l'emploi du temps, 24 sur les étudiants, 17 sur les paiements et les réglages, 14 sur l'inscription.

  - Champs sans libellé lié : 127 sur les réglages, 45 sur les paiements, 39 sur l'inscription.

  - 12 appels `alert()` hérités du layout sur chaque page ; « FCFA » sur 97 pages.

  - Pages lentes : analytics 12,8 s, dashboard comptable 9,2 s, relances 4,8 s, fiche étudiant 4,6 s, paiements 3,9 s.

  - Pages lourdes : emploi du temps 1 146 Ko, parcours LMD 1 091 Ko, dashboard enseignant 878 Ko, étudiants 854 Ko (dont 151 Ko de CSS et 148 Ko de JS inline).

  - Endpoints JSON : places disponibles répond ; l'API des logs refuse sans jeton (bon signe).

Ce qui n'a pas pu être testé et reste à faire avec un navigateur autorisé par l'hébergeur : les comptes des autres rôles (seul superadmin a été fourni), les actions de mutation (encaissement, inscription, saisie de notes), les erreurs JavaScript, le rendu mobile.

## Issues GitHub créées

Une épic et 49 issues enfants, chacune avec preuve, reproduction, comportement attendu, correctif, tests et critères d'acceptation. Les constats déjà couverts par l'audit d'août 2026 (épic #564 : CI, secrets, CSRF, Laravel 10, routes cassées, code mort) et par l'épic comptabilité #347 (clôture de caisse, mobile money) ne sont pas dupliqués, seulement référencés.

| Lot | Issues |
|---|---|
| Épic | #738 |
| A · Production démo cassée | #739 dashboard superadmin · #740 dashboard enseignant · #741 logs · #742 variantes de frais · #743 liens de la barre latérale |
| B · Argent | #744 numéros de reçu · #745 cascade paiements · #746 verrou de période · #747 modes de paiement · #748 double validation · #749 journée de caisse · #750 places disponibles · #751 aperçu des frais · #752 réinscription · #753 PV de réconciliation · #754 dépenses |
| C · Inscription et démarrage | #755 champs perdus · #756 assistant de démarrage · #757 import Excel · #758 inscription en série · #786 flux de vie étudiant |
| D · LMD et pédagogie | #759 seuil ignoré · #760 pondération CC/Examen · #761 enseignants exclus · #762 fenêtre de saisie et propriété · #763 bulletins immuables · #770 conflits d'emploi du temps |
| E · Rôles et configurabilité | #764 permissions mortes · #765 périmètres et durées · #766 rôles personnalisés · #767 registre de réglages · #768 devise, pays, langue · #769 modèle d'établissement · #771 gabarits de documents |
| F · Ergonomie et KPI | #772 guichet caisse · #773 KPI actionnables · #774 dashboard comptable · #775 fabrique de pages · #776 accessibilité · #777 performance terrain |
| G · Fondations et exploitation | #778 observabilité · #779 infrastructure · #780 analyse statique · #785 fondations données · #781 sécurité |
| H · Conformité et offre | #782 données et IA · #783 FNE/RNE · #784 site klassci.com · #787 notifications |

### Contre-vérification des issues (second workflow, 44 agents)

Chaque issue a été relue par un contradicteur chargé de vérifier fichier, ligne et comportement. Résultat : 26 confirmées, 15 partiellement confirmées (décalages de lignes ou chiffres à corriger), 3 réfutées sur un point. Les corrections ont été postées en commentaire sur chaque issue concernée. Les trois réfutations à retenir :

  - **#761** : les enseignants ne sont pas exclus de la saisie LMD, car le rôle enseignant possède `admin.access` par défaut. L'issue devient une demande de saisie par type de note et par rôle, avec la fragilité d'un accès qui dépend d'une permission administrative.

  - **#754** : la table des dépenses n'est pas orpheline, elle est pire : le modèle `ESBTPDepense` n'existe pas sur le disque mais six fichiers l'utilisent, dont un job de calcul de KPI qui plantera à l'exécution.

  - **#772** : le reçu est bien accessible avant validation ; le reste du constat (bouton Modifier affiché puis refusé, montant zéro) tient.

Corrections chiffrées : 22 permissions mortes et non 77 (#764), 1 389 `!important` et non 1 870 (#775), 12 selects natifs et non 14 sur l'inscription (#758), la duplication d'emploi du temps affiche bien un message sur les séances omises (#770), la cause exacte du 500 enseignant est un `@section` dans un commentaire JavaScript ligne 998 (#740).

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

  - Sécurité immédiate : scanner de secrets sur l'historique Git et rotation, mot de passe « password » supprimé, CORS restreint, routes de debug retirées, images de connexion optimisées. _(effort S)_

  - Site : retirer ou marquer « bientôt » collège/lycée et classe virtuelle, publier politique de confidentialité et mentions légales, mettre le changelog à jour. _(effort S)_

  - Vérifier avec un fiscaliste l'obligation FNE/RNE pour les reçus de scolarité ; déclarer les traitements à l'ARTCI. _(effort S)_

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

  - Sortir de l'hébergement mutualisé : VPS avec Forge ou Ploi, workers supervisés, staging, une seule version déployée pour toutes les écoles. _(effort M)_

  - PHPStan avec baseline, deptrac avec six modules, Pint en CI, nettoyage du dépôt et vrai README de mise en route. _(effort M)_

  - Paiement des frais par les parents via un agrégateur mobile money, avec rapprochement automatique et reçu instantané. _(effort L)_

  - Politique de données IA : un seul fournisseur, pseudonymisation, consentement, registre des traitements. _(effort S)_

### 6 mois  · Devenir une plateforme

  - Périmètres par affectation de rôle (filière, classe, site), plafonds, dates de fin, séparation des devoirs déclarative ; supprimer les 36 `hasRole()` en dur ; retirer ou câbler les 77 permissions mortes. _(effort L)_

  - Documents officiels unifiés sur `OfficialDocumentService` : bulletins, attestations, certificats, reçus avec numéro, version, hash, vérification en ligne. _(effort M)_

  - Gabarits de documents éditables par l'école (en-tête, signatures, mentions légales) sans déploiement. _(effort M)_

  - Fabrique de pages : composants hero, KPI, filtres, table, formulaire, export ; toute nouvelle page se déclare, elle ne se recopie plus. _(effort L)_

  - Module dépenses vivant ; intégrations mobile money avec webhooks et rapprochement automatique. _(effort L)_

  - Internationalisation par étapes : caisse, inscription, portail étudiant d'abord ; anglais comme seconde langue. _(effort L)_

  - Modèle de structure d'établissement configurable (périodes, cursus, systèmes académiques en données) et moteur de règles versionné pour les calculs qui font foi. _(effort L)_

  - Reçus normalisés DGI si applicable, documents vérifiables par QR, export ministère, export complet de restitution des données. _(effort M)_

  - Mode caisse hors ligne, module RH avec paie, application mobile parents. _(effort L)_

  - Vérificateur de dérive de schéma multi-tenant, enums SQL pour les statuts, politique de rétention par table, chiffrement des PII. _(effort M)_

## Méthode et limites de cette revue

Cinq lectures parallèles du code (64 agents au total pour la partie UX, 12 millions de tokens lus), puis un élargissement mené directement sur douze axes avec mesures dans le dépôt et recherches web (klassci.com, concurrents, DGI, ARTCI, Wave), un parcours E2E authentifié de 124 pages sur presentation.klassci.com, et la création de 49 issues GitHub sous l'épic #738, contre-vérifiées par un second workflow de 44 agents (26 confirmées, 15 partielles corrigées, 3 réfutées sur un point) (inscription, caisse, pédagogie, rôles, qualité), puis un workflow de quatorze audits de pages et cinq audits transversaux avec une rubrique commune, dont chaque constat critique ou majeur a été soumis à un contradicteur chargé de le réfuter en vérifiant le code. J'ai ensuite revérifié moi-même chaque risque bloquant listé plus haut. Cette revue lit le code, pas les données ni les usages réels : elle ne mesure pas les temps de tâche des utilisateurs en établissement, ce qui reste la prochaine étape indispensable.

Rapport établi le 2 septembre 2026 sur la branche `presentation`, commit 49b96f3. Les numéros de ligne renvoient à cet état du dépôt.
