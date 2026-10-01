<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Actions\Evaluations\CreerEvaluation;
use App\Domain\Assistant\Actions\Evaluations\PublierNotes;
use App\Domain\Assistant\Actions\RegistreDesActions;
use App\Models\ESBTPEvaluation;
use Mockery;
use Tests\TestCase;

/**
 * Ce qui se vérifie sans base : enregistrement, permissions déclarées, et la
 * lecture de ce que le modèle de langue envoie (période, horaires).
 */
class ActionsEvaluationsTest extends TestCase
{
    public function test_les_deux_actions_sont_enregistrees_et_gardees_par_une_permission(): void
    {
        $cles = array_map(fn ($a) => $a->cle(), app(RegistreDesActions::class)->toutes());
        $this->assertContains('creation_evaluation', $cles);
        $this->assertContains('publication_notes', $cles);

        $this->assertSame(['evaluations.create'], config('chatbot.tools.proposer_creation_evaluation.all_permissions'));
        $this->assertSame(['evaluations.edit'], config('chatbot.tools.proposer_publication_notes.all_permissions'));
    }

    public function test_sans_la_permission_l_outil_n_est_ni_expose_ni_execute(): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('can')->with('evaluations.create')->andReturnFalse();

        $action = app(CreerEvaluation::class);
        $this->assertFalse($action->isAvailableFor($user));
        $this->assertSame(['error' => 'Outil indisponible.'], $action->executeAuthorized([], $user));
    }

    public function test_le_type_propose_au_modele_est_la_liste_de_l_ecran(): void
    {
        $schema = app(CreerEvaluation::class)->parameters();

        $this->assertSame(ESBTPEvaluation::TYPES_SAISISSABLES, $schema['properties']['type']['enum']);
        $this->assertNotContains('bareme', $schema['required'], 'Le barème est demandé par la proposition, pas imposé au schéma.');
    }

    /** @dataProvider periodes */
    public function test_la_periode_est_normalisee_ou_refusee(string $brute, ?string $attendue): void
    {
        $this->assertSame($attendue, CreerEvaluation::normaliserPeriode($brute));
    }

    public static function periodes(): array
    {
        return [
            ['S1', 'semestre1'],
            ['s2', 'semestre2'],
            ['semestre3', 'semestre3'],
            ['Semestre 4', 'semestre4'],
            ['5', 'semestre5'],
            ['S11', null],
            ['S0', null],
            ['annuel', null],
            ['', null],
        ];
    }

    public function test_les_horaires_impossibles_sont_refuses(): void
    {
        [$debut, $fin] = CreerEvaluation::horaires('2026-10-12', '08:00', '10:30');
        $this->assertSame('2026-10-12 08:00', $debut->format('Y-m-d H:i'));
        $this->assertSame(150, (int) $debut->diffInMinutes($fin));

        $this->assertNull(CreerEvaluation::horaires('2026-02-31', '08:00', '10:00'), 'Un 31 février ne glisse pas au 3 mars.');
        $this->assertNull(CreerEvaluation::horaires('2026-10-12', '10:00', '08:00'), 'La fin précède le début.');
        $this->assertNull(CreerEvaluation::horaires('2026-10-12', '08:00', '08:00'));
        $this->assertNull(CreerEvaluation::horaires('12/10/2026', '08:00', '10:00'));
    }

    public function test_publier_sans_evaluation_designee_demande_au_lieu_de_deviner(): void
    {
        $user = Mockery::mock();

        $vide = app(PublierNotes::class)->preparer(['evaluation_ids' => []], $user);
        $this->assertFalse($vide->estComplete());
        $this->assertStringContainsString('search_evaluations', $vide->manques[0]);

        $trop = app(PublierNotes::class)->preparer(['evaluation_ids' => range(1, 51)], $user);
        $this->assertStringContainsString('50 au plus', $trop->manques[0]);
    }
}
