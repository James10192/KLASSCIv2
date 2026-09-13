<?php

namespace Tests\Unit\Services;

use App\Exceptions\ImpressionBloquee;
use App\Models\User;
use App\Services\DocumentPrintGuard;
use App\Services\PrintDecision;
use App\Services\SoldeEtudiant;
use App\Services\TenantScolariteSettings;
use Mockery;
use Tests\TestCase;

class DocumentPrintGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_solde_blocks_before_approval(): void
    {
        $decision = $this->guard(solde: 25000)->decide($this->printer(), 'certificat', 1);

        $this->assertSame(PrintDecision::SOLDE, $decision->reason);
        $this->assertFalse($decision->allowed);
    }

    public function test_setting_off_skips_both_gates(): void
    {
        $decision = $this->guard(solde: 40000, settingOn: false)->decide($this->printer(), 'certificat', 1);

        $this->assertTrue($decision->allowed);
        $this->assertFalse($decision->gated);
    }

    public function test_assert_printable_throws_impression_bloquee(): void
    {
        $this->expectException(ImpressionBloquee::class);

        $this->guard(solde: 1000)->assertPrintable($this->printer(), 'certificat', 1);
    }

    public function test_export_passes_through_when_setting_off(): void
    {
        $filtre = $this->guard(solde: 40000, settingOn: false)->filtrerExport($this->printer(), 'bulletin', [
            (object) ['id' => 1, 'etudiant_id' => 10],
        ]);

        $this->assertSame([1], $filtre['allowed_ids']);
        $this->assertSame(0, $filtre['bloques_solde']);
        $this->assertSame(0, $filtre['bloques_approbation']);
    }

    public function test_export_does_not_count_permission_as_approval(): void
    {
        $filtre = $this->guard(solde: 0)->filtrerExport($this->consultant(), 'bulletin', [
            (object) ['id' => 1, 'etudiant_id' => 10],
        ]);

        // Un droit de consultation ne vaut pas un accord : rien ne sort. Et le
        // document retenu se compte, au lieu de disparaitre d'un « 0 bloque »
        // qui laissait croire que l'export etait complet.
        $this->assertSame([], $filtre['allowed_ids']);
        $this->assertSame(0, $filtre['bloques_solde']);
        $this->assertSame(1, $filtre['bloques_approbation']);
    }

    /**
     * Le bulletin n'est pas gouverne par `documents.print` mais par sa propre
     * famille. Le coordinateur porte `bulletins.export.bulk` et `bulletins.view`
     * sans aucun droit `documents.*` : exiger `documents.print` de lui tuerait
     * l'export groupe, qui est justement ce que l'ecole lui a accorde.
     */
    public function test_le_bulletin_s_exporte_avec_le_droit_de_sa_propre_famille(): void
    {
        $coordinateur = Mockery::mock(User::class);
        $coordinateur->shouldReceive('can')->with('documents.print')->andReturn(false);
        $coordinateur->shouldReceive('can')->with('bulletins.view')->andReturn(true);
        $coordinateur->shouldReceive('can')->andReturn(false);

        $filtre = $this->guard(solde: 0, settingOn: false)->filtrerExport($coordinateur, 'bulletin', [
            (object) ['id' => 1, 'etudiant_id' => 10],
        ]);

        $this->assertSame([1], $filtre['allowed_ids'], "L'export groupe du coordinateur ne doit pas se fermer.");
    }

    /**
     * Et l'inverse : le certificat, lui, appartient bien a `documents.*`.
     */
    public function test_le_certificat_exige_le_droit_d_imprimer(): void
    {
        $this->assertSame(
            PrintDecision::PERMISSION,
            $this->guard(solde: 0, settingOn: false)->decide($this->consultant(), 'certificat', 1)->reason
        );
    }

    /**
     * Celui qui VOIT sans pouvoir imprimer : caissier, comptable, enseignant.
     * C'est par lui que l'ancienne garde laissait sortir les documents, parce
     * qu'elle acceptait `students.view` a la place de `documents.print`.
     */
    private function consultant(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->with('documents.print')->andReturn(false);
        $user->shouldReceive('can')->andReturn(true);

        return $user;
    }

    private function printer(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->andReturn(true);

        return $user;
    }

    private function settings(bool $on): TenantScolariteSettings
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('printRequiresApproval')->andReturn($on);

        return $settings;
    }

    private function guard(float $solde, bool $settingOn = true): DocumentPrintGuard
    {
        $soldes = Mockery::mock(SoldeEtudiant::class);
        $soldes->shouldReceive('impaye')->andReturn($solde);
        $soldes->shouldReceive('impayes')->andReturnUsing(
            fn (array $ids) => array_fill_keys($ids, $solde)
        );

        return new DocumentPrintGuard($this->settings($settingOn), $soldes);
    }
}
