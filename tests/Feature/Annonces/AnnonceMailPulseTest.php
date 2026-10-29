<?php

namespace Tests\Feature\Annonces;

use App\Jobs\Annonces\DiffuserAnnonceParCourriel;
use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class AnnonceMailPulseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function annonce(string $type = 'general', bool $courriel = true): ESBTPAnnonce
    {
        return ESBTPAnnonce::create([
            'titre' => 'Annonce test',
            'contenu' => 'Informations importantes pour les étudiants.',
            'type' => $type,
            'priorite' => 0,
            'date_publication' => now(),
            'date_expiration' => now()->addDay(),
            'is_published' => true,
            'email_notification_enabled' => $courriel,
        ]);
    }

    public function test_brouillon_et_case_decochee_n_envoient_rien(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        ESBTPEtudiant::factory()->create(['email' => 'exemple@etudiants.test']);

        $annonce = $this->annonce('general', false);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();
        $this->assertSame(0, DB::table('esbtp_annonce_email_deliveries')->count());

        $annonce->update(['email_notification_enabled' => true, 'is_published' => false]);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();
        $this->assertSame(0, DB::table('esbtp_annonce_email_deliveries')->count());
        Bus::assertNotDispatched(EnvoyerAnnonceCourriel::class);
    }

    public function test_cible_nominative_une_adresse_unique_meme_si_deux_etudiants_la_partagent(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        // Le champ email est unique en base, mais email_personnel peut être partagé.
        $a = ESBTPEtudiant::factory()->create(['email_personnel' => 'partage@etudiants.test']);
        $b = ESBTPEtudiant::factory()->create(['email_personnel' => 'partage@etudiants.test']);
        ESBTPEtudiant::factory()->create(['email' => 'hors-cible@etudiants.test']);

        $annonce = $this->annonce('etudiant');
        $annonce->etudiants()->attach([$a->id, $b->id]);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();

        $this->assertSame(['partage@etudiants.test'],
            DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->pluck('email')->all());
        Bus::assertDispatchedTimes(EnvoyerAnnonceCourriel::class, 1);
    }

    public function test_cible_classe_n_inclut_pas_les_autres_classes_ni_les_anciennes_inscriptions(): void
    {
        Bus::fake([EnvoyerAnnonceCourriel::class]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $ancienne = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        ESBTPAnneeUniversitaire::flushCurrentCache();

        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $autre = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $a = ESBTPEtudiant::factory()->create(['email' => 'bonne@etudiants.test']);
        $b = ESBTPEtudiant::factory()->create(['email' => 'autre@etudiants.test']);
        $c = ESBTPEtudiant::factory()->create(['email' => 'ancienne@etudiants.test']);
        ESBTPInscription::factory()->create(['etudiant_id' => $a->id, 'annee_universitaire_id' => $annee->id, 'classe_id' => $classe->id]);
        ESBTPInscription::factory()->create(['etudiant_id' => $b->id, 'annee_universitaire_id' => $annee->id, 'classe_id' => $autre->id]);
        ESBTPInscription::factory()->create(['etudiant_id' => $c->id, 'annee_universitaire_id' => $ancienne->id, 'classe_id' => $classe->id]);

        $annonce = $this->annonce('classe');
        $annonce->classes()->attach($classe->id);
        (new DiffuserAnnonceParCourriel($annonce->id))->handle();

        $this->assertSame(['bonne@etudiants.test'],
            DB::table('esbtp_annonce_email_deliveries')->where('annonce_id', $annonce->id)->pluck('email')->all());
    }

    public function test_une_livraison_soumise_n_est_jamais_envoyee_deux_fois(): void
    {
        $annonce = $this->annonce();
        $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
            'annonce_id' => $annonce->id, 'email' => 'etudiant@exemple.ci',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $mailer = Mockery::mock();
        $mailer->shouldReceive('raw')->once()->withArgs(function ($texte, $configurer) {
            $this->assertStringContainsString('Annonce test', $texte);
            $message = Mockery::mock();
            $message->shouldReceive('to')->once()->with('etudiant@exemple.ci')->andReturnSelf();
            $message->shouldReceive('subject')->once()->andReturnSelf();
            $configurer($message);
            return true;
        });
        Mail::shouldReceive('mailer')->once()->with('mailpulse')->andReturn($mailer);

        (new EnvoyerAnnonceCourriel($id))->handle();
        (new EnvoyerAnnonceCourriel($id))->handle();

        $this->assertSame('submitted', DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('status'));
        $this->assertNotNull(DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->value('submitted_at'));
    }
}
