<?php

namespace Tests\Unit\Assistant;

use App\Models\User;
use App\Services\Chatbot\Tools\EtatJuryLmdTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use App\Domain\Assistant\Outils\ResumeOutil;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

/**
 * Séance d'entraînement de Nanan sur les jurys LMD : elle lit ce que l'écran
 * du jury affiche, dans le même périmètre, et ne dit jamais « prêt » quand
 * l'émission du PV serait refusée.
 */
class EtatJuryLmdToolTest extends OfficialDocumentDatabaseTestCase
{
    /** @var array<string, list<int>> droit => utilisateurs qui l'ont */
    private array $droits = [];

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::create('esbtp_filieres', function ($t) { $t->id(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        \Illuminate\Support\Facades\Schema::create('esbtp_niveau_etudes', function ($t) { $t->id(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::table('esbtp_annee_universitaires', fn ($t) => $t->boolean('is_current')->default(false));
        DB::table('esbtp_annee_universitaires')->where('id', 1)->update(['is_current' => true]);
        foreach (['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside', 'lmd.jury.deliberate', 'lmd.jury.publish', 'identity.direct_studies', 'lmd.pv.export', 'admin.access', 'identity.registrar', 'identity.registrar_clerk'] as $droit) {
            Gate::define($droit, fn (User $u) => in_array((int) $u->id, $this->droits[$droit] ?? [], true));
        }
    }

    public function test_un_jury_pret_est_annonce_pret_avec_ses_liens(): void
    {
        $this->seedIssuableJury();
        $this->accorder(1, ['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside', 'lmd.pv.export']);

        $jury = $this->lire(1, ['jury_id' => 1])['diagnostic']['jurys'][0];

        self::assertTrue($jury['pret_pour_le_pv']);
        self::assertSame([], $jury['etapes_manquantes']);
        self::assertArrayHasKey('pv_detaille_semestre_pdf', $jury['liens']);
    }

    public function test_il_nomme_l_etape_manquante_et_le_membre_qui_n_a_pas_signe(): void
    {
        $jury = $this->seedIssuableJury();
        $jury->membres()->where('user_id', 2)->firstOrFail()->forceFill(['signature_data' => null, 'signature_at' => null])->save();
        $this->accorder(1, ['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside']);

        $lu = $this->lire(1, ['jury_id' => 1])['diagnostic']['jurys'][0];

        self::assertFalse($lu['pret_pour_le_pv']);
        self::assertSame(['Signatures recueillies'], array_column($lu['etapes_manquantes'], 'etape'));
        self::assertSame('Assesseur Test', $lu['membres_sans_signature'][0]['nom']);
        // Sans le droit d'export, aucun lien de PV n'est donné.
        self::assertArrayNotHasKey('pv_detaille_semestre_pdf', $lu['liens']);
    }

    public function test_un_simple_membre_ne_voit_que_ses_jurys(): void
    {
        $this->seedIssuableJury();
        $now = now();
        DB::table('esbtp_lmd_jurys')->insert(['id' => 2, 'annee_universitaire_id' => 1, 'classe_id' => 1, 'parcours_id' => 1, 'semestre' => 2, 'libelle' => 'Jury S2 confidentiel', 'status' => 'en_cours', 'created_at' => $now, 'updated_at' => $now]);
        // L'utilisateur 2 est assesseur du jury 1 seulement, et ne fait que consulter.
        $this->accorder(2, ['module.lmd.access', 'lmd.jury.view', 'identity.registrar_clerk']);

        $vus = array_column($this->lire(2, [])['diagnostic']['jurys'], 'jury_id');

        self::assertSame([1], $vus);
        self::assertSame('text', $this->lire(2, ['jury_id' => 2])['display_type']);
    }

    public function test_sans_jury_il_donne_les_vraies_etapes_et_les_vrais_roles(): void
    {
        // Constaté sur une école sans jury : le modèle inventait un « rapporteur ».
        $this->seedIssuableJury();
        $this->accorder(1, ['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside']);

        $resultat = $this->lire(1, ['classe' => 'Classe inexistante']);

        self::assertTrue($resultat['diagnostic']['aucun_jury']);
        self::assertSame(['Président', 'Assesseur', 'Secrétaire', 'Consultatif'], $resultat['diagnostic']['roles_des_membres']);
        self::assertSame(['Notes validées', 'Jury composé', 'Décisions calculées', 'Signatures recueillies'], array_keys($resultat['diagnostic']['etapes_avant_le_pv']));
        self::assertStringContainsString('roles_des_membres', ResumeOutil::pourModele('etat_jury_lmd', $resultat, true));
    }

    public function test_l_outil_suit_les_droits_de_l_ecran(): void
    {
        $this->seedIssuableJury();
        $outil = new EtatJuryLmdTool();
        $this->accorder(2, ['lmd.jury.view']);

        self::assertFalse($outil->isAvailableFor(User::findOrFail(2)), 'Sans le module LMD, pas d’outil.');

        $this->accorder(2, ['module.lmd.access']);
        self::assertFalse($outil->isAvailableFor(User::findOrFail(2)), 'Sans identité de scolarité, l’écran répond 403 : Nanan aussi.');

        $this->accorder(2, ['identity.registrar']);
        self::assertTrue($outil->isAvailableFor(User::findOrFail(2)));
    }

    public function test_une_liste_de_huit_jurys_garde_tous_ses_verdicts_dans_la_limite(): void
    {
        $this->seedIssuableJury();
        $now = now();
        for ($id = 2; $id <= 9; $id++) {
            DB::table('esbtp_lmd_jurys')->insert(['id' => $id, 'annee_universitaire_id' => 1, 'classe_id' => 1, 'parcours_id' => 1, 'semestre' => 1, 'libelle' => 'Délibération '.$id.' '.str_repeat('Licence professionnelle en Productions Végétales, ', 4), 'status' => 'preparation', 'created_at' => $now, 'updated_at' => $now]);
        }
        $this->accorder(1, ['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside', 'admin.access']);

        $resultat = $this->lire(1, []);
        $pourModele = ResumeOutil::pourModele('etat_jury_lmd', $resultat, true);
        $decode = json_decode($pourModele, true);

        self::assertNotNull($decode, 'Le résumé rendu au modèle doit rester un JSON valide.');
        $verdicts = $decode['diagnostic']['jurys'] ?? [];
        self::assertCount(8, $verdicts);
        foreach ($verdicts as $jury) {
            self::assertArrayHasKey('pret_pour_le_pv', $jury);
        }
    }

    private function accorder(int $userId, array $droits): void
    {
        foreach ($droits as $droit) {
            $this->droits[$droit][] = $userId;
        }
    }

    private function lire(int $userId, array $args): array
    {
        return (new EtatJuryLmdTool())->execute($args, User::findOrFail($userId));
    }
}
