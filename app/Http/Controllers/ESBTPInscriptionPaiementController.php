<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\Setting;
use App\Services\ComptabiliteService;
use App\Services\ESBTPInscriptionService;
use App\Services\FuzzyNameMatcher;
use App\Services\InscriptionWorkflowService;
use App\Services\StudentDuplicateDetector;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use App\Http\Requests\Inscription\AnnulerInscriptionRequest;
use App\Http\Requests\Inscription\BulkValiderRequest;
use App\Http\Requests\Inscription\ChangerClasseRequest;
use App\Http\Requests\Inscription\CheckDuplicatesRequest;
use App\Http\Requests\Inscription\PayerFraisCategorieRequest;
use App\Http\Requests\Inscription\SubscribeToOptionalFeeRequest;
use App\Http\Requests\Inscription\TransferOverpaymentRequest;
use App\Http\Requests\Inscription\UnsubscribeFromOptionalFeeRequest;
use App\Http\Requests\Inscription\UpdateSubscriptionRequest;
use App\Http\Requests\Inscription\ValiderAvecPaiementRequest;
use App\Http\Requests\Inscription\ValiderDefinitivementRequest;
use App\Http\Requests\Inscription\ValiderInscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ESBTPInscriptionPaiementController extends Controller
{
    private $inscriptionService;
    private $comptabiliteService;
    private $workflowService;

    public function __construct(
        \App\Services\ESBTPInscriptionService $inscriptionService,
        \App\Services\ComptabiliteService $comptabiliteService,
        \App\Services\InscriptionWorkflowService $workflowService,
    ) {
        $this->inscriptionService = $inscriptionService;
        $this->comptabiliteService = $comptabiliteService;
        $this->workflowService = $workflowService;
        $this->middleware('auth');
    }

    /**
     * Valider une inscription avec paiement associé.
     */
    public function validerAvecPaiement(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        $validatePayment = $request->boolean("validate_payment");
        $autoValidateInscription = $request->boolean(
            "auto_validate_inscription",
        );

        if ($autoValidateInscription && !$validatePayment) {
            $message =
                'La validation de l\'inscription nécessite la validation du paiement.';

            if ($request->ajax()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => $message,
                    ],
                    422,
                );
            }

            return redirect()->back()->with("error", $message)->withInput();
        }

        // Validation personnalisée : vérifier que le montant ne dépasse pas le montant restant
        $subscription = \App\Models\ESBTPFraisSubscription::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("frais_category_id", $request->fee_category_id)
            ->first();

        if (!$subscription) {
            return redirect()
                ->back()
                ->withErrors([
                    "fee_category_id" =>
                        'L\'étudiant n\'est pas souscrit à cette catégorie de frais.',
                ])
                ->withInput();
        }

        // Calculer le total déjà payé (validé + en_attente)
        $totalPaye = \App\Models\ESBTPPaiement::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("frais_category_id", $request->fee_category_id)
            ->whereIn("status", ["validé", "en_attente"])
            ->whereNull("deleted_at")
            ->sum("montant");

        $montantRestant = $subscription->amount - $totalPaye;

        // Vérifier que le montant ne dépasse pas le montant restant
        if ($request->montant > $montantRestant) {
            $fraisCategory = \App\Models\ESBTPFraisCategory::find(
                $request->fee_category_id,
            );
            $errorMessage = sprintf(
                'Le montant saisi (%s FCFA) dépasse le montant restant à payer pour "%s" (%s FCFA). Montant total: %s FCFA, Déjà payé: %s FCFA.',
                number_format($request->montant, 0, ",", " "),
                $fraisCategory->name ?? "ce frais",
                number_format($montantRestant, 0, ",", " "),
                number_format($subscription->amount, 0, ",", " "),
                number_format($totalPaye, 0, ",", " "),
            );

            return redirect()
                ->back()
                ->withErrors(["montant" => $errorMessage])
                ->withInput();
        }

        try {
            // Normaliser le mode de paiement (première lettre en majuscule)
            // Pour correspondre aux valeurs du select edit (Espèces, Chèque, Virement bancaire, Mobile Money)
            $modePaiementNormalized = $request->mode_paiement;
            if ($modePaiementNormalized === "especes") {
                $modePaiementNormalized = "Espèces";
            } elseif ($modePaiementNormalized === "cheque") {
                $modePaiementNormalized = "Chèque";
            } elseif ($modePaiementNormalized === "virement") {
                $modePaiementNormalized = "Virement bancaire";
            } elseif ($modePaiementNormalized === "mobile_money") {
                $modePaiementNormalized = "Mobile Money";
            }

            $paiementData = [
                "montant" => $request->montant,
                "fee_category_id" => $request->fee_category_id,
                "mode_paiement" => $modePaiementNormalized,
                "reference_paiement" => $request->reference_paiement,
                "date_paiement" => $request->date_paiement,
                "observations" => $request->observations,
            ];

            $result = $this->workflowService->associerPaiement(
                $inscription,
                $paiementData,
            );

            $paiement = $result["data"]["paiement"] ?? null;
            if (!$paiement && !empty($result["duplicate_id"])) {
                $paiement = \App\Models\ESBTPPaiement::find(
                    $result["duplicate_id"],
                );
            }

            $validationResult = null;
            if ($result["success"] && $paiement && $validatePayment) {
                $paiement->update([
                    "status" => "validé",
                    "date_validation" => now(),
                    "validateur_id" => auth()->id(),
                ]);

                if ($autoValidateInscription) {
                    $validationResult = $this->workflowService->convertProspectToStudent(
                        $inscription->fresh(),
                        $request->input("observations") ?:
                        "Validation directe après paiement",
                    );
                }
            }

            // Si requête AJAX, retourner JSON pour refresh partiel
            if ($request->ajax()) {
                if (
                    $result["success"] &&
                    (!$autoValidateInscription ||
                        ($validationResult && $validationResult["success"]))
                ) {
                    return response()->json([
                        "success" => true,
                        "message" =>
                            $validationResult["message"] ?? $result["message"],
                        "inscription_id" => $inscription->id,
                    ]);
                }

                $errorMessage =
                    $validationResult["message"] ?? $result["message"];

                return response()->json(
                    [
                        "success" => false,
                        "message" => $errorMessage,
                    ],
                    400,
                );
            }

            // Sinon, redirection standard
            if (
                $result["success"] &&
                (!$autoValidateInscription ||
                    ($validationResult && $validationResult["success"]))
            ) {
                return redirect()
                    ->route("esbtp.inscriptions.show", $inscription->id)
                    ->with(
                        "success",
                        $validationResult["message"] ?? $result["message"],
                    );
            }

            return redirect()
                ->back()
                ->with(
                    "error",
                    $validationResult["message"] ?? $result["message"],
                );
        } catch (\Exception $e) {
            Log::error(
                'Erreur lors de l\'association du paiement: ' .
                    $e->getMessage(),
            );

            // Si requête AJAX, retourner JSON d'erreur
            if ($request->ajax()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            'Erreur lors de l\'association du paiement: ' .
                            $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'association du paiement: ' .
                        $e->getMessage(),
                );
        }
    }


    /**
     * Valider définitivement une inscription (conversion prospect -> étudiant).
     */
    public function validerDefinitivement(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        try {
            $result = $this->workflowService->convertProspectToStudent(
                $inscription,
                $request->input("observations"),
            );

            if ($result["success"]) {
                return redirect()
                    ->route("esbtp.inscriptions.show", $inscription->id)
                    ->with("success", $result["message"]);
            } else {
                return redirect()->back()->with("error", $result["message"]);
            }
        } catch (\Exception $e) {
            Log::error(
                "Erreur lors de la validation finale: " . $e->getMessage(),
            );

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la validation: " . $e->getMessage(),
                );
        }
    }


    /**
     * Effectuer un paiement pour une catégorie de frais spécifique.
     */
    public function payerFraisCategorie(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        try {
            DB::beginTransaction();

            // Vérifier que la catégorie de frais est bien configurée pour cette inscription
            $category = ESBTPFraisCategory::findOrFail(
                $request->frais_category_id,
            );

            // Pour les frais optionnels, vérifier qu'il y a une souscription active
            if (!$category->is_mandatory) {
                $subscription = ESBTPFraisSubscription::where(
                    "inscription_id",
                    $inscription->id,
                )
                    ->where("frais_category_id", $category->id)
                    ->where("is_active", true)
                    ->first();

                if (!$subscription) {
                    return redirect()
                        ->back()
                        ->with(
                            "error",
                            'Vous n\'êtes pas souscrit à ce frais optionnel.',
                        );
                }
            }

            // Créer le paiement
            $paiement = ESBTPPaiement::create([
                "inscription_id" => $inscription->id,
                "etudiant_id" => $inscription->etudiant_id,
                "annee_universitaire_id" =>
                    $inscription->annee_universitaire_id,
                "frais_category_id" => $request->frais_category_id,
                "type_paiement" => $category->is_mandatory
                    ? "frais_obligatoire"
                    : "frais_optionnel",
                "motif" => "Paiement " . $category->name,
                "montant" => $request->montant,
                "mode_paiement" => $request->mode_paiement,
                "reference_paiement" => $request->reference_paiement,
                "date_paiement" => $request->date_paiement,
                "commentaire" => $request->commentaire,
                "numero_recu" => ESBTPPaiement::genererNumeroRecu(),
                "status" => "en_attente",
                "created_by" => auth()->id(),
                "updated_by" => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Paiement de " .
                        number_format($request->montant, 0, ",", " ") .
                        " FCFA enregistré avec succès pour " .
                        $category->name,
                );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors du paiement de frais: " . $e->getMessage());

            return redirect()
                ->back()
                ->with(
                    "error",
                    'Erreur lors de l\'enregistrement du paiement: ' .
                        $e->getMessage(),
                );
        }
    }


    /**
     * Transférer un trop-perçu d'une catégorie de frais vers une autre.
     */
    public function transferOverpayment(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        try {
            DB::beginTransaction();

            $sourceCategory = ESBTPFraisCategory::findOrFail(
                $request->source_category_id,
            );

            // Calculer le solde source
            $sourceBalanceInfo = $this->calculerSoldeCategorie(
                $inscription,
                $sourceCategory,
            );

            // Vérifier qu'il y a bien un trop-perçu sur la source
            if ($sourceBalanceInfo["solde"] >= 0) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "Aucun trop-perçu disponible pour cette catégorie de frais.",
                    );
            }

            $availableAmount = abs($sourceBalanceInfo["solde"]);

            // Calculer le total à transférer
            $totalToTransfer = 0;
            $destinationCategories = [];

            foreach ($request->destinations as $destination) {
                $totalToTransfer += $destination["amount"];
                $destinationCategories[] = ESBTPFraisCategory::findOrFail(
                    $destination["category_id"],
                );
            }

            // Vérifier que le total ne dépasse pas le trop-perçu disponible
            if ($totalToTransfer > $availableAmount) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "Le montant total à transférer (" .
                            number_format($totalToTransfer, 0, ",", " ") .
                            " FCFA) " .
                            "dépasse le trop-perçu disponible (" .
                            number_format($availableAmount, 0, ",", " ") .
                            " FCFA).",
                    );
            }

            // Vérifier qu'il n'y a pas de doublons dans les destinations
            $categoryIds = array_column($request->destinations, "category_id");
            if (count($categoryIds) !== count(array_unique($categoryIds))) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "Impossible de transférer vers la même catégorie plusieurs fois.",
                    );
            }

            // Créer une référence unique pour ce transfert multiple
            $transferReference = "MULTI-TRANSFER-" . time();
            $createdPayments = [];

            // Créer les paiements sortants (un seul retrait global)
            $retrait = ESBTPPaiement::create([
                "inscription_id" => $inscription->id,
                "etudiant_id" => $inscription->etudiant_id,
                "annee_universitaire_id" =>
                    $inscription->annee_universitaire_id,
                "frais_category_id" => $sourceCategory->id,
                "type_paiement" => "transfert_sortant_multi",
                "motif" =>
                    "Transfert vers " .
                    count($destinationCategories) .
                    " destinations",
                "montant" => -$totalToTransfer, // Montant négatif pour réduire le trop-perçu
                "mode_paiement" => "transfert",
                "reference_paiement" => $transferReference . "-OUT",
                "date_paiement" => now(),
                "commentaire" =>
                    $request->comment ?:
                    "Transfert multiple automatique de trop-perçu",
                "numero_recu" => ESBTPPaiement::genererNumeroRecu(),
                "status" => "en_attente",
                "created_by" => auth()->id(),
                "updated_by" => auth()->id(),
            ]);

            $createdPayments[] = $retrait;

            // Créer les paiements entrants pour chaque destination
            foreach ($request->destinations as $index => $destination) {
                $destinationCategory = ESBTPFraisCategory::findOrFail(
                    $destination["category_id"],
                );
                $amount = $destination["amount"];

                $credit = ESBTPPaiement::create([
                    "inscription_id" => $inscription->id,
                    "etudiant_id" => $inscription->etudiant_id,
                    "annee_universitaire_id" =>
                        $inscription->annee_universitaire_id,
                    "frais_category_id" => $destinationCategory->id,
                    "type_paiement" => "transfert_entrant_multi",
                    "motif" =>
                        "Transfert depuis " .
                        $sourceCategory->name .
                        " (partie " .
                        ($index + 1) .
                        ")",
                    "montant" => $amount, // Montant positif pour créditer
                    "mode_paiement" => "transfert",
                    "reference_paiement" =>
                        $transferReference . "-IN-" . ($index + 1),
                    "date_paiement" => now(),
                    "commentaire" =>
                        $request->comment ?:
                        "Réception transfert multiple de trop-perçu",
                    "numero_recu" => ESBTPPaiement::genererNumeroRecu(),
                    "status" => "en_attente",
                    "created_by" => auth()->id(),
                    "updated_by" => auth()->id(),
                ]);

                $createdPayments[] = $credit;
            }

            DB::commit();

            // Préparer le message de succès
            $destinationNames = collect($request->destinations)
                ->map(function ($dest) {
                    $category = ESBTPFraisCategory::find($dest["category_id"]);

                    return $category->name .
                        " (" .
                        number_format($dest["amount"], 0, ",", " ") .
                        " FCFA)";
                })
                ->join(", ");

            return redirect()
                ->back()
                ->with(
                    "success",
                    "Transfert multiple de " .
                        number_format($totalToTransfer, 0, ",", " ") .
                        " FCFA effectué avec succès " .
                        "de '{$sourceCategory->name}' vers: " .
                        $destinationNames .
                        ".",
                );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors du transfert multiple de trop-perçu", [
                "inscription_id" => $inscription->id,
                "source_category_id" => $request->source_category_id,
                "destinations" => $request->destinations ?? null,
                "total_amount" => $totalToTransfer ?? null,
                "error" => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors du transfert: " . $e->getMessage(),
                );
        }
    }


    /**
     * Mettre à jour le montant d'une souscription (SuperAdmin uniquement).
     */
    public function updateSubscription(
        Request $request,
        ESBTPInscription $inscription,
        ESBTPFraisSubscription $subscription,
    ) {
        // Vérifier que la souscription appartient bien à cette inscription
        if ($subscription->inscription_id !== $inscription->id) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        'Cette souscription n\'appartient pas à cette inscription.',
                ],
                403,
            );
        }

        try {
            DB::beginTransaction();

            $oldAmount = $subscription->amount;
            $newAmount = $request->amount;

            // Mettre à jour la souscription
            $subscription->update([
                "amount" => $newAmount,
                "updated_at" => now(),
            ]);

            // Créer un log de l'activité pour audit
            Log::info("Modification de souscription par SuperAdmin", [
                "user_id" => auth()->id(),
                "user_name" => auth()->user()->name,
                "inscription_id" => $inscription->id,
                "subscription_id" => $subscription->id,
                "etudiant_matricule" =>
                    $inscription->etudiant->matricule ?? "N/A",
                "frais_category" => $subscription->fraisCategory->name ?? "N/A",
                "old_amount" => $oldAmount,
                "new_amount" => $newAmount,
                "difference" => $newAmount - $oldAmount,
                "reason" => $request->reason,
                "ip_address" => request()->ip(),
                "user_agent" => request()->header("User-Agent"),
            ]);

            // Optionnel: créer une entrée dans une table d'audit si elle existe
            // ESBTPSubscriptionAudit::create([...]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => sprintf(
                    "Souscription mise à jour avec succès. Montant: %s FCFA → %s FCFA (différence: %s%s FCFA)",
                    number_format($oldAmount, 0, ",", " "),
                    number_format($newAmount, 0, ",", " "),
                    $newAmount >= $oldAmount ? "+" : "",
                    number_format($newAmount - $oldAmount, 0, ",", " "),
                ),
                "data" => [
                    "subscription_id" => $subscription->id,
                    "old_amount" => $oldAmount,
                    "new_amount" => $newAmount,
                    "difference" => $newAmount - $oldAmount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erreur lors de la mise à jour de souscription", [
                "user_id" => auth()->id(),
                "inscription_id" => $inscription->id,
                "subscription_id" => $subscription->id,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Erreur lors de la mise à jour de la souscription: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }


    /**
     * Souscrire à un frais optionnel
     */
    public function subscribeToOptionalFee(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        try {
            // Vérifier que c'est bien un frais optionnel
            $category = ESBTPFraisCategory::findOrFail(
                $request->frais_category_id,
            );
            if ($category->is_mandatory) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "Impossible de souscrire à un frais obligatoire.",
                    );
            }

            // Créer la souscription
            ESBTPFraisSubscription::subscribe(
                $inscription->id,
                $request->frais_category_id,
                $request->amount,
                Auth::id(),
                $request->notes,
            );

            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with("success", "Souscription au frais optionnel réussie !");
        } catch (\Exception $e) {
            Log::error(
                "Erreur lors de la souscription au frais optionnel: " .
                    $e->getMessage(),
            );

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors de la souscription au frais optionnel.",
                );
        }
    }


    /**
     * Se désabonner d'un frais optionnel
     */
    public function unsubscribeFromOptionalFee(
        Request $request,
        ESBTPInscription $inscription,
    ) {
        try {
            ESBTPFraisSubscription::unsubscribe(
                $inscription->id,
                $request->frais_category_id,
            );

            return redirect()
                ->route("esbtp.inscriptions.show", $inscription->id)
                ->with("success", "Désabonnement du frais optionnel réussi !");
        } catch (\Exception $e) {
            Log::error(
                "Erreur lors du désabonnement du frais optionnel: " .
                    $e->getMessage(),
            );

            return redirect()
                ->back()
                ->with(
                    "error",
                    "Erreur lors du désabonnement du frais optionnel.",
                );
        }
    }


    /**
     * Prévisualiser la situation financière de l'étudiant pour cette inscription
     */
    public function previewSituationFinanciere(ESBTPInscription $inscription)
    {
        // Charger toutes les données nécessaires
        $inscription->load([
            "etudiant.user",
            "etudiant.parents",
            "filiere",
            "niveau",
            "classe",
            "anneeUniversitaire",
            "paiements" => function ($query) {
                $query
                    ->where("status", "validé")
                    ->orderBy("date_paiement", "desc");
            },
        ]);

        // Récupérer les frais souscrits pour cette inscription
        $fraisSouscrits = ESBTPFraisSubscription::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("is_active", true)
            ->with(["fraisCategory"])
            ->get();

        // Récupérer les reliquats liés à cette inscription (entrants) - comme inscriptions.show
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where(
            "inscription_destination_id",
            $inscription->id,
        )
            ->with([
                "inscriptionSource.anneeUniversitaire",
                "fraisSubscription.fraisCategory",
                "fraisSubscription.selectedOption",
            ])
            ->actifs()
            ->get();

        $totalReliquats = $reliquatsEntrants->sum("solde_restant");

        // Calculer les totaux
        $totalFraisAnnee = $fraisSouscrits->sum("amount"); // Frais année courante seulement
        $totalAttendu = $totalFraisAnnee + $totalReliquats; // Total = Année courante + Reliquats

        // Inclure TOUS les paiements validés (y compris reliquats)
        $totalPaye = $inscription->paiements
            ->where("status", "validé")
            ->sum("montant");

        $soldeRestant = $totalAttendu - $totalPaye;

        // Statistiques
        $statistiques = [
            "total_frais_annee" => $totalFraisAnnee, // Frais année courante uniquement
            "total_attendu" => $totalAttendu, // Frais année + reliquats
            "total_paye" => $totalPaye, // Tous les paiements validés
            "total_reliquats" => $totalReliquats,
            "solde_restant" => $soldeRestant,
            "pourcentage_paye" =>
                $totalAttendu > 0
                    ? round(($totalPaye / $totalAttendu) * 100, 2)
                    : 0,
        ];

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "ESBTP-yAKRO"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", ""),
        ];

        return view(
            "esbtp.inscriptions.situation-financiere-preview",
            compact(
                "inscription",
                "fraisSouscrits",
                "reliquatsEntrants",
                "statistiques",
                "etablissement",
            ),
        );
    }


    /**
     * Exporter la situation financière en PDF
     */
    public function exportSituationFinanciere(ESBTPInscription $inscription)
    {
        // Récupérer les mêmes données que pour la preview
        $inscription->load([
            "etudiant.user",
            "etudiant.parents",
            "filiere",
            "niveau",
            "classe",
            "anneeUniversitaire",
            "paiements" => function ($query) {
                $query
                    ->where("status", "validé")
                    ->orderBy("date_paiement", "desc");
            },
        ]);

        $fraisSouscrits = ESBTPFraisSubscription::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("is_active", true)
            ->with(["fraisCategory"])
            ->get();

        // Récupérer les reliquats liés à cette inscription (entrants) - comme inscriptions.show
        $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where(
            "inscription_destination_id",
            $inscription->id,
        )
            ->with([
                "inscriptionSource.anneeUniversitaire",
                "fraisSubscription.fraisCategory",
                "fraisSubscription.selectedOption",
            ])
            ->actifs()
            ->get();

        $totalReliquats = $reliquatsEntrants->sum("solde_restant");

        // Utiliser la même logique que la page show: PRIORITÉ à la souscription
        $totalFraisAnnee = $fraisSouscrits->sum("amount"); // Frais année courante seulement
        $totalAttendu = $totalFraisAnnee + $totalReliquats; // Total = Année courante + Reliquats

        // Inclure TOUS les paiements validés (y compris reliquats)
        $totalPaye = $inscription->paiements
            ->where("status", "validé")
            ->sum("montant");

        $soldeRestant = $totalAttendu - $totalPaye;

        $statistiques = [
            "total_frais_annee" => $totalFraisAnnee, // Frais année courante uniquement
            "total_attendu" => $totalAttendu, // Frais année + reliquats
            "total_paye" => $totalPaye, // Tous les paiements validés
            "total_reliquats" => $totalReliquats,
            "solde_restant" => $soldeRestant,
            "pourcentage_paye" =>
                $totalAttendu > 0
                    ? round(($totalPaye / $totalAttendu) * 100, 2)
                    : 0,
        ];

        // Récupérer les paramètres de l'établissement
        $etablissement = [
            "nom" => Setting::get("school_name", "ESBTP-yAKRO"),
            "adresse" => Setting::get("school_address", ""),
            "telephone" => Setting::get("school_phone", ""),
            "email" => Setting::get("school_email", ""),
            "logo" => Setting::get("school_logo", null),
        ];

        // Augmenter le temps d'exécution pour le PDF
        set_time_limit(120);
        ini_set("memory_limit", "512M");

        // Générer le PDF
        $pdf = Pdf::loadView(
            "esbtp.inscriptions.situation-financiere-pdf",
            compact(
                "inscription",
                "fraisSouscrits",
                "reliquatsEntrants",
                "statistiques",
                "etablissement",
            ),
        );

        $pdf->setPaper("A4", "portrait");

        // Optimiser les options DomPDF pour les images
        $pdf->setOptions([
            "isHtml5ParserEnabled" => true,
            "isRemoteEnabled" => true,
            "defaultFont" => "DejaVu Sans",
            "dpi" => 96,
            "defaultMediaType" => "print",
            "isFontSubsettingEnabled" => true,
            "isPhpEnabled" => true,
            "margin-top" => 10,
            "margin-right" => 10,
            "margin-bottom" => 10,
            "margin-left" => 10,
        ]);

        $filename =
            "situation_financiere_" .
            $inscription->etudiant->matricule .
            "_" .
            $inscription->anneeUniversitaire->name .
            "_" .
            now()->format("Y-m-d") .
            ".pdf";

        return $pdf->download($filename);
    }


    /**
     * API pour récupérer le montant restant à payer pour une catégorie de frais.
     * Utilisé par le modal de paiement pour valider que le montant ne dépasse pas le solde.
     *
     * @param  int  $category  ID de la catégorie de frais
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMontantRestant(ESBTPInscription $inscription, $category)
    {
        // Vérifier que la catégorie existe
        $fraisCategory = \App\Models\ESBTPFraisCategory::find($category);
        if (!$fraisCategory) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Catégorie de frais introuvable.",
                ],
                404,
            );
        }

        // Récupérer la souscription pour cette inscription + catégorie
        $subscription = \App\Models\ESBTPFraisSubscription::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("frais_category_id", $category)
            ->first();

        // Si l'étudiant n'est pas souscrit à ce frais
        if (!$subscription) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        'L\'étudiant n\'est pas souscrit à cette catégorie de frais.',
                    "is_subscribed" => false,
                ],
                400,
            );
        }

        // Calculer le total déjà payé (validé + en_attente)
        // Exclure les paiements rejetés et soft deleted
        $totalPaye = \App\Models\ESBTPPaiement::where(
            "inscription_id",
            $inscription->id,
        )
            ->where("frais_category_id", $category)
            ->whereIn("status", ["validé", "en_attente"])
            ->whereNull("deleted_at")
            ->sum("montant");

        // Calculer le montant restant
        $montantRestant = $subscription->amount - $totalPaye;

        // Sécurité : si le montant restant est négatif (corruption de données), le forcer à 0
        if ($montantRestant < 0) {
            \Log::warning("Montant restant négatif détecté", [
                "inscription_id" => $inscription->id,
                "category_id" => $category,
                "subscription_amount" => $subscription->amount,
                "total_paye" => $totalPaye,
                "montant_restant_calcule" => $montantRestant,
            ]);
            $montantRestant = 0;
        }

        return response()->json([
            "success" => true,
            "is_subscribed" => true,
            "montant_total" => $subscription->amount,
            "montant_paye" => $totalPaye,
            "montant_restant" => $montantRestant,
            "nom_categorie" => $fraisCategory->name,
        ]);
    }


    /**
     * Calculer le solde d'une catégorie de frais pour une inscription.
     */
    private function calculerSoldeCategorie(
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
    ) {
        // Récupérer la configuration ou le montant par défaut
        $configuration = ESBTPFraisConfiguration::where(
            "frais_category_id",
            $category->id,
        )
            ->where("filiere_id", $inscription->filiere_id)
            ->where("niveau_id", $inscription->niveau_id)
            ->where("is_active", true)
            ->first();

        $montantAttendu = $configuration
            ? $configuration->amount
            : $category->default_amount;

        // Calculer le total payé pour cette catégorie
        $totalPaye = ESBTPPaiement::where("inscription_id", $inscription->id)
            ->where("frais_category_id", $category->id)
            ->where("status", "validé")
            ->sum("montant");

        $solde = $montantAttendu - $totalPaye;

        return [
            "montant_attendu" => $montantAttendu,
            "total_paye" => $totalPaye,
            "solde" => $solde,
            "is_configured" => (bool) $configuration,
        ];
    }


    /**
     * Générer un numéro de reçu unique.
     */
    private function genererNumeroRecu()
    {
        $year = date("Y");
        $month = date("m");
        $prefix = "REC-{$year}{$month}-";

        $lastPayment = ESBTPPaiement::where(
            "numero_recu",
            "like",
            $prefix . "%",
        )
            ->orderBy("numero_recu", "desc")
            ->first();

        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->numero_recu, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, "0", STR_PAD_LEFT);
    }


    /**
     * Régénérer les frais d'inscription après changement de classe/filière/niveau
     */
    private function regenererFraisInscription(ESBTPInscription $inscription)
    {
        try {
            \Log::info("Régénération des frais pour inscription", [
                "inscription_id" => $inscription->id,
                "filiere_id" => $inscription->filiere_id,
                "niveau_id" => $inscription->niveau_id,
                "classe_id" => $inscription->classe_id,
            ]);

            // Charger les relations nécessaires
            $inscription->load(["filiere", "niveau", "classe"]);

            // Récupérer les catégories de frais obligatoires actives
            $categoriesObligatoires = ESBTPFraisCategory::where(
                "is_mandatory",
                true,
            )
                ->where("is_active", true)
                ->orderBy("sort_order")
                ->get();

            foreach ($categoriesObligatoires as $category) {
                // Chercher une configuration de frais pour cette catégorie et cette filière/niveau
                $fraisConfig = ESBTPFraisConfiguration::where(
                    "frais_category_id",
                    $category->id,
                )
                    ->where("filiere_id", $inscription->filiere_id)
                    ->where("niveau_id", $inscription->niveau_id)
                    ->where("is_active", true)
                    ->first();

                if ($fraisConfig) {
                    // Déterminer le montant selon le statut d'affectation
                    $affectationStatus =
                        $inscription->affectation_status ?? "affecté";
                    $montant = $fraisConfig->getMontantByStatus(
                        $affectationStatus,
                    );

                    // Créer ou mettre à jour la souscription (évite la duplication)
                    ESBTPFraisSubscription::updateOrCreate(
                        [
                            "inscription_id" => $inscription->id,
                            "frais_category_id" => $category->id,
                        ],
                        [
                            "selected_option_id" => null,
                            "amount" => $montant,
                            "is_active" => true,
                            "subscribed_at" => now(),
                            "created_by" => Auth::id(),
                            "notes" =>
                                "Régénéré automatiquement après changement de classe/filière/niveau",
                        ],
                    );

                    \Log::info("Souscription créée/mise à jour", [
                        "inscription_id" => $inscription->id,
                        "category_id" => $category->id,
                        "amount" => $montant,
                        "affectation_status" => $affectationStatus,
                    ]);
                }
            }

            // Note: Les frais optionnels ne sont pas automatiquement régénérés
            // L'utilisateur devra les resouscrire manuellement si nécessaire
        } catch (\Exception $e) {
            \Log::error(
                'Erreur lors de la régénération des frais d\'inscription',
                [
                    "inscription_id" => $inscription->id,
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ],
            );
            throw $e;
        }
    }


    /**
     * Vérifier les limites du paywall pour les inscriptions
     */
    private function checkPaywallLimitsForInscription()
    {
        // Vérifier si le paywall est actif
        $isPaywallActive = \App\Models\ESBTPSystemSetting::getValue(
            "paywall_active",
            false,
        );

        if (!$isPaywallActive) {
            return false; // Pas de limitation
        }

        // Obtenir les limites configurées
        $maxInscriptionsPerYear = \App\Models\ESBTPSystemSetting::getValue(
            "paywall_max_inscriptions_per_year",
            500,
        );

        // Compter les inscriptions actuelles pour l'année courante
        $anneeCourante = \App\Models\ESBTPAnneeUniversitaire::where(
            "is_current",
            1,
        )->first();

        if (!$anneeCourante) {
            return false; // Pas d'année courante, on laisse passer
        }

        $inscriptionsActuelles = \App\Models\ESBTPInscription::where(
            "annee_universitaire_id",
            $anneeCourante->id,
        )
            ->where("status", "active")
            ->count();

        // Vérifier si on dépasse la limite
        if ($inscriptionsActuelles >= $maxInscriptionsPerYear) {
            \Log::warning('Paywall: Limite d\'inscriptions atteinte', [
                "inscriptions_actuelles" => $inscriptionsActuelles,
                "limite_configuree" => $maxInscriptionsPerYear,
                "annee_courante" => $anneeCourante->nom,
                "user_id" => auth()->id(),
            ]);

            return true; // Limite atteinte, bloquer
        }

        return false; // Limite pas atteinte, autoriser
    }


    /**
     * Désactiver les rappels pour une inscription
     */
    private function desactiverRappelsInscription($inscriptionId)
    {
        try {
            $reminder = \App\Models\NotificationReminder::where(
                "remindable_type",
                "App\Models\ESBTPInscription",
            )
                ->where("remindable_id", $inscriptionId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Exception $e) {
            Log::error(
                "Erreur désactivation reminder inscription: " .
                    $e->getMessage(),
            );
        }
    }


    /**
     * Désactiver les rappels pour un paiement
     */
    private function desactiverRappelsPaiement($paiementId)
    {
        try {
            $reminder = \App\Models\NotificationReminder::where(
                "remindable_type",
                "App\Models\ESBTPPaiement",
            )
                ->where("remindable_id", $paiementId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Exception $e) {
            Log::error(
                "Erreur désactivation reminder paiement: " . $e->getMessage(),
            );
        }
    }

}
