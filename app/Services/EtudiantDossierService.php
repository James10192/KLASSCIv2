<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNote;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Collection;

class EtudiantDossierService
{
    /**
     * Construit le dossier complet d'un étudiant.
     * L'étudiant doit avoir ses inscriptions chargées avec les relations
     * filiere, niveauEtude, classe, anneeUniversitaire.
     */
    public function buildDossier(ESBTPEtudiant $etudiant): array
    {
        $inscriptionIds = $etudiant->inscriptions->pluck('id');
        $etudiantId     = $etudiant->id;

        return [
            'academique' => $this->getHistoriqueAcademique($etudiant),
            'presences'  => $this->getHistoriquePresences($etudiantId, $etudiant->inscriptions),
            'financier'  => $this->getStatistiquesFinancieres($etudiant, $inscriptionIds),
        ];
    }

    // -------------------------------------------------------------------------
    // ACADEMIQUE
    // -------------------------------------------------------------------------

    /**
     * Retourne le parcours académique groupé par année universitaire.
     * Structure :
     * [
     *   [
     *     'annee'       => ESBTPAnneeUniversitaire,
     *     'inscription' => ESBTPInscription,
     *     'semestres'   => [
     *       'semestre1' => ['moyenne' => float|null, 'rang' => int|null, 'mention' => string, 'bulletin' => ESBTPBulletin|null, 'notes' => Collection],
     *       'semestre2' => [...],
     *     ],
     *     'bulletins'   => Collection,
     *   ],
     *   ...
     * ]
     */
    private function getHistoriqueAcademique(ESBTPEtudiant $etudiant): array
    {
        $historique = [];

        foreach ($etudiant->inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date) as $inscription) {
            $annee    = $inscription->anneeUniversitaire;
            $anneeId  = optional($annee)->id;
            $classeId = optional($inscription->classe)->id;

            // Bulletins publiés pour cet étudiant / cette année
            $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeId)
                ->with(['classe'])
                ->orderBy('periode')
                ->get();

            $semestres = [];
            foreach (['semestre1', 'semestre2'] as $periode) {
                $bulletin = $bulletins->firstWhere('periode', $periode);

                // Notes brutes du semestre (si pas de bulletin calculé)
                $notes = $this->getNotesParSemestre($etudiant->id, $anneeId, $classeId, $periode);

                $moyenne = null;
                $rang    = null;
                $mention = null;

                // Un bulletin "coquille vide" (moyenne_generale NULL, généré avant que les
                // résultats existent) ne doit pas masquer les notes réelles : on le traite
                // comme absent et on retombe sur le calcul depuis les notes.
                if ($bulletin && $bulletin->moyenne_generale !== null) {
                    $moyenne = $bulletin->moyenne_generale;
                    $rang    = $bulletin->rang;
                    $mention = $bulletin->mention;
                } elseif ($notes->isNotEmpty()) {
                    $moyenne = $this->calculerMoyenneDepuisNotes($notes);
                    $mention = $moyenne !== null ? $this->getMention($moyenne) : null;
                }

                $semestres[$periode] = [
                    'moyenne'  => $moyenne,
                    'rang'     => $rang,
                    'mention'  => $mention,
                    'bulletin' => $bulletin,
                    'notes'    => $notes,
                ];
            }

