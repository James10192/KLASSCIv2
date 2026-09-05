<?php

namespace Tests\Feature\Attendance;

use App\Enums\JustificationStatus;
use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Justifier une absence depuis la feuille mobile (fetch multipart, Accept: json).
 *
 * La regle du fondateur : une absence se justifie quand rien n'a ete depose ou
 * quand la justification precedente a ete rejetee. En attente, on ne re-soumet
 * pas (cela reinitialiserait la file de l'administration). Approuvee, c'est
 * terminal. Et une absence n'appartient qu'a son etudiant.
 */
class JustifyAbsenceTest extends TestCase
{
    use RefreshDatabase;

    private const DISQUE = 'local';
    private const DOSSIER = 'absences/justifications';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['attendances.view_own', 'attendances.justify_own'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        foreach (['etudiant', 'superAdmin', 'secretaire'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        Cache::flush();
        InstallationHelper::flushCachedStatus();

        Storage::fake(self::DISQUE);
    }

    public function test_l_etudiant_soumet_un_motif_et_un_pdf_et_la_ligne_passe_en_attente(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant);

        $reponse = $this->actingAs($user)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            [
                'justification' => 'Consultation médicale au CHU, certificat joint.',
                'document' => UploadedFile::fake()->create('certificat-chu.pdf', 120, 'application/pdf'),
            ],
            ['Accept' => 'application/json']
        );

        $reponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('absence.id', $absence->id)
            ->assertJsonPath('absence.justification_status', JustificationStatus::PENDING->value)
            ->assertJsonPath('absence.admin_comment', null);

        $this->assertNotNull($reponse->json('absence.justified_at'));
        $this->assertNotNull($reponse->json('absence.document_url'), 'Le lien signe vers le document doit etre renvoye.');
        $this->assertStringContainsString('signature=', (string) $reponse->json('absence.document_url'));

        $absence->refresh();
        $this->assertSame(JustificationStatus::PENDING, $absence->justification_status);
        $this->assertNotNull($absence->document_path);
        $this->assertStringStartsWith(self::DOSSIER . '/', $absence->document_path);
        Storage::disk(self::DISQUE)->assertExists($absence->document_path);
    }

    public function test_une_justification_en_attente_ne_se_re_soumet_pas(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant, [
            'justification_status' => JustificationStatus::PENDING,
            'justified_at' => now()->subDay(),
            'commentaire' => 'Premier motif depose.',
        ]);

        $this->actingAs($user)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            ['justification' => 'Je re-soumets alors que la premiere est en attente.'],
            ['Accept' => 'application/json']
        )->assertForbidden();

        $this->assertSame('Premier motif depose.', $absence->fresh()->commentaire);
    }

    public function test_une_justification_rejetee_se_re_soumet_et_repasse_en_attente(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant, [
            'justification_status' => JustificationStatus::REJECTED,
            'justified_at' => now()->subDays(3),
            'processed_at' => now()->subDay(),
            'admin_comment' => 'Certificat illisible.',
        ]);

        $reponse = $this->actingAs($user)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            ['justification' => 'Nouveau certificat, plus lisible cette fois.'],
            ['Accept' => 'application/json']
        );

        $reponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('absence.justification_status', JustificationStatus::PENDING->value)
            ->assertJsonPath('absence.admin_comment', null)
            ->assertJsonPath('absence.document_url', null);

        $this->assertStringContainsString('re-soumise', (string) $reponse->json('message'));
        $this->assertSame(JustificationStatus::PENDING, $absence->fresh()->justification_status);
    }

    public function test_un_autre_etudiant_ne_peut_pas_justifier_l_absence(): void
    {
        [, $proprietaire] = $this->etudiantConnecte();
        [$intrus] = $this->etudiantConnecte();
        $absence = $this->absenceDe($proprietaire);

        $this->actingAs($intrus)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            ['justification' => 'Ce n\'est pas mon absence.'],
            ['Accept' => 'application/json']
        )->assertForbidden();

        $this->assertNull($absence->fresh()->justification_status);
    }

    public function test_un_executable_est_refuse(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant);

        $reponse = $this->actingAs($user)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            [
                'justification' => 'Motif valable, mais piece jointe douteuse.',
                'document' => UploadedFile::fake()->create('certificat.exe', 10, 'application/x-msdownload'),
            ],
            ['Accept' => 'application/json']
        );

        $reponse->assertStatus(422)->assertJsonValidationErrors(['document']);
        $this->assertNull($absence->fresh()->justification_status);
        $this->assertSame([], Storage::disk(self::DISQUE)->allFiles(self::DOSSIER));
    }

    public function test_un_motif_trop_court_est_refuse(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant);

        $this->actingAs($user)->post(
            route('esbtp.mes-absences.justify', $absence->id),
            ['justification' => 'Ok'],
            ['Accept' => 'application/json']
        )->assertStatus(422)->assertJsonValidationErrors(['justification']);

        $this->assertNull($absence->fresh()->justification_status);
    }

    public function test_le_formulaire_classique_garde_la_redirection(): void
    {
        [$user, $etudiant] = $this->etudiantConnecte();
        $absence = $this->absenceDe($etudiant);

        $this->actingAs($user)
            ->from(route('esbtp.mes-absences.index'))
            ->post(route('esbtp.mes-absences.justify', $absence->id), [
                'justification' => 'Deces d\'un proche, acte joint plus tard.',
            ])
            ->assertRedirect(route('esbtp.mes-absences.index'))
            ->assertSessionHas('success');

        $this->assertSame(JustificationStatus::PENDING, $absence->fresh()->justification_status);
    }

    /**
     * @return array{0: User, 1: ESBTPEtudiant}
     */
    private function etudiantConnecte(): array
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('etudiant');
        $user->givePermissionTo(['attendances.view_own', 'attendances.justify_own']);

        $etudiant = ESBTPEtudiant::factory()->create(['user_id' => $user->id]);

        return [$user, $etudiant];
    }

    private function absenceDe(ESBTPEtudiant $etudiant, array $attributs = []): ESBTPAttendance
    {
        $annee = ESBTPAnneeUniversitaire::firstOrCreate(
            ['is_current' => true],
            ESBTPAnneeUniversitaire::factory()->raw(['is_current' => true])
        );
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 2, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning test justification',
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1',
            'date_debut' => '2026-01-05',
            'date_fin' => '2026-06-30',
            'is_active' => true,
            'is_current' => true,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $teacher = ESBTPTeacher::create([
            'user_id' => User::factory()->create()->id,
            'matricule' => 'ENS-JUST-' . $etudiant->id,
            'status' => 'active',
        ]);
        $seance = ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'jour' => 'mercredi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $annee->id,
            'date_seance' => '2026-03-04',
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);

        return ESBTPAttendance::create(array_merge([
            'seance_cours_id' => $seance->id,
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-03-04',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => 'absent',
        ], $attributs));
    }
}
