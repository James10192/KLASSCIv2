# Changelog KLASSCI

Toutes les évolutions notables de la plateforme KLASSCI, regroupées par mois.
Le format suit librement [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

> Ce fichier est curé manuellement à partir de l'historique des livraisons.
> Les correctifs mineurs, refactorings internes et changements d'infrastructure
> ne sont pas listés systématiquement. Pour le détail technique complet, consulter
> l'historique Git du dépôt.

---

## Mai 2026

### Ajouts

- **Suivi accessibilité — étudiants en situation de handicap** (`/esbtp/accessibility` + section dans la fiche étudiant) — nouvelle table `esbtp_student_accessibility_profiles` (1↔1 étudiant) avec catégories multi-choix (motrice, visuelle, auditive, cognitive, psychique, dys, chronique, autre), aménagements pédagogiques multi-choix (tiers-temps, salle adaptée, support agrandi, interprète LSF, prise de notes, ordinateur autorisé, repos pendant épreuves), tiers-temps en pourcentage configurable (défaut 33%), assistant requis, reconnaissance officielle + référence, période de validité, description courte (visible aux enseignants) et description médicale complète (gated par permission séparée). Modèle `Auditable` via OwenIt — chaque modification tracée. Cohort dashboard premium namespace `acc-*` avec hero KLASSCI bleu, 4 KPIs (étudiants suivis / tiers-temps actif / assistant requis / reconnaissance officielle), filtres classe/filière/niveau/catégorie/aménagement + toggles binaires, table chips. Section premium dans `etudiants/edit` (chip-grid pour catégories+aménagements, toggles glassmorphism, validation période) et carte sur `etudiants/show` avec chips colorées. Pictogramme &#9881; A discret à côté du nom dans 5 PDF (`liste-appel-pdf`, `liste-complete-pdf`, `etudiants/export-pdf`, `notes/saisie-rapide-pdf`, plus la table de l'index étudiants) et 2 Excel (`EtudiantsSheetExport`, `ClasseEtudiantsExport`). Bloc « Aménagements à respecter » en pied de liste d'appel + liste complète classe pour rappeler les adaptations à l'enseignant. Export cohort dédié (PDF + Excel via `ExportableReport` + `<x-pdf-document>` + `<x-export-modal>`, throttle 60/min preview / 10/min download). 5 nouvelles permissions registry : `students.accessibility.view`, `view_full` (description médicale restreinte), `edit`, `export`, `view_own` (étudiant voit son propre profil). Aucun nouveau rôle créé — chaque école attribue ces permissions via `/esbtp/custom-roles`. Defaults : secrétaire (view+edit+export), coordinateur (tout), enseignant (view+export), étudiant (view_own). Sidebar « Accessibilité » sous Étudiants. Entrée API documentée dans `docs/api/STUDENT_ACCESSIBILITY.md`.

### Corrections

- **Analytics — projection cash-flow ne déduisait pas les tranches déjà payées** (`/esbtp/comptabilite/analytics`) — `CashFlowProjectionService` agrégeait le montant brut (`amount`) de chaque ligne d'échéancier au lieu du restant dû (`remaining_amount`). Conséquence : pour les inscriptions ayant déjà soldé une ou plusieurs tranches, la prévision « Recettes prévues le mois prochain » était systématiquement gonflée. Fix une ligne (`CashFlowProjectionService.php:47`) avec fallback défensif sur `amount` si `remaining_amount` n'est pas posé.

### Ajouts

- **Rapports de cours — vue agrégée pour l'administration** (`/esbtp/rapports-cours`) — nouvelle page premium qui liste tous les rapports de cours soumis par les enseignants à la fin du workflow d'émargement (avant cela, seuls les enseignants pouvaient consulter leurs propres rapports — l'administration n'avait aucun moyen de voir cette donnée centralement). Hero KPIs (cette semaine / ce mois / total / comportement difficile ce mois), filtres premium par enseignant, classe, matière, comportement, période + recherche full-text dans le contenu, table paginée avec aperçu (2 lignes), badge sémantique de comportement (excellent/bon/satisfaisant/difficile) et bouton "Voir" qui ouvre une page de détail (5 sections : Contenu enseigné, Méthodes pédagogiques, Difficultés rencontrées, Devoirs assignés, Préparation prochaine séance). Lien retour vers la séance d'origine. Deux nouvelles permissions registry : `session_reports.view` (accordée par défaut à `secretaire` et `coordinateur`, attribuable à n'importe quel rôle custom via `/esbtp/custom-roles`) et `session_reports.view_own` (accordée à `enseignant`). Entrée sidebar "Rapports de cours" sous Présence & Absences. Namespace CSS `rc-*`.
- **Analytics — détection d'anomalies de recouvrement (attendu vs encaissé)** (`/esbtp/comptabilite/analytics`) — nouveau type d'alerte `recouvrement_gap` : compare, mois par mois sur les 6 derniers mois clos, le montant attendu via les échéanciers actifs (`expected`) au montant effectivement reçu (`paid`). Émet une alerte WARNING si l'écart dépasse 30 % et CRITICAL au-delà de 50 % (configurables via `/esbtp/comptabilite/analytics/settings` clé `analytics.anomaly.recouvrement_gap_*`). Les seuils incluent un montant minimal attendu (`min_expected`, défaut 100 000 FCFA) pour éviter le bruit sur petits volumes. Service `App\Services\Analytics\RecouvrementGapService` (per-request scoped + memoization), wired dans `AnomalyDetector` via DI ; aucun god code, méthode détecteur < 30 lignes.
- **Analytics — section visuelle « Recouvrement mois par mois — attendu vs encaissé »** (`/esbtp/comptabilite/analytics`) — graphique CSS pur (sans Chart.js) sur les 6 derniers mois clos, chaque mois affiché sous forme de barre cadre (= attendu) avec remplissage gradient bleu (= encaissé), tonalité orange/rouge si l'écart franchit les seuils configurables, légende explicite + lien vers les paramètres. KPI strip cumul (Attendu / Encaissé / Écart restant / Taux de recouvrement). Namespace CSS `an-gap-*`. Le mois en cours n'est pas affiché (incomplet par nature).