            $historique[] = [
                'annee'       => $annee,
                'inscription' => $inscription,
                'semestres'   => $semestres,
                'bulletins'   => $bulletins,
            ];
        }

        return $historique;
    }

    /**
     * Récupère les notes d'un étudiant pour un semestre donné,
     * groupées par matière pour affichage.
     */
    private function getNotesParSemestre(int $etudiantId, ?int $anneeId, ?int $classeId, string $periode): Collection
    {
        if (!$anneeId) {
            return collect();
        }

        // Ce calcul est aujourd'hui SANS LECTEUR : `$dossier` est passe a
        // `esbtp/etudiants/show.blade.php` et cette vue ne le cite nulle part.
        // Le filtre est pose quand meme, parce que le jour ou quelqu'un
        // rebranchera cet affichage, personne ne relira ce fichier — et une
        // ECUE du LMD y reapparaitrait dans la moyenne d'une classe BTS.
        $classe = $classeId ? ESBTPClasse::find($classeId) : null;

        return ESBTPNote::where('etudiant_id', $etudiantId)
            ->where('semestre', $this->semestreNumero($periode))
            ->whereHas('evaluation', fn($q) => $q->where('annee_universitaire_id', $anneeId))
            ->with(['matiere', 'evaluation'])
            ->get()
            ->filter(fn(ESBTPNote $note) => ! $classe
                || ! $note->matiere
                || CoherenceSystemeAcademique::matiereRetenue($note->matiere, $classe, 'dossier etudiant/note'))
            ->groupBy('matiere_id')
            ->map(function (Collection $notesMatiere) {
                /** @var \App\Models\ESBTPNote $premiere */
                $premiere  = $notesMatiere->first();
                $noteVingt = $notesMatiere->avg(fn($n) => $n->note_vingt);

                return [
                    'matiere'    => $premiere->matiere,
                    'notes'      => $notesMatiere,
                    'moyenne'    => round($noteVingt, 2),
                    'coefficient'=> optional($premiere->evaluation)->coefficient ?? 1,
                ];
            })
            ->values();
    }

    /**
     * Calcule une moyenne générale pondérée à partir d'une collection de notes groupées par matière.
     */
    private function calculerMoyenneDepuisNotes(Collection $notesGroupees): ?float
    {
        if ($notesGroupees->isEmpty()) {
            return null;
        }

        $totalPondere  = 0;
        $totalCoeff    = 0;

        foreach ($notesGroupees as $item) {
            $coeff         = $item['coefficient'] ?? 1;
            $totalPondere += ($item['moyenne'] ?? 0) * $coeff;
            $totalCoeff   += $coeff;
        }

        return $totalCoeff > 0 ? round($totalPondere / $totalCoeff, 2) : null;
    }

    private function getMention(?float $moyenne): string
    {
        if ($moyenne === null) return 'N/A';

        return app(AppreciationScaleService::class)->labelFor($moyenne, 'bts', 'N/A');
    }

    /** Convertit 'semestre1' en 1, 'semestre2' en 2. */
    private function semestreNumero(string $periode): int
    {
        return (int) str_replace('semestre', '', $periode);
    }

    // -------------------------------------------------------------------------
    // PRESENCES
    // -------------------------------------------------------------------------

    /**
     * Retourne le taux de présence par année universitaire.
     * Structure :
     * [
     *   [
     *     'annee'            => ESBTPAnneeUniversitaire,
     *     'total'            => int,
     *     'presences'        => int,
     *     'absences'         => int,
     *     'absences_just'    => int,
     *     'retards'          => int,
     *     'taux_presence'    => float (0-100),
     *   ],
     *   ...
     * ]
     */
    private function getHistoriquePresences(int $etudiantId, Collection $inscriptions): array
    {
        $result = [];

        foreach ($inscriptions->sortByDesc(fn($i) => optional($i->anneeUniversitaire)->start_date) as $inscription) {
            $annee   = $inscription->anneeUniversitaire;
            $anneeId = optional($annee)->id;

            if (!$anneeId) {
                continue;
            }

            $attendances = ESBTPAttendance::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $anneeId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) as nb_presences,
                    SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END) as nb_absences,
                    SUM(CASE WHEN statut = 'excuse' THEN 1 ELSE 0 END) as nb_absences_just,
                    SUM(CASE WHEN statut IN ('retard', 'late') THEN 1 ELSE 0 END) as nb_retards
                ")
                ->first();

            $total   = (int) ($attendances->total ?? 0);
            $present = (int) ($attendances->nb_presences ?? 0);
            $retards = (int) ($attendances->nb_retards ?? 0);
            // Les retardataires comptent comme présents pour le taux de présence
            $presentAvecRetards = $present + $retards;

            $result[] = [
                'annee'         => $annee,
                'total'         => $total,
                'presences'     => $present,
                'absences'      => (int) ($attendances->nb_absences ?? 0),
                'absences_just' => (int) ($attendances->nb_absences_just ?? 0),
                'retards'       => $retards,
                'taux_presence' => $total > 0 ? round(($presentAvecRetards / $total) * 100, 1) : null,
            ];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // FINANCES
    // -------------------------------------------------------------------------

    /**
     * Retourne les statistiques financières globales et par inscription.
     */
    private function getStatistiquesFinancieres(ESBTPEtudiant $etudiant, Collection $inscriptionIds): array
    {
        $paiements = $etudiant->paiements;

        // Reliquats entrants (à payer)
        $reliquatsEntrants = ESBTPReliquatDetail::whereIn('inscription_destination_id', $inscriptionIds)
            ->with(['inscriptionSource.anneeUniversitaire', 'fraisSubscription.fraisCategory'])
            ->actifs()
            ->get();

        // Reliquats sortants (transférés)
        $reliquatsSortants = ESBTPReliquatDetail::whereIn('inscription_source_id', $inscriptionIds)
            ->with(['inscriptionDestination.anneeUniversitaire', 'fraisSubscription.fraisCategory'])
            ->get();

        return [
            'total_paiements'           => ESBTPPaiement::netStudentPaidFrom($paiements) + ESBTPPaiement::pendingEncaissementsFrom($paiements),
            'paiements_valides'         => ESBTPPaiement::netStudentPaidFrom($paiements),
            'paiements_en_attente'      => ESBTPPaiement::pendingEncaissementsFrom($paiements),
            'nombre_paiements'          => $paiements->count(),
            'inscription_active'        => $etudiant->inscriptions->where('status', 'active')->first(),
            'derniere_inscription'      => $etudiant->inscriptions->first(),
            'reliquats_entrants'        => $reliquatsEntrants,
            'reliquats_sortants'        => $reliquatsSortants,
            'total_reliquats_entrants'  => $reliquatsEntrants->sum('solde_restant'),
            'total_reliquats_sortants'  => $reliquatsSortants->sum('solde_restant'),
            'nombre_reliquats_actifs'   => $reliquatsEntrants->where('statut', 'actif')->count(),
        ];
    }
}
