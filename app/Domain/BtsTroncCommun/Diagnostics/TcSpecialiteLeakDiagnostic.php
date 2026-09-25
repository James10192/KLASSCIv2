<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun\Diagnostics;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Collection;

/**
 * Matieres de specialite qui atteignent un bulletin de tronc commun.
 *
 * Declencheur : a l'ESBTP Yamoussoukro, un etudiant de TRONC COMMUN A portait
 * « Securite » a 12,75, rang 1, professeur « Non attribue ». La feature #576 a
 * pose la classification `specialite` sur le pivot, mais rien n'etait classe au
 * deploiement, et une matiere notee reste au bulletin quelle que soit sa
 * classification. Ce diagnostic dit OU et POURQUOI, etudiant par etudiant.
 *
 * Deux familles de matieres suspectes, par classe de tronc commun et semestre :
 *  (a) classee `specialite` sur le couple de tronc commun, et pourtant au
 *      bulletin parce qu'une note ou une moyenne enregistree l'y porte ;
 *  (b) non classee sur ce couple, presente sur le couple d'une filiere fille
 *      du meme niveau, et absente de la planification du tronc commun.
 *
 * Ce qui figure au bulletin n'est PAS reimplemente ici : la maquette vient de
 * `BtsBulletinSubjectResolver`, la cohorte de `BtsClassCohortCounter` (jamais
 * `inscriptions.classe_id`, qui suit l'etudiant reoriente), la classe porteuse
 * de chaque semestre de `BtsAnnualClassMapResolver`, les classes non ouvertes
 * de `ClasseOuvertureResolver` — les memes pieces que `BulletinService`.
 *
 * LECTURE SEULE. Aucune ecriture, aucune regeneration.
 */
final class TcSpecialiteLeakDiagnostic
{
    /** @var array<int, array<string, mixed>> contexte du couple, par classe */
    private array $couples = [];

    public function __construct(
        private readonly BtsBulletinSubjectResolver $maquette,
        private readonly BtsClassCohortCounter $cohortes,
        private readonly BtsAnnualClassMapResolver $cartes,
        private readonly BtsPhaseResolver $phases,
        private readonly ClasseOuvertureResolver $ouvertures,
        private readonly TcSpecialiteLeakEvidence $pieces,
        private readonly TcSpecialiteLeakReport $rapport,
    ) {}

    /** @return array<string, mixed> */
    public function executer(?int $anneeId = null, ?int $classeId = null, ?int $etudiantId = null): array
    {
        $annee = $anneeId !== null ? ESBTPAnneeUniversitaire::find($anneeId) : ESBTPAnneeUniversitaire::anneeCourante();

        if (! $annee) {
            throw new \DomainException('Annee universitaire introuvable.');
        }

        $classes = $this->classesDeTroncCommun($classeId);
        $rapports = [];

        foreach ($classes as $classe) {
            $semestres = [];
            foreach ([1, 2] as $semestre) {
                $analyse = $this->analyserSemestre($classe, (int) $annee->id, $semestre, $etudiantId);
                if ($analyse !== null && $analyse['matieres'] !== []) {
                    $semestres[] = $analyse;
                }
            }

            $rapports[] = [
                'classe_id' => (int) $classe->id,
                'classe' => $classe->name,
                'filiere_id' => (int) $classe->filiere_id,
                'filiere' => $classe->filiere?->name,
                'niveau_etude_id' => (int) $classe->niveau_etude_id,
                'niveau' => $classe->niveau?->name,
                'semestres' => $semestres,
            ];
        }

        return $this->rapport->assembler($annee, $classeId, $etudiantId, $rapports);
    }

