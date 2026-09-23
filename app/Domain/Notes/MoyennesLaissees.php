<?php

namespace App\Domain\Notes;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Ce que l'ecran d'une evaluation dit des moyennes qu'un deplacement a
 * laissees sans rien a moyenner (voir
 * {@see PerimetreDeRecalcul::recalculerUnCouple()}).
 *
 * Rendu par `evaluations/show.blade.php`, pas par le bandeau global du layout,
 * qui echappe tout et ne peut donc porter aucun lien.
 *
 * Deux choses que le premier texte faisait mal :
 *
 * - il parlait toujours de « l'ancienne matiere ». Or sur cet ecran la classe
 *   et la matiere sont verrouillees des qu'il y a des notes (sauf
 *   `evaluations.edit_locked`) : le cas courant est un changement de PERIODE.
 *   Le texte nomme ce qui a vraiment bouge ;
 * - il renvoyait vers « Modifier les moyennes » sans dire de qui. Il y a deux
 *   remedes, selon ce qui reste :
 *   - plus AUCUNE note : le pre-controle de la generation des bulletins les
 *     liste par matiere, avec une suppression douce et tracee — un lien
 *     pre-rempli par (classe, periode) ;
 *   - des absences seulement : ce pre-controle ne les voit pas. L'ecran
 *     « Modifier les moyennes » est PAR ELEVE : un lien pre-rempli par eleve.
 */
final class MoyennesLaissees
{
    /**
     * @param  array{orphelins:array<int, array<string,mixed>>, echecs:int}  $recalcul  le bilan de {@see RecalculApresDeplacement}
     * @param  array{classe_id:int, matiere_id:int, periode:string}  $avant
     * @return array{total:int, echecs:int, ce_qui_a_bouge:string, nettoyages:array<int,array<string,mixed>>, eleves:array<int,array<string,mixed>>}
     */
    public static function pourLEcran(array $recalcul, ESBTPEvaluation $evaluation, array $avant): array
    {
        return [
            'total' => count($recalcul['orphelins']),
            // Un recalcul en echec laisse la moyenne d'avant en place. Le
            // journal le dit ; l'ecran annoncait pourtant « mise a jour avec
            // succes ». Qui a fait le geste doit le savoir.
            'echecs' => (int) $recalcul['echecs'],
            'ce_qui_a_bouge' => self::ceQuiABouge($evaluation, $avant),
        ] + self::aReprendre($recalcul['orphelins']);
    }

    /**
     * Les liens des remèdes, prêts à suivre, pour un écran qui ne rend pas la
     * fiche de l'évaluation : la liste des évaluations les affiche sous la
     * phrase d'{@see enUnePhrase()}. Mêmes cibles et mêmes droits que la fiche :
     * un lien n'est rendu qu'à qui peut ouvrir l'écran visé.
     *
     * @param  array{orphelins:array<int, array<string,mixed>>}  $recalcul
     * @return array<int, array{libelle:string, url:string}>
     */
    public static function liens(array $recalcul, ?User $utilisateur): array
    {
        if ($recalcul['orphelins'] === []) {
            return [];
        }

        $droits = self::droits($utilisateur);
        $aReprendre = self::aReprendre($recalcul['orphelins']);
        $liens = [];

        if ($droits['verifier']) {
            foreach ($aReprendre['nettoyages'] as $n) {
                $liens[] = [
                    'libelle' => 'Vérifier '.$n['libelle'],
                    'url' => route('esbtp.bulletins.select', [
                        'classe_id' => $n['classe_id'],
                        'periode' => $n['periode'],
                        'annee_universitaire_id' => $n['annee_universitaire_id'],
                    ]),
                ];
            }
        }

        if ($droits['reprendre']) {
            foreach ($aReprendre['eleves'] as $e) {
                $liens[] = [
                    'libelle' => $e['libelle'],
                    'url' => route('esbtp.bulletins.moyennes-preview', [
                        'etudiant_id' => $e['etudiant_id'],
                        'classe_id' => $e['classe_id'],
                        'periode' => $e['periode'],
                        'annee_universitaire_id' => $e['annee_universitaire_id'],
                    ]),
                ];
            }
        }

        return $liens;
    }

