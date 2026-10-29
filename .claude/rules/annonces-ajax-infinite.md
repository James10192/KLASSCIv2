---
description: Annonces et campagnes — AJAX, sélection premium, liste infinie
globs: resources/views/esbtp/annonces/**,app/Http/Controllers/*Annonce*
---

# Annonces KLASSCI — interactions premium

- Toute reprise, confirmation, actualisation, sélection de filtre et action de campagne passe par `fetch()` et JSON sans rechargement de la page.
- Pour les filtres, utiliser `<x-au-select>` (jamais un select natif visible) et préserver la valeur sélectionnée lors des requêtes asynchrones.
- Toutes les listes de destinataires et historiques dépassant une tranche utilisent `<x-liste-infinie>`, le contrat `App\Support\ListeInfinie` et une colonne id de départage stable. Les ajouts suivent le même filtrage.
- Le bouton de reprise ne réexpédie JAMAIS `submitted`, `delayed`, `awaiting_dispatch`, `processing`, `delivered` ni les échecs dont la soumission est incertaine.
- Les campagnes terminées restent terminées. Les garanties de livraison sont celles de MailPulse, pas les HTTP 2xx de soumission.
- HTML mail : utiliser `esbtp.emails.parents.recu` et le composeur commun d'identité (logo, couleurs, nom, contacts) ; texte libre échappé. L'aperçu est isolé dans un iframe sandbox sans scripts.
- Accès : lecture distincte de la mutation ; permissions effectives et CSRF ; pas de `hasRole` ; scopes d'établissements respectés.
