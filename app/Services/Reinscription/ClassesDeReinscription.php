<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * D'ou part une reinscription, et vers quelles classes elle peut aller.
 *
 * Deux questions qui n'avaient pas de reponse unique :
 *
 * - « Quelle inscription l'etudiant quitte-t-il ? » Cinq ecrans la resolvaient
 *   chacun a sa facon (inscription active quelconque, plus recente creee,
 *   derniere a etape « etudiant cree »...). Un etudiant passe du BTS a la
 *   Licence garde son ancienne inscription active : selon l'ecran, la decision
 *   se calculait sur l'une et les classes se proposaient depuis l'autre.
 * - « Quelle est la classe suivante ? » Elle se cherchait par `filiere_id`. En
 *   LMD ce champ porte le reflet de la mention ou du parcours de la classe :
 *   un Master 1 rattache a un autre parcours que la Licence 3 n'etait jamais
 *   propose.
 */
class ClassesDeReinscription
{
    /**
     * L'inscription de la derniere annee suivie : active, dossier finalise
     * (memes conditions que la liste de reinscription et la reinscription
     * groupee), avec une classe.
     */
    public function inscriptionQuittee(int $etudiantId): ?ESBTPInscription
    {
        return ESBTPInscription::query()
            ->select('esbtp_inscriptions.*')
            ->join('esbtp_annee_universitaires as annee', 'annee.id', '=', 'esbtp_inscriptions.annee_universitaire_id')
            ->where('esbtp_inscriptions.etudiant_id', $etudiantId)
            ->where('esbtp_inscriptions.status', 'active')
            ->where('esbtp_inscriptions.workflow_step', 'etudiant_cree')
            ->whereNotNull('esbtp_inscriptions.classe_id')
            ->orderByDesc('annee.start_date')
            ->orderByDesc('esbtp_inscriptions.id')
            ->with(['etudiant', 'classe.niveau', 'classe.filiere', 'classe.parcours', 'anneeUniversitaire'])
            ->first();
    }

    /**
     * Les classes proposees pour une decision, depuis la classe quittee.
     *
     * @return Collection<int, ESBTPClasse>
     */
    public function pour(ESBTPClasse $quittee, string $decision): Collection
    {
        $quittee->loadMissing(['niveau', 'filiere', 'parcours']);

        return match ($decision) {
            'passage' => $this->passage($quittee),
            'redoublement' => $this->redoublement($quittee),
            'rattrapage' => collect([$quittee]),
            default => collect(),
        };
    }

    /** @return array<string, Collection<int, ESBTPClasse>> */
    public function parDecision(ESBTPClasse $quittee): array
    {
        return [
            'passage' => $this->pour($quittee, 'passage'),
            'redoublement' => $this->pour($quittee, 'redoublement'),
            'rattrapage' => $this->pour($quittee, 'rattrapage'),
        ];
    }

    private function passage(ESBTPClasse $quittee): Collection
    {
        $niveau = $quittee->niveau;
        if (! $niveau) {
            return collect();
        }

        $proposes = $niveau->estUnCycleLmd()
            ? $this->passageLmd($quittee, (int) $niveau->year)
            : $this->passageHorsLmd($quittee, (int) $niveau->year, (string) $niveau->type);

        $depuisTc = $this->passageDepuisTroncCommun($quittee, (int) $niveau->year);
        if ($depuisTc->isEmpty()) {
            return $proposes;
        }

        return $proposes->concat($depuisTc)->unique('id')->values();
    }

