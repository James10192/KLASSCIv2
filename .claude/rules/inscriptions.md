---
description: Règles pour validation des inscriptions (bulk + UI)
globs: 
  - app/Http/Controllers/ESBTPInscriptionController.php
  - resources/views/esbtp/inscriptions/**
---

# Inscriptions - Validation

- **NEVER** auto-valider un paiement en attente lors du bulk; une inscription avec paiement `en_attente` doit rester non validee.
- **NEVER** valider une inscription sans paiement en bulk, **sauf** si chaque souscription active monétaire porte une exonération explicite et motivée à 0 FCFA, prouvée par l'audit `frais:exoneration-explicite`, et si tous les frais obligatoires existent. Dans les autres cas, ignorer avec raison "sans_paiement". Un zéro sans preuve n'est pas une exonération.
- **UI**: afficher "En attente" si `workflow_step != etudiant_cree` meme si `status == active` (sinon faux positif sans refresh).
- **DEPLOY**: si l'erreur persiste en prod, verifier que le serveur a bien pull la derniere branche (code local peut etre OK).
