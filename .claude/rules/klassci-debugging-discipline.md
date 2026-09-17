# Rule: Debugging Discipline — Pièges KLASSCI documentés

## Quand s'active

Cette rule s'active quand tu :
- Modifies un controller et vois que tes changements ne prennent pas effet
- Modifies un service et obtiens un 500 sans cause apparente
- Ajoutes du logging et il n'apparaît pas dans les logs
- Lances un visual check qui timeout
- Diagnostiques une page qui rend des données incohérentes
- Recommandes d'éditer un fichier sans avoir d'abord vérifié qu'il est bien sur le chemin actif

## Le principe

**Avant d'éditer un fichier, vérifie qu'il est sur le chemin actif.** Ne pars JAMAIS de l'hypothèse « ce nom de fichier ressemble au domaine donc c'est le bon ». KLASSCI a un historique d'évolution où plusieurs controllers/services nommés similairement coexistent. La vérification prend 30 secondes, faute coûte des heures.

---

## Pièges fondateurs (incidents réels avec heures perdues)

### Piège #1 — Controller/Route mismatch : 3h perdues (juin 2026)

**Symptôme** : J'ai édité `ESBTPEtudiantController::index()` pour fixer un filtre Réinscription groupée. Après 3 itérations de deploy + dev-browser tests, le filtre ne prenait jamais effet. Les logs `Log::error('DIAG_BULK_ELIGIBLE')` n'apparaissaient JAMAIS dans les logs malgré 10+ rechargements de page.

**Cause** : La route `/esbtp/etudiants` est servie par `ESBTPStudentController` (avec **S**), pas `ESBTPEtudiantController`. Définition explicite à `routes/web.php` ligne 2006 :
```php
Route::resource('esbtp/etudiants', ESBTPStudentController::class, ['as' => 'esbtp'])
```

Les deux controllers ont des noms quasi-identiques. Le mauvais avait une logique similaire avec `$etudiantsForBulk`, ce qui renforçait l'impression de bon fichier.

**Workflow correct AVANT d'éditer un controller** :
```bash
# 1. Identifier la route qui sert la page
grep -E "Route::.*'(esbtp/etudiants|/etudiants)'\b" routes/web.php

# 2. Ou via artisan
php artisan route:list --path=esbtp/etudiants

# 3. Confirmer le controller mappé AVANT toute édition
```

**Anti-pattern à BLOQUER** : éditer un fichier en se basant uniquement sur le nom (ESBTPEtudiantController vs ESBTPStudentController vs ESBTPStudentNewController).

**Signal d'alarme à respecter immédiatement** : si tes logs `Log::error('UNIQUE_MARKER')` n'apparaissent pas après deploy + cache:clear, **NE CONTINUE PAS à patcher**. Le fichier édité n'est PAS sur le chemin actif. Cherche le vrai controller AVANT toute autre action.

---

### Piège #2 — Service method visibility : 500 silencieux

**Symptôme** : `BulkReinscriptionService::preview()` retournait 500 sans message clair. Le front catch fallback à `step='select'`, donnant l'impression "la liste se recharge" sans erreur visible côté UX.

**Cause** : Appel à `ReeinscriptionService::calculerSoldeInscription()` qui était `private`. PHP throw `Error: Call to private method`.

**Règle** : Quand un service A doit appeler une méthode d'un service B, vérifie la visibilité (`public`/`private`/`protected`). Si la méthode est `private` mais doit être réutilisée, la rendre `public` avec un commentaire d'usage public.

**Workflow correct** : 
```bash
# AVANT d'écrire un appel cross-service
grep "function methodName" path/to/OtherService.php
# Vérifier la visibilité (public/private/protected)
```

---

### Piège #3 — Filename case sensitivity (Windows ↔ Linux)

**Symptôme** : Un commit avec `git add path/to/Api/CLI/File.php` ne tracke pas le fichier. Sur Linux yakro, la méthode ajoutée renvoie `Method does not exist`.

