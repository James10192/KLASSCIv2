# Notes — Export PDF paiements (with_skill)

## Sections du skill utilisées
- §3 : Structure standard template (Reset CSS, @page, body DejaVu Sans)
- §4 : Settings KLASSCI (`$settings['primary_color']`, `$settings['font_size']`, etc.)
- §6 : Fix tr→td — background sur `.row-even td` et `thead td` (pas sur `<tr>`)
- §7 : Font `DejaVu Sans` pour les accents français
- §8 : Logo base64 via `$logoBase64` (jamais `asset()`)
- §10 : Conversion Bootstrap → table layout pour les stats cards
- §14 : Pattern stat card row + badge pattern + document header block

## Décisions de design
- `DejaVu Sans` explicitement déclaré
- Zebra striping via classes `row-even`/`row-odd` sur `<tr>`, ciblées avec `td` en CSS
- 4 stat cards en haut (total, montant total, validés, en attente)
- Badges avec 3 couleurs distinctes : vert `#10b981`, orange `#f59e0b`, rouge `#ef4444`
- Ligne de total dans `<tfoot>` avec montant global
- Header table layout (pas flexbox)
- `$settings` de SettingsHelper pour toutes les couleurs

## Différences notables vs baseline (without_skill)
- ✅ `DejaVu Sans` (baseline utilise Arial)
- ✅ Fix explicite tr→td selon §6 du skill
- ✅ Stat cards calculées depuis la collection (countValides, countAttente)
- ✅ Tfoot avec total
- ✅ Références explicites aux sections du skill dans les commentaires
- ✅ `match()` PHP 8 pour résolution badge (plus lisible)
