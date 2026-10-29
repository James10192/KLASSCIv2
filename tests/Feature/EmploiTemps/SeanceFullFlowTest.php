<?php

namespace Tests\Feature\EmploiTemps;

use App\Enums\TypeSeance;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Services\VolumeBudgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Couvre les 4 points critiques du plan D LMD volume tracking :
 *  1. TypeSeance enum (CM/TD/TP UPPERCASE) bien persisté + retrouvé en DB.
 *  2. VolumeBudgetService::forClasse agrège correctement par type_seance (réalisé).
 *  3. La cascade UPPERCASE fonctionne sur les types existants (pas de literal 'cours'/'td').
 *  4. TypeSeance::badgeStyles() retourne un style monochrome cohérent par case.
 */
class SeanceFullFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_volume_budget_service_aggregates_realized_hours_by_type_seance(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $annee  = ESBTPAnneeUniversitaire::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        ESBTPPlanificationAcademique::create([
            'filiere_id'              => $classe->filiere_id,
            'niveau_etude_id'         => $classe->niveau_etude_id,
            'semestre'                => 1,
            'matiere_id'              => $matiere->id,
            'annee_universitaire_id'  => $annee->id,
            'volume_horaire_cm'       => 20,
            'volume_horaire_td'       => 10,
            'volume_horaire_tp'       => 5,
            'volume_horaire_total'    => 35,
        ]);

        $this->insertSeance($classe->id, $matiere->id, $annee->id, TypeSeance::CM, '08:00', '10:00');  // 2h
        $this->insertSeance($classe->id, $matiere->id, $annee->id, TypeSeance::CM, '10:00', '12:00');  // 2h
        $this->insertSeance($classe->id, $matiere->id, $annee->id, TypeSeance::TD, '14:00', '15:00');  // 1h
        $this->insertSeance($classe->id, $matiere->id, $annee->id, TypeSeance::TP, '15:00', '16:30');  // 1.5h
        $this->insertSeance($classe->id, $matiere->id, $annee->id, TypeSeance::AUTRE, '16:30', '17:30'); // 1h - ignored

        $service = app(VolumeBudgetService::class);
        $budgets = $service->forClasse($classe, $classe->niveau_etude_id, 1, $annee->id);

        $this->assertArrayHasKey($matiere->id, $budgets);
        $this->assertSame(20.0, (float) $budgets[$matiere->id]['cm']['planifie']);
        $this->assertSame(4.0,  (float) $budgets[$matiere->id]['cm']['realise']);
        $this->assertSame(10.0, (float) $budgets[$matiere->id]['td']['planifie']);
        $this->assertSame(1.0,  (float) $budgets[$matiere->id]['td']['realise']);
        $this->assertSame(5.0,  (float) $budgets[$matiere->id]['tp']['planifie']);
        $this->assertSame(1.5,  (float) $budgets[$matiere->id]['tp']['realise']);
    }

    public function test_type_seance_persisted_uppercase_after_eloquent_save(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $annee  = ESBTPAnneeUniversitaire::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        $seance = new ESBTPSeanceCours();
        $seance->classe_id              = $classe->id;
        $seance->matiere_id             = $matiere->id;
        $seance->annee_universitaire_id = $annee->id;
        $seance->jour                   = 'Lundi';
        $seance->heure_debut            = Carbon::parse('08:00');
        $seance->heure_fin              = Carbon::parse('10:00');
        $seance->type_seance            = TypeSeance::CM;
        $seance->is_active              = true;
        $seance->save();

        $raw = DB::table('esbtp_seance_cours')->where('id', $seance->id)->value('type_seance');
        $this->assertSame('CM', $raw, 'type_seance must be persisted as UPPERCASE enum value, not lowercase legacy.');

        $reloaded = ESBTPSeanceCours::find($seance->id);
        $this->assertInstanceOf(TypeSeance::class, $reloaded->type_seance);
        $this->assertSame(TypeSeance::CM, $reloaded->type_seance);
    }

    public function test_type_seance_badge_styles_returns_monochrome_blue_for_lmd_types(): void
    {
        $styles = TypeSeance::badgeStyles();

        foreach ([TypeSeance::CM, TypeSeance::TD, TypeSeance::TP, TypeSeance::PROJET] as $case) {
            $this->assertArrayHasKey($case->value, $styles, "Missing style for {$case->value}");
            $this->assertStringContainsString('4, 83, 203', $styles[$case->value]['bg'],
                "Type {$case->value} must use KLASSCI blue (4,83,203) in bg, found: {$styles[$case->value]['bg']}");
        }

        $this->assertStringContainsString('220, 38, 38', $styles[TypeSeance::EXAMEN->value]['bg'],
            'EXAMEN must keep semantic red (action critique).');

        $this->assertSame('CM', TypeSeance::CM->value);
        $this->assertSame('Cours Magistral', TypeSeance::CM->label());
        $this->assertStringContainsString('background:', TypeSeance::CM->badgeInlineStyle());
        $this->assertStringContainsString('fa-', TypeSeance::CM->badgeIcon());
    }

    public function test_from_legacy_maps_lowercase_to_canonical_uppercase(): void
    {
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy('cours'));
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy(null));
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy(''));
        $this->assertSame(TypeSeance::CM,    TypeSeance::fromLegacy('cm'));
        $this->assertSame(TypeSeance::TD,    TypeSeance::fromLegacy('TD'));
        $this->assertSame(TypeSeance::TP,    TypeSeance::fromLegacy(' tp '));
        $this->assertSame(TypeSeance::EXAMEN, TypeSeance::fromLegacy('examen'));
    }

    private function insertSeance(
        int $classeId,
        int $matiereId,
        int $anneeId,
        TypeSeance $type,
        string $start,
        string $end
    ): void {
        DB::table('esbtp_seance_cours')->insert([
            'classe_id'              => $classeId,
            'matiere_id'             => $matiereId,
            'annee_universitaire_id' => $anneeId,
            'type_seance'            => $type->value,
            'jour'                   => 'Lundi',
            'heure_debut'            => $start,
            'heure_fin'              => $end,
            'is_active'              => true,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
    }
}