    /**
     * Les deux remèdes, selon ce qui reste : plus aucune note (le pré-contrôle
     * des bulletins, par classe et période) ou des absences seulement (« Modifier
     * les moyennes », par élève).
     *
     * @param  array<int, array<string,mixed>>  $laissees
     * @return array{nettoyages:array<int,array<string,mixed>>, eleves:array<int,array<string,mixed>>}
     */
    private static function aReprendre(array $laissees): array
    {
        $classes = ESBTPClasse::whereIn('id', array_column($laissees, 'classe_id'))->pluck('name', 'id');

        $sansNote = array_filter($laissees, fn (array $l) => $l['reste'] === 'aucune_note');
        $absences = array_filter($laissees, fn (array $l) => $l['reste'] !== 'aucune_note');

        return [
            'nettoyages' => collect($sansNote)
                ->map(fn (array $l) => self::coordonnee($l))
                ->unique(fn (array $c) => implode('|', $c))
                ->map(fn (array $c) => $c + [
                    'libelle' => ($classes[$c['classe_id']] ?? '#'.$c['classe_id']).', '.self::libellePeriode($c['periode']),
                ])
                ->values()
                ->all(),
            'eleves' => self::elevesAReprendre($absences, $classes->all()),
        ];
    }

    /**
     * La même information, en une phrase, pour les écrans qui ne sont pas la
     * fiche de l'évaluation : la liste des évaluations (réponse JSON d'une
     * annulation) et l'emploi du temps (modification d'une séance de devoir).
     * La phrase ne porte pas de lien : le bandeau global de l'emploi du temps
     * échappe tout. La liste des évaluations ajoute ceux de {@see liens()}.
     *
     * @param  array{orphelins:array<int, array<string,mixed>>, echecs:int}  $recalcul
     * @param  string  $pourquoi  ce qui a vidé ces moyennes, en fin de proposition :
     *                            « ne reposaient que sur cette évaluation »
     */
    public static function enUnePhrase(array $recalcul, string $pourquoi): ?string
    {
        $phrases = [];

        if ($recalcul['orphelins'] !== []) {
            $eleves = count(array_unique(array_column($recalcul['orphelins'], 'etudiant_id')));
            $phrases[] = count($recalcul['orphelins']).' moyenne(s) enregistrée(s) '.$pourquoi.' ('.$eleves.' élève(s)) : '
                .'ni recalculées ni supprimées, vérifiez-les dans les résultats avant de régénérer les bulletins.';
        }

        if ($recalcul['echecs'] > 0) {
            $phrases[] = $recalcul['echecs'].' recalcul(s) en échec : ces moyennes gardent leur valeur d\'avant.';
        }

        return $phrases === [] ? null : implode(' ', $phrases);
    }

    /**
     * La phrase d'{@see enUnePhrase()} après une annulation ou une
     * réactivation : ce qui a laissé la moyenne dépend du sens du geste.
     *
     * @param  array{orphelins:array<int, array<string,mixed>>, echecs:int}  $recalcul
     */
    public static function apresChangementDeStatut(array $recalcul, ESBTPEvaluation $evaluation): ?string
    {
        return self::enUnePhrase($recalcul, $evaluation->status === ESBTPEvaluation::STATUS_CANCELLED
            ? 'ne reposaient que sur cette évaluation, désormais annulée'
            : 'n\'ont, même avec cette évaluation réactivée, que des absences à moyenner');
    }

