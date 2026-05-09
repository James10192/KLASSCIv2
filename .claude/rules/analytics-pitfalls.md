# Rule: Analytics Prédictifs — Pièges & robustesse

## Quand s'active

Cette rule s'active quand tu travailles sur :
- `app/Domain/Analytics/**`
- `app/Services/Analytics/**`
- `app/Services/Echeancier*`
- `app/Http/Controllers/ESBTPComptabiliteAnalyticsController.php`
- `resources/views/esbtp/comptabilite/analytics/**`
- Tout job/seeder qui touche aux échéanciers, paiements, ou prédicteurs

## Architecture rapide

```
Page /esbtp/comptabilite/analytics
    └── ESBTPComptabiliteAnalyticsController
        ├── CashFlowPredictor (Domain/Analytics/Predictors)
        │   ├── AnalyticsRepository (revenu mensuel historique)
        │   └── CashFlowProjectionService (Services/Analytics)
        │       └── EcheancierComputationService (tranches projetées)
        ├── DefaultRiskPredictor
        │   ├── StudentRiskRepository
        │   │   └── RelanceCalculationService (financial state)
        │   ├── EcheancierReadinessService (mode configured/fallback)
        │   └── LogisticScoring (sigmoid, label, threshold)
        ├── RecouvrementGapService (attendu vs encaissé mensuel)
        └── AnomalyDetector (Z-score, écarts, outliers)
```

## Les 10 pièges récurrents

### 1. Allocation FIFO par catégorie ≠ libellé du paiement
`EcheancierPaymentAllocationService::allocate()` poole les paiements par `frais_category_id` puis alloue à la tranche la plus ancienne en premier (`due_date ASC`). Le **motif** d'un paiement (ex: "tranche 3/3") n'est qu'un libellé — il ne dirige pas l'allocation. Conséquence : un étudiant qui paye explicitement la tranche 3 voit son paiement absorber la tranche 1 puis la 2 d'abord. Faux signal de retard sur tranches intermédiaires.

→ **Si tu veux respecter le libellé**, utilise `target_due_line_id` sur le paiement (Phase 3 du chantier analytics).

### 2. Mode fallback invisible mais omniprésent
`EcheancierReadinessService::mode()` renvoie `fallback` si **aucune règle active** existe. En fallback, `EcheancierProjectionService::fallbackLine()` crée **une seule tranche** à `date_inscription + payment_deadline_days` (défaut 30j). Toute la scolarité concentrée sur 1 due_date.

→ **Toujours vérifier** : `php artisan analytics:diagnose --tenant=X` retourne `coverage_pct` et `mode`.

### 3. Fenêtre 6 mois clos rate les pics de rentrée
La page Analytics affiche par défaut "6 derniers mois clos". Pour une école qui démarre en septembre et finit en juillet :
- Mai → exclu (en cours)
- Avril, mars, février, janvier, décembre, novembre → fenêtre
- Septembre, octobre → exclus (trop anciens)

→ Une règle d'échéancier 30/30/40 à `+15j / +120j / +240j` après inscription septembre fait tomber **uniquement la tranche 2 dans la fenêtre** (janvier). Pic isolé, faux signal de mauvais recouvrement les autres mois.

### 4. Inscriptions concentrées dans le temps + algo absolu = 100% à risque
Si toute la cohorte est inscrite à la même date, ils ont tous le même `expected_due_to_date` au même moment. S'ils sont tous en retard de 200j → tous au-dessus du seuil `threshold_high=0.66` → **100% à haut risque**.

L'info devient inutilisable. Phase 2 du chantier ajoute une auto-calibration qui élève le seuil dynamiquement quand > 70% de la cohorte sature un bucket.

### 5. CashFlowPredictor mixe 80/20 sans le dire
`CashFlowPredictor::predict()` retourne `forecast = 0.8 * scheduledNextMonth + 0.2 * historicalForecast` quand les deux existent, ou 100% de l'un si l'autre est nul. Pas affiché dans l'UI. Le user voit juste un nombre.

→ Si tu modifies les poids, **mentionne-le explicitement** dans les `explanation`.

