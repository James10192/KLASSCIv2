<?php

return [
    'tools' => [
        'rechercher_rendez_vous' => [
            'enabled' => true,
            'any_permissions' => ['inscriptions.rdv.view', 'inscriptions.rdv.manage'],
            'libelle' => 'Recherche du rendez-vous…',
            'suggestion' => 'Retrouve le rendez-vous de cette famille',
        ],
        'proposer_gestion_rendez_vous' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.rdv.manage'],
            'libelle' => 'Préparation du rendez-vous…',
            'suggestion' => 'Reprogramme ce rendez-vous sur le prochain créneau libre',
        ],
        'proposer_configuration_rendez_vous' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.rdv.configure'],
            'libelle' => 'Préparation des réglages rendez-vous…',
            'suggestion' => 'Active la fermeture des créneaux du jour à minuit',
        ],
        'proposer_modification_enseignant' => [
            'enabled' => true,
            'all_permissions' => ['teachers.edit'],
            'libelle' => 'Préparation de la fiche enseignant…',
            'suggestion' => 'Ajoute le titre académique de cet enseignant',
        ],
        'proposer_professeur_classe_lmd' => [
            'enabled' => true,
            'all_permissions' => ['lmd.planning.edit'],
            'libelle' => 'Préparation du professeur LMD…',
            'suggestion' => 'Confirme le professeur de cet ECUE pour cette classe',
        ],
    ],

    'actions' => [
        \App\Domain\Assistant\Actions\Enseignants\ModifierEnseignant::class,
        \App\Domain\Assistant\Actions\Lmd\DefinirProfesseurClasseLmd::class,
        \App\Domain\Assistant\Actions\RendezVous\GererRendezVous::class,
        \App\Domain\Assistant\Actions\RendezVous\ConfigurerRendezVous::class,
    ],
];
