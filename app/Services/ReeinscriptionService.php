<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPClasse;
use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPNote;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Inscriptions\NormalisationTypeInscription;

class ReeinscriptionService
{
    /**
     * Regles academiques deja lues, par « niveau|filiere ». Nul hors d'un
     * classement : chaque analyse isolee relit alors la base.
     *
     * @var array<string, ESBTPRegleAcademique>|null
     */
    private ?array $reglesMemo = null;

    /**
     * Notes deja lues pour un lot, par annee de resultats puis par etudiant :
     * lignes legeres de `NotesDeLaPromotion`, pas des modeles `ESBTPNote`.
     * Vide hors d'un classement.
     *
     * @var array<string, array<int, Collection<int, object>>>
     */
    private array $notesPrechargees = [];

    /**
     * Moyennes annuelles du bulletin deja calculees pour un lot, par
     * identifiant d'inscription. Vide hors d'un classement.
     *
     * @var array<int, array{moyenne: float|null, semestre1: float|null, semestre2: float|null, poids: array}>
     */
    private array $moyennesAnnuellesPrechargees = [];

    public function __construct(
        private readonly \App\Services\Reinscription\ClassesDeReinscription $classes,
        private readonly \App\Services\Reinscription\NotesDeLaPromotion $notesDeLaPromotion,
        private readonly \App\Services\Reinscription\MoyennesAnnuellesDuBulletin $moyennesAnnuelles,
    ) {}

    public function analyserSituationEtudiant($etudiantId, $anneeAcademique)
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

        // La regle de passage se choisit sur la classe quittee (ClassesDeReinscription).
        $inscriptionQuittee = $this->classes->inscriptionQuittee((int) $etudiantId);
        $classe = $inscriptionQuittee?->classe;

        if (!$classe) {
            throw new \Exception("Étudiant non assigné à une classe");
        }

        $niveauNom = $classe->niveau ? $classe->niveau->name : '';
        $filiereNom = $classe->filiere ? $classe->filiere->name : '';
        
        $regle = $this->regleApplicable($niveauNom, $filiereNom);

