<?php

namespace App\Mail\Parents;

/**
 * Les onze avis aux parents, et des données d'exemple qui les rendent tous
 * sans variable indéfinie.
 *
 * Seule source de ces données : le test des boutons
 * (`tests/Unit/Mail/BoutonsDesAvisAuxParentsTest`) et l'essai d'envoi par la CLI
 * (`App\Services\Courriels\EssaiDesAvisAuxParents`) les lisent tous les deux.
 * Rien ici ne désigne une personne réelle : les adresses pointent vers
 * `ecole.test`, domaine réservé qui ne résout nulle part.
 */
final class AvisDExemple
{
    /** Nom du gabarit (`esbtp.emails.parents.<nom>`) => classe Mailable qui l'envoie. */
    public const MAILABLES = [
        'absence-notification' => AbsenceNotificationMail::class,
        'bulletin-published' => BulletinPublishedMail::class,
        'inscription-confirmation' => InscriptionConfirmationMail::class,
        'low-attendance' => LowAttendanceMail::class,
        'low-grades' => LowGradesMail::class,
        'note-published' => NotePublishedMail::class,
        'paiement-created' => PaiementCreatedMail::class,
        'paiement-rejete' => PaiementRejeteMail::class,
        'paiement-relance' => PaiementRelanceMail::class,
        'paiement-valide' => PaiementValideMail::class,
        'reinscription-confirmation' => ReinscriptionConfirmationMail::class,
    ];

    /** @return list<string> */
    public static function noms(): array
    {
        return array_keys(self::MAILABLES);
    }

    public static function donnees(): array
    {
        return [
            'parentName' => 'M. Koné', 'studentName' => 'Awa Koné', 'classe' => '2A BTS Bâtiment',
            'filiere' => 'Bâtiment', 'niveauEtude' => 'BTS 2', 'matricule' => 'ESB-2026-014',
            'anneeUniversitaire' => '2026-2027', 'periode' => 'Semestre 1', 'periodeStats' => 'septembre 2026',
            'dateInscription' => '01/10/2026', 'username' => 'awa.kone', 'password' => 'Bienvenue2026!',
            'montant' => 150000, 'montantTotal' => 450000, 'montantPaye' => 300000, 'montantDu' => 150000,
            'resteDu' => 150000, 'pourcentagePaye' => 67, 'reference' => 'PAY-0042', 'numeroRecu' => 'R-0042',
            'modePaiement' => 'Espèces', 'datePaiement' => '30/09/2026', 'dateValidation' => '01/10/2026',
            'dateSoumission' => '30/09/2026', 'dateRejet' => '01/10/2026', 'motifRejet' => 'Reçu illisible',
            'validePar' => 'Comptabilité', 'echeance' => '15/10/2026', 'joursRestants' => 14,
            'historiqueRelances' => 2, 'modesPaiement' => ['Espèces', 'Wave'],
            'matiere' => 'Résistance des matériaux', 'note' => 14.5, 'bareme' => 20, 'typeEvaluation' => 'Devoir',
            'dateEvaluation' => '28/09/2026', 'moyenneClasse' => 11.2, 'rang' => 4, 'effectifClasse' => 32,
            'appreciation' => 'Bon travail', 'moyenneGenerale' => 8.4, 'mention' => 'Passable',
            'mentionColor' => '#64748b', 'decision' => 'Admis', 'appreciationGenerale' => 'Peut mieux faire',
            'noteAssiduite' => 0.13, 'totalAbsences' => 5, 'absencesJustifiees' => 2, 'absencesNonJustifiees' => 3,
            'tauxPresence' => 78, 'seuilPresence' => 80, 'requiresSignature' => false, 'date' => '30/09/2026', 'heureDebut' => '08:00',
            'heureFin' => '10:00', 'typeActivite' => 'Cours', 'commentaire' => null, 'coursDisponibles' => true,
            'matieresEnDifficulte' => [['nom' => 'Mathématiques', 'moyenne' => 7.5, 'coefficient' => 3]],
            'dateReinscription' => '30/09/2026',
            'platformUrl' => 'https://ecole.test/login', 'suiviUrl' => 'https://ecole.test/paiements/42',
            'paiementUrl' => 'https://ecole.test/paiements', 'noteUrl' => 'https://ecole.test/notes',
            'absencesUrl' => 'https://ecole.test/absences', 'justificationUrl' => 'https://ecole.test/absences/justifier',
            'recuUrl' => 'https://ecole.test/recus/42', 'bulletinUrl' => 'https://ecole.test/bulletins/7',
            'contactUrl' => 'https://ecole.test/contact', 'emailTitle' => 'Avis aux parents',
        ];
    }
}
