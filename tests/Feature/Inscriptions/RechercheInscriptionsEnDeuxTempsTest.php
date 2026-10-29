<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\InscriptionSearchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La recherche classe des candidats légers, puis ne charge en entier que la
 * page affichée.
 */
class RechercheInscriptionsEnDeuxTempsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seule_la_page_affichee_est_chargee_avec_ses_relations(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        $ids = [];
        foreach (range(1, 12) as $i) {
            $inscription = ESBTPInscription::factory()->create([
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $annee->id,
                'status' => 'active',
            ]);
            $inscription->etudiant->forceFill(['nom' => 'KOUASSI', 'prenoms' => "Awa {$i}"])->save();
            $ids[] = $inscription->id;
        }

        $base = ESBTPInscription::query()
            ->with(['etudiant', 'classe', 'paiements'])
            ->where('annee_universitaire_id', $annee->id);

        $requetesPaiements = [];
        DB::listen(function ($q) use (&$requetesPaiements) {
            if (str_contains($q->sql, 'esbtp_paiements')) {
                // Laravel inscrit les clés entières en clair (whereIntegerInRaw) :
                // on les compte dans le SQL, pas dans les liaisons.
                preg_match('/in \(([^)]*)\)/i', $q->sql, $m);
                $requetesPaiements[] = isset($m[1]) ? array_filter(explode(',', $m[1])) : [];
            }
        });

        $page = app(InscriptionSearchService::class)->search($base, 'kouassi', 5, '/esbtp/inscriptions');

        $this->assertSame(12, $page->total());
        $this->assertCount(5, $page->items());

        foreach ($page->items() as $ligne) {
            $this->assertInstanceOf(ESBTPInscription::class, $ligne);
            $this->assertTrue($ligne->relationLoaded('paiements'));
            $this->assertNotNull($ligne->annee_universitaire_id, 'la ligne affichée porte toutes ses colonnes');
            $this->assertContains($ligne->id, $ids);
        }

        // Les paiements ne sont demandés que pour la page, pas pour les douze candidats.
        $this->assertCount(1, $requetesPaiements);
        $this->assertLessThanOrEqual(5, count($requetesPaiements[0]));
    }

    public function test_l_ordre_du_classement_est_conserve(): void
    {
        $classe = ESBTPClasse::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        foreach (['ZADI Marc', 'KONE Ali', 'KONE Alimata'] as $nomComplet) {
            [$nom, $prenoms] = explode(' ', $nomComplet);
            $inscription = ESBTPInscription::factory()->create([
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $annee->id,
            ]);
            $inscription->etudiant->forceFill(['nom' => $nom, 'prenoms' => $prenoms])->save();
        }

        $base = ESBTPInscription::query()->with('etudiant')->where('annee_universitaire_id', $annee->id);
        $sans = app(InscriptionSearchService::class);

        $page = $sans->search($base, 'kone ali', 15, '/esbtp/inscriptions');
        $noms = collect($page->items())->map(fn ($l) => $l->etudiant->nom.' '.$l->etudiant->prenoms)->all();

        $this->assertSame(['KONE Alimata', 'KONE Ali'], $noms, 'l\'ordre du classement de la recherche est celui d\'avant le chargement en deux temps');
    }
}
