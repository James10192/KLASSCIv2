<?php

namespace App\Services\Documents;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Services\AppreciationScaleService;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\Reinscription\MoyennesAnnuellesDuBulletin;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Source unique de la moyenne affichee sur le certificat de scolarite.
 *
 * Une vraie inscription BTS lit d'abord la moyenne annuelle du bulletin
 * (meme ponderation, meme tronc commun, memes snapshots). Une valeur annuelle
 * historique n'est qu'un repli pour une annee terminee impossible a
 * reconstruire selon la politique annuelle configuree.
 */
class MoyennesAnnuellesDuCertificat
{
    public function __construct(
        private readonly MoyennesAnnuellesDuBulletin $annuelles,
        private readonly BulletinService $bulletins,
        private readonly BtsCurrentResultSnapshotService $snapshots,
        private readonly AppreciationScaleService $appreciations
    ) {}

    public function attacher($inscriptions, int $etudiantId)
    {
        $collection = collect($inscriptions);
        $btsPersistantes = $collection->filter(fn ($inscription) => $this->estBtsPersistante($inscription));
        $canoniques = [];

        if ($btsPersistantes->isNotEmpty()) {
            try {
                $canoniques = $this->annuelles->pour($btsPersistantes);
            } catch (\Throwable $e) {
                Log::warning('Certificat : moyenne annuelle BTS canonique indisponible.', [
                    'etudiant_id' => $etudiantId,
                    'erreur' => $e->getMessage(),
                ]);
            }
        }

        foreach ($collection as $inscription) {
            if ($this->estBtsPersistante($inscription)) {
                $ligne = $canoniques[$inscription->id] ?? null;
                $moyenne = isset($ligne['moyenne']) && $ligne['moyenne'] !== null
                    ? round((float) $ligne['moyenne'], 2)
                    : null;
                $snapshot = null;

                if ($moyenne === null && $this->anneeTerminee($inscription->anneeUniversitaire)) {
                    $snapshot = $this->snapshotAnnuel($inscription);
                    $effective = $snapshot ? $this->bulletins->getEffectiveBulletinAverage($snapshot) : null;
                    $moyenne = $effective !== null ? round((float) $effective, 2) : null;
                }

                $inscription->moyenne_generale_calculee = $moyenne;
                $inscription->moyenne_generale_source = ($ligne['moyenne'] ?? null) !== null
                    ? 'bulletin_canonique'
                    : ($snapshot ? 'snapshot_annuel_historique' : null);
                $inscription->moyenne_historique_existante = $snapshot?->moyenne_generale;
                $inscription->moyenne_historique_saisissable =
                    ($ligne['moyenne'] ?? null) === null
                    && $this->anneeTerminee($inscription->anneeUniversitaire)
                    && $this->snapshotEditable($snapshot);

                continue;
            }

            // Compatibilite des inscriptions LMD et des objets legacy : on garde
            // exactement le repli historique, sans lui confier le calcul BTS reel.
            $inscription->moyenne_generale_calculee = $this->moyenneLegacy($inscription, $etudiantId);
            $inscription->moyenne_generale_source = 'legacy';
            $inscription->moyenne_historique_existante = null;
            $inscription->moyenne_historique_saisissable = false;
        }

        return $inscriptions;
    }

