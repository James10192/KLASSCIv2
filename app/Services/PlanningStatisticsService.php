<?php

namespace App\Services;

use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPTeacherAttendance;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlanningStatisticsService
{
    /**
     * Calcule les statistiques générales pour une année universitaire
     */
    public function calculerStatistiquesGenerales($anneeId)
    {
        $query = ESBTPSeanceCours::query();

        if ($anneeId) {
            $query->whereHas("emploiTemps", function ($q) use ($anneeId) {
                $q->where(
                    "esbtp_emploi_temps.annee_universitaire_id",
                    $anneeId,
                );
            });
        }

        return [
            "total_seances" => $query->count(),
            "total_heures" => $query->sum(
                DB::raw("TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600"),
            ),
            "total_classes" => ESBTPClasse::whereHas("emploiTemps", function (
                $q,
            ) use ($anneeId) {
                if ($anneeId) {
                    $q->where(
                        "esbtp_emploi_temps.annee_universitaire_id",
                        $anneeId,
                    );
                }
            })->count(),
            "total_matieres" => ESBTPMatiere::whereHas(
                "seancesCours",
                function ($q) use ($anneeId) {
                    if ($anneeId) {
                        $q->whereHas("emploiTemps", function ($q2) use (
                            $anneeId,
                        ) {
                            $q2->where(
                                "esbtp_emploi_temps.annee_universitaire_id",
                                $anneeId,
                            );
                        });
                    }
                },
            )->count(),
            "total_enseignants" => User::role("enseignant")
                ->whereHas("seancesCours", function ($q) use ($anneeId) {
                    if ($anneeId) {
                        $q->whereHas("emploiTemps", function ($q2) use (
                            $anneeId,
                        ) {
                            $q2->where(
                                "esbtp_emploi_temps.annee_universitaire_id",
                                $anneeId,
                            );
                        });
                    }
                })
                ->count(),
        ];
    }

    /**
     * Calcule la répartition des heures par matière
     */
    public function calculerRepartitionMatieres($anneeId, $periode = "annee")
    {
        // Récupérer les heures réalisées par matière
        $query = ESBTPSeanceCours::with("matiere")
            ->select(
                "matiere_id",
                DB::raw("COUNT(*) as nb_seances"),
                DB::raw(
                    "SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures",
                ),
            )
            ->groupBy("matiere_id");

        if ($anneeId) {
            $query->whereHas("emploiTemps", function ($q) use ($anneeId) {
                $q->where(
                    "esbtp_emploi_temps.annee_universitaire_id",
                    $anneeId,
                );
            });
        }

        $results = $query->get();

        // Récupérer les heures planifiées par matière selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with("matiere")
            ->select(
                "matiere_id",
                DB::raw("SUM(volume_horaire_total) as heures_planifiees"),
            )
            ->groupBy("matiere_id");

        if ($anneeId) {
            $planificationsQuery->where("annee_universitaire_id", $anneeId);
        }

        // Filtrer par semestre si spécifié
        if ($periode === "semestre1") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 1)->orWhereNull("semestre");
            });
        } elseif ($periode === "semestre2") {
            $planificationsQuery->where(function ($query) {
                $query->where("semestre", 2)->orWhereNull("semestre");
            });
        }

        $planifications = $planificationsQuery->get()->keyBy("matiere_id");

        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum("total_heures");

        return $results->map(function ($item) use (
            $totalHeures,
            $planifications,
            $periode,
        ) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification
                ? $planification->heures_planifiees
                : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);

            return [
                "matiere" => $item->matiere,
                "nb_seances" => $item->nb_seances,
                "total_heures" => round($item->total_heures, 2),
                "heures_planifiees" => round($heuresPlanifiees, 2),
                "heures_restantes" => round($heuresRestantes, 2),
                "pourcentage_realise" =>
                    $heuresPlanifiees > 0
                        ? round(
                            ($item->total_heures / $heuresPlanifiees) * 100,
                            1,
                        )
                        : 0,
                "pourcentage" =>
                    $totalHeures > 0
                        ? round(($item->total_heures / $totalHeures) * 100, 1)
                        : 0,
                "est_configure" => $heuresPlanifiees > 0,
                "periode" => $periode,
            ];
        });
    }

    /**
     * Calcule la charge horaire par matière pour un enseignant
     */
    public function calculerChargeHoraireEnseignant($enseignantId, $anneeId)
    {
        $query = ESBTPSeanceCours::where("teacher_id", $enseignantId)
            ->where("type", ESBTPSeanceCours::TYPE_COURSE)
            ->with("matiere")
            ->select(
                "matiere_id",
                DB::raw("COUNT(*) as nb_seances"),
                DB::raw(
                    "SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures",
                ),
            )
            ->groupBy("matiere_id");

        if ($anneeId) {
            $query->whereHas("emploiTemps", function ($q) use ($anneeId) {
                $q->where(
                    "esbtp_emploi_temps.annee_universitaire_id",
                    $anneeId,
                );
            });
        }

        return $query->get();
    }

    /**
     * Calcule les statistiques d'émargement
     */
    public function calculerStatsEmargement($anneeSelectionnee)
    {
        if (!$anneeSelectionnee) {
            return [
                "total_emargements_aujourd_hui" => 0,
                "enseignants_emarges_aujourd_hui" => 0,
                "codes_generes_semaine" => 0,
                "taux_emargement_semaine" => 0,
            ];
        }

        $aujourd_hui = now()->toDateString();
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        return [
            "total_emargements_aujourd_hui" => ESBTPTeacherAttendance::whereDate(
                "created_at",
                $aujourd_hui,
            )->count(),
            "enseignants_emarges_aujourd_hui" => ESBTPTeacherAttendance::whereDate(
                "created_at",
                $aujourd_hui,
            )
                ->distinct("teacher_id")
                ->count(),
            "codes_generes_semaine" => ESBTPDailyCode::whereBetween(
                "created_at",
                [$debutSemaine, $finSemaine],
            )->count(),
            "taux_emargement_semaine" => $this->calculerTauxEmargementSemaine(),
        ];
    }

    /**
     * Calcule le taux d'émargement de la semaine
     */
    private function calculerTauxEmargementSemaine()
    {
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        $enseignantsActifs = User::role(["enseignant"])
            ->where("is_active", true)
            ->count();
        $emargements = ESBTPTeacherAttendance::whereBetween("created_at", [
            $debutSemaine,
            $finSemaine,
        ])
            ->distinct("teacher_id")
            ->count();

        return $enseignantsActifs > 0
            ? round(($emargements / $enseignantsActifs) * 100, 1)
            : 0;
    }

    /**
     * Calcule la répartition des matières pour une classe
     */
    public function calculerRepartitionMatieresClasse(
        $classeId,
        $anneeId,
        $periode = "annee",
    ) {
        // Récupérer les informations de la classe pour filtrer les planifications
        $classe = ESBTPClasse::find($classeId);

        $query = ESBTPSeanceCours::with("matiere")
            ->whereHas("emploiTemps", function ($q) use ($classeId, $anneeId) {
                $q->where("classe_id", $classeId);
                if ($anneeId) {
                    $q->where(
                        "esbtp_emploi_temps.annee_universitaire_id",
                        $anneeId,
                    );
                }
            })
            ->select(
                "matiere_id",
                DB::raw("COUNT(*) as nb_seances"),
                DB::raw(
                    "SUM(TIME_TO_SEC(TIMEDIFF(heure_fin, heure_debut))/3600) as total_heures",
                ),
            )
            ->groupBy("matiere_id");

        $results = $query->get();

        // Récupérer les heures planifiées pour cette classe selon la période
        $planificationsQuery = ESBTPPlanificationAcademique::with("matiere")
            ->select(
                "matiere_id",
                DB::raw("SUM(volume_horaire_total) as heures_planifiees"),
            )
            ->groupBy("matiere_id");

        if ($anneeId) {
            $planificationsQuery->where("annee_universitaire_id", $anneeId);
        }

        // Filtrer par classe (filière et niveau)
        if ($classe) {
            $planificationsQuery
                ->where("filiere_id", $classe->filiere_id)
                ->where("niveau_etude_id", $classe->niveau_id);
        }

        // Filtrer par semestre si spécifié
        if ($periode === "semestre1") {
            $planificationsQuery->where("semestre", 1);
        } elseif ($periode === "semestre2") {
            $planificationsQuery->where("semestre", 2);
        }

        $planifications = $planificationsQuery->get()->keyBy("matiere_id");

        // Calcul du total pour les pourcentages
        $totalHeures = $results->sum("total_heures");

        return $results->map(function ($item) use (
            $totalHeures,
            $planifications,
            $periode,
        ) {
            $planification = $planifications->get($item->matiere_id);
            $heuresPlanifiees = $planification
                ? $planification->heures_planifiees
                : 0;
            $heuresRestantes = max(0, $heuresPlanifiees - $item->total_heures);

            return [
                "matiere" => $item->matiere,
                "nb_seances" => $item->nb_seances,
                "total_heures" => round($item->total_heures, 2),
                "heures_planifiees" => round($heuresPlanifiees, 2),
                "heures_restantes" => round($heuresRestantes, 2),
                "pourcentage_realise" =>
                    $heuresPlanifiees > 0
                        ? round(
                            ($item->total_heures / $heuresPlanifiees) * 100,
                            1,
                        )
                        : 0,
                "pourcentage" =>
                    $totalHeures > 0
                        ? round(($item->total_heures / $totalHeures) * 100, 1)
                        : 0,
                "est_configure" => $heuresPlanifiees > 0,
                "periode" => $periode,
            ];
        });
    }

    /**
     * Calcule l'impact des émargements sur les planifications
     */
    public function calculerImpactEmargements(
        $anneeId,
        $filiereId = null,
        $niveauId = null,
        $periodeDebut = null,
        $periodeFin = null,
    ) {
        $query = ESBTPPlanificationAcademique::with([
            "matiere",
            "enseignantPrincipal",
            "filiere",
            "niveauEtude",
        ])->where("annee_universitaire_id", $anneeId);

        if ($filiereId) {
            $query->where("filiere_id", $filiereId);
        }
        if ($niveauId) {
            $query->where("niveau_etude_id", $niveauId);
        }

        $planifications = $query->get();

        return $planifications
            ->map(function ($planification) use ($periodeDebut, $periodeFin) {
                // Récupérer les émargements validés pour cette planification
                $emargements = $this->getEmargementsValidesParPlanification(
                    $planification,
                    $periodeDebut,
                    $periodeFin,
                );

                // Calculer les heures effectuées via émargements
                $heuresEmargement = $emargements->sum(function ($emargement) {
                    if ($emargement->course) {
                        return Carbon::parse(
                            $emargement->course->heure_fin,
                        )->diffInMinutes(
                            Carbon::parse($emargement->course->heure_debut),
                        ) / 60;
                    }
                    return 0;
                });

                // Progression calculée
                $tauxProgression =
                    $planification->volume_horaire_total > 0
                        ? round(
                            ($planification->heures_effectuees /
                                $planification->volume_horaire_total) *
                                100,
                            1,
                        )
                        : 0;

                $tauxProgressionEmargement =
                    $planification->volume_horaire_total > 0
                        ? round(
                            ($heuresEmargement /
                                $planification->volume_horaire_total) *
                                100,
                            1,
                        )
                        : 0;

                return [
                    "planification" => $planification,
                    "heures_planifiees" => $planification->volume_horaire_total,
                    "heures_effectuees_base" =>
                        $planification->heures_effectuees ?? 0,
                    "heures_emargement" => round($heuresEmargement, 2),
                    "nb_emargements_valides" => $emargements->count(),
                    "taux_progression_base" => $tauxProgression,
                    "taux_progression_emargement" => $tauxProgressionEmargement,
                    "ecart_heures" => round(
                        $heuresEmargement -
                            ($planification->heures_effectuees ?? 0),
                        2,
                    ),
                    "derniere_maj_heures" =>
                        $planification->derniere_mise_a_jour_heures,
                    "statut_synchronisation" => $this->evaluerStatutSynchronisation(
                        $planification,
                        $heuresEmargement,
                    ),
                    "emargements_recents" => $emargements->take(5),
                ];
            })
            ->sortByDesc("nb_emargements_valides");
    }

    /**
     * Récupérer les émargements validés pour une planification
     */
    private function getEmargementsValidesParPlanification(
        $planification,
        $periodeDebut = null,
        $periodeFin = null,
    ) {
        $query = ESBTPTeacherAttendance::with("course")
            ->where("status", "validated")
            ->whereHas("course", function ($q) use ($planification) {
                $q->where("matiere_id", $planification->matiere_id)->where(
                    "teacher_id",
                    $planification->enseignant_principal_id,
                );
            });

        if ($periodeDebut) {
            $query->where("date", ">=", $periodeDebut);
        }
        if ($periodeFin) {
            $query->where("date", "<=", $periodeFin);
        }

        return $query->orderBy("date", "desc")->get();
    }

    /**
     * Évaluer le statut de synchronisation entre planification et émargements
     */
    private function evaluerStatutSynchronisation(
        $planification,
        $heuresEmargement,
    ) {
        $heuresEffectuees = $planification->heures_effectuees ?? 0;
        $ecart = abs($heuresEmargement - $heuresEffectuees);

        if ($ecart < 0.5) {
            return [
                "statut" => "synchronise",
                "message" => "Parfaitement synchronisé",
            ];
        } elseif ($ecart < 2) {
            return [
                "statut" => "leger_ecart",
                "message" => "Léger écart acceptable",
            ];
        } elseif ($heuresEmargement > $heuresEffectuees) {
            return [
                "statut" => "emargement_superieur",
                "message" => "Émargements en avance sur planification",
            ];
        } else {
            return [
                "statut" => "planification_superieure",
                "message" => "Planification en avance sur émargements",
            ];
        }
    }
}
