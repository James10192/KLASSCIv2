<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPStudentAccessibilityProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Étape 6 — profils d'accessibilité variés sur des étudiants existants.
 *
 * Génère 5 cas représentatifs (motrice, visuelle, dys, chronique, cognitive)
 * pour permettre une démo concrète de la fonctionnalité de suivi handicap.
 *
 * Idempotent : updateOrCreate sur etudiant_id.
 */
class AccessibilityDemoData
{
    public function __construct(private readonly ?Command $command = null) {}

    public function run(): int
    {
        $etudiants = ESBTPEtudiant::query()
            ->whereHas('inscriptions', fn ($q) => $q->where('status', 'active'))
            ->orderBy('id')
            ->take(20)
            ->get();

        if ($etudiants->isEmpty()) {
            $this->command?->warn('   ⚠ Aucun étudiant trouvé — accessibilité démo skippée.');
            return 0;
        }

        $cases = $this->cases();
        $created = 0;

        foreach ($cases as $i => $case) {
            $etudiant = $etudiants->get($i);
            if (! $etudiant) {
                break;
            }

            ESBTPStudentAccessibilityProfile::updateOrCreate(
                ['etudiant_id' => $etudiant->id],
                array_merge($case, [
                    'effective_from' => Carbon::now()->startOfYear()->toDateString(),
                    'effective_to'   => Carbon::now()->addYear()->endOfYear()->toDateString(),
                    'created_by'     => 1,
                    'updated_by'     => 1,
                ])
            );
            $created++;
        }

        $this->command?->line(sprintf('   • %d profils d\'accessibilité créés (sur %d étudiants disponibles).', $created, $etudiants->count()));
        return $created;
    }

    /**
     * 5 cas métier représentatifs et plausibles pour une démo.
     */
    private function cases(): array
    {
        return [
            // Cas 1 — déficience visuelle partielle, tiers-temps + supports agrandis
            [
                'has_official_recognition' => true,
                'recognition_reference'    => 'CDPH-2025-04210',
                'categories'               => ['visuelle'],
                'short_description'        => 'Déficience visuelle partielle — supports agrandis nécessaires',
                'full_description'         => "Acuité visuelle réduite à 3/10 sur l'œil dominant après accident en 2024. Suivi ophtalmologique trimestriel. Pas de progression attendue.",
                'accommodations'           => ['tiers_temps', 'support_agrandi', 'ordinateur_autorise'],
                'accommodations_notes'     => "Préférer documents en taille 16pt minimum, contraste élevé. L'étudiant utilise un ordinateur portable avec lecteur d'écran ZoomText pendant les épreuves longues.",
                'requires_third_time'      => true,
                'third_time_percentage'    => 33,
                'assistant_required'       => false,
            ],
            // Cas 2 — dyslexie sévère, tiers-temps + ordinateur
            [
                'has_official_recognition' => true,
                'recognition_reference'    => 'CDPH-2024-08123',
                'categories'               => ['dys'],
                'short_description'        => 'Dyslexie sévère — tiers-temps et ordinateur autorisés',
                'full_description'         => "Trouble spécifique du langage écrit diagnostiqué en 2018. Bilan orthophonique de référence joint au dossier. Difficulté de lecture et écriture rapide. Pas de difficulté cognitive associée.",
                'accommodations'           => ['tiers_temps', 'ordinateur_autorise', 'prise_de_notes'],
                'accommodations_notes'     => "Examens écrits sur ordinateur (correction orthographique désactivée). Permettre l'usage d'un correcteur à la fin de l'épreuve sur les 5 dernières minutes.",
                'requires_third_time'      => true,
                'third_time_percentage'    => 33,
                'assistant_required'       => false,
            ],
            // Cas 3 — handicap moteur, salle adaptée + assistant
            [
                'has_official_recognition' => true,
                'recognition_reference'    => 'CDPH-2025-01007',
                'categories'               => ['motrice'],
                'short_description'        => 'Mobilité réduite — fauteuil roulant, salle accessible obligatoire',
                'full_description'         => "Paraplégie partielle suite à un accident de la route en 2022. Autonomie en fauteuil. Suivi kinésithérapique hebdomadaire. Accès à la salle informatique du 2e étage à organiser via ascenseur uniquement.",
                'accommodations'           => ['salle_adaptee', 'tiers_temps', 'repos_examen'],
                'accommodations_notes'     => "Toujours prévoir une salle accessible PMR au rez-de-chaussée. Pause de 10 minutes toutes les heures pour mobilisation.",
                'requires_third_time'      => true,
                'third_time_percentage'    => 50,
                'assistant_required'       => true,
            ],
            // Cas 4 — maladie chronique, repos + tiers-temps
            [
                'has_official_recognition' => false,
                'recognition_reference'    => null,
                'categories'               => ['chronique'],
                'short_description'        => 'Suivi médical pour fatigue chronique — pauses pendant les épreuves',
                'full_description'         => "Syndrome de fatigue chronique post-COVID. Suivi médical en cours, dossier de reconnaissance officielle déposé en mars 2026, en attente de retour. Profil mis à jour dès retour CDPH.",
                'accommodations'           => ['tiers_temps', 'repos_examen'],
                'accommodations_notes'     => "Pauses libres pendant les épreuves de plus de 2h. Récupération difficile après efforts intenses prolongés.",
                'requires_third_time'      => true,
                'third_time_percentage'    => 25,
                'assistant_required'       => false,
            ],
            // Cas 5 — auditive, interprète LSF
            [
                'has_official_recognition' => true,
                'recognition_reference'    => 'CDPH-2024-12055',
                'categories'               => ['auditive'],
                'short_description'        => 'Surdité profonde bilatérale — interprète LSF',
                'full_description'         => "Surdité profonde bilatérale congénitale. Communication via langue des signes française et lecture labiale. Étudiant équipé d'implant cochléaire fonctionnel mais préfère LSF en contexte d'examen.",
                'accommodations'           => ['interprete_lsf', 'prise_de_notes', 'tiers_temps'],
                'accommodations_notes'     => "Interprète LSF à demander 48h avant chaque oral. Pour les écrits : aide à la prise de notes par un pair pendant les cours magistraux.",
                'requires_third_time'      => true,
                'third_time_percentage'    => 33,
                'assistant_required'       => false,
            ],
        ];
    }
}
