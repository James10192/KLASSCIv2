<?php

namespace App\Services;

use App\Models\ESBTP\FeeCategory;
use App\Models\ESBTP\FeeCategoryRule;
use App\Models\ESBTP\FeeCategoryRuleInstallment;
use App\Models\ESBTP\Fee;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class FeeAssignmentService
{
    /**
     * Génère les lignes de frais (Fee) pour une inscription donnée.
     *
     * @param ESBTPInscription $inscription
     * @param array $selectedOptionals
     * @return array Liste des frais générés
     */
    public function assignFeesToInscription(ESBTPInscription $inscription, array $selectedOptionals = []): array
    {
        $generatedFees = [];
        $filiereId = $inscription->filiere_id;
        $niveauId = $inscription->niveau_id;
        $anneeId = $inscription->annee_universitaire_id;
        $classId = $inscription->classe_id;

        // Pour chaque catégorie de frais active
        $categories = FeeCategory::where('is_active', true)->get();
        foreach ($categories as $category) {
            // Ne générer les frais optionnels que si sélectionnés
            if (!$category->is_mandatory && !in_array($category->id, $selectedOptionals)) {
                continue;
            }
            // Récupérer la règle applicable
            $rule = FeeCategoryRule::getApplicableRule($category->id, $filiereId, $niveauId, $anneeId);
            if (!$rule) {
                Log::warning('Aucune règle de frais applicable pour la catégorie', [
                    'category_id' => $category->id,
                    'filiere_id' => $filiereId,
                    'niveau_id' => $niveauId,
                    'annee_id' => $anneeId
                ]);
                continue;
            }

            // Si la règle autorise les échéances, générer une ligne Fee par échéance
            if ($rule->installments_allowed) {
                $installments = $rule->installments()->orderBy('offset_days')->get();
                $totalAmount = $rule->amount;
                foreach ($installments as $inst) {
                    $amount = $inst->amount ?? null;
                    if ($amount === null && $inst->pourcentage) {
                        $amount = round($totalAmount * $inst->pourcentage / 100, 2);
                    }
                    if ($amount === null) {
                        // Si aucun montant, ignorer cette échéance
                        continue;
                    }
                    $dueDate = Carbon::parse($inscription->date_inscription)->addDays($inst->offset_days);
                    // Vérifier qu'un frais identique n'existe pas déjà
                    $existingFee = Fee::where('fee_category_id', $category->id)
                        ->where('inscription_id', $inscription->id)
                        ->whereDate('due_date', $dueDate)
                        ->first();
                    if ($existingFee) {
                        continue;
                    }
                    $fee = Fee::create([
                        'fee_category_id' => $category->id,
                        'class_id' => $classId,
                        'academic_year_id' => $anneeId,
                        'inscription_id' => $inscription->id,
                        'amount' => $amount,
                        'description' => $inst->label ?? $category->name,
                        'due_date' => $dueDate,
                        'payment_schedule' => $rule->payment_schedule,
                        'installments_allowed' => true,
                        'min_installment_amount' => $rule->min_installment_amount,
                        'late_fee' => $rule->late_fee,
                        'status' => 'pending',
                    ]);
                    $generatedFees[] = $fee;
                }
            } else {
                // Paiement unique : une seule ligne Fee
                $dueDate = Carbon::parse($inscription->date_inscription);
                // Vérifier qu'un frais identique n'existe pas déjà
                $existingFee = Fee::where('fee_category_id', $category->id)
                    ->where('inscription_id', $inscription->id)
                    ->whereDate('due_date', $dueDate)
                    ->first();
                if ($existingFee) {
                    continue;
                }
                $fee = Fee::create([
                    'fee_category_id' => $category->id,
                    'class_id' => $classId,
                    'academic_year_id' => $anneeId,
                    'inscription_id' => $inscription->id,
                    'amount' => $rule->amount,
                    'description' => $category->name,
                    'due_date' => $dueDate,
                    'payment_schedule' => $rule->payment_schedule,
                    'installments_allowed' => false,
                    'min_installment_amount' => $rule->min_installment_amount,
                    'late_fee' => $rule->late_fee,
                    'status' => 'pending',
                ]);
                $generatedFees[] = $fee;
            }
        }
        // Lier les frais générés à l'inscription (si besoin, via une relation many-to-many ou autre)
        // $inscription->fees()->sync($generatedFees);
        return $generatedFees;
    }
}
