# Runbook — Backfill bulletins annuels BTS Tronc Commun (C8)

> Objet : régénérer les bulletins **annuels** des inscriptions BTS « modèle phases »
> (Tronc Commun → Spécialité) dont la moyenne générale persistée est devenue
> obsolète après le correctif class-map (Plan C, commits c1→c9).
>
> **Périmètre strict : BTS uniquement.** Aucun bulletin LMD n'est touché.
> La commande ne traite que la période `annuel`.

---

## 1. Avertissements absolus

- ❌ **JAMAIS** `php artisan migrate:fresh`, `migrate:reset`, `db:wipe` ni aucune commande
  qui drop la base. Ce backfill ne fait que régénérer des bulletins existants.
- ❌ **JAMAIS** de `pnpm build` / `composer install` lourd / build d'assets sur le serveur
  qui sert le trafic de production. La commande backfill est un process artisan léger ;
  elle ne nécessite aucun build.
- ❌ **JAMAIS** lancer la commande sur une instance dont le `TENANT_CODE` ne correspond
  pas à l'argument `{tenant}` passé. Le garde-fou interne refuse (exit 1) toute exécution
  croisée, mais c'est aussi une discipline opérateur.
- ✅ **TOUJOURS** un `dry-run` AVANT, puis un backup mysqldump AVANT le run réel.
- ✅ **TOUJOURS** lire le rapport JSON (`storage/app/backfill/…`) pour décider GO / NO-GO.

---

## 2. Prérequis

1. Les commits **c1 → c9** du Plan C sont **mergés sur `presentation`** puis **déployés**
   sur le tenant cible (`git pull` de la branche tenant + `cache:clear` + `migrate`
   + `permissions:fix` selon le workflow multi-instance habituel).
2. La commande `bts:tc-bulletins-backfill` répond :
   ```bash
   php artisan list | grep bts:tc-bulletins-backfill
   ```
3. Vous connaissez l'**ID de l'année universitaire** à backfiller (option `--annee=`,
   obligatoire). Récupérable via la table `esbtp_annee_universitaires` (année courante
   ou année concernée par la bascule TC → spécialité).
4. Accès SSH au serveur du tenant + droits `mysqldump` sur la base de l'instance.

---

## 3. Signature de la commande

```
php artisan bts:tc-bulletins-backfill {tenant}
    --annee=<ID_ANNEE>        # OBLIGATOIRE : ID de l'année universitaire
    [--dry-run]               # n'écrit rien, rapporte seulement
    [--limit=<N>]             # limite le nombre d'inscriptions traitées
    [--periode=annuel]        # seule la période "annuel" est supportée
```

- `{tenant}` **doit** matcher le `TENANT_CODE` de l'instance courante, sinon **exit 1**.
- Code retour : `0` si `errors == 0`, sinon `1`.
- Rapport JSON écrit dans :
  `storage/app/backfill/bts-tc-{tenant}-{annee}-{Ymd_His}.json`

### Compteurs du rapport

| Compteur | Signification |
|---|---|
| `scanned` | inscriptions modèle phases scannées |
| `skipped_no_official` | aucun bulletin annuel officiel persisté → ignoré |
| `eligible` | a un bulletin officiel, candidat à comparaison |
| `skipped_recompute_error` | échec du recalcul lecture seule → ignoré |
| `aligned` | persisté == recalculé (écart < 0.01) → rien à faire |
| `affected` | écart >= 0.01 détecté |
| `would_fix` | (dry-run) bulletins qui seraient régénérés |
| `fixed` | (run réel) bulletins régénérés |
| `errors` | échecs de régénération (déclenche exit 1) |

**Règle GO / NO-GO** : un run réel est sûr quand le dry-run montre `errors == 0` et un
`would_fix` cohérent avec le nombre d'inscriptions orientées attendu pour l'année.

---

## 4. Ordre de déploiement des tenants

Traiter les tenants **séquentiellement**, du moins critique au plus critique :

```
presentation  →  hetec  →  rostan  →  ephrata  →  esbtp-yakro  →  esbtp-abidjan
```

