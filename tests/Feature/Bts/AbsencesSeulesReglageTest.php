<?php

namespace Tests\Feature\Bts;

use App\Helpers\SettingsHelper;
use App\Domain\Notes\PerimetreDeRecalcul;
use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Observers\ESBTPNoteObserver;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\NoteCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deux regles, une seule source :
 *
 * 1. Une absence ne compte jamais dans la moyenne d'une matiere. La generation
 *    officielle l'ecartait deja ; le calcul « Courant », la fiche Resultats et
 *    le repli annuel la comptaient pour 0.
 * 2. Une matiere faite SEULEMENT d'absences vaut ce que l'etablissement a
 *    choisi : 0 (defaut, comportement historique) ou « pas de moyenne ».
 *    Le reglage se lit dans `NoteCalculationService::moyenneSansNoteComptable()`.
 *
 * Decor commun : Anglais (coef 2) note 14, Marketing (coef 1).
 */
class AbsencesSeulesReglageTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;
    use SeedsConfiguredBulletin;

    private ESBTPMatiere $coefDeux;

    private ESBTPMatiere $coefUn;

    private ESBTPEtudiant $etudiant;

    private function matiereDeCoefficientUn(): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => 'general', 'coefficient' => 1],
        ]);

        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => 1,
        ]);

        return $matiere;
    }

    /** Absence telle qu'en production : note a 0, `is_absent` vrai. */
    private function absent(ESBTPEvaluation $evaluation): ESBTPNote
    {
        return ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $this->classe->id,
            'note' => 0,
            'is_absent' => true,
        ]);
    }

    private function monterLeDecor(): void
    {
        $this->monterLaClasse();
        $this->coefDeux = $this->matiereConfiguree();
        $this->coefUn = $this->matiereDeCoefficientUn();
        $this->etudiant = $this->etudiantInscrit();
        $this->noter($this->etudiant, $this->evaluationDe($this->coefDeux), 14);
    }

    private function exclureLesAbsencesSeules(): void
    {
        SettingsHelper::setOrCreate(NoteCalculationService::REGLAGE_ABSENCES_SEULES_COMPTENT_ZERO, '0', 'bulletin');
    }

    /** La moyenne figee par la generation officielle du bulletin du semestre. */
    private function moyenneOfficielle(): float
    {
        $bulletin = $this->seedConfiguredBulletin(
            $this->etudiant->id, $this->classe->id, $this->annee->id, 'semestre1', [$this->coefDeux->id, $this->coefUn->id], []
        );

        app(BulletinService::class)->genererDonneesBulletin(
            $this->etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        return (float) DB::table('esbtp_bulletins')->where('id', $bulletin->id)->value('moyenne_generale');
    }

    /** @return array{fiche: ?float, repli: ?float, courant: ?float} */
    private function lesTroisCalculs(): array
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::withoutEvents(fn () => User::factory()->create());
        $admin->assignRole('superAdmin');

        $fiche = $this->actingAs($admin)->get(route('esbtp.resultats.etudiant', [
            'etudiant' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'annuel',
        ]));
        $fiche->assertOk();

        return [
            'fiche' => $fiche->viewData('moyenneGenerale'),
            'repli' => app(BulletinService::class)->calculateStudentAverageForPeriode(
                $this->etudiant->id, $this->classe->id, $this->annee->id, 'annuel'
            ),
            'courant' => app(BtsCurrentResultSnapshotService::class)->getSemesterSnapshot(
                $this->etudiant->id, $this->classe->id, $this->annee->id, 'semestre1'
            )['raw_total'] ?? null,
        ];
    }

    public function test_une_absence_au_milieu_d_autres_notes_ne_compte_pas(): void
    {
        $this->monterLeDecor();
        // Anglais : 14 et une absence. Marketing : 8.
        $this->absent($this->evaluationDe($this->coefDeux));
        $this->noter($this->etudiant, $this->evaluationDe($this->coefUn), 8);

        // Anglais reste a 14 : (14 x 2 + 8 x 1) / 3 = 12,00. Compter l'absence
        // pour 0 faisait tomber Anglais a 7, et la moyenne a 7,33.
        foreach ($this->lesTroisCalculs() as $calcul => $moyenne) {
            $this->assertNotNull($moyenne, "Temoin : {$calcul} doit rendre une moyenne.");
            $this->assertEqualsWithDelta(12.0, (float) $moyenne, 0.01, "{$calcul} : l'absence ne compte pas (avec elle : 7,33).");
        }
    }

    public function test_par_defaut_une_matiere_d_absences_seules_compte_zero(): void
    {
        $this->monterLeDecor();
        $this->absent($this->evaluationDe($this->coefUn));

        // (14 x 2 + 0 x 1) / 3 = 9,33 : le comportement historique du bulletin.
        foreach ($this->lesTroisCalculs() as $calcul => $moyenne) {
            $this->assertEqualsWithDelta(9.33, (float) $moyenne, 0.01, "{$calcul} : par defaut, absences seules = 0.");
        }

        $this->assertEqualsWithDelta(9.33, $this->moyenneOfficielle(), 0.01, 'Generation officielle : absences seules = 0 par defaut.');
    }

    public function test_reglage_desactive_une_matiere_d_absences_seules_sort_du_calcul(): void
    {
        $this->monterLeDecor();
        $this->exclureLesAbsencesSeules();
        $this->absent($this->evaluationDe($this->coefUn));

        // Marketing n'a pas de moyenne : seule Anglais compte, 14,00.
        foreach ($this->lesTroisCalculs() as $calcul => $moyenne) {
            $this->assertNotNull($moyenne, "Temoin : {$calcul} doit rendre une moyenne.");
            $this->assertEqualsWithDelta(14.0, (float) $moyenne, 0.01, "{$calcul} : reglage desactive, absences seules ecartees.");
        }

        $this->assertEqualsWithDelta(14.0, $this->moyenneOfficielle(), 0.01, 'Generation officielle : la matiere sans moyenne sort du calcul.');
    }

    /**
     * Le recalcul automatique, qui tourne a chaque enregistrement de note.
     * Une ligne restee a 12 l'emporterait sur les notes : le reglage desactive,
     * elle doit disparaitre, pas rester ni tomber a 0.
     */
    public function test_le_recalcul_automatique_suit_le_reglage(): void
    {
        $this->monterLeDecor();
        $note = ESBTPNote::create([
            'evaluation_id' => $this->evaluationDe($this->coefUn)->id,
            'etudiant_id' => $this->etudiant->id,
            'matiere_id' => $this->coefUn->id,
            'classe_id' => $this->classe->id,
            'note' => 12,
            'is_absent' => false,
        ]);

        $ligne = fn () => ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $this->coefUn->id)->first();
        $this->assertEqualsWithDelta(12.0, (float) $ligne()?->moyenne, 0.01, 'Temoin : le recalcul a bien ecrit la ligne.');

        // Par defaut : l'eleve devient absent, la matiere tombe a 0.
        $note->update(['note' => 0, 'is_absent' => true]);
        $this->assertNotNull($ligne(), 'Par defaut, la ligne reste.');
        $this->assertEqualsWithDelta(0.0, (float) $ligne()->moyenne, 0.01, 'Par defaut, absences seules = 0.');

        // Reglage desactive : la ligne est retiree en douceur.
        $this->exclureLesAbsencesSeules();
        RecomputeStudentResultatJob::dispatchSync(
            etudiantId: $this->etudiant->id,
            classeId: $this->classe->id,
            matiereId: $this->coefUn->id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
        );
        $this->assertNull($ligne(), 'Reglage desactive : plus de moyenne enregistree.');
        $this->assertNotNull(
            ESBTPResultat::withTrashed()->where('etudiant_id', $this->etudiant->id)->where('matiere_id', $this->coefUn->id)->first(),
            'Retiree en douceur, pas detruite.'
        );
    }
    /**
     * Desactiver le reglage apres coup : les zeros deja enregistres doivent
     * partir au recalcul de rattrapage (`notes:recompute`, API CLI), sinon ils
     * l'emporteraient toujours sur les notes.
     */
    public function test_le_rattrapage_applique_le_reglage_aux_zeros_deja_enregistres(): void
    {
        $this->monterLeDecor();
        $this->absent($this->evaluationDe($this->coefUn));

        $ligne = fn () => ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $this->coefUn->id)->first();
        $this->assertEqualsWithDelta(0.0, (float) $ligne()?->moyenne, 0.01, 'Temoin : par defaut, le zero est enregistre.');

        $this->exclureLesAbsencesSeules();
        $resultat = PerimetreDeRecalcul::recalculerUnCouple([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->coefUn->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
        ], 'manual');

        $this->assertSame(PerimetreDeRecalcul::RECALCULE, $resultat['statut']);
        $this->assertNull($resultat['apres'], 'Plus de moyenne apres le rattrapage.');
        $this->assertNull($ligne(), 'Le zero enregistre est retire.');
    }
    /**
     * Le rattrapage ne retire QUE les zeros. Une moyenne posee a la main (un
     * oral de rattrapage, par exemple) sur une matiere d'absences reste la,
     * laissee a un humain.
     */
    public function test_le_rattrapage_laisse_une_moyenne_posee_a_la_main(): void
    {
        $this->monterLeDecor();
        $this->absent($this->evaluationDe($this->coefUn));
        ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $this->coefUn->id)
            ->update(['moyenne' => 11]);

        $this->exclureLesAbsencesSeules();
        $resultat = PerimetreDeRecalcul::recalculerUnCouple([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->coefUn->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
        ], 'manual');

        $this->assertSame(PerimetreDeRecalcul::LAISSEE, $resultat['statut']);
        $this->assertEqualsWithDelta(11.0, (float) ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $this->coefUn->id)->value('moyenne'), 0.01, 'La moyenne posee a la main reste.');
    }
    /**
     * Le tableau de `/esbtp/resultats` (`calculateStudentStatsFixed()`),
     * jumeau declare de la fiche. Il moyenne les matieres A EGALITE (voulu,
     * verrouille par `EcueLmdHorsDuBulletinNoteTest`). L'observateur est coupe
     * pour exercer le chemin des NOTES : sinon les lignes enregistrees par le
     * recalcul automatique primeraient et masqueraient le defaut.
     */
    public function test_le_tableau_des_resultats_suit_les_deux_regles(): void
    {
        ESBTPNoteObserver::$muted = true;

        try {
            $this->monterLeDecor();
            // Anglais : 14 et une absence. Marketing : absences seulement.
            $this->absent($this->evaluationDe($this->coefDeux));
            $this->absent($this->evaluationDe($this->coefUn));
        } finally {
            ESBTPNoteObserver::$muted = false;
        }

        $tableau = function (): ?float {
            $moyennes = [];
            $rangs = [];
            app(BulletinService::class)->calculateStudentStatsFixed(
                ESBTPEtudiant::whereKey($this->etudiant->id)->get(),
                ESBTPNote::with('evaluation.matiere')->where('etudiant_id', $this->etudiant->id)->get(),
                $moyennes, $rangs, $this->classe->id, $this->annee->id, 'semestre1'
            );

            return $moyennes[$this->etudiant->id] ?? null;
        };

        // Defaut : Anglais 14 (l'absence ne compte pas), Marketing 0 -> 7,00.
        // Avec l'absence comptee : Anglais 7, donc 3,50.
        $this->assertEqualsWithDelta(7.0, (float) $tableau(), 0.01, 'Par defaut : (14 + 0) / 2.');

        $this->exclureLesAbsencesSeules();
        $this->assertEqualsWithDelta(14.0, (float) $tableau(), 0.01, 'Reglage desactive : Marketing sort du calcul.');
    }
}
