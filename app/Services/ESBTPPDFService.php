<?php

namespace App\Services;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultatMatiere;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\NumberToWords;
use App\Helpers\SettingsHelper;

class ESBTPPDFService
{
    protected $numberToWords;

    public function __construct(NumberToWords $numberToWords)
    {
        $this->numberToWords = $numberToWords;
    }

    /**
     * Générer le bulletin en PDF
     */
    public function genererBulletinPDF(ESBTPBulletin $bulletin)
    {
        $etudiant = $bulletin->etudiant;
        $classe = $bulletin->classe;
        $resultats = $bulletin->resultats->sortBy('matiere.name');
        $absences = [
            'justifiees' => $bulletin->absences_justifiees,
            'non_justifiees' => $bulletin->absences_non_justifiees,
            'total' => $bulletin->absences_justifiees + $bulletin->absences_non_justifiees
        ];

        $data = [
            'bulletin' => $bulletin,
            'etudiant' => $etudiant,
            'classe' => $classe,
            'resultats' => $resultats,
            'absences' => $absences,
            'moyenne_en_lettres' => $this->numberToWords->convert($bulletin->moyenne_generale),
            'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL')
        ];

        $pdf = PDF::loadView('pdf.bulletin', $data);
        $pdf->setPaper('A4');

