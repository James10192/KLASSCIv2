<?php

namespace Tests\Feature\AcademicPilotage;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Le bandeau de couverture, rendu pour de vrai.
 *
 * Un gabarit Blade casse en silence : il se compile sans erreur et n'echoue
 * qu'au rendu. Ces tests le rendent donc reellement, plutot que de lire son
 * texte source — ce qui ne prouverait rien.
 *
 * Ce qui est verifie ici : la porte de permission, l'adresse construite, et les
 * deux facons de lui donner un contexte.
 */
class BandeauCouvertureTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private User $lecteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();

        Permission::findOrCreate('academic_health.view', 'web');

        $this->lecteur = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->lecteur->givePermissionTo('academic_health.view');
    }

    /** @param array<string, mixed> $parametres */
    private function rendre(array $parametres, ?User $acteur = null): string
    {
        $this->actingAs($acteur ?? $this->lecteur);

        return view('esbtp.partials._couverture-notes', $parametres)->render();
    }

    /**
     * La configuration passee a la fabrique, lisible.
     *
     * `@js` echappe les guillemets en `"` : on les retablit pour que les
     * assertions parlent du contenu, pas de son encodage.
     */
    private function config(string $html): string
    {
        return str_replace('\u0022', '"', $html);
    }

    public function test_le_bandeau_porte_l_adresse_de_la_classe_demandee(): void
    {
        $html = $this->rendre([
            'classeId' => $this->classe->id,
            'anneeId' => $this->annee->id,
            'periode' => 'semestre1',
        ]);

        // La fabrique est presente, et le contexte lui est passe.
        $config = $this->config($html);
        $this->assertStringContainsString('couvertureNotes(', $html);
        $this->assertStringContainsString('"classeId":'.$this->classe->id, $config);
        $this->assertStringContainsString('"anneeId":'.$this->annee->id, $config);
        $this->assertStringContainsString('"periode":"semestre1"', $config);

        // Le gabarit d'adresse porte le marqueur de classe, celui que la
        // fabrique remplace pour suivre un selecteur sans recharger la page.
        $this->assertStringContainsString('__CLASSE__', $html);
        $this->assertStringContainsString('/couverture', $html);
    }

    /**
     * Sans droit de lecture, le bandeau n'existe pas dans la page.
     *
     * Ce n'est pas une question d'esthetique : un message d'erreur sur un droit
     * manquant n'apprend rien a quelqu'un qui ne peut rien y faire.
     */
    public function test_sans_permission_le_bandeau_ne_sort_pas(): void
    {
        $intrus = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $html = $this->rendre([
            'classeId' => $this->classe->id,
            'anneeId' => $this->annee->id,
            'periode' => 'semestre1',
        ], $intrus);

        $this->assertSame('', trim($html));
    }

    /**
     * Les pages qui font CHOISIR la classe rendent le bandeau sans contexte :
     * il reste muet jusqu'a ce qu'on lui en annonce un.
     */
    public function test_sans_classe_le_bandeau_est_rendu_mais_en_attente(): void
    {
        $html = $this->rendre([
            'classeId' => null,
            'anneeId' => $this->annee->id,
            'periode' => 'annuel',
        ]);

        $this->assertStringContainsString('couvertureNotes(', $html);
        $this->assertStringContainsString('"classeId":null', $this->config($html));
        // `pret()` gouverne l'affichage : pas de classe, pas de bandeau.
        $this->assertStringContainsString('pret()', $html);
    }

    /** Le pont vers les selecteurs natifs se rend aussi, et sait quoi ecouter. */
    public function test_le_pont_vers_les_selecteurs_annonce_le_contexte(): void
    {
        $this->actingAs($this->lecteur);

        $html = view('esbtp.partials._couverture-notes-suivre-selects', [
            'selectClasse' => '#classe_id',
            'selectPeriode' => '#periode',
            'anneeId' => $this->annee->id,
        ])->render();

        $this->assertStringContainsString('couverture:contexte', $html);
        $this->assertStringContainsString('#classe_id', $html);
        $this->assertStringContainsString('#periode', $html);
        $this->assertStringContainsString((string) $this->annee->id, $html);
    }
}