        $notes = $this->getNotesEtudiant($etudiantId, $anneeAcademique, $classe);
        $moyenne = $this->moyennePourDecision($inscriptionQuittee, $classe, $notes);
        $moyenneGenerale = $moyenne['moyenne'];
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);

        return [
            'etudiant' => $etudiant,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'moyenne_source' => $moyenne['source'],
            'moyennes_semestres' => $moyenne['semestres'],
            'notes' => $notes,
            'matieres_echouees' => $matieresEchouees,
            'decision' => $this->determinerDecision($moyenneGenerale, $matieresEchouees, $regle),
            'peut_passer' => $regle->peutPasser($moyenneGenerale),
            'peut_rattraper' => $regle->peutRattraper($moyenneGenerale),
            'doit_redoubler' => $regle->doitRedoubler($moyenneGenerale)
        ];
    }
    
    /**
     * Passer, rattraper ou redoubler : la decision se lit sur les resultats de
     * l'annee que l'inscription analysee couvre — celle que l'etudiant termine.
     *
     * L'annee n'est donc plus un parametre que l'appelant choisit, parce que
     * les appelants se trompaient. L'ecran de reinscription passait l'annee
     * COURANTE, celle vers laquelle on reinscrit, ou ces etudiants n'ont par
     * construction aucune note : la moyenne sortait a zero et toute la
     * promotion tombait en redoublement, sans une ligne d'erreur. La fiche,
     * elle, passait l'annee civile en cours. Seule la reinscription groupee
     * visait juste, et rendait donc un verdict different de l'ecran sur le
     * meme etudiant.
     *
     * $anneeAcademique ne sert plus que de repli, si l'inscription ne porte
     * pas son annee.
     */
    public function analyserSituationEtudiantParInscription($inscription, $anneeAcademique = null)
    {
        $etudiant = $inscription->etudiant;
        $classe = $inscription->classe;

        if (!$classe) {
            throw new \Exception("Classe manquante pour l'étudiant {$etudiant->prenom} {$etudiant->nom}");
        }

        $niveauNom = $classe->niveau ? $classe->niveau->name : '';
        $filiereNom = $classe->filiere ? $classe->filiere->name : '';

        $regle = $this->regleApplicable($niveauNom, $filiereNom);

        $anneeDesResultats = $inscription->anneeUniversitaire->name ?? $anneeAcademique;

        $notes = $this->getNotesEtudiant($etudiant->id, $anneeDesResultats, $classe);
        $moyenne = $this->moyennePourDecision($inscription, $classe, $notes);
        $moyenneGenerale = $moyenne['moyenne'];
        $matieresEchouees = $this->getMatieresEchouees($notes, $regle->moyenne_passage);

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
        $etudiant->affectation_status = $inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;

        return [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'inscription' => $inscription,
            'regle' => $regle,
            'moyenne_generale' => $moyenneGenerale,
            'moyenne_source' => $moyenne['source'],
            'moyennes_semestres' => $moyenne['semestres'],
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
            \Log::info("Analyse de réinscription ignorée: aucune année universitaire précédente trouvée", [
                'annee_courante' => $anneeUniversitaireCourante->name,
                'pour_reinscription_vers' => $anneeAcademique
            ]);

            return $this->emptyDecisionResult();
        }

        \Log::info("Analyse de réinscription", [
            'annee_courante' => $anneeUniversitaireCourante->name,
            'annee_precedente_analysee' => $anneePrecedente->name,
            'pour_reinscription_vers' => $anneeAcademique
        ]);

        // Récupérer les étudiants via leurs inscriptions ACTIVES de l'ANNÉE PRÉCÉDENTE
        // (status=active + workflow_step=etudiant_cree pour ne considérer que les inscriptions
        // réelles qui produisent décisions / bulletins).
        // EXCLUSION : ceux qui ont déjà une inscription dans l'année courante (déjà réinscrits).
        $inscriptions = \App\Models\ESBTPInscription::with(['etudiant', 'classe.niveau', 'classe.filiere', 'anneeUniversitaire'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
            ->where('annee_universitaire_id', $anneePrecedente->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereDoesntHave('etudiant.inscriptions', function($query) use ($anneeUniversitaireCourante) {
                $query->where('annee_universitaire_id', $anneeUniversitaireCourante->id);
            })
            ->get();
        
        $resultat = $this->classerParDecision($inscriptions, $anneeAcademique);

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
     * Range chaque inscription dans sa decision.
     *
     * Les notes sont lues par lots de 200 etudiants, en une requete par lot,
     * et la regle academique une fois par couple niveau / filiere. Avant, la
     * liste de reinscription relisait notes et regles etudiant par etudiant :
     * six requetes par etudiant, et autant de fois que d'onglets ouverts. La
     * decision, elle, ne change pas : memes notes, meme filtre, meme regle.
     */
    private function classerParDecision(Collection $inscriptions, $anneeAcademique): array
    {
        $resultat = $this->emptyDecisionResult();
        $this->reglesMemo = [];

        try {
            foreach ($inscriptions->chunk(200) as $lot) {
                $this->prechargerNotes($lot, $anneeAcademique);
                $this->moyennesAnnuellesPrechargees = $this->moyennesAnnuelles->pour($lot);

                foreach ($lot as $inscription) {
                    if ($inscription->etudiant && $inscription->classe) {
                        $this->classerUneInscription($resultat, $inscription, $anneeAcademique);
                    }
                }

                $this->notesPrechargees = [];
                $this->moyennesAnnuellesPrechargees = [];
            }
        } finally {
            $this->reglesMemo = null;
            $this->notesPrechargees = [];
            $this->moyennesAnnuellesPrechargees = [];
        }

        return $resultat;
    }

    private function classerUneInscription(array &$resultat, $inscription, $anneeAcademique): void
    {
        try {
            $analyse = $this->analyserSituationEtudiantParInscription($inscription, $anneeAcademique);

            // CORRECTION: Vérifier que l'analyse n'est pas null
            if ($analyse === null) {
                \Log::warning("Analyse null ignorée", [
                    'inscription_id' => $inscription->id,
                    'etudiant_id' => $inscription->etudiant_id
                ]);
                return;
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

    /**
     * Les notes de tout un lot d'inscriptions, lues en une requete par annee de
     * resultats, sous la cle que `getNotesEtudiant()` interrogera.
     *
     * Lignes brutes et non modeles (`NotesDeLaPromotion`) : la decision ne lit
     * que la valeur, la matiere et la matiere de l'evaluation, et hydrater un
     * modele par note coutait plus que tout le reste du calcul.
     */
    private function prechargerNotes(Collection $lot, $anneeAcademique): void
    {
        $parAnnee = $lot->groupBy(fn ($inscription) => $inscription->anneeUniversitaire->name ?? $anneeAcademique);

        foreach ($parAnnee as $annee => $inscriptions) {
            $ids = $inscriptions->pluck('etudiant_id')->filter()->unique()->values();
            $notes = $this->notesDeLaPromotion->pour($ids->all(), $annee);

            foreach ($ids as $etudiantId) {
                $this->notesPrechargees[$annee][(int) $etudiantId] = $notes->get($etudiantId) ?? collect();
            }
        }
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
                $statistiques['valides'] = \App\Models\ESBTPInscription::where('type_inscription', NormalisationTypeInscription::REINSCRIPTION)
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

    private function emptyDecisionResult(): array
    {
        return [
            'passages' => [],
            'rattrapages' => [],
            'redoublements' => [],
            'errors' => []
        ];
    }

    /**
     * Effectue une réinscription (1 étudiant).
     *
     * @param bool $skipTransaction Si true, ne gère pas la transaction (laisse au caller, ex: batch)
     * @param bool $sendNotification Si false, ne dispatch pas la notification mail (utile en batch queue)
     */
    public function effectuerReinscription(
        $etudiantId,
        $nouvelleClasseId,
        $decision,
        $observations = null,
        $selectedOptionals = [],
        $affectationStatus = null,
        $anneeUniversitaireId = null,
        $actionReliquat = null,
        bool $skipTransaction = false,
        bool $sendNotification = true
    ): ESBTPInscription {
        if (!$skipTransaction) {
            \DB::beginTransaction();
        }
        try {
            // 1. Vérifications préalables
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

            // Vérifier permissions SuperAdmin pour outrepasser
            $isSuperAdmin = auth()->user() && auth()->user()->can('admin.access');

            $eligibilite = app(\App\Services\Reinscription\EligibiliteReinscription::class)
                ->pour((int) $etudiantId, null, $anneeUniversitaireId ? (int) $anneeUniversitaireId : null);
            // Un dossier sur une année intermédiaire : aucune dérogation ne la
            // saute, pas même une correction ; la fiche et Nanan disent pareil.
            if ($eligibilite['inscription_intermediaire'] !== null) {
                throw new \App\Exceptions\ReinscriptionRefuseeException($eligibilite['message_intermediaire']);
            }
            if (!$this->peutSeReinscrire($etudiantId, $anneeUniversitaireId) && !$isSuperAdmin) {
                throw new \App\Exceptions\ReinscriptionRefuseeException("L'étudiant doit solder tous ses frais avant la réinscription");
            }

            // Note: Si SuperAdmin et que l'étudiant a des impayés, les reliquats seront créés automatiquement

            // 2. L'inscription QUITTÉE pour l'année visée : celle que la garde
            // vient de juger, et dont le reste dû part en reliquat. La dernière
            // inscription active (`latest()`) pouvait être une autre — celle de
            // l'année visée elle-même, quand la réinscription est rejouée.
            $inscriptionActuelle = $eligibilite['inscription']
                ?? $etudiant->inscriptions()->where('status', 'active')->latest()->first();

            if (!$inscriptionActuelle) {
                throw new \App\Exceptions\ReinscriptionRefuseeException("Aucune inscription active trouvée pour cet étudiant");
            }

            // Le statut d'affectation suit l'etudiant d'une annee sur l'autre : le
            // MESRS l'a place, ou ne l'a pas place, et se reinscrire n'y change rien.
            // Quand l'appelant ne le precise pas, on reprend celui de l'inscription
            // quittee. Supposer « affecte » reviendrait a rendre l'etudiant
            // subventionne du jour au lendemain, donc a effacer sa scolarite sans
            // que personne ne l'ait decide.
            $affectationStatus = $affectationStatus
                ?: ($inscriptionActuelle->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS);

            // 3. Déterminer l'année universitaire pour la nouvelle inscription
            if ($anneeUniversitaireId) {
                // Utiliser l'année sélectionnée par l'utilisateur
                $nouvelleAnnee = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);
            } else {
                // Fallback : utiliser l'année courante
                $nouvelleAnnee = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

                if (!$nouvelleAnnee) {
                    throw new \App\Exceptions\ReinscriptionRefuseeException("Aucune année universitaire active trouvée");
                }
            }

            // On ne se réinscrit jamais dans l'année qu'on quitte : l'étape
            // suivante terminerait l'inscription en cours de l'élève et la
            // remplacerait, sans retour possible.
            if ((int) $inscriptionActuelle->annee_universitaire_id === (int) $nouvelleAnnee->id) {
                throw new \App\Exceptions\ReinscriptionRefuseeException(
                    "L'année de destination ({$nouvelleAnnee->name}) est celle que l'étudiant quitte : choisissez l'année suivante."
                );
            }

            // On ne se réinscrit jamais à rebours : l'année visée doit commencer
            // après celle qu'on quitte, et aucune inscription vivante ne doit
            // exister plus tard. Sinon le reste dû partirait en reliquat vers le
            // passé, et l'élève aurait deux inscriptions actives.
            $debutQuitte = $inscriptionActuelle->anneeUniversitaire?->start_date;
            if ($debutQuitte && $nouvelleAnnee->start_date && $debutQuitte->gte($nouvelleAnnee->start_date)) {
                throw new \App\Exceptions\ReinscriptionRefuseeException(
                    "L'année de destination ({$nouvelleAnnee->name}) précède l'année que l'étudiant quitte ({$inscriptionActuelle->anneeUniversitaire->name})."
                );
            }
            if ($nouvelleAnnee->start_date && \App\Models\ESBTPInscription::where('etudiant_id', $etudiantId)
                ->whereIn('status', ['en_attente', 'active'])
                ->whereHas('anneeUniversitaire', fn ($a) => $a->where('start_date', '>', $nouvelleAnnee->start_date))
                ->exists()) {
                throw new \App\Exceptions\ReinscriptionRefuseeException(
                    "L'étudiant a déjà une inscription sur une année postérieure à {$nouvelleAnnee->name}."
                );
            }

            // Une inscription existe déjà sur l'année visée : la refaire la
            // termine et la remplace (correction de classe). Réservé à qui peut
            // déroger — la fiche ne propose « Corriger » qu'à ce compte-là.
            $dejaInscrit = \App\Models\ESBTPInscription::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $nouvelleAnnee->id)
                ->where('status', '!=', 'annulée')
                ->exists();
            // Rejouer vers la MÊME classe ne corrige rien, et heurterait l'index
            // unique (étudiant, année, classe) : l'ancienne ligne n'est que
            // « terminée », pas supprimée.
            // L'index ne regarde pas le statut : une ligne annulée bloque aussi.
            $memeClasse = \App\Models\ESBTPInscription::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $nouvelleAnnee->id)
                ->where('classe_id', $nouvelleClasseId)
                ->first();
            if ($memeClasse) {
                throw new \App\Exceptions\ReinscriptionRefuseeException($memeClasse->status === 'annulée'
                    ? "Une inscription annulée existe déjà dans cette classe pour {$nouvelleAnnee->name} : rouvrez-la depuis la liste des inscriptions."
                    : "L'étudiant est déjà inscrit dans cette classe pour {$nouvelleAnnee->name} : choisissez une autre classe pour corriger.");
            }
            if ($dejaInscrit && !$isSuperAdmin) {
                throw new \App\Exceptions\ReinscriptionRefuseeException(
                    "L'étudiant est déjà inscrit pour {$nouvelleAnnee->name}. Pour corriger cette inscription, demandez à un superadministrateur."
                );
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

            // Le redoublement se juge par rapport a l'annee QUITTEE, pas a la
            // derniere inscription creee : un etudiant accumule des inscriptions
            // actives au fil des ans, et une reinscription rejouee sur l'annee
            // en cours (correction de classe) prendrait sinon sa propre
            // inscription comme reference et se marquerait redoublante a tort.
            // Sans annee anterieure, il n'y a rien a redoubler : la reponse est
            // non, pas un repli sur l'inscription courante.
            $estRedoublement = \App\Models\ESBTPInscription::estUnRedoublement(
                \App\Models\ESBTPInscription::precedantAnnee($etudiantId, $nouvelleAnnee),
                $nouvelleClasse->niveau_etude_id
            );

        // 4. Créer nouvelle inscription

        $nouvelleInscription = \App\Models\ESBTPInscription::create([
            'etudiant_id' => $etudiantId,
            'annee_universitaire_id' => $nouvelleAnnee->id,
                'classe_id' => $nouvelleClasseId,
                'filiere_id' => $nouvelleClasse->filiere_id,
                'niveau_id' => $nouvelleClasse->niveau_etude_id,
                'affectation_status' => $affectationStatus,
                'montant_scolarite' => 0, // À définir plus tard comme les autres inscriptions
                'frais_inscription' => 0, // À définir plus tard
                'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
                // Une reinscription, c'est quelqu'un qui etait deja la : c'est la
                // definition meme du mot. Laisser ce champ vide le faisait passer
                // pour un nouvel arrivant, parce que la regle d'audience lit
                // « nouveau = tout ce qui n'est pas ancien » — un champ nul suffit
                // donc a declencher les frais reserves aux entrants, la tenue en
                // tete. L'ecole n'avait aucun moyen de le voir : la fiche affichait
                // bien « ancien » apres correction, mais la souscription, elle,
                // avait deja ete creee et payee.
                'statut_etablissement' => \App\Models\ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
                'is_redoublant' => $estRedoublement,
                'date_inscription' => now(),
                'status' => 'active',
                'workflow_step' => 'documents_complets',
            'observations' => $observations,
            'created_by' => auth()->id(),
            'numero_recu' => $this->genererNumeroRecu($nouvelleAnnee, $nouvelleClasse)
        ]);

        $decisionLabel = ucfirst($decision);
        $observationText = trim((string) ($observations ?? ''));
        $reinscriptionNote = $decisionLabel . ($observationText !== '' ? ' - ' . $observationText : '');

        $nouvelleInscription->update([
            'reinscription_status' => 'validated',
            'reinscription_validated_at' => now(),
            'reinscription_validated_by' => auth()->id(),
            'reinscription_observations' => $reinscriptionNote,
            'updated_by' => auth()->id(),
        ]);


            // 5. Générer nouveaux frais via service existant
            $inscriptionService = app(\App\Services\ESBTPInscriptionService::class);
            $generatedFees = $inscriptionService->generateFeesForInscription(
                $nouvelleInscription,
                $selectedOptionals,
                $affectationStatus
            );

            // Sauvegarder les frais générés comme souscriptions
            $inscriptionService->saveGeneratedFeesAsSubscriptions($nouvelleInscription, $generatedFees);

            // Note: Facture et paiements seront gérés via inscriptions.show comme d'habitude


            // 5.5 Gérer les reliquats selon l'action choisie par le superAdmin
            $this->gererReliquats($inscriptionActuelle, $nouvelleInscription, $actionReliquat);

            // 6. Mise à jour statut étudiant
            $etudiant->update([
                'statut' => $this->getStatutFromDecision($decision)
            ]);

            // 7. Historique complet
            $this->sauvegarderHistoriqueComplet($etudiant, $decision, $observations, $nouvelleInscription, $generatedFees);
            
            if (!$skipTransaction) {
                \DB::commit();
            }

            if ($sendNotification) {
                try {
                    app(\App\Services\NotificationService::class)
                        ->notifyParentsReinscriptionCreated($nouvelleInscription, $decision, null);
                } catch (\Throwable $notifErr) {
                    \Log::warning('Notification réinscription failed', ['error' => $notifErr->getMessage()]);
                }
            }

            return $nouvelleInscription;

        } catch (\Throwable $e) {
            // \Throwable, et non \Exception : un \Error — « call to a member
            // function on null », la panne la plus banale d'une methode qui
            // deref une dizaine de modeles — laisserait sinon la transaction
            // ouverte. L'appelant qui tente ensuite de reparer son etat ecrirait
            // DANS cette transaction orpheline, que MySQL annulerait a la
            // fermeture de la connexion : sa reparation serait perdue en
            // silence, et la demande resterait figee.
            if (!$skipTransaction) {
                \DB::rollback();
            }
            throw $e;
        }
    }

    /**
     * La regle de passage d'un couple niveau / filiere : la sienne, sinon la
     * regle par defaut de l'ecole, sinon les valeurs de repli.
     *
     * Memorisee le temps d'un classement (`classerParDecision()`) : la meme
     * regle servait a des centaines d'etudiants et se relisait pour chacun.
     * Hors classement, elle se relit a chaque appel, comme avant.
     */
    private function regleApplicable(string $niveauNom, string $filiereNom): ESBTPRegleAcademique
    {
        $cle = $niveauNom.'|'.$filiereNom;
        if ($this->reglesMemo !== null && array_key_exists($cle, $this->reglesMemo)) {
            return $this->reglesMemo[$cle];
        }

        $regle = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveauNom, $filiereNom)
            ?? ESBTPRegleAcademique::where('niveau', '')->where('filiere', '')->where('actif', true)->first()
            ?? $this->regleDeRepli($niveauNom, $filiereNom);

        if ($this->reglesMemo !== null) {
            $this->reglesMemo[$cle] = $regle;
        }

        return $regle;
    }

    private function getNotesEtudiant($etudiantId, $anneeAcademique, ESBTPClasse $classe)
    {
        // Récupérer les notes filtrées par année académique (utilise le champ STRING annee_universitaire)
        $notes = $this->notesPrechargees[$anneeAcademique][(int) $etudiantId]
            ?? ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire', $anneeAcademique)
                // `withTrashed()` : `ESBTPMatiere` est en `SoftDeletes`. Sans lui, une
                // matiere effacee depuis `/esbtp/matieres` rend `null`, le `! $matiere ||`
                // ci-dessous court-circuite, et la note etrangere revient peser — sur une
                // DECISION de passage, pas sur un affichage.
                ->with([
                    'evaluation.matiere' => fn ($q) => $q->withTrashed(),
                    'matiere' => fn ($q) => $q->withTrashed(),
                ])
                ->get();

        // POURQUOI LE FILTRE EST ICI, ET NON DANS LES TROIS CONSOMMATEURS.
        // Ces notes alimentent la moyenne (`calculerMoyenneGenerale()`), la
        // liste des matieres echouees (`getMatieresEchouees()`) ET le tableau
        // rendu a l'ecran ('notes' => $notes). Filtrer a la source les corrige
        // ensemble ; filtrer chez chaque consommateur demanderait au quatrieme,
        // celui qui n'existe pas encore, de s'en souvenir.
        //
        // L'ENJEU N'EST PAS UN AFFICHAGE. Une ECUE du LMD notee 4/20 dans une
        // classe BTS tire la moyenne vers le bas et compte comme une matiere
        // echouee : elle peut faire basculer un passage en redoublement, pour
        // toute une promotion via la reinscription groupee.
        // Modele `ESBTPNote` sur une analyse isolee, ligne legere de
        // `NotesDeLaPromotion` dans une liste : les deux repondent a `matiere`
        // et `evaluation->matiere` de la meme facon.
        return $notes->filter(function (object $note) use ($classe) {
            $matiere = $note->matiere ?? $note->evaluation?->matiere;

            return ! $matiere || CoherenceSystemeAcademique::matiereRetenue(
                $matiere,
                $classe,
                'reinscription/note'
            );
        })->values();
    }

    /**
     * La moyenne de decision : celle du conseil du bulletin en BTS, repli sur
     * les notes brutes dit et journalise (`MoyennesAnnuellesDuBulletin::decision()`).
     */
    private function moyennePourDecision(?ESBTPInscription $inscription, ESBTPClasse $classe, Collection $notes): array
    {
        $annuelle = $inscription ? ($this->moyennesAnnuellesPrechargees[(int) $inscription->id] ?? null) : null;

        return $this->moyennesAnnuelles->decision($inscription, $classe, $annuelle, $this->calculerMoyenneGenerale($notes), $notes->isEmpty());
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

    /**
     * Regle de repli, en memoire uniquement.
     *
     * Cette methode est appelee depuis un chemin de LECTURE (analyse de la
     * situation d'un etudiant). Elle creait auparavant une ligne en base a
     * chaque consultation d'un couple niveau/filiere non configure, ce qui
     * peuplait esbtp_regles_academiques de regles fantomes que personne
     * n'avait saisies, avec des seuils inventes. On retourne desormais une
     * instance non persistee : l'ecole reste seule a decider de ses regles
     * depuis /esbtp/reinscriptions/regles.
     */
    private function regleDeRepli($niveau, $filiere): ESBTPRegleAcademique
    {
        \Log::info("Aucune règle académique configurée, application des valeurs de repli", [
            'niveau' => $niveau,
            'filiere' => $filiere
        ]);

        $regle = new ESBTPRegleAcademique([
            'niveau' => $niveau,
            'filiere' => $filiere,
            'moyenne_passage' => 12.00, // Moyenne classique pour passer
            'moyenne_rattrapage' => 8.00, // Seuil minimum pour rattrapage
            'max_matieres_rattrapage' => 3, // Maximum 3 matières en rattrapage
            'autoriser_redoublement' => true,
            'max_redoublements' => 2, // Maximum 2 redoublements
            'conditions_speciales' => 'Valeurs de repli - aucune règle configurée pour ce niveau et cette filière',
            'actif' => true
        ]);

        return $regle;
    }

    /**
     * Vérifier si un étudiant peut se réinscrire (doit être entièrement soldé)
     */
    public function peutSeReinscrire($etudiantId, $anneeCibleId = null): bool
    {
        // La même inscription et le même solde que la fiche de réinscription.
        // Avant : la dernière inscription ACTIVE (`latest()`), qui pouvait être
        // une autre que celle affichée, donc un autre verdict que l'écran.
        $eligibilite = app(\App\Services\Reinscription\EligibiliteReinscription::class)
            ->pour((int) $etudiantId, null, $anneeCibleId ? (int) $anneeCibleId : null);
        if (!$eligibilite['inscription'] || $eligibilite['etat'] === \App\Services\Reinscription\EligibiliteReinscription::ANNEE_INTERMEDIAIRE) {
            return false;
        }

        return $eligibilite['solde'] <= $eligibilite['tolerance'];
    }

    /**
     * Reste du jusqu'auquel une reinscription reste permise.
     *
     * Defaut 0 : le dossier doit etre entierement solde, ce qui est le
     * comportement d'avant ce reglage. Une ecole qui veut tolerer un reliquat le
     * pose dans ses parametres, et l'affichage comme la garde le suivent
     * ensemble — c'est tout l'objet de cette methode.
     */
    private function toleranceSolde(): float
    {
        return \App\Services\Reinscription\EligibiliteReinscription::tolerance();
    }

    /**
     * Calculer le solde restant d'une inscription basé sur les frais souscriptions actives.
     * Si aucune souscription → solde = 0 (rien à payer).
     */
    public function calculerSoldeInscription($inscription): float
    {
        $subscriptions = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->charged()
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $totalAttendu = $subscriptions->sum('amount');
        $totalPaye = \App\Models\ESBTPPaiement::netPaidForInscription((int) $inscription->id);

        return $totalAttendu - $totalPaye;
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
            $montantAttendu = $fraisSubscription->chargedAmount();

            // Calculer le montant payé pour ce frais spécifique
            // Chercher les paiements avec plusieurs variantes de statut possibles
            $montantPaye = \App\Models\ESBTPPaiement::netPaidForInscription(
                (int) $inscriptionSource->id,
                (int) $fraisSubscription->frais_category_id,
            );

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
     * Calcule le montant payé pour une inscription
     */
    private function calculerMontantPaye($inscription)
    {
        return \App\Models\ESBTPPaiement::netPaidForInscription((int) $inscription->id);
    }

}
