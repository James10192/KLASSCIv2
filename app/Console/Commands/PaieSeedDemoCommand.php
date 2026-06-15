<?php

namespace App\Console\Commands;

use App\Enums\TypeSeance;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEnseignantTauxSeance;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Données démo testables pour la PAIE ENSEIGNANTS, en symbiose avec l'UI :
 *  - taux horaires des enseignants existants : esbtp_enseignant_taux_seance (+ taux_horaire)
 *  - séances dans de NOUVEAUX emplois du temps, EN SUIVANT LE PLANNING HORAIRE
 *    (esbtp_planifications_academiques : volumes CM/TD/TP par matière + enseignant
 *    principal assigné), classes BTS ET LMD.
 *
 * On NE crée PAS de données de paie (aucun bulletin/salaire). L'utilisateur génère
 * lui-même le bulletin. Les séances passées sont émargées (présent/retard) pour que
 * le calcul ait des heures réalisées ; les plus récentes restent à émarger (test UI).
 *
 * Idempotent : les emplois du temps démo (titre préfixé) et leurs séances/émargements
 * sont purgés avant chaque exécution. --dry-run pour investiguer sans écrire.
 */
class PaieSeedDemoCommand extends Command
{
    protected $signature = 'paie:seed-demo
        {--weeks=6 : Nombre de semaines passées de séances à générer}
        {--max-matieres=5 : Nombre max de matières (planifications) par classe}
        {--dry-run : Ne rien écrire, juste rapporter ce qui serait fait}';

    protected $description = 'Seed démo paie : taux profs + séances suivant le planning horaire (BTS + LMD)';

    private const ET_PREFIX = 'Démo paie — ';
    private const MARKER = 'DEMO_PAIE_SEED';

    /** Profils de taux variés (FCFA/h). */
    private const TAUX_PROFILES = [
        ['CM' => 8000, 'TD' => 6000, 'TP' => 5000, 'def' => 5000],
        ['CM' => 10000, 'TD' => 7500, 'TP' => 6000, 'def' => 6000],
        ['CM' => 7000, 'TD' => 5500, 'TP' => 4500, 'def' => 4500],
        ['CM' => 12000, 'TD' => 9000, 'TP' => 7000, 'def' => 7000],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $weeks = max(1, (int) $this->option('weeks'));
        $maxMat = max(1, (int) $this->option('max-matieres'));

        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$annee) {
            $this->error('Aucune année universitaire courante.');
            return self::FAILURE;
        }
        $this->info("Année : {$annee->name} (#{$annee->id})");

        // Classes cibles : 1 LMD + 1 BTS qui ont des planifications (planning horaire).
        $lmd = $this->classeAvecPlanif($annee, 'LMD');
        $bts = $this->classeAvecPlanif($annee, 'BTS');
        $cibles = collect([$lmd, $bts])->filter()->values();

        if ($cibles->isEmpty()) {
            $this->error('Aucune classe avec planification trouvée (esbtp_planifications_academiques).');
            return self::FAILURE;
        }

        // Pool d'enseignants pour fallback (planif sans enseignant principal).
        $pool = ESBTPTeacher::with('user')->whereHas('user')->orderBy('id')->get();
        if ($pool->isEmpty()) {
            $this->error('Aucun enseignant (esbtp_teachers avec user).');
            return self::FAILURE;
        }

        $durations = [60, 90, 120, 180]; // durées VARIÉES (séances pas toutes 1h)
        $startHours = ['08:00', '10:00', '13:30', '15:00'];

        $usedTeachers = collect();
        $report = [];

        foreach ($cibles as $classe) {
            $planifs = $this->planifsDeClasse($annee, $classe)->take($maxMat);
            $report[] = "Classe {$classe->name} ({$classe->systeme_academique}) → " . $planifs->count() . ' matière(s) planifiée(s)';
        }