    /**
     * @param  array<int, array<string,mixed>>  $absences
     * @param  array<int,string>  $classes
     * @return array<int, array<string,mixed>>
     */
    private static function elevesAReprendre(array $absences, array $classes): array
    {
        if ($absences === []) {
            return [];
        }

        $etudiants = ESBTPEtudiant::whereIn('id', array_column($absences, 'etudiant_id'))
            ->get(['id', 'nom', 'prenoms'])
            ->keyBy('id');
        $matieres = ESBTPMatiere::whereIn('id', array_column($absences, 'matiere_id'))->pluck('name', 'id');

        return array_values(array_map(fn (array $l) => self::coordonnee($l) + [
            'etudiant_id' => (int) $l['etudiant_id'],
            'libelle' => trim(($etudiants[$l['etudiant_id']]->nom ?? '').' '.($etudiants[$l['etudiant_id']]->prenoms ?? ''))
                .' — '.($matieres[$l['matiere_id']] ?? '#'.$l['matiere_id'])
                .' ('.($classes[$l['classe_id']] ?? '#'.$l['classe_id']).', '.self::libellePeriode($l['periode']).')',
        ], $absences));
    }

    /**
     * @param  array<string,mixed>  $laissee
     * @return array{classe_id:int, periode:string, annee_universitaire_id:int}
     */
    private static function coordonnee(array $laissee): array
    {
        return [
            'classe_id' => (int) $laissee['classe_id'],
            'periode' => (string) $laissee['periode'],
            'annee_universitaire_id' => (int) $laissee['annee_universitaire_id'],
        ];
    }

    /** @param array{classe_id:int, matiere_id:int, periode:string} $avant */
    private static function ceQuiABouge(ESBTPEvaluation $evaluation, array $avant): string
    {
        $bouge = array_values(array_filter([
            $evaluation->classe_id != $avant['classe_id'] ? 'la classe' : null,
            $evaluation->matiere_id != $avant['matiere_id'] ? 'la matière' : null,
            $evaluation->periode != $avant['periode'] ? 'la période' : null,
        ]));

        return match (count($bouge)) {
            0 => 'l\'évaluation',
            1 => $bouge[0],
            default => implode(', ', array_slice($bouge, 0, -1)).' et '.end($bouge),
        };
    }

    /**
     * Ce que la personne peut ouvrir depuis le bandeau. Un lien visible doit
     * mener quelque part : chaque droit est celui que l'ecran cible verifie
     * VRAIMENT — la garde de sa route (relue sur la route elle-meme, pas
     * recopiee ici) plus celle du controleur ou du bouton.
     *
     * @return array{verifier:bool, retirer:bool, reprendre:bool}
     */
    public static function droits(?User $utilisateur): array
    {
        $verifier = self::routeOuverte('esbtp.bulletins.select', $utilisateur);

        return [
            'verifier' => $verifier,
            // Le bouton de suppression du pre-controle est garde par
            // `bulletins.delete` (bulletins/select.blade.php).
            'retirer' => $verifier && $utilisateur->can('bulletins.delete'),
            // `ESBTPResultatController::previewMoyennes()` exige en plus
            // `resultats.export`.
            'reprendre' => self::routeOuverte('esbtp.bulletins.moyennes-preview', $utilisateur)
                && $utilisateur->can('resultats.export'),
        ];
    }

    /** Les middlewares `permission:a|b` de la route nommee, rejoues pour cet utilisateur. */
    private static function routeOuverte(string $nom, ?User $utilisateur): bool
    {
        $route = Route::getRoutes()->getByName($nom);

        if (! $route || ! $utilisateur) {
            return false;
        }

        // `middleware()` et non `gatherMiddleware()` : ce dernier instancie le
        // controleur pour lire ses middlewares propres, a chaque affichage du
        // bandeau. Les gardes lues ici sont posees sur la route.
        foreach ($route->middleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                if (! $utilisateur->canAny(explode('|', substr($middleware, strlen('permission:'))))) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function libellePeriode(string $periode): string
    {
        $numero = ESBTPEvaluation::numeroDeSemestre($periode)
            ?? (preg_match('/^semestre(\d+)$/', $periode, $m) ? (int) $m[1] : null);

        return $numero !== null ? 'Semestre '.$numero : ucfirst($periode);
    }
}
