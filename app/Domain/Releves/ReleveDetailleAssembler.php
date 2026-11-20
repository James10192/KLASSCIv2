<?php

namespace App\Domain\Releves;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPNote;
use Illuminate\Support\Collection;

/**
 * Le relevé de notes détaillé d'une classe : une page par étudiant, chaque
 * note de chaque évaluation, puis la moyenne de l'élément et du bloc telles
 * que le bulletin les a calculées.
 *
 * Document de travail pour le jury, sans valeur officielle. Il n'invente
 * aucune moyenne : elles viennent des bulletins (LMD ou BTS) déjà calculés.
 * Seules les notes d'évaluations non annulées sont listées, absences exclues.
 */
class ReleveDetailleAssembler
{
    public function pourJuryLmd(ESBTPLMDJury $jury): array
    {
        $jury->loadMissing(['anneeUniversitaire', 'parcours', 'classe.niveau']);
        $bulletins = ESBTPLMDBulletin::query()->forJury($jury)
            ->with(['etudiant', 'resultatsUEs.uniteEnseignement', 'resultatsUEs.resultatsECUEs.matiere'])
            ->get();

        $matiereIds = $bulletins->flatMap(fn ($b) => $b->resultatsUEs->flatMap(fn ($ue) => $ue->resultatsECUEs->pluck('matiere_id')))->unique()->all();
        $notes = $this->notes((int) $jury->classe_id, (int) $jury->annee_universitaire_id, $bulletins->pluck('etudiant_id')->all(), $matiereIds);

        $etudiants = $this->trier($bulletins)->map(function (ESBTPLMDBulletin $b) use ($notes) {
            $blocs = $b->resultatsUEs->sortBy(fn ($ue) => (string) $ue->uniteEnseignement?->code_affiche)->map(fn ($ue) => [
                'code' => $ue->uniteEnseignement?->code_affiche,
                'libelle' => $ue->uniteEnseignement?->name,
                'credit' => $ue->credit,
                'moyenne' => $ue->moyenne,
                'acquis' => (bool) $ue->isValidee(),
                'elements' => $ue->resultatsECUEs->sortBy(fn ($e) => (string) $e->matiere?->code_affiche)->map(fn ($e) => [
                    'code' => $e->matiere?->code_affiche,
                    'libelle' => $e->matiere?->name ?? 'Élément',
                    'poids' => $e->matiere?->credit_ecue ?? null,
                    'notes' => $notes[$b->etudiant_id.':'.$e->matiere_id] ?? [],
                    'moyenne' => $e->moyenne,
                ])->values()->all(),
            ])->values()->all();

            return $this->etudiant($b->etudiant, $blocs, [
                'Moyenne du semestre' => $b->moyenne_generale,
                'Crédits' => (int) $b->credits_capitalises.' / '.(int) $b->credits_totaux,
            ]);
        })->values()->all();

        return $this->entete('LMD', $jury->classe, $jury->anneeUniversitaire?->name, 'Semestre '.$jury->semestre, $etudiants);
    }

    public function pourClasseBts(ESBTPClasse $classe, int $anneeId, string $periode): array
    {
        $bulletins = ESBTPBulletin::query()
            ->where('classe_id', $classe->id)->where('annee_universitaire_id', $anneeId)->where('periode', $periode)
            ->with(['etudiant', 'resultatsMatiere.matiere'])
            ->get();

        $matiereIds = $bulletins->flatMap(fn ($b) => $b->resultatsMatiere->pluck('matiere_id'))->unique()->all();
        $notes = $this->notes($classe->id, $anneeId, $bulletins->pluck('etudiant_id')->all(), $matiereIds, $periode);

        $etudiants = $this->trier($bulletins)->map(function (ESBTPBulletin $b) use ($notes) {
            $elements = $b->resultatsMatiere->sortBy(fn ($r) => (string) $r->matiere?->name)->map(fn ($r) => [
                'code' => $r->matiere?->code,
                'libelle' => $r->matiere?->name ?? 'Matière',
                'poids' => $r->coefficient,
                'notes' => $notes[$b->etudiant_id.':'.$r->matiere_id] ?? [],
                'moyenne' => $r->moyenne,
            ])->values()->all();

            return $this->etudiant($b->etudiant, [['code' => null, 'libelle' => null, 'credit' => null, 'moyenne' => null, 'acquis' => null, 'elements' => $elements]], [
                'Moyenne générale' => $b->moyenne_generale,
                'Rang' => $b->rang ? $b->rang.' / '.$b->effectif_classe : null,
            ]);
        })->values()->all();

        $libelle = $periode === 'semestre1' ? 'Semestre 1' : ($periode === 'semestre2' ? 'Semestre 2' : ucfirst($periode));

        return $this->entete('BTS', $classe, \App\Models\ESBTPAnneeUniversitaire::find($anneeId)?->name, $libelle, $etudiants);
    }

    /** @return array<string, list<array{titre:string,type:?string,note:float,coefficient:float}>> */
    private function notes(int $classeId, int $anneeId, array $etudiantIds, array $matiereIds, ?string $periode = null): array
    {
        if ($etudiantIds === [] || $matiereIds === []) {
            return [];
        }

        return ESBTPNote::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->where(fn ($q) => $q->whereNull('is_absent')->orWhere('is_absent', false))
            ->whereNotNull('note')
            ->whereHas('evaluation', fn ($q) => $q->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeId)
                ->whereIn('matiere_id', $matiereIds)
                ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
                ->when($periode, fn ($q, $p) => $q->where('periode', $p)))
            ->with('evaluation:id,matiere_id,titre,type,coefficient,bareme,date_evaluation')
            ->get()
            ->sortBy(fn ($n) => (string) $n->evaluation->date_evaluation.'|'.$n->evaluation_id)
            ->groupBy(fn ($n) => $n->etudiant_id.':'.$n->evaluation->matiere_id)
            ->map(fn (Collection $g) => $g->map(fn ($n) => [
                'titre' => (string) $n->evaluation->titre,
                'type' => $n->evaluation->type,
                'note' => round((float) $n->note * 20 / max((float) ($n->evaluation->bareme ?: 20), 1), 2),
                'coefficient' => (float) ($n->evaluation->coefficient ?: 1),
            ])->values()->all())
            ->all();
    }

    private function trier(Collection $bulletins): Collection
    {
        return $bulletins->filter(fn ($b) => $b->etudiant)
            ->sortBy(fn ($b) => mb_strtoupper($b->etudiant->nom.' '.$b->etudiant->prenoms, 'UTF-8'))->values();
    }

    private function etudiant($e, array $blocs, array $resultat): array
    {
        return [
            'matricule' => $e->matricule,
            'nom' => trim($e->nom.' '.$e->prenoms),
            'naissance' => trim(optional($e->date_naissance)->format('d/m/Y').' '.($e->lieu_naissance ? 'à '.$e->lieu_naissance : '')),
            'blocs' => $blocs,
            'resultat' => array_filter($resultat, fn ($v) => $v !== null),
        ];
    }

    private function entete(string $systeme, ?ESBTPClasse $classe, ?string $annee, string $periode, array $etudiants): array
    {
        return [
            'systeme' => $systeme,
            'ecole' => SettingsHelper::getSchoolInfo(),
            'classe' => $classe?->name,
            'annee' => $annee,
            'periode' => $periode,
            'etudiants' => $etudiants,
        ];
    }
}
