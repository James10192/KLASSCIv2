<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReeinscriptionService
{
    public function analyserSituationEtudiant($etudiantId, $anneeAcademique)
    {
        $etudiant = ESBTPEtudiant::with(['classe.niveau', 'classe.filiere'])->findOrFail($etudiantId);
        
        if (!$etudiant->classe) {
            throw new \Exception("Étudiant non assigné à une classe");
        }

        $niveauNom = $etudiant->classe->niveau ? $etudiant->classe->niveau->name : '';
        $filiereNom = $etudiant->classe->filiere ? $etudiant->classe->filiere->name : '';
        
        $regle = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveauNom, $filiereNom);

        // Si pas de règle trouvée, chercher une règle par défaut
        if (!$regle) {
            $regle = ESBTPRegleAcademique::where('niveau', '')->where('filiere', '')->where('actif', true)->first();
        }
        
        // Si toujours pas de règle, créer une règle par défaut
        if (!$regle) {
            $regle = $this->creerRegleParDefaut($niveauNom, $filiereNom);
        }

        $notes = $this->getNotesEtudiant($etudiantId, $anneeAcademique);
        $moyenneGenerale = $this->calculerMoyenneGenerale($notes);
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);
        
        return [
            'etudiant' => $etudiant,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'notes' => $notes,
            'matieres_echouees' => $matieresEchouees,
            'decision' => $this->determinerDecision($moyenneGenerale, $matieresEchouees, $regle),
            'peut_passer' => $regle->peutPasser($moyenneGenerale),
            'peut_rattraper' => $regle->peutRattraper($moyenneGenerale),
            'doit_redoubler' => $regle->doitRedoubler($moyenneGenerale)
        ];
    }
    
    public function analyserSituationEtudiantParInscription($inscription, $anneeAcademique)
    {
        $etudiant = $inscription->etudiant;
        $classe = $inscription->classe;

        if (!$classe) {
            throw new \Exception("Classe manquante pour l'étudiant {$etudiant->prenom} {$etudiant->nom}");
        }

        $niveauNom = $classe->niveau ? $classe->niveau->name : '';
        $filiereNom = $classe->filiere ? $classe->filiere->name : '';

        $regle = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveauNom, $filiereNom);

        // Si pas de règle trouvée, chercher une règle par défaut
        if (!$regle) {
            $regle = ESBTPRegleAcademique::where('niveau', '')->where('filiere', '')->where('actif', true)->first();
        }

        // Si toujours pas de règle, créer une règle par défaut
        if (!$regle) {
            $regle = $this->creerRegleParDefaut($niveauNom, $filiereNom);
        }

        $notes = $this->getNotesEtudiant($etudiant->id, $anneeAcademique);
        $moyenneGenerale = $this->calculerMoyenneGenerale($notes);
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);

        // TEMPORAIRE: Désactiver l'enrichissement financier pour éviter timeout (optimisation à faire plus tard)
        // $this->ajouterInformationsFinancieres($etudiant, $inscription);

        // Vérification de sécurité: s'assurer que l'étudiant n'est pas null
        if (!$etudiant) {
            \Log::warning("Étudiant null détecté dans analyserSituationEtudiantParInscription", [
                'inscription_id' => $inscription->id ?? null
            ]);
            return null; // Retourner null pour filtrage ultérieur
        }

        // Ajouter des valeurs par défaut pour éviter erreurs dans la vue
        $etudiant->montant_attendu = 0;
        $etudiant->montant_paye = 0;
        $etudiant->solde_restant = 0;
        $etudiant->peut_reinscrire = true;  // Valeur par défaut optimiste
        $etudiant->affectation_status = $inscription->affectation_status ?? 'affecté';

        return [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'inscription' => $inscription,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'notes' => $notes,
            'matieres_echouees' => $matieresEchouees,
            'decision' => $this->determinerDecision($moyenneGenerale, $matieresEchouees, $regle),
            'peut_passer' => $regle->peutPasser($moyenneGenerale),
            'peut_rattraper' => $regle->peutRattraper($moyenneGenerale),
            'doit_redoubler' => $regle->doitRedoubler($moyenneGenerale)
        ];
    }

    public function getEtudiantsParDecision($anneeAcademique)
    {
        // CORRECTION: Pour la réinscription, nous devons analyser les étudiants de l'année PRÉCÉDENTE
        // et non de l'année courante. Pour réinscrire vers 2025-2026, on analyse 2024-2025
        $anneeUniversitaireCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeUniversitaireCourante) {
            throw new \Exception("Aucune année universitaire courante définie");
        }

        // Trouver l'année précédente (année N-1)
        $anneePrecedente = \App\Models\ESBTPAnneeUniversitaire::where('end_date', '<', $anneeUniversitaireCourante->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$anneePrecedente) {
            throw new \Exception("Aucune année universitaire précédente trouvée pour l'analyse de réinscription");
        }

        \Log::info("Analyse de réinscription", [
            'annee_courante' => $anneeUniversitaireCourante->name,
            'annee_precedente_analysee' => $anneePrecedente->name,
            'pour_reinscription_vers' => $anneeAcademique
        ]);

        // Récupérer les étudiants via leurs inscriptions de l'ANNÉE PRÉCÉDENTE
        // MAIS SEULEMENT ceux qui n'ont pas encore été réinscrits dans l'année courante
        $inscriptions = \App\Models\ESBTPInscription::with(['etudiant', 'classe.niveau', 'classe.filiere'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
            ->where('annee_universitaire_id', $anneePrecedente->id)
            // CORRECTION: Exclure les étudiants qui ont déjà une inscription dans l'année courante
            ->whereDoesntHave('etudiant.inscriptions', function($query) use ($anneeUniversitaireCourante) {
                $query->where('annee_universitaire_id', $anneeUniversitaireCourante->id);
            })
            ->get();
        
        $resultat = [
            'passages' => [],
            'rattrapages' => [],
            'redoublements' => [],
            'errors' => []
        ];

        foreach ($inscriptions as $inscription) {
            if ($inscription->etudiant && $inscription->classe) {
                try {
                    $analyse = $this->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);

                    // CORRECTION: Vérifier que l'analyse n'est pas null
                    if ($analyse === null) {
                        \Log::warning("Analyse null ignorée", [
                            'inscription_id' => $inscription->id,
                            'etudiant_id' => $inscription->etudiant_id
                        ]);
                        continue;
                    }

                    switch ($analyse['decision']) {
                        case 'passage':
                            $resultat['passages'][] = $analyse;
                            break;
                        case 'rattrapage':
                            $resultat['rattrapages'][] = $analyse;
                            break;
                        case 'redoublement':
                            $resultat['redoublements'][] = $analyse;
                            break;
                    }
                } catch (\Exception $e) {
                    $resultat['errors'][] = [
                        'etudiant' => $inscription->etudiant,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        // CORRECTION: Pour les "non validés", chercher les étudiants de l'année précédente qui n'ont pas
        // encore été réinscrits dans l'année courante (et non pas TOUS les étudiants sans inscription courante)
        $etudiantsNonReinscritsDeMêmePeriode = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($anneePrecedente) {
                // Étudiants qui ont une inscription dans l'année précédente
                $query->where('annee_universitaire_id', $anneePrecedente->id)
                      ->whereNotNull('classe_id');
            })
            ->whereDoesntHave('inscriptions', function($query) use ($anneeUniversitaireCourante) {
                // Mais qui n'ont pas d'inscription dans l'année courante
                $query->where('annee_universitaire_id', $anneeUniversitaireCourante->id);
            })
            // Exclure les étudiants déjà traités dans les analyses ci-dessus
            ->whereNotIn('id', collect($resultat['passages'])->pluck('etudiant.id')
                                ->merge(collect($resultat['rattrapages'])->pluck('etudiant.id'))
                                ->merge(collect($resultat['redoublements'])->pluck('etudiant.id'))
                                ->filter())
            ->get();

        foreach ($etudiantsNonReinscritsDeMêmePeriode as $etudiant) {
            $resultat['errors'][] = [
                'etudiant' => $etudiant,
                'error' => 'Non encore réinscrit pour ' . $anneeUniversitaireCourante->name
            ];
        }

        return $resultat;
    }

    /**
     * Obtenir seulement les statistiques (compteurs) pour optimiser les performances
     */
    public function getStatistiquesReinscription($anneeAcademique)
    {
        // CORRECTION: Utiliser la logique corrigée consistante avec getEtudiantsParDecision
        try {
            $resultats = $this->getEtudiantsParDecision($anneeAcademique);

            $statistiques = [
                'passages' => count($resultats['passages'] ?? []),
                'rattrapages' => count($resultats['rattrapages'] ?? []),
                'redoublements' => count($resultats['redoublements'] ?? []),
                'errors' => count($resultats['errors'] ?? []),
                'valides' => 0,
                'abandons_annee' => 0,
                'abandons_ecole' => 0
            ];

            // Récupérer l'année courante pour les autres statistiques
            $anneeUniversitaireCourante = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

            if ($anneeUniversitaireCourante) {
                // Compter les réinscriptions validées dans l'année courante
                $statistiques['valides'] = \App\Models\ESBTPInscription::where('type_inscription', 'reinscription')
                    ->where('annee_universitaire_id', $anneeUniversitaireCourante->id)
                    ->where('status', 'active')
                    ->count();

                // Compter les abandons (basés sur l'année précédente analysée)
                $anneePrecedente = \App\Models\ESBTPAnneeUniversitaire::where('end_date', '<', $anneeUniversitaireCourante->start_date)
                    ->orderBy('end_date', 'desc')
                    ->first();

                if ($anneePrecedente) {
                    $statistiques['abandons_annee'] = ESBTPEtudiant::where('statut', 'abandon')
                        ->where(function($query) {
                            $query->where('abandon_type', 'annee_scolaire')
                                  ->orWhereNull('abandon_type');
                        })
                        ->whereHas('inscriptions', function($query) use ($anneePrecedente) {
                            $query->where('annee_universitaire_id', $anneePrecedente->id);
                        })
                        ->count();

                    $statistiques['abandons_ecole'] = ESBTPEtudiant::where('statut', 'abandon')
                        ->where('abandon_type', 'ecole')
                        ->whereHas('inscriptions', function($query) use ($anneePrecedente) {
                            $query->where('annee_universitaire_id', $anneePrecedente->id);
                        })
                        ->count();
                }
            }

            return $statistiques;

        } catch (\Exception $e) {
            \Log::error("Erreur lors du calcul des statistiques de réinscription", [
                'error' => $e->getMessage(),
                'annee_academique' => $anneeAcademique
            ]);

            // Retourner des statistiques vides en cas d'erreur
            return [
                'passages' => 0,
                'rattrapages' => 0,
                'redoublements' => 0,
                'valides' => 0,
                'abandons_annee' => 0,
                'abandons_ecole' => 0,
                'errors' => 0
            ];
        }
    }

    public function proposerNouvellesClasses($etudiantId, $decision)
    {
        $etudiant = ESBTPEtudiant::with(['classe.niveau', 'classe.filiere'])->findOrFail($etudiantId);
        
        switch ($decision) {
            case 'passage':
                return $this->getClassesNiveauSuperieut($etudiant->classe);
            case 'redoublement':
                return $this->getClassesMemeNiveau($etudiant->classe);
            case 'rattrapage':
                return [$etudiant->classe]; // Reste dans la même classe
        }

        return [];
    }

    public function effectuerReinscription($etudiantId, $nouvelleClasseId, $decision, $observations = null, $selectedOptionals = [], $affectationStatus = 'affecté', $anneeUniversitaireId = null, $actionReliquat = null)
    {
        \DB::beginTransaction();
        try {
            // 1. Vérifications préalables
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

            // Vérifier permissions SuperAdmin pour outrepasser
            $isSuperAdmin = auth()->user() && auth()->user()->hasRole('superAdmin');

            if (!$this->peutSeReinscrire($etudiantId) && !$isSuperAdmin) {
                throw new \Exception("L'étudiant doit solder tous ses frais avant la réinscription");
            }

            // Note: Si SuperAdmin et que l'étudiant a des impayés, les reliquats seront créés automatiquement

            // 2. Récupérer l'inscription active actuelle de l'étudiant
            $inscriptionActuelle = $etudiant->inscriptions()
                ->where('status', 'active')
                ->latest()
                ->first();

            if (!$inscriptionActuelle) {
                throw new \Exception("Aucune inscription active trouvée pour cet étudiant");
            }

            // 3. Déterminer l'année universitaire pour la nouvelle inscription
            if ($anneeUniversitaireId) {
                // Utiliser l'année sélectionnée par l'utilisateur
                $nouvelleAnnee = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);
            } else {
                // Fallback : utiliser l'année courante
                $nouvelleAnnee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

                if (!$nouvelleAnnee) {
                    throw new \Exception("Aucune année universitaire active trouvée");
                }
            }

            // 4. Vérifier et désactiver toute inscription active existante pour cet étudiant dans cette année
            $inscriptionExistante = \App\Models\ESBTPInscription::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $nouvelleAnnee->id)
                ->where('status', 'active')
                ->first();

            if ($inscriptionExistante) {
                $inscriptionExistante->update([
                    'status' => 'terminée',
                    'observations' => ($inscriptionExistante->observations ? $inscriptionExistante->observations . "\n" : '') .
                                    "Inscription terminée automatiquement lors de la réinscription le " . now()->format('d/m/Y H:i'),
                    'updated_by' => auth()->id()
                ]);

                \Log::info('Inscription existante désactivée pour réinscription', [
                    'ancienne_inscription_id' => $inscriptionExistante->id,
                    'etudiant_id' => $etudiantId,
                    'annee_universitaire_id' => $nouvelleAnnee->id
                ]);
            }

            $nouvelleClasse = ESBTPClasse::findOrFail($nouvelleClasseId);

            // 4. Créer nouvelle inscription
            \Log::info('🏗️ TRACE AFFECTATION SERVICE: Création de l\'inscription avec statut', [
                'etudiant_id' => $etudiantId,
                'affectation_status_recu_du_controller' => $affectationStatus,
                'nouvelle_classe_id' => $nouvelleClasseId,
                'fonction_appelee' => 'effectuerReinscription'
            ]);

            $nouvelleInscription = \App\Models\ESBTPInscription::create([
                'etudiant_id' => $etudiantId,
                'annee_universitaire_id' => $nouvelleAnnee->id,
                'classe_id' => $nouvelleClasseId,
                'filiere_id' => $nouvelleClasse->filiere_id,
                'niveau_id' => $nouvelleClasse->niveau_etude_id,
                'affectation_status' => $affectationStatus,
                'montant_scolarite' => 0, // À définir plus tard comme les autres inscriptions
                'frais_inscription' => 0, // À définir plus tard
                'type_inscription' => 'reinscription',
                'date_inscription' => now(),
                'status' => 'active',
                'workflow_step' => 'documents_complets',
                'observations' => $observations,
                'created_by' => auth()->id(),
                'numero_recu' => $this->genererNumeroRecu($nouvelleAnnee, $nouvelleClasse)
            ]);

            \Log::info('🏗️ TRACE AFFECTATION SERVICE: Inscription créée, vérification du statut', [
                'inscription_id' => $nouvelleInscription->id,
                'affectation_status_apres_creation' => $nouvelleInscription->affectation_status,
                'affectation_status_voulu' => $affectationStatus,
                'creation_reussie' => $nouvelleInscription->exists,
                'verify_in_database' => true
            ]);

            // Vérification supplémentaire en base de données
            $inscriptionFromDb = \App\Models\ESBTPInscription::find($nouvelleInscription->id);
            \Log::info('🏗️ TRACE AFFECTATION SERVICE: Vérification en base de données', [
                'inscription_id' => $nouvelleInscription->id,
                'affectation_status_in_db' => $inscriptionFromDb->affectation_status,
                'matches_expected' => $inscriptionFromDb->affectation_status === $affectationStatus
            ]);

            // 5. Générer nouveaux frais via service existant
            $inscriptionService = app(\App\Services\ESBTPInscriptionService::class);
            $generatedFees = $inscriptionService->generateFeesForInscription(
                $nouvelleInscription,
                $selectedOptionals,
                $affectationStatus
            );

            // CORRECTION: Sauvegarder les frais générés comme souscriptions (MANQUANT!)
            $inscriptionService->saveGeneratedFeesAsSubscriptions($nouvelleInscription, $generatedFees);

            // Note: Facture et paiements seront gérés via inscriptions.show comme d'habitude

            // VÉRIFICATION: Le statut d'affectation a-t-il changé après génération des frais ?
            $nouvelleInscription->refresh();
            \Log::info('🏗️ TRACE AFFECTATION SERVICE: Statut après génération des frais', [
                'inscription_id' => $nouvelleInscription->id,
                'affectation_status_apres_frais' => $nouvelleInscription->affectation_status,
                'affectation_status_original' => $affectationStatus,
                'frais_generes_count' => count($generatedFees)
            ]);

            // Vérification finale avant retour
            $nouvelleInscription->refresh();
            \Log::info('🏗️ TRACE AFFECTATION SERVICE: Statut final avant retour au contrôleur', [
                'inscription_id' => $nouvelleInscription->id,
                'affectation_status_final' => $nouvelleInscription->affectation_status,
                'should_be' => $affectationStatus,
                'operation_complete' => true
            ]);

            // 5.5 Gérer les reliquats selon l'action choisie par le superAdmin
            $this->gererReliquats($inscriptionActuelle, $nouvelleInscription, $actionReliquat);

            // 6. Mise à jour statut étudiant
            $etudiant->update([
                'statut' => $this->getStatutFromDecision($decision)
            ]);

            // 7. Historique complet
            $this->sauvegarderHistoriqueComplet($etudiant, $decision, $observations, $nouvelleInscription, $generatedFees);
            
            \DB::commit();
            return $nouvelleInscription;
            
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }

    private function getNotesEtudiant($etudiantId, $anneeAcademique)
    {
        // Récupérer les notes filtrées par année académique (utilise le champ STRING annee_universitaire)
        return ESBTPNote::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire', $anneeAcademique)
            ->with(['evaluation.matiere', 'matiere'])
            ->get();
    }

    private function calculerMoyenneGenerale($notes)
    {
        if ($notes->isEmpty()) {
            return 0;
        }

        $moyennesParMatiere = $notes->groupBy(function($note) {
                // Utiliser matiere_id directement ou via evaluation
                return $note->matiere_id ?? $note->evaluation?->matiere?->id;
            })
            ->map(function($notesMatiere) {
                return $notesMatiere->avg('note');
            });

        return $moyennesParMatiere->avg();
    }

    private function getMatieresEchouees($notes, $moyennePassage)
    {
        $moyennesParMatiere = $notes->groupBy(function($note) {
                // Utiliser matiere_id directement ou via evaluation
                return $note->matiere_id ?? $note->evaluation?->matiere?->id;
            })
            ->map(function($notesMatiere) {
                $premiereNote = $notesMatiere->first();
                $matiere = $premiereNote->matiere ?? $premiereNote->evaluation?->matiere;
                
                return [
                    'matiere' => $matiere,
                    'moyenne' => $notesMatiere->avg('note')
                ];
            })
            ->filter(function($item) use ($moyennePassage) {
                return $item['matiere'] && $item['moyenne'] < $moyennePassage;
            });

        return $moyennesParMatiere->values();
    }

    private function determinerDecision($moyenneGenerale, $matieresEchouees, $regle)
    {
        if ($regle->peutPasser($moyenneGenerale)) {
            return 'passage';
        }

        if ($regle->peutRattraper($moyenneGenerale) && 
            count($matieresEchouees) <= $regle->max_matieres_rattrapage) {
            return 'rattrapage';
        }

        return 'redoublement';
    }

    private function getClassesNiveauSuperieut($classeActuelle)
    {
        // Définir la hiérarchie des niveaux basée sur les codes
        $hierarchie = ['1A' => 1, '2A' => 2, 'L1' => 3, 'L2' => 4, 'L3' => 5, 'M1' => 6, 'M2' => 7];
        
        $niveauActuel = $classeActuelle->niveau->code ?? '';
        $ordreActuel = $hierarchie[$niveauActuel] ?? 0;
        
        // Trouver les codes de niveaux supérieurs
        $niveauxSuperieurs = array_keys(array_filter($hierarchie, function($ordre) use ($ordreActuel) {
            return $ordre > $ordreActuel;
        }));
        
        if (empty($niveauxSuperieurs)) {
            return collect(); // Aucun niveau supérieur
        }
        
        return ESBTPClasse::where('filiere_id', $classeActuelle->filiere_id)
            ->whereHas('niveau', function($query) use ($niveauxSuperieurs) {
                $query->whereIn('code', $niveauxSuperieurs);
            })
            ->with(['niveau', 'filiere'])
            ->get();
    }

    private function getClassesMemeNiveau($classeActuelle)
    {
        return ESBTPClasse::where('niveau_etude_id', $classeActuelle->niveau_etude_id)
            ->where('filiere_id', $classeActuelle->filiere_id)
            ->with(['niveau', 'filiere'])
            ->get();
    }

    private function sauvegarderHistoriqueComplet($etudiant, $decision, $observations, $nouvelleInscription, $generatedFees)
    {
        // Récupérer l'ancienne inscription active
        $ancienneInscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', '!=', $nouvelleInscription->annee_universitaire_id)
            ->where('status', 'active')
            ->latest()
            ->first();
            
        \Log::info("Réinscription effectuée avec nouvelle inscription", [
            'etudiant_id' => $etudiant->id,
            'ancienne_inscription_id' => $ancienneInscription?->id,
            'ancienne_classe' => $ancienneInscription?->classe?->name ?? 'N/A',
            'nouvelle_inscription_id' => $nouvelleInscription->id,
            'nouvelle_classe' => $nouvelleInscription->classe->name,
            'nouvelle_annee' => $nouvelleInscription->anneeUniversitaire->libelle,
            'decision' => $decision,
            'observations' => $observations,
            'frais_generes_count' => count($generatedFees),
            'montant_total_nouveaux_frais' => array_sum(array_column($generatedFees, 'amount')),
            'date' => now()
        ]);
    }

    private function getStatutFromDecision($decision)
    {
        switch ($decision) {
            case 'passage':
                return 'actif';
            case 'redoublement':
                return 'actif';
            case 'rattrapage':
                return 'actif';
            default:
                return 'actif';
        }
    }

    private function creerRegleParDefaut($niveau, $filiere)
    {
        \Log::info("Création automatique d'une règle académique", [
            'niveau' => $niveau,
            'filiere' => $filiere
        ]);

        // Créer une règle académique avec des valeurs par défaut sensées
        $regle = ESBTPRegleAcademique::create([
            'niveau' => $niveau,
            'filiere' => $filiere,
            'moyenne_passage' => 12.00, // Moyenne classique pour passer
            'moyenne_rattrapage' => 8.00, // Seuil minimum pour rattrapage
            'max_matieres_rattrapage' => 3, // Maximum 3 matières en rattrapage
            'autoriser_redoublement' => true,
            'max_redoublements' => 2, // Maximum 2 redoublements
            'conditions_speciales' => 'Règle créée automatiquement - À ajuster selon les besoins',
            'actif' => true
        ]);

        return $regle;
    }

    /**
     * Vérifier si un étudiant peut se réinscrire (doit être entièrement soldé)
     */
    public function peutSeReinscrire($etudiantId): bool
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
        $inscriptionActive = $etudiant->inscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();
        
        if (!$inscriptionActive) return false;
        
        $soldeRestant = $this->calculerSoldeInscription($inscriptionActive);
        return $soldeRestant <= 0;
    }

    /**
     * Calculer le solde restant d'une inscription
     */
    private function calculerSoldeInscription($inscription): float
    {
        // Utiliser la logique existante de calcul des soldes
        $montantAttendu = $inscription->paiements()->sum('montant') + 
                         $inscription->frais_inscription + 
                         $inscription->montant_scolarite;
        
        $montantPaye = $inscription->paiements()
            ->where('status', 'validated')
            ->sum('montant');
            
        return $montantAttendu - $montantPaye;
    }

    /**
     * Générer un numéro de reçu pour la réinscription
     */
    private function genererNumeroRecu($annee, $classe): string
    {
        $prefix = 'REINSC';
        $anneeCode = $annee->code ?? date('Y');
        $numero = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$anneeCode}-{$numero}";
    }

    /**
     * Créer les reliquats pour les montants impayés de l'inscription précédente
     */
    private function gererReliquats($inscriptionSource, $inscriptionDestination, $actionReliquat = null)
    {
        // Récupérer tous les frais souscrits pour l'inscription source
        $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionSource->id)
            ->where('is_active', true)
            ->with(['fraisCategory', 'selectedOption'])
            ->get();

        foreach ($fraisSouscrits as $fraisSubscription) {
            // Calculer le montant attendu pour ce frais
            $montantAttendu = $fraisSubscription->amount;

            // Calculer le montant payé pour ce frais spécifique
            // Chercher les paiements avec plusieurs variantes de statut possibles
            $montantPaye = \App\Models\ESBTPPaiement::where('inscription_id', $inscriptionSource->id)
                ->where('frais_category_id', $fraisSubscription->frais_category_id)
                ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
                ->sum('montant');

            // Calculer le reliquat
            $montantReliquat = $montantAttendu - $montantPaye;

            // Traiter seulement s'il y a un montant impayé
            if ($montantReliquat > 0) {
                if ($actionReliquat === 'reporter') {
                    // Reporter le reliquat vers la nouvelle inscription
                    \App\Models\ESBTPReliquatDetail::create([
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $inscriptionDestination->id,
                        'frais_subscription_id' => $fraisSubscription->id,
                        'montant_attendu' => $montantAttendu,
                        'montant_paye' => $montantPaye,
                        'montant_reliquat' => $montantReliquat,
                        'montant_regle' => 0,
                        'statut' => 'actif',
                        'date_creation' => now(),
                        'date_derniere_maj' => now(),
                        'created_by' => auth()->id(),
                        'notes' => "Reliquat reporté lors de la réinscription de {$inscriptionSource->anneeUniversitaire->name} vers {$inscriptionDestination->anneeUniversitaire->name}"
                    ]);

                    \Log::info("Reliquat reporté pour réinscription", [
                        'etudiant_id' => $inscriptionSource->etudiant_id,
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $inscriptionDestination->id,
                        'frais_category_id' => $fraisSubscription->frais_category_id,
                        'montant_reliquat' => $montantReliquat,
                        'frais_name' => $fraisSubscription->configuration_name ?? 'N/A'
                    ]);
                } elseif ($actionReliquat === 'abandonner') {
                    // Abandonner le reliquat - marquer la souscription comme abandonnée
                    $fraisSubscription->update([
                        'is_active' => false,
                        'status' => 'abandonné',
                        'notes' => ($fraisSubscription->notes ? $fraisSubscription->notes . "\n" : '') .
                                  "Frais impayé abandonné lors de la réinscription le " . now()->format('d/m/Y H:i'),
                        'updated_by' => auth()->id()
                    ]);

                    \Log::info("Reliquat abandonné pour réinscription", [
                        'etudiant_id' => $inscriptionSource->etudiant_id,
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $inscriptionDestination->id,
                        'frais_category_id' => $fraisSubscription->frais_category_id,
                        'montant_reliquat' => $montantReliquat,
                        'frais_name' => $fraisSubscription->configuration_name ?? 'N/A'
                    ]);
                } else {
                    // Comportement par défaut : créer le reliquat (backward compatibility)
                    \App\Models\ESBTPReliquatDetail::create([
                        'inscription_source_id' => $inscriptionSource->id,
                        'inscription_destination_id' => $inscriptionDestination->id,
                        'frais_subscription_id' => $fraisSubscription->id,
                        'montant_attendu' => $montantAttendu,
                        'montant_paye' => $montantPaye,
                        'montant_reliquat' => $montantReliquat,
                        'montant_regle' => 0,
                        'statut' => 'actif',
                        'date_creation' => now(),
                        'date_derniere_maj' => now(),
                        'created_by' => auth()->id(),
                        'notes' => "Reliquat créé automatiquement lors de la réinscription de {$inscriptionSource->anneeUniversitaire->name} vers {$inscriptionDestination->anneeUniversitaire->name}"
                    ]);
                }
            }
        }
    }

    /**
     * Ajoute les informations financières à l'étudiant en tenant compte du statut d'affectation
     */
    private function ajouterInformationsFinancieres($etudiant, $inscription)
    {
        try {
            // Récupérer le statut d'affectation de l'inscription (défaut: affecté)
            $affectationStatus = $inscription->affectation_status ?? 'affecté';

            // Calculer le montant attendu selon le statut d'affectation
            $montantAttendu = $this->calculerMontantAttenduAvecStatut($inscription, $affectationStatus);

            // Calculer le montant payé pour cette inscription
            $montantPaye = $this->calculerMontantPaye($inscription);

            // Calculer le solde restant
            $soldeRestant = max(0, $montantAttendu - $montantPaye);

            // Déterminer si l'étudiant peut se réinscrire (soldé ou quasi-soldé)
            $peutReinscrire = $soldeRestant <= 50000; // Tolérance de 50k FCFA

            // Ajouter les propriétés à l'objet étudiant
            $etudiant->montant_attendu = $montantAttendu;
            $etudiant->montant_paye = $montantPaye;
            $etudiant->solde_restant = $soldeRestant;
            $etudiant->peut_reinscrire = $peutReinscrire;
            $etudiant->affectation_status = $affectationStatus;

        } catch (\Exception $e) {
            \Log::warning("Erreur lors du calcul des informations financières", [
                'etudiant_id' => $etudiant->id ?? null,
                'inscription_id' => $inscription->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Valeurs par défaut en cas d'erreur
            $etudiant->montant_attendu = 0;
            $etudiant->montant_paye = 0;
            $etudiant->solde_restant = 0;
            $etudiant->peut_reinscrire = false;
            $etudiant->affectation_status = 'affecté';
        }
    }

    /**
     * Calcule le montant attendu selon le statut d'affectation
     */
    private function calculerMontantAttenduAvecStatut($inscription, $affectationStatus)
    {
        $montantTotal = 0;

        // Récupérer les frais de l'inscription
        $fraisSubscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->with('fraisConfiguration')
            ->get();

        foreach ($fraisSubscriptions as $fraisSubscription) {
            $config = $fraisSubscription->fraisConfiguration;
            if ($config) {
                // Utiliser le montant selon le statut d'affectation
                $montant = $config->getMontantByStatus($affectationStatus);
                $montantTotal += $montant;
            }
        }

        return $montantTotal;
    }

    /**
     * Calcule le montant payé pour une inscription
     */
    private function calculerMontantPaye($inscription)
    {
        return \App\Models\ESBTPPaiement::where('inscription_id', $inscription->id)
            ->where('status', 'validé')
            ->sum('montant');
    }

}