### Améliorations

- **Analytics — projection cash-flow prend en compte les échéanciers** (`/esbtp/comptabilite/analytics`) — le forecast « Recettes prévues le mois prochain » mélange désormais 80 % le montant restant des tranches d'échéancier qui tombent le mois cible et 20 % la saisonnalité historique des encaissements (lissage exponentiel α=0,3 + delta saisonnier additif sur 24 mois). Quand l'historique est trop court (< 6 mois), 100 % échéanciers ; quand aucune règle d'échéancier n'est résolue, 100 % saisonnier. Ligne explicite « Échéanciers actifs pris en compte pour le mois cible » ajoutée à la liste des raisons quand applicable.
- **Analytics — sous-titres descriptifs corrigés** (`/esbtp/comptabilite/analytics`) — la section « Projection cash-flow » annonçait « Modèle saisonnier (Holt-Winters) + régression linéaire sur 24 mois d'historique. » alors que (1) Holt-Winters n'est pas implémenté (le code est un lissage exponentiel single-α + delta saisonnier additif, pas une décomposition trend × seasonal × level itérative), (2) la régression linéaire ne pilote pas le forecast (uniquement la phrase « tendance en hausse/baisse » dans les raisons), et (3) l'apport principal récent — les échéanciers — n'était pas mentionné. Reformulé en « Tranches restantes des échéanciers actifs combinées à la saisonnalité des encaissements (jusqu'à 24 mois d'historique). ». La section « Anomalies » mentionne désormais explicitement le nouvel axe de détection « écart attendu vs encaissé via les échéanciers ».
- **Analytics — refonte premium de la page Analytics Prédictifs** (`/esbtp/comptabilite/analytics`) — hero avec décorations radiales subtiles (deux orbes dégradées flottantes), animation `fadeDown` à l'apparition, KPIs avec micro-lift au hover, cartes avec ombre élévation au survol et bordure qui passe en bleu très clair, valeur cash-flow agrandie (2,35 rem, font-weight 800, letter-spacing négatif). Nouvelle section bar-chart « Recouvrement mois par mois ». Responsive renforcé pour mobile (chart compact, summary 1-col, gap legend wrap). Namespace CSS `an-*` consolidé.

### Améliorations