**Cause** : Windows filesystem est case-insensitive, Linux est case-sensitive. Le repo a `API` (caps) dans le path. Adding via `Api` (PascalCase) ne match aucun fichier git-tracked, donc le diff est vide.

**Vérification** :
```bash
git ls-files | grep -i controllername  # listing case-réel git-tracked
```

**Toujours** utiliser la case exacte du repo dans les commandes `git add`.

---

### Piège #4 — Log level production filter

**Symptôme** : `Log::info('marker')` n'apparaît pas dans les logs `level=info` même après hit de la page concernée.

**Cause** : Configuration prod peut filter INFO/DEBUG. `Log::error()` passe toujours.

**Pour debug live** : utilise `Log::error('DIAG_MARKER', [...])` temporairement. Cleanup après :
```php
// AVANT cleanup
\Log::error('DIAG_BULK_ELIGIBLE', ['count' => $x->count()]);

// APRÈS validation : remove ou converti en Log::info si vraiment utile en prod
```

---

### Piège #5 — dev-browser timeout 30s sur yakro

**Symptôme** : Scripts dev-browser timeout systématique à 30s sur yakro (serveur LWS lent).

**Stratégie** :
- **Chaîner** les opérations dans 1 seul `evaluate()` pour réduire round-trips
- **Réutiliser la session** : `browser.getPage('check')` réutilise le contexte → évite re-login + re-navigation
- **Vérifier l'état avant nav** : si la page est déjà sur la bonne URL, skip `page.goto()`
- **Avoid waitForLoadState('networkidle')** : peut bloquer indéfiniment, use `setTimeout` fixe
- **Test multi-étapes** : split en plusieurs scripts qui partagent la session, plutôt qu'un mega-script

---

### Piège #6 — Bulletin snapshot drift (esbtp_bulletins.moyenne_generale)

**Symptôme** : Marcel signale "Officiel 11.45 / Courant 12.15 / Delta +0.57" alors qu'il pense n'avoir rien changé.

**Cause** : `esbtp_bulletins.moyenne_generale` est un **snapshot figé** au moment de la génération. La page PDF preview recalcule live (utilise courant). Donc :
- Le snapshot DB peut être stale après modifs sur notes/évaluations
- Le PDF montre la valeur live (correcte)
- L'alerte UI affiche la stale snapshot vs courant

**Règle** : **JAMAIS** mettre à jour `moyenne_generale` automatiquement. C'est un snapshot officiel signé. Seule la régénération explicite par l'utilisateur le met à jour.

---

### Piège #7 — Notes denormalization stale après changement éval

**Symptôme** : Modifier la matière d'eval#622 fait apparaître ancienne matière dans page résultats étudiant (« Matière inconnue » + moyenne 10.50).

**Cause** : `esbtp_notes` a ses propres colonnes dénormalisées `matiere_id`, `classe_id`, `semestre`. Le hook `saving()` sync seulement quand on save la NOTE, pas quand on save l'éval parente. Les notes ne propagent pas auto.

**Solution** : 
1. `ESBTPEvaluationController::update()` capture old values via `getOriginal()` puis bulk update notes si classe/matiere/periode changé
2. Commande `php artisan evaluations:sync-notes [--evaluation=ID] [--clean-resultats]` pour réparer le legacy
3. `--clean-resultats` détecte aussi les `esbtp_resultats` orphelins (broken matiere_id OU pas de notes correspondantes)

---

### Piège #8 — whereDoesntHave + global scopes + soft delete

**Symptôme** : Filtre `whereDoesntHave('inscriptions', N)` semble laisser passer des étudiants qui ont une inscription N.

**Cause** : Cherche d'abord côté **route mismatch** (#1) AVANT de soupçonner Eloquent. Eloquent est généralement correct. Le 99% des cas où ça « ne filtre pas » est que le code édité n'est pas exécuté.

**Vérification rapide** : ajoute `Log::error('FILTER_DIAG', ['count' => $query->count()])` avant le get(). Si le log n'apparaît jamais, tu édites le mauvais fichier (cf #1).

