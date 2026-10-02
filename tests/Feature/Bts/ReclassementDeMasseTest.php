<?php

namespace Tests\Feature\Bts;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * La génération de masse ne reclasse plus la classe à chaque élève.
 *
 * Mesuré sur esbtp-abidjan (30 jours) : 62 099 audits « Bulletin updated »,
 * sans que la part de chaque cause soit mesurée. Celle-ci en est une :
 * chaque bulletin généré reclassait toute la classe, et un élève classé
 * premier décale tous les autres : la classe entière était réécrite à
 * chaque élève, ≈ N²/2 sauvegardes et autant de lignes d'audit.
 *
 * Ce qui est éprouvé : les rangs finaux sont ceux de l'ancienne boucle, au
 * bulletin près, et les écritures deviennent linéaires.
 *
 * BTS uniquement (rule lmd-bts-bulletin-separation).
 */
class ReclassementDeMasseTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    private const ELEVES = 8;

    public function test_les_rangs_finaux_sont_identiques_et_les_ecritures_lineaires(): void
    {
        [$etudiants] = $this->uneClasseNotee();
        $service = app(BulletinService::class);

        // Ancien comportement : chaque bulletin reclasse la classe.
        $avant = $this->auditsDeBulletin();
        $this->genererLaClasse($service, $etudiants);
        $service->calculerRangsPourClasse($this->classe->id, $this->annee->id, 'semestre1');
        $ancienneBoucle = $this->auditsDeBulletin() - $avant;
        $rangsAnciens = $this->rangs();

        $this->remettreABlanc();

        // Nouveau : un seul classement, après la boucle.
        $avant = $this->auditsDeBulletin();
        $service->sansReclasserLaClasse(fn () => $this->genererLaClasse($service, $etudiants));
        $service->calculerRangsPourClasse($this->classe->id, $this->annee->id, 'semestre1');
        $nouvelleBoucle = $this->auditsDeBulletin() - $avant;

        $this->assertCount(self::ELEVES, $rangsAnciens, 'Témoin : chaque élève doit avoir un rang.');
        $this->assertSame(range(1, self::ELEVES), array_values(array_reverse($rangsAnciens, true)), 'Témoin : notes croissantes, rangs décroissants.');
        $this->assertSame($rangsAnciens, $this->rangs(), 'Les rangs persistés ne doivent pas bouger.');
        $this->assertSame(
            $this->effectifs(),
            array_fill_keys(array_keys($rangsAnciens), self::ELEVES),
            "L'effectif de classe reste posé sur chaque bulletin."
        );

        // Ancienne boucle : chaque nouvel élève, premier, décalait tous les
        // précédents — au moins N(N-1)/2 réécritures du seul rang.
        $this->assertGreaterThanOrEqual(intdiv(self::ELEVES * (self::ELEVES - 1), 2), $ancienneBoucle);
        // Nouvelle : la sauvegarde de chaque bulletin, puis son classement.
        $this->assertLessThanOrEqual(2 * self::ELEVES, $nouvelleBoucle);
    }

    public function test_le_service_de_masse_classe_une_fois_et_reste_lineaire(): void
    {
        [$etudiants] = $this->uneClasseNotee();
        $avant = $this->auditsDeBulletin();

        $resultat = app(\App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService::class)
            ->generate($this->classe, (int) $this->annee->id, 'semestre1', null);

        $this->assertTrue($resultat->hasWrites(), 'Témoin : la génération a bien écrit des bulletins.');
        $rangs = $this->rangs();
        $this->assertCount(self::ELEVES, $rangs);
        $this->assertSame(range(1, self::ELEVES), array_values(array_reverse($rangs, true)), 'Notes croissantes, rangs décroissants.');
        // Le vrai chemin ajoute la synchronisation de configuration par élève :
        // trois sauvegardes au plus par bulletin, jamais N².
        $this->assertLessThanOrEqual(3 * self::ELEVES, $this->auditsDeBulletin() - $avant);
    }

    public function test_un_bulletin_genere_seul_reste_classe_aussitot(): void
    {
        [$etudiants] = $this->uneClasseNotee();
        $service = app(BulletinService::class);

        $this->genererLaClasse($service, $etudiants);

        // Sans génération de masse, le rang rendu et persisté est juste tout de suite.
        $dernier = end($etudiants);
        $donnees = $service->genererDonneesBulletin($dernier->id, $this->classe->id, $this->annee->id, 'semestre1');

        $this->assertSame(1, (int) $donnees['rang']);
        $this->assertSame(1, (int) $this->bulletinDe($dernier)->rang);
    }

    public function test_une_note_enregistree_n_ecrit_plus_d_audit_vide_sur_le_bulletin(): void
    {
        [$etudiants, $evaluation] = $this->uneClasseNotee();
        $etudiant = $etudiants[0];
        $bulletin = $this->bulletinDe($etudiant);
        DB::table('esbtp_bulletins')->where('id', $bulletin->id)->update(['updated_at' => now()->subDay()]);
        $avant = $this->auditsDeBulletin();

        app()->call([new RecomputeStudentResultatJob(
            etudiantId: $etudiant->id,
            classeId: $this->classe->id,
            matiereId: $evaluation->matiere_id,
            anneeUniversitaireId: $this->annee->id,
            periode: 'semestre1',
            source: 'observer',
        ), 'handle']);

        $this->assertSame($avant, $this->auditsDeBulletin(), 'Le signal « à régénérer » ne passe plus par le journal.');
        $this->assertTrue(
            $bulletin->fresh()->updated_at->greaterThan(now()->subHour()),
            'Témoin : le bulletin est bien marqué comme modifié.'
        );
    }

    public function test_regenerer_sans_changement_ne_reecrit_pas_les_moyennes_par_matiere(): void
    {
        [$etudiants] = $this->uneClasseNotee();
        $service = app(BulletinService::class);
        $this->genererLaClasse($service, $etudiants);

        $resultat = ESBTPResultat::where('etudiant_id', $etudiants[0]->id)->firstOrFail();
        $auteur = $resultat->created_by;
        $avant = DB::table('audits')->where('auditable_type', ESBTPResultat::class)->count();

        $this->actingAs(\App\Models\User::factory()->create());
        $service->genererDonneesBulletin($etudiants[0]->id, $this->classe->id, $this->annee->id, 'semestre1');

        $this->assertSame($avant, DB::table('audits')->where('auditable_type', ESBTPResultat::class)->count());
        $this->assertSame($auteur, $resultat->fresh()->created_by, "L'auteur de la création reste celui de la création.");
    }

    /**
     * Notes croissantes : chaque élève généré passe premier, le pire cas de
     * l'ancienne boucle.
     *
     * @return array{0: list<ESBTPEtudiant>, 1: ESBTPEvaluation}
     */
    private function uneClasseNotee(): array
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);

        $etudiants = [];
        for ($i = 0; $i < self::ELEVES; $i++) {
            $etudiant = $this->etudiantInscrit();
            $this->noter($etudiant, $evaluation, 8 + $i);
            $this->seedConfiguredBulletin(
                $etudiant->id, $this->classe->id, $this->annee->id, 'semestre1', [$matiere->id], [], [$matiere->id => 'M. Kouadio']
            );
            $etudiants[] = $etudiant;
        }

        return [$etudiants, $evaluation];
    }

    /** @param list<ESBTPEtudiant> $etudiants */
    private function genererLaClasse(BulletinService $service, array $etudiants): void
    {
        foreach ($etudiants as $etudiant) {
            $service->genererDonneesBulletin($etudiant->id, $this->classe->id, $this->annee->id, 'semestre1');
        }
    }

    private function remettreABlanc(): void
    {
        DB::table('esbtp_bulletins')
            ->where('classe_id', $this->classe->id)
            ->update(['moyenne_generale' => null, 'rang' => null, 'effectif_classe' => null]);
    }

    /** @return array<int, int> rang par élève */
    private function rangs(): array
    {
        return DB::table('esbtp_bulletins')
            ->where('classe_id', $this->classe->id)
            ->orderBy('etudiant_id')
            ->pluck('rang', 'etudiant_id')
            ->map(fn ($rang) => (int) $rang)
            ->all();
    }

    /** @return array<int, int> */
    private function effectifs(): array
    {
        return DB::table('esbtp_bulletins')
            ->where('classe_id', $this->classe->id)
            ->orderBy('etudiant_id')
            ->pluck('effectif_classe', 'etudiant_id')
            ->map(fn ($effectif) => (int) $effectif)
            ->all();
    }

    private function bulletinDe(ESBTPEtudiant $etudiant): ESBTPBulletin
    {
        return ESBTPBulletin::where('etudiant_id', $etudiant->id)->where('classe_id', $this->classe->id)->firstOrFail();
    }

    private function auditsDeBulletin(): int
    {
        return DB::table('audits')
            ->where('auditable_type', ESBTPBulletin::class)
            ->where('event', 'updated')
            ->count();
    }
}
