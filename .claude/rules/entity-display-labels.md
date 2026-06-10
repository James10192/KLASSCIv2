# Rule: Libellés d'entités affichés — JAMAIS de préfixe « ESBTP »

## Quand s'active

Cette rule s'active quand tu :
- Affiches un **type d'entité** à l'utilisateur (audit, logs, activity feed, notifications, breadcrumbs, exports PDF/Excel, titres de sections)
- Écris `class_basename($model->auditable_type)` ou `class_basename($x)` pour produire un **label affiché**
- Mappes un nom de classe (`App\Models\ESBTPNote`) vers un texte montré à l'écran
- Touches aux vues `resources/views/esbtp/audit/*`, à `ESBTPAuditController`, à `app/Exports/AuditExport.php`

## Pourquoi cette rule existe

**Demande fondateur Marcel (juin 2026)** :
> « ne mets pas le nom réel de l'entité ; au lieu de *ESBTP Note* tu mets seulement *Note*, et cela pour toutes les autres entités. »

Le préfixe technique `ESBTP` (et `LMD`) est un détail d'implémentation. Il ne doit JAMAIS apparaître dans un libellé montré à l'utilisateur. `class_basename('App\Models\ESBTPNote')` renvoie `ESBTPNote` → fuite technique inacceptable côté UI.

## Source unique de vérité

`App\Helpers\EntityLabelHelper` :

```php
EntityLabelHelper::for('App\Models\ESBTPNote');     // "Note"
EntityLabelHelper::for('App\Models\ESBTPEtudiant'); // "Étudiant"
EntityLabelHelper::for('App\Models\ESBTPLMDJury');  // "LMD Jury" (fallback camel-split)
EntityLabelHelper::plural('App\Models\ESBTPFacture'); // "Factures"
```

- `MAP` = libellés français explicites (singulier) pour les entités connues → prime toujours.
- Fallback générique : retire le préfixe `ESBTP`, puis sépare le CamelCase en gardant les acronymes groupés.

## Comment appliquer

### ✅ BON
```blade
{{ \App\Helpers\EntityLabelHelper::for($audit->auditable_type) }}
```
```php
$modelLabel = \App\Helpers\EntityLabelHelper::for($audit->auditable_type);
```

### ❌ INTERDIT
```php
class_basename($audit->auditable_type)           // -> "ESBTPNote"
str_replace('App\\Models\\', '', $type)          // -> "ESBTPNote"
$type                                             // FQCN brut dans un label UI
```

## Exception tolérée

Un champ **technique forensique** explicitement labellisé « Classe » qui montre le **FQCN réel** (`App\Models\ESBTPNote`) pour le débogage/audit est acceptable — altérer ce champ falsifierait une donnée forensique. Mais le **label d'entité** présenté à côté (« Type : Note ») DOIT passer par `EntityLabelHelper::for()`.

## Ajouter une entité au registre

Si une nouvelle entité doit avoir un libellé propre, ajoute une entrée dans `EntityLabelHelper::MAP` (clé = FQCN, valeur = label français singulier). Ne crée pas un nouveau mapping local dans une vue/contrôleur.

## Audit avant commit

```bash
# Aucun class_basename sur auditable_type dans une surface d'affichage
grep -rn "class_basename(\$.*auditable_type)" resources/ app/
# Aucun FQCN ESBTP affiché brut dans un label (hors champ technique 'Classe')
grep -rn "auditable_type" resources/views/ | grep -v "EntityLabelHelper"
```

## Voir aussi

- `app/Helpers/EntityLabelHelper.php` — source de vérité
- `.claude/rules/premium-redesign.md` — design system (les labels propres font partie du fini premium)
