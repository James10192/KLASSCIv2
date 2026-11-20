<?php

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDJuryDecision;
use App\Models\ESBTPLMDResultatUE;
use Illuminate\Support\Collection;

/**
 * Le procès-verbal de délibération détaillé d'un semestre : une ligne par
 * étudiant, et pour chaque UE la note de chaque ECUE, la moyenne de l'UE,
 * l'année d'obtention et la session.
 *
 * C'est le document que les universités tiennent à la main sous Excel (modèle
 * USAT, novembre 2026). Il n'invente aucune note : tout vient des bulletins
 * LMD que KLASSCI a calculés avec les règles de l'établissement (pondération,
 * compensation). Un tableur recalcule souvent autrement, et un PV qui
 * contredirait les bulletins serait pire que pas de PV.
 */
class PvSemestreDetailleAssembler
{
    public const SESSION_NORMALE = 'SN';
    public const SESSION_RATTRAPAGE = 'SR';

    public function forJury(ESBTPLMDJury $jury): array
    {
        $jury->loadMissing(['anneeUniversitaire', 'parcours', 'classe.filiere', 'classe.niveau']);

        $bulletins = ESBTPLMDBulletin::query()
            ->forJury($jury)
            ->with(['etudiant', 'resultatsUEs.uniteEnseignement', 'resultatsUEs.resultatsECUEs.matiere'])
            ->get()
            ->sortBy(fn ($b) => mb_strtoupper(($b->etudiant?->nom ?? '').' '.($b->etudiant?->prenoms ?? ''), 'UTF-8'))
            ->values();

        $decisions = ESBTPLMDJuryDecision::query()->where('jury_id', $jury->id)->pluck('decision', 'etudiant_id');
        $annee = $this->anneeCivile($jury->anneeUniversitaire);
        $acquises = $this->anneesDAcquisitionAnterieures($jury, $bulletins, $annee);
        $ues = $this->colonnes($bulletins);

        $rows = [];
        foreach ($bulletins as $i => $bulletin) {
            $rows[] = $this->ligne($i + 1, $bulletin, $ues, $decisions, $annee, $acquises);
        }

        return [
            'ecole' => SettingsHelper::getSchoolInfo(),
            'annee' => $jury->anneeUniversitaire?->name ?? $jury->anneeUniversitaire?->libelle,
            'departement' => $jury->classe?->filiere?->name,
            'parcours' => $jury->parcours?->name,
            'niveau' => $jury->classe?->niveau?->name,
            'classe' => $jury->classe?->name,
            'semestre' => $jury->semestre,
            'date_jury' => optional($jury->date_jury)->format('d/m/Y'),
            'ues' => $ues,
            'rows' => $rows,
            'statistiques' => $this->statistiques($rows),
        ];
    }

    /**
     * Les UE du semestre et, pour chacune, ses ECUE : l'union de ce que portent
     * les bulletins, dans l'ordre du code. Un étudiant dispensé d'un élément a
     * une case vide, pas une colonne en moins.
     */
    private function colonnes(Collection $bulletins): array
    {
        $ues = [];
        foreach ($bulletins as $bulletin) {
            foreach ($bulletin->resultatsUEs as $ue) {
                $id = $ue->unite_enseignement_id;
                $ues[$id] ??= [
                    'id' => $id,
                    'code' => $ue->uniteEnseignement?->code_affiche,
                    'name' => $ue->uniteEnseignement?->name,
                    'credit' => $ue->credit,
                    'ecues' => [],
                ];
                foreach ($ue->resultatsECUEs as $ecue) {
                    $ues[$id]['ecues'][$ecue->matiere_id] ??= [
                        'id' => $ecue->matiere_id,
                        'code' => $ecue->matiere?->code_affiche,
                        'name' => $ecue->matiere?->name ?? 'Élément',
                    ];
                }
            }
        }

        $ues = array_values($ues);
        usort($ues, fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));
        foreach ($ues as &$ue) {
            $ue['ecues'] = array_values($ue['ecues']);
            usort($ue['ecues'], fn ($a, $b) => strcmp((string) $a['code'], (string) $b['code']));
        }