---

### Piège #9 — Bootstrap data-bs-strategy IGNORÉ

**Symptôme** : Set `data-bs-strategy="fixed"` sur trigger dropdown, mais Popper utilise toujours `position: absolute`.

**Cause** : Bootstrap 5.3 N'A PAS d'option `strategy` dans son `Default` (vérifié dans le source `Dropdown.js`). L'attribut HTML est ignoré.

**Solution canonique** : monkey-patch `bootstrap.Dropdown.prototype._getPopperConfig` pour forcer strategy:'fixed' :
```js
const orig = bootstrap.Dropdown.prototype._getPopperConfig;
bootstrap.Dropdown.prototype._getPopperConfig = function() {
    const config = orig.apply(this, arguments);
    if (config) config.strategy = 'fixed';
    return config;
};
```

`window.Popper` n'est PAS exposé par bootstrap.bundle (Popper est interne). Le monkey-patch sur `Dropdown.prototype` est la VRAIE façon.

---

### Piège #10 — CSS containing block pour position:fixed

**Symptôme** : Dropdown menu apparaît à des centaines de pixels du trigger.

**Cause** : Un ancestor du `.dropdown-menu` a `transform`, `filter`, `backdrop-filter`, `perspective`, `contain: paint`, ou `will-change` non-`none`. Cela crée un *containing block* pour les descendants `position: fixed`.

**Voir** : `.claude/rules/universal-dropdowns.md` section « Piège critique #2 ».

**Anti-pattern** : `[card]:hover { transform: translateY(-2px) }` quand la card contient un dropdown.

---

### Piège #11 — Blade `@json([multiligne])`

**Symptôme** : `Unclosed '[' on line N does not match ')'` au runtime.

**Cause** : Le parser Blade match mal `@json([` multiligne. Doit utiliser variable intermédiaire.

**Pattern correct** :
```blade
@php
    $myData = [
        'key1' => $val1,
        'key2' => $val2,
    ];
@endphp
<div data-payload='@json($myData)'>
```

**Voir** : `.claude/rules/blade-pitfalls.md` Pitfall #4.

---

### Piège #12 — Colonne sélectionnée dans un `->get([...])` / `select()` mais JAMAIS migrée (500 `Unknown column`)

**Symptôme** : un endpoint (souvent API CLI ou un eager-load) renvoie un **500** `SQLSTATE[42S22] ... Unknown column 'xxx' in 'SELECT'`. Le bug est INVISIBLE en lecture de code (la colonne « a l'air » légitime) et ne se déclenche qu'au runtime, uniquement quand la requête retourne des lignes.

**Incident fondateur (juin 2026)** : `klassci students:show esbtp-abidjan 2743` → 500 `Unknown column 'note_rattrapage' in esbtp_notes`. `CLIStudentController.php:160` faisait `->get(['id','matiere_id','evaluation_id','note','note_rattrapage','is_absent'])` sur `esbtp_notes`. Or :
- AUCUNE migration n'ajoute `note_rattrapage` à `esbtp_notes` (la seule migration `note_rattrapage` cible `esbtp_lmd_resultat_ecue`, table LMD totalement différente) ;
- la colonne est **absente du `$fillable`/casts du modèle `ESBTPNote`**.
→ Copier-coller depuis le contexte LMD (rattrapage = concept ECUE-level LMD, pas note BTS brute). Cassait `students:show` pour **tout étudiant ayant une note sur l'année courante, sur TOUS les tenants** (le CLI parle à l'app déployée).

**Règle** : avant d'ajouter un nom de colonne dans un `select()` / `->get([...])` / `->pluck()`, vérifier qu'une migration la crée RÉELLEMENT sur CETTE table :
```bash
grep -rln "nom_colonne" database/migrations/   # doit matcher une migration sur LA bonne table
grep -n "nom_colonne" app/Models/LeModele.php  # idéalement dans $fillable/casts
```
Si la colonne est un concept d'une autre entité (ex: rattrapage LMD vs note BTS), ne PAS la sélectionner sur la table voisine.