- **Comptabilité — bascule vers un calcul de retard basé sur échéancier réel** (`/esbtp/comptabilite/dashboard`, `/esbtp/comptabilite/relances`, analytics risque) — introduction d'un moteur de projection des échéances (tranches, dates, délais de grâce, allocation paiements par catégorie) puis recalcul des indicateurs à partir du **retard à date** (`expected_due_to_date - paid_due_to_date`) au lieu du solde annuel brut. Les agrégats aging, recouvrement et signaux de risque étudiant sont désormais cohérents avec les échéances configurées.
- **Comptabilité — nouvelle configuration centralisée des échéanciers** (`/esbtp/comptabilite/config/echeanciers`) — page dédiée pour gérer les tranches de paiement par scope (configuration obligatoire et assignation optionnelle), avec statuts d'affectation (`all`, `affecté`, `réaffecté`, `non_affecté`), priorités, fenêtres d'effet, lignes paramétrables (`percent|fixed`, `days_after_inscription|fixed_mm_dd`) et intégration directe depuis les écrans Frais par classe et Frais optionnels.
- **Gouvernance comptabilité renforcée (architecture + rules IA)** — clarification officielle du socle **multi-instance isolé** (et non multi-tenant logique partagé) dans `CLAUDE.md`, alignement des rules permissions/sidebar/custom-roles sur le vocabulaire instance, et ajout de la rule `.claude/rules/no-god-code-compta.md` pour imposer une architecture anti-GOD-code (controllers minces, extraction Actions/Services, seuils configurables par instance, checklist PR obligatoire).
- **Refonte premium de la modification et de l'affichage d'annonce** (`/esbtp/annonces/{id}/edit` et `/esbtp/annonces/{id}`) — la page Modifier reprend exactement le pattern composer 2 colonnes du Create (namespace `ac-*`) avec un bandeau de statut en tête (chip Brouillon/Publiée/Expirée + chip de priorité) qui informe instantanément du contexte, une zone d'upload qui affiche le fichier actuellement attaché (nom + bouton Ouvrir) au-dessus de la zone « cliquez pour remplacer », des actions de sidebar adaptatives (« Enregistrer les modifications » si déjà publiée, « Mettre à jour le brouillon » plus « Publier maintenant » si encore en brouillon) et une zone sensible avec modal de suppression premium (header gradient rouge, encart titre annonce, message d'avertissement). La page Show passe d'une mise en forme legacy à un hero gradient KLASSCI avec icône carrée 52 px, titre h1, chips d'état (Publiée / Brouillon / Expirée plus Type d'audience plus Priorité Urgente animée plus date d'expiration) et trois actions à droite (Retour, Modifier verrouillable selon la règle « moins de 15 min après publication » du controller, Supprimer). Sous le hero : carte « Message » avec préservation des sauts de ligne, carte « Pièce jointe » cliquable (icône gradient plus nom plus CTA Ouvrir), carte « Destinataires » qui s'adapte au type (chips classes pour type=classe, table avec matricule plus nom plus statut de lecture coloré pour type=etudiant). Sidebar avec carte « Diffusion » (KV type, nb classes ou étudiants, priorité, pièce jointe), carte « Suivi de lecture » conditionnelle (2 stats Lus / Non lus plus barre de progression pourcentage avec gradient bleu, affichée seulement si type=etudiant), carte « Informations » (créée par, créée le, publiée le, expire le, modifiée le). Namespace CSS `aps-*` (Annonce Premium Show). (PR à venir)
- **Refonte premium de la création d'annonce** (`/esbtp/annonces/create`) — formulaire entièrement redessiné en deux colonnes (composer + sidebar sticky) avec namespace CSS `ac-*` aligné sur le design system KLASSCI : carte « Composer le message » avec compteur de caractères live (titre 0/255, contenu sans limite avec teintes warn/danger), zone d'upload pièce jointe avec preview du fichier sélectionné (nom + taille KB), carte « Destinataires » avec trois radio cards monochromes (Tous / Classes / Étudiants nominatifs) et état actif gradient bleu, pickers résumés avec compteur badge live et bouton d'ouverture modal. Les modals « Sélectionner les classes » et « Sélectionner les étudiants » récupèrent un header gradient bleu KLASSCI avec actions glass (« Tout sélectionner », « Vider »), une barre de filtres (filière + niveau pour classes, classe pour étudiants) qui était dormante dans le code JS (jamais rendue dans la vue précédente — `#filiere_filter`, `#niveau_filter`, `#classe_etudiant_filter`, `#select_all_classes`, `#etudiants-info` étaient tous référencés mais introuvables dans le DOM), et un Choices.js entièrement re-stylé (chips bleues arrondies, dropdown sans bordure parasite, hover bleu doux). Sidebar avec carte Actions (notice info brouillon, bouton primaire gradient bleu « Envoyer maintenant », secondary « Sauvegarder », ghost « Réinitialiser »), carte Paramètres de publication (date d'expiration + sélecteur premium d'urgence via `<x-au-select>` au lieu d'un `<select>` natif moche), et carte « Bonnes pratiques » avec trois conseils.

### Corrections

- **Création de paiement — robustesse UX et sélection inscription** (`/esbtp/paiements/create` + API étudiants/inscriptions/solde) — auto-sélection de l'inscription de l'année courante après choix étudiant, message explicite d'auto-sélection, correction des calculs de progression qui pouvaient afficher `NaN%`, et stabilisation de la saisie montant avec suffixe FCFA.
- **Création d'annonce en mode brouillon — erreur SQL « date_publication cannot be null »** (`/esbtp/annonces/create`) — la sauvegarde en brouillon échouait avec une erreur d'intégrité car la vue n'envoyait pas de `date_publication` mais la colonne BDD est `NOT NULL`. Le store met désormais `now()` comme date de création logique en brouillon (sera réécrite à la publication réelle). Le bouton « Sauvegarder en brouillon » fonctionne donc enfin de bout en bout.
- **Annonces — fonctions JavaScript `debugLog` / `debugError` non définies** (`/esbtp/annonces/create`) — appelées dans les handlers de filtrage de Choices.js, elles déclenchaient des `ReferenceError` silencieux qui interrompaient le filtrage classes/étudiants. Définies localement comme alias guarded par `config('app.debug')`.
- **Toast de saisie de notes affichait l'ancienne moyenne** (`/esbtp/notes`) — après modification d'une note, le toast « Note enregistrée — X · moyenne Y/20 » montrait la valeur d'avant la modification (15) au lieu de la nouvelle (14), alors que la cellule moyenne du tableau affichait bien la bonne valeur. Cause : `triggerRowHighlight()` dispatchait l'event `nm:note-saved` AVANT que `calculateStudentAverage()` ait mis à jour la cellule, donc le handler du toast lisait une valeur stale du DOM. Inversion de l'ordre + lecture late de la moyenne dans le `setTimeout` du batch (couvre aussi le cas multi-saves rapides <400 ms).
- **PDF de saisie de notes — refonte avec composant unifié + ZÉRO couleur hardcodée** (`/esbtp/classes/{id}/notes/saisie-rapide/pdf/preview` et variantes par évaluation) — l'ancien template utilisait son propre header bleu hardcodé `#007bff` qui ne respectait ni les paramètres "Couleurs des documents PDF" du tenant ni le branding école. Nouveau template basé sur le composant `<x-pdf-document>` (banner école + filtres + footer paginé automatiques), KPIs premium pleine largeur, table notes propre avec badges/matricule monospace, résumé 2-cards + bandeau d'instructions. Toutes les couleurs (bandeaux, headers tableau, badges, bordures, fonds soft, marqueur ABS) sont calculées via un helper `tint()` à partir des 4 settings configurables : « Fond de l'en-tête » → KPIs et banner, « Texte en-tête » → texte sur bandeaux, « Couleur d'accent » (= `pdf_primary_color`) → titres tableaux + badges + séparateurs + ABS, « Texte principal » → corps + nuances dérivées (bordures, fonds, labels). Aucune transparence rgba() : helper produit des hex composites équivalents pour 100 % de compatibilité DomPDF.
- **PDF — bandeau méta « Référence : <code-tenant> » trompeur supprimé** (composant `<x-pdf-document>`, applique à TOUS les exports : bulletins, certificats, attestations, recouvrement, analytics, paiements, etc.) — le bandeau méta affichait « Référence : ESBTP » (acronyme du tenant) en gros à gauche, ce qui suggérait à tort qu'il s'agissait d'un identifiant de document pertinent alors que c'est juste une constante par établissement déjà visible dans le banner. Suppression de la cellule. Le bandeau garde « Généré le » + « Par : <utilisateur> ».
- **Modal de saisie de notes — en-têtes de tableau illisibles** (`/esbtp/notes`, modal `#classSelectionModal`) — les libellés « Étudiants / Semestre 1 / Semestre 2 / Synthèse » du `<thead>` apparaissaient en blanc fantôme sur fond gris clair (héritage de `color: #fff` depuis un ancêtre du modal). Override explicite des couleurs sur les classes `.nm-period-row th` et `.nm-eval-row th` pour garantir le contraste lecture (rgb(30, 41, 59) sur rgb(238, 242, 247) = ratio ≈ 12:1).

