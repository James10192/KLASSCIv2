---
description: Règles pour validation des inscriptions (bulk + UI)
globs: 
  - app/Http/Controllers/ESBTPInscriptionController.php
  - resources/views/esbtp/inscriptions/**
---

# Inscriptions - Validation

- **NEVER** auto-valider un paiement en attente lors du bulk; une inscription avec paiement `en_attente` doit rester non validee.
- **NEVER** valider une inscription sans paiement en bulk, **sauf** si chaque frais obligatoire possède une configuration effective active et applicable (LMD : parcours + niveau ; BTS : filière + niveau ; année et statut d'affectation), que les souscriptions obligatoires sont présentes, que leurs montants dus sont nuls et que chaque zéro sur montant normal positif est exonéré explicitement avec motif, auteur, notes et audit `frais:exoneration-explicite`. Un montant 0 provenant réellement de la configuration est une **gratuité de barème** : il ne nécessite pas d'exonération individuelle. Une configuration absente n'est jamais remplacée par `default_amount = 0`. Dans les autres cas, ignorer avec raison "sans_paiement".
- **UI**: afficher "En attente" si `workflow_step != etudiant_cree` meme si `status == active` (sinon faux positif sans refresh).
- **DEPLOY**: si l'erreur persiste en prod, verifier que le serveur a bien pull la derniere branche (code local peut etre OK).
