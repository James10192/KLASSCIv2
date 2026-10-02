<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPInscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** La fiche double imprime « Réinscription » pour la valeur enregistrée aujourd'hui, accentuée. */
class FicheDoubleTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_reinscription_accentuee_et_l_ancienne_s_impriment_reinscription(): void
    {
        foreach (['réinscription', 'reinscription'] as $type) {
            $this->assertStringContainsString('Réinscription', $this->rendre($type), "type « {$type} »");
        }
    }

    public function test_une_premiere_inscription_reste_inscription(): void
    {
        $this->assertStringNotContainsString('Réinscription', $this->rendre('première_inscription'));
    }

    private function rendre(string $type): string
    {
        $inscription = ESBTPInscription::factory()->create();
        // L'enum MySQL refuse l'ancienne valeur : on la pose en mémoire, comme une ligne héritée lue.
        $inscription->type_inscription = $type;

        return view('esbtp.inscriptions.pdf.fiche-double', [
            'inscription' => $inscription,
            'school' => \App\Helpers\SettingsHelper::getSchoolInfo(),
            'photo' => null,
            'qr' => null,
            'pieces' => collect(),
        ])->render();
    }
}
