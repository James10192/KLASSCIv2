<?php

namespace App\Services;

use App\Models\ESBTPDepense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WorkflowService
{
    /**
     * Les statuts possibles pour les dépenses
     */
    const STATUTS_DEPENSES = [
        'brouillon',
        'en_attente',
        'approuve',
        'paye',
        'rejete'
    ];

    /**
     * Transitions autorisées entre statuts
     */
    const TRANSITIONS_AUTORISEES = [
        'brouillon' => ['en_attente'],
        'en_attente' => ['approuve', 'rejete'],
        'approuve' => ['paye'],
        'rejete' => ['en_attente'], // Possibilité de soumettre à nouveau
        'paye' => [] // État final
    ];

    /**
     * Approuve une dépense
     */
    public function approuverDepense($depenseId, $userId, $commentaire = null)
    {
        try {
            DB::beginTransaction();

            $depense = ESBTPDepense::findOrFail($depenseId);
            $user = User::findOrFail($userId);

            // Vérifier les permissions
            if (!$this->verifierPermissionsApprobation($userId, $depenseId)) {
                throw new \Exception('Permissions insuffisantes pour approuver cette dépense');
            }

            // Vérifier que la transition est autorisée
            if (!$this->transitionAutorisee($depense->statut_workflow, 'approuve')) {
                throw new \Exception("Transition non autorisée de '{$depense->statut_workflow}' vers 'approuve'");
            }

            // Générer un numéro de bon si pas déjà fait
            if (!$depense->numero_bon) {
                $depense->numero_bon = $this->genererNumeroBon();
            }

            // Mettre à jour les données workflow
            $workflowData = $depense->workflow_data ? json_decode($depense->workflow_data, true) : [];
            $workflowData[] = [
                'action' => 'approbation',
                'utilisateur_id' => $userId,
                'utilisateur_nom' => $user->name,
                'date' => Carbon::now()->toISOString(),
                'commentaire' => $commentaire,
                'statut_precedent' => $depense->statut_workflow,
                'statut_nouveau' => 'approuve'
            ];

            // Mettre à jour la dépense
            $depense->update([
                'statut_workflow' => 'approuve',
                'approved_by' => $userId,
                'date_approbation' => Carbon::now(),
                'workflow_data' => json_encode($workflowData)
            ]);

            DB::commit();

            Log::info("Dépense {$depenseId} approuvée par l'utilisateur {$userId}");

            return [
                'success' => true,
                'message' => 'Dépense approuvée avec succès',
                'depense' => $depense->fresh(),
                'numero_bon' => $depense->numero_bon
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'approbation de la dépense {$depenseId}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Rejette une dépense
     */
    public function rejeterDepense($depenseId, $userId, $motif)
    {
        try {
            DB::beginTransaction();

            $depense = ESBTPDepense::findOrFail($depenseId);
            $user = User::findOrFail($userId);

            // Vérifier les permissions
            if (!$this->verifierPermissionsApprobation($userId, $depenseId)) {
                throw new \Exception('Permissions insuffisantes pour rejeter cette dépense');
            }

            // Vérifier que la transition est autorisée
            if (!$this->transitionAutorisee($depense->statut_workflow, 'rejete')) {
                throw new \Exception("Transition non autorisée de '{$depense->statut_workflow}' vers 'rejete'");
            }

            // Mettre à jour les données workflow
            $workflowData = $depense->workflow_data ? json_decode($depense->workflow_data, true) : [];
            $workflowData[] = [
                'action' => 'rejet',
                'utilisateur_id' => $userId,
                'utilisateur_nom' => $user->name,
                'date' => Carbon::now()->toISOString(),
                'motif' => $motif,
                'statut_precedent' => $depense->statut_workflow,
                'statut_nouveau' => 'rejete'
            ];

            // Mettre à jour la dépense
            $depense->update([
                'statut_workflow' => 'rejete',
                'approved_by' => null,
                'date_approbation' => null,
                'workflow_data' => json_encode($workflowData)
            ]);

            DB::commit();

            Log::info("Dépense {$depenseId} rejetée par l'utilisateur {$userId}");

            return [
                'success' => true,
                'message' => 'Dépense rejetée',
                'depense' => $depense->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors du rejet de la dépense {$depenseId}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Change le statut d'une dépense avec validation
     */
    public function changerStatutDepense($depenseId, $nouveauStatut, $userId, $donnees = [])
    {
        try {
            DB::beginTransaction();

            $depense = ESBTPDepense::findOrFail($depenseId);
            $user = User::findOrFail($userId);

            // Vérifier que le nouveau statut est valide
            if (!in_array($nouveauStatut, self::STATUTS_DEPENSES)) {
                throw new \Exception("Statut '{$nouveauStatut}' non valide");
            }

            // Vérifier que la transition est autorisée
            if (!$this->transitionAutorisee($depense->statut_workflow, $nouveauStatut)) {
                throw new \Exception("Transition non autorisée de '{$depense->statut_workflow}' vers '{$nouveauStatut}'");
            }

            // Mettre à jour les données workflow
            $workflowData = $depense->workflow_data ? json_decode($depense->workflow_data, true) : [];
            $workflowData[] = [
                'action' => 'changement_statut',
                'utilisateur_id' => $userId,
                'utilisateur_nom' => $user->name,
                'date' => Carbon::now()->toISOString(),
                'statut_precedent' => $depense->statut_workflow,
                'statut_nouveau' => $nouveauStatut,
                'donnees_supplementaires' => $donnees
            ];

            // Mettre à jour la dépense
            $updateData = [
                'statut_workflow' => $nouveauStatut,
                'workflow_data' => json_encode($workflowData)
            ];

            // Cas spéciaux selon le statut
            if ($nouveauStatut === 'approuve') {
                $updateData['approved_by'] = $userId;
                $updateData['date_approbation'] = Carbon::now();

                if (!$depense->numero_bon) {
                    $updateData['numero_bon'] = $this->genererNumeroBon();
                }
            } elseif ($nouveauStatut === 'rejete') {
                $updateData['approved_by'] = null;
                $updateData['date_approbation'] = null;
            }

            $depense->update($updateData);

            DB::commit();

            Log::info("Statut de la dépense {$depenseId} changé vers '{$nouveauStatut}' par l'utilisateur {$userId}");

            return [
                'success' => true,
                'message' => "Statut changé vers '{$nouveauStatut}' avec succès",
                'depense' => $depense->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors du changement de statut de la dépense {$depenseId}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupère l'historique du workflow pour une dépense
     */
    public function getHistoriqueWorkflow($depenseId)
    {
        $depense = ESBTPDepense::find($depenseId);

        if (!$depense) {
            return [
                'success' => false,
                'message' => 'Dépense non trouvée'
            ];
        }

        $historique = $depense->workflow_data ? json_decode($depense->workflow_data, true) : [];

        // Ajouter l'état actuel
        $historique[] = [
            'action' => 'etat_actuel',
            'statut' => $depense->statut_workflow,
            'date' => $depense->updated_at->toISOString(),
            'numero_bon' => $depense->numero_bon,
            'approuve_par' => $depense->approved_by ? User::find($depense->approved_by)?->name : null,
            'date_approbation' => $depense->date_approbation?->toISOString()
        ];

        return [
            'success' => true,
            'historique' => $historique,
            'depense' => $depense
        ];
    }

    /**
     * Récupère les dépenses en attente d'approbation
     */
    public function getDepensesEnAttente($userId = null)
    {
        $query = ESBTPDepense::with(['categorie', 'createur'])
            ->where('statut_workflow', 'en_attente')
            ->orderBy('created_at', 'asc');

        // Si un utilisateur est spécifié, filtrer selon ses permissions
        if ($userId) {
            $user = User::find($userId);
            if ($user && !$user->hasPermissionTo('comptabilite.bons.approve')) {
                return collect([]);
            }
        }

        return $query->get();
    }

    /**
     * Vérifie les permissions d'approbation pour un utilisateur
     */
    public function verifierPermissionsApprobation($userId, $depenseId = null)
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // Vérifier la permission générale d'approbation
        if (!$user->hasPermissionTo('comptabilite.bons.approve')) {
            return false;
        }

        // Vérifications supplémentaires si nécessaire
        if ($depenseId) {
            $depense = ESBTPDepense::find($depenseId);

            if (!$depense) {
                return false;
            }

            // L'utilisateur ne peut pas approuver sa propre dépense
            if ($depense->createur_id === $userId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie si une transition entre statuts est autorisée
     */
    private function transitionAutorisee($statutActuel, $nouveauStatut)
    {
        if (!isset(self::TRANSITIONS_AUTORISEES[$statutActuel])) {
            return false;
        }

        return in_array($nouveauStatut, self::TRANSITIONS_AUTORISEES[$statutActuel]);
    }

    /**
     * Génère un numéro de bon unique
     */
    private function genererNumeroBon()
    {
        $prefix = 'BON';
        $date = date('Ymd');

        // Trouver le prochain numéro séquentiel pour la journée
        $dernierBon = ESBTPDepense::where('numero_bon', 'like', "{$prefix}-{$date}-%")
            ->orderBy('numero_bon', 'desc')
            ->first();

        if ($dernierBon) {
            $parts = explode('-', $dernierBon->numero_bon);
            $sequence = intval(end($parts)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Récupère les statistiques du workflow
     */
    public function getStatistiquesWorkflow($dateDebut = null, $dateFin = null)
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now()->startOfMonth();
        $dateFin = $dateFin ? Carbon::parse($dateFin) : Carbon::now()->endOfMonth();

        $query = ESBTPDepense::whereBetween('created_at', [$dateDebut, $dateFin]);

        $stats = [
            'total' => $query->count(),
            'par_statut' => [],
            'temps_approbation_moyen' => 0,
            'taux_approbation' => 0
        ];

        // Statistiques par statut
        foreach (self::STATUTS_DEPENSES as $statut) {
            $stats['par_statut'][$statut] = (clone $query)->where('statut_workflow', $statut)->count();
        }

        // Temps d'approbation moyen (en heures)
        $depensesApprouvees = (clone $query)
            ->where('statut_workflow', 'approuve')
            ->whereNotNull('date_approbation')
            ->get();

        if ($depensesApprouvees->count() > 0) {
            $tempsTotal = 0;
            foreach ($depensesApprouvees as $depense) {
                $tempsTotal += $depense->created_at->diffInHours($depense->date_approbation);
            }
            $stats['temps_approbation_moyen'] = round($tempsTotal / $depensesApprouvees->count(), 2);
        }

        // Taux d'approbation
        $totalTraitees = $stats['par_statut']['approuve'] + $stats['par_statut']['rejete'];
        if ($totalTraitees > 0) {
            $stats['taux_approbation'] = round(($stats['par_statut']['approuve'] / $totalTraitees) * 100, 2);
        }

        return $stats;
    }

    /**
     * Soumet une dépense pour approbation
     */
    public function soumettrePourApprobation($depenseId, $userId)
    {
        return $this->changerStatutDepense($depenseId, 'en_attente', $userId, [
            'action' => 'soumission_approbation'
        ]);
    }

    /**
     * Marque une dépense comme payée
     */
    public function marquerCommePaye($depenseId, $userId, $referencesPaiement = [])
    {
        return $this->changerStatutDepense($depenseId, 'paye', $userId, [
            'action' => 'paiement',
            'references_paiement' => $referencesPaiement,
            'date_paiement' => Carbon::now()->toISOString()
        ]);
    }
}
