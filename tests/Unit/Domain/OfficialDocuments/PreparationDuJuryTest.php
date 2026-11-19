<?php

namespace Tests\Unit\Domain\OfficialDocuments;

use App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que l'écran du jury dit avant le procès-verbal : les mêmes contrôles que
 * l'émission, rangés par étape, chacune pointant l'onglet qui la règle.
 */
class PreparationDuJuryTest extends OfficialDocumentDatabaseTestCase
{
    public function test_un_jury_pret_a_toutes_ses_etapes_vertes(): void
    {
        $readiness = $this->guard()->readiness($this->seedIssuableJury());

        self::assertTrue($readiness['ok']);
        self::assertSame(['notes', 'composition', 'decisions', 'signatures'], array_column($readiness['etapes'], 'cle'));
        self::assertSame([true, true, true, true], array_column($readiness['etapes'], 'ok'));
    }

    public function test_chaque_motif_tombe_dans_son_etape_avec_son_onglet(): void
    {
        $jury = $this->seedIssuableJury();
        $jury->membres()->where('user_id', 2)->firstOrFail()
            ->forceFill(['signature_data' => null, 'signature_at' => null])->save();
        DB::table('esbtp_grade_sheets')->where('id', 1)->update(['status' => 'controlled']);

        $etapes = collect($this->guard()->readiness($jury)['etapes'])->keyBy('cle');

        self::assertFalse($etapes['notes']['ok']);
        self::assertStringContainsString('validées et verrouillées', implode(' ', $etapes['notes']['raisons']));
        self::assertNull($etapes['notes']['onglet']);
        self::assertTrue($etapes['composition']['ok']);
        self::assertFalse($etapes['signatures']['ok']);
        self::assertSame('composition', $etapes['signatures']['onglet']);
    }

    public function test_un_jury_en_preparation_voit_aussi_ce_qui_manque_ailleurs(): void
    {
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_lmd_jurys')->where('id', $jury->id)->update(['status' => 'preparation']);
        DB::table('esbtp_grade_sheets')->where('id', 1)->update(['status' => 'controlled']);

        $readiness = $this->guard()->readiness($jury->fresh());
        $etapes = collect($readiness['etapes'])->keyBy('cle');

        self::assertFalse($readiness['ok']);
        self::assertStringContainsString('Appliquer décisions auto', $etapes['decisions']['raisons'][0]);
        self::assertSame('deliberation', $etapes['decisions']['onglet']);
        // Avant, l'état du jury interrompait le contrôle : ce motif-ci restait caché.
        self::assertFalse($etapes['notes']['ok']);
    }

    public function test_un_jury_sur_une_classe_bts_le_dit_nommement(): void
    {
        Schema::table('esbtp_classes', fn (Blueprint $t) => $t->string('systeme_academique')->nullable());
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_classes')->where('id', 1)->update(['systeme_academique' => 'BTS', 'name' => '1BTS IG A']);

        $notes = collect($this->guard()->readiness($jury)['etapes'])->firstWhere('cle', 'notes');

        self::assertFalse($notes['ok']);
        self::assertStringContainsString('« 1BTS IG A » n’est pas une classe LMD', $notes['raisons'][0]);
    }

    public function test_l_emission_garde_son_message_d_etat_d_origine(): void
    {
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_lmd_jurys')->where('id', $jury->id)->update(['status' => 'preparation']);

        $this->expectExceptionMessage('La première émission du PV exige un jury en cours.');
        DB::transaction(fn () => $this->guard()->assertIssuable($jury->id));
    }

    private function guard(): JuryPvIssuanceGuard
    {
        return app(JuryPvIssuanceGuard::class);
    }
}
