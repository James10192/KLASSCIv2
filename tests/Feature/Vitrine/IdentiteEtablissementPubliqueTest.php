<?php

namespace Tests\Feature\Vitrine;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'identite qu'une instance montre a klassci.com.
 *
 * Surface non authentifiee : n'importe qui sur Internet peut l'appeler. Ces
 * tests verrouillent donc deux choses differentes. D'abord ce qu'elle DIT —
 * assez pour qu'un logo et des couleurs arrivent sur le site vitrine. Ensuite,
 * et surtout, ce qu'elle NE DIT PAS : rien d'un etudiant, rien d'un contact,
 * rien du contrat de l'ecole avec KLASSCI.
 *
 * Le troisieme sujet est le repli. Une ecole sans logo ne doit jamais recevoir
 * celui de KLASSCI : le site afficherait alors six fois la meme image en
 * annoncant « nos etablissements ».
 */
class IdentiteEtablissementPubliqueTest extends TestCase
{
    use RefreshDatabase;

    private string $dossierLogos;

    protected function setUp(): void
    {
        parent::setUp();

        // CheckInstalled, middleware global, redirige vers l'assistant tant
        // qu'aucun superAdmin n'existe. En production il y en a un ; sans ces
        // lignes le test mesurerait cette redirection.
        User::factory()->create(['id' => 1])
            ->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));

        $this->dossierLogos = storage_path('app/public/logos');
        File::ensureDirectoryExists($this->dossierLogos);

        $this->reglages([
            'school_name' => 'Ecole Superieure des Travaux Publics',
            'school_acronym' => 'ESBTP',
            'school_city' => 'Yamoussoukro',
            'pdf_primary_color' => '#8b1d3f',
            'pdf_header_bg_color' => '#8b1d3f',
            'pdf_header_text' => "  Ecole Superieure  \n des Travaux Publics ",
        ]);
    }

    protected function tearDown(): void
    {
        File::delete(File::glob($this->dossierLogos . '/test-*'));

        parent::tearDown();
    }

    public function test_l_identite_decrit_l_etablissement_et_ses_couleurs(): void
    {
        $reponse = $this->getJson('/api/public/etablissement');

        $reponse->assertOk()
            ->assertJsonPath('nom', 'Ecole Superieure des Travaux Publics')
            ->assertJsonPath('sigle', 'ESBTP')
            ->assertJsonPath('ville', 'Yamoussoukro')
            ->assertJsonPath('identite_visuelle.couleur_principale', '#8b1d3f')
            ->assertJsonPath('identite_visuelle.bandeau_fond', '#8b1d3f')
            // Le texte d'en-tete est normalise : il s'affiche sur une ligne.
            ->assertJsonPath('identite_visuelle.entete', 'Ecole Superieure des Travaux Publics');
    }

    /**
     * Le fond du bandeau est choisi par l'ecole, la couleur du texte pose
     * dessus est calculee par KLASSCI selon le contraste WCAG. Le site vitrine
     * la relit telle quelle : recalculer de son cote laisserait le document
     * imprime et la page web diverger.
     */
    public function test_la_couleur_du_texte_du_bandeau_suit_le_fond_choisi(): void
    {
        $this->reglages(['pdf_header_bg_color' => '#fdf3c4']);

        $this->getJson('/api/public/etablissement')
            ->assertOk()
            ->assertJsonPath('identite_visuelle.bandeau_fond', '#fdf3c4')
            ->assertJsonPath(
                'identite_visuelle.bandeau_texte',
                fn (string $couleur) => $couleur !== '#ffffff',
            );
    }

    /**
     * Ces couleurs atterrissent dans un attribut `style` du site vitrine. Une
     * valeur libre y deviendrait une injection CSS — d'ou l'assainissement.
     */
    public function test_une_couleur_qui_n_en_est_pas_une_retombe_sur_la_teinte_klassci(): void
    {
        $this->reglages(['pdf_primary_color' => 'red; background-image: url(//pirate.example/x.png)']);

        $reponse = $this->getJson('/api/public/etablissement');

        $reponse->assertOk()->assertJsonPath('identite_visuelle.couleur_principale', '#0453cb');
        $this->assertStringNotContainsString('pirate.example', $reponse->getContent());
    }

    public function test_l_identite_ne_laisse_filtrer_ni_contact_ni_effectif_ni_offre(): void
    {
        $this->reglages([
            'school_phone' => '+225 27 30 64 00 00',
            'school_email' => 'direction@esbtp.ci',
            'school_address' => 'Quartier Habitat, Yamoussoukro',
            'director_name' => 'Dr. Soro Kouadio',
        ]);

        $corps = $this->getJson('/api/public/etablissement')->assertOk()->getContent();

        foreach ([
            '27 30 64', 'direction@esbtp.ci', 'Quartier Habitat', 'Soro Kouadio',
            'plan', 'elite', 'quota', 'etudiants', 'inscriptions', 'montant',
        ] as $interdit) {
            $this->assertStringNotContainsStringIgnoringCase(
                $interdit,
                $corps,
                "L'identite publique ne doit servir que ce qu'un bulletin imprime deja en en-tete.",
            );
        }
    }

    public function test_une_ecole_sans_logo_rend_404_et_jamais_la_marque_klassci(): void
    {
        $this->getJson('/api/public/etablissement')
            ->assertOk()
            ->assertJsonPath('logo.present', false)
            ->assertJsonPath('logo.url', null);

        $this->get('/api/public/etablissement/logo')->assertNotFound();
    }

    public function test_le_logo_configure_est_servi_avec_son_type_et_sans_execution(): void
    {
        $this->deposerLogo('test-esbtp.png');

        $this->getJson('/api/public/etablissement')
            ->assertOk()
            ->assertJsonPath('logo.present', true)
            ->assertJsonPath('logo.url', route('api.public.etablissement.logo'));

        $reponse = $this->get('/api/public/etablissement/logo');

        $reponse->assertOk();
        $this->assertSame('image/png', $reponse->headers->get('Content-Type'));
        $this->assertSame('nosniff', $reponse->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString("default-src 'none'", (string) $reponse->headers->get('Content-Security-Policy'));
    }

    /**
     * Le site vitrine relit ces reponses a chaque regeneration de page, et le
     * navigateur du visiteur charge le logo. Sans duree de cache, une ecole
     * repondrait a chaque affichage de la page d'accueil.
     */
    public function test_les_reponses_annoncent_une_duree_de_cache(): void
    {
        $this->deposerLogo('test-cache.png');

        foreach (['/api/public/etablissement', '/api/public/etablissement/logo'] as $chemin) {
            $this->assertStringContainsString(
                'max-age=3600',
                (string) $this->get($chemin)->headers->get('Cache-Control'),
                "{$chemin} doit annoncer sa duree de cache.",
            );
        }
    }

    private function deposerLogo(string $nom): void
    {
        // Un PNG 1x1 reel : `response()->file` refuse un fichier vide, et le
        // type servi se deduit de l'extension.
        File::put(
            $this->dossierLogos . '/' . $nom,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
        );

        $this->reglages(['school_logo' => 'logos/' . $nom]);
    }

    /**
     * @param  array<string, string>  $valeurs
     */
    private function reglages(array $valeurs): void
    {
        foreach ($valeurs as $cle => $valeur) {
            Setting::updateOrCreate(['key' => $cle], [
                'value' => $valeur,
                'type' => 'string',
                'group' => 'establishment',
                // Setting::get filtre sur is_active : sans ce drapeau la ligne
                // existe mais reste invisible, et le defaut s'applique.
                'is_active' => true,
            ]);
        }

        // Setting::get met en cache la valeur lue, y compris l'absence de
        // ligne : purger apres l'ecriture, jamais avant.
        Cache::flush();
    }
}
