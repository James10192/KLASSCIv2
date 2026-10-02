<?php

namespace App\Domain\Assistant\Actions\Inscriptions;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\Inscriptions\StatutRedoublant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose de confirmer ou de corriger le statut redoublant d'inscriptions.
 *
 * Sans `valeur`, chaque statut est confirmé tel quel : c'est ce que fait la
 * confirmation en masse de la liste, et c'est le seul geste permis sur une
 * classe entière. Avec `valeur`, les inscriptions désignées une à une prennent
 * cette valeur ; celles dont la valeur change exigent un motif, comme sur la
 * fiche. L'écriture passe par StatutRedoublant::etablir, le chemin de l'écran
 * et de la CLI.
 */
class EtablirStatutRedoublant extends ActionAgent
{
    private const MAX = 200;

    private const ETATS = ['deduit' => 'déduit', 'a_confirmer' => 'à confirmer', 'confirme' => 'confirmé', 'corrige' => 'corrigé'];

    public function __construct(
        private DesignationDInscriptions $designation,
        private StatutRedoublant $statut,
    ) {
    }

    public function cle(): string
    {
        return 'statut_redoublant';
    }

    public function libelle(): string
    {
        return 'Préparation du statut redoublant…';
    }

    public function description(): string
    {
        return 'PROPOSE de confirmer ou de corriger le statut redoublant d’inscriptions : par `matricules` (année en cours), par `inscriptions` (identifiants), ou par `classe` (code : celles de l’année qui attendent une confirmation). '
            . 'Sans `valeur`, chaque statut est confirmé tel quel. Avec `valeur` (true = redoublant), seulement pour des inscriptions désignées une à une ; changer la valeur exige un `motif` d’au moins '
            . StatutRedoublant::MOTIF_MINIMUM . ' caractères donné par la personne. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matricules' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Matricules des élèves (inscription de l’année en cours).'],
                'inscriptions' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Identifiants d’inscription (search_inscriptions).'],
                'classe' => ['type' => 'string', 'description' => 'Code de classe : confirme tels quels les statuts de l’année qui attendent.'],
                'valeur' => ['type' => 'boolean', 'description' => 'Statut voulu (true = redoublant). Absent = confirmer la valeur actuelle.'],
                'motif' => ['type' => 'string', 'description' => 'Pourquoi le statut change, dit par la personne.'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Statut redoublant';
        $valeur = array_key_exists('valeur', $args) && $args['valeur'] !== null ? (bool) $args['valeur'] : null;
        if ($valeur !== null && ! empty($args['classe'])) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Une classe entière se confirme telle quelle. Pour changer un statut, désigne les élèves un par un (matricules).',
            ]);
        }

        [$inscriptions, $manques] = $this->inscriptions($args);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $motif = trim((string) ($args['motif'] ?? ''));
        $aEtablir = [];
        $lignes = [];
        $changements = 0;
        $avertissements = [];
        foreach ($inscriptions as $i) {
            // La valeur qui fait foi, pas la colonne : avant le recensement
            // elle peut dire « non » à un vrai redoublant.
            $affichage = $this->statut->pourAffichage($i);
            $actuel = $affichage['valeur'];
            $voulu = $valeur ?? $actuel;
            $nom = trim(($i->etudiant->nom ?? '').' '.($i->etudiant->prenoms ?? ''));
            $deja = $voulu === $actuel && $this->statut->estEtabliParUnePersonne($i);
            $lignes[] = [$nom, (string) ($i->etudiant->matricule ?? '—'), (string) ($i->classe->name ?? '—'),
                $this->libelleStatut($actuel).' ('.self::ETATS[$affichage['etat']].')',
                $deja ? 'Déjà établi, inchangé' : ($voulu === $actuel ? 'Confirmé' : 'Corrigé → '.$this->libelleStatut($voulu))];
            if ($deja) {
                continue;
            }
            $aEtablir[(int) $i->id] = $voulu;
            $changements += (int) ($voulu !== $actuel);
            if ($incoherence = $affichage['incoherence']) {
                $avertissements[] = $nom.' : '.$incoherence;
            }
        }

        if ($aEtablir === []) {
            return Proposition::sansObjet($titre, 'Ces statuts sont déjà confirmés par une personne, à ces valeurs.');
        }
        if ($changements > 0 && mb_strlen($motif) < StatutRedoublant::MOTIF_MINIMUM) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Pourquoi le statut change ? Demande le motif à la personne (au moins '.StatutRedoublant::MOTIF_MINIMUM.' caractères).',
            ]);
        }

        ksort($aEtablir);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d statut(s) établi(s), dont %d corrigé(s).', count($aEtablir), $changements),
            tableau: ['colonnes' => ['Étudiant', 'Matricule', 'Classe', 'Statut actuel', 'Effet'], 'lignes' => $lignes],
            avertissements: $avertissements,
            donnees: ['valeurs' => $aEtablir, 'motif' => $changements > 0 ? $motif : null],
            etat: ['inscriptions' => $this->etat(array_keys($aEtablir))],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can(StatutRedoublant::PERMISSION)) {
            throw new PropositionPerimee('Vous n’avez plus le droit de confirmer un statut redoublant.');
        }
        $valeurs = $proposition->donnees['valeurs'];
        $ids = array_map('intval', array_keys($valeurs));

        DB::transaction(function () use ($ids, $valeurs, $proposition, $user) {
            DesignationDInscriptions::verrouiller('esbtp_inscriptions', $ids);
            if ($this->etat($ids) !== $proposition->etat['inscriptions']) {
                throw new PropositionPerimee('Ces inscriptions ont changé depuis la proposition.');
            }
            foreach (ESBTPInscription::with('anneeUniversitaire')->whereIn('id', $ids)->orderBy('id')->get() as $i) {
                $this->statut->etablir($i, $user, (bool) $valeurs[$i->id], $proposition->donnees['motif']);
            }
        });

        return [
            'message' => count($ids).' statut(s) redoublant établi(s).',
            'lien' => count($ids) === 1
                ? route('esbtp.inscriptions.show', $ids[0], false)
                : route('esbtp.inscriptions.index', [], false),
            'model_type' => ESBTPInscription::class,
            'model_id' => $ids[0] ?? null,
            'details' => ['ids' => $ids],
        ];
    }

    private function libelleStatut(bool $valeur): string
    {
        return $valeur ? 'Redoublant' : 'Non redoublant';
    }

    /** @return array{0: Collection, 1: string[]} */
    private function inscriptions(array $args): array
    {
        $avec = ['etudiant:id,nom,prenoms,matricule', 'classe:id,name', 'anneeUniversitaire', 'redoublantConfirmePar:id,name'];

        if (! empty($args['classe'])) {
            $classe = ESBTPClasse::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim((string) $args['classe']))])->first();
            if (! $classe) {
                return [collect(), ["Classe {$args['classe']} introuvable : vérifie son code avec search_classes."]];
            }
            $annee = $this->designation->anneeCouranteId();
            $liste = StatutRedoublant::contraindreAConfirmer(ESBTPInscription::query())
                ->with($avec)->where('classe_id', $classe->id)
                ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee))
                ->whereNotIn('status', ESBTPInscription::STATUTS_ANNULES)
                ->orderBy('id')->get();

            return [$liste, $liste->isEmpty()
                ? ["Aucun statut redoublant à confirmer dans {$classe->name} cette année."]
                : ($liste->count() > self::MAX ? ['Plus de '.self::MAX.' inscriptions : précise la sélection.'] : [])];
        }

        $manques = [];
        $liste = new EloquentCollection();
        foreach ((array) ($args['inscriptions'] ?? []) as $id) {
            [$i, $manque] = $this->designation->inscriptionCourante(null, (int) $id);
            $manque ? $manques[] = $manque : $liste->push($i);
        }
        foreach ((array) ($args['matricules'] ?? []) as $matricule) {
            [$i, $manque] = $this->designation->inscriptionCourante((string) $matricule, null);
            $manque ? $manques[] = $manque : $liste->push($i);
        }
        if ($liste->isEmpty() && $manques === []) {
            $manques[] = 'Quelles inscriptions ? Donne des matricules, ou le code d’une classe.';
        }
        if ($liste->count() > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' inscriptions : précise la sélection.';
        }

        return [$liste->unique('id')->values()->load($avec), $manques];
    }

    /** @return array<int, array> */
    private function etat(array $ids): array
    {
        return ESBTPInscription::whereIn('id', $ids)->orderBy('id')
            ->get(['id', 'niveau_id', 'is_redoublant', 'redoublant_source', 'redoublant_confirme_par'])
            ->map(fn ($i) => [
                'id' => (int) $i->id, 'niveau' => $i->niveau_id !== null ? (int) $i->niveau_id : null,
                'valeur' => (bool) $i->is_redoublant, 'source' => $i->redoublant_source,
                'par' => $i->redoublant_confirme_par !== null ? (int) $i->redoublant_confirme_par : null,
            ])->all();
    }
}
