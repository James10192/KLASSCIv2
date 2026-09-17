<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ESBTPCalculAbsencesController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ESBTP\ESBTPAbsenceService;

class TestCalculAbsencesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:absences {etudiant_id?} {classe_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le calcul des absences pour un étudiant';

    protected $calculController;
    protected $absenceService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ESBTPAbsenceService $absenceService)
    {
        parent::__construct();
        $this->calculController = new ESBTPCalculAbsencesController();
        $this->absenceService = $absenceService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Test de calcul des absences ===');

        // Récupérer ou demander l'ID de l'étudiant
        $etudiantId = $this->argument('etudiant_id');
        if (!$etudiantId) {
            // Si aucun ID n'est fourni, sélectionner un étudiant qui a des absences
            $etudiantAvecAbsences = ESBTPAttendance::select('etudiant_id')
                ->distinct()
                ->first();

            if ($etudiantAvecAbsences) {
                $etudiantId = $etudiantAvecAbsences->etudiant_id;
                $this->info("Aucun ID d'étudiant fourni, utilisation de l'étudiant ID: $etudiantId");
            } else {
                // Ou sélectionner le premier étudiant disponible
                $etudiant = ESBTPEtudiant::first();
                if ($etudiant) {
                    $etudiantId = $etudiant->id;
                    $this->info("Aucun étudiant avec absences trouvé, utilisation du premier étudiant ID: $etudiantId");
                } else {
                    $this->error("Aucun étudiant trouvé dans la base de données.");
                    return 1;
                }
            }
        }

        // Vérifier que l'étudiant existe
        $etudiant = ESBTPEtudiant::find($etudiantId);
        if (!$etudiant) {
            $this->error("L'étudiant avec l'ID $etudiantId n'existe pas.");
            return 1;
        }

        $this->info("Étudiant: {$etudiant->prenom} {$etudiant->nom} (ID: {$etudiant->id})");

        // Récupérer ou demander l'ID de la classe
        $classeId = $this->argument('classe_id');
        if (!$classeId) {
            // Utiliser la classe de l'étudiant si possible
            if ($etudiant->classe_id) {
                $classeId = $etudiant->classe_id;
                $this->info("Utilisation de la classe associée à l'étudiant (ID: $classeId)");
            } else {
                // Sinon prendre la première classe disponible
                $classe = ESBTPClasse::first();
                if ($classe) {
                    $classeId = $classe->id;
                    $this->info("Aucune classe associée à l'étudiant, utilisation de la première classe (ID: $classeId)");
                } else {
                    $this->error("Aucune classe trouvée dans la base de données.");
                    return 1;
                }
            }
        }

        // Vérifier que la classe existe
        $classe = ESBTPClasse::find($classeId);
        if (!$classe) {
            $this->error("La classe avec l'ID $classeId n'existe pas.");
            return 1;
        }

        $this->info("Classe: {$classe->nom} (ID: {$classe->id})");

        // Définir la période (3 derniers mois)
        $dateFin = Carbon::now()->format('Y-m-d');
        $dateDebut = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->info("Période de calcul: du $dateDebut au $dateFin");

        // Vérifier s'il y a des absences pour cette période
        $nbAbsences = ESBTPAttendance::where('etudiant_id', $etudiantId)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->count();

        $this->info("Nombre d'enregistrements d'absences trouvés: $nbAbsences");

        if ($nbAbsences == 0) {
            $this->warn("Aucune absence trouvée pour la période. Création d'absences de test...");

            // Créer quelques absences de test
            $this->creerAbsencesTest($etudiantId, $classeId, $dateDebut, $dateFin);

            // Recompter
            $nbAbsences = ESBTPAttendance::where('etudiant_id', $etudiantId)
                ->whereBetween('date', [$dateDebut, $dateFin])
                ->count();

            $this->info("Nombre d'absences créées: $nbAbsences");
        }

        // Appel au contrôleur pour calculer les absences
        $this->info("Calcul des absences en cours...");

        try {
            $resultat = $this->absenceService->calculerDetailAbsences(
                $etudiantId,
                $classeId,
                $dateDebut,
                $dateFin
            );

            // Afficher le résultat
            $this->info("=== Résultats du calcul ===");
            $this->info("Heures d'absences justifiées: {$resultat['justifiees']}");
            $this->info("Heures d'absences non justifiées: {$resultat['non_justifiees']}");
            $this->info("Total des heures d'absences: {$resultat['total']}");

            // Détails des absences justifiées
            $this->info("\n--- Détail des absences justifiées ---");
            foreach ($resultat['detail']['justifiees'] as $index => $absence) {
                $this->info("#{$index} - Date: {$absence['date']} - Durée: {$absence['duree']}h - Commentaire: {$absence['commentaire']}");
            }

            // Détails des absences non justifiées
            $this->info("\n--- Détail des absences non justifiées ---");
            foreach ($resultat['detail']['non_justifiees'] as $index => $absence) {
                $this->info("#{$index} - Date: {$absence['date']} - Durée: {$absence['duree']}h - Commentaire: {$absence['commentaire']}");
            }

            $this->info("\n=== Test terminé avec succès ===");
            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors du calcul des absences: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Crée des absences de test pour l'étudiant spécifié
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param string $dateDebut
     * @param string $dateFin
     * @return void
     */
    private function creerAbsencesTest($etudiantId, $classeId, $dateDebut, $dateFin)
    {
        // Créer des séances de cours si nécessaire
        $seances = DB::table('esbtp_seance_cours')
            ->where('classe_id', $classeId)
            ->limit(2)
            ->get();

        if ($seances->isEmpty()) {
            // Récupérer une matière pour la classe
            $matiere = DB::table('esbtp_matieres')->first();

            if (!$matiere) {
                $this->error("Aucune matière trouvée. Impossible de créer des séances de cours.");
                return;
            }

            // Créer deux séances de cours.
            // Le jour s'écrit par son RANG, comme les deux écrans de saisie : une
            // commande de test qui sème « Lundi » rouvrirait à elle seule l'écart
            // de format que le reste du lot vient de refermer.
            $seanceId1 = DB::table('esbtp_seance_cours')->insertGetId([
                'classe_id' => $classeId,
                'jour' => \App\Domain\EmploiTemps\JourDeLaSemaine::rang('Lundi'),
                'heure_debut' => '08:00:00',
                'heure_fin' => '10:00:00',
                'matiere_id' => $matiere->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $seanceId2 = DB::table('esbtp_seance_cours')->insertGetId([
                'classe_id' => $classeId,
                'jour' => \App\Domain\EmploiTemps\JourDeLaSemaine::rang('Mardi'),
                'heure_debut' => '14:00:00',
                'heure_fin' => '16:00:00',
                'matiere_id' => $matiere->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $seanceIds = [$seanceId1, $seanceId2];
        } else {
            $seanceIds = $seances->pluck('id')->toArray();
        }

        // Dates pour les absences
        $dates = [
            Carbon::parse($dateDebut)->addDays(5)->format('Y-m-d'),
            Carbon::parse($dateDebut)->addDays(12)->format('Y-m-d'),
            Carbon::parse($dateDebut)->addDays(20)->format('Y-m-d')
        ];

        // Créer des absences justifiées
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => $etudiantId,
            'date' => $dates[0],
            'statut' => 'absent_excuse',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'commentaire' => 'Absence justifiée par certificat médical',
            'seance_cours_id' => $seanceIds[0],
            'justified_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Créer des absences non justifiées
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => $etudiantId,
            'date' => $dates[1],
            'statut' => 'absent',
            'heure_debut' => '14:00:00',
            'heure_fin' => '16:00:00',
            'commentaire' => 'Absence non justifiée',
            'seance_cours_id' => $seanceIds[1],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Une autre absence non justifiée
        DB::table('esbtp_attendances')->insert([
            'etudiant_id' => $etudiantId,
            'date' => $dates[2],
            'statut' => 'absent',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'commentaire' => 'Absence non justifiée',
            'seance_cours_id' => $seanceIds[0],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->info("3 absences de test créées avec succès.");
    }
}
