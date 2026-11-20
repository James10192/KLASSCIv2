<?php

namespace Tests\Unit\Assistant;

use App\Models\User;
use App\Services\Chatbot\Tools\EtatJuryLmdTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        foreach (['module.lmd.access', 'lmd.jury.view', 'lmd.jury.preside', 'lmd.jury.deliberate', 'lmd.jury.publish', 'identity.direct_studies', 'lmd.pv.export'] as $droit) {
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
        $this->accorder(2, ['module.lmd.access', 'lmd.jury.view']);

        $vus = array_column($this->lire(2, [])['diagnostic']['jurys'], 'jury_id');

        self::assertSame([1], $vus);
        self::assertSame('text', $this->lire(2, ['jury_id' => 2])['display_type']);
    }

    public function test_l_outil_suit_les_droits_de_l_ecran(): void
    {
        $this->seedIssuableJury();
        $outil = new EtatJuryLmdTool();
        $this->accorder(2, ['lmd.jury.view']);

        self::assertFalse($outil->isAvailableFor(User::findOrFail(2)), 'Sans le module LMD, pas d’outil.');

        $this->accorder(2, ['module.lmd.access', 'lmd.jury.view']);
        self::assertTrue($outil->isAvailableFor(User::findOrFail(2)));
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
