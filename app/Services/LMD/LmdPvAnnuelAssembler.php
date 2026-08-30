<?php

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryDecision;
use Illuminate\Support\Collection;

class LmdPvAnnuelAssembler
{
    public function forJury(ESBTPLMDJury $jury): array
    {
        $jury->loadMissing(['anneeUniversitaire', 'parcours', 'classe.filiere', 'classe.niveau']);

        $bulletins = ESBTPLMDBulletin::query()
            ->with(['etudiant', 'resultatsUEs.uniteEnseignement', 'resultatsUEs.resultatsECUEs.matiere'])
            ->where('annee_universitaire_id', $jury->annee_universitaire_id)
            ->when($jury->classe_id, fn ($q) => $q->where('classe_id', $jury->classe_id))
            ->when($jury->parcours_id, fn ($q) => $q->where('parcours_id', $jury->parcours_id))
            ->orderBy('etudiant_id')
            ->orderBy('semestre')
            ->get()
            ->groupBy('etudiant_id');

        $decisions = ESBTPLMDJuryDecision::query()
            ->where('jury_id', $jury->id)
            ->pluck('decision', 'etudiant_id');

        $ues = $bulletins->flatten()->pluck('resultatsUEs')->flatten()
            ->unique('unite_enseignement_id')
            ->values();

        $rows = [];
        $ordre = 1;
        foreach ($bulletins as $etudiantId => $byEtudiant) {
            $etudiant = $byEtudiant->first()?->etudiant;
            if (! $etudiant) {
                continue;
            }
            $s1 = $byEtudiant->first(fn ($b) => (int) $b->semestre === 1);
            $s2 = $byEtudiant->first(fn ($b) => (int) $b->semestre === 2);
            $moyAnnuelle = ($s1 && $s2)
                ? round(((float) $s1->moyenne_generale + (float) $s2->moyenne_generale) / 2, 2)
                : ($s2?->moyenne_generale ?? $s1?->moyenne_generale);

            $rows[] = [
                'ordre' => $ordre++,
                'matricule' => $etudiant->matricule,
                'nom' => $etudiant->nom,
                'prenoms' => $etudiant->prenoms,
                'date_naissance' => optional($etudiant->date_naissance)->format('d/m/Y'),
                'lieu_naissance' => $etudiant->lieu_naissance,
                'sexe' => $etudiant->sexe,
                'nationalite' => $etudiant->nationalite,
                'moy_s1' => $s1?->moyenne_generale,
                'credits_s1' => $s1?->credits_capitalises,
                'moy_s2' => $s2?->moyenne_generale,
                'credits_s2' => $s2?->credits_capitalises,
                'moy_annuelle' => $moyAnnuelle,
                'credits_annuels' => (int) $s1?->credits_capitalises + (int) $s2?->credits_capitalises,
                'decision' => $decisions[$etudiantId] ?? $s2?->decision_deliberation ?? $s1?->decision_deliberation,
                'ues' => $this->mapUes($byEtudiant),
                'ues_non_validees' => $this->uesNonValidees($byEtudiant),
            ];
        }

        return [
            'ecole' => SettingsHelper::getSchoolInfo(),
            'annee' => $jury->anneeUniversitaire?->name,
            'parcours' => $jury->parcours?->name ?? $jury->classe?->filiere?->name,
            'niveau' => $jury->classe?->niveau?->name,
            'classe' => $jury->classe?->name,
            'ues_header' => $ues->map(fn ($r) => [
                'code' => $r->uniteEnseignement?->code,
                'name' => $r->uniteEnseignement?->name,
                'credit' => $r->credit,
            ])->all(),
            'rows' => $rows,
        ];
    }

    private function mapUes(Collection $bulletins): array
    {
        $out = [];
        foreach ($bulletins as $bulletin) {
            foreach ($bulletin->resultatsUEs as $ue) {
                $out[$ue->unite_enseignement_id] = [
                    'code' => $ue->uniteEnseignement?->code,
                    'name' => $ue->uniteEnseignement?->name,
                    'moyenne' => $ue->moyenne,
                    'mention' => $ue->mention,
                    'credit' => $ue->credit,
                    'statut' => $ue->statut,
                    'ecues' => $ue->resultatsECUEs->map(fn ($e) => $e->moyenne)->all(),
                ];
            }
        }

        return array_values($out);
    }

    private function uesNonValidees(Collection $bulletins): array
    {
        $failed = [];
        foreach ($bulletins as $bulletin) {
            foreach ($bulletin->resultatsUEs as $ue) {
                if (! $ue->isValidee()) {
                    $failed[] = [
                        'ue' => $ue->uniteEnseignement?->code.' — '.$ue->uniteEnseignement?->name,
                        'statut' => $ue->statut,
                        'moyenne' => $ue->moyenne,
                        'ie' => $ue->resultatsECUEs
                            ->filter(fn ($e) => (float) $e->moyenne < 10)
                            ->map(fn ($e) => $e->matiere?->code ?? $e->id)
                            ->values()
                            ->all(),
                    ];
                }
            }
        }

        return $failed;
    }
}