        return $pdf;
    }

    /**
     * Générer le relevé de notes en PDF
     */
    public function genererRelevePDF(ESBTPEtudiant $etudiant, $anneeUniversitaireId)
    {
        $bulletins = $etudiant->bulletins()
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->orderBy('periode')
            ->get();

        $data = [
            'etudiant' => $etudiant,
            'bulletins' => $bulletins,
            'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL')
        ];

        $pdf = PDF::loadView('pdf.releve', $data);
        $pdf->setPaper('A4');

        return $pdf;
    }

    /**
     * Générer une évaluation en PDF avec les notes des étudiants
     */
    public function genererEvaluationPDF($evaluation)
    {
        // Chargement des relations nécessaires
        $evaluation->load([
            'matiere',
            'classe',
            'notes.etudiant',
            'createdBy',
            'updatedBy'
        ]);

        // Préparation des statistiques
        $notes = $evaluation->notes;
        $stats = [
            'moyenne' => $notes->count() > 0 ? $notes->avg('note') : 0,
            'max' => $notes->count() > 0 ? $notes->max('note') : 0,
            'min' => $notes->count() > 0 ? $notes->min('note') : 0,
            'total_notes' => $notes->count(),
            'reussite' => $notes->count() > 0
                ? ($notes->filter(function ($note) use ($evaluation) {
                    return $note->note >= ($evaluation->bareme / 2);
                  })->count() / $notes->count()) * 100
                : 0
        ];

        // Préparation des données pour la vue
        $data = [
            'evaluation' => $evaluation,
            'notes' => $notes->sortBy(function($note) {
                return $note->etudiant->nom . ' ' . $note->etudiant->prenom;
            }),
            'stats' => $stats,
            'config' => [
                'school_name' => \App\Helpers\SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
                'school_logo' => \App\Helpers\SettingsHelper::get('school_logo', 'images/LOGO-KLASSCI-PNG.png'),
                'school_address' => \App\Helpers\SettingsHelper::get('school_address', ''),
                'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
                'school_email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            ],
            'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL')
        ];

        // Génération du PDF
        $pdf = PDF::loadView('pdf.evaluation', $data);
        $pdf->setPaper('A4');

        return $pdf;
    }

    /**
     * Générer le PV de délibération en PDF
     */
    public function genererPVDeliberationPDF(ESBTPClasse $classe, $periode, $anneeUniversitaireId)
    {
        $bulletins = ESBTPBulletin::where('classe_id', $classe->id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->orderBy('rang')
            ->get();

        $matieres = $classe->matieres->sortBy('name');

        $data = [
            'classe' => $classe,
            'periode' => $periode,
            'bulletins' => $bulletins,
            'matieres' => $matieres,
            'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL')
        ];

        $pdf = PDF::loadView('pdf.pv_deliberation', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    /**
     * Générer le rapport d'absences en PDF
     */
    public function genererRapportAbsencesPDF(ESBTPClasse $classe, $periode, $anneeUniversitaireId)
    {
        $absenceService = app(ESBTPAbsenceService::class);
        $rapport = $absenceService->genererRapportClasse($classe, $periode, $anneeUniversitaireId);

        $data = [
            'classe' => $classe,
            'periode' => $periode,
            'rapport' => $rapport,
            'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL')
        ];

        $pdf = PDF::loadView('pdf.rapport_absences', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    /**
     * Générer l'emploi du temps en PDF
     *
     * @param ESBTPEmploiTemps $emploiTemps
     * @return \Barryvdh\DomPDF\PDF
     */
    public function genererEmploiTempsPDF(ESBTPEmploiTemps $emploiTemps)
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $emploiTemps->load([
                'seances.matiere',
                'classe',
                'classe.filiere',
                'classe.niveau',
                'annee',
            ]);

            $seancesParJour = $emploiTemps->getSeancesParJour();

            $heuresDebut = [];
            $heuresFin = [];
            for ($heure = 8; $heure < 18; $heure++) {
                $heuresDebut[] = sprintf('%02d:00', $heure);
                $heuresFin[] = sprintf('%02d:00', $heure + 1);
            }

            $joursNoms = [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
            ];

            $timeSlots = $heuresDebut;
            $days = array_keys($joursNoms);

            $matiereStats = [];
            foreach ($emploiTemps->seances as $seance) {
                $matiereName = $seance->matiere ? $seance->matiere->name : 'Non définie';
                $matiereStats[$matiereName] = ($matiereStats[$matiereName] ?? 0) + 1;
            }
            $matiereStats = collect($matiereStats)->sortDesc();

            $totalSeances = $emploiTemps->seances->count();
            $totalMinutes = $emploiTemps->seances->reduce(function ($carry, $seance) {
                $start = $seance->heure_debut instanceof Carbon
                    ? $seance->heure_debut->copy()
                    : ($seance->heure_debut ? Carbon::parse($seance->heure_debut) : null);
                $end = $seance->heure_fin instanceof Carbon
                    ? $seance->heure_fin->copy()
                    : ($seance->heure_fin ? Carbon::parse($seance->heure_fin) : null);

                if ($start && $end) {
                    if ($end->lessThanOrEqualTo($start)) {
                        $end = $end->addDay();
                    }
                    return $carry + $start->diffInMinutes($end);
                }

                return $carry;
            }, 0);
            $totalHours = $totalMinutes > 0 ? $totalMinutes / 60 : 0;
            $totalHoursFormatted = $totalHours > 0
                ? rtrim(rtrim(number_format($totalHours, 1, ',', ' '), '0'), ',') . ' h'
                : '0 h';

            $sessionTypeLabels = [
                ESBTPSeanceCours::TYPE_COURSE => 'Cours',
                ESBTPSeanceCours::TYPE_HOMEWORK => 'Devoir',
                ESBTPSeanceCours::TYPE_BREAK => 'Récréation',
                ESBTPSeanceCours::TYPE_LUNCH => 'Pause déjeuner',
            ];

            $sessionTypeColors = [
                ESBTPSeanceCours::TYPE_COURSE => ['bg' => '#0453cb', 'text' => '#ffffff'],
                ESBTPSeanceCours::TYPE_HOMEWORK => ['bg' => '#3ba54f', 'text' => '#ffffff'],
                ESBTPSeanceCours::TYPE_BREAK => ['bg' => '#f59e0b', 'text' => '#1f2937'],
                ESBTPSeanceCours::TYPE_LUNCH => ['bg' => '#0ea5e9', 'text' => '#ffffff'],
                'default' => ['bg' => '#5e91de', 'text' => '#ffffff'],
            ];

            $sessionTypeStats = $emploiTemps->seances
                ->groupBy(function ($seance) {
                    return $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
                })
                ->map
                ->count()
                ->toArray();

            $sessionTypeSwatches = [];
            foreach ($emploiTemps->seances as $seance) {
                $type = $seance->type ?? ESBTPSeanceCours::TYPE_COURSE;
                if (!isset($sessionTypeSwatches[$type])) {
                    $sessionTypeSwatches[$type] = [
                        'bg' => $seance->color ?: ($sessionTypeColors[$type]['bg'] ?? $sessionTypeColors['default']['bg']),
                        'text' => $sessionTypeColors[$type]['text'] ?? '#ffffff',
                    ];
                }
            }
            foreach ($sessionTypeLabels as $type => $label) {
                if (!isset($sessionTypeSwatches[$type])) {
                    $sessionTypeSwatches[$type] = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                }
            }

            $uniqueMatieres = $matiereStats->count();
            $uniqueTeachers = $emploiTemps->seances
                ->map(function ($seance) {
                    if ($seance->teacher_id) {
                        return 'teacher_' . $seance->teacher_id;
                    }
                    if (!empty($seance->enseignant)) {
                        return 'name_' . $seance->enseignant;
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->count();

            $daysCovered = $emploiTemps->seances->pluck('jour')->filter()->unique()->count();

            $summaryStats = [
                [
                    'label' => 'Séances programmées',
                    'value' => $totalSeances,
                    'description' => 'Total des séances planifiées',
                ],
                [
                    'label' => 'Volume horaire',
                    'value' => $totalHoursFormatted,
                    'description' => 'Durée cumulée des séances',
                ],
                [
                    'label' => 'Matières couvertes',
                    'value' => $uniqueMatieres,
                    'description' => 'Diversité pédagogique hebdomadaire',
                ],
                [
                    'label' => 'Intervenants mobilisés',
                    'value' => $uniqueTeachers,
                    'description' => 'Enseignants affectés à la classe',
                ],
            ];

            $periodeAffichage = null;
            if ($emploiTemps->date_debut && $emploiTemps->date_fin) {
                $periodeAffichage = Carbon::parse($emploiTemps->date_debut)->locale('fr')->isoFormat('LL')
                    . ' → '
                    . Carbon::parse($emploiTemps->date_fin)->locale('fr')->isoFormat('LL');
            } elseif ($emploiTemps->annee && $emploiTemps->annee->name) {
                $periodeAffichage = $emploiTemps->annee->name;
            } elseif (!empty($emploiTemps->semestre)) {
                $periodeAffichage = 'Semestre ' . $emploiTemps->semestre;
            }

            $config = [
                'school_name' => SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
                'school_type' => SettingsHelper::get('school_type', ''),
                'school_authorization' => SettingsHelper::get('school_authorization_number', ''),
                'school_address' => SettingsHelper::get('school_address', ''),
                'school_phone' => SettingsHelper::get('school_phone', ''),
                'school_email' => SettingsHelper::get('school_email', ''),
                'school_website' => SettingsHelper::get('school_website', ''),
                'school_city' => SettingsHelper::get('school_city', ''),
                'school_country' => SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
                'director_name' => SettingsHelper::get('director_name', ''),
                'director_title' => SettingsHelper::get('director_title', 'Directeur'),
                'school_logo' => SettingsHelper::get('school_logo', ''),
            ];
            $logoBase64 = $this->prepareLogoBase64($config['school_logo']);

            $etablissementInfo = [
                'nom' => $config['school_name'],
                'adresse' => $config['school_address'],
                'telephone' => $config['school_phone'],
                'email' => $config['school_email'],
                'ville' => $config['school_city'],
                'pays' => $config['school_country'],
                'type' => $config['school_type'],
            ];

            $data = [
                'emploiTemps' => $emploiTemps,
                'seances' => $emploiTemps->seances,
                'seancesParJour' => $seancesParJour,
                'heuresDebut' => $heuresDebut,
                'heuresFin' => $heuresFin,
                'joursNoms' => $joursNoms,
                'matiereStats' => $matiereStats,
                'timeSlots' => $timeSlots,
                'days' => $days,
                'date_edition' => Carbon::now()->locale('fr')->isoFormat('LL'),
                'settings' => $config,
                'logoBase64' => $logoBase64,
                'sessionTypeColors' => $sessionTypeColors,
                'sessionTypeLabels' => $sessionTypeLabels,
                'sessionTypeSwatches' => $sessionTypeSwatches,
                'sessionTypeStats' => $sessionTypeStats,
                'summaryStats' => $summaryStats,
                'totalSeances' => $totalSeances,
                'totalHoursFormatted' => $totalHoursFormatted,
                'daysCovered' => $daysCovered,
                'periodeAffichage' => $periodeAffichage,
                'etablissement' => $etablissementInfo,
            ];

            // Utiliser Browsershot/Browserless.io pour support CSS Grid complet
            // Utiliser le même template que le preview pour cohérence visuelle
            $html = view('pdf.emploi-temps', $data)->render();

            // Si Browserless.io est configuré (production) - utiliser API HTTP
            if (config('services.browserless.enabled', false)) {
                $apiKey = config('services.browserless.api_key');
                $endpoint = config('services.browserless.endpoint', 'https://chrome.browserless.io');

                // Appel API Browserless.io avec Guzzle HTTP Client
                $client = new \GuzzleHttp\Client(['timeout' => 60]);

                try {
                    $response = $client->post("{$endpoint}/pdf?token={$apiKey}", [
                        'json' => [
                            'html' => $html,
                            'options' => [
                                'format' => 'A4',
                                'landscape' => true,
                                'margin' => [
                                    'top' => '10mm',
                                    'right' => '10mm',
                                    'bottom' => '10mm',
                                    'left' => '10mm',
                                ],
                                'printBackground' => true,
                            ],
                            // waitUntil est au niveau racine, pas dans options
                            'gotoOptions' => [
                                'waitUntil' => 'networkidle0',
                            ],
                        ],
                    ]);

                    $pdf = $response->getBody()->getContents();

                    if (!$pdf) {
                        throw new \Exception("Browserless.io returned empty PDF");
                    }

                    return $pdf;
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    \Log::error('Browserless.io API error', [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw new \Exception("Browserless.io API error: " . $e->getMessage());
                }
            }

            // Fallback: Puppeteer local (développement)
            $pdf = \Spatie\Browsershot\Browsershot::html($html)
                ->paperSize(297, 210) // A4 paysage (largeur, hauteur en mm)
                ->margins(10, 10, 10, 10) // top, right, bottom, left en mm
                ->waitUntilNetworkIdle()
                ->pdf();

            return $pdf;
        } catch (\Exception $e) {
            \Log::error('PDF Generation - Error occurred', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function genererDocumentPdfFromHtml(string $html, array $options = []): string
    {
        $options = array_merge([
            'format' => 'A4',
            'landscape' => false,
            'margin' => ['top' => '10mm', 'right' => '10mm', 'bottom' => '10mm', 'left' => '10mm'],
        ], $options);

        if (config('services.browserless.enabled', false)) {
            $apiKey = config('services.browserless.api_key');
            $endpoint = config('services.browserless.endpoint', 'https://chrome.browserless.io');
            $client = new \GuzzleHttp\Client(['timeout' => 60]);

            $response = $client->post("{$endpoint}/pdf?token={$apiKey}", [
                'json' => [
                    'html' => $html,
                    'options' => [
                        'format' => $options['format'],
                        'landscape' => $options['landscape'],
                        'margin' => $options['margin'],
                        'printBackground' => true,
                    ],
                    'gotoOptions' => [
                        'waitUntil' => 'networkidle0',
                    ],
                ],
            ]);

            $pdf = $response->getBody()->getContents();
            if (! $pdf) {
                throw new \Exception('Browserless.io returned empty PDF');
            }

            return $pdf;
        }

        // Browsershot::margins() attend des float (ex: 10.0), pas des strings (ex: '10mm')
        // On extrait la valeur numérique en supprimant toute unité CSS éventuelle
        $toFloat = fn($v) => (float) preg_replace('/[^0-9.]/', '', (string) $v);

        return \Spatie\Browsershot\Browsershot::html($html)
            ->format($options['format'])
            ->landscape($options['landscape'])
            ->margins(
                $toFloat($options['margin']['top']),
                $toFloat($options['margin']['right']),
                $toFloat($options['margin']['bottom']),
                $toFloat($options['margin']['left'])
            )
            ->waitUntilNetworkIdle()
            ->pdf();
    }

    private function prepareLogoBase64($logoPath): ?string
    {
        if (empty($logoPath)) {
            return null;
        }

        $storagePath = storage_path('app/public/' . $logoPath);
        if (file_exists($storagePath)) {
            $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($storagePath);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        $publicPath = public_path($logoPath);
        if (file_exists($publicPath)) {
            $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
            $logoData = file_get_contents($publicPath);
            return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
        }

        return null;
    }
}