        return $ues;
    }

    private function ligne(int $ordre, ESBTPLMDBulletin $bulletin, array $ues, Collection $decisions, ?string $annee, array $acquises): array
    {
        $etudiant = $bulletin->etudiant;
        $parUe = $bulletin->resultatsUEs->keyBy('unite_enseignement_id');
        $cellules = [];
        $rattrapage = false;
        $aDesNotes = false;

        foreach ($ues as $colonne) {
            /** @var ESBTPLMDResultatUE|null $ue */
            $ue = $parUe->get($colonne['id']);
            $parEcue = $ue?->resultatsECUEs->keyBy('matiere_id') ?? collect();
            $notes = [];
            foreach ($colonne['ecues'] as $ecue) {
                $resultat = $parEcue->get($ecue['id']);
                // La note que les bulletins LMD impriment (`moyenne`, session
                // normale), jamais `note_finale` : le PV et le bulletin d'un
                // étudiant passé au rattrapage ne doivent pas se contredire.
                $note = $resultat?->moyenne;
                $notes[] = $note;
                $aDesNotes = $aDesNotes || $note !== null;
            }
            $session = $ue && $ue->resultatsECUEs->contains(fn ($e) => $e->note_rattrapage !== null)
                ? self::SESSION_RATTRAPAGE : self::SESSION_NORMALE;
            $rattrapage = $rattrapage || ($ue && $session === self::SESSION_RATTRAPAGE);
            $cellules[] = [
                'notes' => $notes,
                'moyenne' => $ue?->moyenne,
                'validee' => (bool) $ue?->isValidee(),
                'annee' => $ue?->isValidee() ? ($acquises[$bulletin->etudiant_id.':'.$colonne['id']] ?? $annee) : null,
                'session' => $ue ? $session : null,
            ];
        }

        $code = $decisions[$bulletin->etudiant_id] ?? $bulletin->decision_deliberation;

        return [
            'ordre' => $ordre,
            'matricule' => $etudiant?->matricule,
            'nom' => $etudiant?->nom,
            'prenoms' => $etudiant?->prenoms,
            'date_naissance' => optional($etudiant?->date_naissance)->format('d/m/Y'),
            'lieu_naissance' => $etudiant?->lieu_naissance,
            'sexe' => $etudiant?->sexe,
            'nationalite' => $etudiant?->nationalite,
            'ues' => $cellules,
            'moyenne' => $bulletin->moyenne_generale,
            'credits' => (int) $bulletin->credits_capitalises,
            'credits_totaux' => (int) $bulletin->credits_totaux,
            'decision_code' => $code,
            'decision' => ESBTPLMDJuryDecision::libelleDecision($code),
            // Présent : une note d'élément saisie, ou une moyenne de semestre
            // calculée. Les notes seules ne suffisent pas : un bulletin peut
            // porter sa moyenne sans le détail des UE, et l'étudiant délibéré
            // apparaissait alors « absent » à côté de sa propre décision.
            'present' => $aDesNotes || $bulletin->moyenne_generale !== null,
            'annee' => $annee,
            'session' => $rattrapage ? self::SESSION_RATTRAPAGE : self::SESSION_NORMALE,
        ];
    }

    /** Effectifs du pied de PV : inscrits, présents, absents, puis une ligne par décision. */
    private function statistiques(array $rows): array
    {
        $inscrits = count($rows);
        $presents = count(array_filter($rows, fn ($r) => $r['present']));
        $pct = fn (int $n) => $inscrits > 0 ? round($n * 100 / $inscrits, 1) : 0.0;

        $lignes = [
            ['libelle' => 'Inscrits', 'nombre' => $inscrits, 'pourcentage' => $inscrits > 0 ? 100.0 : 0.0],
            ['libelle' => 'Présents', 'nombre' => $presents, 'pourcentage' => $pct($presents)],
            ['libelle' => 'Absents', 'nombre' => $inscrits - $presents, 'pourcentage' => $pct($inscrits - $presents)],
        ];
        $parDecision = [];
        foreach ($rows as $row) {
            if ($row['decision'] !== null) {
                $parDecision[$row['decision']] = ($parDecision[$row['decision']] ?? 0) + 1;
            }
        }
        foreach ($parDecision as $libelle => $nombre) {
            $lignes[] = ['libelle' => $libelle, 'nombre' => $nombre, 'pourcentage' => $pct($nombre)];
        }

        return $lignes;
    }

    /**
     * L'année où chaque UE a réellement été acquise, quand c'est avant l'année
     * du jury : un redoublant garde ses UE capitalisées, et le PV doit dire
     * l'année où il les a obtenues, pas l'année où on le délibère.
     *
     * La source est le bulletin de l'année d'acquisition (UE acquise ou
     * acquise par compensation). Le portefeuille de crédits ne suffit pas : il
     * n'enregistre qu'un total par bulletin, pas le détail par UE.
     *
     * @return array<string, string> « etudiant:ue » => année civile
     */
    private function anneesDAcquisitionAnterieures(ESBTPLMDJury $jury, Collection $bulletins, ?string $anneeDuJury): array
    {
        $ueIds = $bulletins->flatMap(fn ($b) => $b->resultatsUEs->pluck('unite_enseignement_id'))->unique()->values();
        if ($ueIds->isEmpty()) {
            return [];
        }

        $anterieures = ESBTPLMDResultatUE::query()
            ->whereIn('etudiant_id', $bulletins->pluck('etudiant_id')->unique())
            ->whereIn('unite_enseignement_id', $ueIds)
            ->whereIn('statut', [ESBTPLMDResultatUE::STATUT_AQ, ESBTPLMDResultatUE::STATUT_APC])
            // Un bulletin publié seulement : un brouillon n'est pas une acquisition.
            ->whereHas('bulletin', fn ($q) => $q->where('is_published', true)
                ->where('annee_universitaire_id', '!=', $jury->annee_universitaire_id))
            ->with('bulletin.anneeUniversitaire')
            ->get();

        $annees = [];
        foreach ($anterieures as $resultat) {
            $annee = $this->anneeCivile($resultat->bulletin?->anneeUniversitaire);
            $cle = $resultat->etudiant_id.':'.$resultat->unite_enseignement_id;
            // Antérieure au jury seulement : une UE acquise APRÈS la session (PV
            // régénéré plus tard) n'est pas une UE capitalisée. Quatre chiffres
            // des deux côtés : l'ordre des chaînes est l'ordre des années.
            $anterieure = $annee !== null && ($anneeDuJury === null || $annee < $anneeDuJury);
            if ($anterieure && (! isset($annees[$cle]) || $annee < $annees[$cle])) {
                $annees[$cle] = $annee;
            }
        }

        return $annees;
    }

    /** L'année civile qui clôt une année universitaire : « 2025-2026 » donne 2026. */
    private function anneeCivile($anneeUniversitaire): ?string
    {
        $libelle = (string) ($anneeUniversitaire?->name ?? $anneeUniversitaire?->libelle ?? '');

        return preg_match_all('/\d{4}/', $libelle, $m) ? end($m[0]) : null;
    }
}