**Variante `nom` vs `name` (récurrente dans `CLIStudentController`)** : `esbtp_etudiants` utilise bien `nom` (français, nom de famille), MAIS `esbtp_matieres` / `esbtp_filieres` / `esbtp_niveaux_etudes` utilisent `name`. Les eager-loads `->with('matiere:id,nom')` / `->with('filiere:id,nom')` lèvent `Unknown column 'nom'`. Toujours vérifier le `$fillable` de CHAQUE modèle ciblé : `grep "'nom'\|'name'" app/Models/ESBTPXxx.php`. Bugs corrigés en série (commit `6f18f30b` filiere/niveau, puis juin 2026 matiere) — vérifier les 3 d'un coup quand on touche un eager-load de ce controller.

**Variante `phone` vs `telephone` (sept. 2026)** : `users` porte **`phone`**
(migration `add_contact_info_to_users_table`, mars 2025). `telephone` n'existe ni
en base ni sur le modele `User` — mais `esbtp_etudiants` et `esbtp_parents`, eux,
portent bien `telephone`. D'ou la confusion. Deux degats distincts, et le second
est le plus vicieux :

- `->get([... 'telephone' ...])` sur `users` leve `Unknown column` ;
- `$user->telephone` en lecture ne leve RIEN : Eloquent rend `null`. Vingt
  lectures de ce genre dans `personnel/unified-index.blade.php` masquaient les
  numeros de tout le personnel sur toutes les instances, sans erreur.

**Ce cas illustre pourquoi un `catch` muet coute cher.** Le `SELECT` fautif ne
s'executait que sur les instances ayant des roles personnalises, et l'exception
tombait dans un `catch (\Throwable)` qui vidait silencieusement trois
collections : les trois cartes de gestion des roles disparaissaient d'un coup,
sans message a l'ecran ni ligne au journal. Le defaut a survecu jusqu'a ce qu'un
utilisateur le signale. **Un rattrapage qui degrade l'affichage doit toujours
journaliser ce qu'il a rattrape** — sinon on ne cherche meme pas.

**Diagnostic** : un endpoint API qui 500 mais dont le code « semble bon » → reproduire avec le CLI (`klassci <cmd> <tenant>`) qui RENVOIE le message SQL exact, bien plus parlant que le 500 web générique. Le CLI est un excellent révélateur de schema/colonnes.

---

### Piège #13 — Snapshot live (notes brutes) vs table agrégat (`esbtp_resultats`) : « Aucune note » trompeur

**Symptôme** : sur `/esbtp/etudiants/{id}` onglet Académique, le **Bilan** affiche une Moyenne/Rang/Mention (ex: 10.64, 9/25, Assez Bien) MAIS la section détaillée juste en dessous dit « Aucune note enregistrée pour cette année ». Contradiction apparente alors que des notes existent bien en DB.

**Cause** : deux sources de vérité différentes dans la même page.
- Le **Bilan** (KPIs moyenne/rang/mention) est calculé EN LIVE depuis `esbtp_notes` via `BtsCurrentResultSnapshotService::getAnnualSnapshot()` (retourne `state`, `effective_total`, `semester_snapshots[*].subjects`).
- La **section détaillée** (liste matières par semestre) lit la table AGRÉGAT `esbtp_resultats` (`show.blade.php` ~3300, `$acadResultatsRef` → `$acadSemestres`). Or `esbtp_resultats` n'est peuplé qu'à la **génération du bulletin**. Tant qu'aucun bulletin n'est généré, l'agrégat est vide → « aucune note », même si les notes brutes existent.

