<?php

namespace Tests\Feature\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Retrouver un rendez-vous » : une famille appelle sans connaitre le jour,
 * elle donne son nom, son telephone ou sa reference.
 */
class RechercheRdvTest extends TestCase
{
    use RefreshDatabase;

    private int $annee;

    private int $numero = 0;

    private const MAINTENANT = '2026-10-05 09:10:00';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::MAINTENANT);
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Cache::flush();
        foreach (['admin.access', 'inscriptions.rdv.view', 'inscriptions.rdv.accueil', 'inscriptions.candidatures.view', 'students.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function agent(array $permissions = ['admin.access', 'inscriptions.rdv.accueil', 'inscriptions.candidatures.view']): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permissions);

        return $u;
    }

    private function rdv(string $nom, string $prenoms, int $dansJours, ?string $telephone = null, ?string $reference = null): ESBTPRdvReservation
    {
        // Un creneau par debut et par jour : plusieurs familles s'y partagent la place.
        $creneau = ESBTPRdvCreneau::firstOrCreate([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDays($dansJours)->toDateString(),
            'heure_debut' => '10:00:00',
        ], ['heure_fin' => '10:30:00', 'capacite' => 40, 'ouvert' => true]);
        $n = ++$this->numero;
        // Un telephone par candidature et par annee : c'est une contrainte d'unicite.
        $telephone ??= '+22505'.sprintf('%08d', $n);
        $candidature = DB::table('esbtp_candidatures')->insertGetId([
            'nom' => $nom, 'prenoms' => $prenoms, 'date_naissance' => '2007-03-12',
            'telephone' => $telephone, 'email' => 'famille'.$n.'@exemple.ci',
            'annee_universitaire_id' => $this->annee, 'consentement_at' => now(), 'statut' => 'en_attente',
            'tuteur_nom' => 'Tuteur', 'tuteur_telephone' => '+2250505050505', 'tuteur_lien' => 'Père',
            'reference_publique' => $reference ?? 'REF'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature, 'statut' => 'confirmee',
            'nom' => $nom, 'prenoms' => $prenoms, 'telephone' => $telephone,
            'date_naissance' => '2007-03-12', 'email' => 'famille'.$n.'@exemple.ci',
        ]);
    }

    private function cles(string $html): array
    {
        preg_match_all('/data-li-cle="(\d+)"/', $html, $m);

        return array_map('intval', $m[1]);
    }

    public function test_un_nom_retrouve_le_rendez_vous_a_venir_comme_le_passe(): void
    {
        $avenir = $this->rdv('KOUASSI', 'Ama', 3);
        // La meme famille, une annee plus tot : autre candidature, autre annee.
        $this->annee = ESBTPAnneeUniversitaire::factory()->create()->id;
        $passe = $this->rdv('KOUASSI', 'Ama', -4, '+22505'.sprintf('%08d', 1));
        $autre = $this->rdv('TRAORE', 'Issa', 3);

        $html = $this->actingAs($this->agent())->get(route('esbtp.rendez-vous.recherche', ['q' => 'kouassi ama']))
            ->assertOk()->getContent();

        $this->assertEqualsCanonicalizing([$avenir->id, $passe->id], $this->cles($html));
        $this->assertStringNotContainsString('TRAORE', $html);
        $this->assertStringContainsString(route('esbtp.rendez-vous.accueil.index', ['jour' => Carbon::today()->addDays(3)->toDateString()]), $html);
    }

    public function test_sans_texte_on_voit_ce_qui_vient_dans_l_ordre_du_calendrier(): void
    {
        $loin = $this->rdv('A', 'Loin', 9);
        $proche = $this->rdv('B', 'Proche', 1);
        $this->rdv('C', 'Passe', -2);

        $html = $this->actingAs($this->agent())->get(route('esbtp.rendez-vous.recherche'))->assertOk()->getContent();

        $this->assertSame([$proche->id, $loin->id], $this->cles($html));
    }

    public function test_un_telephone_tape_avec_espaces_et_une_reference_avec_tiret(): void
    {
        $ama = $this->rdv('KOUASSI', 'Ama', 2, '+2250707123456', 'ABCD1234');
        $this->rdv('TRAORE', 'Issa', 2, '+2250101999999', 'WXYZ9876');
        $agent = $this->agent();

        $this->assertSame([$ama->id], $this->cles($this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche', ['q' => '07 07 12 34']))->getContent()));
        $this->assertSame([$ama->id], $this->cles($this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche', ['q' => 'abcd-1234']))->getContent()));
    }

    public function test_la_suite_arrive_par_tranches_sans_repetition(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->rdv('FAMILLE', 'N'.$i, 1 + ($i % 3));
        }
        $agent = $this->agent();

        $page = $this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche'))
            ->assertOk()->assertSee('data-page-suivante="2"', false)->getContent();
        $suite = $this->actingAs($agent)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.rendez-vous.recherche', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()->assertJsonPath('pagination.total', 30)->json('rows_html');

        $tous = array_merge($this->cles($page), $this->cles($suite));
        $this->assertCount(30, $tous);
        $this->assertCount(30, array_unique($tous));
    }

    public function test_l_acces_suit_les_permissions_du_planning_ou_de_l_accueil(): void
    {
        $this->actingAs($this->agent(['admin.access', 'inscriptions.rdv.view']))
            ->get(route('esbtp.rendez-vous.recherche'))->assertOk();
        $this->actingAs($this->agent(['admin.access']))
            ->get(route('esbtp.rendez-vous.recherche'))->assertForbidden();
    }

    public function test_klassci_cli_retrouve_le_meme_rendez_vous(): void
    {
        $ama = $this->rdv('KOUASSI', 'Ama', 2, '+2250707123456', 'ABCD1234');
        $this->rdv('TRAORE', 'Issa', 2);
        Sanctum::actingAs(User::factory()->create(), ['cli:read']);

        $this->getJson(route('api.cli.rendez-vous.recherche', ['q' => 'kouassi']))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.rendez_vous.0.id', $ama->id)
            ->assertJsonPath('data.rendez_vous.0.reference', 'ABCD-1234')
            ->assertJsonPath('data.rendez_vous.0.date', Carbon::today()->addDays(2)->toDateString())
            ->assertJsonPath('data.rendez_vous.0.etat_accueil', 'attendu')
            ->assertJsonMissingPath('data.rendez_vous.0.telephone')
            ->assertJsonPath('data.rendez_vous.0.telephone_masque', \App\Services\Verification\MasqueContact::telephoneGroupe('+2250707123456'));
    }

    public function test_un_matricule_fait_de_chiffres_retrouve_la_reinscription(): void
    {
        $etudiant = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => '22-0545']);
        $classe = \App\Models\ESBTPClasse::factory()->create();
        $demande = \App\Models\ESBTPReinscriptionDemande::create([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee,
            'classe_souhaitee_id' => $classe->id, 'statut' => \App\Models\ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            'consentement_at' => now(),
        ]);
        $creneau = ESBTPRdvCreneau::firstOrCreate([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDay()->toDateString(),
            'heure_debut' => '10:00:00',
        ], ['heure_fin' => '10:30:00', 'capacite' => 40, 'ouvert' => true]);
        $resa = ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'reinscription_demande_id' => $demande->id, 'statut' => 'confirmee',
            'nom' => 'BAMBA', 'prenoms' => 'Awa', 'telephone' => '+2250102030405', 'date_naissance' => '2006-01-01',
        ]);
        $this->rdv('KOUASSI', 'Ama', 1, '+2250707123456');
        $agent = $this->agent(['admin.access', 'inscriptions.rdv.accueil']);

        foreach (['22-0545', '0545'] as $saisie) {
            $this->assertSame([$resa->id], $this->cles(
                $this->actingAs($agent)->get(route('esbtp.rendez-vous.recherche', ['q' => $saisie]))->getContent()
            ), "« {$saisie} » doit retrouver le matricule, pas seulement un téléphone.");
        }
    }

    private function reinscription(\App\Models\ESBTPEtudiant $etudiant, string $nom, string $prenoms, string $telephone): ESBTPRdvReservation
    {
        $demande = $this->demande($etudiant);
        $creneau = ESBTPRdvCreneau::firstOrCreate([
            'annee_universitaire_id' => $this->annee,
            'date' => Carbon::today()->addDay()->toDateString(),
            'heure_debut' => '10:00:00',
        ], ['heure_fin' => '10:30:00', 'capacite' => 40, 'ouvert' => true]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'reinscription_demande_id' => $demande->id, 'statut' => 'confirmee',
            'nom' => $nom, 'prenoms' => $prenoms, 'telephone' => $telephone, 'date_naissance' => '2006-01-01',
        ]);
    }

    private function demande(\App\Models\ESBTPEtudiant $etudiant): \App\Models\ESBTPReinscriptionDemande
    {
        return \App\Models\ESBTPReinscriptionDemande::create([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee,
            'classe_souhaitee_id' => \App\Models\ESBTPClasse::factory()->create()->id,
            'statut' => \App\Models\ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            'consentement_at' => now(),
        ]);
    }

    private function chercher(string $q): string
    {
        return $this->actingAs($this->agent(['admin.access', 'inscriptions.rdv.accueil', 'students.view']))
            ->get(route('esbtp.rendez-vous.recherche', ['q' => $q]))->assertOk()->getContent();
    }

    public function test_apostrophe_tiret_et_ordre_des_noms_ne_comptent_pas(): void
    {
        $nguessan = $this->rdv('N’GUESSAN', 'Wilfried Yvan', 2);
        $georges = $this->rdv('KOUADIO', 'Georges-Wilfried', 3);
        $this->rdv('TRAORE', 'Issa', 2);

        foreach (["n'guessan wilfried", 'NGUESSAN', 'wilfried yvan nguessan'] as $saisie) {
            $this->assertSame([$nguessan->id], $this->cles($this->chercher($saisie)), $saisie);
        }
        $this->assertSame([$georges->id], $this->cles($this->chercher('Georges Wilfried Kouadio')));
    }

    public function test_une_faute_de_frappe_propose_l_orthographe_voisine_et_le_dit(): void
    {
        $georges = $this->rdv('KOUADIO', 'Georges Wilfried', 3);
        $this->rdv('TRAORE', 'Issa', 2);

        $html = $this->chercher('KOUADO GEORGES');
        $this->assertSame([$georges->id], $this->cles($html));
        $this->assertStringContainsString('orthographes voisines', $html);

        // L'exact passe avant : une faute ne fait pas remonter les homonymes.
        $this->rdv('KOUAKOU', 'Georges', 4);
        $this->assertNotContains($georges->id, $this->cles($this->chercher('KOUAKOU GEORGES')));
        $this->assertStringNotContainsString('orthographes voisines', $this->chercher('KOUADIO GEORGES'));
    }

    public function test_le_nom_et_le_telephone_de_l_eleve_retrouvent_la_reservation_faite_par_un_parent(): void
    {
        $eleve = \App\Models\ESBTPEtudiant::factory()->create([
            'matricule' => 'MESBTP25-0368', 'nom' => 'KOUADIO', 'prenoms' => 'GEORGES WILFRIED', 'telephone' => '0500508292',
        ]);
        // Le pere a reserve sous son propre nom et son propre numero.
        $resa = $this->reinscription($eleve, 'KOUADIO', 'Yao Pierre', '+2250707070707');
        $this->rdv('TRAORE', 'Issa', 2);

        foreach (['kouadio georges', 'MESBTP25-0368', '0368', '+225 05 00 50 82 92', '05 00 50 82 92'] as $saisie) {
            $this->assertSame([$resa->id], $this->cles($this->chercher($saisie)), $saisie);
        }
    }

    public function test_un_eleve_sans_rendez_vous_est_nomme_au_lieu_d_une_liste_vide(): void
    {
        $sansDemande = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0368', 'nom' => 'KOUADIO', 'prenoms' => 'GEORGES WILFRIED']);
        $avecDemande = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0400', 'nom' => 'KOUADIO', 'prenoms' => 'GEORGES ALAIN']);
        $this->demande($avecDemande);
        $dejaReserve = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MESBTP25-0500', 'nom' => 'KOUADIO', 'prenoms' => 'GEORGES MARC']);
        $this->reinscription($dejaReserve, 'KOUADIO', 'Georges Marc', '+2250101010101');

        $html = $this->chercher('kouadio georges');

        $this->assertStringContainsString('sans rendez-vous', $html);
        $this->assertStringContainsString('MESBTP25-0368', $html);
        $this->assertStringContainsString('Aucune demande de réinscription déposée', $html);
        $this->assertStringContainsString('Demande de réinscription en attente, sans créneau réservé', $html);
        $this->assertStringContainsString(route('esbtp.etudiants.show', $sansDemande->id), $html);
        // Celui qui a reserve est dans la liste des rendez-vous, pas dans l'encart.
        $this->assertStringNotContainsString(route('esbtp.etudiants.show', $dejaReserve->id), $html);

        Sanctum::actingAs(User::factory()->create(), ['cli:read']);
        $this->getJson(route('api.cli.rendez-vous.recherche', ['q' => 'MESBTP25-0368']))
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.eleves_sans_rendez_vous.0.matricule', 'MESBTP25-0368')
            ->assertJsonPath('data.eleves_sans_rendez_vous.0.demande_reinscription', null);
    }

    public function test_une_faute_sur_un_nom_tape_seul_est_rattrapee(): void
    {
        $fabrice = $this->rdv('KOUADIO', 'Yao Fabrice', 3);
        $this->rdv('TRAORE', 'Issa', 2);

        $html = $this->chercher('KOUADO');

        $this->assertSame([$fabrice->id], $this->cles($html));
        $this->assertStringContainsString('orthographes voisines', $html);
    }

    public function test_le_message_vide_n_affirme_que_ce_qui_a_ete_verifie(): void
    {
        // Personne ne porte ce nom : on peut le dire.
        $this->assertStringContainsString("Aucun élève de l'école ne répond", $this->chercher('ZZYZX'));

        // Recherche limitee aux nouvelles inscriptions : les eleves ne sont pas
        // cherches, donc rien n'est affirme a leur sujet.
        \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MX-1', 'nom' => 'SORO', 'prenoms' => 'Ali']);
        $html = $this->actingAs($this->agent(['admin.access', 'inscriptions.rdv.accueil']))
            ->get(route('esbtp.rendez-vous.recherche', ['q' => 'soro ali', 'type' => 'candidature']))->assertOk()->getContent();
        $this->assertStringNotContainsString('Aucun élève', $html);
        $this->assertStringNotContainsString('connu de l', $html);

        // L'eleve a un rendez-vous, mais le filtre de statut le cache.
        $eleve = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MX-2', 'nom' => 'KONE', 'prenoms' => 'Awa']);
        $this->reinscription($eleve, 'KONE', 'Awa', '+2250101010101');
        $html = $this->actingAs($this->agent(['admin.access', 'inscriptions.rdv.accueil']))
            ->get(route('esbtp.rendez-vous.recherche', ['q' => 'kone awa', 'statut' => 'annulee']))->assertOk()->getContent();
        $this->assertStringContainsString('a déjà un rendez-vous, mais pas dans les filtres choisis', $html);
        $this->assertStringNotContainsString('Aucun élève', $html);
    }

    public function test_un_eleve_sans_rendez_vous_se_retrouve_sans_son_apostrophe(): void
    {
        $eleve = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MX-3', 'nom' => "N'GUESSAN", 'prenoms' => 'Koffi']);

        $html = $this->chercher('NGUESSAN');

        $this->assertStringContainsString(route('esbtp.etudiants.show', $eleve->id), $html);
        $this->assertStringContainsString('jamais réservé', $html);
    }

    public function test_l_indicatif_est_celui_de_l_instance_pas_celui_de_la_cote_d_ivoire(): void
    {
        \App\Domain\Notifications\PhoneNormalizer::definirResolveurReglages(static fn (string $cle): ?string => match ($cle) {
            \App\Domain\Notifications\PhoneNormalizer::CLE_INDICATIF => '229',
            \App\Domain\Notifications\PhoneNormalizer::CLE_PREFIXES => '01',
            default => null,
        });
        try {
            $eleve = \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'BJ-1', 'nom' => 'HOUNSOU', 'prenoms' => 'Eli', 'telephone' => '0142345678']);
            $resa = $this->reinscription($eleve, 'HOUNSOU', 'Marc', '+22961000000');

            $this->assertSame([$resa->id], $this->cles($this->chercher('+229 01 42 34 56 78')));
        } finally {
            \App\Domain\Notifications\PhoneNormalizer::definirResolveurReglages(null);
        }
    }

    public function test_au_dela_de_cinq_eleves_l_encart_dit_qu_il_y_en_a_d_autres(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            \App\Models\ESBTPEtudiant::factory()->create(['matricule' => 'MZ-'.$i, 'nom' => 'YAO', 'prenoms' => 'Enfant '.$i]);
        }

        $html = $this->chercher('yao');

        $this->assertSame(5, substr_count($html, 'class="rdr-eleve"'));
        $this->assertStringContainsString("D'autres élèves répondent aussi", $html);
    }
}