    /** @return Collection<int, ESBTPClasse> */
    private function classesDeTroncCommun(?int $classeId): Collection
    {
        return ESBTPClasse::query()
            ->with(['filiere', 'niveau'])
            ->when($classeId !== null, fn ($q) => $q->whereKey($classeId))
            ->where(fn ($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->whereNotNull('niveau_etude_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (ESBTPClasse $classe) => (bool) $classe->filiere?->isTroncCommun())
            ->values();
    }

    /** @return array<string, mixed>|null */
    private function analyserSemestre(ESBTPClasse $classe, int $anneeId, int $semestre, ?int $etudiantId): ?array
    {
        $cohorte = $this->cohortes->etudiantIdsPourPeriode((int) $classe->id, $anneeId, 'semestre'.$semestre);

        if ($etudiantId !== null) {
            $cohorte = in_array($etudiantId, $cohorte, true) ? [$etudiantId] : [];
        }

        if ($cohorte === []) {
            return null;
        }

        $couple = $this->couple($classe);
        $planifiees = $this->pieces->planifiees((int) $classe->filiere_id, (int) $classe->niveau_etude_id, $anneeId);
        $inscriptions = $this->pieces->inscriptions($cohorte, $anneeId, (int) $classe->id);
        $nonOuvertes = array_flip($this->ouvertures->classesNonOuvertesAu($semestre));

        $etudiants = [];
        foreach ($cohorte as $id) {
            $etudiants[$id] = $this->contexteEtudiant($inscriptions[$id] ?? null, (int) $classe->id, $anneeId, $semestre);
        }

        $notes = $this->pieces->notes($cohorte, $anneeId, $semestre)->each(function ($note) use ($etudiants, $nonOuvertes) {
            $classeEval = (int) $note->evaluation_classe_id;
            $note->retenue = isset($etudiants[(int) $note->etudiant_id]['classes_bulletin'][$classeEval])
                && ! isset($nonOuvertes[$classeEval]);
        });
        $moyennes = $this->pieces->moyennes($cohorte, (int) $classe->id, $anneeId, $semestre);

        $affichees = array_keys(
            $couple['maquette']
            + $notes->where('retenue', true)->pluck('matiere_id')->mapWithKeys(fn ($id) => [(int) $id => true])->all()
            + $moyennes->pluck('matiere_id')->mapWithKeys(fn ($id) => [(int) $id => true])->all()
        );

        $matieres = [];
        foreach (ESBTPMatiere::whereIn('id', $affichees)->whereNull('unite_enseignement_id')->orderBy('name')->get(['id', 'name', 'code']) as $matiere) {
            $type = $this->typeDeSuspicion((int) $matiere->id, $couple, $planifiees);

            if ($type !== null) {
                $matieres[] = $this->rapport->matiere($matiere, $type, $classe, $couple, [
                    'notes' => $notes->where('matiere_id', $matiere->id),
                    'moyennes' => $moyennes->where('matiere_id', $matiere->id),
                    'etudiants' => $etudiants,
                    'dans_maquette' => isset($couple['maquette'][(int) $matiere->id]),
                    'semestre' => $semestre,
                ]);
            }
        }

        return [
            'semestre' => $semestre,
            'periode' => 'semestre'.$semestre,
            'effectif' => count($cohorte),
            'matieres' => $matieres,
        ];
    }

    /**
     * Ce qu'on sait du couple de tronc commun, une fois par classe.
     *
     * @return array{classification: array<int, string|null>, combos_specialite: array<int, list<array{filiere_id: int, filiere: string|null}>>, maquette: array<int, true>}
     */
    private function couple(ESBTPClasse $classe): array
    {
        return $this->couples[(int) $classe->id] ??= (function () use ($classe) {
            $tcId = (int) $classe->filiere_id;
            $filles = ESBTPFiliere::where('parent_id', $tcId)->pluck('name', 'id');

            $lignes = ESBTPMatiereFilierNiveau::query()
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->whereIn('filiere_id', array_merge([$tcId], $filles->keys()->map(fn ($id) => (int) $id)->all()))
                ->get(['matiere_id', 'filiere_id', 'classification']);

            $combos = [];
            foreach ($lignes->where('filiere_id', '!=', $tcId) as $ligne) {
                $combos[(int) $ligne->matiere_id][] = ['filiere_id' => (int) $ligne->filiere_id, 'filiere' => $filles[$ligne->filiere_id] ?? null];
            }

            return [
                'classification' => $lignes->where('filiere_id', $tcId)->mapWithKeys(fn ($l) => [(int) $l->matiere_id => $l->classification])->all(),
                'combos_specialite' => $combos,
                'maquette' => $this->maquette->subjectsForClasse($classe)->mapWithKeys(fn ($m) => [(int) $m->id => true])->all(),
            ];
        })();
    }

    /** @param array<int, true> $planifiees */
    private function typeDeSuspicion(int $matiereId, array $couple, array $planifiees): ?string
    {
        $classification = $couple['classification'][$matiereId] ?? null;

        if ($classification === ESBTPMatiereFilierNiveau::SPECIALITE) {
            return TcSpecialiteLeakCauses::TYPE_CLASSEE_SPECIALITE;
        }

        if ($classification === ESBTPMatiereFilierNiveau::TRONC_COMMUN
            || ! isset($couple['combos_specialite'][$matiereId])
            || isset($planifiees[$matiereId])) {
            return null;
        }

        return TcSpecialiteLeakCauses::TYPE_NON_CLASSEE;
    }

    /** @return array<string, mixed> */
    private function contexteEtudiant(?ESBTPInscription $inscription, int $classeId, int $anneeId, int $semestre): array
    {
        if (! $inscription) {
            return ['classes_bulletin' => [$classeId => true], 'classes_specialite' => [], 'phase_specialite' => null, 'etudiant' => null];
        }

        $carte = $this->cartes->resolveForInscription($inscription, $anneeId, $classeId);
        $classesBulletin = [$classeId => true];
        if (! empty($carte['semestre'.$semestre.'_classe_id'])) {
            $classesBulletin[(int) $carte['semestre'.$semestre.'_classe_id']] = true;
        }

        $specialites = array_values(array_filter(
            $this->phases->buildJourney($inscription)['timeline'] ?? [],
            fn (array $phase) => ($phase['type_phase'] ?? null) === ESBTPInscriptionPhase::TYPE_SPECIALISATION
        ));
        $phase = collect($specialites)->firstWhere('is_active', true) ?? ($specialites === [] ? null : end($specialites));

        return [
            'classes_bulletin' => $classesBulletin,
            'classes_specialite' => collect($specialites)->pluck('classe_id')->filter()->mapWithKeys(fn ($id) => [(int) $id => true])->all(),
            'phase_specialite' => $phase ? [
                'classe_id' => $phase['classe_id'] ? (int) $phase['classe_id'] : null,
                'classe' => $phase['classe'] ?? null,
                'filiere' => $phase['filiere'] ?? null,
                'semestre_debut' => $phase['semestre_debut'] ?? null,
                'semestre_fin' => $phase['semestre_fin'] ?? null,
                'is_active' => (bool) ($phase['is_active'] ?? false),
            ] : null,
            'etudiant' => $inscription->etudiant,
        ];
    }
}