**Fix canonique (validé Marcel juin 2026)** : fallback PROVISOIRE.
- Si `esbtp_resultats` (et bulletins) vides MAIS le snapshot live a des `subjects` → afficher le détail calculé depuis les notes, **explicitement marqué « Provisoire »** (notes saisies, bulletin non encore généré).
- Dès qu'un bulletin / `esbtp_resultats` existe → il **PRIME** (override officiel) ; le fallback est ignoré.
- Implémentation : `$acadLiveSemestres` construit depuis `$btsAnnualSnapshot['semester_snapshots']` seulement si `$acadBuls->isEmpty() && $acadSemestres->isEmpty()`, rendu via une branche `@elseif($acadLiveSemestres->isNotEmpty())` AVANT le `@else` « Aucune note ».

**Règle générale** : quand un KPI agrégé et une liste de détail divergent, vérifier qu'ils lisent la MÊME source. Un bilan « live » + un détail « agrégat figé » divergent toujours tant que l'agrégat n'est pas régénéré. Cf. Piège #6 (snapshot `moyenne_generale` figé) et Piège #7 (dénormalisation stale).

---

### Piège #14 — `heure_debut` / `heure_fin` rendent « 2026- » au lieu de « 08:00 »

**Symptôme** : un horaire s'affiche « 2026-09-15 08:00:00 » là où on attend « 08:00 », ou pire —
avec un `substr(..., 0, 5)` — **« 2026- »**, sans aucune erreur.

**Cause : un ACCESSEUR, pas le cast.** `ESBTPSeanceCours` déclare
`getHeureDebutAttribute()` / `getHeureFinAttribute()` qui font `Carbon::parse($value)`. L'attribut
rend donc un `Carbon` **daté d'aujourd'hui**, pas la chaîne « 08:00:00 », et toute lecture en
contexte chaîne y lit la DATE.

```php
$s->heure_debut              // Carbon\Carbon
(string) $s->heure_debut     // "2026-09-15 08:00:00"
substr($s->heure_debut, 0,5) // "2026-"     ← et non "08:00"
```

**Le modèle porte AUSSI un cast `'datetime'` sur ces colonnes, et ce cast ne sert à rien** :
`transformModelValue()` consulte l'accesseur **avant** le cast. Débrancher le cast en laissant
l'accesseur ne change donc strictement rien — c'est la première fausse piste, et la plus coûteuse
parce qu'elle a l'air de marcher jusqu'au test.

⚠️ `'datetime:H:i'` **ne corrige rien non plus, `toArray()` compris** : `addCastAttributesToArray()`
saute les attributs mutés, donc `toArray()` rend l'objet `Carbon` brut. Tant que l'accesseur est là,
**aucun** réglage de cast n'a d'effet ; c'est l'accesseur qu'il faudrait retirer, et alors seulement
le cast reprendrait la main.

**Le fix** : `->format('H:i')` pour afficher, ou `$s->getAttributes()['heure_debut']` pour la valeur brute.

Le contrôle qui tranche, à rejouer plutôt qu'à croire :

```php
$s = new \App\Models\ESBTPSeanceCours();
(new ReflectionMethod($s, 'hasGetMutator'))->invoke($s, 'heure_debut');  // true → l'accesseur gagne
```

**L'inventaire s'est trompé quatre fois — et la quatrième dans l'AUTRE sens.** Le relevé du
15 septembre citait **cinq** sites ; écrire la commande de contrôle et la lancer en a sorti **deux
de plus**, d'où « six » (le septième étant mort). Cette phrase-là a tenu deux jours : le
17 septembre, une revue adverse en a trouvé **trois autres bien vivants** — deux sur l'écran des
codes de présence, un sur la fiche matière — qu'aucune version du contrôle ne pouvait voir, parce
qu'il ne cherchait que `substr(…)` et la concaténation, jamais l'affichage nu `{{ $s->heure_debut }}`.

