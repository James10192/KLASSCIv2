<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
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

            // Ajouter des logs pour déboguer
            Log::info('Début de création de l\'inscription', [
                'etudiantData' => $etudiantData,
                'inscriptionData' => $inscriptionData
            ]);

            // Ajouter un log supplémentaire pour vérifier les champs ville et commune
            Log::info('Champs de résidence dans le service', [
                'ville' => $etudiantData['ville'] ?? 'non défini',
                'commune' => $etudiantData['commune'] ?? 'non défini',
                'lieu_naissance' => $etudiantData['lieu_naissance'] ?? 'non défini',
                'adresse' => $etudiantData['adresse'] ?? 'non défini'
            ]);

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
            Log::info('Frais générés automatiquement pour l\'inscription', [
                'inscription_id' => $inscription->id,
                'type_inscription' => $inscription->type_inscription,
                'fees_count' => count($generatedFees),
                'selected_optionals' => $selectedOptionals,
                'affectation_status' => $affectationStatus
            ]);

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
                Log::info('Paiement créé pour l\'inscription', ['paiement' => $paiementData]);
            } else {
                Log::info('Aucun paiement fourni pour cette inscription');
            }

            DB::commit();

            // Ajout de logs pour déboguer
            Log::info('Inscription créée avec succès', [
                'etudiant' => $etudiant,
                'inscription' => $inscription ?? null
            ]);

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
        // Ajouter un log pour déboguer la valeur du matricule reçue
        Log::info('Matricule reçu dans createEtudiant:', [
            'matricule' => $etudiantData['matricule'] ?? 'Non fourni',
            'matricule_empty' => empty($etudiantData['matricule']),
            'matricule_null' => $etudiantData['matricule'] === null,
            'matricule_length' => isset($etudiantData['matricule']) ? strlen($etudiantData['matricule']) : 0
        ]);

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
            Log::info('Génération automatique d\'un matricule car aucun matricule n\'a été fourni ou le matricule est vide');

            $etudiantData['matricule'] = $this->matriculeGenerator->generate([
                'genre' => $etudiantData['genre'] ?? $etudiantData['sexe'] ?? 'M',
                'filiere_id' => $etudiantData['filiere_id'] ?? null,
                'niveau_id' => $etudiantData['niveau_etude_id'] ?? null,
                'annee_universitaire_id' => $anneeUniversitaireId,
            ]);

            Log::info('Matricule généré automatiquement:', [
                'matricule' => $etudiantData['matricule']
            ]);
        } else {
            Log::info('Utilisation du matricule fourni:', [
                'matricule' => $etudiantData['matricule']
            ]);
        }

        // Créer l'étudiant
        $etudiant = ESBTPEtudiant::create($etudiantData);

        Log::info('Étudiant créé avec le matricule:', [
            'matricule' => $etudiant->matricule,
            'id' => $etudiant->id
        ]);

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
        Log::info('Début attachement des parents', ['parentsData' => $parentsData]);

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

                    Log::info('Parent existant attaché à l\'étudiant', [
                        'parent_id' => $parent->id,
                        'etudiant_id' => $etudiant->id
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

                    Log::info('Nouveau parent créé et attaché à l\'étudiant', [
                        'parent_id' => $parent->id,
                        'etudiant_id' => $etudiant->id
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

        Log::info('Fin attachement des parents pour l\'étudiant', ['etudiant_id' => $etudiant->id]);
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

                Log::info('Calcul frais obligatoire avec statut d\'affectation', [
                    'category' => $category->name,
                    'affectation_status' => $affectationStatus,
                    'amount' => $amount,
                    'has_configuration' => $configuration !== null
                ]);

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

            Log::info('Frais générés avec succès', [
                'inscription_id' => $inscription->id,
                'mandatory_fees' => count(array_filter($generatedFees, fn($f) => $f['type'] === 'mandatory')),
                'optional_fees' => count(array_filter($generatedFees, fn($f) => $f['type'] === 'optional')),
                'total_amount' => array_sum(array_column($generatedFees, 'amount'))
            ]);

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
                    // Créer la nouvelle souscription
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

                    Log::info('Souscription créée automatiquement', [
                        'inscription_id' => $inscription->id,
                        'category_id' => $categoryId,
                        'option_id' => $optionId,
                        'amount' => $amount
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
            Log::info('DÉBUT sauvegarde des frais comme souscriptions', [
                'inscription_id' => $inscription->id,
                'type_inscription' => $inscription->type_inscription,
                'generated_fees_count' => count($generatedFees),
                'generated_fees' => $generatedFees
            ]);

            // Récupérer le statut d'affectation de l'inscription
            $affectationStatus = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

            // Charger la classe pour les vérifications
            $classe = $inscription->classe;
            if (!$classe) {
                Log::warning('Classe non trouvée pour la sauvegarde des frais', ['inscription_id' => $inscription->id]);
                return;
            }

            foreach ($generatedFees as $fee) {
                Log::info('Traitement frais individuel', [
                    'fee' => $fee,
                    'inscription_id' => $inscription->id
                ]);

                // Vérifier si la souscription existe déjà
                $existingSubscription = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                    ->where('frais_category_id', $fee['category_id'])
                    ->first();

                Log::info('Vérification souscription existante', [
                    'existing_subscription' => $existingSubscription ? $existingSubscription->id : 'none',
                    'fee_amount' => $fee['amount'],
                    'condition_met' => (!$existingSubscription && $fee['amount'] > 0)
                ]);

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

                            Log::info('Verification du montant avant sauvegarde de la souscription', [
                                'category_id' => $fee['category_id'],
                                'affectation_status' => $affectationStatus,
                                'montant_initial' => $fee['amount'],
                                'montant_verifie' => $verifiedAmount,
                                'configuration_id' => $configuration->id
                            ]);
                        }
                    }

                    // CORRECTION : Utiliser updateOrCreate pour garantir la création (évite la duplication)
                    $subscription = \App\Models\ESBTPFraisSubscription::updateOrCreate(
                        [
                            'inscription_id' => $inscription->id,
                            'frais_category_id' => $fee['category_id'],
                        ],
                        [
                            'selected_option_id' => $fee['option_id'] ?? null,
                            'amount' => $verifiedAmount, // Utiliser le montant vérifié
                            'is_active' => true,
                            'subscribed_at' => $inscription->date_inscription ?? now(),
                            'created_by' => $inscription->created_by ?? auth()->id(),
                            'notes' => 'Frais ' . $fee['type'] . ' créé automatiquement lors de l\'inscription - ' . $fee['description'] . ' (statut: ' . $affectationStatus . ')'
                        ]
                    );

                    Log::info('ESBTPFraisSubscription créée/mise à jour avec updateOrCreate', [
                        'inscription_id' => $inscription->id,
                        'subscription_id' => $subscription->id,
                        'category_id' => $fee['category_id'],
                        'amount_original' => $fee['amount'],
                        'amount_verified' => $verifiedAmount,
                        'affectation_status' => $affectationStatus,
                        'description' => $fee['description'],
                        'type' => $fee['type'],
                        'was_created' => $subscription->wasRecentlyCreated
                    ]);
                } else {
                    Log::info('Souscription de frais ignorée - montant invalide', [
                        'inscription_id' => $inscription->id,
                        'category_id' => $fee['category_id'],
                        'amount' => $fee['amount']
                    ]);
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
}
