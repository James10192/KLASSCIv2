<?php

namespace App\Domain\OfficialDocuments\Services;

/**
 * Les étapes qu'un jury franchit avant son procès-verbal, dans l'ordre où on
 * les fait réellement.
 *
 * Le contrôle d'émission rendait une liste de motifs à plat. Une liste ne dit
 * ni par où commencer, ni où aller corriger : le personnel lisait « La cohorte
 * du jury est vide » sans savoir que cela venait des notes. Chaque étape porte
 * donc l'onglet de l'écran où elle se règle.
 *
 * Aucune règle ici : les motifs viennent de JuryPvIssuanceGuard, qui reste la
 * seule autorité sur ce qui bloque l'émission.
 */
class PreparationDuJury
{
    private const ETAPES = [
        'notes' => [
            'titre' => 'Notes validées',
            'aide' => 'Les feuilles de notes du semestre doivent être validées et verrouillées, et les bulletins générés.',
            'onglet' => null,
        ],
        'composition' => [
            'titre' => 'Jury composé',
            'aide' => 'Un président présent, des assesseurs et le quorum de l’établissement.',
            'onglet' => 'composition',
        ],
        'decisions' => [
            'titre' => 'Décisions calculées',
            'aide' => 'Une décision par étudiant, à jour des bulletins.',
            'onglet' => 'deliberation',
        ],
        'signatures' => [
            'titre' => 'Signatures recueillies',
            'aide' => 'Chaque membre présent signe depuis son propre compte.',
            'onglet' => 'composition',
        ],
    ];

    /**
     * @param  array<string, list<string>>  $raisonsParEtape
     * @return list<array{cle:string, titre:string, aide:string, onglet:?string, ok:bool, raisons:list<string>}>
     */
    public function etapes(array $raisonsParEtape): array
    {
        $etapes = [];
        foreach (self::ETAPES as $cle => $definition) {
            $raisons = array_values($raisonsParEtape[$cle] ?? []);
            $etapes[] = ['cle' => $cle] + $definition + ['ok' => $raisons === [], 'raisons' => $raisons];
        }

        return $etapes;
    }
}
