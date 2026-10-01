<?php

/*
 * Ce que Nanan sait faire, parmi les opérations d'écriture de la CLI.
 *
 * Chaque route POST/PUT/PATCH/DELETE de /api/cli est déclarée ici, d'une de
 * trois façons :
 *   'nanan'       => nom de l'outil ou de l'action de Nanan qui fait la même chose
 *                    (une clé de config/chatbot.php `tools`) ;
 *   'hors_nanan'  => pourquoi un agent ne doit PAS la faire (exploitation,
 *                    démonstration, comptes et droits, réparation de données) ;
 *   'a_apprendre' => opération d'école que Nanan ne sait pas encore faire.
 *
 * `php bin/verifier-couverture-nanan.php` refuse une route d'écriture absente
 * d'ici, une entrée qui ne correspond plus à aucune route, et un outil
 * inconnu. Il tourne en CI et avant chaque push. Une opération faite à la main
 * pour une école (CLI, SQL, tinker) doit finir en 'nanan' : voir le skill
 * .claude/skills/nanan-autonomie.
 */

return [

    // --- Déjà à la portée de Nanan ---
    'POST api/cli/bts/maquette' => ['nanan' => 'proposer_configuration_maquette_bts'],
    'POST api/cli/classes' => ['nanan' => 'proposer_creation_classes'],
    'POST api/cli/frais/souscriptions/ajuster' => ['nanan' => 'proposer_ajustement_souscription'],
    'POST api/cli/lmd/parcours/{parcours}/link-ues' => ['nanan' => 'proposer_liaison_ue_parcours'],

    // --- À apprendre à Nanan ---
    'POST api/cli/annee/create' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/annee/set/{id}' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/bts-tc/classes/{id}/targets' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/bts-tc/filieres/{id}/mark-tronc-commun' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/bts-tc/inscriptions/{id}/orient' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/bts/maquette/retirer' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/bulletins/generate-missing' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/evaluations/deplacer-periode' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/evaluations/{id}/matiere' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/filieres' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/frais/depot-nature/annuler' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/frais/poser-bareme' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/frais/repartir-trop-percu' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/inscriptions/move' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/inscriptions/validate-bulk' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/inscriptions/{id}/validate' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/lmd/import' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/lmd/import-enseignants' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/lmd/link-classes' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/lmd/setup' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/niveaux' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/niveaux/{niveau}/annee' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/notes/corriger' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/paiements/{id}/annuler' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/paiements/{id}/restaurer' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/rendez-vous/convocations/envoyer' => ['nanan' => 'proposer_convocations_rdv'],
    'POST api/cli/rendez-vous/convocations/remettre' => ['nanan' => 'proposer_convocations_rdv'],
    'POST api/cli/rendez-vous/convocations/renvoyer' => ['nanan' => 'proposer_convocations_rdv'],
    'POST api/cli/rendez-vous/generer' => ['nanan' => 'proposer_generation_creneaux_rdv'],
    'POST api/cli/rendez-vous/placer' => ['nanan' => 'proposer_placement_dossiers_rdv'],
    'POST api/cli/resultats/moyennes' => ['a_apprendre' => 'Opération d\'école faite à la main en CLI : à apprendre à Nanan.'],
    'POST api/cli/settings' => ['nanan' => 'proposer_modification_reglages'],
    'PUT api/cli/settings/{key}' => ['nanan' => 'proposer_modification_reglages'],
    'POST api/cli/settings/{key}/image' => ['nanan' => 'proposer_image_reglage'],

    // --- Volontairement hors de la portée de Nanan ---
    'POST api/cli/academic-pilotage/backfill' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/academic-pilotage/refresh' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/acces-temporaires/{grant}/retirer' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'PUT api/cli/assistant/budget' => ['hors_nanan' => 'Réglage de Nanan lui-même (clé, modèle, budget) : hors de sa portée par construction.'],
    'PUT api/cli/assistant/cle' => ['hors_nanan' => 'Réglage de Nanan lui-même (clé, modèle, budget) : hors de sa portée par construction.'],
    'DELETE api/cli/assistant/cle/{fournisseur}' => ['hors_nanan' => 'Réglage de Nanan lui-même (clé, modèle, budget) : hors de sa portée par construction.'],
    'PUT api/cli/assistant/modele' => ['hors_nanan' => 'Réglage de Nanan lui-même (clé, modèle, budget) : hors de sa portée par construction.'],
    'POST api/cli/assistant/tester' => ['hors_nanan' => 'Réglage de Nanan lui-même (clé, modèle, budget) : hors de sa portée par construction.'],
    'POST api/cli/attendance/backfill-note-assiduite' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bts-tc/inscriptions/{id}/seed-academic-sample' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/bts-tc/inscriptions/{id}/specialisation-integrity/repair' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bts-tc/inscriptions/{id}/sync' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bts-tc/sync-all' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bulletins/backfill-averages' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bulletins/backfill-subject-ranks' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/bulletins/recalculate-ranks' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/cache/clear' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/composer/install' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/comptabilite/cleanup-orphan-paiements' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/comptabilite/recus-en-double/renumeroter' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/db/fix-duplicates' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/diagnostics/evaluations-periode/repair' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/echeanciers/recompute' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/emails/corriger-fautes' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/emails/nettoyer-factices' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/env' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/etudiants/{id}/inscriptions-repair' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/evaluations/corriger-dates' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/evaluations/noter-les-non-notes' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/evaluations/sync-notes' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/frais/appliquer-tenue-nouveaux' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/frais/corriger-souscriptions' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/frais/ordonner-categories' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/frais/retirer-configurations-inutiles' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/frais/souscriptions-manquantes' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/inscriptions/normaliser-type' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/lmd/cleanup' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/lmd/evaluations/regulariser-notes' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/lmd/jury-e2e/prepare' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/lmd/planifications/reparer-credits' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/lms/jeton-serveur' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'DELETE api/cli/lms/jeton-serveur/{id}' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/logs/prune' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/mailpulse/parent-chatbot/e2e/cleanup' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/mailpulse/parent-chatbot/e2e/inbound' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/mailpulse/parent-chatbot/e2e/prepare' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/mailpulse/test-notification' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/maintenance/reparer-encodage' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/matieres/cleanup-tronc-commun' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/migrate' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/notes/recompute' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/notes/unicite' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/paie/seed-demo' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/permissions/fix' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/permissions/sync' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/pull' => ['hors_nanan' => 'Exploitation du serveur (déploiement, cache, migrations) : réservé au support technique.'],
    'POST api/cli/rendez-vous/rattrapage-convocations' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/rendez-vous/synchroniser-convocations' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/reprise/inscriptions-annee-ecoulee' => ['hors_nanan' => 'Réparation ou reprise de données décidée par le support, rejouée en commande d\'exploitation.'],
    'POST api/cli/roles' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/roles/{role}/grant' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/roles/{role}/revoke' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/seed-demo' => ['hors_nanan' => 'Données de démonstration ou de recette : jamais sur une école en service.'],
    'POST api/cli/user/create' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/acces-temporaires' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/delete' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/permissions' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/reset-password' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/reset-password-expiry' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
    'POST api/cli/user/{id}/role' => ['hors_nanan' => 'Comptes, rôles et accès : jamais confiés à un agent (escalade de privilèges).'],
];
