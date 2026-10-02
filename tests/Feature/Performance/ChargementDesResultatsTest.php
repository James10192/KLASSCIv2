<?php

namespace Tests\Feature\Performance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Performance\Concerns\ConstruitUneClasseNotee;
use Tests\TestCase;

/**
 * `/esbtp/resultats/load-etudiants` alimente le tableau de `/esbtp/resultats`.
 * Mesure sur presentation (octobre 2026) : 1 a 5,6 s pour 26 eleves d'une
 * classe sans aucune note, 3,3 s pour la premiere page « toutes classes ».
 *
 * Le premier test fige la reponse telle que le code la rendait avant le
 * chantier de performance : moyennes, rangs, statuts, badges, KPI, sur les
 * trois periodes, avec et sans classe, avec et sans les inscriptions
 * inactives, page par page. La reference a ete capturee sur ce code-la
 * (`CAPTURER_REFERENCE_RESULTATS=1`), puis rejouee sans changement. Le second
 * borne le cout : une classe deux fois plus grande ne doit pas couter deux
 * fois plus de requetes.
 */
class ChargementDesResultatsTest extends TestCase
{
    use RefreshDatabase;
    use ConstruitUneClasseNotee;

    private const REFERENCE = __DIR__.'/fixtures/load-etudiants-reference.json';

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('superAdmin', 'web'));
        $this->actingAs($user);

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);
    }

    public function test_la_reponse_est_celle_d_avant_le_chantier(): void
    {
        [$notee, $sansNote, $repli] = $this->decor();

        $obtenu = [];
        foreach (['notee' => $notee->id, 'sans_note' => $sansNote->id, 'repli' => $repli->id, 'toutes' => ''] as $nomClasse => $classeId) {
            foreach (['annuel' => '', 's1' => '1', 's2' => '2'] as $nomPeriode => $semestre) {
                foreach ([1, 0] as $tous) {
                    $page = 1;
                    do {
                        $reponse = $this->getJson(route('esbtp.resultats.load-etudiants', [
                            'page' => $page,
                            'per_page' => 4,
                            'classe_id' => $classeId,
                            'semestre' => $semestre,
                            'annee_universitaire_id' => $this->annee->id,
                            'include_all_statuses' => $tous,
                        ]))->assertOk()->json();

                        $obtenu["{$nomClasse}|{$nomPeriode}|tous={$tous}|page={$page}"] = [
                            'total' => $reponse['total'],
                            'current_page' => $reponse['current_page'],
                            'has_more' => $reponse['has_more'],
                            'loaded_count' => $reponse['loaded_count'],
                            'kpis' => $reponse['kpis'],
                            'lignes' => $this->lignes($reponse['html']),
                        ];
                        $page++;
                    } while ($reponse['has_more'] && $page <= 6);
                }
            }
        }

        if (getenv('CAPTURER_REFERENCE_RESULTATS')) {
            @mkdir(dirname(self::REFERENCE), 0777, true);
            file_put_contents(self::REFERENCE, json_encode($obtenu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
            $this->markTestSkipped('Reference capturee.');
        }

        $attendu = json_decode((string) file_get_contents(self::REFERENCE), true);
        $this->assertNotEmpty($attendu, 'Reference absente.');
        foreach ($attendu as $cas => $valeur) {
            // Depuis le chantier, les KPI ne sont calcules qu'en page 1 : les pages
            // suivantes rendent la cle a null (l'ecran les ignore). La reference,
            // capturee avant, les portait a chaque page.
            if (! str_ends_with($cas, '|page=1')) {
                $valeur['kpis'] = null;
            }
            $this->assertSame($valeur, $obtenu[$cas] ?? null, "Reponse differente pour {$cas}.");
        }
        $this->assertSame(array_keys($attendu), array_keys($obtenu));
    }

    /**
     * Le nombre de requetes d'une page ne suit plus la taille de la classe
     * qu'au titre des absences : chaque eleve coutait 84 requetes en annuel
     * (moyenne calculee deux fois, snapshot et carte des classes eleve par
     * eleve, un bulletin cherche par eleve).
     */
    public function test_une_classe_plus_grande_ne_coute_pas_plus_de_requetes_par_eleve(): void
    {
        $classe = $this->classeNotee($this->annee, 4);
        $mesure = function (string $semestre) use ($classe): int {
            $this->commeUneNouvelleRequete();

            return $this->requetesDe(fn () => $this->getJson(route('esbtp.resultats.load-etudiants', [
                'page' => 1, 'per_page' => 50, 'classe_id' => $classe['classe']->id,
                'semestre' => $semestre, 'annee_universitaire_id' => $this->annee->id, 'include_all_statuses' => 1,
            ]))->assertOk());
        };

        $mesure(''); // rechauffe les caches de reglages, hors mesure
        $avant = ['annuel' => $mesure(''), 's1' => $mesure('1')];
        $this->classeNotee($this->annee, 8, $classe['classe']);
        $apres = ['annuel' => $mesure(''), 's1' => $mesure('1')];

        fwrite(STDERR, "\nRequetes load-etudiants 4 -> 12 eleves : ".json_encode(compact('avant', 'apres'))."\n");

        foreach ($avant as $periode => $requetes) {
            $this->assertLessThanOrEqual(
                $requetes + 8 * self::REQUETES_PAR_ELEVE_TOLEREES,
                $apres[$periode],
                "Huit eleves de plus coutent trop de requetes en {$periode}."
            );
        }
    }

    /**
     * Des homonymes a cheval sur une limite de page : chacun paraît une fois,
     * dans l'ordre de son identifiant.
     */
    public function test_les_homonymes_ne_sont_ni_repetes_ni_sautes_d_une_page_a_l_autre(): void
    {
        $classe = $this->classeBts();
        $attendus = [];
        foreach (range(1, 5) as $n) {
            $this->inscrire($this->eleve("HOMO{$n}", 'Kouassi', 'Jean'), $classe, $this->annee);
            $attendus[] = "HOMO{$n}";
        }

        $vus = [];
        foreach ([1, 2, 3] as $page) {
            $html = $this->getJson(route('esbtp.resultats.load-etudiants', [
                'page' => $page, 'per_page' => 2, 'classe_id' => $classe->id,
                'semestre' => '1', 'annee_universitaire_id' => $this->annee->id, 'include_all_statuses' => 1,
            ]))->assertOk()->json('html');
            preg_match_all('/HOMO\d/', $html, $trouves);
            array_push($vus, ...array_values(array_unique($trouves[0])));
        }

        $this->assertSame($attendus, $vus);
    }

    /**
     * Une classe n'appartient a aucune annee : sans annee demandee, l'ecran
     * s'ouvre sur l'annee courante, pas sur l'annee que porte encore la colonne
     * historique `esbtp_classes.annee_universitaire_id`.
     */
    public function test_une_classe_sans_annee_ouvre_l_annee_courante(): void
    {
        $ancienne = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-31', 'is_current' => false,
        ]);
        $classe = $this->classeBts();
        $classe->update(['annee_universitaire_id' => $ancienne->id]);

        $this->get(route('esbtp.resultats.index', ['classe_id' => $classe->id]))
            ->assertOk()
            ->assertViewHas('annee_universitaire_id', $this->annee->id);
    }

    /**
     * En production chaque requete repart d'un conteneur neuf. Ici le meme
     * conteneur sert toutes les requetes du test : le controleur reste
     * attache a sa route (avec la memoire de ses services) et les services
     * `scoped` gardent la cohorte lue avant l'arrivee des nouveaux eleves.
     */
    private function commeUneNouvelleRequete(): void
    {
        $this->app->forgetScopedInstances();
        $route = $this->app['router']->getRoutes()->getByName('esbtp.resultats.load-etudiants');
        $route->flushController();
    }

    /**
     * Ce qui reste lu par eleve : ses absences (saisie manuelle et seances),
     * deux requetes par semestre, dans `ESBTPAbsenceService`. Mesure : 5,3 par
     * eleve en annuel, 2,8 au semestre. Avant le chantier : 84 en annuel,
     * 20 au semestre.
     */
    private const REQUETES_PAR_ELEVE_TOLEREES = 6;

    /**
     * @return array{0: ESBTPClasse, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function decor(): array
    {
        $notee = $this->classeBts();
        $notee->update(['name' => '2BTS GC A']);
        $sansNote = $this->classeBts();
        $sansNote->update(['name' => '2BTS CG A']);

        $matieres = [ESBTPMatiere::factory()->create(['name' => 'Beton']), ESBTPMatiere::factory()->create(['name' => 'Topographie'])];
        $evaluations = [];
        foreach ($matieres as $i => $matiere) {
            foreach (['semestre1', 'semestre2'] as $periode) {
                $evaluations[$periode][$i] = ESBTPEvaluation::factory()->create([
                    'matiere_id' => $matiere->id, 'classe_id' => $notee->id,
                    'annee_universitaire_id' => $this->annee->id, 'periode' => $periode,
                    'coefficient' => $i + 1, 'bareme' => 20, 'status' => 'completed',
                ]);
            }
        }

        // Classe notee : notes des deux semestres, un eleve au S1 seulement,
        // un eleve sans note, une inscription non validee, une annulee.
        $eleves = [];
        foreach (range(1, 9) as $n) {
            $eleve = $this->eleve("NOTE{$n}", "Kone{$n}", "Awa{$n}");
            $statut = $n === 8 ? 'annulee' : 'active';
            $etape = $n === 7 ? 'prospect' : 'etudiant_cree';
            $this->inscrire($eleve, $notee, $this->annee)->update([
                'status' => $statut, 'workflow_step' => $etape, 'date_inscription' => '2025-09-1'.($n % 9),
            ]);
            $eleves[$n] = $eleve;
            if ($n === 6) {
                continue; // aucune note
            }
            foreach (['semestre1', 'semestre2'] as $periode) {
                if ($n === 5 && $periode === 'semestre2') {
                    continue; // S1 seulement
                }
                foreach ($evaluations[$periode] as $i => $evaluation) {
                    $this->noter($eleve, $evaluation, 5 + (($n * 3 + $i * 5 + ($periode === 'semestre2' ? 2 : 0)) % 14));
                }
            }
        }
        // Deux eleves ex aequo au semestre 1.
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $eleves[1]->id, 'classe_id' => $notee->id, 'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1', 'moyenne_generale' => 12.5, 'rang' => 1, 'mention' => 'Assez Bien',
        ]);

        // Classe sans note : une moyenne saisie a la main au S1 pour un eleve.
        foreach (range(1, 6) as $n) {
            $eleve = $this->eleve("VIDE{$n}", "Traore{$n}", "Ibrahim{$n}");
            $this->inscrire($eleve, $sansNote, $this->annee)->update(['date_inscription' => '2025-09-2'.$n]);
            if ($n === 2) {
                ESBTPResultat::create([
                    'etudiant_id' => $eleve->id, 'classe_id' => $sansNote->id, 'matiere_id' => $matieres[0]->id,
                    'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
                    'moyenne' => 11.75, 'coefficient' => 1,
                ]);
            }
        }

        // Les deux cas ou le calcul historique reste VISIBLE (voir
        // `MoyennesDeLaListe::besoinDuCalculHistorique()`). Sans eux, toute page
        // qui l'emprunte rendrait une sortie vide et le test ne prouverait rien.
        $repli = $this->classeBts();
        $repli->update(['name' => '1BTS REPLI']);

        // (a) Seule note du semestre posee sur une evaluation annulee : le snapshot
        // l'ecarte, l'ancien calcul des notes non. Moyenne et rang historiques au
        // semestre, classe choisie ou non.
        $annulee = $this->eleve('REPLI1', 'Zran1', 'Moussa');
        $this->inscrire($annulee, $repli, $this->annee)->update(['date_inscription' => '2025-09-25']);
        $evaluationAnnulee = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matieres[0]->id, 'classe_id' => $repli->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
            'coefficient' => 1, 'bareme' => 20, 'status' => 'cancelled',
        ]);
        $this->noter($annulee, $evaluationAnnulee, 13);

        // (b) Aucune note, mais un bulletin a moyenne enregistree : sans classe
        // choisie, en annuel, l'ancien calcul (par le bulletin) donne un RANG sans
        // moyenne affichee.
        $bulletinSeul = $this->eleve('REPLI2', 'Zran2', 'Fatou');
        $this->inscrire($bulletinSeul, $repli, $this->annee)->update(['date_inscription' => '2025-09-26']);
        ESBTPBulletin::factory()->create([
            'etudiant_id' => $bulletinSeul->id, 'classe_id' => $repli->id, 'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1', 'moyenne_generale' => 13.0, 'rang' => 1, 'mention' => 'Assez Bien',
        ]);

        // Un eleve oriente : semestre 1 en tronc commun, semestre 2 dans la classe
        // notee. Sa carte des classes passe par le prechargement de la cohorte.
        $troncCommun = $this->classeBts();
        $troncCommun->update(['name' => '1BTS TC']);
        $oriente = $this->eleve('ORIENT1', 'Yao', 'Serge');
        $inscription = $this->inscrire($oriente, $notee, $this->annee);
        $inscription->update(['date_inscription' => '2025-09-27']);
        foreach ([[$troncCommun, 'tronc_commun', 1, 1, false], [$notee, 'specialisation', 2, null, true]] as [$c, $type, $debut, $fin, $actif]) {
            ESBTPInscriptionPhase::create([
                'inscription_id' => $inscription->id, 'type_phase' => $type, 'classe_id' => $c->id,
                'filiere_id' => $c->filiere_id, 'semestre_debut' => $debut, 'semestre_fin' => $fin, 'is_active' => $actif,
            ]);
        }
        $evaluationTc = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matieres[0]->id, 'classe_id' => $troncCommun->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
            'coefficient' => 1, 'bareme' => 20, 'status' => 'completed',
        ]);
        $this->noter($oriente, $evaluationTc, 15);
        $this->noter($oriente, $evaluations['semestre2'][0], 9);

        return [$notee, $sansNote, $repli];
    }

    private function eleve(string $matricule, string $nom, string $prenoms): ESBTPEtudiant
    {
        return ESBTPEtudiant::factory()->create([
            'matricule' => $matricule, 'nom' => $nom, 'prenoms' => $prenoms,
            'email' => strtolower($matricule).'@exemple.test',
        ]);
    }

    private function noter(ESBTPEtudiant $eleve, ESBTPEvaluation $evaluation, float $note): void
    {
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id, 'etudiant_id' => $eleve->id,
            'matiere_id' => $evaluation->matiere_id, 'classe_id' => $evaluation->classe_id,
            'annee_universitaire' => $this->annee->name, 'note' => $note, 'valeur' => $note, 'is_absent' => false,
        ]);
    }

    /**
     * Le texte visible et les classes CSS de chaque ligne : ce que voit
     * l'utilisateur, sans les identifiants techniques qui changent d'une
     * course a l'autre.
     *
     * @return list<string>
     */
    private function lignes(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?><table>'.$html.'</table>');
        $lignes = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $morceaux = [];
            $parcourir = function (\DOMNode $noeud) use (&$parcourir, &$morceaux) {
                if ($noeud instanceof \DOMElement) {
                    foreach (['class', 'title'] as $attribut) {
                        if ($noeud->hasAttribute($attribut)) {
                            $morceaux[] = $attribut.'='.trim($noeud->getAttribute($attribut));
                        }
                    }
                } elseif ($noeud instanceof \DOMText && trim($noeud->textContent) !== '') {
                    $morceaux[] = preg_replace('/\s+/u', ' ', trim($noeud->textContent));
                }
                foreach ($noeud->childNodes as $enfant) {
                    $parcourir($enfant);
                }
            };
            $parcourir($tr);
            $lignes[] = implode(' | ', $morceaux);
        }

        return $lignes;
    }
}