Puis, en élargissant le contrôle, **deux « sites » de plus sont sortis — et c'étaient deux faux**.
Un message d'absence et l'écran « mes absences » de l'étudiant : le contrôle les a signalés, je les
ai « corrigés » en posant `->format('H:i')`, et **j'ai cassé les deux**. Ils lisent un
`ESBTPAttendance`, **qui n'a ni accesseur ni cast sur ses heures** : l'attribut y est la chaîne
brute `'08:00:00'`, le code d'avant était juste, et `format()` sur une chaîne lève une `Error` —
que le `catch (\Exception)` alentour ne rattrape pas, puisque `Error` ne descend pas d'`Exception`.
L'écran des absences serait tombé en 500 pour tout étudiant ayant une absence horodatée.

**C'est la même erreur que les trois précédentes, retournée** : j'ai pris le silence du détecteur
pour une preuve d'absence, puis son signalement pour une preuve de présence. Un tamis ne prouve
rien dans un sens comme dans l'autre. **Avant de corriger un site signalé, ouvrez le modèle qu'il
lit** — le tableau ci-dessous porte une colonne pour ça, et son absence est exactement ce qui a
permis les deux casses.

**Dix sites vivants** sont corrigés à ce jour, plus un repli mort retiré. Tous lisent un
`ESBTPSeanceCours` ; aucun autre modèle n'est concerné.

**Ce chiffre a été faux quatre fois de suite** — cinq, puis six, puis neuf, puis dix — et chaque
version a été publiée comme définitive. Lisez-le donc pour ce qu'il est : le nombre de sites trouvés
à ce jour, pas le nombre de sites existants. Le dixième a été trouvé par une revue adverse, dans le
fichier même que la version précédente de ce tableau déclarait couvert « (×2) ».

| fichier | ce que l'utilisateur voyait | modèle lu | trouvé par |
|---|---|---|---|
| `resources/views/esbtp/seances-cours/index.blade.php` (×2) | colonne horaire, confirmation de suppression | `ESBTPSeanceCours` | lecture |
| `app/Domain/EmploiTemps/DetectionDesConflits.php` → bandeau de `seances-cours/index` | **« — 2026-09-17 08:00:00 à 2026-09-17 10:00:00 » dans le panneau de conflits** | `ESBTPSeanceCours` | revue adverse |
| `app/Http/Controllers/ESBTPAttendanceController.php` (×2) | **« Heure: 2026- » dans l'avis d'absence au parent**, export CSV | `ESBTPSeanceCours` (via `->seanceCours`) | lecture |
| `app/Http/Controllers/ESBTPPlanningGeneralController.php` | `"horaire"` du planning général | `ESBTPSeanceCours` | lecture |
| `resources/views/teacher/attendance.blade.php` | **« 2026- - 2026- » sur l'écran d'appel** | `ESBTPSeanceCours` | 1ᵉʳ contrôle |
| `resources/views/esbtp/attendance/generate-code.blade.php` (×2) | code de présence : carte du code actif, codes récents | `ESBTPSeanceCours` (via `->seance`) | revue adverse |
| `resources/views/esbtp/matieres/show.blade.php` | séances de la fiche matière | `ESBTPSeanceCours` | revue adverse |

Et un onzième, `ESBTPSeanceCoursController` (`(int) substr($session->heure_debut, 0, 2)`), qui
aurait lu l'heure **20** au lieu de **08**. Celui-là était une **branche morte** : le ternaire qui
le gardait teste `instanceof Carbon`, et l'accesseur rend toujours un Carbon. Il a été retiré
quand même — un piège désamorcé reste un piège écrit, et le prochain lecteur le recopiera.

Les lignes ne sont plus citées par numéro : c'est ce qui rendait ce tableau faux au bout de trois
mois. Le contrôle, lui, se rejoue — il cherche les **quatre mises en contexte texte** d'une heure
(affichage Blade, `substr`, concaténation, interpolation) :

```bash
grep -rnP '(\{\{[^}]*->heure_(debut|fin)\b[^}]*\}\}|substr\(\s*\$[^,]*->heure_(debut|fin)\b|->heure_(debut|fin)\s*\.[^.]|\{\$[^}]*->heure_(debut|fin)\b[^}]*\})' app/ resources/ \
  | grep -vE "format\(|old\(|^\S+:[0-9]+:\s*(\*|//|\{\{--)"
```

