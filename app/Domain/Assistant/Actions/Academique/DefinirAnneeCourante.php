<?php

namespace App\Domain\Assistant\Actions\Academique;

use App\Domain\Academique\AnneesUniversitaires;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\DB;

/**
 * Propose de changer l'année universitaire en cours. Le geste le plus lourd du
 * lot : TOUS les écrans, pour TOUS les utilisateurs, basculent sur l'année
 * choisie à l'instant où la personne valide. Risque élevé, conséquences
 * écrites en clair, et la désignation doit être exacte (identifiant ou nom
 * complet, jamais « la prochaine »).
 *
 * Écrit par AnneesUniversitaires::definirCourante(), soit setAsCurrent() de
 * l'écran (avec le cache vidé), partagé avec la CLI.
 */
class DefinirAnneeCourante extends ActionAgent
{
    public function cle(): string
    {
        return 'annee_courante';
    }

    public function description(): string
    {
        return "PROPOSE de désigner l'année universitaire EN COURS pour tout l'établissement. `annee` = son identifiant ou son nom exact (ex. 2026-2027), donné par la personne ; lire_structure_academique les liste. "
            . "Bascule tous les écrans de tous les utilisateurs : ne le propose que sur demande explicite. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'annee' => ['type' => 'string', 'description' => 'Identifiant ou nom exact de l\'année.'],
            ],
            'required' => ['annee'],
        ];
    }

    /** Ce que la bascule change, dit avant de valider. Partagé avec la création. */
    public static function consequences(?string $avant, string $apres): array
    {
        return [
            "Tous les utilisateurs passeront de l'année ".($avant ?? '(aucune)')." à {$apres} : tableaux de bord, listes d'étudiants, inscriptions, paiements, notes, emplois du temps et bulletins affichent l'année en cours.",
            "Les nouvelles inscriptions et les encaissements se rattacheront par défaut à {$apres}.",
            'Les données de l\'année précédente restent en base et consultables en la choisissant : rien n\'est supprimé.',
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = "Changer l'année en cours";
        $designation = trim((string) ($args['annee'] ?? ''));
        if ($designation === '') {
            return new Proposition(titre: $titre, resume: '', manques: ['Quelle année doit devenir l\'année en cours ?']);
        }

        $annee = ctype_digit($designation)
            ? ESBTPAnneeUniversitaire::find((int) $designation)
            : ESBTPAnneeUniversitaire::where('name', $designation)->first();
        if (! $annee) {
            return new Proposition(titre: $titre, resume: '', manques: ["Aucune année « {$designation} » : vérifie le nom exact avec lire_structure_academique."]);
        }

        $actuelle = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if ($actuelle && (int) $actuelle->id === (int) $annee->id) {
            return new Proposition(titre: $titre, resume: '', manques: ["{$annee->name} est déjà l'année en cours : rien à changer."]);
        }

        $effectifs = app(AnneesUniversitaires::class)->effectifs($annee);
        $avertissements = self::consequences($actuelle?->name, (string) $annee->name);
        if ($effectifs['inscriptions'] === 0) {
            $avertissements[] = "{$annee->name} n'a encore aucune inscription : les listes paraîtront vides après la bascule.";
        }
        if ($effectifs['sous_reserve'] > 0) {
            $avertissements[] = "{$effectifs['sous_reserve']} inscription(s) sous réserve sur {$annee->name} restent à régulariser.";
        }
        if (! $annee->is_active) {
            $avertissements[] = "{$annee->name} est marquée inactive.";
        }

        return new Proposition(
            titre: $titre,
            resume: "Année en cours : {$annee->name} au lieu de ".($actuelle?->name ?? '(aucune)').'.',
            tableau: [
                'colonnes' => ['Année', 'Période', 'Inscriptions', 'En cours'],
                'lignes' => array_values(array_filter([
                    $actuelle ? [(string) $actuelle->name, $this->periode($actuelle), (string) app(AnneesUniversitaires::class)->effectifs($actuelle)['inscriptions'], 'oui → non'] : null,
                    [(string) $annee->name, $this->periode($annee), (string) $effectifs['inscriptions'], 'non → oui'],
                ])),
            ],
            avertissements: $avertissements,
            donnees: ['annee_id' => (int) $annee->id],
            etat: ['courante_id' => $actuelle ? (int) $actuelle->id : null],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $annee = DB::transaction(function () use ($proposition) {
            $courante = ESBTPAnneeUniversitaire::where('is_current', true)->lockForUpdate()->value('id');
            if (($courante === null ? null : (int) $courante) !== $proposition->etat['courante_id']) {
                throw new PropositionPerimee("L'année en cours a changé depuis la proposition.");
            }
            $annee = ESBTPAnneeUniversitaire::findOrFail($proposition->donnees['annee_id']);
            app(AnneesUniversitaires::class)->definirCourante($annee);

            return $annee;
        });

        return [
            'message' => "{$annee->name} est désormais l'année en cours.",
            'lien' => route('esbtp.annees-universitaires.index', [], false),
            'model_type' => ESBTPAnneeUniversitaire::class,
            'model_id' => (int) $annee->id,
            'details' => ['avant' => $proposition->etat['courante_id'], 'apres' => (int) $annee->id],
        ];
    }

    private function periode(ESBTPAnneeUniversitaire $annee): string
    {
        return ($annee->start_date?->format('d/m/Y') ?? '?').' – '.($annee->end_date?->format('d/m/Y') ?? '?');
    }
}
