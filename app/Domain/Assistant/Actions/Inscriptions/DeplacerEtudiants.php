<?php

namespace App\Domain\Assistant\Actions\Inscriptions;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Inscriptions\ExamenDeDeplacement;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\ClassStudentService;
use Illuminate\Support\Facades\DB;

/**
 * Propose de changer des élèves de classe pour l'année en cours (« mets
 * KOUASSI en GBAT 1B, il est en 1A par erreur »).
 *
 * Même lecture que la CLI (ExamenDeDeplacement), même écriture que l'écran de
 * la classe (ClassStudentService::addStudents) : inscription active exigée,
 * spécialisation active protégée, notes et bulletins déjà saisis signalés.
 * Une classe pleine est signalée, pas refusée : l'écran ne la refuse pas non plus.
 */
class DeplacerEtudiants extends ActionAgent
{
    private const MAX = 60;

    public function __construct(
        private DesignationDInscriptions $designation,
        private ExamenDeDeplacement $examen,
        private ClassStudentService $classes,
    ) {
    }

    public function cle(): string
    {
        return 'deplacement_etudiants';
    }

    public function libelle(): string
    {
        return 'Préparation du changement de classe…';
    }

    public function description(): string
    {
        return 'PROPOSE de changer des élèves de classe pour l’année en cours. `deplacements` : liste de {matricule, vers (code de la classe d’arrivée), depuis (code de la classe de départ, facultatif : contrôle)}. '
            . 'La classe d’arrivée vient de la personne : ne la déduis jamais. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deplacements' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'matricule' => ['type' => 'string'],
                            'vers' => ['type' => 'string', 'description' => 'Code de la classe d’arrivée.'],
                            'depuis' => ['type' => 'string', 'description' => 'Code de la classe de départ (contrôle).'],
                        ],
                        'required' => ['matricule'],
                    ],
                ],
            ],
            'required' => ['deplacements'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Changer des élèves de classe';
        $demandes = array_values((array) ($args['deplacements'] ?? []));
        $annee = $this->designation->anneeCouranteId();
        if ($demandes === []) {
            return new Proposition(titre: $titre, resume: '', manques: ['Quels élèves, et vers quelle classe ?']);
        }
        if (! $annee) {
            return new Proposition(titre: $titre, resume: '', manques: ['Aucune année universitaire en cours.']);
        }
        if (count($demandes) > self::MAX) {
            return new Proposition(titre: $titre, resume: '', manques: ['Plus de '.self::MAX.' élèves : procède par classe.']);
        }

        $manques = [];
        $avertissements = [];
        $lignes = [];
        $mouvements = [];
        $arrivees = [];
        foreach ($demandes as $d) {
            $matricule = trim((string) ($d['matricule'] ?? ''));
            if (empty($d['vers'])) {
                $manques[] = "Vers quelle classe envoyer {$matricule} ? Donne son code.";
                continue;
            }
            $vers = $this->classe((string) $d['vers']);
            if (! $vers) {
                $manques[] = "Classe {$d['vers']} introuvable : vérifie son code avec search_classes.";
                continue;
            }
            [$inscription, $manque] = $this->designation->inscriptionCourante($matricule, null);
            if ($manque) {
                $manques[] = $manque;
                continue;
            }
            if (! empty($d['depuis'])) {
                $depuis = $this->classe((string) $d['depuis']);
                if (! $depuis || (int) $depuis->id !== (int) $inscription->classe_id) {
                    $manques[] = "{$matricule} n'est pas inscrit en {$d['depuis']} cette année : vérifie sa classe actuelle.";
                    continue;
                }
            }

            $examen = $this->examen->examiner((int) $inscription->etudiant_id, (int) $inscription->classe_id, (int) $vers->id, $annee);
            if ($examen['saute']) {
                $manques[] = "{$matricule} est déjà en {$vers->name}.";
                continue;
            }
            if ($examen['erreur']) {
                $manques[] = "{$matricule} : ".match ($examen['erreur']) {
                    'inscription_not_active' => 'son inscription n’est pas active (statut « '.$examen['inscription']->status.' ») : elle se valide d’abord.',
                    'classe_not_found' => 'sa classe actuelle est introuvable.',
                    default => 'aucune inscription active trouvée cette année.',
                };
                continue;
            }

            $nom = trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? ''));
            $donnees = $examen['donnees'];
            if ($donnees) {
                $avertissements[] = sprintf('%s a déjà %d note(s), %d moyenne(s) et %d bulletin(s) en %s : ils restent rattachés à cette classe.',
                    $nom, $donnees['notes_count'] ?? 0, $donnees['resultats_count'] ?? 0, $donnees['bulletins_count'] ?? 0, $examen['depuis']->name);
            }
            $mouvements[] = ['inscription_id' => (int) $inscription->id, 'etudiant_id' => (int) $inscription->etudiant_id, 'vers' => (int) $vers->id];
            $arrivees[$vers->id] = ($arrivees[$vers->id] ?? 0) + 1;
            $lignes[] = [$nom, $matricule, (string) $examen['depuis']->name, (string) $vers->name];
        }

        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        foreach ($arrivees as $classeId => $n) {
            $classe = ESBTPClasse::find($classeId);
            $inscrits = ESBTPInscription::where('classe_id', $classeId)->where('annee_universitaire_id', $annee)->where('status', 'active')->count();
            if ($classe->places_totales && $inscrits + $n > (int) $classe->places_totales) {
                $avertissements[] = "{$classe->name} passera à ".($inscrits + $n)." inscrits pour {$classe->places_totales} places.";
            }
        }

        usort($mouvements, fn ($a, $b) => $a['inscription_id'] <=> $b['inscription_id']);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d élève(s) changé(s) de classe pour l’année en cours.', count($mouvements)),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Classe actuelle', 'Nouvelle classe'], 'lignes' => $lignes],
            avertissements: $avertissements,
            donnees: ['mouvements' => $mouvements],
            etat: ['inscriptions' => $this->etat(array_column($mouvements, 'inscription_id'))],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('students.edit')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de changer un élève de classe.');
        }
        $mouvements = $proposition->donnees['mouvements'];
        $ids = array_column($mouvements, 'inscription_id');

        DB::transaction(function () use ($mouvements, $ids, $proposition) {
            DesignationDInscriptions::verrouiller('esbtp_inscriptions', $ids);
            if ($this->etat($ids) !== $proposition->etat['inscriptions']) {
                throw new PropositionPerimee('Ces inscriptions ont changé depuis la proposition.');
            }
            foreach ($mouvements as $m) {
                $resultat = $this->classes->addStudents(ESBTPClasse::findOrFail($m['vers']), [$m['etudiant_id']]);
                if (($resultat['added'] ?? 0) !== 1) {
                    throw new PropositionPerimee(implode(' ', $resultat['errors'] ?? ['Changement de classe refusé.']));
                }
            }
        });

        return [
            'message' => count($mouvements).' élève(s) changé(s) de classe.',
            'lien' => route('esbtp.classes.show', $mouvements[0]['vers'], false),
            'model_type' => ESBTPInscription::class,
            'model_id' => $ids[0] ?? null,
            'details' => ['mouvements' => $mouvements],
        ];
    }

    private function classe(string $code): ?ESBTPClasse
    {
        return ESBTPClasse::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])->first();
    }

    private function etat(array $ids): array
    {
        return ESBTPInscription::whereIn('id', $ids)->orderBy('id')->get(['id', 'classe_id', 'status'])
            ->map(fn ($i) => ['id' => (int) $i->id, 'classe' => (int) $i->classe_id, 'status' => (string) $i->status])->all();
    }
}
