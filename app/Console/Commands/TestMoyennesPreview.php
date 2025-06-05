<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPResultat;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestMoyennesPreview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:moyennes-preview {etudiant_id=15} {classe_id=1} {periode=semestre1} {annee_universitaire_id=6}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test des données pour la page moyennes-preview';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $etudiantId = $this->argument('etudiant_id');
        $classeId = $this->argument('classe_id');
        $periode = $this->argument('periode');
        $anneeUniversitaireId = $this->argument('annee_universitaire_id');

        $this->info("🔍 Test du contrôleur previewMoyennes...\n");

        try {
            // Simuler l'authentification
            $user = User::where('username', 'superadmin')->first();
            if ($user) {
                Auth::login($user);
                $this->info("✅ Utilisateur connecté: {$user->name}\n");
            }

            // Créer une requête simulée
            $request = new Request([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'periode' => $periode,
                'annee_universitaire_id' => $anneeUniversitaireId
            ]);

            // Créer une instance du contrôleur
            $absenceService = app(\App\Services\ESBTP\ESBTPAbsenceService::class);
            $controller = new \App\Http\Controllers\ESBTPBulletinController($absenceService);

            $this->info("📊 Paramètres de test:");
            $this->info("- Étudiant ID: {$etudiantId}");
            $this->info("- Classe ID: {$classeId}");
            $this->info("- Période: {$periode}");
            $this->info("- Année universitaire ID: {$anneeUniversitaireId}\n");

            // Appeler la méthode previewMoyennes
            $response = $controller->previewMoyennes($request);

            // Vérifier le type de réponse
            if ($response instanceof \Illuminate\View\View) {
                $this->info("✅ Vue retournée avec succès\n");

                // Récupérer les données de la vue
                $viewData = $response->getData();

                $this->info("📋 Données passées à la vue:");
                $this->info("- etudiant: " . ($viewData['etudiant']->nom ?? 'N/A') . " " . ($viewData['etudiant']->prenoms ?? 'N/A'));
                $this->info("- classe: " . ($viewData['classe']->name ?? 'N/A'));
                $this->info("- periode: " . ($viewData['periode'] ?? 'N/A'));
                $this->info("- anneeUniversitaire: " . ($viewData['anneeUniversitaire']->annee_debut ?? 'N/A') . "-" . ($viewData['anneeUniversitaire']->annee_fin ?? 'N/A'));

                $resultatsData = $viewData['resultatsData'] ?? [];
                $notesByMatiere = $viewData['notesByMatiere'] ?? [];

                $this->info("\n📈 Résultats dans resultatsData: " . count($resultatsData));

                if (count($resultatsData) > 0) {
                    $this->info("\n📋 Détails des résultats:");
                    foreach ($resultatsData as $matiereId => $resultat) {
                        $this->info("- Matière ID {$matiereId}: " . ($resultat['matiere']->name ?? 'N/A'));
                        $this->info("  Moyenne: " . ($resultat['moyenne'] ?? 'N/A'));
                        $this->info("  Coefficient: " . ($resultat['coefficient'] ?? 'N/A'));
                        $this->info("  ID résultat: " . ($resultat['id'] ?? 'Nouveau'));
                        $this->info("  Appréciation: " . ($resultat['appreciation'] ?? 'Aucune') . "\n");
                    }
                } else {
                    $this->warn("❌ Aucun résultat dans resultatsData");
                }

                $this->info("\n📝 Notes par matière dans notesByMatiere: " . count($notesByMatiere));

                if (count($notesByMatiere) > 0) {
                    foreach ($notesByMatiere as $matiereId => $matiereData) {
                        $this->info("- Matière ID {$matiereId}: " . ($matiereData['matiere']->name ?? 'N/A'));
                        $this->info("  Moyenne calculée: " . number_format($matiereData['moyenne'] ?? 0, 2));
                        $this->info("  Nombre de notes: " . count($matiereData['notes'] ?? []));
                    }
                }

            } else {
                $this->error("❌ Réponse inattendue du contrôleur");
                $this->error("Type de réponse: " . get_class($response));
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->error("📍 Fichier: " . $e->getFile() . " ligne " . $e->getLine());
        }
    }
}