### 6. `confidenceLabel` peut mentir
`CashFlowPredictor` retourne `'indicatif'` si pas assez d'historique mais `'fiable'` ou `'tres_fiable'` si > 6 mois. Mais "tres_fiable" sur des données démo synthétiques est trompeur. Ne pas se fier au label seul.

### 7. `RecouvrementGapService` regarde les snapshots, pas les paiements
Le "Attendu cumulé" vient des `due_date` des snapshots. Si un étudiant n'a pas de snapshot → contribue 0 à l'attendu. Si une école n'a aucune règle active → tous les étudiants en mode fallback → tout l'attendu se concentre sur une tranche unique.

### 8. `AnomalyDetector` Z-score sensible aux outliers démo
Les paiements démo créés en bulk avec montants similaires créent une moyenne stable + faible variance. Tout paiement légèrement différent ressort en "outlier" même s'il est légitime. Sur presentation : 2 paiements de 840k flagués comme aberrants alors qu'ils sont des frais d'inscription standards.

### 9. `taux_risque_pct` inclut "moyen" + "haut"
Bug subtil : `tauxRisque = (haut + moyen) / total` pas seulement `haut/total`. Donc `100% taux_risque` peut signifier "tout le monde haut OU moyen", pas forcément "tout le monde haut".

→ Lire `metadata.buckets.haut` directement pour avoir le pourcentage haut seul.

### 10. Hero `.an-hero` clippe le dropdown export
La page analytics avait `overflow:hidden + position:relative + ::before/::after + .an-kpi:hover transform` qui créait des stacking contexts concurrents. Le dropdown `<x-export-modal>` se faisait clipper. Voir `.claude/rules/css-stacking-pitfalls.md` section "Variante critique — Hero premium contenant un `<x-export-modal>` ou dropdown".

## Diagnostic : la commande à lancer

```bash
# Sur le serveur tenant
php artisan analytics:diagnose

# Avec année spécifique
php artisan analytics:diagnose --annee=2

# Sortie JSON pour CLI distant ou parsing
php artisan analytics:diagnose --json

# Via klassci-cli depuis local (consomme l'endpoint /api/cli/analytics/diagnose)
klassci analytics:diagnose presentation
```

Sortie clé à observer :
- `coverage.coverage_pct` < 50% → règles d'échéancier trop sélectives
- `risk_saturation.is_saturated = true` → calibration nécessaire
- `echeancier.mode = 'fallback'` → aucune règle active, mode dégradé
- `monthly_attendu.rows` avec 1 mois > 70% du total → distribution dégénérée

## Anti-patterns à BLOQUER en review

1. ❌ Modifier `DefaultRiskPredictor` formule sans test couvrant cohorte saturée (100% mêmes données)
2. ❌ Changer `threshold_high`/`threshold_medium` codés en dur — passer par `analytics.default_risk.threshold_*` settings tenant
3. ❌ Allocation custom de paiement qui contourne `EcheancierPaymentAllocationService::allocate()` — sinon désync entre `paid_amount`/`remaining_amount` des due_lines
4. ❌ Créer une règle d'échéancier avec une seule ligne à 100% — équivalent au mode fallback, autant ne pas activer
5. ❌ Hardcoder une fenêtre temporelle (3, 6, 12 mois) sans la rendre configurable
6. ❌ Hero analytics avec `overflow:hidden` ou `position:relative` direct (cf rule css-stacking-pitfalls)
7. ❌ Seeder qui crée toutes les inscriptions à la même date — pas réaliste, masque les bugs algo
8. ❌ Ajouter une feature au `LogisticScoring` sans normaliser dans [0,1] — bias devient ininterprétable
9. ❌ Cron analytics qui tourne sans calibration check → notification spam si saturation
10. ❌ Page UI qui affiche un nombre sans `confidenceLabel` ni explication des "raisons"

## Voir aussi

- `.claude/rules/no-god-code-compta.md` — extraction Actions/Services
- `.claude/rules/css-stacking-pitfalls.md` — variante hero+dropdown
- Mémoire projet : `project_analytics_diagnostic.md` — savoir technique consolidé
- `app/Console/Commands/AnalyticsDiagnoseCommand.php` — commande de diagnostic