### Sécurité

- **Couverture de non-régression sur les flux paiements critiques (tests)** — ajout d'une suite `tests/Feature/Compta/PaiementCriticalFlowRegressionTest.php` pour verrouiller les garde-fous de routes et sécurité : présence des middlewares de permission/throttle sur create/edit/view/validate/reject/cancel-own, validation stricte du `motif_rejet` (min 10 caractères), et garde anti auto-validation S1.1 (créateur = validateur bloqué, validateur distinct autorisé). Objectif : prévenir les régressions anti-fraude lors des prochains lots comptabilité.
- **Validation server-side des sélections d'audience d'annonce** (`/esbtp/annonces/store` et `/esbtp/annonces/{id}/update`) — le front Choices.js limitait déjà à 20 classes / 50 étudiants, mais le controller acceptait n'importe quelle taille. Ajout `array|max:20` sur `classes` et `array|max:50` sur `etudiants`, plus validation `exists:` sur chaque ID pour bloquer les soumissions forgées. Constantes `MAX_CLASSES` / `MAX_ETUDIANTS` exposées pour rester en phase avec le front.

### Améliorations

- **Saisie de notes plus robuste et productive** (`/esbtp/notes`, modal de saisie) — autosave local automatique à chaque frappe (anti-perte sur coupure réseau, rejouable après reprise via une bannière « Brouillon non sauvegardé détecté »), badge réseau temps réel dans l'en-tête du modal (synchronisé / sauvegarde / hors ligne), raccourcis clavier Excel-like (`Tab`/`Shift+Tab` pour les colonnes, `Enter`/`Shift+Enter` pour la ligne suivante/précédente, `Ctrl+S` pour tout sauvegarder, `Esc` pour défocus, `Ctrl+F` pour la recherche), confirmation premium avant fermeture si des sauvegardes sont encore en cours, autofocus sur la première cellule à l'ouverture du modal avec tooltip raccourcis affiché une seule fois par utilisateur.
- **Refonte UX premium du modal de saisie de notes** (`/esbtp/notes`) — en-tête de tableau collant lors du défilement vertical (les libellés Étudiants / Semestre / Évaluations restent visibles), toast premium discret remplaçant le flash de ligne 2 secondes (« Note enregistrée — Marie Konaté · moyenne 14,25/20 », agrégé si plusieurs notes en moins de 400 ms), toggle absent visuellement clair (Bootstrap form-switch rouge avec libellé « Abs ») qui colore la ligne en rouge léger, italise le nom et ajoute un badge « Absent N/M » dans la cellule étudiant, en-têtes d'évaluation lisibles (titre + barème en bleu + coefficient + bouton crayon discret pour édition rapide via mini modal), barre d'outils tableau avec recherche d'étudiant en direct et compteurs (étudiants / évaluations / visibles), pagination intelligente « Charger 30 étudiants de plus » au-delà de 80 étudiants, indicateur de défilement horizontal (gradient + flèche flottante) qui apparaît si le tableau dépasse, bouton « Créer et continuer » dans le modal de création d'évaluation pour enchaîner plusieurs évaluations sans fermer le formulaire (réinitialise titre/type/description, conserve date/horaires/barème/coefficient/classe/matière).
- **Nouvelle route PATCH `/esbtp/evaluations/{id}/quick-update`** — endpoint dédié pour modifier rapidement uniquement le titre, le barème (1 à 100) et le coefficient (0,1 à 10) d'une évaluation depuis le modal de saisie de notes. Throttle 30 requêtes/min, validation stricte, retour JSON.

