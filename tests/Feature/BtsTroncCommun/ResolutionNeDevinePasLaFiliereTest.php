<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « ON NE DEVINE JAMAIS » valait pour les matières, pas pour leur couple.
 *
 * Le corps de `ResolutionDeMatiere` est rigoureux sur les matières : une
 * ambiguïté est rendue avec ses candidats. Mais `filiere()` et `niveau()`
 * faisaient `where('name', $cle)->first()` — or `name` n'est unique ni sur
 * `esbtp_filieres` ni sur `esbtp_niveau_etudes`. Seul `code` l'est
 * (migrations de mars 2024). Deux homonymes, et la maquette se chargeait
 * contre l'une des deux au hasard de l'ordre d'insertion.
 *
 * Le second test garde un cas plus vicieux, propre aux instances mixtes :
 * `FiliereMiroirLmd` crée des filières au nom et au code d'un parcours LMD.
 * Une maquette BTS chargée en nommant sa filière pouvait tomber dessus et
 * n'apparaître sur aucune classe BTS. Silencieux des deux bouts.
 *
 * @see \App\Domain\BtsTroncCommun\ResolutionDeMatiere::filiere()
 * @see .claude/rules/classe-lmd-filiere-as-mention.md
 */
class ResolutionNeDevinePasLaFiliereTest extends TestCase
{
    use RefreshDatabase;

    public function test_deux_filieres_homonymes_rendent_une_ambiguite_nommee(): void
    {
        ESBTPFiliere::factory()->create(['name' => 'Genie Civil', 'code' => 'GC-A']);
        ESBTPFiliere::factory()->create(['name' => 'Genie Civil', 'code' => 'GC-B']);

        $resolue = app(ResolutionDeMatiere::class)->filiere('Genie Civil');

        $this->assertSame('ambigu', $resolue['statut'],
            'Un nom qui repond deux fois n\'en designe aucune.');
        $this->assertCount(2, $resolue['candidats']);
        $this->assertEqualsCanonicalizing(
            ['GC-A', 'GC-B'],
            array_column($resolue['candidats'], 'code'),
            'Les candidats doivent etre nommes : c\'est par leur code qu\'on tranche.'
        );
    }

    public function test_le_code_tranche_la_ou_le_nom_hesite(): void
    {
        ESBTPFiliere::factory()->create(['name' => 'Genie Civil', 'code' => 'GC-A']);
        ESBTPFiliere::factory()->create(['name' => 'Genie Civil', 'code' => 'GC-B']);

        $resolue = app(ResolutionDeMatiere::class)->filiere('GC-B');

        $this->assertSame('ok', $resolue['statut']);
        $this->assertSame('GC-B', $resolue['filiere']->code);
    }

    public function test_un_reflet_lmd_n_est_jamais_choisi_comme_filiere_bts(): void
    {
        $bts = ESBTPFiliere::factory()->create(['name' => 'Batiment', 'code' => 'BAT']);

        // Le reflet qu'une classe LMD fait creer : meme nom, code du parcours.
        // Le parcours est cree pour de vrai — la colonne porte une cle
        // etrangere, et une doublure qui la contourne ne mesurerait rien.
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences et Technologies', 'code' => 'ST']);
        $mention = ESBTPLMDMention::create([
            'name' => 'Genie Civil', 'code' => 'GC', 'domaine_id' => $domaine->id,
        ]);
        $parcours = ESBTPLMDParcours::create([
            'name' => 'Batiment', 'code' => 'BAT-LMD', 'mention_id' => $mention->id,
        ]);
        ESBTPFiliere::factory()->create([
            'name' => 'Batiment',
            'code' => 'BAT-LMD',
            'lmd_parcours_id' => $parcours->id,
        ]);

        $resolue = app(ResolutionDeMatiere::class)->filiere('Batiment');

        $this->assertSame('ok', $resolue['statut'],
            'Le reflet ne doit pas rendre le nom ambigu : il n\'est pas une filiere BTS.');
        $this->assertSame($bts->id, $resolue['filiere']->id);
    }

    public function test_le_niveau_se_resout_aussi_par_son_code(): void
    {
        // `niveau()` n'essayait meme pas la cle unique : seulement le nom.
        $niveau = ESBTPNiveauEtude::factory()->create([
            'name' => 'Deuxieme annee',
            'code' => '2A',
            'type' => 'BTS',
        ]);

        $resolue = app(ResolutionDeMatiere::class)->niveau('2A');

        $this->assertSame('ok', $resolue['statut']);
        $this->assertSame($niveau->id, $resolue['niveau']->id);
    }
}
