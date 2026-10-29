<?php

namespace Tests\Feature\Emails;

use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `emails:nettoyer-factices` ne touche a rien sans --execute, ne vide que les
 * adresses fabriquees, et sauvegarde avant d'ecrire.
 */
class NettoyerEmailsFacticesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_sans_execute_rien_ne_change(): void
    {
        [$user, $etudiant, $faute] = $this->donnees();

        $this->artisan('emails:nettoyer-factices', ['--sans-mx' => true])
            ->expectsOutputToContain('Simulation')
            ->assertSuccessful();

        $this->assertSame('awa.kone@esbtp.edu', $user->fresh()->email);
        $this->assertSame('m22-0521@esbtp.edu.ci', $etudiant->fresh()->email);
        $this->assertSame([], Storage::disk('local')->allFiles('backups'));
    }

    public function test_sans_inclure_comptes_les_comptes_utilisateurs_ne_sont_que_listes(): void
    {
        [$user, $etudiant] = $this->donnees();

        $this->artisan('emails:nettoyer-factices', ['--sans-mx' => true, '--execute' => true])
            ->expectsOutputToContain('par rôle')
            ->assertSuccessful();

        $this->assertSame('awa.kone@esbtp.edu', $user->fresh()->email, 'Un compte peut se connecter par cette adresse.');
        $this->assertNull($etudiant->fresh()->email);
    }

    public function test_execute_vide_les_factices_et_garde_les_fautes(): void
    {
        [$user, $etudiant, $faute] = $this->donnees();

        $this->artisan('emails:nettoyer-factices', ['--sans-mx' => true, '--execute' => true, '--inclure-comptes' => true])->assertSuccessful();

        $this->assertNull($user->fresh()->email);
        $this->assertNull($etudiant->fresh()->email);
        $this->assertSame('awa@gmail.con', $faute->fresh()->email, 'Une faute de frappe ne se corrige pas sans la famille.');

        $fichiers = Storage::disk('local')->allFiles('backups');
        $this->assertCount(1, $fichiers);
        $sauvegarde = collect(json_decode(Storage::disk('local')->get($fichiers[0]), true));
        $this->assertTrue($sauvegarde->contains(fn ($l) => $l['table'] === 'users' && $l['id'] === $user->id && $l['ancienne_valeur'] === 'awa.kone@esbtp.edu'));
        $this->assertTrue($sauvegarde->contains(fn ($l) => $l['table'] === 'esbtp_etudiants' && $l['colonne'] === 'email' && $l['ancienne_valeur'] === 'm22-0521@esbtp.edu.ci'));
        $this->assertFalse($sauvegarde->contains(fn ($l) => $l['ancienne_valeur'] === 'awa@gmail.con'));
    }

    /** @return array{0: User, 1: ESBTPEtudiant, 2: ESBTPEtudiant} */
    private function donnees(): array
    {
        $user = User::factory()->create(['email' => 'awa.kone@esbtp.edu']);
        $etudiant = ESBTPEtudiant::factory()->create(['email' => 'm22-0521@esbtp.edu.ci']);
        $faute = ESBTPEtudiant::factory()->create(['email' => 'awa@gmail.con']);
        // Les autres adresses des fabriques restent hors du perimetre du test.
        DB::table('esbtp_etudiants')->whereNotIn('id', [$etudiant->id, $faute->id])->update(['email' => null]);

        return [$user, $etudiant, $faute];
    }
}