    /**
     * En LMD l'annee est comptee en continu (Licence 3 = 3, Master 1 = 4) : la
     * classe suivante est a l'annee suivante, quel que soit le cycle. On la
     * cherche du plus proche au plus large, et le premier palier qui trouve
     * l'emporte : meme parcours, puis meme mention, puis meme filiere (donnees
     * anterieures au reflet, ou ecole dont Licence et Master partagent la
     * filiere).
     */
    private function passageLmd(ESBTPClasse $quittee, int $annee): Collection
    {
        $suivantes = fn () => ESBTPClasse::where('is_active', 1)
            ->whereHas('niveau', fn (Builder $q) => $q->where('year', $annee + 1)->whereIn('type', ESBTPNiveauEtude::CYCLES_LMD));

        $mentionId = $this->mentionDe($quittee);

        $memeParcours = $quittee->parcours_id
            ? $this->avecRelations($suivantes()->where('parcours_id', $quittee->parcours_id))
            : collect();

        $memeMention = $mentionId
            ? $this->avecRelations($suivantes()->where(fn (Builder $q) => $q
                ->whereHas('parcours', fn (Builder $p) => $p->where('mention_id', $mentionId))
                ->orWhereHas('filiere', fn (Builder $f) => $f->where('lmd_mention_id', $mentionId))))
            : collect();

        $prochainsParcours = $memeMention->pluck('parcours_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $anneeDOrientation = OrientationLmd::estAnneeDOrientation(
            $annee,
            (string) $quittee->niveau->type
        );

        if (OrientationLmd::doitProposerTousLesParcoursDeLaMention(
            $quittee->parcours_id ? (int) $quittee->parcours_id : null,
            $prochainsParcours,
            $anneeDOrientation
        ) && $memeMention->isNotEmpty()) {
            return $memeMention;
        }

        if ($memeParcours->isNotEmpty()) {
            return $memeParcours;
        }

        if ($memeMention->isNotEmpty()) {
            return $memeMention;
        }

        return $this->avecRelations($suivantes()->where('filiere_id', $quittee->filiere_id));
    }

    private function redoublement(ESBTPClasse $quittee): Collection
    {
        $memeNiveau = fn () => ESBTPClasse::where('niveau_etude_id', $quittee->niveau_etude_id)->where('is_active', 1);

        if ($quittee->niveau?->estUnCycleLmd() && $quittee->parcours_id) {
            $memeParcours = $this->avecRelations($memeNiveau()->where('parcours_id', $quittee->parcours_id));
            if ($memeParcours->isNotEmpty()) {
                return $memeParcours;
            }
        }

        return $this->avecRelations($memeNiveau()->where('filiere_id', $quittee->filiere_id));
    }

    /**
     * Tronc commun (filière parente) : l'année suivante est dans les filières filles.
     */
    private function passageDepuisTroncCommun(ESBTPClasse $quittee, int $annee): Collection
    {
        $filiere = $quittee->filiere;
        if (! $filiere || ! $filiere->isTroncCommun()) {
            return collect();
        }

        $filles = \App\Models\ESBTPFiliere::query()->where('parent_id', $filiere->id)->pluck('id');
        if ($filles->isEmpty()) {
            return collect();
        }

        $dansFilles = fn (int $year) => $this->avecRelations(
            ESBTPClasse::whereIn('filiere_id', $filles)
                ->where('is_active', 1)
                ->whereHas('niveau', fn (Builder $q) => $q->where('year', $year))
        );

        $suivantes = $dansFilles($annee + 1);

        return $suivantes->isNotEmpty()
            ? $suivantes
            : $dansFilles(1)->reject(fn ($classe) => (int) $classe->id === (int) $quittee->id)->values();
    }

    /**
     * Hors LMD : meme type, annee suivante ; en fin de cycle, la premiere annee
     * d'un autre type de la meme filiere (BTS 2 → Licence 1 d'une filiere
     * commune). Comportement inchange.
     */
    private function passageHorsLmd(ESBTPClasse $quittee, int $annee, string $type): Collection
    {
        $suivantes = $this->avecRelations(ESBTPClasse::where('filiere_id', $quittee->filiere_id)
            ->where('is_active', 1)
            ->whereHas('niveau', fn (Builder $q) => $q->where('year', $annee + 1)->where('type', $type)));

        return $suivantes->isNotEmpty()
            ? $suivantes
            : $this->avecRelations(ESBTPClasse::where('filiere_id', $quittee->filiere_id)
                ->where('is_active', 1)
                ->whereHas('niveau', fn (Builder $q) => $q->where('year', 1)->where('type', '!=', $type)));
    }

    /** La mention d'une classe LMD : par son parcours, sinon par le reflet de sa filiere. */
    private function mentionDe(ESBTPClasse $classe): ?int
    {
        return $classe->parcours?->mention_id
            ?? $classe->filiere?->lmd_mention_id
            ?? $classe->filiere?->lmdParcours?->mention_id;
    }

    private function avecRelations(Builder $requete): Collection
    {
        return $requete->with(['niveau', 'filiere', 'parcours'])->get();
    }
}
