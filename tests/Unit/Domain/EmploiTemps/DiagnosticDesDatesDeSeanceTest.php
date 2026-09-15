<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\DiagnosticDesDatesDeSeance;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use PHPUnit\Framework\TestCase;

/**
 * Les deux décisions du relevé qui ne dépendent pas de la base.
 *
 * Le reste — le comptage, le groupement par enseignant, le rattrapage — est une
 * requête SQL, et cet environnement n'a pas de MySQL. Ce n'est PAS couvert ici,
 * et il faut le dire plutôt que laisser croire le contraire : le relevé étant en
 * lecture seule, son premier passage sur une instance est sa première mesure.
 *
 * Ce qui est couvert est ce qui porte une décision :
 *
 *  - **pourquoi** une date n'a pas pu être recalculée — le libellé sert dans
 *    les deux rapports (relevé et rattrapage), et deux libellés divergents
 *    feraient deux comptes qui ne se recoupent pas ;
 *  - **combien d'heures** une séance fait disparaître de la paie, qui est le
 *    seul chiffre rendant ce défaut visible.
 */
class DiagnosticDesDatesDeSeanceTest extends TestCase
{
    private function emploiTemps(?string $dateDebut): ESBTPEmploiTemps
    {
        $emploiTemps = new ESBTPEmploiTemps;
        // `setRawAttributes` : écrire `date_debut`, qui est casté en `date`,
        // passerait par `getDateFormat()` et réclamerait une connexion.
        $emploiTemps->setRawAttributes(['date_debut' => $dateDebut], true);

        return $emploiTemps;
    }

    private function seance(?string $debut, ?string $fin): ESBTPSeanceCours
    {
        $seance = new ESBTPSeanceCours;
        $seance->setRawAttributes(['heure_debut' => $debut, 'heure_fin' => $fin], true);

        return $seance;
    }

    public function test_sans_emploi_du_temps_la_date_est_irrattrapable(): void
    {
        $this->assertSame(
            'emploi du temps introuvable',
            DiagnosticDesDatesDeSeance::raisonDeLEchec(null, 'Lundi'),
        );
    }

    public function test_un_emploi_du_temps_sans_debut_prime_sur_le_jour(): void
    {
        // L'ordre compte : sans date de début, AUCUN jour ne donne de date, même
        // parfaitement lisible. Annoncer « jour illisible » enverrait corriger le
        // mauvais bout.
        $this->assertSame(
            'emploi du temps sans date de début',
            DiagnosticDesDatesDeSeance::raisonDeLEchec($this->emploiTemps(null), 'Lundi'),
        );
    }

    public function test_avec_une_periode_l_echec_vient_du_jour(): void
    {
        $this->assertSame(
            'jour illisible',
            DiagnosticDesDatesDeSeance::raisonDeLEchec($this->emploiTemps('2026-09-16'), 'Dimanche'),
        );
    }

    public function test_la_duree_se_compte_en_heures_decimales(): void
    {
        $this->assertSame(2.0, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('08:00', '10:00')));
        $this->assertSame(1.5, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('08:00', '09:30')));
        $this->assertSame(3.25, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('07:45:00', '11:00:00')));
    }

    public function test_des_bornes_manquantes_ou_inversees_comptent_zero(): void
    {
        // Zéro plutôt qu'un négatif ou une estimation : ce total part dans un
        // rapport qui sert à décider d'un rattrapage sur une instance en
        // service. Un chiffre inventé y vaut moins que rien.
        $this->assertSame(0.0, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance(null, '10:00')));
        $this->assertSame(0.0, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('08:00', null)));
        $this->assertSame(0.0, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('10:00', '08:00')));
        $this->assertSame(0.0, DiagnosticDesDatesDeSeance::dureeEnHeures($this->seance('10:00', '10:00')));
    }
}
