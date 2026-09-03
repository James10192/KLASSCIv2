<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Verrouille la note retenue pour un enseignement quand une seconde session a eu lieu.
 *
 * Ces tests n'ouvrent aucune base : ils appellent le vrai service avec un profil de
 * regles dont le resolveur de reglages est simule, et lui passent un resultat
 * d'enseignement sous forme d'objet simple. Le service ne lit cet objet que par ses
 * proprietes « note_finale » et « moyenne ».
 *
 * L'enjeu tient en une phrase : sans cette note effective, un etudiant qui obtient 14
 * au rattrapage apres un 7 en premiere session verrait son unite rester non acquise,
 * ses credits non capitalises et son jury deliberer sur le 7.
 */
class LMDBulletinServiceNoteEffectiveTest extends TestCase
{
    /** Construit le service avec les reglages d'une ecole donnee. */
    private function service(array $reglages = []): LMDBulletinService
    {
        $profil = new LmdAcademicRuleProfile(
            fn (string $cle, mixed $defaut = null): mixed => $reglages[$cle] ?? $defaut
        );

        return new LMDBulletinService($profil);
    }

    /**
     * Resultat d'enseignement minimal, tel que le service le consomme.
     *
     * Les valeurs sont passees en chaine pour reproduire fidelement ce que rend la
     * conversion decimale d'Eloquent sur ces deux colonnes (« 7.00 », « 0.00 »).
     */
    private function resultatEcue(?string $moyenne, ?string $noteFinale = null): stdClass
    {
        $resultat = new stdClass();
        $resultat->moyenne = $moyenne;
        $resultat->note_finale = $noteFinale;

        return $resultat;
    }

    // ---------------------------------------------------------------------
    // Pas de seconde session : la premiere session fait foi
    // ---------------------------------------------------------------------

    public function test_sans_seconde_session_la_moyenne_de_premiere_session_est_retenue(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue('7.00', null));

        $this->assertSame(7.0, $note);
    }

    public function test_sans_note_du_tout_il_n_y_a_pas_de_note_retenue(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue(null, null));

        $this->assertNull($note);
    }

    // ---------------------------------------------------------------------
    // Le piege du zero : une note nulle est une note, pas une absence de note
    // ---------------------------------------------------------------------

    public function test_une_note_finale_de_zero_ecrase_la_moyenne_de_premiere_session(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue('12.00', '0.00'));

        $this->assertSame(0.0, $note, 'Un zero de seconde session est une note, pas une absence de note.');
    }

    public function test_une_note_finale_de_zero_reste_retenue_meme_sans_premiere_session(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue(null, '0.00'));

        $this->assertSame(0.0, $note);
    }

    // ---------------------------------------------------------------------
    // Seconde session presente : elle prime, dans les deux sens
    // ---------------------------------------------------------------------

    public function test_une_note_finale_superieure_remplace_la_premiere_session(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue('7.00', '14.00'));

        $this->assertSame(14.0, $note);
    }

    public function test_une_note_finale_inferieure_remplace_aussi_la_premiere_session(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue('14.00', '9.50'));

        $this->assertSame(
            9.5,
            $note,
            "En mode remplacement, l'ecole assume que la seconde session prime meme si elle est moins bonne."
        );
    }

    public function test_une_note_finale_sans_premiere_session_est_retenue(): void
    {
        $note = $this->service()->noteEffectiveECUE($this->resultatEcue(null, '11.25'));

        $this->assertSame(11.25, $note);
    }

    /**
     * Le seuil de validation de l'ecole ne doit pas influer sur la note retenue :
     * il tranche l'acquisition en aval, pas le choix de la note.
     */
    public function test_la_note_retenue_ne_depend_pas_du_seuil_de_validation_de_l_ecole(): void
    {
        $service = $this->service(['lmd_validation_threshold' => 12]);

        $this->assertSame(9.5, $service->noteEffectiveECUE($this->resultatEcue('14.00', '9.50')));
        $this->assertSame(0.0, $service->noteEffectiveECUE($this->resultatEcue('14.00', '0.00')));
    }
}