**Ce contrôle est un tamis, PAS une preuve — et c'est le point le plus important de cette
section.** La version précédente de cette rule le déclarait « faire foi » ; elle rendait bien zéro
ligne, et cinq sites vivants imprimaient pourtant la date. Un détecteur qui se dit autorité ferme
l'enquête suivante : on le lance, on lit zéro, on conclut. Il est ici pour signaler, jamais pour
absoudre.

Ce qu'il **ne voit pas**, et qu'il faut chercher à l'œil : une heure passée en argument à une
fonction qui la met en texte plus loin, une mise en forme construite ailleurs que sur la ligne, un
appel via une variable intermédiaire. Et il exclut toute ligne portant `format(`, donc une ligne
qui affiche **deux** heures dont une seule est formatée lui échappe.

**L'angle mort a une forme reconnaissable, et c'est par elle qu'est arrivé le dixième site** : un
`Carbon` rangé dans un tableau, puis affiché plus loin par sa clé. La vue écrit
`{{ $conflit['heure_debut'] }}` — aucun `->heure_debut` sur la ligne, donc le motif ne peut pas
mordre, et le tableau était pourtant rempli d'objets `Carbon` bruts. D'où la consigne qui vaut
mieux que le tamis : **une heure se met en forme là où elle est mise dans un tableau d'affichage,
pas là où on l'affiche.** Un tableau destiné à l'écran ne transporte pas de `Carbon`.

**Il rend aujourd'hui trois lignes, et les trois sont des faux positifs. Laissez-les.** C'est
l'état normal de ce contrôle, pas un reste à traiter :

- `resources/views/dashboard/etudiant.blade.php` — calcule une durée par `diffInHours()` entre les
  deux heures ; aucune mise en texte, donc aucun défaut.
- `app/Services/NotificationService.php` — heure d'un message d'absence, sur un `ESBTPAttendance`.
- `resources/views/esbtp/attendances/mes-absences.blade.php` — écran des absences de l'étudiant,
  sur un `ESBTPAttendance`.

Les deux derniers ont **déjà été « corrigés » une fois, et la correction les a cassés** : y poser
`->format('H:i')` lève une `Error` non rattrapée (voir plus haut). Les deux fichiers portent
maintenant un commentaire qui dit pourquoi le code est juste tel quel. Ajuster le motif jusqu'à ce
qu'il rende zéro serait refaire exactement l'erreur que cette section raconte.

**La règle à appliquer en lecture prime sur le tamis** : une heure **de séance** qui part à
l'écran, dans un courriel ou dans un export passe par `->format('H:i')` — parce que c'est un
`Carbon`. Une heure **d'absence** (`ESBTPAttendance`) est une chaîne et se coupe à cinq
caractères. Le geste dépend du modèle, jamais du nom de la colonne.

**Second effet, à ne PAS confondre** : sur `ESBTPSeanceCours`, une heure NULLE ne se lit pas `null` —
`Carbon::parse(null)` rend l'instant présent. C'est encore l'accesseur, **pas** le cast : celui-ci
court-circuite le nul (`castAttribute()` teste `is_null()` avant tout), donc `asDateTime(null)` n'est
jamais atteint. La preuve croisée est `ESBTPCours`, qui porte le cast **sans** accesseur : là, une
heure nulle se lit bien `null`.

Et ce second effet n'a **aucune population en base** : `heure_debut` et `heure_fin` sont **NOT NULL**
(migration `2024_03_18_000002`, jamais relâchée). Il ne concerne que les objets construits en
mémoire. Ne pas partir en chasse dessus.

**Trois modèles portent des colonnes d'heures, et les trois se comportent différemment** — c'est le
piège dans le piège, et la colonne du milieu est celle qui décide du geste :

