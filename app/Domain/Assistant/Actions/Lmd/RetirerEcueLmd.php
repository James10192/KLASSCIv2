<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\SortieDuLmd;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Retirer un élément constitutif (ECUE) d'une maquette LMD :
 * SortieDuLmd::retirer(), le même chemin que la croix de /esbtp/lmd/ue.
 *
 * Quand c'est la dernière maquette qui le porte, l'école choisit ce qu'il
 * devient (supprimer, archiver dans le LMD, en faire une matière BTS). Nanan
 * pose la question avec les mêmes options que l'écran, jamais de choix par
 * défaut : le défaut historique versait l'élément dans les listes BTS (ESBTP
 * Abidjan, octobre 2026).
 */
class RetirerEcueLmd extends ActionAgent
{
    use DesigneLaMaquette;

    public function __construct(private SortieDuLmd $sortie, private CompositionUe $composition)
    {
    }

    public function cle(): string
    {
        return 'retrait_ecue_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation du retrait de l’élément…';
    }

    public function description(): string
    {
        return "PROPOSE de retirer un élément constitutif (ECUE) d'une UE LMD, dans la composition commune ou dans la maquette d'un parcours. "
            . "Si c'est sa dernière maquette, demande ce qu'il devient : supprimer (jamais servi), archiver (a servi) ou catalogue_bts (matière BTS rattachée par erreur). "
            . "Ne choisis jamais ce devenir à la place de la personne. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ue' => ['type' => 'string', 'description' => "Code imprimé ou identifiant de l'UE."],
                'element' => ['type' => 'string', 'description' => "Code, intitulé exact ou identifiant de l'élément à retirer."],
                'parcours' => ['type' => 'string', 'description' => 'Code du parcours si l\'élément est réservé à sa maquette ; vide pour la composition commune.'],
                'devenir' => ['type' => 'string', 'enum' => [SortieDuLmd::SUPPRIMER, SortieDuLmd::ARCHIVER, SortieDuLmd::CATALOGUE_BTS],
                    'description' => "Ce que devient l'élément s'il ne figure dans aucune autre maquette, tel que choisi par la personne."],
            ],
            'required' => ['ue', 'element'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Retirer un élément de la maquette';
        [$ue, $manque] = $this->unite((string) ($args['ue'] ?? ''));
        if (! $ue) {
            return $this->manque($titre, $manque);
        }
        [$ecue, $manque] = $this->elementDeLUnite($ue, (string) ($args['element'] ?? ''));
        if (! $ecue) {
            return $this->manque($titre, $manque);
        }
        $parcours = null;
        if (($code = trim((string) ($args['parcours'] ?? ''))) !== '') {
            $parcours = ESBTPLMDParcours::whereRaw('UPPER(code) = ?', [mb_strtoupper($code)])->first();
            if (! $parcours) {
                return $this->manque($titre, "Parcours inconnu : {$code}.");
            }
        }
        [$portee, $manque] = $this->porteeDeLElement($ue, $ecue, $parcours);
        if ($manque) {
            return $this->manque($titre, $manque);
        }
        // Un parcours nommé, un élément commun : le retirer l'enlèverait aussi
        // des autres parcours de l'UE. L'écran le refuse ; Nanan aussi.
        if ($parcours && $portee === CompositionUe::COMMUN && ($autres = $this->autresParcours($ue, (int) $parcours->id)) !== []) {
            return $this->manque($titre, sprintf(
                '« %s » est commun à tous les parcours de l\'UE %s (aussi %s) : il ne se retire pas de la seule maquette %s. '
                . 'Le retirer pour tous (sans nommer de parcours), ou le garder ?',
                $ecue->name, $ue->code_affiche, implode(', ', $autres), $parcours->code
            ));
        }

        $devenir = $args['devenir'] ?? null;
        $sortirait = $this->sortie->sortirait($ue, $ecue, $portee);
        if ($sortirait) {
            $question = $this->sortie->question($ecue);
            $possibles = array_column(array_filter($question['options'], fn ($o) => $o['possible']), 'valeur');
            if (! in_array($devenir, $possibles, true)) {
                return $this->manque($titre, $question['message'] . ' '
                    . collect($question['options'])->map(fn ($o) => "{$o['valeur']} : {$o['aide']}")->implode(' — '));
            }
        }

        $maquette = $portee === CompositionUe::COMMUN ? 'composition commune' : 'maquette ' . ESBTPLMDParcours::find($portee)?->code;
        $apres = ! $sortirait ? 'reste dans les autres maquettes' : match ($devenir) {
            SortieDuLmd::SUPPRIMER => 'supprimé',
            SortieDuLmd::ARCHIVER => 'archivé dans le LMD',
            default => 'devient une matière BTS',
        };

        return new Proposition(
            titre: $titre,
            resume: sprintf('Retirer « %s » (%s) de l’UE %s, %s : il %s.', $ecue->name, $ecue->code_affiche ?? $ecue->code, $ue->code_affiche ?? $ue->code, $maquette, $apres),
            tableau: [
                'colonnes' => ['UE', 'Élément', 'Maquette', 'Ensuite'],
                'lignes' => [[(string) ($ue->code_affiche ?? $ue->code), (string) $ecue->name, $maquette, $apres]],
            ],
            avertissements: $devenir === SortieDuLmd::CATALOGUE_BTS
                ? ['Il apparaîtra dans les notes, évaluations et bulletins BTS.'] : [],
            donnees: ['ue_id' => (int) $ue->id, 'matiere_id' => (int) $ecue->id, 'portee' => $portee, 'devenir' => $sortirait ? $devenir : null],
            etat: $this->etat($ecue),
            risque: $sortirait ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $ue = ESBTPUniteEnseignement::find((int) $d['ue_id']);
        $ecue = ESBTPMatiere::find((int) $d['matiere_id']);
        if (! $ue || ! $ecue || $this->etat($ecue) !== $proposition->etat) {
            throw new PropositionPerimee('Cet élément ou ses maquettes ont changé depuis la proposition.');
        }

        try {
            $resultat = $this->sortie->retirer($ue, $ecue, (int) $d['portee'], $d['devenir']);
        } catch (ValidationException $e) {
            throw new PropositionPerimee(collect($e->errors())->flatten()->implode(' '));
        }
        if (is_array($resultat)) {
            throw new PropositionPerimee($resultat['refus']);
        }

        return [
            'message' => $resultat,
            'lien' => route('esbtp.lmd.ue.index', [], false),
            'model_type' => ESBTPUniteEnseignement::class,
            'model_id' => (int) $ue->id,
        ];
    }

    private function manque(string $titre, string $manque): Proposition
    {
        return new Proposition(titre: $titre, resume: '', manques: [$manque]);
    }

    /** Ses lignes de maquette, sa clé et son activité : tout ce que le retrait lit. */
    private function etat(ESBTPMatiere $ecue): array
    {
        return [
            'lignes' => DB::table('esbtp_ue_matiere')->where('matiere_id', $ecue->id)
                ->orderBy('unite_enseignement_id')->orderBy('parcours_id')
                ->get(['unite_enseignement_id', 'parcours_id'])
                ->map(fn ($l) => [(int) $l->unite_enseignement_id, (int) $l->parcours_id])->all(),
            'ue' => $ecue->unite_enseignement_id === null ? null : (int) $ecue->unite_enseignement_id,
            'actif' => (bool) $ecue->is_active,
        ];
    }
}
