<?php

namespace App\Services;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPParent;
use App\Models\ESBTPResultat;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Services\InscriptionWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use App\Models\ESBTPClasse;
use Illuminate\Support\Str;
use App\Support\MatriculeGenerator;

class ESBTPInscriptionService
{
    protected MatriculeGenerator $matriculeGenerator;

    public function __construct(MatriculeGenerator $matriculeGenerator)
    {
        $this->matriculeGenerator = $matriculeGenerator;
    }

    /**
     * Créer une nouvelle inscription d'étudiant
     *
     * @param array $etudiantData Données de l'étudiant
     * @param array $inscriptionData Données de l'inscription
     * @param array $parentsData Données des parents [optionnel]
     * @param array|null $paiementData Données de paiement initial [optionnel]
     * @param int $userId ID de l'utilisateur qui crée l'inscription
     * @param array $selectedOptionals Additional options for FeeAssignmentService
     * @param string $affectationStatus Statut d'affectation pour le calcul des frais
     * @return ESBTPInscription
     */
    public function createInscription(array $etudiantData, array $inscriptionData, array $parentsData = [], ?array $paiementData = null, int $userId = null, array $selectedOptionals = [], string $affectationStatus = ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
    {
        try {
            DB::beginTransaction();

            // 1. Vérification des données minimales requises
            if (empty($etudiantData['nom']) || empty($etudiantData['prenoms'])) {
                throw new \Exception("Les informations de base de l'étudiant sont manquantes");
            }

            if (empty($inscriptionData['classe_id'])) {
                throw new \Exception("Une classe doit être sélectionnée pour l'inscription");
            }

            // 2. Récupération des données de la classe pour remplir les données de l'inscription
            $classe = ESBTPClasse::with(['filiere', 'niveau', 'annee'])->findOrFail($inscriptionData['classe_id']);

            // S'assurer que les données de filière, niveau et année sont disponibles
            if (!$classe->filiere_id || !$classe->niveau_etude_id || !$classe->annee_universitaire_id) {
                throw new \Exception("La classe sélectionnée n'a pas toutes les informations requises");
            }

            // 3. Préparer les données de l'étudiant pour la création
            $etudiantData['filiere_id'] = $classe->filiere_id;
            $etudiantData['niveau_etude_id'] = $classe->niveau_etude_id;
            // CORRECTION: L'année universitaire de l'étudiant doit suivre l'inscription, pas la classe
            // L'année sera définie lors de la création de l'inscription
            $etudiantData['created_by'] = $userId;
            $etudiantData['updated_by'] = $userId;

            // Convertir sexe en genre si nécessaire
            if (isset($etudiantData['sexe']) && !isset($etudiantData['genre'])) {
                $etudiantData['genre'] = $etudiantData['sexe'];
            }

            // Statut par défaut pour un nouvel étudiant
            $etudiantData['statut'] = 'actif';

            // 4. Créer l'étudiant et récupérer son instance
            $etudiant = $this->createEtudiant($etudiantData, $userId, $classe->annee_universitaire_id);

            // Si le statut est 'actif', on active également le compte utilisateur
            if (isset($inscriptionData['status']) && $inscriptionData['status'] === 'active') {
                $etudiant->statut = 'actif';
                $etudiant->save();

                if ($etudiant->user_id) {
                    $user = User::find($etudiant->user_id);
                    if ($user) {
                        $user->is_active = true;
                        $user->save();
                    }
                }
            }

            // 5. Préparer les données d'inscription
            $inscriptionData['etudiant_id'] = $etudiant->id;
            // CORRECTION: Ne pas surcharger l'année universitaire si elle est déjà définie par le contrôleur
            if (!isset($inscriptionData['annee_universitaire_id'])) {
                $inscriptionData['annee_universitaire_id'] = $classe->annee_universitaire_id;
            }
            $inscriptionData['filiere_id'] = $classe->filiere_id;
            $inscriptionData['niveau_id'] = $classe->niveau_etude_id;
            $inscriptionData['date_inscription'] = $inscriptionData['date_inscription'] ?? now()->format('Y-m-d');
            $inscriptionData['type_inscription'] = $inscriptionData['type_inscription'] ?? 'PREMIERE';
            $inscriptionData['status'] = $inscriptionData['status'] ?? 'en_attente';
            $inscriptionData['created_by'] = $userId;
            $inscriptionData['updated_by'] = $userId;

            // Générer un numéro de reçu si nécessaire
            if (empty($inscriptionData['numero_recu'])) {
                $annee = date('Y');
                $anneeCode = $classe->annee->code ?? $annee;
                $inscriptionData['numero_recu'] = 'INSC-' . $anneeCode . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            // 6. Créer l'inscription
            $inscription = ESBTPInscription::create($inscriptionData);

            // 6bis. Générer automatiquement les frais selon la nouvelle architecture
            // IMPORTANT: Générer les frais pour TOUS les types d'inscription (première inscription ET réinscription)
            $generatedFees = $this->generateFeesForInscription($inscription, $selectedOptionals, $affectationStatus);

            // 6bis-2. Sauvegarder les frais générés comme ESBTPFraisSubscription
            $this->saveGeneratedFeesAsSubscriptions($inscription, $generatedFees);

            // 6ter. Générer automatiquement la facture liée à l'inscription
            $facture = new \App\Models\ESBTPFacture();
            $facture->numero_facture = 'FAC-' . date('Ymd') . '-' . str_pad($inscription->id, 5, '0', STR_PAD_LEFT);
            $facture->etudiant_id = $inscription->etudiant_id;
            $facture->inscription_id = $inscription->id;
            $facture->annee_universitaire_id = $inscription->annee_universitaire_id;
            $facture->date_emission = now();
            $facture->date_echeance = now()->addDays(15); // Par défaut 15 jours après inscription
            $facture->montant_ht = collect($generatedFees)->sum('amount');
            $facture->taux_taxe = 0; // À adapter si TVA
            $facture->montant_taxe = 0; // À adapter si TVA
            $facture->montant_ttc = $facture->montant_ht + $facture->montant_taxe;
            $facture->montant_regle = 0;
            $facture->montant_du = $facture->montant_ttc;
            $facture->statut = 'émise';
            $facture->notes = 'Facture générée automatiquement à l\'inscription';
            $facture->createur_id = $userId;
            $facture->save();
            // Générer les détails de la facture à partir des frais
            foreach ($generatedFees as $fee) {
                \App\Models\ESBTPFactureDetail::create([
                    'facture_id' => $facture->id,
                    'designation' => $fee['description'],
                    'description' => null,
                    'quantite' => 1,
                    'montant' => $fee['amount'],
                    'total_ligne' => $fee['amount'],
                    'prix_unitaire' => $fee['amount'] ?? 0,
                ]);
            }

            // 7. Traiter les parents s'ils sont fournis
            if (!empty($parentsData)) {
                $this->attachParentsToEtudiant($etudiant, $parentsData, $userId);
            }

            // 8. Enregistrement du paiement initial (si fourni)
            if ($paiementData && !empty($paiementData)) {
                $paiementData['inscription_id'] = $inscription->id;
                $paiementData['etudiant_id'] = $etudiant->id;
                $paiementData['created_by'] = $userId;
                $paiementData['updated_by'] = $userId;

                // Générer un numéro de reçu
                if (empty($paiementData['numero_recu'])) {
                    $paiementData['numero_recu'] = ESBTPPaiement::genererNumeroRecu();
                }

                ESBTPPaiement::create($paiementData);
            }

            DB::commit();

            return $inscription;

        } catch (\Exception $e) {
            DB::rollBack();

            // Ajout de logs pour déboguer
            Log::error('Erreur lors de la création de l\'inscription', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'etudiantData' => $etudiantData ?? null,
                'inscriptionData' => $inscriptionData ?? null,
                'parentsData' => $parentsData ?? null,
                'paiementData' => $paiementData ?? null
            ]);

            throw $e;
        }
    }

    /**
     * Créer un nouvel étudiant et son compte utilisateur.
     *
     * @param array $etudiantData
     * @param int $userId
     * @param int|null $anneeUniversitaireId
     * @return ESBTPEtudiant
     */
    private function createEtudiant(array $etudiantData, int $userId, ?int $anneeUniversitaireId = null)
    {
        // Générer un username unique basé sur le prénom et le nom
        $prenoms = explode(' ', $etudiantData['prenoms']);
        $prenom = strtolower($prenoms[0] ?? '');
        $nom = strtolower($etudiantData['nom'] ?? '');

        // Translitérer les accents avant la regex (ex: é→e, '→ supprimé)
        $translit = [
            'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
            'è'=>'e','ê'=>'e','ë'=>'e','é'=>'e',
            'ì'=>'i','î'=>'i','ï'=>'i','í'=>'i',
            'ò'=>'o','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o',
            'ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
            'ÿ'=>'y','ý'=>'y',
            'ç'=>'c','ñ'=>'n',
            "'"=>'',"\u{2019}"=>'' // apostrophe simple + typographique (U+2019)
        ];
        $prenom = strtr($prenom, $translit);
        $nom    = strtr($nom,    $translit);

        // Créer un username basé sur le prénom et le nom
        $baseUsername = $prenom . '.' . $nom;
        $baseUsername = preg_replace('/[^a-z0-9.]/', '', $baseUsername);
        $username = $baseUsername;

        // withTrashed() : les users soft-deleted occupent toujours leur username
        // (même logique que pour les matricules — cf. commit 0ee6748)
        $count = 1;
        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $baseUsername . '.' . $count;
            $count++;
        }

        // Générer un email basé sur le username
        $baseEmail = $username . '@esbtp.edu';
        $email = $baseEmail;
        $count = 1;
        while (User::withTrashed()->where('email', $email)->exists()) {
            $email = str_replace('@', '.' . $count . '@', $baseEmail);
            $count++;
        }

        // Générer un mot de passe aléatoire
        $password = Str::random(10);

        $user = User::create([
            'name' => $etudiantData['prenoms'] . ' ' . $etudiantData['nom'],
            'first_name' => $etudiantData['prenoms'],
            'last_name' => $etudiantData['nom'],
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($password),
            'is_active' => true
        ]);

        // Enregistrer le mot de passe généré en session pour l'afficher plus tard
        session()->put('generated_password', $password);

        // Assigner le rôle étudiant
        $role = Role::where('name', 'etudiant')->first();
        if ($role) {
            $user->assignRole($role);
        }

        $etudiantData['user_id'] = $user->id;

        if (!isset($etudiantData['matricule']) || trim((string) $etudiantData['matricule']) === '') {
            $etudiantData['matricule'] = $this->matriculeGenerator->generate([
                'genre' => $etudiantData['genre'] ?? $etudiantData['sexe'] ?? 'M',
                'filiere_id' => $etudiantData['filiere_id'] ?? null,
                'niveau_id' => $etudiantData['niveau_etude_id'] ?? null,
                'annee_universitaire_id' => $anneeUniversitaireId,
            ]);
        }

        // Créer l'étudiant
        $etudiant = ESBTPEtudiant::create($etudiantData);

        return $etudiant;
    }