### Ajouts

- **Modal premium "What's New" post-connexion** (layout global) — nouvelle modal d'annonce des livraisons avec persistance locale par utilisateur/version : fermeture définitive (`dismissed=true`) ou rappel différé (`remindAt` + 48h). Première édition dédiée aux livraisons comptabilité depuis le 30 avril 2026.
- **Recalcul automatique des bulletins après chaque saisie de note** — toute création, modification ou suppression d'une note déclenche désormais un recalcul asynchrone du résultat de l'étudiant pour la matière concernée (table `esbtp_resultats`) et touche le bulletin associé pour signaler "données sources modifiées". Garantit qu'un bulletin imprimé reflète toujours l'état réel des notes saisies, y compris celles ajoutées après une première génération. Architecture : Observer Eloquent `ESBTPNoteObserver` → Job `RecomputeStudentResultatJob` (queue `default`, 3 retries, dispatch en `afterCommit()`). Calcul indépendant de `BulletinService` (formule locale `SUM((note/bareme)*20*coef) / SUM(coef)` excluant absents et barèmes invalides) pour éviter de bloquer le trigger sur la pré-existence d'une configuration bulletin complète. Badge discret « Bulletin synchronisé · à l'instant » dans le footer du modal `/esbtp/notes` se rafraîchit automatiquement à chaque sauvegarde réussie.
- **Commande `php artisan notes:recompute`** — recalcul batch des résultats par matière depuis les notes courantes, avec filtres `--classe=`, `--matiere=`, `--etudiant=`, `--periode=`, `--annee=`, modes `--queue` (asynchrone) et `--dry-run` (simulation). Pratique après import de notes, migration de données ou audit. L'observer est temporairement muté pendant l'exécution pour éviter les cascades.
- **Table d'audit `esbtp_resultats_recompute_log`** — chaque recalcul écrit une ligne (étudiant, classe, matière, période, année, moyenne avant, moyenne après, source `observer|command|manual`, utilisateur déclencheur, horodatage) pour répondre à « quand cette moyenne a-t-elle changé et qui l'a déclenché ? » sans parcourir l'audit global.
- **Excel bidirectionnel + aperçu impact bulletin temps réel** (`/esbtp/notes`) — nouveau bouton « Exporter Excel » dans le modal de saisie qui télécharge un fichier xlsx ergonomique (1 ligne par étudiant, 1 colonne par évaluation avec barème et coefficient dans le header, freeze pane sur étudiant + nom, autoFilter, matricule en text pour préserver les zéros initiaux, ligne meta cachée pour ré-import). Nouveau bouton « Importer Excel » avec drag-drop premium, modal preview Avant/Après (4 KPIs créer/maj/inchangé/erreur, table colorée par action, validation par cellule barème + matricule + format français), application atomique en transaction. Sous chaque ligne étudiant dans la grille de saisie, un encadré discret affiche désormais en temps réel l'impact d'une note hypothétique sur la moyenne matière, la moyenne générale et la mention CAMES (Très Bien/Bien/Assez Bien/Passable/Insuffisant), debouncé 500 ms. Permission `notes.import_excel` ajoutée au registry pour secrétaire, coordinateur, enseignant et superAdmin. Throttle 10/min export, 5/min dry-run import, 3/min apply import, 60/min preview impact.
- **Journal d'audit étendu à 14 entités critiques + 4 vues premium + composant `<x-entity-history>`** — l'application logge désormais qui crée, modifie ou supprime quoi sur les entités sensibles : Notes, Inscriptions, Étudiants, Évaluations, Classes, Matières, Présences, Frais (catégories / options / abonnements), Utilisateurs, Bulletins, Résultats, Enseignants. Auparavant seuls les Paiements et Factures étaient tracés. Couverture étendue via le package `owen-it/laravel-auditing` et un whitelist par modèle (`$auditInclude`) pour ne tracer que les colonnes métier sensibles. Les changements de paramètres (`Setting`) et les attributions/révocations de rôles & permissions Spatie sont également loggés via observer + listener dédiés.
- **4 vues premium `/esbtp/audit`** — Index (KPIs total / jour / semaine / événements critiques + filtres live + tableau paginé avec chips événement et niveau de risque), Détail (diff field-by-field, métadonnées parsées navigateur+OS, 5 audits liés sur la même entité), Audit comptable (Paiements + Factures avec KPIs financiers), Activité utilisateurs (timeline groupée par jour, top entités, top IPs, distribution horaire 24h, alertes hors heures bureau).
- **Composant Blade `<x-entity-history :model :limit>`** — réutilisable sur n'importe quelle page détail. Timeline des derniers événements avec event chips, diff inline 3 premiers champs modifiés, lien vers détail complet. Gardé par `security.audit.view`. Premier exemple sur la fiche Paiement.
- **Sidebar — nouvelle catégorie "Sécurité & Audit"** — accordion avec 3 sous-liens (Toutes les actions / Audit comptable / Activité utilisateurs). Visible avec `security.audit.view` (Super Administrateur + Service Technique par défaut ; un Super Administrateur peut déléguer cette permission à un autre rôle depuis la page Rôles & Permissions).
- **Exports PDF + Excel du journal d'audit** — formats premium (PDF via `<x-pdf-document>`, Excel avec colonnes formatées). Throttle 5/min sur exports, 100/min sur consultation.
- **Index DB sur `audits`** — ajout de 3 indexes (`created_at`, `event`, `tags`) pour temps de réponse stables au-delà de 10 000 enregistrements.

