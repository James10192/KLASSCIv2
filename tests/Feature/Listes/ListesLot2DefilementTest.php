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
            'audits' => $this->ligneMinimale($table, ['auditable_type' => 'App\\Models\\ESBTPPaiement', 'event' => 'updated', 'created_at' => now(), 'updated_at' => now()]),
            'esbtp_lmd_jurys', 'esbtp_lmd_sessions' => $this->ligneMinimale($table, ['annee_universitaire_id' => $this->annee->id]),
            'esbtp_inscriptions' => $this->ligneMinimale($table, ['status' => 'en_attente', 'is_sous_reserve' => 1, 'annee_universitaire_id' => $this->annee->id]),
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
            'audit comptable' => ['esbtp.audit.comptabilite', 'audits', '`id` desc'],
            'activite des utilisateurs' => ['esbtp.audit.user-activity', 'audits', '`id` desc'],
            'jurys LMD' => ['esbtp.lmd.jurys.index', 'esbtp_lmd_jurys', '`id` desc'],
            'sessions de rattrapage' => ['esbtp.lmd.rattrapage.index', 'esbtp_lmd_sessions', '`id` desc'],
            'bulletins LMD' => ['esbtp.lmd.bulletins.index', 'esbtp_lmd_bulletins', '`id` desc'],
            'inscriptions a valider' => ['esbtp.inscriptions.administration', 'esbtp_inscriptions', '`esbtp_inscriptions`.`id` desc'],
            'inscriptions sous reserve' => ['esbtp.inscriptions.sous-reserve', 'esbtp_inscriptions', '`esbtp_inscriptions`.`id` desc'],
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

    public function test_activite_une_tranche_ouverte_sur_un_jour_deja_affiche_porte_sa_cle(): void
    {
        // 51 actions du meme jour : la tranche 2 (50 par tranche) commence au
        // milieu de ce jour. Elle repete l'en-tete, avec la cle du jour, pour que
        // le defilement l'ecarte au lieu de l'afficher deux fois.
        for ($i = 0; $i < 51; $i++) {
            $this->ligneMinimale('audits', ['auditable_type' => 'App\\Models\\ESBTPPaiement', 'event' => 'updated', 'created_at' => now()->startOfSecond(), 'updated_at' => now()]);
        }

        // La mise en place du test ecrit elle aussi des actions, toutes du jour.
        $total = DB::table('audits')->count();

        $html = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.audit.user-activity', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.affiches', min($total, 100))
            ->json('rows_html');

        $this->assertSame(1, substr_count($html, 'data-li-cle="jour-'.now()->format('Y-m-d').'"'));
        $this->assertSame(min($total, 100) - 50, substr_count($html, 'au-timeline-item '));
    }

    public function test_suivi_des_pieces_la_suite_repond_en_lignes_seules(): void
    {
        $this->ligneMinimale('esbtp_pieces_dossier', ['is_active' => 1]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.pieces-dossier.suivi', ['page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonStructure(['success', 'rows_html', 'pagination' => ['next_page', 'has_more', 'affiches']])
            ->assertJsonMissingPath('kpis');
    }

    public function test_bulletins_lmd_les_indicateurs_portent_sur_tous_les_bulletins(): void
    {
        // 25 bulletins : les 20 plus anciens publies, les 5 plus recents non.
        // La premiere tranche (20) n'en montre que 15 publies ; l'indicateur,
        // lui, doit en compter 20.
        for ($i = 0; $i < 25; $i++) {
            $this->ligneMinimale('esbtp_lmd_bulletins', [
                'etudiant_id' => 900000 + $i,
                'is_published' => $i < 20 ? 1 : 0,
                'moyenne_generale' => 10,
                'created_at' => now()->subMinutes(100 - $i),
            ]);
        }

        $kpis = $this->get(route('esbtp.lmd.bulletins.index'))->assertOk()->viewData('kpis');

        $this->assertSame(20, $kpis['publies']);
        $this->assertEquals(10, $kpis['moyenne']);
    }
}
