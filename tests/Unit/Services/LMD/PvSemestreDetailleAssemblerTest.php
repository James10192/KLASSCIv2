<?php

namespace Tests\Unit\Services\LMD;

use App\Exports\LmdPvSemestreDetailleExport;
use App\Services\LMD\PvSemestreDetailleAssembler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

/**
 * Le PV détaillé d'un semestre (modèle USAT) imprime ce que KLASSCI a calculé :
 * notes des ECUE, moyenne et validation de l'UE, session, crédits, décision.
 */
class PvSemestreDetailleAssemblerTest extends OfficialDocumentDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('esbtp_filieres', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_unites_enseignement', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_matieres', function (Blueprint $t) { $t->id(); $t->string('code')->nullable(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_resultats_ues', function (Blueprint $t) { $t->id(); foreach (['bulletin_id', 'unite_enseignement_id', 'etudiant_id'] as $c) { $t->unsignedBigInteger($c); } $t->decimal('moyenne', 5, 2)->nullable(); $t->string('statut')->nullable(); $t->integer('credit')->default(0); $t->timestamps(); $t->softDeletes(); });
        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $t) { $t->id(); foreach (['bulletin_id', 'resultat_ue_id', 'matiere_id', 'etudiant_id'] as $c) { $t->unsignedBigInteger($c); } foreach (['moyenne', 'note_session_normale', 'note_rattrapage', 'note_finale'] as $c) { $t->decimal($c, 5, 2)->nullable(); } $t->integer('credit')->default(0); $t->timestamps(); $t->softDeletes(); });
    }

    public function test_une_ligne_par_etudiant_avec_les_notes_par_ue_et_le_resultat(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();

        $pv = app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh());

        self::assertSame(['CHI2101', 'MAT2101'], array_column($pv['ues'], 'code'));
        self::assertSame(['Algèbre 1', 'Analyse 1'], array_column($pv['ues'][1]['ecues'], 'name'));

        $koffi = collect($pv['rows'])->firstWhere('matricule', 'LMD001');
        self::assertEquals([14.0], $koffi['ues'][0]['notes']);
        self::assertSame('2026', $koffi['ues'][0]['annee']);
        self::assertSame('SN', $koffi['ues'][0]['session']);

        $yao = collect($pv['rows'])->firstWhere('matricule', 'LMD002');
        // Une ECUE passée au rattrapage met l'UE, puis le semestre, en session de rattrapage.
        self::assertSame('SR', $yao['ues'][1]['session']);
        self::assertSame('SR', $yao['session']);
        // UE non validée : pas d'année d'obtention.
        self::assertNull($yao['ues'][0]['annee']);
        self::assertSame(24, $yao['credits']);
        // Rattrapage à 12, session normale à 10 : le PV imprime 10, comme le bulletin.
        self::assertEquals(10.0, $yao['ues'][1]['notes'][0]);
    }

    public function test_une_ue_acquise_une_annee_precedente_porte_son_annee_d_acquisition(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();
        $now = now();
        DB::table('esbtp_annee_universitaires')->insert(['id' => 3, 'libelle' => '2024-2025', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_lmd_bulletins')->insert(['id' => 200, 'etudiant_id' => 10, 'classe_id' => 1, 'parcours_id' => 1, 'annee_universitaire_id' => 3, 'semestre' => 1, 'moyenne_generale' => 12, 'credits_capitalises' => 3, 'credits_totaux' => 30, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('esbtp_lmd_resultats_ues')->insert(['id' => 50, 'bulletin_id' => 200, 'unite_enseignement_id' => 2, 'etudiant_id' => 10, 'moyenne' => 12, 'statut' => 'AQ', 'credit' => 3, 'created_at' => $now, 'updated_at' => $now]);

        $koffi = collect(app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh())['rows'])->firstWhere('matricule', 'LMD001');

        self::assertSame('2025', $koffi['ues'][0]['annee'], 'Chimie acquise en 2024-2025');
        self::assertSame('2026', $koffi['ues'][1]['annee'], 'Mathématiques acquises cette année');
    }

    public function test_ni_une_annee_posterieure_ni_un_brouillon_ne_fournissent_l_annee(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();
        $now = now();
        DB::table('esbtp_annee_universitaires')->insert(['id' => 3, 'libelle' => '2024-2025', 'created_at' => $now, 'updated_at' => $now]);
        // Mathématiques (UE 1) : acquise en 2026-2027, APRÈS le jury, et en brouillon en 2024-2025.
        $bulletin = ['etudiant_id' => 10, 'classe_id' => 1, 'parcours_id' => 1, 'semestre' => 1, 'moyenne_generale' => 12, 'credits_capitalises' => 4, 'credits_totaux' => 30, 'created_at' => $now, 'updated_at' => $now];
        DB::table('esbtp_lmd_bulletins')->insert([
            ['id' => 201, 'annee_universitaire_id' => 2, 'is_published' => true] + $bulletin,
            ['id' => 202, 'annee_universitaire_id' => 3, 'is_published' => false] + $bulletin,
        ]);
        foreach ([51 => 201, 52 => 202] as $id => $bulletinId) {
            DB::table('esbtp_lmd_resultats_ues')->insert(['id' => $id, 'bulletin_id' => $bulletinId, 'unite_enseignement_id' => 1, 'etudiant_id' => 10, 'moyenne' => 12, 'statut' => 'AQ', 'credit' => 4, 'created_at' => $now, 'updated_at' => $now]);
        }

        $koffi = collect(app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh())['rows'])->firstWhere('matricule', 'LMD001');

        self::assertSame('2026', $koffi['ues'][1]['annee']);
    }

    public function test_un_bulletin_sans_detail_d_ue_mais_avec_moyenne_compte_present(): void
    {
        // Constaté sur presentation : moyenne et décision, aucun résultat d'UE.
        $jury = $this->seedIssuableJury();

        $stats = collect(app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh())['statistiques'])->keyBy('libelle');

        self::assertSame(2, $stats['Présents']['nombre']);
        self::assertSame(0, $stats['Absents']['nombre']);
    }

    public function test_les_statistiques_comptent_inscrits_presents_et_decisions(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();

        $stats = collect(app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh())['statistiques'])->keyBy('libelle');

        self::assertSame(2, $stats['Inscrits']['nombre']);
        self::assertSame(2, $stats['Présents']['nombre']);
        self::assertSame(0, $stats['Absents']['nombre']);
        self::assertSame(100.0, array_sum(array_map(fn ($s) => $s['pourcentage'], array_slice($stats->values()->all(), 3))));
    }

    public function test_l_export_aligne_chaque_ligne_sur_l_entete(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();
        $pv = app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh());

        $rows = (new LmdPvSemestreDetailleExport($pv))->array();
        $entete = $rows[8];
        $premiere = $rows[9];

        // 8 colonnes d'identité, (1 ECUE + 3) + (2 ECUE + 3), 5 de résultat.
        self::assertCount(8 + 4 + 5 + 5, $entete);
        self::assertCount(count($entete), $premiere);
    }

    public function test_le_pdf_se_rend_avec_les_statistiques_et_le_lieu(): void
    {
        $jury = $this->seedIssuableJury();
        $this->seedResultats();
        $payload = app(PvSemestreDetailleAssembler::class)->forJury($jury->fresh());

        $html = view('esbtp.lmd.jurys.pdf.pv-semestre-detaille', compact('payload'))->render();

        self::assertStringContainsString('UE 1 : CHI2101 Chimie', $html);
        self::assertStringContainsString('Statistiques du semestre 1', $html);
        self::assertStringContainsString('Admis sous condition', $html);

        if ($dossier = getenv('PV_APERCU_DIR')) {
            file_put_contents($dossier.'/pv-semestre.pdf', \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a3', 'landscape')->output());
            config(['filesystems.disks.apercu' => ['driver' => 'local', 'root' => $dossier]]);
            \Maatwebsite\Excel\Facades\Excel::store(new LmdPvSemestreDetailleExport($payload), 'pv-semestre.xlsx', 'apercu');
        }
    }

    private function seedResultats(): void
    {
        $now = now();
        DB::table('esbtp_unites_enseignement')->insert([
            ['id' => 1, 'code' => 'MAT2101', 'name' => 'Mathématiques 1', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'CHI2101', 'name' => 'Chimie', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => 1, 'code' => 'MAT21011', 'name' => 'Algèbre 1', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'MAT21012', 'name' => 'Analyse 1', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'CHI21011', 'name' => 'Chimie générale', 'created_at' => $now, 'updated_at' => $now],
        ]);
        // Bulletin 100 = KOFFI (10), bulletin 101 = YAO (11), posés par seedIssuableJury.
        $ues = [
            [1, 100, 2, 10, 14, 'AQ', 3], [2, 100, 1, 10, 12, 'AQ', 4],
            [3, 101, 2, 11, 7, 'NAQ', 3], [4, 101, 1, 11, 10.5, 'AQ', 4],
        ];
        foreach ($ues as [$id, $b, $ue, $e, $moy, $statut, $cr]) {
            DB::table('esbtp_lmd_resultats_ues')->insert(['id' => $id, 'bulletin_id' => $b, 'unite_enseignement_id' => $ue, 'etudiant_id' => $e, 'moyenne' => $moy, 'statut' => $statut, 'credit' => $cr, 'created_at' => $now, 'updated_at' => $now]);
        }
        $ecues = [
            [1, 3, 10, 14, null], [2, 1, 10, 11, null], [2, 2, 10, 13, null],
            [3, 3, 11, 7, null], [4, 1, 11, 10, 12], [4, 2, 11, 11, null],
        ];
        foreach ($ecues as $i => [$ue, $matiere, $e, $note, $ratt]) {
            DB::table('esbtp_lmd_resultats_ecues')->insert(['id' => $i + 1, 'bulletin_id' => $e === 10 ? 100 : 101, 'resultat_ue_id' => $ue, 'matiere_id' => $matiere, 'etudiant_id' => $e, 'moyenne' => $note, 'note_finale' => $ratt ?? $note, 'note_rattrapage' => $ratt, 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
