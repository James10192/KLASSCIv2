<?php

namespace App\Domain\Assistant\Actions\Academique;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPNiveauEtude;
use App\Services\LMD\CoherenceNiveauxLmd;
use Illuminate\Support\Facades\DB;

/**
 * Propose de replacer un niveau LMD sur la bonne année de son cycle (un
 * Master 1 saisi en année 1 reçoit les UE de Licence). Le constat et
 * l'écriture sont ceux de la CLI : CoherenceNiveauxLmd::corrigerAnnee(),
 * en simulation puis appliqué.
 *
 * Refus du service, repris tels quels : niveau hors LMD, année hors du cycle,
 * couple déjà tenu par un autre niveau, ou unités, séances, évaluations, notes,
 * bulletins, jurys rattachés (la correction les détacherait de leurs semestres).
 */
class CorrigerAnneeNiveau extends ActionAgent
{
    public function cle(): string
    {
        return 'annee_niveau';
    }

    public function description(): string
    {
        return "PROPOSE de corriger l'année d'un niveau LMD (Licence 1 à 3 = années 1 à 3, Master = 4 et 5, Doctorat = 6 à 8). `niveau` = identifiant ou code, `annee` = nouvelle année donnée par la personne. "
            . 'Refusé si des unités, notes ou bulletins y sont rattachés. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'niveau' => ['type' => 'string', 'description' => 'Identifiant ou code du niveau.'],
                'annee' => ['type' => 'integer', 'description' => 'Année cible dans le cycle.'],
            ],
            'required' => ['niveau', 'annee'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = "Corriger l'année d'un niveau";
        $designation = trim((string) ($args['niveau'] ?? ''));
        if ($designation === '' || ! is_numeric($args['annee'] ?? null)) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quel niveau, et vers quelle année ?']);
        }
        $niveau = $this->niveau($designation);
        if (! $niveau) {
            return new Proposition(titre: $titre, resume: '', manques: ["Aucun niveau « {$designation} » : vérifie avec lire_structure_academique."]);
        }
        $cible = (int) $args['annee'];
        if ($cible === (int) $niveau->year) {
            return new Proposition(titre: $titre, resume: '', manques: ["{$niveau->name} est déjà en année {$cible}."]);
        }

        $constat = app(CoherenceNiveauxLmd::class)->corrigerAnnee($niveau, $cible, false);
        if ($constat['refus'] !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $constat['refus']);
        }

        $dep = $constat['niveau']['dependances'];
        $avertissements = [];
        if ($dep['classes'] > 0 || $dep['inscriptions'] > 0) {
            $avertissements[] = "{$dep['classes']} classe(s) et {$dep['inscriptions']} inscription(s) suivent ce niveau : leurs semestres passeront à ceux de l'année {$cible}.";
        }
        $s = ($cible - 1) * 2 + 1;

        return new Proposition(
            titre: $titre,
            resume: "{$niveau->name} passe de l'année {$niveau->year} à l'année {$cible}.",
            tableau: [
                'colonnes' => ['Niveau', 'Type', 'Année', 'Semestres', 'Classes', 'Inscriptions'],
                'lignes' => [[(string) $niveau->name, (string) $niveau->type, $niveau->year.' → '.$cible,
                    'S'.implode('-S', $constat['niveau']['semestres_actuels']).' → S'.$s.'-S'.($s + 1),
                    (string) $dep['classes'], (string) $dep['inscriptions']]],
            ],
            avertissements: $avertissements,
            donnees: ['niveau_id' => (int) $niveau->id, 'annee' => $cible],
            etat: ['annee' => (int) $niveau->year, 'dependances' => $dep],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;

        $niveau = DB::transaction(function () use ($d, $proposition) {
            $niveau = ESBTPNiveauEtude::lockForUpdate()->findOrFail($d['niveau_id']);
            if ((int) $niveau->year !== $proposition->etat['annee']) {
                throw new PropositionPerimee('Ce niveau a changé depuis la proposition.');
            }
            $resultat = app(CoherenceNiveauxLmd::class)->corrigerAnnee($niveau, $d['annee'], true);
            if (! $resultat['applique']) {
                throw new PropositionPerimee(implode(' ', $resultat['refus']) ?: 'La correction n\'est plus possible.');
            }

            return $niveau;
        });

        return [
            'message' => "{$niveau->name} est désormais en année {$d['annee']}.",
            'lien' => route('esbtp.niveaux-etudes.index', [], false),
            'model_type' => ESBTPNiveauEtude::class,
            'model_id' => (int) $niveau->id,
            'details' => ['avant' => $proposition->etat['annee'], 'apres' => $d['annee']],
        ];
    }

    private function niveau(string $designation): ?ESBTPNiveauEtude
    {
        return ctype_digit($designation)
            ? ESBTPNiveauEtude::find((int) $designation)
            : ESBTPNiveauEtude::whereRaw('UPPER(code) = ?', [mb_strtoupper($designation)])->first();
    }
}
