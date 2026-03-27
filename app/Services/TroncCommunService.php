<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TroncCommunService
{
    /**
     * Vérifie si le mode tronc commun est activé globalement.
     */
    public function isTroncCommunEnabled(): bool
    {
        return (bool) SettingsHelper::get('tronc_commun_enabled', false);
    }

    /**
     * Vérifie si une filière est en mode tronc commun.
     */
    public function isFiliereTroncCommun(ESBTPFiliere $filiere): bool
    {
        return $this->isTroncCommunEnabled() && $filiere->isTroncCommun();
    }

    /**
     * Récupère les spécialisations disponibles pour une filière tronc commun.
     */
    public function getSpecialisationsDisponibles(ESBTPFiliere $filiereTroncCommun): Collection
    {
        if (!$filiereTroncCommun->isTroncCommun()) {
            return collect();
        }

        return $filiereTroncCommun->getSpecialisations();
    }

    /**
     * Récupère les classes disponibles pour une spécialisation donnée.
     */
    public function getClassesDisponibles(ESBTPFiliere $specialisation, int $niveauId, int $anneeId): Collection
    {
        return ESBTPClasse::where('filiere_id', $specialisation->id)
            ->where('niveau_etude_id', $niveauId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($classe) => $classe->hasPlacesDisponibles());
    }

    /**
     * Crée l'inscription de spécialisation à partir d'une inscription tronc commun.
     *
     * Flow :
     * 1. Vérifie que l'inscription origine est bien tronc commun
     * 2. Passe l'inscription origine en status 'terminée'
     * 3. Crée la nouvelle inscription avec lien vers l'origine
     * 4. Reporte les paiements si configuré
     * 5. Met à jour les places des classes
     */
    public function creerInscriptionSpecialisation(
        ESBTPInscription $inscriptionOrigine,
        int $classeSpecialisationId
    ): ESBTPInscription {
        $classeSpec = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->findOrFail($classeSpecialisationId);

        // Vérifications
        $filiereTroncCommun = $inscriptionOrigine->filiere;
        if (!$filiereTroncCommun || !$filiereTroncCommun->isTroncCommun()) {
            throw new \InvalidArgumentException("L'inscription d'origine n'est pas sur une filière tronc commun.");
        }

        if (!$classeSpec->filiere || !$classeSpec->filiere->parent_id || $classeSpec->filiere->parent_id !== $filiereTroncCommun->id) {
            throw new \InvalidArgumentException("La classe de spécialisation n'appartient pas à une filière enfant du tronc commun.");
        }

        if (!$classeSpec->hasPlacesDisponibles()) {
            throw new \InvalidArgumentException("La classe de spécialisation n'a plus de places disponibles.");
        }

        if ($inscriptionOrigine->hasSpecialisation()) {
            throw new \InvalidArgumentException("Cette inscription a déjà donné lieu à une spécialisation.");
        }

        return DB::transaction(function () use ($inscriptionOrigine, $classeSpec) {
            // 1. Terminer l'inscription tronc commun
            $inscriptionOrigine->update(['status' => 'terminée']);

            // 2. Créer la nouvelle inscription
            $inscriptionSpec = ESBTPInscription::create([
                'etudiant_id' => $inscriptionOrigine->etudiant_id,
                'annee_universitaire_id' => $inscriptionOrigine->annee_universitaire_id,
                'filiere_id' => $classeSpec->filiere_id,
                'niveau_id' => $inscriptionOrigine->niveau_id,
                'classe_id' => $classeSpec->id,
                'inscription_origine_id' => $inscriptionOrigine->id,
                'type_changement' => 'specialisation',
                'affectation_status' => 'affecté',
                'date_inscription' => now(),
                'type_inscription' => $inscriptionOrigine->type_inscription,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'montant_scolarite' => $inscriptionOrigine->montant_scolarite,
                'frais_inscription' => 0, // Déjà payé sur le tronc commun
                'comptabilite_activee' => $inscriptionOrigine->comptabilite_activee,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // 3. Reporter les paiements si configuré
            if (SettingsHelper::get('tronc_commun_report_paiements', true)) {
                $this->reporterPaiements($inscriptionOrigine, $inscriptionSpec);
            }

            // 4. Mettre à jour les places
            $classeSpec->updatePlacesOccupees();

            // Mettre à jour l'ancienne classe aussi
            if ($inscriptionOrigine->classe) {
                $inscriptionOrigine->classe->updatePlacesOccupees();
            }

            Log::info('Spécialisation créée', [
                'inscription_origine_id' => $inscriptionOrigine->id,
                'inscription_spec_id' => $inscriptionSpec->id,
                'etudiant_id' => $inscriptionOrigine->etudiant_id,
                'filiere_tc' => $inscriptionOrigine->filiere_id,
                'filiere_spec' => $classeSpec->filiere_id,
                'classe_spec' => $classeSpec->id,
            ]);

            return $inscriptionSpec;
        });
    }

    /**
     * Reporte les paiements de l'inscription d'origine vers la spécialisation.
     *
     * Ne duplique pas les paiements — met à jour les metadata pour référencer
     * la nouvelle inscription et génère les frais de la spécialisation.
     */
    public function reporterPaiements(
        ESBTPInscription $origine,
        ESBTPInscription $specialisation
    ): void {
        $paiementsValides = $origine->paiements()->where('status', 'validé')->get();
        $totalPaye = $paiementsValides->sum('montant');

        // Copier les souscriptions de frais actives vers la nouvelle inscription
        $fraisOrigine = $origine->fraisSubscriptions()->where('is_active', true)->get();
        foreach ($fraisOrigine as $frais) {
            ESBTPFraisSubscription::subscribe(
                $specialisation->id,
                $frais->frais_category_id,
                $frais->amount,
                Auth::id(),
                $frais->selected_option_id,
                "Reporté depuis inscription tronc commun #{$origine->id}"
            );
        }

        // Mettre à jour le montant de scolarité sur la nouvelle inscription
        // en tenant compte de ce qui a déjà été payé
        $specialisation->update([
            'montant_scolarite' => $origine->montant_scolarite,
        ]);

        // Marquer les paiements existants avec une référence à la spécialisation via metadata
        foreach ($paiementsValides as $paiement) {
            $metadata = $paiement->metadata ?? [];
            $metadata['reporte_vers_inscription_id'] = $specialisation->id;
            $metadata['type_report'] = 'tronc_commun_specialisation';
            $paiement->update(['metadata' => $metadata]);
        }

        Log::info('Paiements reportés', [
            'origine_id' => $origine->id,
            'specialisation_id' => $specialisation->id,
            'total_paye' => $totalPaye,
            'nb_paiements' => $paiementsValides->count(),
            'nb_frais_reportes' => $fraisOrigine->count(),
        ]);
    }

    /**
     * Récupère les notes du tronc commun (S1) pour une inscription de spécialisation.
     */
    public function getNotesTroncCommun(ESBTPInscription $inscriptionSpecialisation): Collection
    {
        if (!$inscriptionSpecialisation->isSpecialisation()) {
            return collect();
        }

        $origine = $inscriptionSpecialisation->inscriptionOrigine;
        if (!$origine) {
            return collect();
        }

        return ESBTPNote::where('classe_id', $origine->classe_id)
            ->where('etudiant_id', $inscriptionSpecialisation->etudiant_id)
            ->whereHas('evaluation', function ($q) {
                $q->where('periode', 'semestre1');
            })
            ->with(['evaluation', 'matiere'])
            ->get();
    }

    /**
     * Récupère la classe tronc commun d'une inscription de spécialisation.
     */
    public function getClasseTroncCommun(ESBTPInscription $inscriptionSpecialisation): ?ESBTPClasse
    {
        if (!$inscriptionSpecialisation->isSpecialisation()) {
            return null;
        }

        $origine = $inscriptionSpecialisation->inscriptionOrigine;
        return $origine?->classe;
    }

    /**
     * Récupère toutes les inscriptions tronc commun éligibles à la spécialisation
     * pour une année universitaire donnée.
     */
    public function getInscriptionsEligibles(int $anneeUniversitaireId): Collection
    {
        return ESBTPInscription::where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('type_changement')
            ->whereHas('filiere', function ($q) {
                $q->where('is_tronc_commun', true);
            })
            ->doesntHave('inscriptionSpecialisation')
            ->with(['etudiant', 'filiere', 'classe', 'niveau'])
            ->get();
    }
}
