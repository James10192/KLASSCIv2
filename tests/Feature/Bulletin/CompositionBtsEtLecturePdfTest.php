<?php

namespace Tests\Feature\Bulletin;

use App\Domain\Reglages\ModificationDeReglages;
use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPBulletin;
use App\Models\User;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use App\Helpers\SettingsHelper;
use App\Models\Setting;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrats de separation : la consultation d'un PDF ne doit pas ecrire ;
 * la composition du semestre est explicite et configurable par etablissement.
 */
class CompositionBtsEtLecturePdfTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts, SeedsConfiguredBulletin;

    public function test_composition_par_matiere_ou_par_blocs_pour_les_deux_bulletins_yakro(): void
    {
        // Ecran BTS1 batiment G : 5 coefficients en general, 27 en technique.
        $generales = collect([(object) [
            'moyenne' => 15.43, 'coefficient' => 5, 'statut' => 'note',
            'type_formation' => 'generale',
        ]]);
        $techniques = collect([(object) [
            'moyenne' => 14.21, 'coefficient' => 27, 'statut' => 'note',
            'type_formation' => 'technologique_professionnelle',
        ]]);
        $toutes = $generales->merge($techniques);
        $service = app(BulletinService::class);

        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'ponderee');
        $this->assertEqualsWithDelta(14.40, $service->composerLaMoyenneDuSemestre(
            $toutes, $generales, $techniques, 15.43, 14.21
        ), 0.01);

        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'blocs');
        SettingsHelper::setOrCreate('bulletin_bloc_general_coef', '1');
        SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', '1');
        $this->assertEqualsWithDelta(14.82, $service->composerLaMoyenneDuSemestre(
            $toutes, $generales, $techniques, 15.43, 14.21
        ), 0.01);

        SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', '2');
        $this->assertEqualsWithDelta((15.43 + 14.21 * 2) / 3, $service->composerLaMoyenneDuSemestre(
            $toutes, $generales, $techniques, 15.43, 14.21
        ), 0.01);
    }

    public function test_reglages_bts_refusent_mode_et_coefficients_invalides(): void
    {
        $controle = app(ModificationDeReglages::class);
        $mode = $this->setting('bulletin_moyenne_mode', 'ponderee');
        [$valeur, $erreur] = $controle->normaliserSelonLeReglage($mode, 'blocs');
        $this->assertSame('blocs', $valeur);
        $this->assertNull($erreur);

        [, $erreur] = $controle->normaliserSelonLeReglage($mode, 'autre');
        $this->assertNotNull($erreur);

        foreach (['bulletin_bloc_general_coef', 'bulletin_bloc_professionnel_coef'] as $cle) {
            $poids = $this->setting($cle, '1');
            foreach (['0', '-1', '11', 'abc', ''] as $mauvais) {
                // Un poids vide est aussi refuse : sinon le moteur lirait 0.
                // Les reglages sont requis pour eviter une configuration silencieuse.
                $poids->is_required = true;
                [, $erreur] = $controle->normaliserSelonLeReglage($poids, $mauvais);
                $this->assertNotNull($erreur, "$cle a accepte une valeur invalide : $mauvais");
            }
            [, $erreur] = $controle->normaliserSelonLeReglage($poids, '1.5');
            $this->assertNull($erreur);
        }
    }

    public function test_reglages_rendent_accessible_le_choix_premium_et_les_poids(): void
    {
        $vue = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));
        $this->assertStringContainsString('id="bts-moyenne-semestrielle"', $vue);
        $this->assertStringContainsString('<x-au-select', $vue);
        $this->assertStringContainsString('name="setting_bulletin_moyenne_mode"', $vue);
        $this->assertStringContainsString('name="setting_bulletin_bloc_general_coef"', $vue);
        $this->assertStringContainsString('name="setting_bulletin_bloc_professionnel_coef"', $vue);
    }

    public function test_un_bulletin_publie_ou_signe_est_protege_des_editions_et_suppressions(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $bulletin = $this->seedConfiguredBulletin(
            $etudiant->id, $this->classe->id, $this->annee->id
        );
        $controle = app(\App\Services\ESBTP\BulletinConsistencyService::class);

        $this->assertFalse($controle->isProtected($bulletin));
        foreach (['is_published', 'signature_responsable', 'signature_directeur', 'signature_parent'] as $cle) {
            $bulletin->forceFill([$cle => true])->save();
            $this->assertTrue($controle->isProtected($bulletin), "Protection absente pour $cle");
            $bulletin->forceFill([$cle => false])->save();
        }

        $bulletin->forceFill(['is_published' => true])->save();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ESBTPBulletinController::class)->destroy($bulletin);
    }

    public function test_un_lot_avec_un_bulletin_publie_ne_supprime_aucun_document(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $bulletin = $this->seedConfiguredBulletin($etudiant->id, $this->classe->id, $this->annee->id);
        $bulletin->forceFill(['is_published' => true])->save();

        $requete = \Illuminate\Http\Request::create(
            '/esbtp/bulletins/bulk-delete',
            'POST',
            ['ids' => [$bulletin->id]]
        );
        $reponse = app(ESBTPBulletinController::class)->bulkDelete($requete);
        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertFalse((bool) (json_decode($reponse->getContent(), true)['success'] ?? true));
        $this->assertTrue(ESBTPBulletin::query()->whereKey($bulletin->id)->exists());
    }

    public function test_le_rendu_pdf_d_un_bulletin_publie_ne_recrit_pas_ses_resultats(): void
    {
        $this->monterLaClasse();
        $this->actingAs(User::findOrFail(1));
        $eleve = $this->etudiantInscrit();
        $matiere = $this->matiereConfiguree();
        $this->noter($eleve, $this->evaluationDe($matiere), 18);

        $bulletin = $this->seedConfiguredBulletin(
            $eleve->id, $this->classe->id, $this->annee->id, 'semestre1', [$matiere->id]
        );
        $bulletin->forceFill([
            'moyenne_generale' => 9.75,
            'rang' => 12,
            'is_published' => true,
            'note_assiduite' => 0,
        ])->save();

        $avant = $bulletin->fresh()->only(['moyenne_generale', 'rang', 'is_published', 'updated_at']);
        $pdf = app(ESBTPBulletinController::class)->buildBulletinPdf($bulletin, false)->output();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame($avant, $bulletin->fresh()->only(['moyenne_generale', 'rang', 'is_published', 'updated_at']));
    }

    public function test_tous_les_chemins_pdf_get_sont_non_persistants(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/ESBTPBulletinController.php'));

        // Le telechargement du bulletin ne doit plus lancer une regeneration.
        $this->assertStringContainsString('$pdf = $this->buildBulletinPdf($bulletin, false);', $source);
        $this->assertStringContainsString('bool $persist = false): \\Barryvdh\\DomPDF\\PDF', $source);

        // Meme quand il n'existe pas de bulletin officiel, le GET par params
        // doit utiliser la version preview (sans ecriture).
        $start = strpos($source, 'public function genererPDFParParamsUnified(');
        $end = strpos($source, '// Ajouter le logo pour le PDF', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $bloc = substr($source, $start, $end - $start);
        $this->assertStringContainsString('genererDonneesBulletinPreview(', $bloc);
        $this->assertStringNotContainsString('->genererDonneesBulletin(', $bloc);
    }

    private function setting(string $key, string $value): Setting
    {
        return Setting::firstOrCreate(['key' => $key], [
            'value' => $value,
            'type' => 'string',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_required' => true,
        ]);
    }
}
