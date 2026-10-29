# Audit transversal — transport HTTP des inscriptions — 21 octobre 2026

## Portée examinée
- Nouvelle inscription : `GET/POST esbtp.inscriptions.create/store`, `StoreInscriptionRequest`, `create.blade.php` (POST HTML normal, photo optionnelle).
- Demandes d'admission et conversion en inscription : `ESBTPDemandesInscriptionController`, `admissions/demandes/_script.blade.php`, PR #1648 (URL-encoded déjà livré).
- Fiche étudiant : bannière de workflow, `iiSoumettreValidationInscription`, preview et PUT spoofé.
- Liste étudiants : `etudiants-index.js`, même helper, desktop/mobile.
- Index inscriptions : `inscriptions/index.js`, validation ligne, sélection explicite et filtre complet.
- Administration des inscriptions : `administration.blade.php`, validation unitaire, dérogation de capacité, aperçu/validation en lot.
- Fiche inscription : `show.blade.php`, preview puis POST `valider-definitivement`.
- Domaines/API : `ESBTPInscriptionController::store/valider/bulkValider/validationPreview`, `ESBTPInscriptionPaiementController::validerDefinitivement`, `ESBTPInscriptionService`, `ExonerationInscription`, `ObstacleALaValidation`, `CLIStudentController::validateInscription` (Sanctum), workflow Nanan/CLI. Pas de modification du contrat API métier.

## Garanties du correctif
- Requêtes sans fichier : `application/x-www-form-urlencoded`, conserve `_token`, `_method=PUT`, champs répétés `inscription_ids[]` et filtres.
- Requêtes avec vrai fichier : multipart conservé, pas de représentation `[object File]` dans un corps texte.
- Les permissions `inscriptions.create`, `inscriptions.validate`, `paiements.create`, `inscriptions.override_capacity` et Sanctum `cli:write` restent du côté serveur.
- Un paiement `en_attente`, une inscription annulée, déjà validée, une classe pleine ou une gratuité sans barème/audit doivent rester bloqués.
- Réponses HTML et JSON invalides, HTTP 403/419/422/429/500 : ne jamais être traités comme une confirmation. Une référence proxy dans l'en-tête 403 est affichée uniquement si son format est sûr.
- Aucune donnée personnelle, corps de requête, Cookie ou token n'est ajouté aux journaux.

## Limites et preuves restantes
- Hypothèse WAF **non confirmée** faute de trace corrélée sur le serveur client ; un JSON Laravel 403 n'est pas le même incident qu'un 403 HTML de passerelle.
- Les tests Node couvrent le contrat JS ; PHPUnit/MariaDB couvre le métier et l'encodage HTTP. L'exécution réelle via navigateur et session SuperAdmin demeure nécessaire.
- Les formulaires photos continuent d'exiger que le reverse proxy accepte le multipart. Aucune configuration d'infrastructure n'est modifiée.
- Pas de propagation de branche vers les écoles tant que la recette et les annonces ne sont pas complètes.

## Documents de référence
- MDN `URLSearchParams` (non prise en charge native des fichiers dans URLSearchParams).
- Laravel HTTP Request/CSRF/Validation ; route POST + `_method=PUT`.
- OWASP Logging Cheat Sheet : ne pas logger les secrets, PII et corps de requête.
- Rules projet : `.claude/skills/plan-and-confirm/SKILL.md`, `.claude/rules/pre-merge-checklist.md`, `.claude/rules/inscriptions.md`.