### Sécurité

- **Throttling complet sur les exports paiements** (`/esbtp/paiements/export-detaille/*` + `/esbtp/paiements/export/*`) — 60 req/min sur les routes preview AJAX (count rapide), 30 req/min sur les preview PDF inline, 10 req/min sur les téléchargements PDF/Excel/CSV (download = opération coûteuse côté serveur DomPDF + PhpSpreadsheet). Avant ce correctif, les routes generate / preview-pdf / export-excel / export-csv / export-pdf / suivi-categories.export étaient sans rate-limit et exposaient le serveur aux scripts d'extraction massive.
- **Throttling sur les endpoints notes** — 30 requêtes/min sur la saisie unitaire (`/esbtp/notes/save-ajax`), 10 req/min sur les saisies en masse (`/esbtp/notes/save-ajax-bulk` et `/esbtp/notes/store-batch`), 60 req/min sur les endpoints AJAX de lecture (étudiants par classe, évaluations par classe/matière). Évite les abus, scripts d'enumeration et erreurs en boucle.
- **Validation backend stricte pour les notes** — refacto en `StoreNoteRequest` + `StoreBulkNotesRequest` (Form Requests) avec autorisation centralisée (permissions `notes.create` / `notes.edit` / `notes.manage_own`), nouvelle règle `App\Rules\NoteRespectsBareme` qui rejette toute note dépassant le barème de l'évaluation, plafond strict de 500 notes par batch, borne haute défensive à 100, validation `commentaire` ≤ 500 caractères, coercion robuste de `is_absent` (boolean au lieu de string). Garde-fou anti division par zéro : barème de l'évaluation rejeté s'il est ≤ 0.
- **Validation barème + coefficient sur les évaluations** — `bareme` strictement > 0 (entre 0.1 et 100), `coefficient` borné entre 0.1 et 10 (déjà strict en création, désormais aussi en modification), `duree_minutes` borné entre 1 et 480 minutes (8h max). Empêche la création d'évaluations qui casseraient ensuite le calcul de moyenne.
- **Filtre `workflow_step = etudiant_cree` sur la liste des étudiants pour saisie de notes** — la modal de saisie via `/esbtp/notes`, les pages de saisie rapide (web + PDF vierges) ainsi que les pages détail/PDF d'une évaluation (`ESBTPEvaluationController`) n'affichent plus les étudiants en pré-inscription / prospect. Évite la création accidentelle de notes orphelines pour des étudiants qui ne sont pas encore officiellement inscrits.

### Améliorations