    /**
     * Attache les parents à un étudiant (existants ou nouveaux)
     *
     * @param ESBTPEtudiant $etudiant L'étudiant auquel attacher les parents
     * @param array $parentsData Données des parents
     * @param int $userId ID de l'utilisateur qui fait l'action
     * @return void
     */
    private function attachParentsToEtudiant(ESBTPEtudiant $etudiant, array $parentsData, int $userId)
    {
        foreach ($parentsData as $index => $parentData) {
            $isTuteur = $index === 0; // Le premier parent est le tuteur par défaut

            try {
                // Parent existant sélectionné
                if (isset($parentData['parent_id']) && !empty($parentData['parent_id'])) {
                    $parent = ESBTPParent::findOrFail($parentData['parent_id']);

                    // Associer le parent existant à l'étudiant
                    $etudiant->parents()->syncWithoutDetaching([
                        $parent->id => [
                            'relation' => $parentData['relation'] ?? 'Tuteur',
                            'is_tuteur' => $isTuteur
                        ]
                    ]);
                }
                // Nouveau parent
                elseif (isset($parentData['nom']) && !empty($parentData['nom'])) {
                    // Créer le nouveau parent
                    $parent = ESBTPParent::create([
                        'nom' => $parentData['nom'],
                        'prenoms' => $parentData['prenoms'],
                        'telephone' => $parentData['telephone'] ?? null,
                        'email' => $parentData['email'] ?? null,
                        'profession' => $parentData['profession'] ?? null,
                        'adresse' => $parentData['adresse'] ?? null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    // Associer le nouveau parent à l'étudiant
                    $etudiant->parents()->attach($parent->id, [
                        'relation' => $parentData['relation'] ?? 'Tuteur',
                        'is_tuteur' => $isTuteur
                    ]);
                } else {
                    Log::warning('Données de parent incomplètes ignorées', ['parentData' => $parentData]);
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'attachement d\'un parent', [
                    'message' => $e->getMessage(),
                    'parentData' => $parentData
                ]);
                // On continue malgré l'erreur pour traiter les autres parents
            }
        }

    }

    /**
     * Valider une inscription
     *
     * @param int $inscriptionId ID de l'inscription à valider
     * @param int $userId ID de l'utilisateur qui valide l'inscription
     * @return array Résultat de l'opération
     */
    public function validerInscription(int $inscriptionId, int $userId)
    {
        try {
            DB::beginTransaction();

            $inscription = ESBTPInscription::findOrFail($inscriptionId);

            // Ne pas valider une inscription déjà complètement validée
            if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Cette inscription est déjà validée'
                ];
            }

            $paiement = $inscription->paiement_validation_id
                ? ESBTPPaiement::find($inscription->paiement_validation_id)
                : null;

            if (! $paiement || $paiement->status !== 'validé') {
                $autrePaiementValide = $inscription->paiements()
                    ->where('status', 'validé')
                    ->first();

                if ($autrePaiementValide) {
                    $inscription->update([
                        'paiement_validation_id' => $autrePaiementValide->id
                    ]);
                    $paiement = $autrePaiementValide;
                }
            }

            if (! $paiement || $paiement->status !== 'validé') {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Aucun paiement validé trouvé sur cette inscription.'
                ];
            }
            $inscription->status = 'active';
            $inscription->workflow_step = 'etudiant_cree';
            $inscription->date_validation = now();
            $inscription->validated_by = $userId;
            $inscription->updated_by = $userId;
            $inscription->save();

            // Mettre à jour le statut de l'étudiant
            $etudiant = $inscription->etudiant;
            $etudiant->statut = 'actif';
            $etudiant->updated_by = $userId;
            $etudiant->save();

            // Activer le compte utilisateur de l'étudiant
            if ($etudiant->user_id) {
                $user = User::find($etudiant->user_id);
                if ($user) {
                    $user->is_active = true;
                    $user->save();

                    // Stocker les informations du compte dans la session
                    session()->flash('account_info', [
                        'username' => $user->username,
                        'password' => session('generated_password'),
                        'role' => 'Étudiant'
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'inscription' => $inscription,
                'message' => 'Inscription validée avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la validation de l\'inscription: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'inscription: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Annuler une inscription
     *
     * @param int $inscriptionId ID de l'inscription à annuler
     * @param string $motif Motif de l'annulation
     * @param int $userId ID de l'utilisateur qui annule l'inscription
     * @return array Résultat de l'opération
     */
    public function annulerInscription(int $inscriptionId, string $motif, int $userId)
    {
        try {
            DB::beginTransaction();

            $inscription = ESBTPInscription::findOrFail($inscriptionId);

            // Ne pas annuler une inscription déjà annulée
            if ($inscription->status === 'annulée') {
                return [
                    'success' => false,
                    'message' => 'Cette inscription est déjà annulée'
                ];
            }

            $inscription->status = 'annulée';
            $inscription->observations = ($inscription->observations ? $inscription->observations . "\n" : '') .
                                        "Annulée le " . now()->format('d/m/Y') . ". Motif: " . $motif;
            $inscription->updated_by = $userId;
            $inscription->save();

            // Désactiver l'étudiant si c'est sa seule inscription active
            $etudiant = $inscription->etudiant;
            $hasActiveInscriptions = $etudiant->inscriptions()
                                            ->where('id', '!=', $inscriptionId)
                                            ->where('status', 'active')
                                            ->exists();

            if (!$hasActiveInscriptions) {
                $etudiant->statut = 'inactif';
                $etudiant->updated_by = $userId;
                $etudiant->save();

                // Désactiver le compte utilisateur de l'étudiant
                if ($etudiant->user_id) {
                    $user = User::find($etudiant->user_id);
                    if ($user) {
                        $user->is_active = false;
                        $user->save();
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'inscription' => $inscription,
                'message' => 'Inscription annulée avec succès'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'annulation de l\'inscription: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'inscription: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Générer les frais pour une inscription selon la nouvelle architecture
     *
     * @param ESBTPInscription $inscription
     * @param array $selectedOptionals Frais optionnels sélectionnés
     * @param string $affectationStatus Statut d'affectation pour le calcul des frais
     * @return array Liste des frais générés
     */
    public function generateFeesForInscription(ESBTPInscription $inscription, array $selectedOptionals = [], string $affectationStatus = ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
    {
        $generatedFees = [];
        
        try {
            $resolver = app(\App\Services\ApplicableFraisResolver::class);
            $mandatoryFees = $resolver->resolveMandatoryFeesForInscription($inscription, $affectationStatus);

            foreach ($mandatoryFees as $fee) {
                $generatedFees[] = [
                    'id' => 'mandatory_' . $fee['category']->id,
                    'category_id' => $fee['category']->id,
                    'description' => $fee['description'],
                    'amount' => $fee['amount'],
                    'type' => 'mandatory',
                    'configuration_id' => $fee['configuration']?->id,
                ];
            }

            foreach ($selectedOptionals as $categoryId => $optionData) {
                if (empty($optionData['variant_id']) || $optionData['variant_id'] === 'none') {
                    continue;
                }

                $category = \App\Models\ESBTPFraisCategory::find($categoryId);
                if (! $category || $category->is_mandatory) {
                    continue;
                }

                $optionId = null;
                $amount = (float) ($category->default_amount ?? 0);
                $description = $category->name . ' (standard)';

                if ($optionData['variant_id'] !== 'default') {
                    $option = \App\Models\ESBTPFraisOption::find($optionData['variant_id']);
                    if (! $option) {
                        continue;
                    }

                    $optionId = $option->id;
                    $amount = (float) ($option->additional_amount ?? 0);
                    $description = $category->name . ' - ' . $option->name;
                }

                $generatedFees[] = [
                    'id' => 'optional_' . $categoryId . '_' . $optionData['variant_id'],
                    'category_id' => $categoryId,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => 'optional',
                    'option_id' => $optionId,
                ];
            }

            $this->createOptionalFeeSubscriptions($inscription, $selectedOptionals);

            return $generatedFees;

            // 1. Charger la classe de l'inscription
            $classe = $inscription->classe;
            if (!$classe) {
                Log::warning('Classe non trouvée pour l\'inscription', ['inscription_id' => $inscription->id]);
                return [];
            }

            // 2. Générer les frais obligatoires
            $mandatoryCategories = \App\Models\ESBTPFraisCategory::where('is_mandatory', true)
                ->where('is_active', true)
                ->get();

            foreach ($mandatoryCategories as $category) {
                // Chercher une configuration spécifique pour cette classe
                $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $category->id)
                    ->where('filiere_id', $classe->filiere_id)
                    ->where('niveau_id', $classe->niveau_etude_id)
                    ->where('is_active', true)
                    ->first();

                // Utiliser getMontantByStatus pour prendre en compte le statut d'affectation
                $amount = $configuration ? $configuration->getMontantByStatus($affectationStatus) : $category->default_amount;

                $generatedFees[] = [
                    'id' => 'mandatory_' . $category->id,
                    'category_id' => $category->id,
                    'description' => $category->name,
                    'amount' => $amount,
                    'type' => 'mandatory',
                    'configuration_id' => $configuration ? $configuration->id : null
                ];
            }

            // 3. Traiter les frais optionnels sélectionnés
            foreach ($selectedOptionals as $categoryId => $optionData) {
                if (empty($optionData['variant_id']) || $optionData['variant_id'] === 'none') {
                    continue; // Pas d'option sélectionnée pour cette catégorie
                }

                $category = \App\Models\ESBTPFraisCategory::find($categoryId);
                if (!$category) {
                    Log::warning('Catégorie de frais non trouvée', ['category_id' => $categoryId]);
                    continue;
                }

                // IMPORTANT: Skip les catégories obligatoires car elles sont déjà générées automatiquement
                if ($category->is_mandatory) {
                    Log::warning('Catégorie obligatoire ignorée dans selectedOptionals', [
                        'category_id' => $categoryId,
                        'category_name' => $category->name,
                        'message' => 'Les frais obligatoires sont générés automatiquement et ne doivent pas être dans selectedOptionals'
                    ]);
                    continue;
                }

                if ($optionData['variant_id'] === 'default') {
                    // Option par défaut - utiliser le montant configuré ou par défaut
                    $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $categoryId)
                        ->where('filiere_id', $classe->filiere_id)
                        ->where('niveau_id', $classe->niveau_etude_id)
                        ->where('is_active', true)
                        ->first();

                    $amount = $configuration ? $configuration->amount : $category->default_amount;
                    $description = $category->name . ' (standard)';
                } else {
                    // Option spécifique
                    $option = \App\Models\ESBTPFraisOption::find($optionData['variant_id']);
                    if (!$option) {
                        Log::warning('Option de frais non trouvée', ['option_id' => $optionData['variant_id']]);
                        continue;
                    }

                    $amount = $option->additional_amount ?: $option->amount;
                    $description = $category->name . ' - ' . $option->name;
                }

                $generatedFees[] = [
                    'id' => 'optional_' . $categoryId . '_' . $optionData['variant_id'],
                    'category_id' => $categoryId,
                    'description' => $description,
                    'amount' => $amount,
                    'type' => 'optional',
                    'option_id' => $optionData['variant_id'] !== 'default' ? $optionData['variant_id'] : null
                ];
            }

            // 4. Créer les souscriptions pour les frais optionnels
            $this->createOptionalFeeSubscriptions($inscription, $selectedOptionals);

            return $generatedFees;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des frais', [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * Créer les souscriptions aux frais optionnels sélectionnés
     *
     * @param ESBTPInscription $inscription
     * @param array $selectedOptionals
     * @return void
     */
    private function createOptionalFeeSubscriptions(ESBTPInscription $inscription, array $selectedOptionals = [])
    {
        try {
            foreach ($selectedOptionals as $categoryId => $optionData) {
                if (empty($optionData['variant_id']) || $optionData['variant_id'] === 'none') {
                    continue; // Pas d'option sélectionnée pour cette catégorie
                }

                $category = \App\Models\ESBTPFraisCategory::find($categoryId);
                if (!$category || $category->is_mandatory) {
                    continue; // Skip si catégorie introuvable ou obligatoire
                }

                // Déterminer l'option et le montant
                $optionId = null;
                $amount = $category->default_amount;

                if ($optionData['variant_id'] !== 'default') {
                    $option = \App\Models\ESBTPFraisOption::find($optionData['variant_id']);
                    if ($option) {
                        $optionId = $option->id;
                        $amount = $option->additional_amount ?: $option->amount;
                    }
                }

                // Vérifier si la souscription existe déjà
                $existingSubscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $categoryId)
                    ->first();

                if (!$existingSubscription) {
                    \App\Models\ESBTPFraisSubscription::create([
                        'inscription_id' => $inscription->id,
                        'frais_category_id' => $categoryId,
                        'selected_option_id' => $optionId,
                        'amount' => $amount,
                        'is_active' => true,
                        'subscribed_at' => $inscription->date_inscription ?? now(),
                        'created_by' => $inscription->created_by ?? auth()->id(),
                        'notes' => 'Souscription créée automatiquement lors de l\'inscription'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création des souscriptions', [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarder les frais générés comme ESBTPFraisSubscription
     * IMPORTANT: Vérifier le statut d'affectation avant la sauvegarde
     */
    public function saveGeneratedFeesAsSubscriptions(ESBTPInscription $inscription, array $generatedFees)
    {
        try {
            // Récupérer le statut d'affectation de l'inscription
            $affectationStatus = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

            // Charger la classe pour les vérifications
            $classe = $inscription->classe;
            if (!$classe) {
                Log::warning('Classe non trouvée pour la sauvegarde des frais', ['inscription_id' => $inscription->id]);
                return;
            }

            foreach ($generatedFees as $fee) {

                if ($fee['amount'] > 0) {
                    // CORRECTION : Utiliser updateOrCreate comme dans regenererFraisInscription
                    // pour garantir la création même si des problèmes de concurrence existent

                    // VERIFICATION OBLIGATOIRE: Recalculer le montant selon le statut d'affectation
                    $verifiedAmount = $fee['amount']; // Montant par défaut

                    // Pour les frais obligatoires, vérifier avec la configuration
                    if ($fee['type'] === 'mandatory') {
                        $configuration = \App\Models\ESBTPFraisConfiguration::where('frais_category_id', $fee['category_id'])
                            ->where('filiere_id', $classe->filiere_id)
                            ->where('niveau_id', $classe->niveau_etude_id)
                            ->where('is_active', true)
                            ->first();

                        if ($configuration) {
                            $verifiedAmount = $configuration->getMontantByStatus($affectationStatus);
                        }
                    }

                    // CORRECTION : Utiliser updateOrCreate pour garantir la création (évite la duplication)
                    \App\Models\ESBTPFraisSubscription::updateOrCreate(
                        [
                            'inscription_id' => $inscription->id,
                            'frais_category_id' => $fee['category_id'],
                        ],
                        [
                            'selected_option_id' => $fee['option_id'] ?? null,
                            'amount' => $verifiedAmount,
                            'is_active' => true,
                            'subscribed_at' => $inscription->date_inscription ?? now(),
                            'created_by' => $inscription->created_by ?? auth()->id(),
                            'notes' => 'Frais ' . $fee['type'] . ' créé automatiquement lors de l\'inscription - ' . $fee['description'] . ' (statut: ' . $affectationStatus . ')'
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde des frais générés comme subscriptions', [
                'inscription_id' => $inscription->id,
                'error' => $e->getMessage(),
                'generated_fees_count' => count($generatedFees)
            ]);
            throw $e; // Re-lancer l'erreur pour interrompre la transaction
        }
    }

    /**
     * Préparer les données de l'étudiant depuis la requête validée.
     */
    public function prepareEtudiantData(array $requestData, ?string $photoFilename = null): array
    {
        $data = [
            'nom' => $requestData['nom'],
            'prenoms' => $requestData['prenoms'],
            'sexe' => $requestData['sexe'],
            'date_naissance' => $requestData['date_naissance'],
            'lieu_naissance' => $requestData['lieu_naissance'] ?? null,
            'nationalite' => $requestData['nationalite'] ?? null,
            'email_personnel' => $requestData['email_personnel'] ?? null,
            'telephone' => $requestData['telephone'] ?? null,
            'adresse' => $requestData['adresse'] ?? null,
            'ville' => $requestData['ville'] ?? null,
            'commune' => $requestData['commune'] ?? null,
            'statut' => 'actif',
            'creer_compte_utilisateur' => true,
            'matricule' => $requestData['matricule'] ?? null,
        ];

        if ($photoFilename) {
            $data['photo'] = $photoFilename;
        }

        return $data;
    }

    /**
     * Préparer les données d'inscription depuis la classe et la requête.
     */
    public function prepareInscriptionData(ESBTPClasse $classe, array $requestData): array
    {
        // Utiliser l'année fournie par le formulaire, sinon l'année courante
        if (!empty($requestData['annee_universitaire_id'])) {
            $annee = ESBTPAnneeUniversitaire::find($requestData['annee_universitaire_id']);
            if (!$annee) {
                throw new \Exception('L\'année universitaire sélectionnée est invalide.');
            }
        } else {
            $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (!$annee) {
                throw new \Exception('Aucune année universitaire courante définie. Veuillez configurer l\'année courante.');
            }
        }

        $isSousReserve = !empty($requestData['is_sous_reserve']);

        return [
            'date_inscription' => $requestData['date_inscription'] ?? now()->format('Y-m-d'),
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'en_attente',
            'is_sous_reserve' => $isSousReserve,
            'condition_reserve' => $isSousReserve ? ($requestData['condition_reserve'] ?? null) : null,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'type_inscription' => 'première_inscription',
            'montant_scolarite' => $requestData['montant_scolarite'] ?? 0,
            'frais_inscription' => $requestData['frais_inscription'] ?? 0,
            'affectation_status' => $requestData['affectation_status'] ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            'est_transfert' => !empty($requestData['est_transfert']),
            'etablissement_origine' => $requestData['etablissement_origine'] ?? null,
        ];
    }

    /**
     * Normaliser les données des parents depuis le formulaire.
     */
    public function prepareParentsData(array $rawParents): array
    {
        $parentsData = [];

        foreach ($rawParents as $parent) {
            if (
                isset($parent['type']) &&
                $parent['type'] === 'existant' &&
                !empty($parent['parent_id'])
            ) {
                $parentsData[] = [
                    'parent_id' => $parent['parent_id'],
                    'relation' => $parent['relation'] ?? 'Autre',
                ];
            } elseif (
                isset($parent['type']) &&
                $parent['type'] === 'nouveau' &&
                !empty($parent['nom']) &&
                !empty($parent['prenoms']) &&
                !empty($parent['telephone'])
            ) {
                $parentsData[] = [
                    'nom' => $parent['nom'],
                    'prenoms' => $parent['prenoms'],
                    'email' => $parent['email'] ?? null,
                    'telephone' => $parent['telephone'] ?? null,
                    'profession' => $parent['profession'] ?? null,
                    'relation' => $parent['relation'] ?? 'Autre',
                    'adresse' => $parent['adresse'] ?? null,
                ];
            }
        }

        return $parentsData;
    }

    /**
     * Convertir les frais variants du formulaire en selectedOptionals.
     */
    public function prepareFraisOptionals(array $fraisVariants): array
    {
        $selectedOptionals = [];

        foreach ($fraisVariants as $categoryId => $fraisData) {
            if (!empty($fraisData['variant_id'])) {
                $selectedOptionals[$categoryId] = $fraisData;
            }
        }

        return $selectedOptionals;
    }

    /**
     * Traiter la validation groupée d'inscriptions.
     *
     * @return array Stats du traitement
     */
    public function processBulkValidation(
        array $inscriptionIds,
        bool $forceValidation,
        InscriptionWorkflowService $workflowService,
        int $userId
    ): array {
        $stats = [
            'validees_direct' => 0,
            'paiements_valides' => 0,
            'validees_apres_paiement' => 0,
            'inscriptions_deja_validees' => 0,
            'ignorees' => [],
            'erreurs' => [],
            'raisons_ignorees' => [
                'sans_paiement' => 0,
                'paiement_non_valide' => 0,
                'classe_pleine' => 0,
                'inscription_existante' => 0,
            ],
        ];

        foreach ($inscriptionIds as $id) {
            try {
                $inscription = ESBTPInscription::with(['paiements', 'etudiant'])->find($id);

                if (!$inscription) {
                    $stats['erreurs'][] = ['id' => $id, 'erreur' => 'Inscription introuvable'];
                    continue;
                }

                // Skip si déjà validée
                if ($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree') {
                    $stats['inscriptions_deja_validees']++;
                    continue;
                }

                $etudiantNom = $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms;

                // Cas 1: A déjà un paiement validé ET workflow = en_validation
                if ($inscription->paiement_validation_id && $inscription->workflow_step === 'en_validation') {
                    $result = $this->processBulkCase1($inscription, $etudiantNom, $forceValidation, $workflowService, $stats, $userId);
                    if ($result === 'continue') {
                        continue;
                    }
                    continue;
                }

                // Cas 2: A des paiement(s) validé(s) mais pas en_validation
                $paiementsValides = $inscription->paiements->where('status', 'validé');
                if ($paiementsValides->count() > 0) {
                    $this->processBulkCase2($inscription, $paiementsValides, $etudiantNom, $forceValidation, $workflowService, $stats, $userId);
                    continue;
                }

                // Cas 3: Paiements en attente
                $paiementsEnAttente = $inscription->paiements->where('status', 'en_attente');
                if ($paiementsEnAttente->count() > 0) {
                    $stats['ignorees'][] = [
                        'id' => $inscription->id,
                        'etudiant' => $etudiantNom,
                        'raison' => 'Le paiement associe n\'est pas encore valide',
                    ];
                    $stats['raisons_ignorees']['paiement_non_valide']++;
                    continue;
                }

                // Cas 4: Aucun paiement
                if ($inscription->paiements->count() === 0) {
                    $stats['ignorees'][] = [
                        'id' => $inscription->id,
                        'etudiant' => $etudiantNom,
                        'raison' => 'Aucun paiement associe a cette inscription',
                    ];
                    $stats['raisons_ignorees']['sans_paiement']++;
                    continue;
                }

                // Cas 5: Paiement présent, valider
                $blockReason = $this->checkBulkValidationPrerequisites($inscription, $etudiantNom, $forceValidation, $workflowService);
                if ($blockReason) {
                    $stats['ignorees'][] = $blockReason;
                    if (str_contains($blockReason['raison'], 'Classe pleine')) {
                        $stats['raisons_ignorees']['classe_pleine']++;
                    } else {
                        $stats['raisons_ignorees']['inscription_existante']++;
                    }
                    continue;
                }

                $result = $this->validerInscription($inscription->id, $userId);
                if ($result['success']) {
                    $stats['validees_direct']++;
                    $this->sendBulkValidationNotification($inscription, $userId);
                } else {
                    $stats['ignorees'][] = [
                        'id' => $id,
                        'etudiant' => $etudiantNom,
                        'raison' => $result['message'],
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Erreur validation inscription bulk #{$id}: " . $e->getMessage());
                $stats['erreurs'][] = ['id' => $id, 'erreur' => $e->getMessage()];
            }
        }

        return $stats;
    }

    /**
     * Cas 1 bulk : paiement_validation_id défini et workflow en_validation.
     */
    private function processBulkCase1(
        ESBTPInscription $inscription,
        string $etudiantNom,
        bool $forceValidation,
        InscriptionWorkflowService $workflowService,
        array &$stats,
        int $userId
    ): string {
        // Vérifier le paiement
        $paiement = ESBTPPaiement::find($inscription->paiement_validation_id);
        if (!$paiement || $paiement->status !== 'validé') {
            $autrePaiementValide = $inscription->paiements->where('status', 'validé')->first();
            if ($autrePaiementValide) {
                $inscription->update(['paiement_validation_id' => $autrePaiementValide->id]);
            }
        }

        $blockReason = $this->checkBulkValidationPrerequisites($inscription, $etudiantNom, $forceValidation, $workflowService);
        if ($blockReason) {
            $stats['ignorees'][] = $blockReason;
            if (str_contains($blockReason['raison'], 'Classe pleine')) {
                $stats['raisons_ignorees']['classe_pleine']++;
            } else {
                $stats['raisons_ignorees']['inscription_existante']++;
            }
            return 'continue';
        }

        $result = $workflowService->convertProspectToStudent($inscription, 'Validation groupée');
        if ($result['success']) {
            $stats['validees_direct']++;
            $this->sendBulkValidationNotification($inscription, $userId);
            $this->desactiverRappelsInscription($inscription->id);
        } else {
            $stats['ignorees'][] = [
                'id' => $inscription->id,
                'etudiant' => $etudiantNom,
                'raison' => $result['message'],
            ];
        }

        return 'continue';
    }

    /**
     * Cas 2 bulk : paiements validés mais pas encore en workflow en_validation.
     */
    private function processBulkCase2(
        ESBTPInscription $inscription,
        $paiementsValides,
        string $etudiantNom,
        bool $forceValidation,
        InscriptionWorkflowService $workflowService,
        array &$stats,
        int $userId
    ): void {
        $premierPaiement = $paiementsValides->first();

        $blockReason = $this->checkBulkValidationPrerequisites($inscription, $etudiantNom, $forceValidation, $workflowService);
        if ($blockReason) {
            $stats['ignorees'][] = $blockReason;
            if (str_contains($blockReason['raison'], 'Classe pleine')) {
                $stats['raisons_ignorees']['classe_pleine']++;
            } else {
                $stats['raisons_ignorees']['inscription_existante']++;
            }
            return;
        }

        // Associer le paiement via le workflow
        $inscription->update([
            'paiement_validation_id' => $premierPaiement->id,
            'workflow_step' => 'en_validation',
        ]);

        \App\Models\ESBTPInscriptionWorkflowHistory::createEntry(
            $inscription->id,
            $inscription->workflow_step,
            'en_validation',
            'paiement_associe',
            $userId,
            'Paiement associé lors de validation groupée',
            ['paiement_id' => $premierPaiement->id],
        );

        $result = $workflowService->convertProspectToStudent($inscription, 'Validation groupée');
        if ($result['success']) {
            $stats['validees_direct']++;
            $this->sendBulkValidationNotification($inscription, $userId);
            $this->desactiverRappelsInscription($inscription->id);
        } else {
            $stats['ignorees'][] = [
                'id' => $inscription->id,
                'etudiant' => $etudiantNom,
                'raison' => $result['message'],
            ];
        }
    }

    /**
     * Vérifications communes avant validation bulk (classe dispo + inscription existante).
     *
     * @return array|null Raison du blocage ou null si OK
     */
    private function checkBulkValidationPrerequisites(
        ESBTPInscription $inscription,
        string $etudiantNom,
        bool $forceValidation,
        InscriptionWorkflowService $workflowService
    ): ?array {
        // Vérifier disponibilité classe
        if (!$forceValidation) {
            $classAvailability = $workflowService->checkClassAvailability($inscription->classe_id);
            if (!$classAvailability['available']) {
                return [
                    'id' => $inscription->id,
                    'etudiant' => $etudiantNom,
                    'raison' => 'Classe pleine - ' . $classAvailability['message'],
                ];
            }
        }

        // Vérifier inscription active existante
        $existingInscription = ESBTPInscription::where('etudiant_id', $inscription->etudiant_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->where('status', 'active')
            ->where('id', '!=', $inscription->id)
            ->first();

        if ($existingInscription) {
            return [
                'id' => $inscription->id,
                'etudiant' => $etudiantNom,
                'raison' => 'L\'étudiant a déjà une inscription active pour cette année',
            ];
        }

        return null;
    }

    /**
     * Envoyer la notification de validation bulk à l'étudiant.
     */
    private function sendBulkValidationNotification(ESBTPInscription $inscription, int $userId): void
    {
        try {
            if ($inscription->etudiant && $inscription->etudiant->user) {
                $notificationService = app(\App\Services\NotificationService::class);
                $user = User::find($userId);
                $notificationService->createNotification(
                    $inscription->etudiant->user,
                    'Inscription validée',
                    'Votre inscription a été validée avec succès. Vous pouvez maintenant accéder à votre espace étudiant.',
                    'success',
                    route('esbtp.inscriptions.show', $inscription->id),
                    $user,
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification bulk validation: ' . $e->getMessage());
        }
    }

    /**
     * Désactiver les rappels pour une inscription.
     */
    private function desactiverRappelsInscription(int $inscriptionId): void
    {
        try {
            $reminder = \App\Models\NotificationReminder::where('remindable_type', 'App\Models\ESBTPInscription')
                ->where('remindable_id', $inscriptionId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
        } catch (\Exception $e) {
            Log::error('Erreur désactivation reminder inscription: ' . $e->getMessage());
        }
    }

    /**
     * Changer la classe d'une inscription (logique métier).
     *
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function changerClasse(
        ESBTPInscription $inscription,
        int $nouvelleClasseId,
        ?string $affectationStatus,
        InscriptionWorkflowService $workflowService
    ): array {
        $ancienneClasseId = $inscription->classe_id;

        // Vérifier la disponibilité de la nouvelle classe
        $availability = $workflowService->checkClassAvailability(
            $nouvelleClasseId,
            $inscription->annee_universitaire_id,
        );

        if (!$availability['available']) {
            return ['success' => false, 'message' => $availability['message'], 'data' => null];
        }

        // Vérifier que ce n'est pas la même classe
        if ($ancienneClasseId && $ancienneClasseId == $nouvelleClasseId) {
            return ['success' => false, 'message' => 'La nouvelle classe est identique à l\'ancienne.', 'data' => null];
        }

        // Archiver les notes/résultats/bulletins de l'ancienne classe
        if ($ancienneClasseId) {
            $etudiantId = $inscription->etudiant_id;
            $now = now();
            ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('classe_id', $ancienneClasseId)
                ->update(['archived_at' => $now]);
            ESBTPResultat::where('etudiant_id', $etudiantId)
                ->where('classe_id', $ancienneClasseId)
                ->update(['archived_at' => $now]);
            ESBTPBulletin::where('etudiant_id', $etudiantId)
                ->where('classe_id', $ancienneClasseId)
                ->update(['archived_at' => $now]);
        }

        // Restaurer les données archivées si l'étudiant revient dans la nouvelle classe
        $etudiantId = $inscription->etudiant_id;
        ESBTPNote::withoutGlobalScope('not_archived')
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $nouvelleClasseId)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);
        ESBTPResultat::withoutGlobalScope('not_archived')
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $nouvelleClasseId)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);
        ESBTPBulletin::withoutGlobalScope('not_archived')
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $nouvelleClasseId)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);

        // Mettre à jour la classe et le statut d'affectation
        $resolvedAffectationStatus = $affectationStatus ?? ($ancienneClasseId ? 'réaffecté' : ESBTPInscription::DEFAULT_AFFECTATION_STATUS);
        $inscription->update([
            'classe_id' => $nouvelleClasseId,
            'affectation_status' => $resolvedAffectationStatus,
            'updated_at' => now(),
        ]);

        // Régénérer les souscriptions de frais avec la nouvelle classe/statut
        $this->regenererFraisInscription($inscription);

        // Charger les relations pour retourner les infos
        $inscription->load('classe');

        return [
            'success' => true,
            'message' => $ancienneClasseId
                ? 'Classe changée avec succès. Les frais ont été recalculés.'
                : 'Étudiant affecté à la classe avec succès. Les frais ont été générés.',
            'data' => [
                'id' => $inscription->id,
                'affectation_status' => $resolvedAffectationStatus,
                'nouvelle_classe' => [
                    'id' => $inscription->classe->id,
                    'name' => $inscription->classe->name,
                ],
            ],
        ];
    }

    /**
     * Régénérer les frais obligatoires après changement de classe/filière/niveau.
     */
    public function regenererFraisInscription(ESBTPInscription $inscription): void
    {
        $resolver = app(\App\Services\ApplicableFraisResolver::class);
        $fees = $resolver->resolveMandatoryFeesForInscription($inscription);
        $affectationStatus = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        foreach ($fees as $fee) {
            ESBTPFraisSubscription::updateOrCreate(
                [
                    'inscription_id' => $inscription->id,
                    'frais_category_id' => $fee['category']->id,
                ],
                [
                    'selected_option_id' => null,
                    'amount' => $fee['amount'],
                    'is_active' => true,
                    'subscribed_at' => now(),
                    'created_by' => Auth::id(),
                    'notes' => 'Regenerate automatically after class change (' . $affectationStatus . ')',
                ],
            );
        }

        return;

        $categoriesObligatoires = ESBTPFraisCategory::where('is_mandatory', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $configurations = ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $categoriesObligatoires->pluck('id'))
            ->get()
            ->groupBy(fn($config) => "{$config->frais_category_id}_{$config->filiere_id}_{$config->niveau_id}");

        $affectationStatus = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        foreach ($categoriesObligatoires as $category) {
            $configKey = "{$category->id}_{$inscription->filiere_id}_{$inscription->niveau_id}";
            $fraisConfig = $configurations->get($configKey, collect())->first();

            if ($fraisConfig) {
                $montant = $fraisConfig->getMontantByStatus($affectationStatus);

                ESBTPFraisSubscription::updateOrCreate(
                    [
                        'inscription_id' => $inscription->id,
                        'frais_category_id' => $category->id,
                    ],
                    [
                        'selected_option_id' => null,
                        'amount' => $montant,
                        'is_active' => true,
                        'subscribed_at' => now(),
                        'created_by' => Auth::id(),
                        'notes' => 'Régénéré automatiquement après changement de classe/filière/niveau',
                    ],
                );
            }
        }
    }

    /**
     * Construire le message de résultat de la validation groupée.
     */
    public function buildBulkValidationMessage(array $stats): string
    {
        $message = '';

        if ($stats['validees_direct'] > 0) {
            $message .= "{$stats['validees_direct']} inscription(s) validée(s) directement. ";
        }
        if ($stats['paiements_valides'] > 0) {
            $message .= "{$stats['paiements_valides']} paiement(s) auto-validé(s). ";
        }
        if ($stats['validees_apres_paiement'] > 0) {
            $message .= "{$stats['validees_apres_paiement']} inscription(s) validée(s) après validation du paiement. ";
        }
        if ($stats['inscriptions_deja_validees'] > 0) {
            $message .= "{$stats['inscriptions_deja_validees']} inscription(s) déjà validée(s) (ignorée(s)). ";
        }

        if (count($stats['ignorees']) > 0) {
            $message .= count($stats['ignorees']) . ' inscription(s) ignorée(s) : ';
            $raisons = [];
            if ($stats['raisons_ignorees']['sans_paiement'] > 0) {
                $raisons[] = "{$stats['raisons_ignorees']['sans_paiement']} sans paiement";
            }
            if ($stats['raisons_ignorees']['paiement_non_valide'] > 0) {
                $raisons[] = "{$stats['raisons_ignorees']['paiement_non_valide']} paiement non validé";
            }
            if ($stats['raisons_ignorees']['classe_pleine'] > 0) {
                $raisons[] = "{$stats['raisons_ignorees']['classe_pleine']} classe pleine";
            }
            if ($stats['raisons_ignorees']['inscription_existante'] > 0) {
                $raisons[] = "{$stats['raisons_ignorees']['inscription_existante']} inscription existante";
            }
            $message .= implode(', ', $raisons) . '. ';
        }

        if (count($stats['erreurs']) > 0) {
            $message .= count($stats['erreurs']) . ' erreur(s) techniques. ';
        }

        return $message;
    }

    /**
     * Extraire les inscriptions avec problèmes depuis les stats bulk.
     */
    public function extractBulkProblems(array $stats): array
    {
        $problems = [];

        if (is_array($stats['erreurs'])) {
            foreach ($stats['erreurs'] as $erreur) {
                if (is_array($erreur) && isset($erreur['id'], $erreur['erreur'])) {
                    $problems[$erreur['id']] = ['type' => 'error', 'message' => $erreur['erreur']];
                }
            }
        }

        if (is_array($stats['ignorees'])) {
            foreach ($stats['ignorees'] as $ignoree) {
                if (is_array($ignoree) && isset($ignoree['id'], $ignoree['raison'])) {
                    $problems[$ignoree['id']] = ['type' => 'warning', 'message' => $ignoree['raison']];
                }
            }
        }

        return $problems;
    }
}
