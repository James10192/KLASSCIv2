<?php

namespace App\Domain\Assistant\Actions\TroncCommun;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\BtsTroncCommun\BtsOrientationPolicySupport;
use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\DB;

/**
 * Propose d'orienter un étudiant de tronc commun BTS vers sa classe de
 * spécialité. Écrit par BtsOrientationService::orient(), le chemin de l'écran
 * « Spécialisation » et de la CLI ; droit de cet écran
 * (inscriptions.specialisation.manage).
 *
 * La préparation n'écrit rien : la cible est contrôlée par
 * BtsOrientationPolicySupport::cibleAdmissible(), qui prend la même décision
 * que l'orientation sans créer la sortie par hiérarchie de filières.
 * Corriger une spécialité déjà posée n'est PAS ici : l'écran demande un motif.
 */
class OrienterInscription extends ActionAgent
{
    public function cle(): string
    {
        return 'orientation_bts';
    }

    public function description(): string
    {
        return "PROPOSE d'orienter un étudiant de tronc commun BTS vers une classe de spécialité. `inscription_id` (search_inscriptions) et `classe` = code ou identifiant de la classe cible nommée par la personne. "
            . 'Ne choisis jamais la spécialité à sa place. Une spécialité déjà posée se corrige sur l\'écran. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'inscription_id' => ['type' => 'integer'],
                'classe' => ['type' => 'string', 'description' => 'Classe de spécialité (code ou identifiant).'],
            ],
            'required' => ['inscription_id', 'classe'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Orientation BTS';
        $inscription = ESBTPInscription::with(['etudiant', 'filiere', 'classe.filiere', 'classe.orientationTargets', 'phases'])
            ->find((int) ($args['inscription_id'] ?? 0));
        $designation = trim((string) ($args['classe'] ?? ''));
        if (! $inscription) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quel étudiant ? Retrouve son inscription avec search_inscriptions.']);
        }
        if ($designation === '') {
            return new Proposition(titre: $titre, resume: '', manques: ['Vers quelle classe de spécialité ?']);
        }
        $cible = ESBTPClasse::with('filiere')
            ->where(fn ($q) => $q->where('id', ctype_digit($designation) ? (int) $designation : 0)->orWhereRaw('UPPER(code) = ?', [mb_strtoupper($designation)]))
            ->first();
        if (! $cible) {
            return new Proposition(titre: $titre, resume: '', manques: ["Classe « {$designation} » introuvable : vérifie avec search_classes."]);
        }

        $politique = app(BtsOrientationPolicySupport::class);
        $nom = trim(($inscription->etudiant?->nom ?? '').' '.($inscription->etudiant?->prenoms ?? '')) ?: 'Inscription #'.$inscription->id;
        if (! $politique->canOrient($inscription)) {
            return new Proposition(titre: $titre, resume: '', manques: [$inscription->phases->contains(fn ($p) => $p->type_phase === 'specialisation' && $p->is_active)
                ? "{$nom} a déjà une spécialité : sa correction se fait sur l'écran de spécialisation, avec un motif."
                : "{$nom} n'est pas en tronc commun : il n'y a pas d'orientation à faire."]);
        }
        if (! $politique->cibleAdmissible($inscription, $cible)) {
            return new Proposition(titre: $titre, resume: '', manques: ["{$cible->name} n'est pas une sortie ouverte de {$inscription->classe?->name} (même niveau, classe active, sortie configurée)."]);
        }

        $avertissements = [];
        if ($cible->places_disponibles <= 0) {
            $avertissements[] = "{$cible->name} est pleine ({$cible->places_totales} places).";
        }

        return new Proposition(
            titre: $titre,
            resume: "{$nom} : {$inscription->classe?->name} → {$cible->name}.",
            tableau: [
                'colonnes' => ['Étudiant', 'Matricule', 'Classe actuelle', 'Spécialité'],
                'lignes' => [[$nom, (string) ($inscription->etudiant?->matricule ?? '—'), (string) ($inscription->classe?->name ?? '—'), (string) $cible->name]],
            ],
            avertissements: $avertissements,
            donnees: ['inscription_id' => (int) $inscription->id, 'classe_id' => (int) $cible->id],
            etat: $this->etat($inscription),
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        try {
            $inscription = DB::transaction(function () use ($d, $proposition) {
                $inscription = ESBTPInscription::with('phases')->lockForUpdate()->findOrFail($d['inscription_id']);
                if ($this->etat($inscription) !== $proposition->etat) {
                    throw new PropositionPerimee('Le parcours de cet étudiant a changé depuis la proposition.');
                }

                return app(BtsOrientationService::class)->orient($inscription, $d['classe_id']);
            });
        } catch (\InvalidArgumentException $e) {
            throw new PropositionPerimee($e->getMessage());
        }

        return [
            'message' => 'Orientation enregistrée vers '.($inscription->classe?->name ?? 'la classe choisie').'.',
            'lien' => route('esbtp.inscriptions.show', $inscription->id, false),
            'model_type' => ESBTPInscription::class,
            'model_id' => (int) $inscription->id,
            'details' => $d,
        ];
    }

    private function etat(ESBTPInscription $inscription): array
    {
        return [
            'classe_id' => (int) $inscription->classe_id,
            'phases' => $inscription->phases->sortBy('id')->map(fn ($p) => [(int) $p->id, (string) $p->type_phase, (bool) $p->is_active])->values()->all(),
        ];
    }
}