| modèle | accesseur | cast | `->heure_debut` rend | une heure nulle se lit |
|---|---|---|---|---|
| `ESBTPSeanceCours` | oui (`Carbon::parse`) | `'datetime'` (inerte) | un `Carbon` daté d'aujourd'hui | l'instant présent |
| `ESBTPCours` | non | `'datetime:H:i'` | un `Carbon` | `null` |
| `ESBTPAttendance` | **non** | **aucun** | **la chaîne `'08:00:00'`** | `null` |

Le contrôle à rejouer avant de toucher une heure sur un modèle qu'on ne connaît pas :

```bash
grep -n "getHeure\(Debut\|Fin\)Attribute\|heure_debut" app/Models/LeModele.php
```

Pas d'accesseur et pas de `'heure_debut' => 'datetime'` dans `$casts` → c'est une **chaîne**, et
`->format()` dessus lève une `Error` que `catch (\Exception)` ne rattrape pas.

---

## Workflow systematic pour debug d'un bug "mes changements ne prennent pas effet"

Quand tu vois le symptôme « mes logs/changements n'apparaissent pas », exécute ce checklist DANS L'ORDRE :

1. **Verify route → controller mapping** :
   ```bash
   grep -E "Route::.*'$URL_PART'" routes/web.php
   php artisan route:list --path=$URL_PART | head -3
   ```

2. **Verify file is git-tracked + deployed** :
   ```bash
   git log --oneline -3 path/to/file.php
   git push --dry-run  # voir si commit ahead
   ```

3. **Verify cache:clear ran AFTER deploy** :
   ```bash
   klassci pull $tenant && klassci cache:clear $tenant
   ```
   Le `cache:clear` fait `view:clear + opcache reset`. Sans ça les changements PHP ne sont pas pris en compte.

4. **Verify log level production** : Use `Log::error()` pour debug, JAMAIS `Log::info()` car peut être filtré.

5. **Verify case sensitivity** : `git ls-files | grep -i $filename` pour voir la case réelle.

6. **Add unique marker** : `Log::error('MARKER_UUID_$(date +%s)', [...])` dans le code édité, puis grep les logs. Si marker absent → tu édites le mauvais fichier. STOP et cherche le vrai.

7. **Verify service method visibility** si appel cross-service :
   ```bash
   grep "function methodName" path/to/Service.php  # vérifier private/public
   ```

---

## Anti-patterns à BLOQUER en review

1. ❌ **Patcher 3+ fois le même file** sans avoir vérifié qu'il est sur le chemin actif
2. ❌ **Ignorer l'absence du log de debug** dans les outputs (signal #1 que tu édites le mauvais fichier)
3. ❌ **Hardcoder des moyenne_generale updates** sur `esbtp_bulletins` sans passage par régénération explicite
4. ❌ **Synchroniser denormalized columns** sur `esbtp_notes` sans aussi cleaner `esbtp_resultats` orphelins
5. ❌ **Compter sur `data-bs-strategy`** HTML attribute pour configurer Popper (ignored)
6. ❌ **Mettre `transform` sur :hover d'un parent de dropdown** (créé containing block)
7. ❌ **Utiliser `@json([multiligne])` direct** sans extract en variable
8. ❌ **Test dev-browser monolithe** qui timeout à 30s — split en étapes courtes
9. ❌ **Commit `git add path/Api/CLI/File.php`** sur Windows alors que le repo a `API/CLI/`
10. ❌ **Continuer à éditer** après avoir vu que tes logs n'apparaissent pas (= signal STOP IMMÉDIAT)

---

## Voir aussi

- `.claude/rules/blade-pitfalls.md` — pièges Blade silencieux
- `.claude/rules/universal-dropdowns.md` — Popper strategy + containing block
- `.claude/rules/feature-delivery-methodology.md` — méthodologie 13 phases
- `.claude/rules/multi-agent-git-safety.md` — discipline cross-branch
- `.claude/rules/exports-pdf-excel.md` — pattern unifié exports
- Memory projet : feedback_controller_method_name_collisions
