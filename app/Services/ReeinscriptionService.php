<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;

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
        // Récupérer les étudiants via leurs inscriptions validées
        $inscriptions = \App\Models\ESBTPInscription::with(['etudiant', 'classe.niveau', 'classe.filiere'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
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

        // Ajouter les étudiants sans inscription validée dans les erreurs
        $etudiantsSansInscription = ESBTPEtudiant::whereDoesntHave('inscriptions', function($query) {
            $query->whereNotNull('classe_id');
        })->get();
        
        foreach ($etudiantsSansInscription as $etudiant) {
            $resultat['errors'][] = [
                'etudiant' => $etudiant,
                'error' => 'Inscription en cours - Non encore validée'
            ];
        }

        return $resultat;
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

    public function effectuerReinscription($etudiantId, $nouvelleClasseId, $decision, $observations = null)
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
        $nouvelleClasse = ESBTPClasse::findOrFail($nouvelleClasseId);
        
        // Sauvegarder l'historique
        $this->sauvegarderHistorique($etudiant, $decision, $observations);
        
        // Mettre à jour la classe de l'étudiant
        $etudiant->update([
            'classe_id' => $nouvelleClasseId,
            'statut' => $this->getStatutFromDecision($decision)
        ]);

        return $etudiant->fresh();
    }

    private function getNotesEtudiant($etudiantId, $anneeAcademique)
    {
        // Pour l'instant, récupérer toutes les notes de l'étudiant
        // car nous n'avons pas encore la logique pour mapper l'année académique à l'ID
        return ESBTPNote::where('etudiant_id', $etudiantId)
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
        // Logique pour trouver les classes du niveau supérieur
        // À adapter selon la structure des niveaux dans votre système
        return ESBTPClasse::where('filiere_id', $classeActuelle->filiere_id)
            ->whereHas('niveau', function($query) use ($classeActuelle) {
                $query->where('ordre', '>', $classeActuelle->niveau->ordre ?? 0);
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

    private function sauvegarderHistorique($etudiant, $decision, $observations)
    {
        // Créer un enregistrement d'historique de réinscription
        // Vous pourriez créer une table esbtp_historique_reinscriptions
        \Log::info("Réinscription effectuée", [
            'etudiant_id' => $etudiant->id,
            'ancienne_classe' => $etudiant->classe->nom ?? 'N/A',
            'decision' => $decision,
            'observations' => $observations,
            'date' => now()
        ]);
    }

    private function getStatutFromDecision($decision)
    {
        switch ($decision) {
            case 'passage':
                return 'actif';
            case 'redoublement':
                return 'redoublant';
            case 'rattrapage':
                return 'rattrapage';
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
}