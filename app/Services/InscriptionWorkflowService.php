<?php

namespace App\Services;

use App\Models\ESBTPInscription;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscriptionWorkflowHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InscriptionWorkflowService
{
    /**
     * Valider une inscription (vérifier les prérequis).
     *
     * @param  ESBTPInscription  $inscription
     * @return array
     */
    public function validateInscription(ESBTPInscription $inscription)
    {
        try {
            // Pour les réinscriptions, vérifier le workflow_step au lieu du status
            if ($inscription->type_inscription === 'réinscription' || $inscription->type_inscription === 'reinscription') {
                // Pour les réinscriptions, vérifier le workflow_step
                if ($inscription->workflow_step !== 'en_validation') {
                    return [
                        'success' => false,
                        'message' => 'Cette réinscription a déjà été traitée ou n\'est pas prête pour validation.'
                    ];
                }
            } else {
                // Pour les premières inscriptions, vérifier le status
                if ($inscription->status !== 'en_attente') {
                    return [
                        'success' => false,
                        'message' => 'Seules les inscriptions en attente peuvent être validées.'
                    ];
                }
            }

            // Vérifier qu'un paiement est associé
            if (!$inscription->paiement_validation_id) {
                return [
                    'success' => false,
                    'message' => 'Aucun paiement associé à cette inscription.'
                ];
            }

            // Vérifier que le paiement est validé
            $paiement = ESBTPPaiement::find($inscription->paiement_validation_id);
            if (!$paiement || $paiement->status !== 'validé') {
                return [
                    'success' => false,
                    'message' => 'Le paiement associé n\'est pas validé.'
                ];
            }

            // Vérifier la disponibilité de la classe
            $classAvailability = $this->checkClassAvailability($inscription->classe_id);
            if (!$classAvailability['available']) {
                return [
                    'success' => false,
                    'message' => 'La classe sélectionnée n\'a plus de places disponibles.',
                    'alternatives' => $classAvailability['alternatives']
                ];
            }

            // Vérifier que l'étudiant n'a pas déjà une inscription active pour cette année
            $existingInscription = ESBTPInscription::where('etudiant_id', $inscription->etudiant_id)
                ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                ->where('status', 'active')
                ->where('id', '!=', $inscription->id)
                ->first();

            if ($existingInscription) {
                return [
                    'success' => false,
                    'message' => 'L\'étudiant a déjà une inscription active pour cette année universitaire.'
                ];
            }

            return [
                'success' => true,
                'message' => 'Inscription prête pour validation.',
                'data' => [
                    'paiement' => $paiement,
                    'classe' => $inscription->classe
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation de l\'inscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier la disponibilité d'une classe.
     *
     * @param  int  $classeId
     * @return array
     */
    public function checkClassAvailability($classeId)
    {
        try {
            $classe = ESBTPClasse::find($classeId);
            if (!$classe) {
                return [
                    'available' => false,
                    'message' => 'Classe non trouvée.',
                    'alternatives' => []
                ];
            }

            // Récupérer l'année universitaire courante
            $anneeUniversitaireCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if (!$anneeUniversitaireCourante) {
                Log::warning('Aucune année universitaire courante définie');
                return [
                    'available' => false,
                    'message' => 'Aucune année universitaire courante définie.',
                    'alternatives' => []
                ];
            }

            // Compter les inscriptions actives pour cette classe dans l'année courante uniquement
            $inscriptionsActives = ESBTPInscription::where('classe_id', $classeId)
                ->where('status', 'active')
                ->where('annee_universitaire_id', $anneeUniversitaireCourante->id)
                ->count();

            // Vérifier si la classe a une limite définie
            if ($classe->places_totales && $inscriptionsActives >= $classe->places_totales) {
                // Chercher des classes alternatives
                $alternatives = ESBTPClasse::where('filiere_id', $classe->filiere_id)
                    ->where('niveau_etude_id', $classe->niveau_etude_id)
                    ->where('annee_universitaire_id', $classe->annee_universitaire_id)
                    ->where('id', '!=', $classeId)
                    ->where('is_active', true)
                    ->whereRaw('(places_totales IS NULL OR places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE classe_id = esbtp_classes.id AND status = "active" AND annee_universitaire_id = ?))', [$anneeUniversitaireCourante->id])
                    ->get();

                return [
                    'available' => false,
                    'message' => 'Classe pleine (' . $inscriptionsActives . '/' . $classe->places_totales . ')',
                    'alternatives' => $alternatives
                ];
            }

            return [
                'available' => true,
                'message' => 'Places disponibles',
                'places_restantes' => $classe->places_totales ? ($classe->places_totales - $inscriptionsActives) : null,
                'alternatives' => []
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de la disponibilité: ' . $e->getMessage());
            return [
                'available' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage(),
                'alternatives' => []
            ];
        }
    }

    /**
     * Convertir un prospect en étudiant (finaliser l'inscription).
     *
     * @param  ESBTPInscription  $inscription
     * @param  string|null  $observations
     * @return array
     */
    public function convertProspectToStudent(ESBTPInscription $inscription, $observations = null)
    {
        try {
            DB::beginTransaction();

            // Valider l'inscription d'abord
            $validation = $this->validateInscription($inscription);
            if (!$validation['success']) {
                return $validation;
            }

            // Mettre à jour le statut de l'inscription
            $oldWorkflowStep = $inscription->workflow_step;
            $inscription->update([
                'workflow_step' => 'etudiant_cree',
                'status' => 'active',
                'date_validation' => now(),
                'validated_by' => Auth::id(),
            ]);

            // Activer le compte utilisateur de l'étudiant s'il existe
            $etudiant = $inscription->etudiant;
            if ($etudiant && $etudiant->user) {
                $etudiant->user->update([
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            // Mettre à jour le statut de l'étudiant
            $etudiant->update([
                'statut' => 'actif',
            ]);

            // Enregistrer dans l'historique
            ESBTPInscriptionWorkflowHistory::createEntry(
                $inscription->id,
                $oldWorkflowStep,
                'etudiant_cree',
                'validation_finale',
                Auth::id(),
                'Conversion prospect → étudiant - ' . $observations,
                [
                    'validation_date' => now(),
                    'validated_by' => Auth::id(),
                    'paiement_id' => $inscription->paiement_validation_id,
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Prospect converti en étudiant avec succès.',
                'data' => [
                    'etudiant' => $etudiant,
                    'inscription' => $inscription->fresh()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la conversion prospect → étudiant: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la conversion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Associer un paiement à une inscription.
     *
     * @param  ESBTPInscription  $inscription
     * @param  array  $paiementData
     * @return array
     */
    public function associerPaiement(ESBTPInscription $inscription, array $paiementData)
    {
        try {
            DB::beginTransaction();

            // Récupérer la catégorie de frais pour le motif
            $fraisCategory = \App\Models\ESBTPFraisCategory::find($paiementData['fee_category_id']);
            $motif = $fraisCategory ? $fraisCategory->name : 'Frais d\'inscription';

            // Créer le paiement
            $paiement = ESBTPPaiement::create([
                'inscription_id' => $inscription->id,
                'etudiant_id' => $inscription->etudiant_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'type_paiement' => 'inscription', // Type de paiement pour validation d'inscription
                'frais_category_id' => $paiementData['fee_category_id'],
                'montant' => $paiementData['montant'],
                'mode_paiement' => $paiementData['mode_paiement'],
                'reference_paiement' => $paiementData['reference_paiement'] ?? null,
                'date_paiement' => $paiementData['date_paiement'],
                'commentaire' => $paiementData['observations'] ?? null,
                'motif' => $motif,
                'status' => 'en_attente',
                'numero_recu' => ESBTPPaiement::genererNumeroRecu(),
                'created_by' => Auth::id(),
            ]);

            // Mettre à jour l'inscription
            $oldWorkflowStep = $inscription->workflow_step;
            $inscription->update([
                'paiement_validation_id' => $paiement->id,
                'workflow_step' => 'en_validation',
                'comptabilite_activee' => true,
                'updated_by' => Auth::id(),
            ]);

            // Enregistrer dans l'historique
            ESBTPInscriptionWorkflowHistory::createEntry(
                $inscription->id,
                $oldWorkflowStep,
                'en_validation',
                'paiement_associe',
                Auth::id(),
                'Paiement associé - ' . ($paiementData['observations'] ?? ''),
                [
                    'paiement_id' => $paiement->id,
                    'montant' => $paiementData['montant'],
                    'mode_paiement' => $paiementData['mode_paiement'],
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paiement associé avec succès.',
                'data' => [
                    'paiement' => $paiement,
                    'inscription' => $inscription->fresh()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'association du paiement: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'association du paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Changer la classe d'une inscription.
     *
     * @param  ESBTPInscription  $inscription
     * @param  int  $nouvelleClasseId
     * @param  string|null  $motif
     * @return array
     */
    public function changerClasse(ESBTPInscription $inscription, $nouvelleClasseId, $motif = null)
    {
        try {
            DB::beginTransaction();

            // Vérifier la disponibilité de la nouvelle classe
            $availability = $this->checkClassAvailability($nouvelleClasseId);
            if (!$availability['available']) {
                return [
                    'success' => false,
                    'message' => $availability['message'],
                    'alternatives' => $availability['alternatives']
                ];
            }

            $ancienneClasse = $inscription->classe;
            $nouvelleClasse = ESBTPClasse::find($nouvelleClasseId);

            // Mettre à jour l'inscription
            $inscription->update([
                'classe_id' => $nouvelleClasseId,
                'updated_by' => Auth::id(),
            ]);

            // Enregistrer dans l'historique
            ESBTPInscriptionWorkflowHistory::createEntry(
                $inscription->id,
                $inscription->workflow_step,
                $inscription->workflow_step,
                'changement_classe',
                Auth::id(),
                'Changement de classe - ' . $motif,
                [
                    'ancienne_classe_id' => $ancienneClasse->id,
                    'ancienne_classe_nom' => $ancienneClasse->nom,
                    'nouvelle_classe_id' => $nouvelleClasse->id,
                    'nouvelle_classe_nom' => $nouvelleClasse->nom,
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Classe changée avec succès.',
                'data' => [
                    'ancienne_classe' => $ancienneClasse,
                    'nouvelle_classe' => $nouvelleClasse,
                    'inscription' => $inscription->fresh()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du changement de classe: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors du changement de classe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir l'historique du workflow d'une inscription.
     *
     * @param  int  $inscriptionId
     * @return array
     */
    public function getWorkflowHistory($inscriptionId)
    {
        try {
            $history = ESBTPInscriptionWorkflowHistory::where('inscription_id', $inscriptionId)
                ->with('user')
                ->orderBy('action_timestamp', 'desc')
                ->get();

            return [
                'success' => true,
                'data' => $history
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Générer un numéro de reçu unique.
     *
     * @return string
     */
    private function genererNumeroRecu()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "REC-{$year}{$month}-";
        
        $lastPayment = ESBTPPaiement::where('numero_recu', 'like', $prefix . '%')
            ->orderBy('numero_recu', 'desc')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->numero_recu, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
} 