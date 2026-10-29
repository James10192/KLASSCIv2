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
        $inchange = $action->executeAuthorized(['reglages' => [['cle' => 'pdf_font_size', 'valeur' => '12']]], $this->admin);
        $this->assertTrue($inchange['sans_objet'] ?? false, json_encode($inchange, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('déjà', $inchange['message']);
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

    private function png(int $l = 40, int $h = 20): string
    {
        $image = imagecreatetruecolor($l, $h);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * La signature du directeur : posée par Nanan, elle apparaît vraiment sur un
     * PDF (lue par <x-pdf-document> sous storage/app/public). Les images que rien
     * ne lit ne sont pas proposées.
     */
    public function test_la_signature_du_directeur_apparait_sur_les_pdf(): void
    {
        $this->reglage('pdf_show_director_signature', '1');
        $png = $this->png(60, 30);
        $piece = app(PiecesJointes::class)->garderImage($this->admin->id, 'signature.png', 'image/png', $png);

        $r = app(PoserImageReglage::class)->executeAuthorized(['cle' => 'pdf_signature_director', 'piece_id' => $piece], $this->admin);
        $this->assertNull(Setting::where('key', 'pdf_signature_director')->value('value'), 'rien avant Valider');
        $this->valider($r);

        $chemin = Setting::where('key', 'pdf_signature_director')->value('value');
        try {
            $this->assertSame($chemin, \App\Helpers\SettingsHelper::getPdfSettings()['signature_director']);
            $html = \Illuminate\Support\Facades\Blade::render('<x-pdf-document title="Essai" signature-block="director">corps</x-pdf-document>');
            $this->assertStringContainsString(base64_encode($png), $html, 'le PDF doit embarquer la signature posée');
        } finally {
            Storage::disk('public')->delete($chemin);
        }

        foreach (['header_logo', 'bulletin_logo', 'watermark_image', 'signature_image', 'school_favicon'] as $nonLue) {
            $this->assertStringContainsString('Nanan', implode(' ', app(PoserImageReglage::class)->executeAuthorized(['cle' => $nonLue, 'piece_id' => $piece], $this->admin)['manques'] ?? []), $nonLue);
        }
        $this->reglage('header_logo', 'logos/x.png', ['type' => 'file']);
        $lu = collect(app(LireReglages::class)->executeAuthorized(['cles' => ['header_logo']], $this->admin)['results'])->sole();
        $this->assertSame('non', $lu['modifiable_par_nanan']);
    }

    public function test_les_bornes_valent_pour_l_ecran_le_cli_et_nanan(): void
    {
        $this->reglage('pdf_margin_top', '20', ['type' => 'integer']);
        $this->reglage('pdf_watermark_opacity', '0.05', ['type' => 'float']);

        $this->assertStringContainsString('entre 0 et 50', implode(' ', app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'pdf_margin_top', 'valeur' => '80']]], $this->admin)['manques'] ?? []));
        $this->assertStringContainsString('entre 0,02 et 0,3', implode(' ', app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'pdf_watermark_opacity', 'valeur' => '0.9']]], $this->admin)['manques'] ?? []));

        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_pdf_margin_top' => '80'])
            ->assertStatus(422)->assertJsonPath('errors.pdf_margin_top', fn ($m) => str_contains($m, 'entre 0 et 50'));
        $this->assertSame('20', Setting::where('key', 'pdf_margin_top')->value('value'));
        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_pdf_margin_top' => '25'])->assertOk();
        $this->assertSame('25', Setting::where('key', 'pdf_margin_top')->value('value'));

        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);
        $this->postJson('/api/cli/settings', ['key' => 'pdf_margin_top', 'value' => '80', 'apply' => true])->assertStatus(422);
        $this->putJson('/api/cli/settings/pdf_margin_top', ['value' => '80'])->assertStatus(422);
        $this->assertSame('25', Setting::where('key', 'pdf_margin_top')->value('value'));
    }

    private function migrerReglagesDecimaux(): void
    {
        (require base_path('database/migrations/2026_10_01_223046_convertir_reglages_decimaux_en_float.php'))->up();
    }

    /**
     * Un coefficient ou un seuil que l'écran propose au pas de 0,1 / 0,5 est lu
     * en décimal : il s'enregistre en décimal, il n'est ni tronqué ni refusé.
     */
    public function test_les_reglages_decimaux_proposes_par_l_ecran_sont_acceptes(): void
    {
        // Ligne ancienne, en `integer` : la migration la passe en float.
        $this->reglage('bulletin_semester1_weight', '1', ['type' => 'integer', 'validation_rules' => ['nullable', 'numeric', 'min:0']]);
        $this->reglage('lmd_validation_threshold', '10', ['type' => 'integer']);
        $this->migrerReglagesDecimaux();

        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_bulletin_semester1_weight' => '1.5'])->assertOk();
        $this->assertSame('1.5', Setting::where('key', 'bulletin_semester1_weight')->value('value'));
        $this->assertSame(1.5, (float) \App\Helpers\SettingsHelper::get('bulletin_semester1_weight'));

        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'lmd_validation_threshold', 'valeur' => '10.5']]], $this->admin);
        $this->valider($r);
        $this->assertSame(10.5, app(\App\Services\LMD\LmdAcademicRuleProfile::class)->validationThreshold());

        // Ligne absente : l'écran la crée directement en float.
        Setting::where('key', 'bulletin_semester2_weight')->delete();
        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_bulletin_semester2_weight' => '2.5'])->assertOk();
        $this->assertSame('2.5', Setting::where('key', 'bulletin_semester2_weight')->value('value'));
    }

    /**
     * Passer en float ne change pas la valeur qu'une école utilisait : « 10.5 »
     * stocké en integer était lu 10, il reste 10 (journalisé). down() ne défait
     * que les lignes converties, jamais une ligne déjà en float.
     */
    public function test_la_migration_garde_la_valeur_lue_et_ne_defait_que_ses_lignes(): void
    {
        $journal = [];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($e) use (&$journal) {
            $journal[] = $e;
        });
        $this->reglage('lmd_validation_threshold', '10.5', ['type' => 'integer']);
        $this->reglage('bulletin_semester1_weight', '1', ['type' => 'integer']);
        $this->reglage('bulletin_bts1_semester1_weight', '1.5', ['type' => 'float']);
        $this->assertSame(10, Setting::get('lmd_validation_threshold'), 'témoin : lu 10 avant');

        $migration = require base_path('database/migrations/2026_10_01_223046_convertir_reglages_decimaux_en_float.php');
        $migration->up();

        $this->assertSame(['10', 'float'], [Setting::where('key', 'lmd_validation_threshold')->value('value'), Setting::where('key', 'lmd_validation_threshold')->value('type')]);
        $this->assertSame(10.0, Setting::get('lmd_validation_threshold'), 'toujours lu 10 après');
        $this->assertNotEmpty(array_filter($journal, fn ($e) => $e->level === 'warning' && ($e->context['key'] ?? null) === 'lmd_validation_threshold'
            && ($e->context['avant'] ?? null) === '10.5' && ($e->context['apres'] ?? null) === '10'), 'chaque réécriture est journalisée');

        $migration->down();
        $this->assertSame(['10.5', 'integer'], [Setting::where('key', 'lmd_validation_threshold')->value('value'), Setting::where('key', 'lmd_validation_threshold')->value('type')]);
        $this->assertSame('integer', Setting::where('key', 'bulletin_semester1_weight')->value('type'));
        $this->assertSame(['1.5', 'float'], [Setting::where('key', 'bulletin_bts1_semester1_weight')->value('value'), Setting::where('key', 'bulletin_bts1_semester1_weight')->value('type')], 'une ligne déjà en float ne redescend jamais');
    }

    /** Un champ non touché ne bloque pas l'enregistrement, même hors bornes en base. */
    public function test_un_champ_inchange_hors_bornes_ne_bloque_pas_la_page(): void
    {
        $this->reglage('pdf_watermark_opacity', '0.50', ['type' => 'float']);
        $this->reglage('pdf_margin_top', '20', ['type' => 'integer']);
        $this->reglage('pdf_font_size', '12', ['type' => 'integer']);

        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), [
            'setting_pdf_watermark_opacity' => '0.5',   // inchangé (même nombre), hors bornes en base
            'setting_pdf_margin_top' => '30',
            'setting_pdf_font_size' => '08',            // zéro de tête : un entier
        ])->assertOk();

        $this->assertSame('0.50', Setting::where('key', 'pdf_watermark_opacity')->value('value'));
        $this->assertSame('30', Setting::where('key', 'pdf_margin_top')->value('value'));
        $this->assertSame('8', Setting::where('key', 'pdf_font_size')->value('value'));
    }

    /** Un réglage d'image ne s'écrit jamais en texte par le CLI : un chemin libre viserait n'importe quel fichier. */
    public function test_le_cli_refuse_un_chemin_d_image_ecrit_en_texte(): void
    {
        $this->reglage('school_logo', 'logos/a.png', ['type' => 'file']);
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->putJson('/api/cli/settings/school_logo', ['value' => '../../.env'])->assertStatus(422);
        $this->putJson('/api/cli/settings/pdf_signature_director', ['value' => '/etc/passwd'])->assertStatus(422);
        $this->postJson('/api/cli/settings', ['key' => 'school_logo', 'value' => '../../.env', 'apply' => true])->assertStatus(422);
        $this->assertSame('logos/a.png', Setting::where('key', 'school_logo')->value('value'));
        $this->assertNull(Setting::where('key', 'pdf_signature_director')->value('value'));
    }

    /** Toutes les cases d'instance se lisent par drapeau() : plus aucune comparaison stricte à '1'. */
    public function test_aucune_case_n_est_comparee_strictement_a_1(): void
    {
        $sortie = shell_exec('grep -rnE "SettingsHelper::get\(\'[a-zA-Z0-9_.]+\', \'[01]\'\) === \'1\'|self::get\(\'[a-zA-Z0-9_.]+\', \'[01]\'\) === \'1\'" '.base_path('app').' '.base_path('resources/views'));
        $this->assertSame('', trim((string) $sortie));

        $this->reglage('receipt_show_logo', '1', ['type' => 'boolean']);
        Cache::flush();
        $this->assertTrue(\App\Helpers\SettingsHelper::drapeau('receipt_show_logo', false));
    }

    /** Un réglage vraiment entier : l'écran n'offre que des entiers, la règle refuse une décimale. */
    public function test_un_reglage_entier_reste_entier_et_l_ecran_ne_propose_pas_de_decimale(): void
    {
        $this->reglage('pdf_font_size', '12', ['type' => 'integer']);
        $this->assertStringContainsString('entier', implode(' ', app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'pdf_font_size', 'valeur' => '12.5']]], $this->admin)['manques'] ?? []));

        $vue = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));
        foreach (['pdf_font_size', 'pdf_margin_top', 'pdf_logo_size', 'pdf_signature_height'] as $entier) {
            $this->assertMatchesRegularExpression('/step="(1|5|10)"[^>]*name="setting_'.$entier.'"/s', $vue, $entier);
        }
    }

    /** Une case PDF enregistrée en booléen, en chaîne ou absente se lit pareil. */
    public function test_les_cases_pdf_se_lisent_quel_que_soit_leur_type(): void
    {
        foreach (['pdf_show_director_signature', 'pdf_show_logo', 'pdf_show_generator_name', 'pdf_show_pagination'] as $cle) {
            $lu = fn () => \App\Helpers\SettingsHelper::getPdfSettings()[str_replace('pdf_', '', $cle)];
            foreach ([['1', 'boolean', true], ['0', 'boolean', false], ['true', 'string', true], ['1', 'string', true], ['0', 'string', false]] as [$valeur, $type, $attendu]) {
                $this->reglage($cle, $valeur, ['type' => $type]);
                Cache::flush();
                $this->assertSame($attendu, $lu(), "{$cle} = {$valeur} ({$type})");
            }
            Setting::where('key', $cle)->delete();
            Cache::flush();
            $this->assertTrue($lu(), "{$cle} absent : défaut");
        }
    }

    public function test_le_put_du_cli_garde_les_refus_et_cree_encore_une_cle_absente(): void
    {
        $this->reglage('pdf_header_bg_color', '#0453cb');
        $this->reglage('pdf_header_text_color', '#ffffff');
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->putJson('/api/cli/settings/portail_api_key', ['value' => 'x'])->assertStatus(422);
        $this->putJson('/api/cli/settings/pdf_header_text_color', ['value' => '#0453CB'])->assertStatus(422);
        $this->assertSame('#ffffff', Setting::where('key', 'pdf_header_text_color')->value('value'));

        Setting::where('key', 'reglage_provisionne_lot_d')->delete();
        $this->putJson('/api/cli/settings/reglage_provisionne_lot_d', ['value' => 'oui'])->assertOk()->assertJsonPath('data.created', true);
    }

    /** Un PUT qui renvoie la valeur déjà en base répond 200 sans rien écrire (il créait un doublon : 500). */
    public function test_un_put_inchange_repond_sans_rien_ecrire(): void
    {
        $this->reglage('school_postal_code', '01 BP 123');
        $this->reglage('pdf_font_size', '12', ['type' => 'integer']);
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->putJson('/api/cli/settings/school_postal_code', ['value' => '01 BP 123'])
            ->assertOk()->assertJsonPath('data.changed', false)->assertJsonPath('data.created', false);
        $this->putJson('/api/cli/settings/pdf_font_size', ['value' => '12.0'])->assertOk()->assertJsonPath('data.changed', false);
        $this->assertSame(1, Setting::where('key', 'school_postal_code')->count());
        $this->assertSame('12', Setting::where('key', 'pdf_font_size')->value('value'));
    }

    /** L'égalité numérique ne vaut que pour un réglage numérique : « 0123 » n'est pas « 123 » pour un texte. */
    public function test_une_virgule_en_base_ne_masque_pas_une_correction(): void
    {
        // « 1,5 » en base est lu 1 par castValue : « 1.5 » est donc une vraie correction.
        $this->assertFalse(\App\Domain\Reglages\ModificationDeReglages::memeNombre('1,5', '1.5'));
        // Une virgule tapée par l'école vaut un point.
        $this->assertTrue(\App\Domain\Reglages\ModificationDeReglages::memeNombre('1.5', '1,5'));
        $this->assertTrue(\App\Domain\Reglages\ModificationDeReglages::memeNombre('0.50', '0.5'));
    }

    public function test_un_texte_garde_ses_zeros_de_tete(): void
    {
        $this->reglage('school_postal_code', '123');
        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_school_postal_code' => '0123'])->assertOk();
        $this->assertSame('0123', Setting::where('key', 'school_postal_code')->value('value'), 'écran');

        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);
        $this->putJson('/api/cli/settings/school_postal_code', ['value' => '00123'])->assertOk()->assertJsonPath('data.changed', true);
        $this->assertSame('00123', Setting::where('key', 'school_postal_code')->value('value'), 'PUT du CLI');
        $this->postJson('/api/cli/settings', ['key' => 'school_postal_code', 'value' => '123', 'apply' => true])->assertOk();
        $this->assertSame('123', Setting::where('key', 'school_postal_code')->value('value'), 'POST du CLI');

        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'school_postal_code', 'valeur' => '000123']]], $this->admin);
        $this->valider($r);
        $this->assertSame('000123', Setting::where('key', 'school_postal_code')->value('value'), 'Nanan');

        // Un entier, lui, reste comparé en nombre : « 08 » est « 8 », rien n'est réécrit.
        $this->reglage('pdf_font_size', '8', ['type' => 'integer']);
        $this->actingAs($this->admin)->putJson(route('esbtp.settings.update'), ['setting_pdf_font_size' => '08'])->assertOk();
        $this->assertSame('8', Setting::where('key', 'pdf_font_size')->value('value'));
    }

    /** La base ignore la casse des clés : les refus aussi. */
    public function test_la_casse_de_la_cle_ne_contourne_pas_les_refus(): void
    {
        $this->reglage('pdf_signature_director', 'signatures/d.png');
        $this->reglage('pdf_signature_secretary', 'signatures/s.png');
        $this->reglage('school_logo', 'logos/a.png');
        $this->reglage('pdf_cachet_ecole', 'cachets/c.png');
        $this->reglage('mailpulse_base_url', 'https://mailpulse.example');
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->putJson('/api/cli/settings/PDF_SIGNATURE_DIRECTOR', ['value' => '../../.env'])->assertStatus(422);
        $this->putJson('/api/cli/settings/Pdf_Signature_Secretary', ['value' => '../../.env'])->assertStatus(422);
        $this->postJson('/api/cli/settings', ['key' => 'SCHOOL_LOGO', 'value' => '../../.env', 'apply' => true])->assertStatus(422);
        $this->putJson('/api/cli/settings/MAILPULSE_BASE_URL', ['value' => 'https://ailleurs.example'])->assertStatus(422);
        // Une clé qui évoque une image sans être dans la liste : pas de remontée de dossier.
        $this->putJson('/api/cli/settings/pdf_cachet_ecole', ['value' => '../../.env'])->assertStatus(422);

        $this->assertSame('signatures/d.png', Setting::where('key', 'pdf_signature_director')->value('value'));
        $this->assertSame('signatures/s.png', Setting::where('key', 'pdf_signature_secretary')->value('value'));
        $this->assertSame('logos/a.png', Setting::where('key', 'school_logo')->value('value'));
        $this->assertSame('cachets/c.png', Setting::where('key', 'pdf_cachet_ecole')->value('value'));
        $this->assertSame('https://mailpulse.example', Setting::where('key', 'mailpulse_base_url')->value('value'));

        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'PDF_SIGNATURE_DIRECTOR', 'valeur' => '../../.env']]], $this->admin);
        $this->assertNotSame('approbation', $r['widget']['kind'] ?? null, json_encode($r, JSON_UNESCAPED_UNICODE));
        $this->assertSame('signatures/d.png', Setting::where('key', 'pdf_signature_director')->value('value'));
    }

    /** Une case vide est décochée ; seule une case absente prend son défaut. */
    public function test_une_case_vide_vaut_non_une_case_absente_son_defaut(): void
    {
        $this->reglage('case_vide_lot_d', '');
        Cache::flush();
        $this->assertFalse(\App\Helpers\SettingsHelper::drapeau('case_vide_lot_d', true));

        Setting::where('key', 'case_vide_lot_d')->delete();
        Cache::flush();
        $this->assertTrue(\App\Helpers\SettingsHelper::drapeau('case_vide_lot_d', true));
    }

    /** La trace de migration ne se restaure pas comme une sauvegarde d'école (elle viderait la table). */
    public function test_la_trace_de_migration_ne_se_restaure_pas(): void
    {
        $journal = [];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($e) use (&$journal) {
            $journal[] = $e;
        });
        $this->reglage('lmd_validation_threshold', '10', ['type' => 'integer']);
        $this->reglage('school_name', 'Mon école');
        $migration = require base_path('database/migrations/2026_10_01_223046_convertir_reglages_decimaux_en_float.php');
        $migration->up();

        $trace = \App\Models\SettingsBackup::where('backup_type', 'migration')->latest('id')->first();
        $this->assertNotNull($trace);
        $this->assertSame('archived', $trace->status, 'absente de la liste des sauvegardes actives');
        try {
            $trace->restore($this->admin->id);
            $this->fail('une trace de migration ne doit pas se restaurer');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("ne se restaure pas", $e->getMessage());
        }
        $this->assertSame('Mon école', Setting::where('key', 'school_name')->value('value'), 'la table est intacte');

        \App\Models\SettingsBackup::where('backup_type', 'migration')->delete();
        $migration->down();
        $this->assertNotEmpty(array_filter($journal, fn ($e) => $e->level === 'warning' && str_contains($e->message, 'sans trace')), 'down() sans trace le dit');
    }

    public function test_le_cli_dit_de_poser_les_prefixes_avant_l_indicatif(): void
    {
        $this->reglage(PhoneNormalizer::CLE_INDICATIF, '225');
        $this->reglage(PhoneNormalizer::CLE_PREFIXES, PhoneNormalizer::PREFIXES_PAR_DEFAUT);
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->postJson('/api/cli/settings', ['key' => PhoneNormalizer::CLE_INDICATIF, 'value' => '229', 'apply' => true])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, "posez d'abord les préfixes"));
    }

    public function test_les_deux_cases_de_signature_du_bulletin_vont_ensemble(): void
    {
        $this->reglage('bulletin_show_signature', '1', ['type' => 'boolean']);
        $this->reglage('bulletin_show_signatures', '1', ['type' => 'boolean']);

        $this->valider(app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => 'bulletin_show_signature', 'valeur' => '0']]], $this->admin));

        $this->assertSame('0', Setting::where('key', 'bulletin_show_signatures')->value('value'), 'le bulletin lit le pluriel');
        $this->assertSame('0', Setting::where('key', 'bulletin_show_signature')->value('value'));
    }

    /** Préparer n'écrit rien, même les lignes par défaut du parcours d'inscription. */
    public function test_proposer_un_parcours_d_inscription_ne_cree_aucune_ligne(): void
    {
        $w = \App\Services\Admissions\InscriptionWorkflowSettings::class;
        Setting::whereIn('key', $w::cles())->delete();
        $this->reglage($w::ENABLED, '0', ['type' => 'boolean']);

        $r = app(ModifierReglages::class)->executeAuthorized(['reglages' => [['cle' => $w::ENABLED, 'valeur' => '1']]], $this->admin);

        $this->assertSame(1, Setting::whereIn('key', $w::cles())->count(), 'aucune ligne créée par la préparation');
        $this->assertStringContainsString('entre deux campagnes', implode(' ', $r['widget']['avertissements'] ?? []), json_encode($r, JSON_UNESCAPED_UNICODE));
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

    /** L'aperçu et le placement lisent les dossiers par le même chemin : mêmes comptes. */
    public function test_l_apercu_annonce_ce_que_le_placement_fait(): void
    {
        $this->reglagesRdv();
        ESBTPRdvCreneau::create(['annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays(2)->toDateString(), 'heure_debut' => '09:00:00', 'heure_fin' => '09:30:00', 'capacite' => 2, 'ouvert' => true]);
        $ancienne = $this->reservation('ancien@gmail.com', null);      // d'avant le suivi : reconvoquée sans place
        $this->reservation('deja@gmail.com', StatutConvocationRdv::Envoyee); // déjà traitée
        $this->candidature('kone8@gmail.com');
        $this->candidature('');                                          // sans e-mail : à prévenir
        $this->candidature('kone9@gmail.com');                           // plus de dossiers que de places : sans créneau

        $apercu = app(\App\Services\RendezVous\AffecteurDossiersRdv::class)->apercu();
        $place = app(\App\Services\RendezVous\AffecteurDossiersRdv::class)->placer();

        $garder = fn (array $r) => array_intersect_key($r, array_flip(['places', 'a_prevenir', 'sans_creneau', 'deja', 'refus']));
        $this->assertSame($garder($place), $garder($apercu));
        $this->assertSame(['places' => 3, 'a_prevenir' => 1, 'sans_creneau' => 1, 'deja' => 1, 'refus' => null], $garder($place));
        $this->assertSame(StatutConvocationRdv::EnAttente, $ancienne->fresh()->convocation_statut);
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
        // Le nombre d'abord, la confirmation ensuite.
        $this->assertStringContainsString('1 famille(s) seraient reconvoquée(s)', implode(' ', $action->executeAuthorized(['mode' => 'remettre', 'quoi' => 'inconnues'], $this->admin)['manques']));

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
        $this->assertStringContainsString('1 famille(s) seraient reconvoquée(s) (motif : adresse_corrigee)', implode(' ', $action->executeAuthorized(['mode' => 'renvoyer', 'reservations' => [$cible->id, 999999], 'motif' => 'adresse_corrigee'], $this->admin)['manques']));

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
