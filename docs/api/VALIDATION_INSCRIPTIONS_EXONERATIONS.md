# Validation d'inscription avec exonération explicite à 0 FCFA

## Objet

Une inscription peut être validée sans encaissement si **toutes** ses
souscriptions monétaires actives sont à 0 FCFA **après une décision motivée
et auditée**, que les frais obligatoires ont été configurés/souscrits et
qu'aucun paiement n'est en attente. Aucune écriture de paiement artificielle
n'est créée.

Une souscription créée directement à 0 FCFA, non configurée, sans trace
d'ajustement, n'est **pas** une gratuité.

Les ajustements autorisés existants :

- `PUT /esbtp/inscriptions/{inscription}/subscriptions/{subscription}`
  sous permission `frais.souscriptions.ajuster`, champs obligatoires
  `amount >= 0` et `reason` (10 à 1 000 caractères).
- API CLI/Nanan `AjustementMontantSouscription::appliquer`, avec motif et
  acteur validés par son chemin d'accès.

Leur écriture à 0 conserve dans `notes` auteur/date/motif et produit le
marqueur OwenIt Auditing `frais:exoneration-explicite`. Les anciens 0 sans
marqueur doivent être **reconfirmés** avec un motif par un utilisateur
autorisé. Une correction à un montant positif révoque immédiatement
l'éligibilité, et une nouvelle exonération doit être auditée.

## API d'aperçu (lecture uniquement)

### `GET /esbtp/inscriptions/{inscription}/validation-preview`

Permission : `inscriptions.validate`.

Réponse 200 :

```json
{"eligible":true,"mode":"explicit_waiver","message":"Toutes les souscriptions sont exonérées ou satisfaites en nature : aucun paiement n’est requis."}
```

Modes : `validated_payment`, `configured_free` (gratuité issue de la configuration effective), `explicit_waiver` (montant normal positif ramené à zéro avec audit), `blocked`. Un `default_amount` à zéro sans configuration applicable reste bloquant. L'aperçu
ne transmet aucun détail financier privé et ne réserve pas de place.

### `POST /esbtp/inscriptions/bulk-validation-preview`

Permission : `inscriptions.validate` ; limite 200 identifiants distincts.

Corps : `inscription_ids[]` (entiers).

Réponse : `total`, `ready`, `waived`, `items[]` avec
`id`, `eligible`, `mode`, `message`.

## Validation effective (écriture)

- `PUT /esbtp/inscriptions/{inscription}/valider`
- `POST /esbtp/inscriptions/{inscription}/valider-definitivement`
- `POST /esbtp/inscriptions/bulk-valider`
- CLI et Nanan : validation via la même politique.

Permission académique `inscriptions.validate` ; CLI exige aussi l'ability
Sanctum `cli:write`. Pour `bulk-valider`, une demande `force=true`
exige en outre `inscriptions.override_capacity`.

**IMPORTANT :** l'aperçu AJAX n'est jamais une autorisation. Les services
revérifient l'état sous transaction et verrouillent les écritures. Une classe
pleine, une inscription annulée/déjà validée, une seconde inscription active,
un versement en attente ou une dette active empêchent la validation
conformément aux contrôles existants.

La validation définitive supporte `Accept: application/json` et renvoie
`{success,message,inscription_id}` (HTTP 200 ou 422). L'ancien flux HTML
continue d'utiliser des redirections.

## Historique

- 2026-10-19 : exonération à 0 FCFA tracée et vérifiée, prévisualisation
  unitaire/groupée, vérification du rattachement des paiements, permissions
  de validation et de dérogation de capacité.