- `presentation` = démo, sert de validation pilote.
- `esbtp-yakro` et `esbtp-abidjan` = Élite, > 2000 inscriptions chacun : à traiter en
  dernier, après confirmation que le pilote et les tenants intermédiaires sont sains.

Ne JAMAIS enchaîner deux tenants sans avoir validé le précédent (rapport JSON + vérif
fonctionnelle).

---

## 5. Procédure par tenant

Pour CHAQUE tenant, dans l'ordre ci-dessus, sur le serveur de l'instance :

### Étape 5.1 — Dry-run (lecture seule)

```bash
php artisan bts:tc-bulletins-backfill <tenant> --annee=<ID_ANNEE> --dry-run
```

### Étape 5.2 — Lire le rapport JSON → décision GO / NO-GO

```bash
cat storage/app/backfill/bts-tc-<tenant>-<ID_ANNEE>-<timestamp>.json
```

- **GO** si : `errors == 0` et `would_fix` correspond à l'ordre de grandeur attendu
  d'étudiants orientés (Tronc Commun → spécialité) sur cette année.
- **NO-GO** si : `errors > 0`, ou `would_fix` anormalement élevé (ex. toute la cohorte),
  ou `skipped_no_official` inattendu. Investiguer AVANT tout run réel.

Optionnel : limiter un premier run réel de test avec `--limit=5` pour valider sur un
petit échantillon avant le run complet.

### Étape 5.3 — Backup mysqldump AVANT le run réel

```bash
mkdir -p ~/Downloads/dev/_db_backups
mysqldump --single-transaction --routines --triggers <DB_INSTANCE> \
    > ~/Downloads/dev/_db_backups/<tenant>_$(date +%Y%m%d_%H%M%S).sql
```

Vérifier que le fichier n'est pas vide (`ls -lh`). Ce dump est le point de rollback.

### Étape 5.4 — Run réel

```bash
php artisan bts:tc-bulletins-backfill <tenant> --annee=<ID_ANNEE>
```

Vérifier le code retour (`echo $?` → doit être `0`) et le rapport JSON : `fixed`
correspond au `would_fix` du dry-run, `errors == 0`.

### Étape 5.5 — Vérifier l'idempotence

Relancer un dry-run immédiatement après le run réel :

```bash
php artisan bts:tc-bulletins-backfill <tenant> --annee=<ID_ANNEE> --dry-run
```

Résultat attendu : `affected == 0` et `would_fix == 0` (tout est aligné). Si ce n'est
pas le cas, NE PAS continuer vers le tenant suivant : investiguer.

### Étape 5.6 — Vérification fonctionnelle

Ouvrir l'application du tenant et contrôler quelques bulletins annuels d'étudiants
orientés (Tronc Commun → spécialité) :

- la moyenne générale annuelle affichée est cohérente ;
- le rang annuel est calculé sur la bonne cohorte ;
- aucune régression visible sur un bulletin BTS « pur » (non TC) servant de témoin.

---

## 6. Rollback

En cas d'anomalie après un run réel sur un tenant :

```bash
mysql <DB_INSTANCE> < ~/Downloads/dev/_db_backups/<tenant>_<timestamp>.sql
```

Puis purger les caches Laravel de l'instance (`config:clear`, `cache:clear`,
`view:clear`). Le backfill ne touchant que des bulletins, la restauration du dump
pris en 5.3 ramène l'état exact d'avant le run.

- **Ne JAMAIS** tenter un rollback via `migrate:fresh` ou par suppression manuelle de
  lignes : restaurer le dump est la seule procédure autorisée.

---

## 7. Checklist récapitulative (par tenant)

- [ ] c1→c9 mergés + déployés sur le tenant
- [ ] `--dry-run` exécuté
- [ ] Rapport JSON lu → décision **GO**
- [ ] `mysqldump` réalisé dans `~/Downloads/dev/_db_backups/` (fichier non vide)
- [ ] Run réel exécuté, exit code `0`, `errors == 0`
- [ ] Dry-run d'idempotence → `affected == 0`
- [ ] Vérification fonctionnelle OK (bulletins TC + témoin BTS pur)
- [ ] Tenant suivant uniquement après validation complète du courant
