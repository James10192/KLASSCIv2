<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDResultatUE;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;

/**
 * Presente les notes d'un etudiant LMD, semestre par semestre, en UE puis ECUE.
 *
 * Les moyennes d'ECUE, d'UE, la moyenne generale, les credits et la validation
 * viennent de LmdBulletinProjectionService — la meme source que le bulletin.
 * Rien n'est recalcule ici : ce service ne fait que reformer ces resultats en
 * tableaux prets a afficher, et y accroche les evaluations brutes de l'etudiant
 * (CC, examen…) pour la ligne de detail de chaque ECUE.
 */
class EtudiantNotesLmdPresenter
{
    public function __construct(
        private readonly LmdBulletinProjectionService $projections,
        private readonly LMDBulletinService $bulletins,
    ) {}

    /**
     * Un tableau par semestre du niveau (S1/S2 en L1, S3/S4 en L2, …).
     *
     * @param  Collection  $notes  notes de l'etudiant sur l'annee, relation `evaluation` chargee
     * @return array{semestres: list<array<string, mixed>>}
     */
    public function presenter(ESBTPClasse $classe, int $etudiantId, int $anneeUniversitaireId, Collection $notes): array
    {
        $semestres = [];

        foreach ($classe->getSemestresLMD() as $semestre) {
            $projection = $this->projections->calculerProjectionLive(
                $etudiantId,
                (int) $classe->id,
                $anneeUniversitaireId,
                (int) $semestre
            );

            $variants = $this->bulletins->getPeriodeVariants((int) $semestre);
            $notesSemestre = $notes->filter(
                fn ($note) => in_array((string) ($note->evaluation?->periode ?? ''), $variants, true)
            );

            $semestres[] = $this->presenterSemestre((int) $semestre, $projection, $notesSemestre);
        }

        return ['semestres' => $semestres];
    }

    /**
     * Reforme une projection (sortie de LmdBulletinProjectionService) en
     * semestre affichable. Pure : aucune requete, testable en memoire.
     *
     * @param  Collection  $notes  notes de l'etudiant pour CE semestre, relation `evaluation` chargee
     */
    public function presenterSemestre(int $semestre, array $projection, Collection $notes): array
    {
        $notesParMatiere = $notes->groupBy(
            fn ($note) => (int) ($note->matiere_id ?: ($note->evaluation?->matiere_id ?? 0))
        );

        $bulletin = $projection['bulletin'] ?? null;

        $ues = collect($projection['resultats_ues'] ?? [])
            ->map(fn (array $resultatUe) => $this->presenterUe($resultatUe, $notesParMatiere))
            ->values()
            ->all();

        return [
            'numero' => $semestre,
            'code' => 'S'.$semestre,
            'label' => 'Semestre '.$semestre,
            'moyenne' => isset($projection['moyenne_generale']) ? (float) $projection['moyenne_generale'] : null,
            'mention' => $projection['mention_generale'] ?? null,
            'credits_acquis' => (int) ($projection['credits_capitalises'] ?? 0),
            'credits_attendus' => (int) ($projection['credits_totaux'] ?? 0),
            'rang' => $bulletin?->rang !== null ? (int) $bulletin->rang : null,
            'effectif' => $bulletin?->effectif !== null ? (int) $bulletin->effectif : null,
            'has_bulletin' => (bool) ($projection['has_bulletin'] ?? false),
            'statut' => (string) ($projection['status'] ?? 'configuration_missing'),
            'notes_count' => $notes->count(),
            'ues' => $ues,
        ];
    }

    private function presenterUe(array $resultatUe, Collection $notesParMatiere): array
    {
        $ue = $resultatUe['unite_enseignement'];
        $statut = $resultatUe['statut'] ?? null;
        $moyenne = isset($resultatUe['moyenne']) ? (float) $resultatUe['moyenne'] : null;

        $validee = $moyenne !== null
            && in_array($statut, [ESBTPLMDResultatUE::STATUT_AQ, ESBTPLMDResultatUE::STATUT_APC], true);

        if ($moyenne === null) {
            $etat = 'en_cours';
            $etatLabel = 'En cours';
        } elseif ($validee) {
            $etat = 'validee';
            $etatLabel = $statut === ESBTPLMDResultatUE::STATUT_APC ? 'Validée par compensation' : 'Validée';
        } else {
            $etat = 'non_validee';
            $etatLabel = 'Non validée';
        }

        $ecues = collect($resultatUe['resultats_ecues'] ?? [])
            ->map(fn (array $resultatEcue) => $this->presenterEcue($resultatEcue, $notesParMatiere))
            ->values()
            ->all();

        return [
            'id' => (int) ($resultatUe['unite_enseignement_id'] ?? $ue->id ?? 0),
            'code' => (string) ($ue->code_affiche ?? ''),
            'name' => (string) ($ue->name ?? ''),
            'credit' => (int) ($resultatUe['credit'] ?? 0),
            'moyenne' => $moyenne,
            'mention' => $resultatUe['mention'] ?? null,
            'statut' => $statut,
            'etat' => $etat,
            'etat_label' => $etatLabel,
            'ecues_manquantes' => (int) ($resultatUe['missing_ecues'] ?? 0),
            'ecues' => $ecues,
        ];
    }

    private function presenterEcue(array $resultatEcue, Collection $notesParMatiere): array
    {
        $matiere = $resultatEcue['matiere'];
        $matiereId = (int) ($resultatEcue['matiere_id'] ?? $matiere->id ?? 0);

        $evaluations = $notesParMatiere->get($matiereId, collect())
            ->sortBy(fn ($note) => (string) ($note->evaluation?->date_evaluation ?? ''))
            ->map(function ($note) {
                $absent = (bool) $note->is_absent;
                $valeur = ! $absent && is_numeric($note->note) ? (float) $note->note : null;
                $type = $note->evaluation?->type;

                return [
                    'type' => $type,
                    'type_label' => self::libelleCourt($type),
                    'titre' => $note->evaluation?->titre,
                    'note' => $valeur,
                    'bareme' => (float) ($note->evaluation?->bareme ?: 20),
                    'absent' => $absent,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $matiereId,
            'code' => (string) ($matiere->code_affiche ?? ''),
            'name' => (string) ($matiere->name ?? ''),
            'credit' => (int) ($resultatEcue['credit'] ?? 0),
            'coefficient' => (float) ($resultatEcue['coefficient'] ?? 1),
            'moyenne' => isset($resultatEcue['moyenne']) ? (float) $resultatEcue['moyenne'] : null,
            'evaluations' => $evaluations,
        ];
    }

    /**
     * Libelle court d'un type d'evaluation pour une ligne de detail
     * (« CC 15 · Examen — »).
     */
    public static function libelleCourt(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'devoir' => 'CC',
            'examen' => 'Examen',
            'rattrapage' => 'Rattrapage',
            'tp' => 'TP',
            'projet' => 'Projet',
            'oral' => 'Oral',
            '' => 'Évaluation',
            default => ucfirst((string) $type),
        };
    }

    /**
     * Formate une note a la francaise sans zeros inutiles : 15.00 → « 15 »,
     * 13.40 → « 13,4 », 11.55 → « 11,55 », null → « — ».
     */
    public static function formatNote(?float $valeur): string
    {
        if ($valeur === null) {
            return '—';
        }

        $texte = number_format($valeur, 2, ',', ' ');

        return rtrim(rtrim($texte, '0'), ',');
    }
}
