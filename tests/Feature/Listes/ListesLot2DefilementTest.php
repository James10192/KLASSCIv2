<?php

namespace Tests\Feature\Listes;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Listes passees des numeros de page au defilement : la page rend le bas de
 * liste (x-liste-infinie), la suite repond en lignes seules selon le contrat
 * App\Support\ListeInfinie, et le tri se departage par identifiant.
 */
class ListesLot2DefilementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('enseignant', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('teacher', 'web');
        User::factory()->create()->assignRole('superAdmin');
        \App\Helpers\InstallationHelper::flushCachedStatus();

        $this->actingAs(User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]));
        Gate::before(fn () => true);

        \App\Models\ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = \App\Models\ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    private \App\Models\ESBTPAnneeUniversitaire $annee;

    /**
     * Une ligne minimale, colonnes obligatoires remplies selon leur type, cles
     * etrangeres non verifiees : elle n'est jamais rendue, elle sert seulement
     * a ce que la liste ait un total non nul. Sans elle, Laravel saute la
     * requete des lignes, et l'ordre ne serait jamais observe.
     */
    private function ligneMinimale(string $table, array $valeurs = []): int
    {
        $colonnes = DB::select(
            "SELECT COLUMN_NAME AS n, DATA_TYPE AS t, COLUMN_TYPE AS ct FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND IS_NULLABLE = 'NO'
               AND (COLUMN_DEFAULT IS NULL OR COLUMN_DEFAULT = 'NULL') AND EXTRA NOT LIKE '%auto_increment%'",
            [$table]
        );
        foreach ($colonnes as $c) {
            if (array_key_exists($c->n, $valeurs)) {
                continue;
            }
            $valeurs[$c->n] = match (true) {
                str_contains($c->t, 'int') || in_array($c->t, ['decimal', 'float', 'double'], true) => 1,
                $c->t === 'date' => '2026-01-05',
                in_array($c->t, ['datetime', 'timestamp'], true) => now(),
                $c->t === 'time' => '08:00:00',
                $c->t === 'enum' => preg_match("/'([^']*)'/", $c->ct, $m) ? $m[1] : '',
                $c->t === 'json' => '{}',
                default => 'x',
            };
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            return DB::table($table)->insertGetId($valeurs);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Ce qui fait entrer une ligne dans chaque liste, avec ses filtres par defaut.
     */
    private function peupler(string $table): void
    {
        $user = auth()->id();
        match ($table) {
            'esbtp_examens_planifies' => $this->ligneMinimale($table, ['annee_universitaire_id' => $this->annee->id]),
            'esbtp_attendances' => $this->ligneMinimale($table, ['annee_universitaire_id' => $this->annee->id, 'call_type' => 'merged']),
            'esbtp_session_reports' => $this->ligneMinimale($table, ['status' => 'submitted']),
            'esbtp_tpe_declarations' => $this->ligneMinimale($table, [
                'statut' => \App\Enums\TpeDeclarationStatut::EN_ATTENTE->value,
                'matiere_id' => $this->ligneMinimale('esbtp_planifications_academiques', ['enseignant_principal_id' => $user, 'is_active' => 1, 'matiere_id' => 987654]) ? 987654 : 0,
            ]),
            default => $this->ligneMinimale($table),
        };
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function listes(): array
    {
        // route, table interrogee, departage attendu dans l'ORDER BY
        return [
            'enseignants' => ['esbtp.enseignants.index', 'esbtp_teachers', '`id` asc'],
            'examens' => ['esbtp.examens.index', 'esbtp_examens_planifies', '`id` asc'],
            'seances' => ['esbtp.seances-cours.index', 'esbtp_seance_cours', '`esbtp_seance_cours`.`id` asc'],
            'annonces' => ['esbtp.annonces.index', 'esbtp_annonces', '`id` desc'],
            'presences' => ['esbtp.attendances.index', 'esbtp_attendances', '`esbtp_attendances`.`id` desc'],
            'rapports de cours' => ['esbtp.rapports-cours.index', 'esbtp_session_reports', '`id` desc'],
            'declarations TPE' => ['esbtp.tpe-validation.index', 'esbtp_tpe_declarations', '`id` desc'],
        ];
    }

    /**
     * @dataProvider listes
     */
    public function test_la_suite_repond_en_lignes_seules_et_departagees(string $route, string $table, string $departage): void
    {
        $this->peupler($table);
        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = $q->sql; });

        // Page 2 d'une liste d'une ligne : la requete ordonnee s'execute, sans
        // rien a rendre.
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonStructure(['success', 'rows_html', 'pagination' => ['current_page', 'next_page', 'has_more', 'affiches', 'par_page']]);

        $tri = collect($sql)->first(fn ($s) => str_contains($s, 'from `'.$table.'`') && str_contains($s, 'order by'));
        $this->assertNotNull($tri, 'La liste interroge '.$table.' avec un tri.');
        $this->assertStringContainsString($departage, $tri, 'Le tri se departage par identifiant.');
    }

    /**
     * @dataProvider listes
     */
    public function test_la_page_rend_le_bas_de_liste_sans_pagination(string $route): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertDontSee('class="pagination', false);
    }

    public function test_annonces_la_grille_du_telephone_recoit_des_cartes(): void
    {
        $annonce = \App\Models\ESBTPAnnonce::create([
            'titre' => 'Rentrée', 'contenu' => 'Texte', 'type' => 'general', 'date_publication' => now(),
            'priorite' => 0, 'is_published' => true, 'created_by' => auth()->id(),
        ]);

        $grille = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.annonces.index', ['page' => 1, 'mode' => 'rows', 'vue' => 'grille']))
            ->assertOk()->json('rows_html');
        $tableau = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.annonces.index', ['page' => 1, 'mode' => 'rows']))
            ->assertOk()->json('rows_html');

        $this->assertStringContainsString('annonce-card', $grille);
        $this->assertStringContainsString('data-li-cle="'.$annonce->id.'"', $grille);
        $this->assertStringStartsWith('<tr', trim(preg_replace('/\{\{--.*?--\}\}/s', '', $tableau)));
    }
}
