<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\ConflitsDUnCreneau;
use App\Models\ESBTPEmploiTemps;
use PHPUnit\Framework\TestCase;

/**
 * Les refus que `ConflitsDUnCreneau` prononce SANS interroger la base.
 *
 * Ce sont les seuls cas qu'un test unitaire peut couvrir ici : les trois
 * recherches (enseignant, salle, classe) sont des requêtes SQL, et cette
 * machine n'a pas de MySQL. Ce qui est vérifié ci-dessous, c'est la porte
 * d'entrée — et elle porte la décision qui compte : que faire quand la date
 * n'est pas calculable.
 *
 * Ce qui n'est PAS couvert, et qui demande une base : le contenu des trois
 * recherches, l'exclusion de la séance modifiée, et le filtre d'emploi du temps
 * en vigueur.
 */
class ConflitsDUnCreneauTest extends TestCase
{
    private function emploiTemps(?string $dateDebut): ESBTPEmploiTemps
    {
        $emploiTemps = new ESBTPEmploiTemps;

        // `setRawAttributes` et non une affectation : `date_debut` est casté en
        // `date`, et ÉCRIRE un casté passe par `getDateFormat()`, qui réclame
        // une connexion. La LECTURE, elle, reconnaît « Y-m-d » sans connexion.
        // C'est ce qui garde ce test sans base.
        $emploiTemps->setRawAttributes(['date_debut' => $dateDebut, 'classe_id' => 1], true);

        return $emploiTemps;
    }

    public function test_une_date_incalculable_est_dite_et_non_tue(): void
    {
        // Le silence ressemblerait à une autorisation : « aucun conflit »
        // signifierait pour l'appelant que le créneau est libre, alors qu'on
        // n'a rien pu vérifier du tout.
        $conflits = (new ConflitsDUnCreneau($this->emploiTemps(null)))
            ->pourUneNouvelleSeance('Lundi', '08:00', '10:00', 3, 'A1');

        $this->assertCount(1, $conflits);
        $this->assertStringContainsString('illisible', $conflits[0]);
    }

    public function test_un_jour_illisible_est_dit_lui_aussi(): void
    {
        // La semaine KLASSCI va du lundi au samedi : « Dimanche » n'a pas de
        // rang, donc pas de date, donc rien de vérifiable.
        $conflits = (new ConflitsDUnCreneau($this->emploiTemps('2026-09-14')))
            ->pourUneNouvelleSeance('Dimanche', '08:00', '10:00', 3, 'A1');

        $this->assertCount(1, $conflits);
        $this->assertStringContainsString('illisible', $conflits[0]);
    }

    public function test_sans_horaire_aucune_recherche_n_est_lancee(): void
    {
        // Un créneau sans bornes ne chevauche rien : on rend une liste vide
        // plutôt que d'émettre trois requêtes dont la clause de chevauchement
        // comparerait des nuls.
        $creneau = new ConflitsDUnCreneau($this->emploiTemps('2026-09-14'));

        $this->assertSame([], $creneau->pourUneNouvelleSeance('Lundi', null, '10:00', 3, 'A1'));
        $this->assertSame([], $creneau->pourUneNouvelleSeance('Lundi', '08:00', null, 3, 'A1'));
    }
}
