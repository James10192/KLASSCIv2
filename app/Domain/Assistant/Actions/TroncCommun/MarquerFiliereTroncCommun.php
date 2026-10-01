<?php

namespace App\Domain\Assistant\Actions\TroncCommun;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\BtsTroncCommun\ConfigurationTroncCommun;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscriptionPhase;
use Illuminate\Support\Facades\DB;

/**
 * Propose de marquer une filière BTS comme tronc commun (ou de retirer la
 * marque), avec le nombre de semestres communs. Écrit par
 * ConfigurationTroncCommun, le chemin de la CLI ; droit de l'écran de
 * modification d'une filière (filieres.edit), qui porte la même case.
 */
class MarquerFiliereTroncCommun extends ActionAgent
{
    public function cle(): string
    {
        return 'tronc_commun_filiere';
    }

    public function description(): string
    {
        return "PROPOSE de marquer une filière BTS comme tronc commun (tronc_commun=true) ou de retirer la marque (false), et le nombre de semestres communs (1 à 6). `filiere` = code ou identifiant. "
            . 'Demande les deux si la personne ne les donne pas. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filiere' => ['type' => 'string', 'description' => 'Code ou identifiant de la filière.'],
                'tronc_commun' => ['type' => 'boolean'],
                'semestres' => ['type' => 'integer', 'description' => 'Semestres communs avant la spécialité (1 à 6).'],
            ],
            'required' => ['filiere'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Filière tronc commun';
        $designation = trim((string) ($args['filiere'] ?? ''));
        $resolue = $designation === '' ? null : app(ResolutionDeMatiere::class)->filiere($designation);
        if (! $resolue || $resolue['statut'] !== 'ok') {
            return new Proposition(titre: $titre, resume: '', manques: [$designation === ''
                ? 'Quelle filière ?'
                : "Filière « {$designation} » ".(($resolue['statut'] ?? '') === 'ambigu' ? 'ambiguë : donne son code.' : 'introuvable.')]);
        }
        /** @var ESBTPFiliere $filiere */
        $filiere = $resolue['filiere'];

        $manques = [];
        if (! array_key_exists('tronc_commun', $args)) {
            $manques[] = 'Faut-il marquer la filière comme tronc commun, ou retirer la marque ?';
        }
        $troncCommun = (bool) ($args['tronc_commun'] ?? false);
        $semestres = isset($args['semestres']) ? (int) $args['semestres'] : null;
        if ($troncCommun && $semestres === null && ! $filiere->is_tronc_commun) {
            $manques[] = 'Combien de semestres communs avant la spécialité (1 à 6) ?';
        }
        if ($semestres !== null && ($semestres < 1 || $semestres > 6)) {
            $manques[] = 'Le nombre de semestres communs va de 1 à 6.';
        }
        $semestresApres = $semestres ?? ((int) $filiere->semestres_tronc_commun ?: 1);
        if ($manques === [] && (bool) $filiere->is_tronc_commun === $troncCommun && (int) $filiere->semestres_tronc_commun === $semestresApres) {
            $manques[] = "{$filiere->name} est déjà dans cet état : rien à changer.";
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        // Une option peut être un tronc commun secondaire : prévenu, pas refusé.
        $avertissements = app(ConfigurationTroncCommun::class)->avertissementsMarquage($filiere, $troncCommun);
        if (! $troncCommun && $filiere->is_tronc_commun && $filiere->parent_id === null) {
            $classes = ESBTPClasse::where('filiere_id', $filiere->id)->pluck('id');
            $phases = ESBTPInscriptionPhase::whereIn('classe_id', $classes)->where('type_phase', ESBTPInscriptionPhase::TYPE_TRONC_COMMUN)->where('is_active', true)->count();
            $avertissements[] = "Ses classes ne proposeront plus d'orientation vers une spécialité.".($phases > 0 ? " {$phases} étudiant(s) y sont encore en phase de tronc commun." : '');
        }

        return new Proposition(
            titre: $titre,
            resume: $troncCommun ? "{$filiere->name} devient un tronc commun de {$semestresApres} semestre(s)." : "{$filiere->name} n'est plus un tronc commun.",
            tableau: [
                'colonnes' => ['Filière', 'Code', 'Tronc commun', 'Semestres communs'],
                'lignes' => [[(string) $filiere->name, (string) $filiere->code,
                    ($filiere->is_tronc_commun ? 'oui' : 'non').' → '.($troncCommun ? 'oui' : 'non'),
                    ((int) $filiere->semestres_tronc_commun ?: 1).' → '.$semestresApres]],
            ],
            avertissements: $avertissements,
            donnees: ['filiere_id' => (int) $filiere->id, 'tronc_commun' => $troncCommun, 'semestres' => $semestresApres],
            etat: $this->etat($filiere),
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $filiere = DB::transaction(function () use ($d, $proposition) {
            $filiere = ESBTPFiliere::lockForUpdate()->findOrFail($d['filiere_id']);
            if ($this->etat($filiere) !== $proposition->etat) {
                throw new PropositionPerimee('Cette filière a changé depuis la proposition.');
            }

            return app(ConfigurationTroncCommun::class)->marquerFiliere($filiere, $d['tronc_commun'], $d['semestres']);
        });

        return [
            'message' => $d['tronc_commun'] ? "{$filiere->name} est désormais un tronc commun." : "{$filiere->name} n'est plus un tronc commun.",
            'lien' => route('esbtp.filieres.show', $filiere->id, false),
            'model_type' => ESBTPFiliere::class,
            'model_id' => (int) $filiere->id,
            'details' => $d,
        ];
    }

    private function etat(ESBTPFiliere $filiere): array
    {
        return ['tc' => (bool) $filiere->is_tronc_commun, 'semestres' => (int) $filiere->semestres_tronc_commun, 'parent' => $filiere->parent_id ? (int) $filiere->parent_id : null];
    }
}