        if ($dry) {
            foreach ($report as $r) {
                $this->line('  ' . $r);
            }
            $this->warn('[DRY-RUN] Aucune écriture. Lancez sans --dry-run pour générer.');
            return self::SUCCESS;
        }

        $stats = ['emplois' => 0, 'seances' => 0, 'emargements' => 0, 'taux' => 0];

        DB::transaction(function () use (
            $cibles, $annee, $pool, $weeks, $maxMat, $durations, $startHours, &$usedTeachers, &$stats
        ) {
            // Purge démo précédente (idempotence).
            $oldSeances = ESBTPSeanceCours::where('description', self::MARKER)->pluck('id');
            if ($oldSeances->isNotEmpty()) {
                ESBTPTeacherAttendance::whereIn('course_id', $oldSeances)->delete();
                ESBTPSeanceCours::whereIn('id', $oldSeances)->forceDelete();
            }
            ESBTPEmploiTemps::where('titre', 'like', self::ET_PREFIX . '%')->forceDelete();

            $teacherCursor = 0;

            foreach ($cibles as $classe) {
                $planifs = $this->planifsDeClasse($annee, $classe)->take($maxMat);
                if ($planifs->isEmpty()) {
                    continue;
                }
                $semestre = (int) ($planifs->first()->semestre ?? 1);

                $emploi = ESBTPEmploiTemps::create([
                    'titre' => self::ET_PREFIX . $classe->name,
                    'classe_id' => $classe->id,
                    'annee_universitaire_id' => $annee->id,
                    'semestre' => $semestre,
                    'date_debut' => Carbon::now()->subWeeks($weeks + 1)->startOfWeek()->toDateString(),
                    'date_fin' => Carbon::now()->addWeeks(3)->toDateString(),
                    'is_active' => true,
                    'is_current' => false,
                ]);
                $stats['emplois']++;

                foreach ($planifs as $mIdx => $planif) {
                    // Enseignant : principal assigné, sinon round-robin du pool.
                    $teacher = $planif->enseignant_principal_id
                        ? $pool->firstWhere('user_id', $planif->enseignant_principal_id)
                        : null;
                    if (!$teacher) {
                        $teacher = $pool[$teacherCursor % $pool->count()];
                        $teacherCursor++;
                    }
                    $usedTeachers->put($teacher->id, $teacher);

                    // Types présents dans le planning de cette matière.
                    $types = [];
                    if (($planif->volume_horaire_cm ?? 0) > 0) $types[] = TypeSeance::CM;
                    if (($planif->volume_horaire_td ?? 0) > 0) $types[] = TypeSeance::TD;
                    if (($planif->volume_horaire_tp ?? 0) > 0) $types[] = TypeSeance::TP;
                    if (empty($types)) {
                        $types[] = TypeSeance::CM; // matière planifiée sans détail → CM par défaut
                    }

                    foreach ($types as $tIdx => $type) {
                        // 1 séance / semaine sur la fenêtre.
                        for ($w = 0; $w < $weeks; $w++) {
                            $date = Carbon::now()->subWeeks($w)->startOfWeek()
                                ->addDays(($mIdx + $tIdx) % 5); // lundi..vendredi
                            if ($date->isFuture()) {
                                continue;
                            }
                            $startStr = $startHours[($mIdx + $tIdx + $w) % count($startHours)];
                            $duration = $durations[($w + $tIdx) % count($durations)];
                            $debut = Carbon::parse($date->toDateString() . ' ' . $startStr);
                            $fin = (clone $debut)->addMinutes($duration);

                            $seance = ESBTPSeanceCours::create([
                                'emploi_temps_id' => $emploi->id,
                                'classe_id' => $classe->id,
                                'matiere_id' => $planif->matiere_id,
                                'teacher_id' => $teacher->id,
                                'jour' => (int) $date->dayOfWeekIso,
                                'heure_debut' => $debut->format('H:i:s'),
                                'heure_fin' => $fin->format('H:i:s'),
                                'salle' => 'DEMO-' . $type->value,
                                'description' => self::MARKER,
                                'type' => ESBTPSeanceCours::TYPE_COURSE,
                                'type_seance' => $type->value,
                                'is_active' => true,
                                'date_seance' => $date->toDateString(),
                                'annee_universitaire_id' => $annee->id,
                            ]);
                            $stats['seances']++;

                            // Émargement des séances de + de 2 semaines (passées/faites) ~75%.
                            $est2sPlus = $date->lt(Carbon::now()->subWeeks(2));
                            if ($est2sPlus && (($w + $mIdx + $tIdx) % 4 !== 0)) {
                                $status = (($w + $mIdx) % 6 === 0) ? 'late' : 'present';
                                ESBTPTeacherAttendance::create([
                                    'teacher_id' => $teacher->user_id,
                                    'course_id' => $seance->id,
                                    'date' => $date->toDateString(),
                                    'status' => $status,
                                    'type' => 'start',
                                    'attempts' => 1,
                                    'validated_at' => (clone $debut),
                                ]);
                                ESBTPTeacherAttendance::create([
                                    'teacher_id' => $teacher->user_id,
                                    'course_id' => $seance->id,
                                    'date' => $date->toDateString(),
                                    'status' => 'present',
                                    'type' => 'end',
                                    'attempts' => 1,
                                    'validated_at' => (clone $fin),
                                ]);
                                $stats['emargements'] += 2;
                            }
                        }
                    }
                }
            }

            // Taux horaires des enseignants utilisés (table esbtp_enseignant_taux_seance + défaut).
            foreach ($usedTeachers->values() as $i => $teacher) {
                $profile = self::TAUX_PROFILES[$i % count(self::TAUX_PROFILES)];
                $teacher->update(['taux_horaire' => $profile['def']]);
                foreach (['CM', 'TD', 'TP'] as $tp) {
                    ESBTPEnseignantTauxSeance::updateOrCreate(
                        ['teacher_id' => $teacher->id, 'type_seance' => $tp],
                        ['taux_horaire' => $profile[$tp]]
                    );
                    $stats['taux']++;
                }
            }
        });