- **Transaction DB sur la saisie de note unitaire** — `saveNoteAjax` est désormais wrappé dans `DB::transaction(...)` (le bulk l'avait déjà). Garantit l'atomicité face aux Observer / triggers de recalcul de moyennes en cascade. Les notifications d'absence sont envoyées après le commit (évite les rollbacks en cascade en cas d'échec d'envoi).

### Corrections

- **CRITIQUE — calcul de moyenne dans la saisie de notes** (`/esbtp/notes`) — la moyenne par étudiant affichée côté UI excluait silencieusement les notes 0 légitimes. Une enseignante saisissait 10 et 0 et voyait 10 au lieu de 5. Cause : la fonction JS `calculateStudentAverage()` filtrait `noteValue > 0` au lieu de `rawValue !== ''`, ce qui rejetait à la fois les cellules vides ET les notes 0. La fonction sœur `calculateClassAverages()` du même fichier utilisait le bon filtre, ce qui créait une divergence entre la moyenne par étudiant (fausse) et la moyenne par évaluation (correcte). Algo aligné sur le bon filtre + ajout d'un garde-fou contre les barèmes invalides (≤ 0).

- **CRITIQUE — moyennes des bulletins non normalisées par barème** — `BulletinService::genererDonneesBulletin()` calculait la moyenne par matière en multipliant directement la note brute par le coefficient (`note × coef`), sans la normaliser sur 20 lorsque le barème de l'évaluation différait de 20. Conséquence : un étudiant ayant 15/30 dans une éval (équiv. 10/20) et 10/20 dans une autre voyait sur son bulletin officiel (15+10)/2 = 12.5 au lieu de la vraie moyenne (10+10)/2 = 10. Refactor pour capturer barème + flag `is_absent` dans la structure de notes et déléguer le calcul à une nouvelle méthode pure `computeMoyenneFromNotesData()` qui normalise systématiquement chaque note sur 20, exclut les absences et résiste aux barèmes invalides.

- **Bug accents en majuscules dans les titres PDF** — les exports comptabilité affichaient « TABLEAU DéTAILLé DES PAIEMENTS » au lieu de « TABLEAU DÉTAILLÉ DES PAIEMENTS ». La fonction PHP `strtoupper()` ne gère pas correctement les caractères UTF-8 (les accents `é`, `à`, `è`, etc. ne sont pas convertis en majuscules). Remplacement par `mb_strtoupper(..., 'UTF-8')` sur les 3 templates PDF concernés (recouvrement, analytics, paiements détaillés). Effet collatéral : les badges de statut (Validé / Rejeté / Annulé) et les libellés de niveau de risque sont également correctement majuscules.

### Ajouts

- **Commande d'audit `php artisan notes:audit-divergence`** — détecte les bulletins/résultats matière dont la moyenne persistée diverge de la moyenne recalculée avec la nouvelle logique (post-fix barème). Lecture seule par défaut, options `--classe=ID` pour cibler une classe, `--export-csv=path.csv` pour un rapport complet, écarts > 0.5 affichés en rouge, eager-load anti N+1, chunkById(500) pour gros volumes. Sortie résumée avec écart max et nombre de divergences significatives. Le recompute des moyennes persistées sera traité dans une PR séparée.

### Améliorations

- **Service unifié de calcul des moyennes** — extraction de toute la logique de calcul (moyenne par matière, moyenne générale pondérée, moyenne classe par évaluation, moyenne classe par matière, moyenne UE / semestre LMD pondérée par crédits ECTS, crédits validés, mention CAMES) dans un service `NoteCalculationService` consommé par les modules BTS et LMD pour garantir la cohérence des calculs sur tous les écrans (preview impact, bulletins, exports, saisie temps réel). 28 tests unitaires verrouillent les invariants algorithmiques (inclusion des notes 0, normalisation par barème, exclusion des absences, garde-fou contre barèmes invalides, arrondi 2 décimales). 10 cas tests référencés comme contrat JS↔PHP dans `tests/Feature/Notes/JsPhpCalculationConsistencyTest.php` à reproduire à l'identique côté JS pour garantir la cohérence saisie temps réel ↔ bulletin officiel. Documentation complète dans `docs/architecture/note-calculation-service.md` listant les consommateurs déjà migrés (`previewImpact`) et la dette technique tracée (`BulletinService`, `LMDBulletinService`, fonctions JS) à migrer dans des PRs successives.

- **Aperçu PDF universel** — bouton « Aperçu PDF » ajouté à côté de chaque bouton « Télécharger PDF » dans toute l'application : bulletins (BTS et LMD), évaluations, certificats de scolarité, attestations de fréquentation, rapports de présence, emplois du temps, situation financière, saisies rapides de notes. Ouvre le PDF inline dans un nouvel onglet du navigateur sans téléchargement, pour vérification avant impression. 11 nouvelles routes `*.preview-pdf` (throttle 60/min). Composant Blade `<x-pdf-actions>` réutilisable. Refactor SOLID : extraction de méthodes privées `buildXxxPdf()` retournant l'objet DomPDF pour éviter la duplication entre download et preview.

- **Refonte premium des 3 PDFs comptabilité** (Recouvrement quotidien, Analytics financiers, Tableau détaillé des paiements) — adoption du pattern canonique `liste-complete-pdf` avec header 2 colonnes (logo carré 18 % fond bleu primaire | nom école + contact + titre document 82 %), KPI bar pleine largeur fond primaire, footer summary 2 colonnes (résumé statistique / infos document) et nouvel emplacement signature & cachet spacieux (hauteur configurable via setting `pdf_signature_height`, défaut 80 px) avec bordure pointillée et zone visa comptabilité. Toutes les couleurs proviennent de `SettingsHelper::getPdfSettings()` (header_bg, primary, secondary, text). Respect du nouveau toggle `pdf_show_generator_name` partout (footer généré + carte « Document » + section signature).

- **Customisation PDF par tenant avec aperçu en nouvelle tab** (`/esbtp/settings` tab PDF) — chaque école peut désormais ajuster la mise en page de ses exports PDF : taille du logo, marges (4 côtés en mm), texte personnalisé du pied de page, toggle pagination, toggle mention « Directeur », filigrane (texte + opacité 2-30 % + rotation -90° à 90°). Bouton « Aperçu PDF (nouvelle tab) » qui génère un document de démonstration représentatif (KPIs + tableau étudiants + totaux) avec les paramètres en cours d'édition, sans persister. Le composant `<x-pdf-document>` accepte un prop `$overrides` qui se merge avec les settings sauvegardées, ce qui rend l'aperçu strictement fidèle au rendu final. Permission `settings.pdf.manage` (registry) pour les `secretaire` et `superAdmin`.

### Migrations DB

- **Fusion `esbtp_enseignant_profiles` dans `esbtp_teachers`** — ajout de 6 colonnes à `esbtp_teachers` : `regime` (enum vacataire/permanent/consultant), `taux_horaire`, `date_debut_activite`, `diplome_principal`, `universite_diplome`, `annee_diplome`. La data utile de `esbtp_enseignant_profiles` est copiée via UPDATE INNER JOIN (idempotent : `COALESCE` préserve les valeurs déjà saisies). `type_contrat` et `statut_emploi` consolidés en un seul champ `regime`. Drop des tables `esbtp_enseignant_profiles`, `esbtp_enseignant_disponibilites`, `esbtp_enseignant_affectations` (tables ambitieuses jamais réellement alimentées par l'UI moderne).

### Améliorations

- **Mono-write côté contrôleur enseignant** — fin du `Schema::hasTable('esbtp_enseignant_profiles')` defensif et du double-write `DB::table('esbtp_enseignant_profiles')->insert/update`. `ESBTPEnseignantController::store/quickStore/update/edit` lit/écrit directement sur `ESBTPTeacher`. Suppression des helpers `regimeToContrat()` et `normalizeRegime()`. Vues `show.blade.php` et `edit.blade.php` : références `$profileData->X` remplacées par `$teacher->X`. KPI « Expérience » remplacé par KPI « Régime ». Section « Motivation & Objectifs » de la fiche profil supprimée (champs jamais alimentés).

### Suppressions

- **Cleanup zombies du domaine enseignant** — suppression des controllers `TeacherAdminController` (legacy non-ESBTP), `App\Http\Controllers\ESBTP\Admin\TeacherAdminController` (UI admin parallèle), `ESBTPEnseignantProfileController` (ancien système de profils), `ESBTPDepartmentController` (concept hors scope KLASSCI). Suppression des modèles `ESBTPEnseignantProfile`, `ESBTPEnseignantAffectation`, `ESBTPEnseignantDisponibilite` (jamais réellement utilisés par l'UI moderne). Suppression des dossiers de vues `esbtp/departments/`, `esbtp/teachers/` (ancienne UI parallèle), `superadmin/teachers/`. Suppression du lien « Départements » dans la sidebar et des routes correspondantes (`esbtp.departments.*`, `esbtp.teachers.*` admin namespace, `esbtp.enseignants.profiles.*`). Mise à jour de `User.php` : retrait des relations Eloquent legacy `hasOne(Teacher)` et `hasOne(ESBTPEnseignantProfile)`. Mise à jour de `ESBTPTeacher` : retrait des champs `department_id` et `laboratory_id` du fillable et des relations `department()` / `laboratory()`. Repointage de la relation `ESBTPResultat::enseignant()` vers `User`. Recherche globale (`SearchController`) et résultats (`search/results.blade.php`) : liens `esbtp.teachers.*` remplacés par `esbtp.enseignants.*`.

### Améliorations

- **Refonte des pages création / modification d'enseignants** (`/esbtp/enseignants/create` et `/esbtp/enseignants/{id}/edit`) — abandon du wizard 4 étapes au profit d'un formulaire premium en une seule page (namespaces `ec-*` et `ee-*`). Trois champs requis seulement pour démarrer (Nom complet, Téléphone, Spécialisation), une section « Régime d'engagement » avec radio cards (Vacataire / Permanent / Consultant) et champs conditionnels (taux horaire pour les vacataires, charge hebdomadaire pour les permanents), et un panneau « Profil détaillé » pliable pour les diplômes et grade. La date d'embauche devient « Date de début d'activité », optionnelle et pré-remplie à la date du jour.

### Suppressions

- **Champs hors-contexte sur la fiche enseignant** — retrait de Laboratoire (école BTS/Licence sans labos de recherche), Motivation et Objectifs pédagogiques (références à des templates de candidature RH étrangers à l'usage admin), Méthodes d'enseignement et Outils pédagogiques (paramètres jamais affichés ailleurs), upload CV et photo (à venir via upload progressif sur la fiche). Le filtre et la colonne « Département » sur la liste, ainsi que les pills département/laboratoire dans les fiches, sont également retirés.
- **Page de debug `/esbtp/enseignants/{id}/debug-result`** — vue, route et méthode `debugResult` supprimées du flux de modification ; la mise à jour redirige désormais vers la fiche profil avec un simple message de succès.

### Corrections

- **Création d'enseignant sans dépendance au modèle legacy `Department`** — suppression du hook `ESBTPTeacher::boot::creating` qui pré-remplissait `department_id` via `Department::first()` (table legacy `departments`) alors que la FK pointait vers `esbtp_departments`, source potentielle de violations silencieuses de FK. Les relations `department()` et `laboratory()` du modèle pointent désormais vers les modèles ESBTP.
- **Logs de debug en production retirés** du contrôleur `ESBTPEnseignantController` (`updateAvailability`, `prepareAvailabilityData`, `update`) — plus de `\Log::info("🔧 DEBUG ...")` ni de `error_log()` à chaque interaction.

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

### Corrections

- **Crash PDF de l'export détaillé des paiements** (`/esbtp/paiements/export-detaille`) — l'erreur DomPDF `Call to a member function get_cellmap() on null` provoquée par une `<table>` imbriquée dans un `<td>` est corrigée. La rangée méta (Lignes / Date / Total) est désormais une table sœur du bandeau d'en-tête, sans changer le rendu visuel.
- **Export Excel détaillé en .xlsx natif** — le service `PaiementExportService::exportExcel()` produit maintenant un vrai `.xlsx` via `maatwebsite/excel` (en-tête bleu KLASSCI, badges colorés sur le statut, format monétaire FCFA, freeze pane, autofilter, ligne TOTAL, résumé filtres) au lieu d'un CSV. Fallback automatique vers CSV UTF-8 BOM en cas d'erreur PhpSpreadsheet.
- **Sélection des classes et étudiants en Select2 premium** sur le formulaire d'export — la sélection multi-classes utilise désormais Select2 avec recherche, tags premium (chips bleu KLASSCI), bouton X par tag pour retirer, et templateResult avec icône + nom + filière. La recherche d'étudiants passe en Select2 AJAX (3 caractères min). Bouton « Réinitialiser tous les filtres » et bouton « Effacer » sur les modes de paiement.

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
