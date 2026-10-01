<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Reglages\ModifierReglages;
use App\Domain\Assistant\Actions\Reglages\PoserImageReglage;
use App\Domain\Assistant\Actions\RendezVous\ConvocationsRdv;
use App\Domain\Assistant\Actions\RendezVous\GenererCreneaux;
use App\Domain\Assistant\Actions\RendezVous\PlacerDossiers;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Outils\LireReglages;
use App\Domain\Assistant\Outils\LireRendezVous;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Domain\Notifications\PhoneNormalizer;
use App\Enums\StatutConvocationRdv;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\MessagerieRdv;
use App\Services\RendezVous\RendezVousReglages as R;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Lot D appris à Nanan : réglages de l'établissement (valeurs et images) et
 * rendez-vous d'inscription (générer les créneaux, placer les dossiers,
 * envoyer / remettre / renvoyer les convocations). Rien n'est écrit ni envoyé
 * avant « Valider » ; tout l'est après.
 */
class NananLotDTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private ESBTPAnneeUniversitaire $annee;

    private int $numero = 0;

    /** Appels réels à la messagerie : un envoi avant « Valider » serait un défaut. */
    private int $envois = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Http::fake();
        Cache::flush();
        Role::findOrCreate('superAdmin', 'web');
        foreach (['system.manage', 'inscriptions.rdv.manage', 'inscriptions.rdv.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);

        // La messagerie ne part jamais vraiment : on compte les envois.
        $this->mock(MessagerieRdv::class, function ($m) {
            $m->shouldReceive('envoyer')->andReturnUsing(function (ESBTPRdvReservation $r) {
                $this->envois++;
                $r->forceFill(['convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_envoyee_at' => now()])->save();

                return null;
            });
            $m->shouldReceive('planifier')->andReturnUsing(function (ESBTPRdvReservation $r, string $action = 'confirme') {
                $r->forceFill(['convocation_statut' => StatutConvocationRdv::EnAttente, 'convocation_action' => $action, 'convocation_tentatives' => 0])->save();
            });
        });
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
    }

    private function valider(array $resultat, string $statut = 'executee'): void
    {
        $this->assertSame('approbation', $resultat['widget']['kind'] ?? null, json_encode($resultat, JSON_UNESCAPED_UNICODE));
        $this->actingAs($this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->assertStatus($statut === 'executee' ? 200 : 409)->assertJson(['statut' => $statut]);
    }

    private function reglage(string $cle, ?string $valeur, array $autres = []): Setting
    {
        Setting::where('key', $cle)->delete();

        return Setting::create($autres + ['key' => $cle, 'value' => $valeur, 'type' => 'string', 'group' => 'general', 'description' => $cle, 'is_required' => false, 'is_active' => true]);
    }

    // --- Réglages ----------------------------------------------------------------

    public function test_modifier_un_reglage_n_ecrit_qu_apres_valider(): void
    {
        $this->reglage('director_name', 'M. KOUAME');
        $this->reglage('director_title', 'Directeur');

        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [
            ['cle' => 'director_name', 'valeur' => 'Mme DIALLO'], ['cle' => 'director_title', 'valeur' => 'Directrice des études'],
        ]], $this->admin);

        $this->assertSame('M. KOUAME', Setting::where('key', 'director_name')->value('value'), 'rien avant Valider');
        $this->assertSame(['Directeur', 'Directrice des études'], [$r['widget']['lignes'][1][2], $r['widget']['lignes'][1][3]]);
        $sauvegardes = \App\Models\SettingsBackup::count();
        $this->valider($r);

        $this->assertSame('Mme DIALLO', Setting::where('key', 'director_name')->value('value'));
        $this->assertSame('Directrice des études', Setting::where('key', 'director_title')->value('value'));
        $this->assertSame($sauvegardes + 1, \App\Models\SettingsBackup::count(), 'une sauvegarde avant d\'écrire, comme l\'écran');
    }

    public function test_les_reglages_hors_de_portee_et_les_valeurs_invalides_sont_refuses(): void
    {
        $this->reglage('mailpulse_api_key', 'mp_live_secret');
        $this->reglage('scolarite.split_roles', '0', ['type' => 'boolean']);
        $this->reglage('pdf_font_size', '12', ['type' => 'integer']);
        $this->reglage('pdf_header_bg_color', '#0453cb');
        $this->reglage('pdf_header_text_color', '#ffffff');
        $this->reglage(PhoneNormalizer::CLE_INDICATIF, '225');
        $this->reglage(PhoneNormalizer::CLE_PREFIXES, PhoneNormalizer::PREFIXES_PAR_DEFAUT);
        $action = app(ModifierReglages::class);
        $manques = fn (array $reglages) => implode(' ', $action->executeAuthorized(['reglages' => $reglages], $this->admin)['manques'] ?? ['(proposée)']);

        $this->assertStringContainsString('secret', $manques([['cle' => 'mailpulse_api_key', 'valeur' => 'mp_live_autre']]));
        $this->assertStringContainsString('écran', $manques([['cle' => 'scolarite.split_roles', 'valeur' => '1']]));
        $this->assertStringContainsString('introuvable', $manques([['cle' => 'pdf_inexistant', 'valeur' => '1']]));
        $this->assertStringContainsString('entier', $manques([['cle' => 'pdf_font_size', 'valeur' => 'grand']]));
        $this->assertStringContainsString('invisible', $manques([['cle' => 'pdf_header_text_color', 'valeur' => '#0453CB']]));
        $this->assertStringContainsString('préfixes', $manques([['cle' => PhoneNormalizer::CLE_INDICATIF, 'valeur' => '229']]));
        $this->assertStringContainsString('déjà', $manques([['cle' => 'pdf_font_size', 'valeur' => '12']]));
        $this->assertStringContainsString('valeur', $manques([]));

        // Indicatif et préfixes ensemble : accepté.
        $ok = $action->executeAuthorized(['reglages' => [
            ['cle' => PhoneNormalizer::CLE_INDICATIF, 'valeur' => '229'], ['cle' => PhoneNormalizer::CLE_PREFIXES, 'valeur' => '01'],
        ]], $this->admin);
        $this->assertSame('approbation', $ok['widget']['kind'] ?? null, json_encode($ok, JSON_UNESCAPED_UNICODE));
        $this->assertSame('mp_live_secret', Setting::where('key', 'mailpulse_api_key')->value('value'));
    }

    public function test_un_reglage_change_entre_temps_rend_la_proposition_perimee(): void
    {
        $this->reglage('school_name', 'ISLG');
        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'school_name', 'valeur' => 'ISLG Rostan']]], $this->admin);
        Setting::where('key', 'school_name')->update(['value' => 'Autre']);

        $this->valider($r, 'perimee');
        $this->assertSame('Autre', Setting::where('key', 'school_name')->value('value'));
    }

    public function test_le_cli_garde_les_memes_refus(): void
    {
        $this->reglage('pdf_header_bg_color', '#0453cb');
        $this->reglage('pdf_header_text_color', '#ffffff');
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->postJson('/api/cli/settings', ['key' => 'pdf_header_text_color', 'value' => '#0453cb', 'apply' => true])->assertStatus(422);
        $this->postJson('/api/cli/settings', ['key' => 'pdf_header_text_color', 'value' => '#111111', 'apply' => true])->assertOk();
        $this->assertSame('#111111', Setting::where('key', 'pdf_header_text_color')->value('value'));
    }

    public function test_remplacer_le_logo_depuis_une_image_jointe(): void
    {
        Storage::fake('public');
        $this->reglage('school_logo', 'logos/ancien.png', ['type' => 'file']);
        Storage::disk('public')->put('logos/ancien.png', 'x');
        $image = imagecreatetruecolor(40, 20);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        $piece = app(PiecesJointes::class)->garderImage($this->admin->id, 'logo-islg.png', 'image/png', $png);

        $r = app(PoserImageReglage::class)->executeAuthorized(['cle' => 'school_logo', 'piece_id' => $piece], $this->admin);
        $this->assertSame('logos/ancien.png', Setting::where('key', 'school_logo')->value('value'), 'rien avant Valider');
        $this->assertCount(1, Storage::disk('public')->allFiles('logos'));
        $this->valider($r);

        $chemin = Setting::where('key', 'school_logo')->value('value');
        $this->assertStringStartsWith('logos/', $chemin);
        $this->assertStringEndsWith('.png', $chemin);
        Storage::disk('public')->assertExists($chemin);
        Storage::disk('public')->assertMissing('logos/ancien.png');

        // Une image d'un autre, une clé hors liste : demandées, jamais devinées.
        $autre = $this->utilisateur();
        $autre->assignRole('superAdmin');
        $this->assertNotEmpty(app(PoserImageReglage::class)->executeAuthorized(['cle' => 'school_logo', 'piece_id' => $piece], $autre)['manques'] ?? []);
        $this->assertNotEmpty(app(PoserImageReglage::class)->executeAuthorized(['cle' => 'pdf_primary_color', 'piece_id' => $piece], $this->admin)['manques'] ?? []);
    }

    public function test_lire_reglages_masque_les_secrets_et_dit_ce_qui_est_modifiable(): void
    {
        $this->reglage('mailpulse_api_key', 'mp_live_secret');
        $this->reglage('director_name', 'M. KOUAME');

        $r = app(LireReglages::class)->executeAuthorized(['cles' => ['mailpulse_api_key', 'director_name', 'absente']], $this->admin);
        $lignes = collect($r['results'])->keyBy('cle');
        $this->assertSame('(masquée)', $lignes['mailpulse_api_key']['valeur']);
        $this->assertSame('non', $lignes['mailpulse_api_key']['modifiable_par_nanan']);
        $this->assertSame('oui', $lignes['director_name']['modifiable_par_nanan']);
        $this->assertSame(['absente'], $r['diagnostic']['introuvables']);
    }

    // --- Rendez-vous -------------------------------------------------------------

    private function reglagesRdv(): void
    {
        foreach ([
            R::ENABLED => '1', R::OUVERTURE => now()->addDay()->toDateString(), R::FERMETURE => now()->addDays(2)->toDateString(),
            R::HEURE_DEBUT => '08:00', R::HEURE_FIN => '10:00', R::DUREE => '30', R::CAPACITE => '2', R::JOURS => '1,2,3,4,5,6,7',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => now()->toDateString(),
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => (string) $this->annee->id,
        ] as $cle => $valeur) {
            Setting::setOrCreate($cle, $valeur);
        }
        Cache::flush();
    }

    private function candidature(string $email): ESBTPCandidature
    {
        $n = ++$this->numero;

        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa'.$n, 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', $n),
            'email' => $email, 'annee_universitaire_id' => $this->annee->id, 'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function reservation(string $email, ?StatutConvocationRdv $convocation): ESBTPRdvReservation
    {
        $candidature = $this->candidature($email);
        $n = $this->numero;
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => sprintf('%02d:%02d:00', 8 + intdiv($n, 2), ($n % 2) * 30), 'heure_fin' => sprintf('%02d:%02d:00', 8 + intdiv($n, 2), ($n % 2) * 30 + 20),
            'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => $candidature->prenoms, 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $convocation, 'convocation_action' => $convocation ? 'confirme' : null,
            'convocation_envoyee_at' => $convocation === StatutConvocationRdv::Envoyee ? now()->subDay() : null,
        ]);
    }

    public function test_generer_les_creneaux_selon_les_reglages(): void
    {
        $sans = app(GenererCreneaux::class)->executeAuthorized([], $this->admin);
        $this->assertStringContainsString('ne les devine pas', implode(' ', $sans['manques'] ?? []));

        $this->reglagesRdv();
        $r = app(GenererCreneaux::class)->executeAuthorized([], $this->admin);
        $this->assertSame(0, ESBTPRdvCreneau::where('annee_universitaire_id', $this->annee->id)->count(), 'rien avant Valider');
        $this->assertSame('8', $r['widget']['lignes'][0][5], json_encode($r['widget'], JSON_UNESCAPED_UNICODE));
        $this->valider($r);

        $this->assertSame(8, ESBTPRdvCreneau::where('annee_universitaire_id', $this->annee->id)->count());
        $this->assertStringContainsString('déjà à jour', implode(' ', app(GenererCreneaux::class)->executeAuthorized([], $this->admin)['manques'] ?? []));
    }

    public function test_placer_les_dossiers_annonce_les_familles_convoquees(): void
    {
        $this->reglagesRdv();
        ESBTPRdvCreneau::create(['annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays(2)->toDateString(), 'heure_debut' => '09:00:00', 'heure_fin' => '09:30:00', 'capacite' => 5, 'ouvert' => true]);
        $avec = $this->candidature('kone1@gmail.com');
        $this->candidature('');

        $r = app(PlacerDossiers::class)->executeAuthorized([], $this->admin);
        $this->assertSame(0, ESBTPRdvReservation::where('candidature_id', $avec->id)->count(), 'rien avant Valider');
        $this->assertSame(['2', '1', '1'], array_slice($r['widget']['lignes'][0], 0, 3), json_encode($r['widget'], JSON_UNESCAPED_UNICODE));
        $this->assertSame('eleve', $r['widget']['risque']);
        $this->valider($r);

        $this->assertSame(StatutConvocationRdv::EnAttente, ESBTPRdvReservation::where('candidature_id', $avec->id)->sole()->convocation_statut);
        $this->assertSame(0, $this->envois, 'placer pose la convocation, la tâche planifiée l\'envoie');
    }

    public function test_envoyer_les_convocations_en_attente_seulement_apres_valider(): void
    {
        $a = $this->reservation('kone1@gmail.com', StatutConvocationRdv::EnAttente);
        $b = $this->reservation('kone2@gmail.com', StatutConvocationRdv::EnAttente);
        $deja = $this->reservation('kone3@gmail.com', StatutConvocationRdv::Envoyee);

        $r = app(ConvocationsRdv::class)->executeAuthorized(['mode' => 'envoyer'], $this->admin);
        $this->assertSame(0, $this->envois, 'rien n\'est envoyé avant Valider');
        $this->assertCount(2, $r['widget']['lignes']);
        $this->valider($r);

        $this->assertSame(2, $this->envois);
        $this->assertSame(StatutConvocationRdv::Envoyee, $a->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $b->fresh()->convocation_statut);
        $this->assertNotNull($deja->fresh()->convocation_envoyee_at);
    }

    public function test_un_paquet_qui_a_change_n_envoie_rien(): void
    {
        $a = $this->reservation('kone1@gmail.com', StatutConvocationRdv::EnAttente);
        $this->reservation('kone2@gmail.com', StatutConvocationRdv::EnAttente);
        $r = app(ConvocationsRdv::class)->executeAuthorized(['mode' => 'envoyer'], $this->admin);
        // La tâche planifiée est passée entre-temps.
        $a->forceFill(['convocation_statut' => StatutConvocationRdv::Envoyee])->save();

        $this->valider($r, 'perimee');
        $this->assertSame(0, $this->envois);
    }

    /** La validation fixe la liste : un envoi ne prend jamais une famille que la proposition ne montrait pas. */
    public function test_un_paquet_valide_n_envoie_que_les_reservations_montrees(): void
    {
        $a = $this->reservation('kone1@gmail.com', StatutConvocationRdv::EnAttente);
        $arrivee = $this->reservation('kone2@gmail.com', StatutConvocationRdv::EnAttente);

        $r = app(\App\Services\RendezVous\FileConvocationsRdv::class)->envoyerUnPaquet(50, 5.0, [$a->id]);

        $this->assertSame(1, $r['envoyees']);
        $this->assertSame(StatutConvocationRdv::EnAttente, $arrivee->fresh()->convocation_statut);
    }

    public function test_reconvoquer_exige_une_confirmation_explicite(): void
    {
        $inconnue = $this->reservation('kone1@gmail.com', null);
        $action = app(ConvocationsRdv::class);

        $this->assertStringContainsString('Lesquelles', implode(' ', $action->executeAuthorized(['mode' => 'remettre', 'confirmation_renvoi' => true], $this->admin)['manques']));
        $this->assertStringContainsString('confirme', implode(' ', $action->executeAuthorized(['mode' => 'remettre', 'quoi' => 'inconnues'], $this->admin)['manques']));

        $r = $action->executeAuthorized(['mode' => 'remettre', 'quoi' => 'inconnues', 'confirmation_renvoi' => true], $this->admin);
        $this->assertNull($inconnue->fresh()->convocation_statut, 'rien avant Valider');
        $this->valider($r);
        $this->assertSame(StatutConvocationRdv::EnAttente, $inconnue->fresh()->convocation_statut);
        $this->assertSame(0, $this->envois, 'remettre ne fait que mettre en file');
    }

    public function test_renvoyer_a_des_reservations_precises_avec_motif(): void
    {
        $cible = $this->reservation('kone1@gmail.com', StatutConvocationRdv::Envoyee);
        $voisine = $this->reservation('kone2@gmail.com', StatutConvocationRdv::Envoyee);
        $action = app(ConvocationsRdv::class);

        $this->assertStringContainsString('Pourquoi', implode(' ', $action->executeAuthorized(['mode' => 'renvoyer', 'reservations' => [$cible->id], 'confirmation_renvoi' => true], $this->admin)['manques']));

        $r = $action->executeAuthorized(['mode' => 'renvoyer', 'reservations' => [$cible->id, 999999], 'motif' => 'adresse_corrigee', 'confirmation_renvoi' => true], $this->admin);
        $this->assertSame(StatutConvocationRdv::Envoyee, $cible->fresh()->convocation_statut, 'rien avant Valider');
        $this->assertStringContainsString('1 famille', $r['widget']['resume']);
        $this->valider($r);

        $this->assertSame(StatutConvocationRdv::EnAttente, $cible->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Envoyee, $voisine->fresh()->convocation_statut);
        $this->assertSame(1, Audit::query()->where('event', 'renvoi_convocation')->where('auditable_id', $cible->id)->count());
    }

    public function test_sans_les_droits_de_l_ecran_rien_n_est_propose(): void
    {
        $secretaire = $this->utilisateur();
        $outils = [ModifierReglages::class, PoserImageReglage::class, GenererCreneaux::class, PlacerDossiers::class, ConvocationsRdv::class, LireReglages::class, LireRendezVous::class];
        foreach ($outils as $classe) {
            $this->assertFalse(app($classe)->isAvailableFor($secretaire), $classe);
        }
        $noms = array_column(app(CatalogueOutils::class)->schemas($secretaire), 'nom');
        $this->assertEmpty(array_intersect($noms, ['proposer_modification_reglages', 'proposer_convocations_rdv', 'lire_reglages']));

        // Lecture seule des rendez-vous : lire oui, agir non.
        $secretaire->givePermissionTo('inscriptions.rdv.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue(app(LireRendezVous::class)->isAvailableFor($secretaire->fresh()));
        $this->assertFalse(app(ConvocationsRdv::class)->isAvailableFor($secretaire->fresh()));
    }

    /**
     * Séance d'entraînement : la vraie boucle, le vrai catalogue, le vrai prompt ;
     * le modèle scripté lit le réglage puis propose. Rien n'est écrit avant « Valider ».
     */
    public function test_seance_d_entrainement_reglages_et_rendez_vous(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 4, 'assistant.limites.budget_tokens' => 0]);
        $this->reglage('director_name', 'M. KOUAME');
        $this->reservation('kone1@gmail.com', StatutConvocationRdv::EnAttente);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'lire_reglages', ['cles' => ['director_name']]),
            FauxFournisseur::outil('t2', 'proposer_modification_reglages', ['reglages' => [['cle' => 'director_name', 'valeur' => 'Mme DIALLO']]]),
            FauxFournisseur::outil('t3', 'proposer_convocations_rdv', ['mode' => 'envoyer']),
            FauxFournisseur::texte('Je propose ces deux changements : relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        foreach (['lire_reglages', 'proposer_modification_reglages', 'proposer_image_reglage', 'lire_rendez_vous', 'proposer_generation_creneaux_rdv', 'proposer_placement_dossiers_rdv', 'confirmation_renvoi'] as $mot) {
            $this->assertStringContainsString($mot, $systeme);
        }

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Mets Mme DIALLO comme directrice et envoie les convocations en attente']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['lire_reglages', 'proposer_modification_reglages', 'proposer_convocations_rdv'], array_column($r->appels, 'tool'));
        $this->assertSame('M. KOUAME', Setting::where('key', 'director_name')->value('value'));
        $this->assertSame(0, $this->envois);
        $this->assertSame(2, \App\Models\ChatbotActionLog::where('status', 'proposed')->where('user_id', $this->admin->id)->count());
    }
}
