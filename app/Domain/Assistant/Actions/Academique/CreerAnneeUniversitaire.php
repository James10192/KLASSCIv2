<?php

namespace App\Domain\Assistant\Actions\Academique;

use App\Domain\Academique\AnneesUniversitaires;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use Carbon\Carbon;

/**
 * Propose de créer une année universitaire (« ouvre l'année 2026-2027 du
 * 15 septembre au 31 juillet »). Écrit par AnneesUniversitaires, le chemin de
 * la CLI.
 *
 * Le nom et les deux dates viennent de la personne, jamais d'une déduction.
 * En faire l'année courante est une décision à part : elle n'est jamais
 * supposée (la CLI le faisait par défaut, l'écran non), et elle exige le droit
 * de l'écran qui la porte (annees.set_current).
 */
class CreerAnneeUniversitaire extends ActionAgent
{
    public function cle(): string
    {
        return 'creation_annee';
    }

    public function description(): string
    {
        return "PROPOSE de créer une année universitaire : nom (ex. « 2026-2027 »), date_debut et date_fin (AAAA-MM-JJ), et courante (true/false : en faire l'année en cours). "
            . "Tout vient de la personne : ne déduis ni les dates ni le nom, et demande si elle doit devenir l'année courante. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nom' => ['type' => 'string', 'description' => 'Nom de l\'année, tel que donné (ex. 2026-2027).'],
                'date_debut' => ['type' => 'string', 'description' => 'Date de début, AAAA-MM-JJ.'],
                'date_fin' => ['type' => 'string', 'description' => 'Date de fin, AAAA-MM-JJ.'],
                'courante' => ['type' => 'boolean', 'description' => 'En faire l\'année en cours (à demander).'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Créer une année universitaire';
        $nom = trim((string) ($args['nom'] ?? ''));
        $debut = $this->date($args['date_debut'] ?? null);
        $fin = $this->date($args['date_fin'] ?? null);
        $manques = [];

        if ($nom === '') {
            $manques[] = 'Quel nom pour cette année (ex. 2026-2027) ?';
        } elseif (mb_strlen($nom) > 50) {
            $manques[] = 'Le nom ne dépasse pas 50 caractères.';
        } elseif (app(AnneesUniversitaires::class)->nomPris($nom)) {
            $manques[] = "L'année « {$nom} » existe déjà.";
        }
        if (! $debut || ! $fin) {
            $manques[] = 'Quelles dates de début et de fin (AAAA-MM-JJ) ?';
        } elseif ($fin->lte($debut)) {
            $manques[] = 'La date de fin doit suivre la date de début.';
        }
        if (! array_key_exists('courante', $args)) {
            $manques[] = "Faut-il en faire l'année en cours dès maintenant ?";
        }
        $courante = (bool) ($args['courante'] ?? false);
        if ($courante && ! $user->can('annees.set_current')) {
            $manques[] = "Vous pouvez créer l'année, mais pas la désigner comme année en cours : créez-la sans, puis demandez-le à qui en a le droit.";
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $actuelle = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $avertissements = $courante ? DefinirAnneeCourante::consequences($actuelle?->name, $nom) : [];

        return new Proposition(
            titre: $titre,
            resume: "Année {$nom}, du {$debut->translatedFormat('j F Y')} au {$fin->translatedFormat('j F Y')}".($courante ? ', désignée année en cours.' : '.'),
            tableau: [
                'colonnes' => ['Nom', 'Début', 'Fin', 'Année en cours'],
                'lignes' => [[$nom, $debut->format('d/m/Y'), $fin->format('d/m/Y'), $courante ? ($actuelle?->name ?? 'aucune').' → '.$nom : 'inchangée ('.($actuelle?->name ?? 'aucune').')']],
            ],
            avertissements: $avertissements,
            donnees: ['name' => $nom, 'start_date' => $debut->toDateString(), 'end_date' => $fin->toDateString(), 'courante' => $courante],
            etat: ['courante_id' => $actuelle ? (int) $actuelle->id : null],
            risque: $courante ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $service = app(AnneesUniversitaires::class);

        // La revérification se fait DANS la transaction du service, sous
        // verrou : une seule transaction (voir AnneesUniversitaires).
        $annee = $service->creer($d, (bool) $d['courante'], function () use ($d, $proposition, $service) {
            if ($service->nomPris($d['name']) || $service->idCourante() !== $proposition->etat['courante_id']) {
                throw new PropositionPerimee('Les années universitaires ont changé depuis la proposition.');
            }
        });

        return [
            'message' => "Année {$annee->name} créée".($d['courante'] ? ' et désignée année en cours.' : '.'),
            'lien' => route('esbtp.annees-universitaires.index', [], false),
            'model_type' => ESBTPAnneeUniversitaire::class,
            'model_id' => (int) $annee->id,
            'details' => ['courante' => (bool) $d['courante']],
        ];
    }

    private function date(mixed $valeur): ?Carbon
    {
        if (! is_string($valeur) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($valeur))) {
            return null;
        }
        try {
            $date = Carbon::createFromFormat('!Y-m-d', trim($valeur));
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === trim($valeur) ? $date : null;
    }
}
