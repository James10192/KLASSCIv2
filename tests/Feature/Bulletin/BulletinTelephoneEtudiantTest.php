<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Services\BulletinService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le telephone de l'etudiant, dans l'en-tete du bulletin, se regle par
 * instance : une ecole peut le masquer, les autres le gardent par defaut.
 *
 * Les deux gabarits sont RENDUS, avec le reglage relu par BulletinService
 * au moment du rendu, comme en production.
 */
class BulletinTelephoneEtudiantTest extends TestCase
{
    private const NUMERO = '0707123456';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->string('category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Cache::flush();
    }

    public static function gabarits(): array
    {
        return [
            'gabarit Yakro' => ['esbtp.bulletins.pdf-configurable'],
            'gabarit Abidjan' => ['esbtp.bulletins.pdf-configurable-abidjan'],
        ];
    }

    /** @dataProvider gabarits */
    public function test_le_numero_est_affiche_par_defaut(string $vue): void
    {
        $html = $this->rendre($vue);

        $this->assertStringContainsString(self::NUMERO, $html);
        $this->assertStringContainsString('Téléphone', $html);
    }

    /** @dataProvider gabarits */
    public function test_le_numero_est_affiche_quand_le_reglage_vaut_un(string $vue): void
    {
        SettingsHelper::setOrCreate('bulletin_show_student_phone', '1', 'bulletin');

        $html = $this->rendre($vue);

        $this->assertStringContainsString(self::NUMERO, $html);
        $this->assertStringContainsString('Téléphone', $html);
    }

    /** @dataProvider gabarits */
    public function test_le_numero_disparait_quand_le_reglage_vaut_zero(string $vue): void
    {
        SettingsHelper::setOrCreate('bulletin_show_student_phone', '0', 'bulletin');

        $html = $this->rendre($vue);

        $this->assertStringNotContainsString(self::NUMERO, $html);
        $this->assertDoesNotMatchRegularExpression('/class="info-label">Téléphone/u', $html);
        // Le reste de la fiche identite reste en place.
        $this->assertStringContainsString('KOUASSI', $html);
    }

    /** @dataProvider gabarits */
    public function test_un_bulletin_deja_rendu_relit_le_reglage(string $vue): void
    {
        SettingsHelper::setOrCreate('bulletin_show_student_phone', '1', 'bulletin');
        $this->assertStringContainsString(self::NUMERO, $this->rendre($vue));

        SettingsHelper::setOrCreate('bulletin_show_student_phone', '0', 'bulletin');
        $this->assertStringNotContainsString(self::NUMERO, $this->rendre($vue));
    }

    private function rendre(string $vue): string
    {
        // Une instance neuve par rendu : le service memorise sa configuration
        // pour la duree d'un export, pas au-dela.
        $settings = app(BulletinService::class)->getPDFConfig();

        $etudiant = new ESBTPEtudiant();
        $etudiant->forceFill([
            'nom' => 'KOUASSI',
            'prenoms' => 'Aya',
            'matricule' => 'MAT-001',
            'telephone' => self::NUMERO,
            'genre' => 'F',
            'date_naissance' => '2004-01-15',
            'lieu_naissance' => 'Yamoussoukro',
        ]);

        $classe = new ESBTPClasse();
        $classe->forceFill(['name' => 'BTS1 GC A']);

        // Les memes cles que BulletinService::genererDonneesBulletin(), avec
        // des valeurs neutres : seul l'en-tete de l'etudiant est examine ici.
        return view($vue, [
            'settings' => $settings,
            'etudiant' => $etudiant,
            'inscription' => null,
            'classe' => $classe,
            'anneeUniversitaire' => null,
            'periode' => 'semestre1',
            'resultatsGeneraux' => collect(),
            'resultatsTechniques' => collect(),
            'moyenneGenerale' => 0,
            'moyenneTechnique' => 0,
            'moyenneGlobale' => 0,
            'moyenneAvecAssiduite' => 0,
            'noteAssiduite' => null,
            'note_assiduite' => null,
            'rang' => null,
            'rangAnnuel' => null,
            'councilDecision' => null,
            'effectif' => 0,
            'meilleure_moyenne' => null,
            'plus_faible_moyenne' => null,
            'moyenne_classe' => null,
            'appreciation' => null,
            'decisionConseil' => null,
            'absences' => [],
            'absencesJustifiees' => 0,
            'absencesNonJustifiees' => 0,
            'absences_justifiees' => 0,
            'absences_non_justifiees' => 0,
            'professeurs' => [],
            'date_edition' => '01/01/2026',
            'photoEtudiantBase64' => null,
            'moyenneSemestre1' => null,
            'moyenneSemestre2' => null,
            'moyenneAnnuelle' => null,
            'semesterWeights' => [],
            'warnings' => [],
            'noteConduite' => null,
            'mentionConduite' => null,
            'absencesParMatiere' => [],
            'totalHeuresAbsencesParMatiere' => 0,
            'classeTroncCommun' => null,
            'isSpecialisation' => false,
            'inscriptionWorkflowAlert' => null,
            'isPdfExport' => true,
        ])->render();
    }
}