        $this->info("✓ Emplois du temps démo : {$stats['emplois']}");
        $this->info("✓ Séances (suivant le planning) : {$stats['seances']}");
        $this->info("✓ Émargements (séances passées) : {$stats['emargements']}");
        $this->info("✓ Taux par type définis : {$stats['taux']} (sur " . $usedTeachers->count() . ' enseignants)');
        $this->newLine();
        $this->info('Enseignants dotés de taux :');
        foreach ($usedTeachers->values() as $teacher) {
            $this->line('  - ' . ($teacher->user->name ?? ('#' . $teacher->id)));
        }
        $this->newLine();
        $this->info('Testez : /esbtp/teacher-attendance/report (marquez les séances récentes) puis');
        $this->info('         /esbtp/comptabilite/salaires → Préparer un bulletin sur un de ces enseignants.');

        return self::SUCCESS;
    }

    /** Première classe d'un système (LMD/BTS) qui possède des planifications. */
    private function classeAvecPlanif(ESBTPAnneeUniversitaire $annee, string $systeme): ?ESBTPClasse
    {
        return ESBTPClasse::query()
            ->where('systeme_academique', $systeme)
            ->whereNotNull('filiere_id')
            ->whereNotNull('niveau_etude_id')
            ->get()
            ->first(fn ($c) => $this->planifsDeClasse($annee, $c)->isNotEmpty());
    }

    /** Planifications (planning horaire) d'une classe pour l'année. */
    private function planifsDeClasse(ESBTPAnneeUniversitaire $annee, ESBTPClasse $classe)
    {
        return ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('is_active', true)
            ->with('matiere:id,name')
            ->orderByDesc(DB::raw('(COALESCE(volume_horaire_cm,0)+COALESCE(volume_horaire_td,0)+COALESCE(volume_horaire_tp,0))'))
            ->get();
    }
}