    public function enregistrerHistorique(ESBTPInscription $inscription, float $moyenne, User $user): ESBTPBulletin
    {
        $inscription->loadMissing(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude']);

        if (! $this->estBtsPersistante($inscription)) {
            throw ValidationException::withMessages([
                'moyenne' => 'La saisie manuelle d’une moyenne annuelle historique est reservee aux inscriptions BTS.',
            ]);
        }

        if (! $this->anneeTerminee($inscription->anneeUniversitaire)) {
            throw ValidationException::withMessages([
                'moyenne' => 'La moyenne historique ne peut etre saisie que pour une annee universitaire terminee.',
            ]);
        }

        $canonique = $this->annuelles->pour([$inscription])[$inscription->id]['moyenne'] ?? null;
        if ($canonique !== null) {
            throw ValidationException::withMessages([
                'moyenne' => 'Cette moyenne annuelle est deja calculable selon la regle annuelle configuree. Corrigez les notes ou les bulletins sources plutot que de la remplacer manuellement.',
            ]);
        }

        $bulletin = $this->snapshotAnnuel($inscription, true);
        if (! $this->snapshotEditable($bulletin)) {
            throw ValidationException::withMessages([
                'moyenne' => 'Le bulletin annuel existant est deja configure, publie ou signe et ne peut pas servir de saisie historique.',
            ]);
        }

        if ($bulletin?->trashed()) {
            $bulletin->restore();
        }

        $bulletin ??= new ESBTPBulletin([
            'etudiant_id' => $inscription->etudiant_id,
            'classe_id' => $inscription->classe_id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
            'periode' => 'annuel',
        ]);

        $bulletin->moyenne_generale = round($moyenne, 2);
        $bulletin->note_assiduite = 0;
        $bulletin->rang = null;
        $bulletin->mention = $this->appreciations->labelFor($moyenne, 'bts');
        $bulletin->user_id = $user->id;
        $bulletin->created_by ??= $user->id;
        $bulletin->updated_by = $user->id;
        $bulletin->save();

        return $bulletin;
    }

    private function estBtsPersistante($inscription): bool
    {
        return $inscription instanceof ESBTPInscription
            && $inscription->exists
            && $inscription->id
            && $inscription->etudiant_id
            && $inscription->annee_universitaire_id
            && $inscription->classe
            && $inscription->classe->isBTS();
    }

    private function snapshotAnnuel(ESBTPInscription $inscription, bool $avecSupprimes = false): ?ESBTPBulletin
    {
        $query = $avecSupprimes ? ESBTPBulletin::withTrashed() : ESBTPBulletin::query();

        return $query
            ->withCount('resultatsMatiere')
            ->where('etudiant_id', $inscription->etudiant_id)
            ->where('classe_id', $inscription->classe_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->where('periode', 'annuel')
            ->latest('id')
            ->first();
    }

    private function snapshotEditable(?ESBTPBulletin $bulletin): bool
    {
        if (! $bulletin) {
            return true;
        }

        return ! $bulletin->is_published
            && ! $bulletin->signature_directeur
            && ! $bulletin->signature_responsable
            && ! $bulletin->signature_parent
            && empty($bulletin->config_matieres)
            && (int) ($bulletin->resultats_matiere_count ?? $bulletin->resultatsMatiere()->count()) === 0;
    }

    private function moyenneLegacy($inscription, int $etudiantId): ?float
    {
        $anneeId = optional($inscription->anneeUniversitaire)->id;
        if (! $anneeId) {
            return null;
        }

        $poidsS1 = max(0, (float) SettingsHelper::get('bulletin_semester1_weight', 1));
        $poidsS2 = max(0, (float) SettingsHelper::get('bulletin_semester2_weight', 1));
        if ($poidsS1 + $poidsS2 <= 0) {
            $poidsS1 = $poidsS2 = 1;
        }

        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('moyenne_generale', '>', 0)
            ->get();
        if ($bulletins->isNotEmpty()) {
            return round($bulletins->avg(function (ESBTPBulletin $bulletin) {
                $assiduite = SettingsHelper::drapeau('bulletin_show_attendance_note', true)
                    ? ($bulletin->note_assiduite ?? 0)
                    : 0;

                return $bulletin->moyenne_generale + $assiduite;
            }), 2);
        }

        $resultats = CoherenceSystemeAcademique::resultatsRetenus(
            ESBTPResultat::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $anneeId)
                ->whereNotNull('moyenne')
                ->get(),
            'certificat/moyenne enregistree'
        );

        if ($resultats->isNotEmpty()) {
            $moyenneS1 = $this->moyenneDePeriode($resultats, 'semestre1');
            $moyenneS2 = $this->moyenneDePeriode($resultats, 'semestre2');
            if ($moyenneS1 !== null && $moyenneS2 !== null) {
                return round(($moyenneS1 * $poidsS1 + $moyenneS2 * $poidsS2) / ($poidsS1 + $poidsS2), 2);
            }
            if ($moyenneS1 !== null || $moyenneS2 !== null) {
                return round((float) ($moyenneS1 ?? $moyenneS2), 2);
            }
        }

        if (! $this->anneeTerminee($inscription->anneeUniversitaire)) {
            return null;
        }

        $classeId = $inscription->classe_id ?? null;
        if (! $classeId || (optional($inscription->classe)->systeme_academique ?? '') === 'LMD') {
            return null;
        }

        try {
            $total = $this->snapshots->getAnnualSnapshot($etudiantId, (int) $classeId, $anneeId)['effective_total'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Certificat : moyenne non calculee depuis les notes.', [
                'etudiant_id' => $etudiantId,
                'annee_universitaire_id' => $anneeId,
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }

        return $total !== null ? round((float) $total, 2) : null;
    }

    private function moyenneDePeriode($resultats, string $periode): ?float
    {
        $points = 0.0;
        $coefficients = 0.0;
        foreach ($resultats->where('periode', $periode) as $resultat) {
            $coefficient = (float) ($resultat->coefficient ?? 1);
            $points += (float) $resultat->moyenne * $coefficient;
            $coefficients += $coefficient;
        }

        return $coefficients > 0 ? $points / $coefficients : null;
    }

    private function anneeTerminee($annee): bool
    {
        if (! $annee) {
            return false;
        }
        if (! empty($annee->end_date)) {
            return $annee->estTerminee();
        }

        return ! ($annee->is_current ?? false);
    }
}
