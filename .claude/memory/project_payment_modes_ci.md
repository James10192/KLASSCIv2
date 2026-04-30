# Modes de paiement Côte d'Ivoire — KLASSCI

> Référence : modes de paiement supportés et convention de mapping pour la
> normalisation `mode_paiement` (champ `esbtp_paiements.mode_paiement`).
>
> Source de vérité : `config/payment_modes.php`.
> Première utilisation : Lot 14 — widget dashboard `paiements.by_mode`.

## Modes Côte d'Ivoire 2026 (référence)

Les modes de paiement courants à supporter pour un établissement scolaire en
Côte d'Ivoire :

| Clé canonique  | Libellé FR          | Icône (FA5)         | Notes |
|----------------|---------------------|---------------------|-------|
| `especes`      | Espèces             | `fa-money-bill-wave`| Cash. Le mode majoritaire pour scolarité ESBTP. |
| `cheque`       | Chèque              | `fa-money-check`    | Avec n° de référence dans `reference_paiement`. |
| `virement`     | Virement bancaire   | `fa-university`     | Idem chèque côté reférence. |
| `carte`        | Carte bancaire      | `fa-credit-card`    | Rare pour scolarité. |
| `mobile_money` | Mobile Money        | `fa-mobile-alt`     | Catégorie générique (legacy form select). |
| `orange_money` | Orange Money        | `fa-mobile-alt`     | Orange CI — leader marché. |
| `mtn_money`    | MTN Money           | `fa-mobile-alt`     | MTN CI / MTN MoMo. |
| `moov_money`   | Moov Money          | `fa-mobile-alt`     | Moov Africa CI. |
| `wave`         | Wave                | `fa-mobile-alt`     | Wave Mobile Money — croissance forte. |
| `djamo`        | Djamo               | `fa-mobile-alt`     | Fintech récente. |
| `autre`        | Autre               | `fa-question-circle`| Catch-all. |

## Convention de mapping

Le champ `mode_paiement` (varchar, nullable) reçoit des valeurs hétérogènes
selon le flux d'écriture :

- **Form select** envoie snake_case minuscule : `especes`, `cheque`, `virement`,
  `mobile_money`, `carte`.
- **Certains contrôleurs** mappent vers libellés FR avant insert :
  - `ESBTPInscriptionPaiementController::storeOptionalFee` → `Espèces`,
    `Chèque`, `Virement bancaire`, `Mobile Money`.
- **Variants legacy** rencontrés en DB :
  - `espece` (singulier — `ComptabiliteService::createPaiementFromInscription`)
  - `transfert` (`ESBTPInscriptionPaiementController` ligne 472)

### Règle de normalisation

1. Slugifier la valeur brute en snake_case via `Str::slug($raw, '_')`.
   Exemple : `"Espèces"` → `"especes"`, `"Virement bancaire"` →
   `"virement_bancaire"`.
2. Chercher le slug dans `config('payment_modes.aliases')` → renvoie une clé
   canonique (`especes`, `virement`, `mobile_money`, etc.).
3. Si pas d'alias trouvé, fallback : utiliser le slug lui-même comme clé et
   afficher la valeur brute en `Str::title`.
4. Récupérer le label FR + icône via `config('payment_modes.labels.{key}')`.

## Source de vérité

Pour chaque modification :

- **Ajouter un nouveau mode** → entrée dans `config/payment_modes.php` :
  - `labels.{key}` avec `{label, icon}`.
  - `aliases.{slug}` pour chaque variante connue → `{key}`.
- **Vérifier les valeurs réelles en DB** :
  ```sql
  SELECT mode_paiement, COUNT(*) c FROM esbtp_paiements
  GROUP BY mode_paiement ORDER BY c DESC;
  ```
- **Form selects** à synchroniser : la liste des `<option>` dans les
  formulaires (`esbtp/etudiants/show.blade.php`, `esbtp/inscriptions/show.blade.php`,
  validators `ValiderAvecPaiementRequest`, etc.) doit utiliser les **clés
  canoniques** snake_case définies ici.

## Fichiers liés

- `config/payment_modes.php` — catalogue + alias (source de vérité).
- `resources/views/dashboard/widgets/paiements-by-mode.blade.php` — premier
  consommateur (Lot 14).
- `resources/views/dashboard/comptable.blade.php` (lignes 488-510) — ancien
  pattern à base de `str_contains` (à migrer plus tard pour profiter du
  mapping centralisé).
