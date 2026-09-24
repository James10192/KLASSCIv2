<?php

/*
|--------------------------------------------------------------------------
| Rapport d'usage d'une instance
|--------------------------------------------------------------------------
|
| Lu par App\Services\Usage\*. Sert a repondre a une seule question :
| l'etablissement travaille-t-il reellement dans KLASSCI ?
|
| Tout est ici plutot que dans le code : la liste des modules evolue avec
| l'application, et une instance peut vouloir d'autres seuils.
|
*/

return [

    // Au-dela, une fenetre coute trop cher sur un hebergement mutualise.
    'max_window_days' => 186,

    // Un utilisateur qui depasse ce nombre d'actions dans une meme journee
    // a tres probablement lance un import ou un traitement de masse. Sa
    // journee reste comptee comme active, mais elle est signalee a part.
    'bulk_actions_per_user_day' => 300,

    // Les audits dont l'URL contient l'un de ces fragments viennent de nos
    // outils (CLI, API interne), jamais du personnel de l'ecole.
    'internal_url_fragments' => [
        '/api/cli/',
    ],

    // Comptes qui ne sont jamais le personnel de l'ecole, quels que soient
    // leurs droits : on les classe avec KLASSCI.
    'internal_roles' => [
        'serviceTechnique',
    ],

    // Roles qui ne sont pas du personnel : ils sont comptes a part.
    'non_staff_roles' => [
        'etudiant',
        'parent',
    ],

    // Evenements d'audit qui modifient une donnee. Les lectures
    // ('retrieved') sont comptees a part : seuls quelques modeles les
    // journalisent, elles ne sont pas comparables d'un module a l'autre.
    'write_events' => ['created', 'updated', 'deleted', 'restored'],

    'modules' => [
        'inscriptions' => [
            'label' => 'Inscriptions et dossiers étudiants',
            'models' => [
                App\Models\ESBTPInscription::class,
                App\Models\ESBTPInscriptionPhase::class,
                App\Models\ESBTPEtudiant::class,
                App\Models\ESBTPReinscriptionDemande::class,
                App\Models\ESBTPCandidature::class,
                App\Models\ESBTPStudentAccessibilityProfile::class,
            ],
        ],
        'finances' => [
            'label' => 'Paiements et facturation',
            'models' => [
                App\Models\ESBTPPaiement::class,
                App\Models\ESBTPPaiementAllocation::class,
                App\Models\ESBTPFacture::class,
                App\Models\ESBTPFraisSubscription::class,
                App\Models\ESBTPFraisCategory::class,
                App\Models\ESBTPFraisOption::class,
                App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession::class,
            ],
        ],
        'notes' => [
            'label' => 'Évaluations et notes',
            'models' => [
                App\Models\ESBTPEvaluation::class,
                App\Models\ESBTPNote::class,
                App\Models\ESBTPExamenPlanifie::class,
                App\Models\ESBTPTpeDeclaration::class,
                App\Domain\AcademicPilotage\Models\GradeSheet::class,
            ],
        ],
        'resultats' => [
            'label' => 'Résultats, bulletins et jurys',
            'models' => [
                App\Models\ESBTPBulletin::class,
                App\Models\ESBTPResultat::class,
                App\Models\ESBTPLMDJury::class,
                App\Models\ESBTPLMDJuryDecision::class,
                App\Models\ESBTPLMDJuryMembre::class,
                App\Models\ESBTPLMDSession::class,
            ],
        ],
        'presences' => [
            'label' => 'Présences',
            'models' => [
                App\Models\ESBTPAttendance::class,
            ],
        ],
        'referentiel' => [
            'label' => 'Référentiel académique',
            'models' => [
                App\Models\ESBTPClasse::class,
                App\Models\ESBTPMatiere::class,
                App\Models\ESBTPUniteEnseignement::class,
                App\Models\ESBTPPlanificationAcademique::class,
                App\Domain\AcademicPilotage\Models\AcademicActorAssignment::class,
                App\Domain\AcademicPilotage\Models\AcademicAlert::class,
            ],
        ],
        'personnel' => [
            'label' => 'Personnel et paie',
            'models' => [
                App\Models\ESBTPTeacher::class,
                App\Models\ESBTPSalaire::class,
                App\Models\ESBTPEnseignantTauxSeance::class,
                App\Models\User::class,
            ],
        ],
    ],

    // Modules sans journal d'audit : on ne sait pas qui a saisi, seulement
    // combien de lignes sont nees dans la periode. Colonne de date explicite.
    'volumes' => [
        'seances' => ['label' => 'Séances de cours (emploi du temps)', 'table' => 'esbtp_seance_cours', 'date' => 'created_at'],
        'presences_enseignants' => ['label' => 'Présences des enseignants', 'table' => 'esbtp_teacher_attendances', 'date' => 'created_at'],
        'messages' => ['label' => 'Messages internes', 'table' => 'chat_messages', 'date' => 'created_at'],
        'annonces' => ['label' => 'Annonces', 'table' => 'esbtp_annonces', 'date' => 'created_at'],
        'documents' => ['label' => 'Documents étudiants déposés', 'table' => 'esbtp_etudiant_documents', 'date' => 'created_at'],
        'relances' => ['label' => 'Relances de paiement', 'table' => 'esbtp_relances', 'date' => 'created_at'],
        'caisse' => ['label' => 'Sessions de caisse', 'table' => 'esbtp_cash_sessions', 'date' => 'created_at'],
    ],
];
